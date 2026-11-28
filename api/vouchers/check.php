<?php
declare(strict_types=1);

/** GET /api/vouchers/check.php?code=XXX */
require_once __DIR__ . '/../../core/bootstrap.php';

api_only_method('GET');

try {
    $code = strtoupper(trim((string)($_GET['code'] ?? '')));
    if ($code === '') {
        json_response(['success' => false, 'message' => 'Vui lòng cung cấp mã giảm giá.'], 400);
    }

    $pdo = getDBConnection();
    PromotionService::syncStatuses($pdo);
    $now = PromotionService::nowString();

    $stmt = $pdo->prepare("
        SELECT v.*, COALESCE(NULLIF(v.program_name,''), c.name, v.description, v.code) AS program_name
        FROM vouchers v
        LEFT JOIN campaigns c ON c.id = v.campaign_id
        WHERE UPPER(v.code) = :code
        LIMIT 1
    ");
    $stmt->execute([':code' => $code]);
    $v = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$v) {
        json_response(['success' => false, 'message' => 'Mã giảm giá không tồn tại.'], 404);
    }
    if ($v['status'] === 'draft') {
        json_response(['success' => false, 'message' => 'Mã giảm giá chưa được kích hoạt.'], 400);
    }
    if ($v['status'] === 'paused' || !(int)$v['is_active']) {
        json_response(['success' => false, 'message' => 'Mã giảm giá hiện đang tạm dừng.'], 400);
    }
    if ($v['status'] === 'scheduled' || $now < $v['start_at']) {
        json_response(['success' => false, 'message' => 'Mã giảm giá chưa đến thời gian áp dụng.'], 400);
    }
    if ($v['status'] === 'ended' || $now > $v['end_at']) {
        json_response(['success' => false, 'message' => 'Mã giảm giá đã hết hạn.'], 400);
    }
    if ((int)$v['usage_limit'] > 0 && (int)$v['used_count'] >= (int)$v['usage_limit']) {
        json_response(['success' => false, 'message' => 'Mã giảm giá đã hết lượt sử dụng.'], 400);
    }

    $scopes = PromotionService::getVoucherScopes($pdo, (int)$v['id'], $v['campaign_id'] ? (int)$v['campaign_id'] : null);

    json_response([
        'success' => true,
        'data'    => [
            'id'                     => (int)$v['id'],
            'program_name'           => $v['program_name'],
            'code'                   => $v['code'],
            'description'            => $v['description'],
            'discount_type'          => $v['discount_type'],
            'discount_value'         => (float)$v['discount_value'],
            'min_order_value'        => (float)$v['min_order_value'],
            'max_discount_amount'    => $v['max_discount_amount'] !== null ? (float)$v['max_discount_amount'] : null,
            'max_discount_per_order' => $v['max_discount_per_order'] !== null ? (float)$v['max_discount_per_order'] : null,
            'scope_type'             => $v['scope_type'] ?? 'all',
            'customer_scope'         => $v['customer_scope'] ?? 'all',
            'remaining_usage'        => (int)$v['usage_limit'] > 0 ? max(0, (int)$v['usage_limit'] - (int)$v['used_count']) : null,
            'scope_categories'       => array_column($scopes['categories'], 'name'),
            'scope_products'         => array_column($scopes['products'], 'name'),
            'expires_at'             => $v['end_at'],
        ]
    ]);
} catch (Throwable $e) {
    error_log('[Voucher Check] ' . $e->getMessage());
    json_response(['success' => false, 'message' => 'Không thể kiểm tra mã giảm giá.'], 500);
}
