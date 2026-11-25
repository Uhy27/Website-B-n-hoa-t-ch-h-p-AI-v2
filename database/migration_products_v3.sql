-- ===================================================================
-- BLOOMORA PRODUCT MANAGEMENT SYSTEM UPGRADE - SCHEMA MIGRATION v3
-- Chuẩn hóa thuộc tính hoa tươi & danh mục sản phẩm
-- ===================================================================

USE `bloomora_db`;

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Bổ sung trường cho bảng `categories` nếu chưa có
SET @col_cat_desc := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = 'bloomora_db' AND TABLE_NAME = 'categories' AND COLUMN_NAME = 'description'
);
SET @sql_cat_desc = IF(@col_cat_desc = 0,
    'ALTER TABLE `categories` ADD COLUMN `description` TEXT NULL AFTER `name`',
    'SELECT 1'
);
PREPARE stmt_cat_desc FROM @sql_cat_desc;
EXECUTE stmt_cat_desc;
DEALLOCATE PREPARE stmt_cat_desc;

SET @col_cat_img := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = 'bloomora_db' AND TABLE_NAME = 'categories' AND COLUMN_NAME = 'image'
);
SET @sql_cat_img = IF(@col_cat_img = 0,
    'ALTER TABLE `categories` ADD COLUMN `image` VARCHAR(500) NULL AFTER `icon`',
    'SELECT 1'
);
PREPARE stmt_cat_img FROM @sql_cat_img;
EXECUTE stmt_cat_img;
DEALLOCATE PREPARE stmt_cat_img;

-- 2. Bổ sung các thuộc tính hoa chuyên biệt cho bảng `products`
SET @col_p_short := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = 'bloomora_db' AND TABLE_NAME = 'products' AND COLUMN_NAME = 'short_description'
);
SET @sql_p_short = IF(@col_p_short = 0,
    'ALTER TABLE `products` ADD COLUMN `short_description` VARCHAR(500) NULL AFTER `name`',
    'SELECT 1'
);
PREPARE stmt_p_short FROM @sql_p_short;
EXECUTE stmt_p_short;
DEALLOCATE PREPARE stmt_p_short;

SET @col_p_stem := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = 'bloomora_db' AND TABLE_NAME = 'products' AND COLUMN_NAME = 'stem_count'
);
SET @sql_p_stem = IF(@col_p_stem = 0,
    'ALTER TABLE `products` ADD COLUMN `stem_count` VARCHAR(50) NULL AFTER `primary_flower`',
    'SELECT 1'
);
PREPARE stmt_p_stem FROM @sql_p_stem;
EXECUTE stmt_p_stem;
DEALLOCATE PREPARE stmt_p_stem;

SET @col_p_sec := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = 'bloomora_db' AND TABLE_NAME = 'products' AND COLUMN_NAME = 'secondary_flower'
);
SET @sql_p_sec = IF(@col_p_sec = 0,
    'ALTER TABLE `products` ADD COLUMN `secondary_flower` VARCHAR(150) NULL AFTER `stem_count`',
    'SELECT 1'
);
PREPARE stmt_p_sec FROM @sql_p_sec;
EXECUTE stmt_p_sec;
DEALLOCATE PREPARE stmt_p_sec;

SET @col_p_wrap := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = 'bloomora_db' AND TABLE_NAME = 'products' AND COLUMN_NAME = 'wrapping_paper'
);
SET @sql_p_wrap = IF(@col_p_wrap = 0,
    'ALTER TABLE `products` ADD COLUMN `wrapping_paper` VARCHAR(150) NULL AFTER `size_dimensions`',
    'SELECT 1'
);
PREPARE stmt_p_wrap FROM @sql_p_wrap;
EXECUTE stmt_p_wrap;
DEALLOCATE PREPARE stmt_p_wrap;

SET @col_p_care := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = 'bloomora_db' AND TABLE_NAME = 'products' AND COLUMN_NAME = 'care_guide'
);
SET @sql_p_care = IF(@col_p_care = 0,
    'ALTER TABLE `products` ADD COLUMN `care_guide` TEXT NULL AFTER `meaning`',
    'SELECT 1'
);
PREPARE stmt_p_care FROM @sql_p_care;
EXECUTE stmt_p_care;
DEALLOCATE PREPARE stmt_p_care;

SET @col_p_gal := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = 'bloomora_db' AND TABLE_NAME = 'products' AND COLUMN_NAME = 'gallery'
);
SET @sql_p_gal = IF(@col_p_gal = 0,
    'ALTER TABLE `products` ADD COLUMN `gallery` TEXT NULL AFTER `thumbnail`',
    'SELECT 1'
);
PREPARE stmt_p_gal FROM @sql_p_gal;
EXECUTE stmt_p_gal;
DEALLOCATE PREPARE stmt_p_gal;

SET FOREIGN_KEY_CHECKS = 1;
