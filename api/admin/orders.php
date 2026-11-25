<?php
declare(strict_types=1);
/**
 * Admin Orders API - Bloomora Admin 2.0
 * List, filter, view details, print invoice data & update status with safe stock/voucher rollback on cancel
 */
require_once __DIR__ . '/../../core/bootstrap.php';
require_admin_auth();

try {
    $pdo = getDBConnection();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Chi tiết 1 đơn hàng cụ thể (kèm lịch sử tiến trình & QC)
        $singleId = (int)($_GET['id'] ?? 0);
        if ($singleId > 0) {
            $stmt = $pdo->prepare("
                SELECT o.*, u_f.full_name AS florist_name, u_s.full_name AS shipper_name
                FROM orders o
                LEFT JOIN users u_f ON u_f.id = o.assigned_florist_id
                LEFT JOIN users u_s ON u_s.id = o.assigned_shipper_id
                WHERE o.id = :id
            ");
            $stmt->execute([':id' => $singleId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$order) {
                json_response(['success' => false, 'message' => 'Không tìm thấy đơn hàng.'], 404);
            }

            // Lấy danh sách sản phẩm trong đơn
            $itStmt = $pdo->prepare("
                SELECT oi.*, p.thumbnail, p.primary_flower, p.color_name
                FROM order_items oi
                LEFT JOIN products p ON p.id = oi.product_id
                WHERE oi.order_id = :oid
            ");
            $itStmt->execute([':oid' => $singleId]);
            $order['items'] = $itStmt->fetchAll(PDO::FETCH_ASSOC);

            // Lịch sử trạng thái đơn
            $hStmt = $pdo->prepare("
                SELECT h.*, u.full_name AS created_by_name
                FROM order_status_history h
                LEFT JOIN users u ON u.id = h.created_by
                WHERE h.order_id = :oid
                ORDER BY h.id ASC
            ");
            $hStmt->execute([':oid' => $singleId]);
            $order['history'] = $hStmt->fetchAll(PDO::FETCH_ASSOC);

            // Kiểm định chất lượng QC gần nhất
            $qcStmt = $pdo->prepare("
                SELECT qc.*, u.full_name AS checker_name
                FROM order_quality_checks qc
                LEFT JOIN users u ON u.id = qc.checked_by
                WHERE qc.order_id = :oid
                ORDER BY qc.id DESC LIMIT 1
            ");
            $qcStmt->execute([':oid' => $singleId]);
            $order['quality_check'] = $qcStmt->fetch(PDO::FETCH_ASSOC) ?: null;

            json_response(['success' => true, 'data' => $order]);
        }

        // Lọc danh sách đơn hàng
        $status = isset($_GET['status']) ? trim((string)$_GET['status']) : '';
        $paymentStatus = isset($_GET['payment_status']) ? trim((string)$_GET['payment_status']) : '';
        $paymentMethod = isset($_GET['payment_method']) ? trim((string)$_GET['payment_method']) : '';
        $search = isset($_GET['search']) ? trim((string)$_GET['search']) : '';
        $deliveryDate = isset($_GET['delivery_date']) ? trim((string)$_GET['delivery_date']) : '';

        $where = [];
        $params = [];

        if ($status !== '' && $status !== 'all') {
            $where[] = 'o.order_status = :status';
            $params[':status'] = $status;
        }

        if ($paymentStatus !== '' && $paymentStatus !== 'all') {
            $where[] = 'o.payment_status = :pstatus';
            $params[':pstatus'] = $paymentStatus;
        }

        if ($paymentMethod !== '' && $paymentMethod !== 'all') {
            $where[] = 'o.payment_method = :pmethod';
            $params[':pmethod'] = $paymentMethod;
        }

        if ($deliveryDate !== '') {
            $where[] = 'o.delivery_date = :ddate';
            $params[':ddate'] = $deliveryDate;
        }

        if ($search !== '') {
            $where[] = '(o.order_code LIKE :s1 OR o.sender_name LIKE :s2 OR o.receiver_name LIKE :s3 OR o.receiver_phone LIKE :s4)';
            $params[':s1'] = "%$search%";
            $params[':s2'] = "%$search%";
            $params[':s3'] = "%$search%";
            $params[':s4'] = "%$search%";
        }

        $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "
            SELECT o.id, o.order_code, o.user_id, o.sender_name, o.sender_phone, o.is_anonymous,
                   o.receiver_name, o.receiver_phone, o.receiver_address, o.city,
                   o.delivery_date, o.delivery_time_slot, o.card_message, o.ribbon_message,
                   o.subtotal, o.shipping_fee, o.discount_amount, o.total_amount, 
                   o.payment_method, o.payment_status, o.order_status, o.photo_proof, o.created_at,
                   o.assigned_florist_id, o.assigned_shipper_id,
                   u_f.full_name AS florist_name, u_s.full_name AS shipper_name
            FROM orders o
            LEFT JOIN users u_f ON u_f.id = o.assigned_florist_id
            LEFT JOIN users u_s ON u_s.id = o.assigned_shipper_id
            $whereSql 
            ORDER BY o.id DESC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($orders)) {
            $ids = array_column($orders, 'id');
            $ph = implode(',', array_fill(0, count($ids), '?'));
            $it = $pdo->prepare("SELECT order_id, product_id, product_name, product_sku, unit_price, quantity, total_price FROM order_items WHERE order_id IN ($ph)");
            $it->execute($ids);
            $by = [];
            foreach ($it->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $by[$row['order_id']][] = $row;
            }
            foreach ($orders as &$o) {
                $o['items'] = $by[$o['id']] ?? [];
                $o['voucher_code'] = $o['voucher_code'] ?? null;
            }
        }

        json_response(['success' => true, 'count' => count($orders), 'data' => $orders]);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = get_json_input();
        $orderId = (int)($input['orderId'] ?? 0);
        $newStatus = trim((string)($input['status'] ?? ''));
        $photoProof = array_key_exists('photoProof', $input) ? trim((string)$input['photoProof']) : null;
        $note = trim((string)($input['note'] ?? 'Cập nhật trạng thái bởi quản trị viên'));
        $adminId = (int)($_SESSION['user_id'] ?? 0);
        $valid = ['received', 'preparing', 'photo_ready', 'delivering', 'completed', 'cancelled'];

        if ($orderId <= 0 || !in_array($newStatus, $valid, true)) {
            json_response(['success' => false, 'message' => 'Mã đơn hàng hoặc trạng thái không hợp lệ.'], 400);
        }

        $pdo->beginTransaction();
        $oStmt = $pdo->prepare("SELECT * FROM orders WHERE id = :id FOR UPDATE");
        $oStmt->execute([':id' => $orderId]);
        $order = $oStmt->fetch(PDO::FETCH_ASSOC);
        if (!$order) {
            $pdo->rollBack();
            json_response(['success' => false, 'message' => 'Đơn hàng không tồn tại.'], 404);
        }
        $oldStatus = $order['order_status'];

        $updateSql = "UPDATE orders SET order_status = :status";
        $params = [':status' => $newStatus, ':id' => $orderId];

        if ($photoProof !== null && $photoProof !== '') {
            $updateSql .= ", photo_proof = :photo";
            $params[':photo'] = $photoProof;
        }

        if ($newStatus === 'completed' && ($order['payment_method'] ?? '') === 'cod') {
            $updateSql .= ", payment_status = 'paid'";
        }

        $updateSql .= " WHERE id = :id";
        $pdo->prepare($updateSql)->execute($params);

        // Khi hủy đơn: hoàn kho tự động và hoàn trả voucher
        if ($newStatus === 'cancelled' && $oldStatus !== 'cancelled') {
            $itemsStmt = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = :oid");
            $itemsStmt->execute([':oid' => $orderId]);
            $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

            $invUpdate = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity + :q WHERE id = :pid");
            $invLog = $pdo->prepare("INSERT INTO inventory_logs (product_id, change_quantity, remaining_quantity, reason, order_id, user_id, note, created_at) VALUES (:pid, :chg, :rem, 'cancel_refund', :oid, :uid, :note, NOW())");
            $remSel = $pdo->prepare("SELECT stock_quantity FROM products WHERE id = :pid");

            foreach ($items as $it) {
                $invUpdate->execute([':q' => (int)$it['quantity'], ':pid' => (int)$it['product_id']]);
                try {
                    $remSel->execute([':pid' => (int)$it['product_id']]);
                    $rem = (int)$remSel->fetchColumn();
                    $invLog->execute([
                        ':pid' => (int)$it['product_id'],
                        ':chg' => (int)$it['quantity'],
                        ':rem' => $rem,
                        ':oid' => $orderId,
                        ':uid' => $adminId,
                        ':note' => 'Hoàn kho do hủy đơn ' . $order['order_code']
                    ]);
                } catch (Throwable $ignore) {}
            }

            // Hoàn lại voucher nếu đã áp dụng
            try {
                $vu = $pdo->prepare("SELECT vu.id, vu.voucher_id, vu.discount_amount, v.campaign_id FROM voucher_usages vu LEFT JOIN vouchers v ON v.id = vu.voucher_id WHERE vu.order_id = :oid AND vu.status = 'used'");
                $vu->execute([':oid' => $orderId]);
                foreach ($vu->fetchAll(PDO::FETCH_ASSOC) as $u) {
                    $pdo->prepare("UPDATE voucher_usages SET status = 'refunded' WHERE id = :id")->execute([':id' => $u['id']]);
                    $pdo->prepare("UPDATE vouchers SET used_count = GREATEST(used_count - 1, 0) WHERE id = :vid")->execute([':vid' => $u['voucher_id']]);
                    if (!empty($u['campaign_id']) && class_exists('BudgetService')) {
                        BudgetService::refundBudget($pdo, (int)$u['campaign_id'], $orderId, (float)$u['discount_amount']);
                    }
                }
            } catch (Throwable $ignore) {}
        }

        $pdo->prepare("INSERT INTO order_status_history (order_id, status, note, created_by) VALUES (:oid, :st, :note, :uid)")
            ->execute([':oid' => $orderId, ':st' => $newStatus, ':note' => $note, ':uid' => $adminId]);

        log_activity($adminId, 'update_order_status', 'orders', $orderId, "Cập nhật đơn #{$order['order_code']}: $oldStatus -> $newStatus. Ghi chú: $note");

        $pdo->commit();
        json_response(['success' => true, 'message' => 'Cập nhật trạng thái đơn hàng thành công.']);
    }

    json_response(['success' => false, 'message' => 'Phương thức HTTP không được hỗ trợ.'], 405);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[Admin Orders Error] ' . $e->getMessage());
    json_response(['success' => false, 'message' => 'Lỗi xử lý đơn hàng quản trị.'], 500);
}
