<?php
declare(strict_types=1);

/**
 * CLI / Cron helper script to automatically transition scheduled/expired promotions.
 * Timezone: Asia/Ho_Chi_Minh
 * Usage: php cron/sync_promotions.php
 */

require_once __DIR__ . '/../core/bootstrap.php';

try {
    $pdo = getDBConnection();
    PromotionService::syncStatuses($pdo);
    echo '[' . PromotionService::nowString() . "] Synchronized promotions and campaigns successfully.\n";
} catch (Throwable $e) {
    error_log('[Cron Sync Promotions] ' . $e->getMessage());
    fwrite(STDERR, 'Error syncing promotions: ' . $e->getMessage() . "\n");
    exit(1);
}
