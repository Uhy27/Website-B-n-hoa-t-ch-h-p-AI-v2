<?php
declare(strict_types=1);

/**
 * BLOOMORA ADMIN 2.0 - QUẢN LÝ THANH TOÁN & ĐỐI SOÁT (PAYMENT & RECONCILIATION API)
 */
require_once __DIR__ . '/../../core/bootstrap.php';
require_admin_auth();

try {
    $pdo = getDBConnection();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'GET') {
        $methodFilter = trim((string)($_GET['payment_method'] ?? ''));
        $statusFilter = trim((string)($_GET['payment_status'] ?? ''));
        $search = trim((string)($_GET['search'] ?? ''));
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $where = [];
        $params = [];

        if ($methodFilter !== '' && $methodFilter !== 'all') {
            $where[] = 'o.payment_method = :pm';
            $params[':pm'] = $methodFilter;
        }

        if ($statusFilter !== '' && $statusFilter !== 'all') {
            $where[] = 'o.payment_status = :ps';
            $params[':ps'] = $statusFilter;
        }

        if ($search !== '') {
            $where[] = '(o.order_code LIKE :s1 OR o.sender_name LIKE :s2 OR o.receiver_name LIKE :s3 OR o.receiver_phone LIKE :s4)';
            $params[':s1'] = "%$search%";
            $params[':s2'] = "%$search%";
            $params[':s3'] = "%$search%";
            $params[':s4'] = "%$search%";
        }

        $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Đếm tổng số bản ghi
        $cStmt = $pdo->prepare("SELECT COUNT(*) FROM orders o $whereSql");
        $cStmt->execute($params);
        $total = (int)$cStmt->fetchColumn();

        // Tổng hợp số liệu tài chính nhanh
        $statsStmt = $pdo->query("
            SELECT 
                COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END), 0) AS total_collected,
                COALESCE(SUM(CASE WHEN payment_method = 'cod' AND payment_status = 'unpaid' AND order_status != 'cancelled' THEN total_amount ELSE 0 END), 0) AS pending_cod,
                COALESCE(SUM(CASE WHEN payment_status = 'refunded' THEN total_amount ELSE 0 END), 0) AS total_refunded,
                COUNT(CASE WHEN payment_status = 'paid' THEN 1 END) AS count_paid,
                COUNT(CASE WHEN payment_status = 'unpaid' AND order_status != 'cancelled' THEN 1 END) AS count_unpaid
            FROM orders
        ");
        $paymentSummary = $statsStmt->fetch(PDO::FETCH_ASSOC);

        // Danh sách đơn hàng & giao dịch
        $sql = "
            SELECT 
                o.id, o.order_code, o.sender_name, o.sender_phone, o.receiver_name, o.receiver_phone,
                o.subtotal, o.shipping_fee, o.discount_amount, o.total_amount,
                o.payment_method, o.payment_status, o.order_status, o.created_at,
                o.voucher_code
            FROM orders o
            $whereSql
            ORDER BY o.id DESC
            LIMIT :limit OFFSET :offset
        ";
        $stmt = $pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        json_response([
            'success' => true,
            'summary' => $paymentSummary,
            'data' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'items' => $transactions
            ]
        ]);
    }

    if ($method === 'POST') {
        $input = get_json_input();
        $action = trim((string)($input['action'] ?? ''));
        $orderId = (int)($input['orderId'] ?? 0);
        $adminId = (int)($_SESSION['user_id'] ?? 0);
        $note = trim((string)($input['note'] ?? ''));

        if ($orderId <= 0) {
            json_response(['success' => false, 'message' => 'Mã đơn hàng không hợp lệ.'], 400);
        }

        $oStmt = $pdo->prepare("SELECT * FROM orders WHERE id = :id");
        $oStmt->execute([':id' => $orderId]);
        $order = $oStmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            json_response(['success' => false, 'message' => 'Đơn hàng không tồn tại.'], 404);
        }

        if ($action === 'verify_payment') {
            // Xác nhận đã nhận tiền (Ví dụ: VietQR báo có, MoMo đã nhận, hoặc Shipper đã thu COD)
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE orders SET payment_status = 'paid' WHERE id = :id")->execute([':id' => $orderId]);
            $pdo->prepare("INSERT INTO order_status_history (order_id, status, note, created_by) VALUES (:oid, :st, :note, :uid)")
                ->execute([
                    ':oid' => $orderId,
                    ':st' => $order['order_status'],
                    ':note' => 'Xác nhận thanh toán thủ công: ' . ($note !== '' ? $note : 'Đã đối soát khớp tiền'),
                    ':uid' => $adminId
                ]);
            log_activity($adminId, 'verify_payment', 'payments', $orderId, "Xác thực đã thu tiền cho đơn #{$order['order_code']} ({$order['payment_method']}): {$order['total_amount']}₫");
            $pdo->commit();

            json_response(['success' => true, 'message' => "Đã xác nhận thanh toán đơn {$order['order_code']} thành công!"]);
        }

        if ($action === 'refund_payment') {
            // Hoàn tiền giao dịch
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE orders SET payment_status = 'refunded' WHERE id = :id")->execute([':id' => $orderId]);
            $pdo->prepare("INSERT INTO order_status_history (order_id, status, note, created_by) VALUES (:oid, :st, :note, :uid)")
                ->execute([
                    ':oid' => $orderId,
                    ':st' => $order['order_status'],
                    ':note' => 'Ghi nhận hoàn tiền: ' . ($note !== '' ? $note : 'Đã xử lý hoàn trả tiền cho khách'),
                    ':uid' => $adminId
                ]);
            log_activity($adminId, 'refund_payment', 'payments', $orderId, "Hoàn tiền cho đơn #{$order['order_code']} ({$order['total_amount']}₫). Lý do: $note");
            $pdo->commit();

            json_response(['success' => true, 'message' => "Đã chuyển trạng thái hoàn tiền cho đơn {$order['order_code']}."]);
        }

        json_response(['success' => false, 'message' => 'Hành động không được hỗ trợ.'], 400);
    }

    json_response(['success' => false, 'message' => 'Phương thức HTTP không được hỗ trợ.'], 405);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[Admin Payments Error] ' . $e->getMessage());
    json_response(['success' => false, 'message' => 'Lỗi hệ thống đối soát thanh toán.'], 500);
}
