<?php
declare(strict_types=1);

/**
 * POST /api/checkout.php - Xử lý đặt hàng Bloomora Florist
 * Chuẩn ACID Transaction, Server-side Pricing, Stock Deduction & Multi-Payment Support
 */

require_once __DIR__ . '/../core/bootstrap.php';

init_secure_session();
api_only_method('POST');

try {
    $pdo = getDBConnection();
    $input = get_json_input();

    // 1. Thu thập và chuẩn hóa dữ liệu từ Frontend
    $senderName    = sanitize_string($input['senderName'] ?? 'Khách hàng', 100);
    $senderPhone   = sanitize_string($input['senderPhone'] ?? '', 20);
    $isAnonymous   = !empty($input['isAnonymous']) ? 1 : 0;
    $receiverName  = sanitize_string($input['receiverName'] ?? '', 100);
    $receiverPhone = sanitize_string($input['receiverPhone'] ?? '', 20);
    $address       = sanitize_string($input['address'] ?? '', 255);
    $city          = sanitize_string($input['city'] ?? 'TP. Hồ Chí Minh', 50);
    $deliveryDate  = sanitize_string($input['deliveryDate'] ?? date('Y-m-d'), 20);
    $deliveryTime  = sanitize_string($input['deliveryTime'] ?? 'Hỏa tốc 60-90 phút', 50);
    $bannerMessage = sanitize_string($input['bannerMessage'] ?? '', 500);
    
    // Hỗ trợ cả 3 phương thức: vietqr, momo, cod
    $rawPaymentMethod = strtolower(trim((string)($input['paymentMethod'] ?? 'vietqr')));
    $validPaymentMethods = ['vietqr', 'momo', 'cod', 'bank_transfer'];
    $paymentMethod = in_array($rawPaymentMethod, $validPaymentMethods, true) ? $rawPaymentMethod : 'vietqr';

    $voucherCode   = sanitize_string($input['voucherCode'] ?? ($input['code'] ?? ''), 50);
    $items         = (isset($input['items']) && is_array($input['items'])) ? $input['items'] : [];
    $userId        = !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

    // 2. Validate dữ liệu người nhận và giỏ hàng
    if ($receiverName === '' || $receiverPhone === '' || $address === '') {
        json_response(['success' => false, 'message' => 'Vui lòng cung cấp đầy đủ tên, số điện thoại và địa chỉ người nhận.'], 400);
    }

    if (empty($items)) {
        json_response(['success' => false, 'message' => 'Giỏ hàng của bạn đang trống.'], 400);
    }

    // Giá niêm yết phụ kiện tặng kèm (server-side pricing)
    $addonPrices = [
        'gau-bong' => ['name' => 'Gấu Bông Teddy Cao Cấp', 'price' => 80000],
        'socola'   => ['name' => 'Hộp Socola Ferrero Rocher', 'price' => 120000],
        'nen-thom' => ['name' => 'Nến Thơm Tinh Dầu Tự Nhiên', 'price' => 150000],
        'banh-kem' => ['name' => 'Bánh Kem Mini Chúc Mừng', 'price' => 180000],
    ];

    // 3. Khởi tạo Database Transaction
    $pdo->beginTransaction();

    // Query khóa hàng FOR UPDATE để chống race-condition / bán vượt tồn kho
    // Lưu ý: Chỉ chọn các cột thực tế có trong bảng products (không query cost_price)
    $productStmt = $pdo->prepare("SELECT id, name, sku, price, sale_price, stock_quantity, stock_status, thumbnail
                                  FROM products 
                                  WHERE (id = :id OR sku = :sku) AND is_active = 1 
                                  LIMIT 1 FOR UPDATE");

    $updateStockStmt = $pdo->prepare("UPDATE products 
                                      SET stock_quantity = :stock_after,
                                          stock_status = :stock_status
                                      WHERE id = :id");

    // Kiểm tra an toàn bảng inventory_logs trước khi chuẩn bị câu lệnh
    $hasInventoryLogs = (bool)$pdo->query("SHOW TABLES LIKE 'inventory_logs'")->fetch();
    $logStmt = null;
    if ($hasInventoryLogs) {
        $logStmt = $pdo->prepare("INSERT INTO inventory_logs (product_id, change_quantity, remaining_quantity, reason, order_id, user_id, note, created_at)
                                  VALUES (:pid, :chg, :rem, 'sale', :oid, :uid, :note, NOW())");
    }

    $subtotal = 0.0;
    $orderItemsData = [];

    // 4. Kiểm tra từng sản phẩm và tính toán giá trị đơn hàng thực tế
    foreach ($items as $item) {
        $itemId = $item['id'] ?? ($item['productId'] ?? null);
        $qty = max(1, (int)($item['quantity'] ?? 1));
        if ($itemId === null || $itemId === '') continue;

        $productStmt->execute([':id' => $itemId, ':sku' => (string)$itemId]);
        $prod = $productStmt->fetch();
        if (!$prod) {
            $pdo->rollBack();
            json_response(['success' => false, 'message' => "Sản phẩm ID #{$itemId} không tồn tại hoặc đã ngừng bán."], 400);
        }

        $stockQty = (int)$prod['stock_quantity'];
        if ($stockQty < $qty || $prod['stock_status'] === 'out_of_stock') {
            $pdo->rollBack();
            $avail = max(0, $stockQty);
            $msg = ($avail === 0)
                ? "Sản phẩm '{$prod['name']}' hiện đã hết hàng."
                : "Sản phẩm '{$prod['name']}' chỉ còn {$avail} sản phẩm trong kho (bạn đặt {$qty}).";
            json_response(['success' => false, 'message' => $msg], 400);
        }

        $unitPrice = (float)$prod['price'];
        $addonsExtra = 0.0; 
        $addonNames = [];

        if (!empty($item['addons']) && is_array($item['addons'])) {
            foreach ($item['addons'] as $add) {
                $aid = is_array($add) ? ($add['id'] ?? '') : (string)$add;
                if (isset($addonPrices[$aid])) {
                    $addonsExtra += (float)$addonPrices[$aid]['price'];
                    $addonNames[] = $addonPrices[$aid]['name'];
                }
            }
        }

        $lineUnit = $unitPrice + $addonsExtra;
        $lineTotal = $lineUnit * $qty;
        $subtotal += $lineTotal;

        $displayName = $prod['name'] . (!empty($addonNames) ? ' (Kèm: ' . implode(', ', $addonNames) . ')' : '');

        $orderItemsData[] = [
            'product_id'   => (int)$prod['id'],
            'product_name' => $displayName,
            'product_sku'  => (string)$prod['sku'],
            'unit_price'   => $lineUnit,
            'quantity'     => $qty,
            'total_price'  => $lineTotal,
            'stock_after'  => $stockQty - $qty
        ];
    }

    if (empty($orderItemsData)) {
        $pdo->rollBack();
        json_response(['success' => false, 'message' => 'Không có sản phẩm hợp lệ trong giỏ hàng.'], 400);
    }

    // 5. Xử lý Voucher / Mã giảm giá nếu có (Server-side validation)
    $discountAmount = 0.0;
    $appliedVoucher = null;
    if ($voucherCode !== '' && class_exists('PromotionService')) {
        try {
            $res = PromotionService::checkVoucher($pdo, $voucherCode, $subtotal, $userId);
            if (!empty($res['valid'])) {
                $appliedVoucher = $res['voucher'] ?? null;
                $discountAmount = (float)($res['discountAmount'] ?? 0.0);
            }
        } catch (Throwable $ignore) {
            // Không để lỗi voucher làm sập luồng đặt hàng
        }
    }

    $discountAmount = round(min($discountAmount, $subtotal), 2);
    $shippingFee = 0.00; // Miễn phí giao hoa toàn quốc theo chính sách Bloomora
    $totalAmount = max(0.0, round($subtotal - $discountAmount + $shippingFee, 2));

    // 6. Tạo mã đơn hàng duy nhất dạng BLxxxxx
    $orderCode = '';
    for ($i = 0; $i < 10; $i++) {
        $candidate = 'BL' . random_int(10000, 99999);
        $chk = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE order_code = :c');
        $chk->execute([':c' => $candidate]);
        if ((int)$chk->fetchColumn() === 0) {
            $orderCode = $candidate;
            break;
        }
    }
    if ($orderCode === '') {
        $orderCode = 'BL' . date('ymdHis');
    }

    // 7. Tạo bản ghi đơn hàng trong bảng `orders`
    // Đối với tất cả phương thức (COD, VietQR, MoMo), trạng thái khởi tạo là 'unpaid', trạng thái đơn là 'received'
    $hasVoucherCol = (bool)$pdo->query("SHOW COLUMNS FROM orders LIKE 'voucher_code'")->fetch();
    if ($hasVoucherCol) {
        $orderSql = "INSERT INTO orders (
            order_code, user_id, sender_name, sender_phone, is_anonymous,
            receiver_name, receiver_phone, receiver_address, city,
            delivery_date, delivery_time_slot, card_message, ribbon_message,
            voucher_code, subtotal, shipping_fee, discount_amount, total_amount,
            payment_method, payment_status, order_status
        ) VALUES (
            :order_code, :user_id, :sender_name, :sender_phone, :is_anonymous,
            :receiver_name, :receiver_phone, :receiver_address, :city,
            :delivery_date, :delivery_time_slot, :card_message, :ribbon_message,
            :voucher_code, :subtotal, :shipping_fee, :discount_amount, :total_amount,
            :payment_method, 'unpaid', 'received'
        )";
    } else {
        $orderSql = "INSERT INTO orders (
            order_code, user_id, sender_name, sender_phone, is_anonymous,
            receiver_name, receiver_phone, receiver_address, city,
            delivery_date, delivery_time_slot, card_message, ribbon_message,
            subtotal, shipping_fee, discount_amount, total_amount,
            payment_method, payment_status, order_status
        ) VALUES (
            :order_code, :user_id, :sender_name, :sender_phone, :is_anonymous,
            :receiver_name, :receiver_phone, :receiver_address, :city,
            :delivery_date, :delivery_time_slot, :card_message, :ribbon_message,
            :subtotal, :shipping_fee, :discount_amount, :total_amount,
            :payment_method, 'unpaid', 'received'
        )";
    }

    $params = [
        ':order_code'          => $orderCode,
        ':user_id'             => $userId,
        ':sender_name'         => $senderName,
        ':sender_phone'        => $senderPhone,
        ':is_anonymous'        => $isAnonymous,
        ':receiver_name'       => $receiverName,
        ':receiver_phone'      => $receiverPhone,
        ':receiver_address'    => $address,
        ':city'                => $city,
        ':delivery_date'       => $deliveryDate,
        ':delivery_time_slot'  => $deliveryTime,
        ':card_message'        => $bannerMessage,
        ':ribbon_message'      => $bannerMessage,
        ':subtotal'            => $subtotal,
        ':shipping_fee'        => $shippingFee,
        ':discount_amount'     => $discountAmount,
        ':total_amount'        => $totalAmount,
        ':payment_method'      => $paymentMethod
    ];
    if ($hasVoucherCol) {
        $params[':voucher_code'] = ($appliedVoucher ? $appliedVoucher['code'] : null);
    }

    $pdo->prepare($orderSql)->execute($params);
    $orderId = (int)$pdo->lastInsertId();

    // 8. Tạo chi tiết đơn hàng trong `order_items` và trừ tồn kho trong `products`
    $itemInsert = $pdo->prepare("INSERT INTO order_items (
        order_id, product_id, product_name, product_sku, unit_price, quantity, total_price
    ) VALUES (
        :order_id, :product_id, :product_name, :product_sku, :unit_price, :quantity, :total_price
    )");

    foreach ($orderItemsData as $it) {
        $itemInsert->execute([
            ':order_id'     => $orderId,
            ':product_id'   => $it['product_id'],
            ':product_name' => $it['product_name'],
            ':product_sku'  => $it['product_sku'],
            ':unit_price'   => $it['unit_price'],
            ':quantity'     => $it['quantity'],
            ':total_price'  => $it['total_price']
        ]);

        $newStockStatus = ($it['stock_after'] <= 0) ? 'out_of_stock' : (($it['stock_after'] <= 5) ? 'low_stock' : 'in_stock');
        $updateStockStmt->execute([
            ':stock_after'  => $it['stock_after'],
            ':stock_status' => $newStockStatus,
            ':id'           => $it['product_id']
        ]);

        if ($logStmt !== null) {
            try {
                $logStmt->execute([
                    ':pid'  => $it['product_id'],
                    ':chg'  => -$it['quantity'],
                    ':rem'  => $it['stock_after'],
                    ':oid'  => $orderId,
                    ':uid'  => $userId,
                    ':note' => 'Bán hàng đơn ' . $orderCode
                ]);
            } catch (Throwable $ignore) {}
        }
    }

    // 9. Cập nhật lịch sử trạng thái đơn hoa trong `order_status_history`
    $hasHistoryTable = (bool)$pdo->query("SHOW TABLES LIKE 'order_status_history'")->fetch();
    if ($hasHistoryTable) {
        $pdo->prepare("INSERT INTO order_status_history (order_id, status, note, created_by) VALUES (:oid, 'received', 'Hệ thống đã tiếp nhận đơn hàng', :uid)")
            ->execute([':oid' => $orderId, ':uid' => $userId]);
    }

    // 10. Hoàn tất Transaction
    $pdo->commit();

    // 11. Sinh URL VietQR động theo chuẩn Napas247
    $vietqrUrl = "https://img.vietqr.io/image/MB-0908123456-compact.png?amount=" . (int)round($totalAmount) . "&addInfo={$orderCode}&accountName=BLOOMORA%20FLORIST";

    json_response([
        'success' => true,
        'message' => 'Đặt hoa thành công!',
        'data'    => [
            'orderId'        => $orderId,
            'orderCode'      => $orderCode,
            'subtotal'       => $subtotal,
            'discountAmount' => $discountAmount,
            'shippingFee'    => $shippingFee,
            'totalAmount'    => $totalAmount,
            'paymentMethod'  => $paymentMethod,
            'paymentStatus'  => 'unpaid',
            'orderStatus'    => 'received',
            'vietqrUrl'      => $vietqrUrl,
            'senderName'     => $isAnonymous ? 'Người tặng ẩn danh' : $senderName,
            'senderPhone'    => $senderPhone,
            'receiverName'   => $receiverName,
            'receiverPhone'  => $receiverPhone,
            'address'        => $address,
            'city'           => $city,
            'deliveryDate'   => $deliveryDate,
            'deliveryTime'   => $deliveryTime,
            'bannerMessage'  => $bannerMessage
        ]
    ]);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[Checkout Error] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    json_response([
        'success' => false,
        'message' => 'Không thể hoàn tất đơn hàng. Vui lòng thử lại sau.'
    ], 500);
}
