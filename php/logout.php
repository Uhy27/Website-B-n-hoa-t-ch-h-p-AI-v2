<?php
declare(strict_types=1);

require_once __DIR__ . '/config/helpers.php';

init_secure_session();

$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();

init_secure_session();
set_flash('success', 'Bạn đã đăng xuất tài khoản an toàn.');
header('Location: login.php');
exit;
