-- ===================================================================
-- BLOOMORA ADMIN 2.0 MIGRATION
-- Bổ sung các bảng: materials, order_quality_checks, activity_logs,
-- store_settings và hoàn thiện roles, staff cho hệ thống quản trị hoa
-- ===================================================================

USE `bloomora_db`;

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Mở rộng bảng `roles`
INSERT INTO `roles` (`id`, `name`, `description`) VALUES
(4, 'manager', 'Quản lý cửa hàng & điều hành kinh doanh'),
(5, 'shipper', 'Nhân viên giao nhận hỏa tốc'),
(6, 'warehouse', 'Thủ kho hoa tươi & nguyên phụ liệu')
ON DUPLICATE KEY UPDATE `description` = VALUES(`description`);

-- 2. Bổ sung nhân sự mẫu
-- Password hash cho 'admin123': $2y$10$vI8aWBnW3fID.ZQ4/zo1G.q1lRps.9cGLcZEiGPEWi9GQORtNVqeq
INSERT INTO `users` (`id`, `role_id`, `email`, `password_hash`, `full_name`, `phone`, `status`) VALUES
(4, 4, 'manager@bloomora.vn', '$2y$10$vI8aWBnW3fID.ZQ4/zo1G.q1lRps.9cGLcZEiGPEWi9GQORtNVqeq', 'Nguyễn Thị Bích Trâm', '0908112233', 'active'),
(5, 5, 'shipper@bloomora.vn', '$2y$10$vI8aWBnW3fID.ZQ4/zo1G.q1lRps.9cGLcZEiGPEWi9GQORtNVqeq', 'Lê Hoàng Nam (Shipper Express)', '0912334455', 'active'),
(6, 6, 'kho@bloomora.vn', '$2y$10$vI8aWBnW3fID.ZQ4/zo1G.q1lRps.9cGLcZEiGPEWi9GQORtNVqeq', 'Phạm Minh Đức (Thủ Kho)', '0934556677', 'active')
ON DUPLICATE KEY UPDATE `full_name` = VALUES(`full_name`);

-- 3. Tạo bảng `materials` (Quản lý nguyên vật liệu hoa tươi & phụ liệu)
CREATE TABLE IF NOT EXISTS `materials` (
    `id`              INT AUTO_INCREMENT PRIMARY KEY,
    `sku`             VARCHAR(50) NOT NULL UNIQUE,
    `name`            VARCHAR(150) NOT NULL,
    `category`        ENUM('flower_fresh', 'flower_dry', 'wrapping_paper', 'ribbon', 'basket_vase', 'accessories') NOT NULL DEFAULT 'flower_fresh',
    `unit`            VARCHAR(30) NOT NULL,
    `stock_quantity`  INT NOT NULL DEFAULT 0,
    `min_stock_alert` INT NOT NULL DEFAULT 10,
    `cost_per_unit`   DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `supplier`        VARCHAR(150) NULL,
    `status`          ENUM('in_stock', 'low_stock', 'out_of_stock') NOT NULL DEFAULT 'in_stock',
    `updated_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dữ liệu mẫu nguyên vật liệu xưởng hoa
INSERT INTO `materials` (`sku`, `name`, `category`, `unit`, `stock_quantity`, `min_stock_alert`, `cost_per_unit`, `supplier`, `status`) VALUES
('MAT-HOA-01', 'Hoa Hồng Đỏ Explorer Ecuador', 'flower_fresh', 'Cành', 180, 50, 18000.00, 'Dalay Farm Đà Lạt', 'in_stock'),
('MAT-HOA-02', 'Hoa Baby Trắng Hà Lan', 'flower_fresh', 'Bó (500g)', 25, 10, 85000.00, 'GreenImports SG', 'in_stock'),
('MAT-HOA-03', 'Hoa Cẩm Tú Cầu Xanh Lam Đà Lạt', 'flower_fresh', 'Cành lớn', 40, 15, 32000.00, 'Dalay Farm Đà Lạt', 'in_stock'),
('MAT-HOA-04', 'Hoa Cát Tường Song Hỷ', 'flower_fresh', 'Cành', 120, 30, 12000.00, 'Vườn Hoa Minh Tâm', 'in_stock'),
('MAT-HOA-05', 'Hoa Hướng Dương Lùn Cắt Cành', 'flower_fresh', 'Cành', 8, 20, 15000.00, 'Dalay Farm Đà Lạt', 'low_stock'),
('MAT-HOA-06', 'Hoa Lan Hồ Điệp Trắng Cành Lớn', 'flower_fresh', 'Cành (8-10 bông)', 3, 5, 180000.00, 'Orchid Farm Bảo Lộc', 'low_stock'),
('MAT-GIAY-01', 'Giấy Gói Lụa Hàn Quốc Màu Pastel Hồng', 'wrapping_paper', 'Xấp (20 tờ)', 45, 10, 65000.00, 'Phụ Kiện Hoa Kim Phụng', 'in_stock'),
('MAT-GIAY-02', 'Giấy Báo Vintage Chống Nước', 'wrapping_paper', 'Xấp (20 tờ)', 30, 10, 55000.00, 'Phụ Kiện Hoa Kim Phụng', 'in_stock'),
('MAT-RUY-01', 'Ruy Băng Voan Viền Ren 2.5cm Hồng', 'ribbon', 'Cuộn (45m)', 12, 5, 42000.00, 'Phụ Kiện Hoa Sài Gòn', 'in_stock'),
('MAT-RUY-02', 'Ruy Băng Lụa Ép Nhũ Vàng Bloomora', 'ribbon', 'Cuộn (50m)', 20, 5, 95000.00, 'In Ấn & Phụ Kiện Đại Nam', 'in_stock'),
('MAT-GIO-01', 'Giỏ Gỗ Vintage Quai Cầm Mộc', 'basket_vase', 'Cái', 35, 10, 48000.00, 'Mây Tre Đan Bình Dương', 'in_stock'),
('MAT-PHU-01', 'Xốp Cắm Hoa Oasis Chuẩn Hút Nước', 'accessories', 'Thùng (20 viên)', 15, 5, 210000.00, 'Oasis Floral VN', 'in_stock'),
('MAT-PHU-02', 'Thiệp Mỹ Thuật Kèm Phong Bì Ép Kim', 'accessories', 'Xấp (50 cái)', 85, 20, 120000.00, 'Bloomora Brand Pack', 'in_stock')
ON DUPLICATE KEY UPDATE `stock_quantity` = VALUES(`stock_quantity`);

-- 4. Bảng `order_quality_checks` (Kiểm định chất lượng thành phẩm trước khi xuất kho)
CREATE TABLE IF NOT EXISTS `order_quality_checks` (
    `id`                    INT AUTO_INCREMENT PRIMARY KEY,
    `order_id`              INT NOT NULL,
    `checked_by`            INT NULL,
    `flower_freshness`      TINYINT(1) NOT NULL DEFAULT 1,
    `color_accuracy`        TINYINT(1) NOT NULL DEFAULT 1,
    `stem_count_matched`    TINYINT(1) NOT NULL DEFAULT 1,
    `wrapping_standard`     TINYINT(1) NOT NULL DEFAULT 1,
    `card_message_verified` TINYINT(1) NOT NULL DEFAULT 1,
    `photo_proof_url`       VARCHAR(500) NULL,
    `notes`                 TEXT NULL,
    `created_at`            TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`checked_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Bảng `activity_logs` (Audit Log - Nhật ký hệ thống quản trị)
CREATE TABLE IF NOT EXISTS `activity_logs` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`     INT NULL,
    `action`      VARCHAR(100) NOT NULL,
    `module`      VARCHAR(50) NOT NULL,
    `entity_id`   INT NULL,
    `details`     TEXT NULL,
    `ip_address`  VARCHAR(45) NULL,
    `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Nhật ký mẫu ban đầu
INSERT INTO `activity_logs` (`user_id`, `action`, `module`, `entity_id`, `details`, `ip_address`) VALUES
(1, 'system_init', 'settings', NULL, 'Khởi động hệ thống Bloomora Admin 2.0 thành công', '127.0.0.1'),
(1, 'pricing_sync', 'products', NULL, 'Đồng bộ giá bán và cập nhật biên lợi nhuận cho toàn bộ danh mục hoa', '127.0.0.1'),
(2, 'qc_pass', 'quality_check', 1, 'Kiểm duyệt chất lượng bó hoa Hướng Dương Rạng Rỡ đạt 5/5 tiêu chí', '127.0.0.1');

-- 6. Bảng `store_settings` (Cấu hình thông tin cửa hàng & kênh thanh toán)
CREATE TABLE IF NOT EXISTS `store_settings` (
    `key_name`    VARCHAR(100) PRIMARY KEY,
    `key_value`   TEXT NULL,
    `description` VARCHAR(255) NULL,
    `updated_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `store_settings` (`key_name`, `key_value`, `description`) VALUES
('store_name', 'Bloomora Florist & Gifts', 'Tên thương hiệu cửa hàng'),
('store_hotline', '1900 6868 - 0988 123 456', 'Hotline chăm sóc khách hàng'),
('store_address', '123 Đường Hoa Hồng, Phường Bến Nghé, Quận 1, TP. Hồ Chí Minh', 'Địa chỉ cửa hàng chính'),
('store_email', 'contact@bloomora.vn', 'Email hỗ trợ chính thức'),
('store_opening_hours', '07:30 - 21:30 (Thứ 2 - Chủ Nhật)', 'Khung giờ hoạt động tiếp nhận và giao hoa'),
('vietqr_bank_id', 'MB', 'Mã ngân hàng nhận tiền VietQR (MBBank, VCB, ACB, ...)'),
('vietqr_account_no', '0988123456', 'Số tài khoản ngân hàng thụ hưởng'),
('vietqr_account_name', 'CONG TY TNHH BLOOMORA FLORIST', 'Tên chủ tài khoản thụ hưởng'),
('momo_phone', '0988123456', 'Số điện thoại nhận tiền MoMo Business'),
('free_shipping_threshold', '800000', 'Đơn hàng từ mức này trở lên được miễn phí giao hoa hỏa tốc')
ON DUPLICATE KEY UPDATE `key_value` = VALUES(`key_value`);

-- 7. Bổ sung trường gán người phụ trách vào bảng `orders` nếu chưa có
SET @col_florist := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = 'bloomora_db' AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'assigned_florist_id'
);
SET @sql_f = IF(@col_florist = 0,
    'ALTER TABLE `orders` ADD COLUMN `assigned_florist_id` INT NULL AFTER `photo_proof`',
    'SELECT 1'
);
PREPARE stmt_f FROM @sql_f;
EXECUTE stmt_f;
DEALLOCATE PREPARE stmt_f;

SET @col_shipper := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = 'bloomora_db' AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'assigned_shipper_id'
);
SET @sql_s = IF(@col_shipper = 0,
    'ALTER TABLE `orders` ADD COLUMN `assigned_shipper_id` INT NULL AFTER `assigned_florist_id`',
    'SELECT 1'
);
PREPARE stmt_s FROM @sql_s;
EXECUTE stmt_s;
DEALLOCATE PREPARE stmt_s;

SET FOREIGN_KEY_CHECKS = 1;
