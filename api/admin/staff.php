<?php
declare(strict_types=1);

/**
 * BLOOMORA ADMIN 2.0 - QUẢN LÝ NHÂN SỰ & PHÂN QUYỀN (STAFF & RBAC API)
 */
require_once __DIR__ . '/../../core/bootstrap.php';
require_admin_auth(['admin', 'manager']); // Chỉ Admin và Manager mới có quyền quản lý nhân sự

try {
    $pdo = getDBConnection();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'GET') {
        // Lấy danh sách vai trò nội bộ
        $rolesStmt = $pdo->query("SELECT id, name, description FROM roles WHERE id != 3 ORDER BY id ASC");
        $roles = $rolesStmt->fetchAll(PDO::FETCH_ASSOC);

        // Lấy danh sách nhân sự
        $staffStmt = $pdo->query("
            SELECT u.id, u.role_id, r.name AS role_name, r.description AS role_desc,
                   u.email, u.full_name, u.phone, u.avatar, u.status, u.created_at, u.updated_at
            FROM users u
            INNER JOIN roles r ON u.role_id = r.id
            WHERE u.role_id != 3
            ORDER BY u.role_id ASC, u.id ASC
        ");
        $staffList = $staffStmt->fetchAll(PDO::FETCH_ASSOC);

        json_response([
            'success' => true,
            'roles' => $roles,
            'data' => $staffList
        ]);
    }

    if ($method === 'POST') {
        $in = get_json_input();
        $adminId = (int)($_SESSION['user_id'] ?? 0);

        foreach (['full_name', 'email', 'password', 'role_id'] as $f) {
            if (!isset($in[$f]) || trim((string)$in[$f]) === '') {
                json_response(['success' => false, 'message' => "Trường '$f' là bắt buộc."], 400);
            }
        }

        $email = strtolower(trim((string)$in['email']));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_response(['success' => false, 'message' => 'Email không đúng định dạng.'], 400);
        }

        // Kiểm tra email tồn tại
        $chk = $pdo->prepare('SELECT COUNT(*) FROM users WHERE LOWER(email) = :e');
        $chk->execute([':e' => $email]);
        if ((int)$chk->fetchColumn() > 0) {
            json_response(['success' => false, 'message' => 'Email này đã được sử dụng.'], 409);
        }

        $roleId = (int)$in['role_id'];
        $passHash = password_hash((string)$in['password'], PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            INSERT INTO users (role_id, email, password_hash, full_name, phone, status, created_at)
            VALUES (:r, :e, :p, :fn, :ph, :st, NOW())
        ");
        $stmt->execute([
            ':r' => $roleId,
            ':e' => $email,
            ':p' => $passHash,
            ':fn' => trim((string)$in['full_name']),
            ':ph' => trim((string)($in['phone'] ?? '')),
            ':st' => $in['status'] ?? 'active'
        ]);

        $newId = (int)$pdo->lastInsertId();
        log_activity($adminId, 'create_staff', 'staff', $newId, "Tạo tài khoản nhân viên mới: {$in['full_name']} ($email, Role ID: $roleId)");

        json_response(['success' => true, 'message' => 'Tạo tài khoản nhân viên thành công!', 'data' => ['id' => $newId]], 201);
    }

    if ($method === 'PUT') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            json_response(['success' => false, 'message' => 'Thiếu ID nhân viên.'], 400);
        }

        $chk = $pdo->prepare('SELECT * FROM users WHERE id = :id AND role_id != 3');
        $chk->execute([':id' => $id]);
        $user = $chk->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            json_response(['success' => false, 'message' => 'Không tìm thấy tài khoản nhân sự.'], 404);
        }

        $in = get_json_input();
        $adminId = (int)($_SESSION['user_id'] ?? 0);

        // Cấm tự hạ quyền hoặc tự khóa chính mình
        if ($adminId === $id && isset($in['status']) && $in['status'] !== 'active') {
            json_response(['success' => false, 'message' => 'Không thể tự vô hiệu hóa tài khoản đang đăng nhập.'], 400);
        }

        $sets = [];
        $params = [':id' => $id];

        if (!empty($in['full_name'])) {
            $sets[] = "`full_name` = :fn";
            $params[':fn'] = trim((string)$in['full_name']);
        }

        if (isset($in['phone'])) {
            $sets[] = "`phone` = :ph";
            $params[':ph'] = trim((string)$in['phone']);
        }

        if (!empty($in['role_id']) && (int)$in['role_id'] != 3) {
            $sets[] = "`role_id` = :rid";
            $params[':rid'] = (int)$in['role_id'];
        }

        if (isset($in['status']) && in_array($in['status'], ['active', 'inactive', 'banned'], true)) {
            $sets[] = "`status` = :st";
            $params[':st'] = $in['status'];
        }

        if (!empty($in['new_password'])) {
            $sets[] = "`password_hash` = :pw";
            $params[':pw'] = password_hash((string)$in['new_password'], PASSWORD_DEFAULT);
        }

        if (empty($sets)) {
            json_response(['success' => false, 'message' => 'Không có thông tin thay đổi.'], 400);
        }

        $pdo->prepare('UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = :id')->execute($params);

        log_activity($adminId, 'update_staff', 'staff', $id, "Cập nhật tài khoản nhân sự: {$user['full_name']} ({$user['email']})");

        json_response(['success' => true, 'message' => 'Cập nhật tài khoản nhân sự thành công!']);
    }

    if ($method === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            json_response(['success' => false, 'message' => 'Thiếu ID nhân sự.'], 400);
        }

        $adminId = (int)($_SESSION['user_id'] ?? 0);
        if ($adminId === $id) {
            json_response(['success' => false, 'message' => 'Không thể xóa chính tài khoản đang đăng nhập.'], 400);
        }

        // Đổi trạng thái sang inactive thay vì xóa vĩnh viễn để bảo toàn foreign keys đơn hàng
        $pdo->prepare("UPDATE users SET status = 'inactive' WHERE id = :id AND role_id != 3")->execute([':id' => $id]);
        log_activity($adminId, 'deactivate_staff', 'staff', $id, "Vô hiệu hóa tài khoản nhân viên ID $id");

        json_response(['success' => true, 'message' => 'Đã khóa tài khoản nhân sự thành công.']);
    }

    json_response(['success' => false, 'message' => 'Phương thức HTTP không được hỗ trợ.'], 405);
} catch (Throwable $e) {
    error_log('[Admin Staff Error] ' . $e->getMessage());
    json_response(['success' => false, 'message' => 'Lỗi hệ thống quản lý nhân sự.'], 500);
}
