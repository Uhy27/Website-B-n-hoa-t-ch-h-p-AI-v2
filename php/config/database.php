<?php
declare(strict_types=1);

/**
 * BLOOMORA - KẾT NỐI CƠ SỞ DỮ LIỆU MYSQL BẰNG PDO
 * Tự động khởi tạo database bloomora_db và bảng users/roles nếu chưa có
 */

require_once __DIR__ . '/helpers.php';

define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'bloomora_db');
define('DB_USER', 'root');
define('DB_PASS', '');

function getDBConnection(): ?PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    try {
        // 1. Kiểm tra kết nối và tạo database nếu chưa có
        $initPdo = new PDO(
            "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
        $initPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        // 2. Kết nối chính thức vào database bloomora_db
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);

        // 3. Tự động khởi tạo bảng roles và users nếu chưa có
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `roles` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(50) NOT NULL UNIQUE,
                `description` VARCHAR(255) NULL
            ) ENGINE=InnoDB;

            CREATE TABLE IF NOT EXISTS `users` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `role_id` INT NOT NULL DEFAULT 3,
                `email` VARCHAR(150) NOT NULL UNIQUE,
                `password_hash` VARCHAR(255) NOT NULL,
                `full_name` VARCHAR(100) NOT NULL,
                `phone` VARCHAR(20) NULL,
                `avatar` VARCHAR(255) NULL,
                `status` ENUM('active', 'inactive', 'banned') DEFAULT 'active',
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE RESTRICT
            ) ENGINE=InnoDB;
        ");

        // Chèn roles mặc định nếu rỗng
        $roleCheck = $pdo->query("SELECT COUNT(*) FROM `roles`")->fetchColumn();
        if ((int)$roleCheck === 0) {
            $pdo->exec("
                INSERT INTO `roles` (`id`, `name`, `description`) VALUES 
                (1, 'admin', 'Quản trị viên toàn quyền hệ thống'),
                (2, 'staff', 'Nhân viên cắm hoa & giao nhận'),
                (3, 'customer', 'Khách hàng');
            ");
        }

        // Chèn tài khoản mẫu nếu rỗng (Pass: 123456)
        $userCheck = $pdo->query("SELECT COUNT(*) FROM `users`")->fetchColumn();
        if ((int)$userCheck === 0) {
            $defaultHash = password_hash('123456', PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("
                INSERT INTO `users` (`role_id`, `email`, `password_hash`, `full_name`, `phone`, `status`) VALUES
                (1, 'admin@bloomora.vn', :admin_pass, 'Admin Chủ Tiệm', '0901234567', 'active'),
                (3, 'khachhang@gmail.com', :user_pass, 'Nguyễn Văn Khách', '0912345678', 'active')
            ");
            $stmt->execute([
                'admin_pass' => $defaultHash,
                'user_pass'  => $defaultHash
            ]);
        }

        return $pdo;
    } catch (PDOException $e) {
        error_log('[Bloomora DB Warning] ' . $e->getMessage());
        return null;
    }
}
