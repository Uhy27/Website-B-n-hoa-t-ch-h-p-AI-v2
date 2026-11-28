<?php
declare(strict_types=1);

/**
 * POST /api/vouchers/apply.php
 * Preview-validates voucher strictly on Backend (recalculating prices, scope, limits, margin)
 * WITHOUT incrementing used_count (usage is only recorded upon real order creation in checkout.php).
 */
require_once __DIR__ . '/../../core/bootstrap.php';

init_secure_session();
api_only_method('POST');

try {
    $pdo = getDBConnection();
    $in = get_json_input();
    $code = strtoupper(trim((string)($in['code'] ?? '')));
    $subtotal = (float)($in['subtotal'] ?? 0);
    $items = (isset($in['items']) && is_array($in['items'])) ? $in['items'] : [];
    $customerPhone = isset($in['phone']) ? (string)$in['phone'] : (isset($in['senderPhone']) ? (string)$in['senderPhone'] : null);
    $userId = !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

    if ($code === '') {
        json_response(['success' => false, 'message' => 'Vui lòng nhập mã khuyến mãi.'], 400);
    }
    if (empty($items)) {
        json_response(['success' => false, 'message' => 'Giỏ hàng của bạn đang trống.'], 400);
    }

    $res = PromotionService::checkVoucher($pdo, $code, $subtotal, $userId, $items, $customerPhone, false);
    if (empty($res['valid'])) {
        json_response(['success' => false, 'message' => $res['message']], 400);
    }

    $voucher = $res['voucher'];
    $serverSubtotal = (float)($res['subtotal'] ?? $subtotal);
    $eligibleSubtotal = (float)($res['eligibleSubtotal'] ?? $serverSubtotal);
    $discount = round(min((float)$res['discountAmount'], $eligibleSubtotal, $serverSubtotal), 2);
    $finalSubtotal = max(0.0, round($serverSubtotal - $discount, 2));

    json_response([
        'success'          => true,
        'voucherCode'      => $voucher['code'],
        'programName'      => $voucher['program_name'] ?? $voucher['code'],
        'discountType'     => $voucher['discount_type'],
        'discountValue'    => (float)$voucher['discount_value'],
        'subtotal'         => $serverSubtotal,
        'eligibleSubtotal' => $eligibleSubtotal,
        'discountAmount'   => $discount,
        'finalSubtotal'    => $finalSubtotal,
        'message'          => "Đã áp dụng mã {$voucher['code']} (-" . number_format($discount, 0, ',', '.') . '₫).'
    ]);
} catch (Throwable $e) {
    error_log('[Voucher Apply] ' . $e->getMessage());
    json_response(['success' => false, 'message' => 'Không thể xử lý mã giảm giá.'], 500);
}
