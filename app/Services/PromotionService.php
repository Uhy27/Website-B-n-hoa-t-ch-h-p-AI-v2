<?php
declare(strict_types=1);

/**
 * BLOOMORA - Promotion Service
 * Handles timezone-aware scheduling (Asia/Ho_Chi_Minh), unique voucher generation,
 * strict server-side validation (status, date bounds, global/per-user/guest limits,
 * category/product/customer scopes, min order, max discount), and atomic redemption/refund.
 */
class PromotionService
{
    public const TIMEZONE = 'Asia/Ho_Chi_Minh';

    public static function nowString(): string
    {
        return (new DateTimeImmutable('now', new DateTimeZone(self::TIMEZONE)))->format('Y-m-d H:i:s');
    }

    /**
     * Synchronize scheduled and expired statuses for vouchers and campaigns using Asia/Ho_Chi_Minh.
     */
    public static function syncStatuses(PDO $pdo): void
    {
        $now = self::nowString();

        try {
            // 1. Vouchers: scheduled -> active when start_at <= now <= end_at
            $st1 = $pdo->prepare("
                UPDATE vouchers
                SET status = 'active', is_active = 1
                WHERE status = 'scheduled' AND start_at <= :n1 AND end_at >= :n2
            ");
            $st1->execute([':n1' => $now, ':n2' => $now]);

            // 2. Vouchers: active/scheduled -> ended when end_at < now
            $st2 = $pdo->prepare("
                UPDATE vouchers
                SET status = 'ended', is_active = 0
                WHERE status IN ('scheduled', 'active') AND end_at < :n1
            ");
            $st2->execute([':n1' => $now]);

            // 3. Vouchers: active -> scheduled if start_at moved to future
            $st3 = $pdo->prepare("
                UPDATE vouchers
                SET status = 'scheduled'
                WHERE status = 'active' AND start_at > :n1
            ");
            $st3->execute([':n1' => $now]);

            // 4. Campaigns sync
            $c1 = $pdo->prepare("
                UPDATE campaigns
                SET status = 'active'
                WHERE status = 'scheduled' AND approval_status = 'approved'
                  AND start_at <= :n1 AND end_at >= :n2
            ");
            $c1->execute([':n1' => $now, ':n2' => $now]);

            $c2 = $pdo->prepare("
                UPDATE campaigns
                SET status = 'expired'
                WHERE status IN ('scheduled', 'active') AND end_at < :n1
            ");
            $c2->execute([':n1' => $now]);
        } catch (Throwable $e) {
            error_log('[PromotionService syncStatuses] ' . $e->getMessage());
        }
    }

    /**
     * Generate a guaranteed unique promotion code in vouchers table.
     */
    public static function generateUniqueCode(PDO $pdo, string $prefix = 'BLOOM', int $suffixLen = 5): string
    {
        $cleanPrefix = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $prefix));
        if ($cleanPrefix === '') {
            $cleanPrefix = 'BLOOM';
        }
        $cleanPrefix = substr($cleanPrefix, 0, 12);
        $suffixLen = max(3, min(10, $suffixLen));
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $alphaMax = strlen($alphabet) - 1;

        $checkStmt = $pdo->prepare('SELECT COUNT(*) FROM vouchers WHERE UPPER(code) = :code');

        for ($attempt = 0; $attempt < 60; $attempt++) {
            $suffix = '';
            for ($i = 0; $i < $suffixLen; $i++) {
                $suffix .= $alphabet[random_int(0, $alphaMax)];
            }
            $candidate = $cleanPrefix . $suffix;
            $checkStmt->execute([':code' => $candidate]);
            if ((int)$checkStmt->fetchColumn() === 0) {
                return $candidate;
            }
        }

        throw new RuntimeException('Không thể sinh mã khuyến mãi duy nhất. Vui lòng thử tiền tố khác.');
    }

    /**
     * Normalize phone digits for reliable guest per-user limits.
     */
    public static function normalizePhone(?string $phone): string
    {
        if ($phone === null) {
            return '';
        }
        return preg_replace('/\D+/', '', trim($phone)) ?? '';
    }

    /**
     * Load category and product IDs restricted by a voucher (or fallback campaign).
     */
    public static function getVoucherScopes(PDO $pdo, int $voucherId, ?int $campaignId = null): array
    {
        $catRows = [];
        $prodRows = [];

        try {
            $cStmt = $pdo->prepare("
                SELECT c.id, c.name, c.code, c.slug
                FROM promotion_categories pc
                INNER JOIN categories c ON c.id = pc.category_id
                WHERE pc.voucher_id = :vid
                ORDER BY c.display_order ASC, c.id ASC
            ");
            $cStmt->execute([':vid' => $voucherId]);
            $catRows = $cStmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($catRows) && $campaignId !== null) {
                $cStmt2 = $pdo->prepare("
                    SELECT c.id, c.name, c.code, c.slug
                    FROM promotion_categories pc
                    INNER JOIN categories c ON c.id = pc.category_id
                    WHERE pc.campaign_id = :cid
                    ORDER BY c.display_order ASC, c.id ASC
                ");
                $cStmt2->execute([':cid' => $campaignId]);
                $catRows = $cStmt2->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Throwable $ignore) {}

        try {
            $pStmt = $pdo->prepare("
                SELECT p.id, p.sku, p.name, p.price, p.thumbnail, p.category_id
                FROM promotion_products pp
                INNER JOIN products p ON p.id = pp.product_id
                WHERE pp.voucher_id = :vid
                ORDER BY p.id ASC
            ");
            $pStmt->execute([':vid' => $voucherId]);
            $prodRows = $pStmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($prodRows) && $campaignId !== null) {
                $pStmt2 = $pdo->prepare("
                    SELECT p.id, p.sku, p.name, p.price, p.thumbnail, p.category_id
                    FROM promotion_products pp
                    INNER JOIN products p ON p.id = pp.product_id
                    WHERE pp.campaign_id = :cid
                    ORDER BY p.id ASC
                ");
                $pStmt2->execute([':cid' => $campaignId]);
                $prodRows = $pStmt2->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Throwable $ignore) {}

        return [
            'category_ids' => array_map(fn($r) => (int)$r['id'], $catRows),
            'categories'   => $catRows,
            'product_ids'  => array_map(fn($r) => (int)$r['id'], $prodRows),
            'products'     => $prodRows,
        ];
    }

    /**
     * Validate voucher strictly on server side and compute authoritative discount.
     *
     * @param PDO         $pdo
     * @param string      $code
     * @param float       $subtotal
     * @param int|null    $userId
     * @param array       $cartItems     Array of ['id'|'productId', 'quantity', 'addons']
     * @param string|null $customerPhone Optional guest/customer phone for guest limits
     * @param bool        $forUpdate     Pass true inside checkout transaction for row-level lock
     */
    public static function checkVoucher(
        PDO $pdo,
        string $code,
        float $subtotal,
        ?int $userId,
        array $cartItems = [],
        ?string $customerPhone = null,
        bool $forUpdate = false
    ): array {
        if (!$forUpdate && !$pdo->inTransaction()) {
            self::syncStatuses($pdo);
        }
        $now = self::nowString();
        $cleanCode = strtoupper(trim($code));

        if ($cleanCode === '') {
            return ['valid' => false, 'voucher' => null, 'discountAmount' => 0.0, 'eligibleSubtotal' => 0.0, 'message' => 'Vui lòng nhập mã khuyến mãi.'];
        }

        $lockSql = $forUpdate ? ' FOR UPDATE' : '';
        $stmt = $pdo->prepare("SELECT * FROM vouchers WHERE UPPER(code) = :code LIMIT 1" . $lockSql);
        $stmt->execute([':code' => $cleanCode]);
        $voucher = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$voucher) {
            return ['valid' => false, 'voucher' => null, 'discountAmount' => 0.0, 'eligibleSubtotal' => 0.0, 'message' => "Mã khuyến mãi \"{$cleanCode}\" không tồn tại."];
        }

        // 1. Check status & active flags
        $vStatus = (string)($voucher['status'] ?? ((int)$voucher['is_active'] === 1 ? 'active' : 'paused'));
        if ($vStatus === 'draft') {
            return ['valid' => false, 'voucher' => $voucher, 'discountAmount' => 0.0, 'eligibleSubtotal' => 0.0, 'message' => 'Mã khuyến mãi đang ở trạng thái Nháp, chưa được kích hoạt.'];
        }
        if ($vStatus === 'paused' || (int)$voucher['is_active'] !== 1) {
            return ['valid' => false, 'voucher' => $voucher, 'discountAmount' => 0.0, 'eligibleSubtotal' => 0.0, 'message' => 'Chương trình khuyến mãi hiện đang tạm dừng.'];
        }
        if ($vStatus === 'ended' || $now > $voucher['end_at']) {
            return ['valid' => false, 'voucher' => $voucher, 'discountAmount' => 0.0, 'eligibleSubtotal' => 0.0, 'message' => 'Mã khuyến mãi đã hết hạn sử dụng.'];
        }
        if ($vStatus === 'scheduled' || $now < $voucher['start_at']) {
            $startFormatted = date('d/m/Y H:i', strtotime($voucher['start_at']));
            return ['valid' => false, 'voucher' => $voucher, 'discountAmount' => 0.0, 'eligibleSubtotal' => 0.0, 'message' => "Mã khuyến mãi chưa đến thời gian bắt đầu (hiệu lực từ {$startFormatted})."];
        }

        // 2. Check linked campaign if present
        $campaign = null;
        if (!empty($voucher['campaign_id'])) {
            $cSql = "SELECT * FROM campaigns WHERE id = :cid LIMIT 1" . $lockSql;
            $cStmt = $pdo->prepare($cSql);
            $cStmt->execute([':cid' => (int)$voucher['campaign_id']]);
            $campaign = $cStmt->fetch(PDO::FETCH_ASSOC);

            if ($campaign) {
                if ($campaign['approval_status'] !== 'approved') {
                    return ['valid' => false, 'voucher' => $voucher, 'discountAmount' => 0.0, 'eligibleSubtotal' => 0.0, 'message' => 'Chiến dịch khuyến mãi chưa được phê duyệt.'];
                }
                if ($campaign['status'] === 'paused') {
                    return ['valid' => false, 'voucher' => $voucher, 'discountAmount' => 0.0, 'eligibleSubtotal' => 0.0, 'message' => 'Chiến dịch khuyến mãi hiện đang tạm dừng.'];
                }
                if ($campaign['status'] === 'expired' || $now > $campaign['end_at']) {
                    return ['valid' => false, 'voucher' => $voucher, 'discountAmount' => 0.0, 'eligibleSubtotal' => 0.0, 'message' => 'Chiến dịch khuyến mãi đã hết hạn.'];
                }
                if ($campaign['status'] === 'budget_exhausted' || ((float)$campaign['budget_limit'] > 0 && (float)$campaign['budget_used'] >= (float)$campaign['budget_limit'])) {
                    return ['valid' => false, 'voucher' => $voucher, 'discountAmount' => 0.0, 'eligibleSubtotal' => 0.0, 'message' => 'Ngân sách chương trình khuyến mãi đã hết.'];
                }
                if ($now < $campaign['start_at']) {
                    return ['valid' => false, 'voucher' => $voucher, 'discountAmount' => 0.0, 'eligibleSubtotal' => 0.0, 'message' => 'Chiến dịch khuyến mãi chưa bắt đầu.'];
                }
                if (class_exists('ScheduleService') && !ScheduleService::isCampaignActiveNow($pdo, (int)$campaign['id'])) {
                    return ['valid' => false, 'voucher' => $voucher, 'discountAmount' => 0.0, 'eligibleSubtotal' => 0.0, 'message' => 'Mã khuyến mãi hiện không nằm trong khung giờ áp dụng của chiến dịch.'];
                }
            }
        }

        // 3. Global usage limit
        $usageLimit = (int)$voucher['usage_limit'];
        $usedCount = (int)$voucher['used_count'];
        if ($usageLimit > 0 && $usedCount >= $usageLimit) {
            return ['valid' => false, 'voucher' => $voucher, 'discountAmount' => 0.0, 'eligibleSubtotal' => 0.0, 'message' => 'Mã khuyến mãi đã hết lượt sử dụng trên toàn hệ thống.'];
        }

        // 4. Per-user / per-guest-phone limit
        $perUserLimit = (int)$voucher['per_user_limit'];
        $cleanPhone = self::normalizePhone($customerPhone);
        if ($perUserLimit > 0) {
            if ($userId !== null && $userId > 0) {
                if ($cleanPhone !== '') {
                    $uStmt = $pdo->prepare("
                        SELECT COUNT(*) FROM voucher_usages
                        WHERE voucher_id = :vid AND status = 'used'
                          AND (user_id = :uid OR customer_phone = :ph)
                    ");
                    $uStmt->execute([':vid' => (int)$voucher['id'], ':uid' => $userId, ':ph' => $cleanPhone]);
                } else {
                    $uStmt = $pdo->prepare("
                        SELECT COUNT(*) FROM voucher_usages
                        WHERE voucher_id = :vid AND status = 'used' AND user_id = :uid
                    ");
                    $uStmt->execute([':vid' => (int)$voucher['id'], ':uid' => $userId]);
                }
                if ((int)$uStmt->fetchColumn() >= $perUserLimit) {
                    return ['valid' => false, 'voucher' => $voucher, 'discountAmount' => 0.0, 'eligibleSubtotal' => 0.0, 'message' => "Tài khoản của bạn đã sử dụng hết {$perUserLimit} lượt cho mã khuyến mãi này."];
                }
            } elseif ($cleanPhone !== '') {
                $uStmt = $pdo->prepare("
                    SELECT COUNT(*) FROM voucher_usages
                    WHERE voucher_id = :vid AND status = 'used' AND customer_phone = :ph
                ");
                $uStmt->execute([':vid' => (int)$voucher['id'], ':ph' => $cleanPhone]);
                if ((int)$uStmt->fetchColumn() >= $perUserLimit) {
                    return ['valid' => false, 'voucher' => $voucher, 'discountAmount' => 0.0, 'eligibleSubtotal' => 0.0, 'message' => "Số điện thoại này đã sử dụng hết {$perUserLimit} lượt cho mã khuyến mãi này."];
                }
            }
        }

        // 5. Customer eligibility scope ('all', 'new_customer', 'vip')
        $customerScope = (string)($voucher['customer_scope'] ?? 'all');
        if ($customerScope === 'new_customer') {
            if (($userId === null || $userId <= 0) && $cleanPhone === '') {
                return ['valid' => false, 'voucher' => $voucher, 'discountAmount' => 0.0, 'eligibleSubtotal' => 0.0, 'message' => 'Mã dành cho khách hàng mới. Vui lòng đăng nhập hoặc nhập số điện thoại đặt hàng để kiểm tra điều kiện.'];
            }
            $priorOrders = 0;
            if ($userId !== null && $userId > 0) {
                $pChk = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = :uid AND order_status != 'cancelled'");
                $pChk->execute([':uid' => $userId]);
                $priorOrders += (int)$pChk->fetchColumn();
            }
            if ($priorOrders === 0 && $cleanPhone !== '') {
                $pChk2 = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE REPLACE(REPLACE(sender_phone, ' ', ''), '-', '') = :ph AND order_status != 'cancelled'");
                $pChk2->execute([':ph' => $cleanPhone]);
                $priorOrders += (int)$pChk2->fetchColumn();
            }
            if ($priorOrders > 0) {
                return ['valid' => false, 'voucher' => $voucher, 'discountAmount' => 0.0, 'eligibleSubtotal' => 0.0, 'message' => 'Mã khuyến mãi chỉ áp dụng cho khách hàng mới (đơn hàng đầu tiên).'];
            }
        } elseif ($customerScope === 'vip') {
            if ($userId === null || $userId <= 0) {
                return ['valid' => false, 'voucher' => $voucher, 'discountAmount' => 0.0, 'eligibleSubtotal' => 0.0, 'message' => 'Mã khuyến mãi chỉ áp dụng cho khách hàng thành viên VIP đã đăng nhập.'];
            }
            $vipStmt = $pdo->prepare("
                SELECT COUNT(*) AS cnt, COALESCE(SUM(total_amount), 0) AS spent
                FROM orders
                WHERE user_id = :uid AND order_status != 'cancelled'
            ");
            $vipStmt->execute([':uid' => $userId]);
            $vip = $vipStmt->fetch(PDO::FETCH_ASSOC);
            $isVip = ((int)($vip['cnt'] ?? 0) >= 5) || ((float)($vip['spent'] ?? 0) >= 3000000.0);
            if (!$isVip) {
                return ['valid' => false, 'voucher' => $voucher, 'discountAmount' => 0.0, 'eligibleSubtotal' => 0.0, 'message' => 'Mã khuyến mãi chỉ áp dụng cho khách hàng VIP (từ 5 đơn hoặc tổng chi tiêu từ 3.000.000₫).'];
            }
        }

        // 6. Product / Category Scope validation & eligible subtotal computation
        $scopeType = (string)($voucher['scope_type'] ?? 'all');
        $scopes = self::getVoucherScopes($pdo, (int)$voucher['id'], isset($voucher['campaign_id']) ? (int)$voucher['campaign_id'] : null);

        $addonPrices = [
            'gau-bong' => 80000.0,
            'socola'   => 120000.0,
            'nen-thom' => 150000.0,
            'banh-kem' => 180000.0,
        ];

        $computedSubtotal = $subtotal;
        $eligibleSubtotal = $subtotal;

        if (!empty($cartItems)) {
            $itemIds = [];
            $parsedItems = [];
            foreach ($cartItems as $it) {
                $rawId = $it['id'] ?? ($it['productId'] ?? ($it['product_id'] ?? null));
                $qty = max(1, (int)($it['quantity'] ?? 1));
                if ($rawId === null || $rawId === '') {
                    continue;
                }
                $addonsExtra = 0.0;
                if (!empty($it['addons']) && is_array($it['addons'])) {
                    foreach ($it['addons'] as $add) {
                        $aid = is_array($add) ? ($add['id'] ?? '') : (string)$add;
                        if (isset($addonPrices[$aid])) {
                            $addonsExtra += $addonPrices[$aid];
                        }
                    }
                }
                $itemIds[] = $rawId;
                $parsedItems[] = ['rawId' => $rawId, 'qty' => $qty, 'addonsExtra' => $addonsExtra];
            }

            if (!empty($parsedItems)) {
                $prodStmt = $pdo->prepare("SELECT id, sku, category_id, price FROM products WHERE id = :id OR sku = :sku LIMIT 1");
                $computedSubtotal = 0.0;
                $eligibleSubtotal = 0.0;

                foreach ($parsedItems as $pi) {
                    $prodStmt->execute([':id' => $pi['rawId'], ':sku' => (string)$pi['rawId']]);
                    $pRow = $prodStmt->fetch(PDO::FETCH_ASSOC);
                    if (!$pRow) {
                        continue;
                    }
                    $lineTotal = ((float)$pRow['price'] + $pi['addonsExtra']) * $pi['qty'];
                    $computedSubtotal += $lineTotal;

                    if ($scopeType === 'all') {
                        $eligibleSubtotal += $lineTotal;
                    } elseif ($scopeType === 'category') {
                        if (in_array((int)$pRow['category_id'], $scopes['category_ids'], true)) {
                            $eligibleSubtotal += $lineTotal;
                        }
                    } elseif ($scopeType === 'product') {
                        if (in_array((int)$pRow['id'], $scopes['product_ids'], true)) {
                            $eligibleSubtotal += $lineTotal;
                        }
                    }
                }
                $subtotal = $computedSubtotal;
            }
        } elseif ($scopeType !== 'all') {
            return ['valid' => false, 'voucher' => $voucher, 'discountAmount' => 0.0, 'eligibleSubtotal' => 0.0, 'message' => 'Mã khuyến mãi có giới hạn phạm vi sản phẩm/danh mục, không thể áp dụng khi thiếu thông tin giỏ hàng.'];
        }

        if ($scopeType === 'category' && $eligibleSubtotal <= 0) {
            $catNames = array_column($scopes['categories'], 'name');
            $label = !empty($catNames) ? implode(', ', $catNames) : 'danh mục chỉ định';
            return ['valid' => false, 'voucher' => $voucher, 'discountAmount' => 0.0, 'eligibleSubtotal' => 0.0, 'message' => "Mã \"{$cleanCode}\" chỉ áp dụng cho danh mục: {$label}."];
        }

        if ($scopeType === 'product' && $eligibleSubtotal <= 0) {
            $prodNames = array_slice(array_column($scopes['products'], 'name'), 0, 3);
            $label = !empty($prodNames) ? implode(', ', $prodNames) : 'sản phẩm chỉ định';
            return ['valid' => false, 'voucher' => $voucher, 'discountAmount' => 0.0, 'eligibleSubtotal' => 0.0, 'message' => "Mã \"{$cleanCode}\" chỉ áp dụng cho các sản phẩm quy định ({$label}...)."];
        }

        // 7. Minimum order value check
        $minOrder = (float)$voucher['min_order_value'];
        if ($minOrder > 0 && $subtotal < $minOrder) {
            $formatted = number_format($minOrder, 0, ',', '.');
            $diff = number_format($minOrder - $subtotal, 0, ',', '.');
            return ['valid' => false, 'voucher' => $voucher, 'discountAmount' => 0.0, 'eligibleSubtotal' => $eligibleSubtotal, 'message' => "Đơn hàng cần tối thiểu {$formatted}₫ để áp dụng mã này (còn thiếu {$diff}₫)."];
        }

        // 8. Calculate server-side discount capped by max_discount_amount, max_discount_per_order, eligibleSubtotal
        $maxCap = isset($voucher['max_discount_amount']) && $voucher['max_discount_amount'] !== null && (float)$voucher['max_discount_amount'] > 0
            ? (float)$voucher['max_discount_amount']
            : null;
        $maxPerOrder = isset($voucher['max_discount_per_order']) && $voucher['max_discount_per_order'] !== null && (float)$voucher['max_discount_per_order'] > 0
            ? (float)$voucher['max_discount_per_order']
            : null;

        $effectiveCap = $maxCap;
        if ($maxPerOrder !== null) {
            $effectiveCap = $effectiveCap !== null ? min($effectiveCap, $maxPerOrder) : $maxPerOrder;
        }

        $discountAmount = self::calculateDiscount(
            (string)$voucher['discount_type'],
            (float)$voucher['discount_value'],
            $eligibleSubtotal,
            $effectiveCap
        );

        // Also enforce remaining campaign budget ceiling & explicit margin safety if configured
        if ($campaign) {
            if ((float)$campaign['budget_limit'] > 0) {
                $budgetRemaining = max(0.0, (float)$campaign['budget_limit'] - (float)$campaign['budget_used']);
                if ($budgetRemaining <= 0) {
                    return ['valid' => false, 'voucher' => $voucher, 'discountAmount' => 0.0, 'eligibleSubtotal' => $eligibleSubtotal, 'message' => 'Ngân sách khuyến mãi đã cạn.'];
                }
                $discountAmount = min($discountAmount, round($budgetRemaining, 2));
            }
            $campMinMargin = (float)($campaign['min_margin'] ?? 0);
            if ($campMinMargin > 0 && !empty($cartItems) && class_exists('MarginService')) {
                try {
                    $ids = [];
                    $qtyMap = [];
                    foreach ($cartItems as $it) {
                        $pid = (int)($it['productId'] ?? $it['id'] ?? $it['product_id'] ?? 0);
                        if ($pid > 0) {
                            $ids[$pid] = true;
                            $qtyMap[$pid] = ($qtyMap[$pid] ?? 0) + max(1, (int)($it['quantity'] ?? 1));
                        }
                    }
                    $totalCost = 0.0;
                    if (!empty($ids)) {
                        $ph = implode(',', array_fill(0, count($ids), '?'));
                        $stCost = $pdo->prepare("SELECT id, cost_price FROM products WHERE id IN ($ph)");
                        $stCost->execute(array_keys($ids));
                        foreach ($stCost->fetchAll(PDO::FETCH_ASSOC) as $cr) {
                            $totalCost += (float)($cr['cost_price'] ?? 0) * ($qtyMap[(int)$cr['id']] ?? 0);
                        }
                    }
                    if ($totalCost > 0) {
                        $m = MarginService::evaluateSafety($subtotal, $discountAmount, $totalCost, $campMinMargin);
                        if (empty($m['isSafe'])) {
                            $discountAmount = min($discountAmount, max(0.0, round((float)$m['maxAllowedDiscount'], 2)));
                        }
                    }
                } catch (Throwable $ignore) {}
            }
        }

        $discountAmount = round(min($discountAmount, $eligibleSubtotal, $subtotal), 2);

        return [
            'valid'            => true,
            'voucher'          => $voucher,
            'campaign'         => $campaign,
            'scopes'           => $scopes,
            'subtotal'         => round($subtotal, 2),
            'eligibleSubtotal' => round($eligibleSubtotal, 2),
            'discountAmount'   => $discountAmount,
            'finalTotal'       => max(0.0, round($subtotal - $discountAmount, 2)),
            'message'          => 'Áp dụng mã khuyến mãi thành công!'
        ];
    }

    /**
     * Atomically record voucher usage inside checkout transaction.
     * Caller MUST hold transaction & FOR UPDATE lock on voucher.
     */
    public static function redeemVoucher(
        PDO $pdo,
        array $voucher,
        int $orderId,
        string $orderCode,
        float $discountAmount,
        ?int $userId,
        ?string $customerPhone = null
    ): void {
        $voucherId = (int)$voucher['id'];
        $cleanPhone = self::normalizePhone($customerPhone);
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        // Enforce strict concurrency-safe increment
        $incStmt = $pdo->prepare("
            UPDATE vouchers
            SET used_count = used_count + 1, updated_at = NOW()
            WHERE id = :id AND (usage_limit <= 0 OR used_count < usage_limit)
        ");
        $incStmt->execute([':id' => $voucherId]);
        if ($incStmt->rowCount() !== 1) {
            throw new RuntimeException('Mã khuyến mãi vừa hết lượt sử dụng. Vui lòng kiểm tra lại.');
        }

        // Insert immutable usage log (uk_voucher_usages_order guarantees max 1 voucher per order)
        $logStmt = $pdo->prepare("
            INSERT INTO voucher_usages (voucher_id, voucher_code, order_id, order_code, user_id, customer_phone, discount_amount, status, ip_address, used_at)
            VALUES (:vid, :vcode, :oid, :ocode, :uid, :ph, :disc, 'used', :ip, NOW())
        ");
        $logStmt->execute([
            ':vid'   => $voucherId,
            ':vcode' => (string)$voucher['code'],
            ':oid'   => $orderId,
            ':ocode' => $orderCode,
            ':uid'   => $userId,
            ':ph'    => $cleanPhone !== '' ? $cleanPhone : null,
            ':disc'  => $discountAmount,
            ':ip'    => $ip
        ]);

        // Deduct campaign budget if linked & budget_limit > 0
        if (!empty($voucher['campaign_id']) && class_exists('BudgetService')) {
            $cChk = $pdo->prepare('SELECT budget_limit FROM campaigns WHERE id = :id LIMIT 1');
            $cChk->execute([':id' => (int)$voucher['campaign_id']]);
            $bLimit = (float)$cChk->fetchColumn();
            if ($bLimit > 0) {
                BudgetService::deductBudget($pdo, (int)$voucher['campaign_id'], $orderId, $discountAmount);
            }
        }
    }

    /**
     * Refund voucher usage & budget when an order is cancelled.
     */
    public static function refundVoucherForOrder(PDO $pdo, int $orderId): void
    {
        $vuStmt = $pdo->prepare("
            SELECT vu.id, vu.voucher_id, vu.discount_amount, v.campaign_id
            FROM voucher_usages vu
            LEFT JOIN vouchers v ON v.id = vu.voucher_id
            WHERE vu.order_id = :oid AND vu.status = 'used'
            FOR UPDATE
        ");
        $vuStmt->execute([':oid' => $orderId]);
        $usages = $vuStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($usages as $u) {
            $pdo->prepare("UPDATE voucher_usages SET status = 'refunded' WHERE id = :id")
                ->execute([':id' => (int)$u['id']]);
            $pdo->prepare("UPDATE vouchers SET used_count = GREATEST(used_count - 1, 0), updated_at = NOW() WHERE id = :vid")
                ->execute([':vid' => (int)$u['voucher_id']]);
            if (!empty($u['campaign_id']) && class_exists('BudgetService')) {
                $cChk = $pdo->prepare('SELECT budget_limit FROM campaigns WHERE id = :id LIMIT 1');
                $cChk->execute([':id' => (int)$u['campaign_id']]);
                if ((float)$cChk->fetchColumn() > 0) {
                    BudgetService::refundBudget($pdo, (int)$u['campaign_id'], $orderId, (float)$u['discount_amount']);
                }
            }
        }
    }

    /**
     * Get active, approved campaigns that are within budget and date range.
     */
    public static function getActiveCampaigns(PDO $pdo, ?int $productId = null): array
    {
        self::syncStatuses($pdo);
        $now = self::nowString();

        $sql = "SELECT c.* FROM campaigns c";
        if ($productId !== null) {
            $sql .= " INNER JOIN promotion_products pp ON pp.campaign_id = c.id AND pp.product_id = :productId";
        }
        $sql .= " WHERE c.status = 'active'
                    AND c.approval_status = 'approved'
                    AND c.start_at <= :now1
                    AND c.end_at   >= :now2
                    AND (c.budget_limit <= 0 OR c.budget_used < c.budget_limit)
                  ORDER BY c.start_at ASC";

        $stmt = $pdo->prepare($sql);
        $params = [':now1' => $now, ':now2' => $now];
        if ($productId !== null) {
            $params[':productId'] = $productId;
        }
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Calculate exact discount amount.
     */
    public static function calculateDiscount(string $discountType, float $discountValue, float $subtotal, ?float $maxDiscountAmount): float
    {
        if ($subtotal <= 0 || $discountValue <= 0) {
            return 0.0;
        }

        $discount = 0.0;
        if ($discountType === 'percentage') {
            $pct = min(100.0, max(0.0, $discountValue));
            $discount = $subtotal * ($pct / 100.0);
        } elseif ($discountType === 'fixed_amount') {
            $discount = $discountValue;
        }

        if ($maxDiscountAmount !== null && $maxDiscountAmount > 0 && $discount > $maxDiscountAmount) {
            $discount = $maxDiscountAmount;
        }

        if ($discount > $subtotal) {
            $discount = $subtotal;
        }

        return round(max(0.0, $discount), 2);
    }
}
