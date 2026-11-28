-- ===================================================================
-- BLOOMORA CATEGORY MANAGEMENT UPGRADE MIGRATION
-- Target DB: bloomora_db | Charset: utf8mb4_unicode_ci
-- Preserves all existing categories & product relationships
-- ===================================================================

USE `bloomora_db`;

SET FOREIGN_KEY_CHECKS = 0;

-- Ensure table uses utf8mb4_unicode_ci
ALTER TABLE `categories` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 1. Add `code` column (Mã danh mục)
SET @col := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = 'bloomora_db' AND TABLE_NAME = 'categories' AND COLUMN_NAME = 'code'
);
SET @sql := IF(@col = 0,
    'ALTER TABLE `categories` ADD COLUMN `code` VARCHAR(50) NULL AFTER `name`',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2. Add `parent_id` column (Danh mục cha phân cấp)
SET @col := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = 'bloomora_db' AND TABLE_NAME = 'categories' AND COLUMN_NAME = 'parent_id'
);
SET @sql := IF(@col = 0,
    'ALTER TABLE `categories` ADD COLUMN `parent_id` INT NULL DEFAULT NULL AFTER `slug`',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3. Add `status` ENUM('active','hidden') synced with `is_active`
SET @col := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = 'bloomora_db' AND TABLE_NAME = 'categories' AND COLUMN_NAME = 'status'
);
SET @sql := IF(@col = 0,
    'ALTER TABLE `categories` ADD COLUMN `status` ENUM(\'active\',\'hidden\') NOT NULL DEFAULT \'active\' AFTER `is_active`',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 4. Add `created_at` and `updated_at`
SET @col := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = 'bloomora_db' AND TABLE_NAME = 'categories' AND COLUMN_NAME = 'created_at'
);
SET @sql := IF(@col = 0,
    'ALTER TABLE `categories` ADD COLUMN `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `status`',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = 'bloomora_db' AND TABLE_NAME = 'categories' AND COLUMN_NAME = 'updated_at'
);
SET @sql := IF(@col = 0,
    'ALTER TABLE `categories` ADD COLUMN `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Backfill unique category codes and sync `status` <-> `is_active`
UPDATE `categories`
SET `code` = CONCAT('DM', LPAD(`id`, 3, '0'))
WHERE `code` IS NULL OR TRIM(`code`) = '';

UPDATE `categories`
SET `status` = IF(`is_active` = 1, 'active', 'hidden');

-- Add UNIQUE index on `code` if missing
SET @idx := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = 'bloomora_db' AND TABLE_NAME = 'categories' AND INDEX_NAME = 'uk_categories_code'
);
SET @sql := IF(@idx = 0,
    'ALTER TABLE `categories` ADD UNIQUE KEY `uk_categories_code` (`code`)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add foreign key for parent_id -> categories(id) ON DELETE SET NULL if missing
SET @fk := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = 'bloomora_db' AND TABLE_NAME = 'categories' AND CONSTRAINT_NAME = 'fk_categories_parent'
);
SET @sql := IF(@fk = 0,
    'ALTER TABLE `categories` ADD CONSTRAINT `fk_categories_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;
