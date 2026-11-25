<?php
declare(strict_types=1);

/**
 * BLOOMORA - CÁC HÀM TIỆN ÍCH DÙNG CHUNG (HELPERS & SESSION)
 */

// Khởi tạo phiên làm việc bảo mật
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

// Lưu thông báo Flash (hiển thị 1 lần)
function set_flash(string $type, string $message): void {
    init_secure_session();
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

// Lấy thông báo Flash
function get_flash(): ?array {
    init_secure_session();
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Lọc chuỗi an toàn chống XSS
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
