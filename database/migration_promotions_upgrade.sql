-- ===================================================================
-- BLOOMORA PROMOTIONS & VOUCHERS PROFESSIONAL UPGRADE MIGRATION
-- Target DB: bloomora_db | Charset: utf8mb4_unicode_ci
-- Extends existing tables cleanly without removing data
-- ===================================================================

USE `bloomora_db`;

SET FOREIGN_KEY_CHECKS = 0;

-- -------------------------------------------------------------------
-- 1. EXTEND `vouchers`
-- -------------------------------------------------------------------
SET @col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='bloomora_db' AND TABLE_NAME='vouchers' AND COLUMN_NAME='program_name');
SET @sql := IF(@col=0, 'ALTER TABLE `vouchers` ADD COLUMN `program_name` VARCHAR(200) NULL AFTER `campaign_id`', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='bloomora_db' AND TABLE_NAME='vouchers' AND COLUMN_NAME='prefix');
SET @sql := IF(@col=0, 'ALTER TABLE `vouchers` ADD COLUMN `prefix` VARCHAR(20) NULL AFTER `code`', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='bloomora_db' AND TABLE_NAME='vouchers' AND COLUMN_NAME='batch_code');
SET @sql := IF(@col=0, 'ALTER TABLE `vouchers` ADD COLUMN `batch_code` VARCHAR(64) NULL AFTER `prefix`', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='bloomora_db' AND TABLE_NAME='vouchers' AND COLUMN_NAME='max_discount_per_order');
SET @sql := IF(@col=0, 'ALTER TABLE `vouchers` ADD COLUMN `max_discount_per_order` DECIMAL(12,2) NULL AFTER `max_discount_amount`', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='bloomora_db' AND TABLE_NAME='vouchers' AND COLUMN_NAME='scope_type');
SET @sql := IF(@col=0, 'ALTER TABLE `vouchers` ADD COLUMN `scope_type` ENUM(\'all\',\'category\',\'product\') NOT NULL DEFAULT \'all\' AFTER `per_user_limit`', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='bloomora_db' AND TABLE_NAME='vouchers' AND COLUMN_NAME='customer_scope');
SET @sql := IF(@col=0, 'ALTER TABLE `vouchers` ADD COLUMN `customer_scope` ENUM(\'all\',\'new_customer\',\'vip\') NOT NULL DEFAULT \'all\' AFTER `scope_type`', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='bloomora_db' AND TABLE_NAME='vouchers' AND COLUMN_NAME='status');
SET @sql := IF(@col=0, 'ALTER TABLE `vouchers` ADD COLUMN `status` ENUM(\'draft\',\'scheduled\',\'active\',\'paused\',\'ended\') NOT NULL DEFAULT \'active\' AFTER `end_at`', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='bloomora_db' AND TABLE_NAME='vouchers' AND COLUMN_NAME='created_by');
SET @sql := IF(@col=0, 'ALTER TABLE `vouchers` ADD COLUMN `created_by` INT NULL AFTER `is_active`', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='bloomora_db' AND TABLE_NAME='vouchers' AND COLUMN_NAME='updated_at');
SET @sql := IF(@col=0, 'ALTER TABLE `vouchers` ADD COLUMN `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Backfill program_name & status from existing campaigns/vouchers
UPDATE `vouchers` v
LEFT JOIN `campaigns` c ON c.id = v.campaign_id
SET
    v.program_name = COALESCE(NULLIF(v.program_name, ''), c.name, v.description, CONCAT('Chương trình ', v.code)),
    v.status = CASE
        WHEN v.is_active = 0 THEN 'paused'
        WHEN NOW() > v.end_at THEN 'ended'
        WHEN NOW() < v.start_at THEN 'scheduled'
        ELSE 'active'
    END
WHERE v.program_name IS NULL OR v.program_name = '';

-- -------------------------------------------------------------------
-- 2. EXTEND `campaigns`
-- -------------------------------------------------------------------
SET @col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='bloomora_db' AND TABLE_NAME='campaigns' AND COLUMN_NAME='max_discount_per_order');
SET @sql := IF(@col=0, 'ALTER TABLE `campaigns` ADD COLUMN `max_discount_per_order` DECIMAL(12,2) NULL AFTER `max_discount_amount`', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='bloomora_db' AND TABLE_NAME='campaigns' AND COLUMN_NAME='scope_type');
SET @sql := IF(@col=0, 'ALTER TABLE `campaigns` ADD COLUMN `scope_type` ENUM(\'all\',\'category\',\'product\') NOT NULL DEFAULT \'all\' AFTER `min_margin`', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='bloomora_db' AND TABLE_NAME='campaigns' AND COLUMN_NAME='customer_scope');
SET @sql := IF(@col=0, 'ALTER TABLE `campaigns` ADD COLUMN `customer_scope` ENUM(\'all\',\'new_customer\',\'vip\') NOT NULL DEFAULT \'all\' AFTER `scope_type`', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- -------------------------------------------------------------------
-- 3. EXTEND `promotion_products` TO SUPPORT `voucher_id` TOO
-- -------------------------------------------------------------------
ALTER TABLE `promotion_products` MODIFY COLUMN `campaign_id` INT NULL;

SET @col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='bloomora_db' AND TABLE_NAME='promotion_products' AND COLUMN_NAME='voucher_id');
SET @sql := IF(@col=0, 'ALTER TABLE `promotion_products` ADD COLUMN `voucher_id` INT NULL AFTER `campaign_id`', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @fk := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA='bloomora_db' AND TABLE_NAME='promotion_products' AND CONSTRAINT_NAME='fk_pp_voucher');
SET @sql := IF(@fk=0, 'ALTER TABLE `promotion_products` ADD CONSTRAINT `fk_pp_voucher` FOREIGN KEY (`voucher_id`) REFERENCES `vouchers`(`id`) ON DELETE CASCADE', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- -------------------------------------------------------------------
-- 4. CREATE TABLE `promotion_categories`
-- -------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `promotion_categories` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `campaign_id` INT NULL,
    `voucher_id`  INT NULL,
    `category_id` INT NOT NULL,
    INDEX `idx_pc_voucher` (`voucher_id`),
    INDEX `idx_pc_campaign` (`campaign_id`),
    FOREIGN KEY (`campaign_id`) REFERENCES `campaigns`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`voucher_id`)  REFERENCES `vouchers`(`id`)  ON DELETE CASCADE,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------------
-- 5. EXTEND `voucher_usages` (Audit trail + Guest phone + Single voucher per order)
-- -------------------------------------------------------------------
SET @col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='bloomora_db' AND TABLE_NAME='voucher_usages' AND COLUMN_NAME='voucher_code');
SET @sql := IF(@col=0, 'ALTER TABLE `voucher_usages` ADD COLUMN `voucher_code` VARCHAR(50) NULL AFTER `voucher_id`', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='bloomora_db' AND TABLE_NAME='voucher_usages' AND COLUMN_NAME='order_code');
SET @sql := IF(@col=0, 'ALTER TABLE `voucher_usages` ADD COLUMN `order_code` VARCHAR(20) NULL AFTER `order_id`', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='bloomora_db' AND TABLE_NAME='voucher_usages' AND COLUMN_NAME='customer_phone');
SET @sql := IF(@col=0, 'ALTER TABLE `voucher_usages` ADD COLUMN `customer_phone` VARCHAR(20) NULL AFTER `user_id`', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='bloomora_db' AND TABLE_NAME='voucher_usages' AND COLUMN_NAME='ip_address');
SET @sql := IF(@col=0, 'ALTER TABLE `voucher_usages` ADD COLUMN `ip_address` VARCHAR(45) NULL AFTER `customer_phone`', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Enforce at most 1 active voucher usage per order
SET @idx := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA='bloomora_db' AND TABLE_NAME='voucher_usages' AND INDEX_NAME='uk_voucher_usages_order');
SET @sql := IF(@idx=0, 'ALTER TABLE `voucher_usages` ADD UNIQUE KEY `uk_voucher_usages_order` (`order_id`)', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- -------------------------------------------------------------------
-- 6. EXTEND `orders` WITH `voucher_id` AND `voucher_snapshot`
-- -------------------------------------------------------------------
SET @col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='bloomora_db' AND TABLE_NAME='orders' AND COLUMN_NAME='voucher_id');
SET @sql := IF(@col=0, 'ALTER TABLE `orders` ADD COLUMN `voucher_id` INT NULL AFTER `voucher_code`', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='bloomora_db' AND TABLE_NAME='orders' AND COLUMN_NAME='voucher_snapshot');
SET @sql := IF(@col=0, 'ALTER TABLE `orders` ADD COLUMN `voucher_snapshot` TEXT NULL AFTER `voucher_id`', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET FOREIGN_KEY_CHECKS = 1;
