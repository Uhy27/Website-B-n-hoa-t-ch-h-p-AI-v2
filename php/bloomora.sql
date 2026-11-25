-- ============================================================================
-- BLOOMORA FLORAL E-COMMERCE MASTER DATABASE (PHIÊN BẢN NÂNG CẤP CHUẨN 3NF)
-- Đề tài: Hệ thống Thương mại Điện tử Hoa tươi Bloomora tích hợp AI
-- Địa bàn thị trường mục tiêu: Đắk Lắk (TP. Buôn Ma Thuột & Phụ cận)
-- Tên CSDL: bloomora_db
-- Môi trường: MySQL / MariaDB (XAMPP)
-- Bộ mã: utf8mb4 / utf8mb4_unicode_ci (Hỗ trợ 100% Tiếng Việt có dấu)
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `bloomora_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `bloomora_db`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `ai_conversations`;
DROP TABLE IF EXISTS `order_status_logs`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `shipping_zones`;
DROP TABLE IF EXISTS `delivery_slots`;
DROP TABLE IF EXISTS `vouchers`;
DROP TABLE IF EXISTS `inventory`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `roles`;
SET FOREIGN_KEY_CHECKS = 1;

-- ----------------------------------------------------------------------------
-- 1. BẢNG PHÂN QUYỀN (ROLES)
-- ----------------------------------------------------------------------------
CREATE TABLE `roles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL UNIQUE,
    `description` VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`id`, `name`, `description`) VALUES 
(1, 'admin', 'Quản trị viên toàn quyền hệ thống, quản lý danh mục, đối soát VietQR, phân công đơn'),
(2, 'staff', 'Nghệ nhân Florist cắm hoa, chụp ảnh thành phẩm Photo Proof và giao nhận'),
(3, 'customer', 'Khách hàng thành viên có tài khoản, lưu lịch sử đơn và sổ địa chỉ');

-- ----------------------------------------------------------------------------
-- 2. BẢNG NGƯỜI DÙNG & TÀI KHOẢN (USERS)
-- Mật khẩu mặc định băm bcrypt: 123456 ($2y$10$7Cs0fdzC2DjTsz9RC93DJecqPnwETRnmGWbvVu4OY.v4yqivjUa0S)
-- ----------------------------------------------------------------------------
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `role_id` INT NOT NULL DEFAULT 3,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(20) NULL,
    `address` VARCHAR(255) NULL,
    `avatar` VARCHAR(255) NULL,
    `status` ENUM('active', 'inactive', 'banned') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `role_id`, `email`, `password_hash`, `full_name`, `phone`, `address`, `status`) VALUES
(1, 1, 'admin@bloomora.vn', '$2y$10$7Cs0fdzC2DjTsz9RC93DJecqPnwETRnmGWbvVu4OY.v4yqivjUa0S', 'Admin Chủ Tiệm', '0946618960', '120 Phan Chu Trinh, P. Thắng Lợi, TP. Buôn Ma Thuột, Đắk Lắk', 'active'),
(2, 2, 'florist.linh@bloomora.vn', '$2y$10$7Cs0fdzC2DjTsz9RC93DJecqPnwETRnmGWbvVu4OY.v4yqivjUa0S', 'Mai Phương Florist', '0918765432', '45 Lê Duẩn, P. Tự An, TP. Buôn Ma Thuột, Đắk Lắk', 'active'),
(3, 3, 'khachhang@gmail.com', '$2y$10$7Cs0fdzC2DjTsz9RC93DJecqPnwETRnmGWbvVu4OY.v4yqivjUa0S', 'Nguyễn Văn Khách', '0912345678', '88 Nguyễn Tất Thành, P. Tân Lợi, TP. Buôn Ma Thuột, Đắk Lắk', 'active');

-- ----------------------------------------------------------------------------
-- 3. BẢNG DANH MỤC HOA (CATEGORIES)
-- ----------------------------------------------------------------------------
CREATE TABLE `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(120) NOT NULL UNIQUE,
    `description` TEXT NULL,
    `image` VARCHAR(255) NULL,
    `is_active` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `categories` (`id`, `name`, `slug`, `description`) VALUES
(1, 'Hoa Khai Trương', 'hoa-khai-truong', 'Kệ hoa khai trương phát tài, hồng phát sang trọng cho doanh nghiệp Đắk Lắk'),
(2, 'Hoa Tình Yêu & Hoa Hồng', 'hoa-tinh-yeu', 'Hoa hồng nhập khẩu đỏ, hồng pastel lãng mạn dành cho người yêu, ngày kỷ niệm'),
(3, 'Lan Hồ Điệp', 'lan-ho-diep', 'Chậu lan hồ điệp thuần khiết, quý phái cắm chậu gốm nghệ thuật'),
(4, 'Hoa Sinh Nhật', 'hoa-sinh-nhat', 'Bó hoa và giỏ hoa chúc mừng sinh nhật tươi tắn, ý nghĩa'),
(5, 'Hoa Vĩnh Cửu', 'hoa-vinh-cuu', 'Hoa sáp thơm và hoa khô công nghệ giữ màu tươi mới 3 năm');

-- ----------------------------------------------------------------------------
-- 4. BẢNG SẢN PHẨM HOA TƯƠI (PRODUCTS)
-- ----------------------------------------------------------------------------
CREATE TABLE `products` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT NOT NULL,
    `sku` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(150) NOT NULL,
    `slug` VARCHAR(180) NOT NULL UNIQUE,
    `price` DECIMAL(12, 0) NOT NULL,
    `original_price` DECIMAL(12, 0) NULL,
    `image` VARCHAR(255) NULL,
    `badge` VARCHAR(50) DEFAULT 'Mẫu Mới',
    `rating` DECIMAL(2, 1) DEFAULT 5.0,
    `sold_count` INT DEFAULT 0,
    `stock` INT DEFAULT 20,
    `is_featured` TINYINT(1) DEFAULT 0,
    `is_available_for_order` TINYINT(1) DEFAULT 1 COMMENT 'Bật/tắt nhận đơn cho mẫu hoa theo ngày',
    `description` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `products` (`id`, `category_id`, `sku`, `name`, `slug`, `price`, `original_price`, `image`, `badge`, `rating`, `sold_count`, `stock`, `is_featured`, `is_available_for_order`, `description`) VALUES
(1, 1, 'WF-KT01', 'Kệ Khai Trương Đại Cát Hồng Phát', 'ke-khai-truong-dai-cat-hong-phat', 1850000, 2200000, 'https://images.unsplash.com/photo-1561181286-d3fee7d55364?auto=format&fit=crop&w=600&q=80', 'Bán Chạy Nhất', 5.0, 128, 10, 1, 1, 'Kệ hoa 2 tầng phối hoa hồng Ohara đỏ, hướng dương Đà Lạt và lan vũ nữ vàng rực rỡ, mang ý nghĩa phát tài hanh thông.'),
(2, 2, 'WF-TY01', 'Bó Hồng Đỏ Nồng Nàn 99 Bông (Forever Love)', 'bo-hong-do-nong-nan-99-bong', 1250000, 1500000, 'https://images.unsplash.com/photo-1582794543139-8ac9cb0f7b11?auto=format&fit=crop&w=600&q=80', 'Gợi Ý Valentine', 4.9, 95, 15, 1, 1, '99 đóa hoa hồng đỏ Ecuador tuyển chọn cành thẳng, gói giấy craft cao cấp chuẩn Wish Florist.'),
(3, 3, 'WF-HD01', 'Chậu Lan Hồ Điệp Hoàng Kim 5 Cành', 'chau-lan-ho-diep-hoang-kim-5-canh', 2500000, 2800000, 'https://images.unsplash.com/photo-1525310072745-f49212b5ac6d?auto=format&fit=crop&w=600&q=80', 'Cao Cấp', 5.0, 42, 8, 1, 1, 'Lan hồ điệp vàng hoàng kim cắm chậu gốm mộc nghệ thuật, phù hợp biếu tặng đối tác, chúc thọ.'),
(4, 4, 'WF-SN01', 'Giỏ Hoa Pastel Nắng Mai Ấm Áp', 'gio-hoa-pastel-nang-mai-am-ap', 650000, 800000, 'https://images.unsplash.com/photo-1567696911980-2eed69a46042?auto=format&fit=crop&w=600&q=80', 'Yêu Thích', 4.8, 110, 20, 1, 1, 'Giỏ hoa tone kem pastel nhẹ nhàng kết hợp hoa baby trắng và cúc tana ngọt ngào mừng sinh nhật.'),
(5, 1, 'WF-KT02', 'Kệ Hoa Khai Trương Tinh Khôi Thịnh Vượng', 'ke-hoa-khai-truong-tinh-khoi-thinh-vuong', 2150000, 2500000, 'https://images.unsplash.com/photo-1519378058457-4c29a0a2efac?auto=format&fit=crop&w=600&q=80', 'VIP', 5.0, 36, 6, 1, 1, 'Thiết kế phong cách tối giản Bắc Âu sang trọng với hoa cẩm tú cầu xanh bơ và hồng trắng nhập khẩu.'),
(6, 2, 'WF-TY02', 'Hộp Hoa Trái Tim Hồng Phấn Thủy Tinh', 'hop-hoa-trai-tim-hong-phan-thuy-tinh', 890000, 1050000, 'https://images.unsplash.com/photo-1591886960571-74d43a9d4166?auto=format&fit=crop&w=600&q=80', 'Mẫu Mới', 4.9, 58, 12, 1, 1, 'Hộp mica trong suốt xếp hình trái tim với hoa hồng phấn Ecuador và đèn led lung linh lãng mạn.'),
(7, 4, 'WF-SN02', 'Bó Hướng Dương Năng Lượng Tươi Sáng', 'bo-huong-duong-nang-luong-tuoi-sang', 550000, 650000, 'https://images.unsplash.com/photo-1597848212624-a19eb35e2651?auto=format&fit=crop&w=600&q=80', 'Best Value', 4.8, 88, 18, 1, 1, '5 cành hướng dương lớn vươn mình rực rỡ, món quà tuyệt vời mừng tốt nghiệp và sinh nhật bạn bè.'),
(8, 5, 'WF-VC01', 'Khung Kính Hoa Hồng Vĩnh Cửu 3 Năm', 'khung-kinh-hoa-hong-vinh-cuu-3-nam', 950000, 1200000, 'https://images.unsplash.com/photo-1533616688419-b7a58556458e?auto=format&fit=crop&w=600&q=80', 'Độc Quyền', 5.0, 75, 14, 1, 1, 'Hoa hồng vĩnh cửu công nghệ ướp tươi Nhật Bản đặt trong vòm kính pha lê cao cấp.');

-- ----------------------------------------------------------------------------
-- 5. BẢNG MÃ GIẢM GIÁ (VOUCHERS)
-- ----------------------------------------------------------------------------
CREATE TABLE `vouchers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(30) NOT NULL UNIQUE,
    `description` VARCHAR(255) NULL,
    `discount_type` ENUM('percentage', 'fixed') DEFAULT 'percentage',
    `discount_value` DECIMAL(12, 0) NOT NULL,
    `min_order_amount` DECIMAL(12, 0) NOT NULL DEFAULT 0,
    `max_discount` DECIMAL(12, 0) NULL,
    `usage_limit` INT DEFAULT 100,
    `used_count` INT DEFAULT 0,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `vouchers` (`id`, `code`, `description`, `discount_type`, `discount_value`, `min_order_amount`, `max_discount`, `usage_limit`, `used_count`, `start_date`, `end_date`, `is_active`) VALUES
(1, 'BMT50K', 'Giảm 50k cho khách hàng tại TP. Buôn Ma Thuột', 'fixed', 50000, 500000, 50000, 200, 14, '2026-01-01', '2026-12-31', 1),
(2, 'BLOOMNEW10', 'Giảm 10% cho đơn hàng đầu tiên', 'percentage', 10, 300000, 150000, 500, 45, '2026-01-01', '2026-12-31', 1),
(3, 'FREESHIPBMT', 'Miễn phí giao hàng nội thành Buôn Ma Thuột', 'fixed', 15000, 400000, 15000, 300, 68, '2026-01-01', '2026-12-31', 1);

-- ----------------------------------------------------------------------------
-- 6. BẢNG KHUNG GIỜ GIAO HÀNG & NĂNG LỰC NHẬN ĐƠN (DELIVERY_SLOTS)
-- ----------------------------------------------------------------------------
CREATE TABLE `delivery_slots` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `slot_name` VARCHAR(50) NOT NULL,
    `time_range` VARCHAR(50) NOT NULL,
    `max_orders_per_slot` INT NOT NULL DEFAULT 15 COMMENT 'Giới hạn năng lực cắm của tiệm mỗi khung giờ',
    `is_active` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `delivery_slots` (`id`, `slot_name`, `time_range`, `max_orders_per_slot`, `is_active`) VALUES
(1, 'Sáng sớm (Khai trương)', '07:30 - 09:30', 20, 1),
(2, 'Buổi sáng', '09:30 - 11:30', 15, 1),
(3, 'Đầu giờ chiều', '13:30 - 15:30', 15, 1),
(4, 'Chiều tà', '15:30 - 17:30', 18, 1),
(5, 'Buổi tối (Sinh nhật / Hẹn hò)', '18:00 - 20:00', 12, 1);

-- ----------------------------------------------------------------------------
-- 7. BẢNG PHÍ SHIP KHU VỰC ĐẮK LẮK (SHIPPING_ZONES)
-- ----------------------------------------------------------------------------
CREATE TABLE `shipping_zones` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `zone_name` VARCHAR(100) NOT NULL,
    `province` VARCHAR(50) NOT NULL DEFAULT 'Đắk Lắk',
    `district_name` VARCHAR(100) NOT NULL,
    `shipping_fee` DECIMAL(12, 0) NOT NULL,
    `estimated_minutes` INT NOT NULL DEFAULT 60,
    `is_active` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `shipping_zones` (`id`, `zone_name`, `province`, `district_name`, `shipping_fee`, `estimated_minutes`, `is_active`) VALUES
(1, 'Nội thành Buôn Ma Thuột (P. Thắng Lợi, Tân Lợi, Tự An, Tân Lập)', 'Đắk Lắk', 'TP. Buôn Ma Thuột', 15000, 45, 1),
(2, 'Ngoại thành Buôn Ma Thuột (Hòa Thuận, Hòa Khánh, Cư Êbur, Ea Kao)', 'Đắk Lắk', 'TP. Buôn Ma Thuột', 25000, 60, 1),
(3, 'Huyện Cư M\'gar (Thị trấn Quảng Phú & lân cận)', 'Đắk Lắk', 'Huyện Cư M\'gar', 35000, 75, 1),
(4, 'Huyện Krông Pắc (Thị trấn Phước An)', 'Đắk Lắk', 'Huyện Krông Pắc', 40000, 90, 1),
(5, 'Huyện Buôn Đôn', 'Đắk Lắk', 'Huyện Buôn Đôn', 45000, 90, 1);

-- ----------------------------------------------------------------------------
-- 8. BẢNG ĐƠN HÀNG (ORDERS) - QUY TRÌNH 5 TRẠNG THÁI & PHOTO PROOF
-- Trạng thái: pending -> processing -> photo_review -> delivering -> completed (hoặc cancelled)
-- ----------------------------------------------------------------------------
CREATE TABLE `orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_code` VARCHAR(20) NOT NULL UNIQUE,
    `user_id` INT NULL COMMENT 'NULL nếu là Guest đặt hoa',
    `sender_name` VARCHAR(100) NOT NULL,
    `sender_phone` VARCHAR(20) NOT NULL,
    `receiver_name` VARCHAR(100) NOT NULL,
    `receiver_phone` VARCHAR(20) NOT NULL,
    `is_anonymous` TINYINT(1) DEFAULT 0 COMMENT 'Gửi tặng ẩn danh, che thông tin người gửi trên phiếu giao',
    `delivery_address` VARCHAR(255) NOT NULL,
    `shipping_zone_id` INT NULL,
    `delivery_slot_id` INT NULL,
    `delivery_time_slot` VARCHAR(50) NOT NULL,
    `delivery_date` DATE NOT NULL,
    
    -- Số tiền tài chính
    `subtotal` DECIMAL(12, 0) NOT NULL DEFAULT 0,
    `discount_amount` DECIMAL(12, 0) NOT NULL DEFAULT 0,
    `shipping_fee` DECIMAL(12, 0) NOT NULL DEFAULT 0,
    `total_amount` DECIMAL(12, 0) NOT NULL,
    `voucher_id` INT NULL,

    -- Thanh toán & Đối soát VietQR
    `payment_method` ENUM('cod', 'vietqr', 'vnpay') DEFAULT 'vietqr',
    `payment_status` ENUM('unpaid', 'paid') DEFAULT 'unpaid',
    `payment_confirmed_by` INT NULL COMMENT 'Admin ID đối soát nhận tiền',
    `payment_confirmed_at` TIMESTAMP NULL,

    -- Phân công & Quy trình 5 bước
    `assigned_staff_id` INT NULL COMMENT 'Staff Florist chịu trách nhiệm cắm hoa',
    `order_status` ENUM('pending', 'processing', 'photo_review', 'delivering', 'completed', 'cancelled') DEFAULT 'pending',
    
    -- Photo Proof Duyệt Ảnh Hoa Thật
    `photo_proof_url` VARCHAR(255) NULL,
    `photo_status` ENUM('none', 'pending_approval', 'approved', 'revision_requested', 'auto_approved') DEFAULT 'none',
    `photo_uploaded_at` TIMESTAMP NULL,
    `photo_review_note` TEXT NULL COMMENT 'Ghi chú yêu cầu chỉnh sửa từ khách hàng',
    `photo_approved_at` TIMESTAMP NULL,
    
    -- Ảnh chụp giao thành công
    `delivery_proof_url` VARCHAR(255) NULL,
    `florist_note` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`shipping_zone_id`) REFERENCES `shipping_zones`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`delivery_slot_id`) REFERENCES `delivery_slots`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`voucher_id`) REFERENCES `vouchers`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`assigned_staff_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`payment_confirmed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `orders` (
    `id`, `order_code`, `user_id`, `sender_name`, `sender_phone`, `receiver_name`, `receiver_phone`, 
    `is_anonymous`, `delivery_address`, `shipping_zone_id`, `delivery_slot_id`, `delivery_time_slot`, `delivery_date`,
    `subtotal`, `discount_amount`, `shipping_fee`, `total_amount`, `voucher_id`,
    `payment_method`, `payment_status`, `payment_confirmed_by`, `payment_confirmed_at`,
    `assigned_staff_id`, `order_status`, `photo_proof_url`, `photo_status`, `photo_uploaded_at`, `photo_approved_at`, `florist_note`
) VALUES
(
    1, 'BL89241', 3, 'Nguyễn Văn Khách', '0912345678', 'Công ty Xây Dựng Đắk Lắk', '0935112233',
    0, '120 Phan Chu Trinh, P. Thắng Lợi, TP. Buôn Ma Thuột, Đắk Lắk', 1, 4, '15:30 - 17:30', '2026-11-19',
    1850000, 50000, 15000, 1815000, 1,
    'vietqr', 'paid', 1, '2026-11-19 14:00:00',
    2, 'photo_review', 'https://images.unsplash.com/photo-1561181286-d3fee7d55364?auto=format&fit=crop&w=400&q=80', 'pending_approval', '2026-11-19 14:30:00', NULL, 'Kệ hoa đã cắm xong đúng tone đỏ hồng phát, chờ khách duyệt'
),
(
    2, 'BL17305', NULL, 'Trần Minh Hoàng', '0988776655', 'Lê Thu Thảo', '0977665544',
    1, '45 Lê Duẩn, P. Tự An, TP. Buôn Ma Thuột, Đắk Lắk', 1, 4, '15:30 - 17:30', '2026-11-19',
    1250000, 0, 15000, 1265000, NULL,
    'vietqr', 'paid', 1, '2026-11-19 14:10:00',
    2, 'processing', NULL, 'none', NULL, NULL, 'Khách gửi tặng ẩn danh, tuyển chọn 99 bông hồng đỏ tươi đẹp'
),
(
    3, 'BL22419', NULL, 'Ngân hàng Vietcombank Đắk Lắk', '0909112233', 'Văn phòng Công chứng Ban Mê', '0903445566',
    0, '88 Nguyễn Tất Thành, P. Tân Lợi, TP. Buôn Ma Thuột, Đắk Lắk', 1, 5, '18:00 - 20:00', '2026-11-19',
    2500000, 0, 15000, 2515000, NULL,
    'vietqr', 'unpaid', NULL, NULL,
    NULL, 'pending', NULL, 'none', NULL, NULL, 'Đơn mới đặt qua VietQR, chờ khách quét mã thanh toán'
);

-- ----------------------------------------------------------------------------
-- 9. BẢNG CHI TIẾT ĐƠN HÀNG (ORDER_ITEMS)
-- ----------------------------------------------------------------------------
CREATE TABLE `order_items` (
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

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `ribbon_text`) VALUES
(1, 1, 1, 'Kệ Khai Trương Đại Cát Hồng Phát', 1, 1850000, 'CÔNG TY XÂY DỰNG CHÚC MỪNG KHAI TRƯƠNG HỒNG PHÁT'),
(2, 2, 2, 'Bó Hồng Đỏ Nồng Nàn 99 Bông (Forever Love)', 1, 1250000, 'Thiệp: Chúc em sinh nhật luôn rạng ngời hạnh phúc'),
(3, 3, 3, 'Chậu Lan Hồ Điệp Hoàng Kim 5 Cành', 1, 2500000, 'VIETCOMBANK ĐẮK LẮK CHÚC MỪNG PHÁT TÀI THỊNH VƯỢNG');

-- ----------------------------------------------------------------------------
-- 10. BẢNG NHẬT KÝ CHUYỂN TRẠNG THÁI ĐƠN HÀNG (ORDER_STATUS_LOGS)
-- ----------------------------------------------------------------------------
CREATE TABLE `order_status_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `previous_status` VARCHAR(50) NULL,
    `new_status` VARCHAR(50) NOT NULL,
    `changed_by_user_id` INT NULL,
    `note` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`changed_by_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `order_status_logs` (`id`, `order_id`, `previous_status`, `new_status`, `changed_by_user_id`, `note`) VALUES
(1, 1, NULL, 'pending', NULL, 'Khách hàng hoàn tất đặt đơn hoa trên website'),
(2, 1, 'pending', 'processing', 1, 'Admin xác nhận đã nhận tiền VietQR và phân công Florist Mai Phương'),
(3, 1, 'processing', 'photo_review', 2, 'Florist Mai Phương tải ảnh Photo Proof lên hệ thống'),
(4, 2, NULL, 'pending', NULL, 'Khách hàng đặt hoa giấu tên thành công'),
(5, 2, 'pending', 'processing', 1, 'Admin duyệt tiền và chuyển cho Florist cắm');

-- ----------------------------------------------------------------------------
-- 11. BẢNG QUẢN LÝ LỊCH SỬ AI TƯ VẤN & LỜI CHÚC (AI_CONVERSATIONS)
-- ----------------------------------------------------------------------------
CREATE TABLE `ai_conversations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `session_id` VARCHAR(100) NOT NULL,
    `user_id` INT NULL,
    `feature_type` ENUM('chatbot_consult', 'ribbon_wishes', 'product_desc') NOT NULL,
    `occasion` VARCHAR(100) NULL,
    `recipient` VARCHAR(100) NULL,
    `budget` DECIMAL(12, 0) NULL,
    `prompt_input` TEXT NOT NULL,
    `ai_response` TEXT NOT NULL,
    `suggested_product_ids` VARCHAR(255) NULL,
    `ip_address` VARCHAR(45) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `ai_conversations` (`id`, `session_id`, `user_id`, `feature_type`, `occasion`, `recipient`, `budget`, `prompt_input`, `ai_response`, `suggested_product_ids`, `ip_address`) VALUES
(1, 'sess_demo_101', 3, 'chatbot_consult', 'Khai Trương', 'Đối tác', 2000000, 'Tôi cần tìm hoa khai trương đối tác mở công ty tại Buôn Ma Thuột tầm 1.8 - 2.2 triệu', 'Chào bạn! Với ngân sách tầm 2 triệu mừng đối tác khai trương, Bloomora gợi ý mẫu Kệ Khai Trương Đại Cát Hồng Phát (1.850.000₫) cắm hồng Ohara và hướng dương, kèm băng rôn in vi tính miễn phí.', '1,5', '127.0.0.1'),
(2, 'sess_demo_102', NULL, 'ribbon_wishes', 'Khai Trương', 'Bạn thân', NULL, 'Dịp: Khai Trương | Giọng điệu: Hài hước & Thân mật', '1. CHÚC TIỆM MỚI TIỀN VÀO NHƯ NƯỚC\n2. KHAI TRƯƠNG HỒNG PHÁT ĐẾM TIỀN MỎI TAY\n3. MUA MAY BÁN ĐẮT KHÁCH VÀO NẮM NƯỚC', NULL, '127.0.0.1');

-- ----------------------------------------------------------------------------
-- 12. BẢNG CẢNH BÁO TỒN KHO NGUYÊN VẬT LIỆU (INVENTORY)
-- ----------------------------------------------------------------------------
CREATE TABLE `inventory` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `category` VARCHAR(100) NOT NULL,
    `quantity` INT NOT NULL DEFAULT 0,
    `unit` VARCHAR(20) NOT NULL,
    `min_threshold` INT NOT NULL DEFAULT 5,
    `status` VARCHAR(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `inventory` (`id`, `name`, `category`, `quantity`, `unit`, `min_threshold`, `status`) VALUES
(1, 'Hoa Hướng Dương Đà Lạt', 'Nguyên liệu hoa chính', 3, 'bó', 5, 'Cần nhập gấp'),
(2, 'Hồng Ohara Nhập Khẩu', 'Mẫu hoa VIP', 4, 'cành', 5, 'Sắp hết hàng'),
(3, 'Dải Ruy Băng Đỏ Viền Vàng', 'Vật tư in vi tính', 12, 'cuộn', 5, 'Đủ ca hôm nay'),
(4, 'Lan Vũ Nữ Vàng', 'Nguyên liệu hoa phụ', 8, 'cành', 6, 'Đủ ca hôm nay'),
(5, 'Giỏ Gỗ Mộc Đắk Lắk', 'Vật tư phụ kiện', 15, 'chiếc', 5, 'Đủ ca hôm nay');
