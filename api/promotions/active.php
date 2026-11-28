<?php
declare(strict_types=1);

/**
 * GET /api/promotions/active.php
 * Returns active, valid vouchers and active campaigns with scope & eligibility details for Client display.
 */
require_once __DIR__ . '/../../core/bootstrap.php';

api_only_method('GET');

try {
    $pdo = getDBConnection();
    PromotionService::syncStatuses($pdo);
    $now = PromotionService::nowString();

    $vStmt = $pdo->prepare("
        SELECT v.id, v.campaign_id, COALESCE(NULLIF(v.program_name,''), c.name, v.description, v.code) AS program_name,
               v.code, v.description, v.discount_type, v.discount_value,
               v.min_order_value, v.max_discount_amount, v.max_discount_per_order,
               v.usage_limit, v.used_count, v.per_user_limit,
               v.scope_type, v.customer_scope, v.start_at, v.end_at, v.status
        FROM vouchers v
        LEFT JOIN campaigns c ON c.id = v.campaign_id
        WHERE v.status = 'active'
          AND v.is_active = 1
          AND v.start_at <= :n1
          AND v.end_at >= :n2
          AND (v.usage_limit <= 0 OR v.used_count < v.usage_limit)
          AND (v.campaign_id IS NULL OR (c.approval_status = 'approved' AND c.status = 'active' AND (c.budget_limit <= 0 OR c.budget_used < c.budget_limit)))
        ORDER BY v.discount_type = 'percentage' DESC, v.end_at ASC, v.id DESC
    ");
    $vStmt->execute([':n1' => $now, ':n2' => $now]);
    $vouchers = $vStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($vouchers as &$v) {
        $v['id'] = (int)$v['id'];
        $v['discount_value'] = (float)$v['discount_value'];
        $v['min_order_value'] = (float)$v['min_order_value'];
        $v['max_discount_amount'] = $v['max_discount_amount'] !== null ? (float)$v['max_discount_amount'] : null;
        $v['max_discount_per_order'] = $v['max_discount_per_order'] !== null ? (float)$v['max_discount_per_order'] : null;
        $v['usage_limit'] = (int)$v['usage_limit'];
        $v['used_count'] = (int)$v['used_count'];
        $v['remaining_usage'] = $v['usage_limit'] > 0 ? max(0, $v['usage_limit'] - $v['used_count']) : null;
        $v['per_user_limit'] = (int)$v['per_user_limit'];

        $scopes = PromotionService::getVoucherScopes($pdo, $v['id'], $v['campaign_id'] ? (int)$v['campaign_id'] : null);
        $v['scope_categories'] = array_column($scopes['categories'], 'name');
        $v['scope_products'] = array_column($scopes['products'], 'name');
    }
    unset($v);

    $campaigns = PromotionService::getActiveCampaigns($pdo);

    json_response([
        'success'   => true,
        'count'     => count($vouchers),
        'vouchers'  => $vouchers,
        'campaigns' => $campaigns,
        'data'      => $vouchers
    ]);
} catch (Throwable $e) {
    error_log('[Promotions Active Error] ' . $e->getMessage());
    json_response(['success' => false, 'message' => 'Không thể tải danh sách khuyến mãi.'], 500);
}
