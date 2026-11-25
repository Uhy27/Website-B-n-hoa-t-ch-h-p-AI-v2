<?php
declare(strict_types=1);

/**
 * BLOOMORA ADMIN 2.0 - ĐIỀU PHỐI CẮM HOA, KIỂM TRA CHẤT LƯỢNG (QC) & GIAO HÀNG (DELIVERY & QC API)
 */
require_once __DIR__ . '/../../core/bootstrap.php';
require_admin_auth();

try {
    $pdo = getDBConnection();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'GET') {
        $filterStatus = trim((string)($_GET['stage'] ?? ''));
        $where = [];
        $params = [];

        if ($filterStatus === 'florist') {
            // Các đơn đang cần cắm hoặc đang cắm
            $where[] = "o.order_status IN ('received', 'preparing')";
        } elseif ($filterStatus === 'qc') {
            // Các đơn cắm xong chờ duyệt ảnh QC
            $where[] = "o.order_status = 'photo_ready'";
        } elseif ($filterStatus === 'shipping') {
            // Các đơn đang trên đường giao
            $where[] = "o.order_status = 'delivering'";
        } elseif ($filterStatus === 'completed') {
            $where[] = "o.order_status = 'completed'";
        } else {
            // Mặc định: các đơn đang trong chu kỳ hoạt động
            $where[] = "o.order_status IN ('received', 'preparing', 'photo_ready', 'delivering')";
        }

        $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "
            SELECT 
                o.id, o.order_code, o.sender_name, o.sender_phone, o.receiver_name, o.receiver_phone,
                o.receiver_address, o.city, o.delivery_date, o.delivery_time_slot,
                o.card_message, o.ribbon_message, o.total_amount, o.payment_method, o.payment_status,
                o.order_status, o.photo_proof, o.created_at,
                o.assigned_florist_id, o.assigned_shipper_id,
                u_f.full_name AS florist_name,
                u_s.full_name AS shipper_name,
                qc.flower_freshness, qc.color_accuracy, qc.stem_count_matched,
                qc.wrapping_standard, qc.card_message_verified, qc.notes AS qc_notes,
                qc.created_at AS qc_time
            FROM orders o
            LEFT JOIN users u_f ON u_f.id = o.assigned_florist_id
            LEFT JOIN users u_s ON u_s.id = o.assigned_shipper_id
            LEFT JOIN (
                SELECT * FROM order_quality_checks WHERE id IN (
                    SELECT MAX(id) FROM order_quality_checks GROUP BY order_id
                )
            ) qc ON qc.order_id = o.id
            $whereSql
            ORDER BY o.delivery_date ASC, o.id DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Lấy danh sách sản phẩm trong từng đơn
        if (!empty($orders)) {
            $ids = array_column($orders, 'id');
            $ph = implode(',', array_fill(0, count($ids), '?'));
            $it = $pdo->prepare("SELECT order_id, product_id, product_name, product_sku, unit_price, quantity, total_price FROM order_items WHERE order_id IN ($ph)");
            $it->execute($ids);
            $itemsByOrder = [];
            foreach ($it->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $itemsByOrder[$row['order_id']][] = $row;
            }
            foreach ($orders as &$ord) {
                $ord['items'] = $itemsByOrder[$ord['id']] ?? [];
            }
        }

        // Lấy danh sách Florist & Shipper đang hoạt động để phân công
        $staffStmt = $pdo->query("
            SELECT u.id, u.full_name, u.phone, r.name AS role_name
            FROM users u
            INNER JOIN roles r ON u.role_id = r.id
            WHERE u.status = 'active' AND r.name IN ('florist', 'staff', 'shipper')
            ORDER BY u.full_name ASC
        ");
        $availableStaff = $staffStmt->fetchAll(PDO::FETCH_ASSOC);

        json_response([
            'success' => true,
            'data' => [
                'orders' => $orders,
                'staff' => $availableStaff
            ]
        ]);
    }

    if ($method === 'POST') {
        $input = get_json_input();
        $action = trim((string)($input['action'] ?? ''));
        $orderId = (int)($input['orderId'] ?? 0);
        $adminId = (int)($_SESSION['user_id'] ?? 0);

        if ($orderId <= 0) {
            json_response(['success' => false, 'message' => 'Mã đơn hàng không hợp lệ.'], 400);
        }

        $oStmt = $pdo->prepare("SELECT * FROM orders WHERE id = :id");
        $oStmt->execute([':id' => $orderId]);
        $order = $oStmt->fetch(PDO::FETCH_ASSOC);
        if (!$order) {
            json_response(['success' => false, 'message' => 'Đơn hàng không tồn tại.'], 404);
        }

        if ($action === 'assign_staff') {
            $floristId = isset($input['floristId']) && (int)$input['floristId'] > 0 ? (int)$input['floristId'] : null;
            $shipperId = isset($input['shipperId']) && (int)$input['shipperId'] > 0 ? (int)$input['shipperId'] : null;

            $pdo->prepare("UPDATE orders SET assigned_florist_id = :fid, assigned_shipper_id = :sid WHERE id = :id")->execute([
                ':fid' => $floristId,
                ':sid' => $shipperId,
                ':id' => $orderId
            ]);

            log_activity($adminId, 'assign_staff', 'orders', $orderId, "Phân công xử lý đơn #{$order['order_code']} (Florist ID: $floristId, Shipper ID: $shipperId)");
            json_response(['success' => true, 'message' => 'Phân công nhân sự thành công.']);
        }

        if ($action === 'submit_qc') {
            // Ghi nhận kiểm định chất lượng (QC) và đính kèm Photo Proof
            $freshness = !empty($input['flower_freshness']) ? 1 : 0;
            $color = !empty($input['color_accuracy']) ? 1 : 0;
            $stems = !empty($input['stem_count_matched']) ? 1 : 0;
            $wrap = !empty($input['wrapping_standard']) ? 1 : 0;
            $card = !empty($input['card_message_verified']) ? 1 : 0;
            $photoProof = trim((string)($input['photo_proof_url'] ?? ''));
            $notes = trim((string)($input['notes'] ?? ''));

            if ($photoProof === '' && !empty($order['photo_proof'])) {
                $photoProof = $order['photo_proof'];
            }

            $pdo->beginTransaction();

            $qcStmt = $pdo->prepare("
                INSERT INTO order_quality_checks (order_id, checked_by, flower_freshness, color_accuracy, stem_count_matched, wrapping_standard, card_message_verified, photo_proof_url, notes, created_at)
                VALUES (:oid, :uid, :fresh, :color, :stems, :wrap, :card, :photo, :notes, NOW())
            ");
            $qcStmt->execute([
                ':oid' => $orderId,
                ':uid' => $adminId,
                ':fresh' => $freshness,
                ':color' => $color,
                ':stems' => $stems,
                ':wrap' => $wrap,
                ':card' => $card,
                ':photo' => $photoProof !== '' ? $photoProof : null,
                ':notes' => $notes !== '' ? $notes : 'Đạt tiêu chuẩn kiểm định chất lượng hoa tươi'
            ]);

            // Cập nhật trạng thái đơn thành photo_ready (Đã cắm & chụp ảnh thành phẩm)
            $pdo->prepare("UPDATE orders SET order_status = 'photo_ready', photo_proof = :photo WHERE id = :id")->execute([
                ':photo' => $photoProof !== '' ? $photoProof : null,
                ':id' => $orderId
            ]);

            $pdo->prepare("INSERT INTO order_status_history (order_id, status, note, created_by) VALUES (:oid, 'photo_ready', :note, :uid)")
                ->execute([
                    ':oid' => $orderId,
                    ':note' => 'Đã hoàn thành cắm hoa và đạt kiểm định QC thành phẩm',
                    ':uid' => $adminId
                ]);

            log_activity($adminId, 'submit_qc', 'quality_check', $orderId, "Đã duyệt QC và lưu ảnh mẫu hoa cho đơn #{$order['order_code']}");
            $pdo->commit();

            json_response(['success' => true, 'message' => 'Kiểm định chất lượng thành phẩm thành công. Đơn hàng sẵn sàng giao!']);
        }

        if ($action === 'start_delivery') {
            // Chuyển sang đang giao hàng
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE orders SET order_status = 'delivering' WHERE id = :id")->execute([':id' => $orderId]);
            $pdo->prepare("INSERT INTO order_status_history (order_id, status, note, created_by) VALUES (:oid, 'delivering', 'Shipper đã nhận hoa và bắt đầu giao đến địa chỉ khách hàng', :uid)")
                ->execute([':oid' => $orderId, ':uid' => $adminId]);
            log_activity($adminId, 'start_delivery', 'orders', $orderId, "Bàn giao cho Shipper xuất phát giao đơn #{$order['order_code']}");
            $pdo->commit();

            json_response(['success' => true, 'message' => 'Đã chuyển đơn hàng sang trạng thái đang giao.']);
        }

        if ($action === 'complete_delivery') {
            // Hoàn thành đơn hàng
            $pdo->beginTransaction();
            $upSql = "UPDATE orders SET order_status = 'completed'";
            if ($order['payment_method'] === 'cod') {
                $upSql .= ", payment_status = 'paid'";
            }
            $upSql .= " WHERE id = :id";
            $pdo->prepare($upSql)->execute([':id' => $orderId]);

            $pdo->prepare("INSERT INTO order_status_history (order_id, status, note, created_by) VALUES (:oid, 'completed', 'Giao hoa thành công đến tay người nhận. Đơn hàng hoàn tất.', :uid)")
                ->execute([':oid' => $orderId, ':uid' => $adminId]);

            log_activity($adminId, 'complete_delivery', 'orders', $orderId, "Giao thành công đơn #{$order['order_code']}");
            $pdo->commit();

            json_response(['success' => true, 'message' => 'Giao hàng thành công! Đơn hàng đã hoàn tất.']);
        }

        json_response(['success' => false, 'message' => 'Hành động không hợp lệ.'], 400);
    }

    json_response(['success' => false, 'message' => 'Phương thức HTTP không được hỗ trợ.'], 405);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[Admin Delivery Error] ' . $e->getMessage());
    json_response(['success' => false, 'message' => 'Lỗi hệ thống điều phối giao hoa.'], 500);
}
