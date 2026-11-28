<?php
declare(strict_types=1);

/**
 * Bloomora - Core helpers (session, auth, json, input)
 */

if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}

function bloomora_env(string $key, $default = null) {
    $v = getenv($key);
    return $v !== false ? $v : $default;
}

function get_json_input(): array {
    $raw = file_get_contents('php://input');
    if (!empty($raw)) {
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);
        $data = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            return $data;
        }
    }
    return !empty($_POST) ? $_POST : [];
}

function init_secure_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443);
        session_set_cookie_params([
            'lifetime' => 86400 * 7,
            'path' => '/',
            'domain' => '',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        session_start();
    }
}

function require_admin_auth(array $roles = ['admin', 'staff', 'manager', 'florist', 'shipper', 'warehouse']): void {
    init_secure_session();
    $role = $_SESSION['role_name'] ?? '';
    if (empty($_SESSION['user_id']) || !in_array($role, $roles, true)) {
        json_response([
            'success' => false,
            'message' => 'Yêu cầu quyền truy cập quản trị. Vui lòng đăng nhập.'
        ], 401);
    }
}

function log_activity(?int $userId, string $action, string $module, ?int $entityId = null, ?string $details = null): void {
    try {
        $pdo = getDBConnection();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, module, entity_id, details, ip_address, created_at) VALUES (:uid, :act, :mod, :eid, :dt, :ip, NOW())");
        $stmt->execute([
            ':uid' => $userId,
            ':act' => $action,
            ':mod' => $module,
            ':eid' => $entityId,
            ':dt' => $details,
            ':ip' => $ip
        ]);
    } catch (Throwable $e) {
        error_log('[Activity Log Error] ' . $e->getMessage());
    }
}

function json_response(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=UTF-8');
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function sanitize_string($value, int $maxLen = 255): string {
    $s = trim((string)$value);
    if (mb_strlen($s, 'UTF-8') > $maxLen) {
        $s = mb_substr($s, 0, $maxLen, 'UTF-8');
    }
    return $s;
}

function api_only_method($allowed): void {
    $allowed = is_array($allowed) ? $allowed : [$allowed];
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (!in_array($method, $allowed, true)) {
        json_response(['success' => false, 'message' => 'Phương thức HTTP không được hỗ trợ.'], 405);
    }
}

if (!function_exists('bloomora_slugify')) {
    function bloomora_slugify(string $text): string {
        $text = mb_strtolower(trim($text), 'UTF-8');
        $map = [
            'à'=>'a','á'=>'a','ạ'=>'a','ả'=>'a','ã'=>'a','â'=>'a','ầ'=>'a','ấ'=>'a','ậ'=>'a','ẩ'=>'a','ẫ'=>'a','ă'=>'a','ằ'=>'a','ắ'=>'a','ặ'=>'a','ẳ'=>'a','ẵ'=>'a',
            'è'=>'e','é'=>'e','ẹ'=>'e','ẻ'=>'e','ẽ'=>'e','ê'=>'e','ề'=>'e','ế'=>'e','ệ'=>'e','ể'=>'e','ễ'=>'e',
            'ì'=>'i','í'=>'i','ị'=>'i','ỉ'=>'i','ĩ'=>'i',
            'ò'=>'o','ó'=>'o','ọ'=>'o','ỏ'=>'o','õ'=>'o','ô'=>'o','ồ'=>'o','ố'=>'o','ộ'=>'o','ổ'=>'o','ỗ'=>'o','ơ'=>'o','ờ'=>'o','ớ'=>'o','ợ'=>'o','ở'=>'o','ỡ'=>'o',
            'ù'=>'u','ú'=>'u','ụ'=>'u','ủ'=>'u','ũ'=>'u','ư'=>'u','ừ'=>'u','ứ'=>'u','ự'=>'u','ử'=>'u','ữ'=>'u',
            'ỳ'=>'y','ý'=>'y','ỵ'=>'y','ỷ'=>'y','ỹ'=>'y','đ'=>'d'
        ];
        $text = strtr($text, $map);
        $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
        $text = preg_replace('/[\s-]+/', '-', (string)$text);
        return trim((string)$text, '-');
    }
}