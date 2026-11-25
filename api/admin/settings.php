<?php
declare(strict_types=1);

/**
 * BLOOMORA ADMIN 2.0 - CÀI ĐẶT CỬA HÀNG & KÊNH THANH TOÁN (STORE SETTINGS API)
 */
require_once __DIR__ . '/../../core/bootstrap.php';
require_admin_auth(['admin', 'manager']);

try {
    $pdo = getDBConnection();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'GET') {
        $stmt = $pdo->query("SELECT key_name, key_value, description FROM store_settings");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $settings = [];
        foreach ($rows as $r) {
            $settings[$r['key_name']] = [
                'value' => $r['key_value'],
                'description' => $r['description']
            ];
        }
        json_response(['success' => true, 'data' => $settings]);
    }

    if ($method === 'POST' || $method === 'PUT') {
        $input = get_json_input();
        $adminId = (int)($_SESSION['user_id'] ?? 0);

        if (empty($input)) {
            json_response(['success' => false, 'message' => 'Không có dữ liệu cài đặt.'], 400);
        }

        $stmt = $pdo->prepare("
            INSERT INTO store_settings (key_name, key_value) 
            VALUES (:k, :v) 
            ON DUPLICATE KEY UPDATE key_value = VALUES(key_value)
        ");

        foreach ($input as $k => $v) {
            $stmt->execute([
                ':k' => sanitize_string((string)$k, 100),
                ':v' => (string)$v
            ]);
        }

        log_activity($adminId, 'update_settings', 'settings', null, 'Cập nhật cấu hình cửa hàng Bloomora');

        json_response(['success' => true, 'message' => 'Lưu cài đặt cửa hàng thành công!']);
    }

    json_response(['success' => false, 'message' => 'Phương thức HTTP không được hỗ trợ.'], 405);
} catch (Throwable $e) {
    error_log('[Admin Settings Error] ' . $e->getMessage());
    json_response(['success' => false, 'message' => 'Lỗi lưu thông tin cấu hình cửa hàng.'], 500);
}
