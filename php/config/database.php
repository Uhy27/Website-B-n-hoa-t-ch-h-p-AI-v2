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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `categories` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(100) NOT NULL,
                `slug` VARCHAR(120) NOT NULL UNIQUE,
                `description` TEXT NULL,
                `image` VARCHAR(255) NULL,
                `is_active` TINYINT(1) DEFAULT 1
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `products` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `category_id` INT NOT NULL,
                `name` VARCHAR(150) NOT NULL,
                `slug` VARCHAR(180) NOT NULL UNIQUE,
                `price` DECIMAL(12, 0) NOT NULL,
                `original_price` DECIMAL(12, 0) NULL,
                `image` VARCHAR(255) NULL,
                `stock` INT DEFAULT 20,
                `is_featured` TINYINT(1) DEFAULT 0,
                `description` TEXT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `orders` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `order_code` VARCHAR(20) NOT NULL UNIQUE,
                `user_id` INT NULL,
                `customer_name` VARCHAR(100) NOT NULL,
                `customer_phone` VARCHAR(20) NOT NULL,
                `delivery_address` VARCHAR(255) NOT NULL,
                `delivery_time_slot` VARCHAR(50) NOT NULL,
                `total_amount` DECIMAL(12, 0) NOT NULL,
                `payment_method` ENUM('cod', 'vietqr', 'vnpay') DEFAULT 'vietqr',
                `payment_status` ENUM('unpaid', 'paid') DEFAULT 'paid',
                `order_status` ENUM('received', 'preparing', 'photo_ready', 'delivering', 'completed', 'cancelled') DEFAULT 'received',
                `florist_note` TEXT NULL,
                `photo_proof` VARCHAR(255) NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `order_items` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `order_id` INT NOT NULL,
                `product_id` INT NOT NULL,
                `product_name` VARCHAR(150) NOT NULL,
                `quantity` INT NOT NULL DEFAULT 1,
                `unit_price` DECIMAL(12, 0) NOT NULL,
                `ribbon_text` VARCHAR(255) NULL,
                FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `inventory` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(100) NOT NULL,
                `category` VARCHAR(100) NOT NULL,
                `quantity` INT NOT NULL DEFAULT 0,
                `unit` VARCHAR(20) NOT NULL,
                `min_threshold` INT NOT NULL DEFAULT 5,
                `status` VARCHAR(50) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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

        // Chèn danh mục và sản phẩm hoa mẫu nếu rỗng
        $catCheck = $pdo->query("SELECT COUNT(*) FROM `categories`")->fetchColumn();
        if ((int)$catCheck === 0) {
            $pdo->exec("
                INSERT INTO `categories` (`id`, `name`, `slug`, `description`) VALUES
                (1, 'Hoa Khai Trương', 'hoa-khai-truong', 'Kệ hoa khai trương phát tài, hồng phát sang trọng'),
                (2, 'Hoa Sinh Nhật', 'hoa-sinh-nhat', 'Bó hoa và giỏ hoa chúc mừng sinh nhật ý nghĩa'),
                (3, 'Hoa Tình Yêu & Hoa Hồng', 'hoa-tinh-yeu', 'Hoa hồng nhập khẩu đỏ, hồng pastel lãng mạn'),
                (4, 'Lan Hồ Điệp', 'lan-ho-diep', 'Chậu lan hồ điệp thuần khiết, quý phái');

                INSERT INTO `products` (`id`, `category_id`, `name`, `slug`, `price`, `original_price`, `image`, `stock`, `is_featured`, `description`) VALUES
                (1, 1, 'Kệ Khai Trương Đại Cát', 'ke-khai-truong-dai-cat', 1850000, 2200000, 'https://images.unsplash.com/photo-1561181286-d3fee7d55364?auto=format&fit=crop&w=400&q=80', 10, 1, 'Kệ hoa 2 tầng phối hoa hồng Ecuador và hướng dương rực rỡ'),
                (2, 3, 'Bó Hồng Đỏ Nồng Nàn 99 Bông', 'bo-hong-do-nong-nan-99-bong', 1250000, 1500000, 'https://images.unsplash.com/photo-1582794543139-8ac9cb0f7b11?auto=format&fit=crop&w=400&q=80', 15, 1, 'Bó hoa 99 bông hồng đỏ thắm tượng trưng cho tình yêu vĩnh cửu'),
                (3, 4, 'Chậu Lan Hồ Điệp Hoàng Kim 5 Cành', 'chau-lan-ho-diep-hoang-kim-5-canh', 2500000, 2800000, 'https://images.unsplash.com/photo-1525310072745-f49212b5ac6d?auto=format&fit=crop&w=400&q=80', 8, 1, 'Lan hồ điệp vàng hoàng kim cắm chậu gốm nghệ thuật');
            ");
        }

        // Chèn đơn hàng mẫu nếu rỗng
        $orderCheck = $pdo->query("SELECT COUNT(*) FROM `orders`")->fetchColumn();
        if ((int)$orderCheck === 0) {
            $pdo->exec("
                INSERT INTO `orders` (`id`, `order_code`, `user_id`, `customer_name`, `customer_phone`, `delivery_address`, `delivery_time_slot`, `total_amount`, `payment_method`, `payment_status`, `order_status`, `florist_note`, `photo_proof`) VALUES
                (1, 'BL89241', 2, 'Nguyễn Văn Khách', '0912345678', 'Tòa nhà Landmark 81, P.22, Bình Thạnh, TP.HCM', '15:30 - 17:30', 1850000, 'vietqr', 'paid', 'photo_ready', 'Hoa hồng nhập khẩu đỏ tươi, nơ đỏ viền kim tuyến vàng', 'https://images.unsplash.com/photo-1561181286-d3fee7d55364?auto=format&fit=crop&w=400&q=80'),
                (2, 'BL17305', NULL, 'Trần Minh Hoàng', '0988776655', '120 Nguyễn Huệ, Quận 1, TP.HCM', '15:30 - 17:30', 1250000, 'vietqr', 'paid', 'preparing', 'Florist Mai Phương đang tuyển chọn hoa tại xưởng', NULL),
                (3, 'BL22419', NULL, 'Công ty Công Nghệ Alpha', '0909112233', 'Khu Công Nghệ Cao, TP. Thủ Đức', '18:00 - 20:00', 2500000, 'cod', 'unpaid', 'received', 'Đã tiếp nhận yêu cầu, chuẩn bị chậu gốm mộc', NULL);

                INSERT INTO `inventory` (`id`, `name`, `category`, `quantity`, `unit`, `min_threshold`, `status`) VALUES
                (1, 'Hoa Hướng Dương Đà Lạt', 'Nguyên liệu hoa chính', 3, 'bó', 5, 'Cần nhập gấp'),
                (2, 'Hồng Ohara Nhập Khẩu', 'Mẫu hoa VIP', 4, 'cành', 5, 'Sắp hết hàng'),
                (3, 'Dải Ruy Băng Đỏ Viền Vàng', 'Vật tư in vi tính', 12, 'cuộn', 5, 'Đủ ca hôm nay');
            ");
        }

        return $pdo;
    } catch (PDOException $e) {
        error_log('[Bloomora DB Warning] ' . $e->getMessage());
        return null;
    }
}
