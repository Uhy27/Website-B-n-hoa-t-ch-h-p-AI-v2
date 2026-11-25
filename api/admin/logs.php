<?php
declare(strict_types=1);

/**
 * BLOOMORA ADMIN 2.0 - NHẬT KÝ HOẠT ĐỘNG HỆ THỐNG (AUDIT LOGS API)
 */
require_once __DIR__ . '/../../core/bootstrap.php';
require_admin_auth();

try {
    $pdo = getDBConnection();
    api_only_method('GET');

    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = max(1, min(100, (int)($_GET['limit'] ?? 25)));
    $offset = ($page - 1) * $limit;
    $module = trim((string)($_GET['module'] ?? ''));
    $search = trim((string)($_GET['search'] ?? ''));

    $where = [];
    $params = [];

    if ($module !== '' && $module !== 'all') {
        $where[] = 'l.module = :mod';
        $params[':mod'] = $module;
    }

    if ($search !== '') {
        $where[] = '(l.action LIKE :s1 OR l.details LIKE :s2 OR u.full_name LIKE :s3)';
        $params[':s1'] = "%$search%";
        $params[':s2'] = "%$search%";
        $params[':s3'] = "%$search%";
    }

    $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    $cStmt = $pdo->prepare("SELECT COUNT(*) FROM activity_logs l LEFT JOIN users u ON u.id = l.user_id $whereSql");
    $cStmt->execute($params);
    $total = (int)$cStmt->fetchColumn();

    $sql = "
        SELECT 
            l.id, l.user_id, l.action, l.module, l.entity_id, l.details, l.ip_address, l.created_at,
            u.full_name AS user_name, u.email AS user_email, r.name AS role_name
        FROM activity_logs l
        LEFT JOIN users u ON u.id = l.user_id
        LEFT JOIN roles r ON r.id = u.role_id
        $whereSql
        ORDER BY l.id DESC
        LIMIT :limit OFFSET :offset
    ";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    json_response([
        'success' => true,
        'data' => [
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'items' => $logs
        ]
    ]);
} catch (Throwable $e) {
    error_log('[Admin Logs Error] ' . $e->getMessage());
    json_response(['success' => false, 'message' => 'Lỗi hệ thống khi tải nhật ký hoạt động.'], 500);
}
