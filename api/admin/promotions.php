<?php
declare(strict_types=1);

/**
 * ADMIN PROMOTIONS & VOUCHERS API
 * Supports complete professional promotion management:
 * - GET ?id=X               : Full detail + scopes + usage history + stats + batch codes
 * - GET ?check_code=XXX     : Realtime duplicate code check
 * - GET (list)              : Search, status/type/date filters, sorting, pagination, KPI summary
 * - POST action=generate_code : Generate unique code preview(s)
 * - POST action=create_promotion (or create_voucher / create_campaign)
 * - POST action=update_promotion / PUT ?id=X : Edit with financial integrity protection if used
 * - POST action=pause / resume / end_now / toggle_voucher / approve / pause_campaign
 * - DELETE ?id=X            : Safe delete only when never used
 */

require_once __DIR__ . '/../../core/bootstrap.php';

require_admin_auth();

/**
 * Resolve appropriate status based on Vietnam time & user choice.
 */
function resolvePromotionStatus(string $requestedStatus, string $startAt, string $endAt, string $now): string
{
    if ($requestedStatus === 'draft') {
        return 'draft';
    }
    if ($requestedStatus === 'paused') {
        return 'paused';
    }
    if ($requestedStatus === 'ended' || $endAt < $now) {
        return 'ended';
    }
    if ($startAt > $now) {
        return 'scheduled';
    }
    return 'active';
}

/**
 * Parse and validate scope IDs arrays.
 */
function saveVoucherScopes(PDO $pdo, int $voucherId, ?int $campaignId, string $scopeType, array $categoryIds, array $productIds): void
{
    $pdo->prepare('DELETE FROM promotion_categories WHERE voucher_id = :vid')->execute([':vid' => $voucherId]);
    $pdo->prepare('DELETE FROM promotion_products WHERE voucher_id = :vid')->execute([':vid' => $voucherId]);

    if ($scopeType === 'category' && !empty($categoryIds)) {
        $ins = $pdo->prepare('INSERT INTO promotion_categories (campaign_id, voucher_id, category_id) VALUES (:cid, :vid, :catid)');
        foreach (array_unique(array_map('intval', $categoryIds)) as $catId) {
            if ($catId > 0) {
                $ins->execute([':cid' => $campaignId, ':vid' => $voucherId, ':catid' => $catId]);
            }
        }
    } elseif ($scopeType === 'product' && !empty($productIds)) {
        $ins = $pdo->prepare('INSERT INTO promotion_products (campaign_id, voucher_id, product_id) VALUES (:cid, :vid, :pid)');
        foreach (array_unique(array_map('intval', $productIds)) as $prodId) {
            if ($prodId > 0) {
                $ins->execute([':cid' => $campaignId, ':vid' => $voucherId, ':pid' => $prodId]);
            }
        }
    }
}

try {
    $pdo = getDBConnection();
    PromotionService::syncStatuses($pdo);
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $adminId = (int)($_SESSION['user_id'] ?? 0);
    $now = PromotionService::nowString();

    // =========================================================================
    // GET: Single detail (?id=X), Duplicate code check (?check_code=XXX), or List
    // =========================================================================
    if ($method === 'GET') {
        if (isset($_GET['check_code'])) {
            $code = strtoupper(trim((string)$_GET['check_code']));
            $excludeId = (int)($_GET['exclude_id'] ?? 0);
            if ($code === '') {
                json_response(['success' => true, 'available' => false, 'message' => 'Mã không được trống.']);
            }
            $sql = 'SELECT COUNT(*) FROM vouchers WHERE UPPER(code) = :c';
            $params = [':c' => $code];
            if ($excludeId > 0) {
                $sql .= ' AND id != :id';
                $params[':id'] = $excludeId;
            }
            $st = $pdo->prepare($sql);
            $st->execute($params);
            $exists = (int)$st->fetchColumn() > 0;
            json_response([
                'success'   => true,
                'available' => !$exists,
                'message'   => $exists ? "Mã \"{$code}\" đã tồn tại trong hệ thống." : "Mã \"{$code}\" hợp lệ và chưa được sử dụng."
            ]);
        }

        // Single Promotion Detail
        $detailId = (int)($_GET['id'] ?? 0);
        if ($detailId > 0) {
            $stmt = $pdo->prepare("
                SELECT v.*, COALESCE(NULLIF(v.program_name,''), c.name, v.description, v.code) AS program_name,
                       c.name AS campaign_name, c.campaign_type, c.budget_limit, c.budget_used, c.min_margin,
                       u.full_name AS creator_name
                FROM vouchers v
                LEFT JOIN campaigns c ON c.id = v.campaign_id
                LEFT JOIN users u ON u.id = v.created_by
                WHERE v.id = :id
                LIMIT 1
            ");
            $stmt->execute([':id' => $detailId]);
            $promo = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$promo) {
                json_response(['success' => false, 'message' => 'Không tìm thấy chương trình khuyến mãi.'], 404);
            }

            $scopes = PromotionService::getVoucherScopes($pdo, $detailId, $promo['campaign_id'] ? (int)$promo['campaign_id'] : null);

            // Usage history with order & user details
            $uStmt = $pdo->prepare("
                SELECT vu.id, vu.voucher_id, vu.voucher_code, vu.order_id,
                       COALESCE(NULLIF(vu.order_code,''), o.order_code) AS order_code,
                       vu.user_id, vu.customer_phone, vu.discount_amount, vu.status, vu.used_at,
                       COALESCE(u.full_name, o.sender_name, 'Khách vãng lai') AS customer_name,
                       COALESCE(u.email, '') AS customer_email,
                       COALESCE(o.order_status, 'unknown') AS order_status,
                       COALESCE(o.payment_status, 'unpaid') AS payment_status,
                       COALESCE(o.total_amount, 0) AS order_total
                FROM voucher_usages vu
                LEFT JOIN users u ON u.id = vu.user_id
                LEFT JOIN orders o ON o.id = vu.order_id
                WHERE vu.voucher_id = :vid
                ORDER BY vu.used_at DESC, vu.id DESC
            ");
            $uStmt->execute([':vid' => $detailId]);
            $usages = $uStmt->fetchAll(PDO::FETCH_ASSOC);

            $totalDiscountGiven = 0.0;
            $activeUsagesCount = 0;
            foreach ($usages as $us) {
                if ($us['status'] === 'used') {
                    $totalDiscountGiven += (float)$us['discount_amount'];
                    $activeUsagesCount++;
                }
            }

            // Related batch codes if created as multi-code campaign
            $batchCodes = [];
            if (!empty($promo['batch_code'])) {
                $bStmt = $pdo->prepare('SELECT id, code, used_count, usage_limit, status FROM vouchers WHERE batch_code = :bc ORDER BY id ASC');
                $bStmt->execute([':bc' => $promo['batch_code']]);
                $batchCodes = $bStmt->fetchAll(PDO::FETCH_ASSOC);
            }

            $promo['id'] = (int)$promo['id'];
            $promo['discount_value'] = (float)$promo['discount_value'];
            $promo['min_order_value'] = (float)$promo['min_order_value'];
            $promo['max_discount_amount'] = $promo['max_discount_amount'] !== null ? (float)$promo['max_discount_amount'] : null;
            $promo['max_discount_per_order'] = $promo['max_discount_per_order'] !== null ? (float)$promo['max_discount_per_order'] : null;
            $promo['usage_limit'] = (int)$promo['usage_limit'];
            $promo['used_count'] = (int)$promo['used_count'];
            $promo['per_user_limit'] = (int)$promo['per_user_limit'];
            $promo['remaining_usage'] = $promo['usage_limit'] > 0 ? max(0, $promo['usage_limit'] - $promo['used_count']) : null;
            $promo['total_discount_given'] = round($totalDiscountGiven, 2);
            $promo['scopes'] = $scopes;
            $promo['usages'] = $usages;
            $promo['batch_codes'] = $batchCodes;
            $promo['is_locked_financials'] = ((int)$promo['used_count'] > 0 || count($usages) > 0);

            json_response(['success' => true, 'data' => $promo]);
        }

        // Legacy ?vouchers=1 parameter preserved alongside main filtered query
        $search = trim((string)($_GET['search'] ?? ''));
        $statusFilter = trim((string)($_GET['status'] ?? 'all'));
        $typeFilter = trim((string)($_GET['discount_type'] ?? 'all'));
        $dateFrom = trim((string)($_GET['date_from'] ?? ''));
        $dateTo = trim((string)($_GET['date_to'] ?? ''));
        $sort = trim((string)($_GET['sort'] ?? 'created_desc'));
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $where = [];
        $params = [];

        if ($search !== '') {
            $where[] = "(v.code LIKE :s1 OR v.program_name LIKE :s2 OR c.name LIKE :s3 OR v.description LIKE :s4)";
            $kw = '%' . $search . '%';
            $params[':s1'] = $kw;
            $params[':s2'] = $kw;
            $params[':s3'] = $kw;
            $params[':s4'] = $kw;
        }
        if ($statusFilter !== '' && $statusFilter !== 'all') {
            $where[] = "v.status = :st";
            $params[':st'] = $statusFilter;
        }
        if ($typeFilter !== '' && $typeFilter !== 'all') {
            $where[] = "v.discount_type = :dt";
            $params[':dt'] = $typeFilter;
        }
        if ($dateFrom !== '') {
            $where[] = "v.end_at >= :df";
            $params[':df'] = $dateFrom . ' 00:00:00';
        }
        if ($dateTo !== '') {
            $where[] = "v.start_at <= :dt_to";
            $params[':dt_to'] = $dateTo . ' 23:59:59';
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $orderBy = 'v.created_at DESC, v.id DESC';
        if ($sort === 'created_asc') {
            $orderBy = 'v.created_at ASC, v.id ASC';
        } elseif ($sort === 'end_asc') {
            $orderBy = 'v.end_at ASC';
        } elseif ($sort === 'end_desc') {
            $orderBy = 'v.end_at DESC';
        } elseif ($sort === 'used_desc') {
            $orderBy = 'v.used_count DESC';
        }

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM vouchers v LEFT JOIN campaigns c ON c.id = v.campaign_id $whereSql");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $sql = "
            SELECT v.id, v.campaign_id,
                   COALESCE(NULLIF(v.program_name,''), c.name, v.description, v.code) AS program_name,
                   c.name AS campaign_name,
                   v.code, v.prefix, v.batch_code, v.description,
                   v.discount_type, v.discount_value, v.min_order_value,
                   v.max_discount_amount, v.max_discount_per_order,
                   v.usage_limit, v.used_count,
                   CASE WHEN v.usage_limit > 0 THEN GREATEST(v.usage_limit - v.used_count, 0) ELSE NULL END AS remaining_usage,
                   v.per_user_limit, v.scope_type, v.customer_scope,
                   v.start_at, v.end_at, v.status, v.is_active, v.created_at, v.updated_at,
                   (SELECT COUNT(*) FROM promotion_categories pc WHERE pc.voucher_id = v.id) AS scope_cat_count,
                   (SELECT COUNT(*) FROM promotion_products pp WHERE pp.voucher_id = v.id) AS scope_prod_count,
                   COALESCE((SELECT SUM(vu.discount_amount) FROM voucher_usages vu WHERE vu.voucher_id = v.id AND vu.status = 'used'), 0) AS total_discount_given
            FROM vouchers v
            LEFT JOIN campaigns c ON c.id = v.campaign_id
            $whereSql
            ORDER BY $orderBy
            LIMIT :limit OFFSET :offset
        ";
        $st = $pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $st->bindValue($k, $v);
        }
        $st->bindValue(':limit', $limit, PDO::PARAM_INT);
        $st->bindValue(':offset', $offset, PDO::PARAM_INT);
        $st->execute();
        $items = $st->fetchAll(PDO::FETCH_ASSOC);

        foreach ($items as &$it) {
            $it['id'] = (int)$it['id'];
            $it['discount_value'] = (float)$it['discount_value'];
            $it['min_order_value'] = (float)$it['min_order_value'];
            $it['max_discount_amount'] = $it['max_discount_amount'] !== null ? (float)$it['max_discount_amount'] : null;
            $it['max_discount_per_order'] = $it['max_discount_per_order'] !== null ? (float)$it['max_discount_per_order'] : null;
            $it['usage_limit'] = (int)$it['usage_limit'];
            $it['used_count'] = (int)$it['used_count'];
            $it['remaining_usage'] = $it['remaining_usage'] !== null ? (int)$it['remaining_usage'] : null;
            $it['per_user_limit'] = (int)$it['per_user_limit'];
            $it['total_discount_given'] = (float)$it['total_discount_given'];
        }
        unset($it);

        // KPI Stats
        $kpiRow = $pdo->query("
            SELECT COUNT(*) AS total_count,
                   COALESCE(SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END), 0) AS active_count,
                   COALESCE(SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END), 0) AS scheduled_count,
                   COALESCE(SUM(CASE WHEN status = 'paused' THEN 1 ELSE 0 END), 0) AS paused_count,
                   COALESCE(SUM(CASE WHEN status = 'ended' THEN 1 ELSE 0 END), 0) AS ended_count,
                   COALESCE(SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END), 0) AS draft_count,
                   COALESCE(SUM(used_count), 0) AS total_used_count
            FROM vouchers
        ")->fetch(PDO::FETCH_ASSOC);

        $totalSavedMoney = (float)$pdo->query("SELECT COALESCE(SUM(discount_amount), 0) FROM voucher_usages WHERE status = 'used'")->fetchColumn();

        if (isset($_GET['vouchers']) && $_GET['vouchers'] == '1') {
            json_response(['success' => true, 'data' => $items]);
        }

        json_response([
            'success' => true,
            'data'    => [
                'vouchers'   => $items,
                'items'      => $items,
                'stats'      => [
                    'total'       => (int)($kpiRow['total_count'] ?? 0),
                    'active'      => (int)($kpiRow['active_count'] ?? 0),
                    'scheduled'   => (int)($kpiRow['scheduled_count'] ?? 0),
                    'paused'      => (int)($kpiRow['paused_count'] ?? 0),
                    'ended'       => (int)($kpiRow['ended_count'] ?? 0),
                    'draft'       => (int)($kpiRow['draft_count'] ?? 0),
                    'total_used'  => (int)($kpiRow['total_used_count'] ?? 0),
                    'total_saved' => round($totalSavedMoney, 2),
                ],
                'pagination' => [
                    'total'       => $total,
                    'page'        => $page,
                    'limit'       => $limit,
                    'total_pages' => max(1, (int)ceil($total / $limit)),
                ]
            ]
        ]);
    }

    // =========================================================================
    // POST: Create, Update, Generate Code, Pause, Resume, End Now
    // =========================================================================
    if ($method === 'POST' || $method === 'PUT') {
        $in = get_json_input();
        $action = $method === 'PUT' ? 'update_promotion' : trim((string)($in['action'] ?? 'create_promotion'));

        // 1. Auto generate unique code(s) preview
        if ($action === 'generate_code') {
            $prefix = trim((string)($in['prefix'] ?? 'BLOOM'));
            $count = max(1, min(20, (int)($in['count'] ?? 1)));
            $codes = [];
            $seen = [];
            for ($i = 0; $i < $count; $i++) {
                do {
                    $c = PromotionService::generateUniqueCode($pdo, $prefix, 5);
                } while (isset($seen[$c]));
                $seen[$c] = true;
                $codes[] = $c;
            }
            json_response(['success' => true, 'code' => $codes[0], 'codes' => $codes]);
        }

        // 2. Create Promotion (1 shared code or batch of unique codes)
        if ($action === 'create_promotion' || $action === 'create_voucher') {
            $programName = sanitize_string($in['program_name'] ?? $in['name'] ?? '', 200);
            $description = isset($in['description']) ? trim((string)$in['description']) : null;
            $discountType = trim((string)($in['discount_type'] ?? ''));
            $discountValue = isset($in['discount_value']) ? (float)$in['discount_value'] : 0.0;
            $maxDiscountAmount = isset($in['max_discount_amount']) && $in['max_discount_amount'] !== '' && $in['max_discount_amount'] !== null
                ? (float)$in['max_discount_amount'] : null;
            $maxDiscountPerOrder = isset($in['max_discount_per_order']) && $in['max_discount_per_order'] !== '' && $in['max_discount_per_order'] !== null
                ? (float)$in['max_discount_per_order'] : null;
            $minOrderValue = max(0.0, (float)($in['min_order_value'] ?? 0));
            $usageLimit = max(0, (int)($in['usage_limit'] ?? 100));
            $perUserLimit = max(0, (int)($in['per_user_limit'] ?? 1));
            $startAt = str_replace('T', ' ', trim((string)($in['start_at'] ?? '')));
            $endAt = str_replace('T', ' ', trim((string)($in['end_at'] ?? '')));
            if (strlen($startAt) === 16) $startAt .= ':00';
            if (strlen($endAt) === 16) $endAt .= ':00';

            $rawScopeType = trim((string)($in['scope_type'] ?? 'all'));
            $scopeType = in_array($rawScopeType, ['all', 'category', 'product'], true) ? $rawScopeType : 'all';
            $rawCustomerScope = trim((string)($in['customer_scope'] ?? 'all'));
            $customerScope = in_array($rawCustomerScope, ['all', 'new_customer', 'vip'], true) ? $rawCustomerScope : 'all';
            $categoryIds = is_array($in['category_ids'] ?? null) ? $in['category_ids'] : [];
            $productIds = is_array($in['product_ids'] ?? null) ? $in['product_ids'] : [];
            $requestedStatus = trim((string)($in['status'] ?? 'active'));

            $codeMode = trim((string)($in['code_mode'] ?? 'single')); // 'single' or 'batch'
            $prefix = strtoupper(preg_replace('/[^A-Z0-9]/i', '', trim((string)($in['prefix'] ?? 'BLOOM')))) ?: 'BLOOM';
            $manualCode = strtoupper(trim((string)($in['code'] ?? '')));
            $batchCount = $codeMode === 'batch' ? max(2, min(50, (int)($in['batch_count'] ?? 5))) : 1;

            // Validation
            if ($programName === '' && $action === 'create_promotion') {
                json_response(['success' => false, 'message' => 'Tên chương trình khuyến mãi là bắt buộc.'], 400);
            }
            if ($programName === '') {
                $programName = 'Ưu đãi ' . ($manualCode ?: $prefix);
            }
            if (!in_array($discountType, ['percentage', 'fixed_amount'], true)) {
                json_response(['success' => false, 'message' => 'Vui lòng chọn loại giảm giá hợp lệ (% hoặc VNĐ).'], 400);
            }
            if ($discountValue <= 0) {
                json_response(['success' => false, 'message' => 'Giá trị giảm phải lớn hơn 0.'], 400);
            }
            if ($discountType === 'percentage' && $discountValue > 100) {
                json_response(['success' => false, 'message' => 'Mức giảm theo phần trăm không được vượt quá 100%.'], 400);
            }
            if ($startAt === '' || $endAt === '' || strtotime($startAt) === false || strtotime($endAt) === false) {
                json_response(['success' => false, 'message' => 'Thời gian bắt đầu và kết thúc không hợp lệ.'], 400);
            }
            if (strtotime($endAt) <= strtotime($startAt)) {
                json_response(['success' => false, 'message' => 'Thời gian kết thúc phải lớn hơn thời gian bắt đầu.'], 400);
            }
            if ($scopeType === 'category' && empty($categoryIds)) {
                json_response(['success' => false, 'message' => 'Vui lòng chọn ít nhất 1 danh mục áp dụng.'], 400);
            }
            if ($scopeType === 'product' && empty($productIds)) {
                json_response(['success' => false, 'message' => 'Vui lòng chọn ít nhất 1 sản phẩm áp dụng.'], 400);
            }

            $finalStatus = resolvePromotionStatus($requestedStatus, $startAt, $endAt, $now);
            $isActive = ($finalStatus === 'active' || $finalStatus === 'scheduled') ? 1 : 0;
            if ($finalStatus === 'draft' || $finalStatus === 'paused' || $finalStatus === 'ended') {
                $isActive = 0;
            }

            $pdo->beginTransaction();

            // Create companion campaign record for reporting consistency & margin checks
            $campaignId = !empty($in['campaign_id']) ? (int)$in['campaign_id'] : null;
            if ($campaignId === null) {
                $campSlug = bloomora_slugify($programName) . '-' . substr(bin2hex(random_bytes(3)), 0, 5);
                $campStatus = $finalStatus === 'ended' ? 'expired' : $finalStatus;
                    $minMarginVal = isset($in['min_margin']) && is_numeric($in['min_margin']) ? max(0.0, (float)$in['min_margin']) : 0.0;
                    $cStmt = $pdo->prepare("
                    INSERT INTO campaigns (
                        name, slug, description, campaign_type, discount_type, discount_value,
                        min_order_value, max_discount_amount, max_discount_per_order,
                        budget_limit, budget_used, min_margin, scope_type, customer_scope,
                        start_at, end_at, allow_stacking, status, approval_status, created_by
                    ) VALUES (
                        :name, :slug, :desc, 'holiday', :dtype, :dval,
                        :min_ord, :max_d, :max_per,
                        0, 0, :min_margin, :scope, :cscope,
                        :sat, :eat, 0, :st, 'approved', :uid
                    )
                ");
                $cStmt->execute([
                    ':name'       => $programName,
                    ':slug'       => $campSlug,
                    ':desc'       => $description ?: null,
                    ':dtype'      => $discountType,
                    ':dval'       => $discountValue,
                    ':min_ord'    => $minOrderValue,
                    ':max_d'      => $maxDiscountAmount,
                    ':max_per'    => $maxDiscountPerOrder,
                    ':min_margin' => $minMarginVal,
                    ':scope'      => $scopeType,
                    ':cscope'     => $customerScope,
                    ':sat'        => $startAt,
                    ':eat'        => $endAt,
                    ':st'         => $campStatus,
                    ':uid'        => $adminId ?: null,
                ]);
                $campaignId = (int)$pdo->lastInsertId();
            }

            // Prepare codes to insert
            $codesToCreate = [];
            $batchGroup = null;
            if ($codeMode === 'single') {
                if ($manualCode !== '') {
                    $cleanCode = preg_replace('/[^A-Z0-9_-]/', '', $manualCode);
                    if (strlen($cleanCode) < 3 || strlen($cleanCode) > 50) {
                        $pdo->rollBack();
                        json_response(['success' => false, 'message' => 'Mã khuyến mãi phải từ 3–50 ký tự chữ không dấu hoặc số.'], 400);
                    }
                    $chk = $pdo->prepare('SELECT COUNT(*) FROM vouchers WHERE UPPER(code) = :c');
                    $chk->execute([':c' => $cleanCode]);
                    if ((int)$chk->fetchColumn() > 0) {
                        $pdo->rollBack();
                        json_response(['success' => false, 'message' => "Mã khuyến mãi \"{$cleanCode}\" đã tồn tại trong hệ thống."], 409);
                    }
                    $codesToCreate[] = $cleanCode;
                } else {
                    $codesToCreate[] = PromotionService::generateUniqueCode($pdo, $prefix, 5);
                }
            } else {
                $batchGroup = $prefix . '-' . date('ymdHis');
                $seen = [];
                for ($i = 0; $i < $batchCount; $i++) {
                    do {
                        $c = PromotionService::generateUniqueCode($pdo, $prefix, 6);
                    } while (isset($seen[$c]));
                    $seen[$c] = true;
                    $codesToCreate[] = $c;
                }
            }

            $insVoucher = $pdo->prepare("
                INSERT INTO vouchers (
                    campaign_id, program_name, code, prefix, batch_code, description,
                    discount_type, discount_value, min_order_value,
                    max_discount_amount, max_discount_per_order,
                    usage_limit, used_count, per_user_limit, scope_type, customer_scope,
                    start_at, end_at, status, is_active, created_by
                ) VALUES (
                    :cid, :pname, :code, :pref, :bcode, :desc,
                    :dtype, :dval, :min_ord,
                    :max_d, :max_per,
                    :ulim, 0, :pulim, :scope, :cscope,
                    :sat, :eat, :st, :ia, :uid
                )
            ");

            $createdIds = [];
            foreach ($codesToCreate as $vCode) {
                $insVoucher->execute([
                    ':cid'     => $campaignId,
                    ':pname'   => $programName,
                    ':code'    => $vCode,
                    ':pref'    => $prefix,
                    ':bcode'   => $batchGroup,
                    ':desc'    => $description ?: null,
                    ':dtype'   => $discountType,
                    ':dval'    => $discountValue,
                    ':min_ord' => $minOrderValue,
                    ':max_d'   => $maxDiscountAmount,
                    ':max_per' => $maxDiscountPerOrder,
                    ':ulim'    => $usageLimit,
                    ':pulim'   => $perUserLimit,
                    ':scope'   => $scopeType,
                    ':cscope'  => $customerScope,
                    ':sat'     => $startAt,
                    ':eat'     => $endAt,
                    ':st'      => $finalStatus,
                    ':ia'      => $isActive,
                    ':uid'     => $adminId ?: null,
                ]);
                $vid = (int)$pdo->lastInsertId();
                $createdIds[] = $vid;
                saveVoucherScopes($pdo, $vid, $campaignId, $scopeType, $categoryIds, $productIds);
            }

            log_activity($adminId, 'create_promotion', 'promotions', $createdIds[0], "Tạo khuyến mãi \"{$programName}\" (" . implode(', ', array_slice($codesToCreate, 0, 5)) . (count($codesToCreate) > 5 ? '...' : '') . ")");

            $pdo->commit();

            json_response([
                'success' => true,
                'message' => count($codesToCreate) > 1
                    ? "Đã tạo thành công chương trình \"{$programName}\" với " . count($codesToCreate) . " mã riêng biệt!"
                    : "Đã tạo khuyến mãi \"{$programName}\" (Mã: {$codesToCreate[0]}) thành công!",
                'data' => [
                    'id'          => $createdIds[0],
                    'ids'         => $createdIds,
                    'codes'       => $codesToCreate,
                    'status'      => $finalStatus,
                    'campaign_id' => $campaignId
                ]
            ], 201);
        }

        // 3. Update Promotion (Protects financials if already used!)
        if ($action === 'update_promotion') {
            $id = (int)($_GET['id'] ?? $in['id'] ?? 0);
            if ($id <= 0) {
                json_response(['success' => false, 'message' => 'Thiếu ID chương trình khuyến mãi.'], 400);
            }

            $stmt = $pdo->prepare('SELECT * FROM vouchers WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $id]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$existing) {
                json_response(['success' => false, 'message' => 'Chương trình khuyến mãi không tồn tại.'], 404);
            }

            $uChk = $pdo->prepare('SELECT COUNT(*) FROM voucher_usages WHERE voucher_id = :vid');
            $uChk->execute([':vid' => $id]);
            $hasUsages = ((int)$existing['used_count'] > 0) || ((int)$uChk->fetchColumn() > 0);

            $programName = sanitize_string($in['program_name'] ?? $existing['program_name'] ?? '', 200);
            if ($programName === '') {
                json_response(['success' => false, 'message' => 'Tên chương trình khuyến mãi không được để trống.'], 400);
            }
            $description = array_key_exists('description', $in) ? trim((string)$in['description']) : $existing['description'];
            $startAt = array_key_exists('start_at', $in) ? str_replace('T', ' ', trim((string)$in['start_at'])) : $existing['start_at'];
            $endAt = array_key_exists('end_at', $in) ? str_replace('T', ' ', trim((string)$in['end_at'])) : $existing['end_at'];
            if (strlen($startAt) === 16) $startAt .= ':00';
            if (strlen($endAt) === 16) $endAt .= ':00';

            if (strtotime($endAt) <= strtotime($startAt)) {
                json_response(['success' => false, 'message' => 'Thời gian kết thúc phải lớn hơn thời gian bắt đầu.'], 400);
            }

            $usageLimit = array_key_exists('usage_limit', $in) ? max(0, (int)$in['usage_limit']) : (int)$existing['usage_limit'];
            if ($usageLimit > 0 && $usageLimit < (int)$existing['used_count']) {
                json_response(['success' => false, 'message' => "Tổng lượt sử dụng mới không được nhỏ hơn số lượt đã dùng ({$existing['used_count']})."], 400);
            }

            // Lock discount code, discount_type, discount_value, min_order if voucher already has real orders!
            $code = (string)$existing['code'];
            $discountType = (string)$existing['discount_type'];
            $discountValue = (float)$existing['discount_value'];
            $maxDiscountAmount = $existing['max_discount_amount'];
            $maxDiscountPerOrder = $existing['max_discount_per_order'];
            $minOrderValue = (float)$existing['min_order_value'];
            $perUserLimit = array_key_exists('per_user_limit', $in) ? max(0, (int)$in['per_user_limit']) : (int)$existing['per_user_limit'];
            $scopeType = (string)$existing['scope_type'];
            $customerScope = (string)$existing['customer_scope'];

            if (!$hasUsages) {
                if (isset($in['code'])) {
                    $newCode = strtoupper(preg_replace('/[^A-Z0-9_-]/', '', trim((string)$in['code'])));
                    if (strlen($newCode) < 3) {
                        json_response(['success' => false, 'message' => 'Mã khuyến mãi không hợp lệ.'], 400);
                    }
                    $chk = $pdo->prepare('SELECT COUNT(*) FROM vouchers WHERE UPPER(code) = :c AND id != :id');
                    $chk->execute([':c' => $newCode, ':id' => $id]);
                    if ((int)$chk->fetchColumn() > 0) {
                        json_response(['success' => false, 'message' => "Mã \"{$newCode}\" đã tồn tại."], 409);
                    }
                    $code = $newCode;
                }
                if (isset($in['discount_type']) && in_array($in['discount_type'], ['percentage', 'fixed_amount'], true)) {
                    $discountType = $in['discount_type'];
                }
                if (isset($in['discount_value'])) {
                    $discountValue = (float)$in['discount_value'];
                    if ($discountValue <= 0 || ($discountType === 'percentage' && $discountValue > 100)) {
                        json_response(['success' => false, 'message' => 'Giá trị giảm không hợp lệ.'], 400);
                    }
                }
                if (array_key_exists('max_discount_amount', $in)) {
                    $maxDiscountAmount = ($in['max_discount_amount'] !== '' && $in['max_discount_amount'] !== null) ? (float)$in['max_discount_amount'] : null;
                }
                if (array_key_exists('max_discount_per_order', $in)) {
                    $maxDiscountPerOrder = ($in['max_discount_per_order'] !== '' && $in['max_discount_per_order'] !== null) ? (float)$in['max_discount_per_order'] : null;
                }
                if (array_key_exists('min_order_value', $in)) {
                    $minOrderValue = max(0.0, (float)$in['min_order_value']);
                }
                if (isset($in['scope_type']) && in_array($in['scope_type'], ['all', 'category', 'product'], true)) {
                    $scopeType = $in['scope_type'];
                }
                if (isset($in['customer_scope']) && in_array($in['customer_scope'], ['all', 'new_customer', 'vip'], true)) {
                    $customerScope = $in['customer_scope'];
                }
            }

            $reqStatus = (string)($in['status'] ?? $existing['status']);
            $finalStatus = resolvePromotionStatus($reqStatus, $startAt, $endAt, $now);
            $isActive = ($finalStatus === 'active' || $finalStatus === 'scheduled') ? 1 : 0;

            $pdo->beginTransaction();

            $up = $pdo->prepare("
                UPDATE vouchers
                SET program_name = :pname, code = :code, description = :desc,
                    discount_type = :dtype, discount_value = :dval, min_order_value = :min_ord,
                    max_discount_amount = :max_d, max_discount_per_order = :max_per,
                    usage_limit = :ulim, per_user_limit = :pulim,
                    scope_type = :scope, customer_scope = :cscope,
                    start_at = :sat, end_at = :eat, status = :st, is_active = :ia, updated_at = NOW()
                WHERE id = :id
            ");
            $up->execute([
                ':pname'   => $programName,
                ':code'    => $code,
                ':desc'    => $description ?: null,
                ':dtype'   => $discountType,
                ':dval'    => $discountValue,
                ':min_ord' => $minOrderValue,
                ':max_d'   => $maxDiscountAmount,
                ':max_per' => $maxDiscountPerOrder,
                ':ulim'    => $usageLimit,
                ':pulim'   => $perUserLimit,
                ':scope'   => $scopeType,
                ':cscope'  => $customerScope,
                ':sat'     => $startAt,
                ':eat'     => $endAt,
                ':st'      => $finalStatus,
                ':ia'      => $isActive,
                ':id'      => $id,
            ]);

            if (!$hasUsages && (isset($in['category_ids']) || isset($in['product_ids']))) {
                saveVoucherScopes(
                    $pdo,
                    $id,
                    $existing['campaign_id'] ? (int)$existing['campaign_id'] : null,
                    $scopeType,
                    is_array($in['category_ids'] ?? null) ? $in['category_ids'] : [],
                    is_array($in['product_ids'] ?? null) ? $in['product_ids'] : []
                );
            }

            if (!empty($existing['campaign_id'])) {
                $campSt = $finalStatus === 'ended' ? 'expired' : $finalStatus;
                $pdo->prepare('UPDATE campaigns SET name = :n, start_at = :s, end_at = :e, status = :st, updated_at = NOW() WHERE id = :cid')
                    ->execute([':n' => $programName, ':s' => $startAt, ':e' => $endAt, ':st' => $campSt, ':cid' => (int)$existing['campaign_id']]);
            }

            log_activity($adminId, 'update_promotion', 'promotions', $id, "Cập nhật khuyến mãi #{$id} ({$code})");
            $pdo->commit();

            json_response([
                'success' => true,
                'message' => $hasUsages
                    ? 'Đã cập nhật thông tin cho phép (giá trị giảm và mã gốc được khóa để bảo toàn lịch sử đơn hàng đã hoàn tất).'
                    : 'Cập nhật chương trình khuyến mãi thành công!'
            ]);
        }

        // 4. Pause / Resume / End Now / Toggle Voucher
        if (in_array($action, ['pause', 'resume', 'end_now', 'toggle_voucher'], true)) {
            $id = (int)($in['id'] ?? $in['voucher_id'] ?? 0);
            if ($id <= 0) {
                json_response(['success' => false, 'message' => 'Thiếu ID chương trình khuyến mãi.'], 400);
            }

            $st = $pdo->prepare('SELECT * FROM vouchers WHERE id = :id LIMIT 1');
            $st->execute([':id' => $id]);
            $voucher = $st->fetch(PDO::FETCH_ASSOC);
            if (!$voucher) {
                json_response(['success' => false, 'message' => 'Chương trình khuyến mãi không tồn tại.'], 404);
            }

            $targetAction = $action;
            if ($action === 'toggle_voucher') {
                $wantActive = (int)($in['is_active'] ?? ($voucher['status'] === 'active' ? 0 : 1));
                $targetAction = $wantActive === 1 ? 'resume' : 'pause';
            }

            if ($targetAction === 'pause') {
                $pdo->prepare("UPDATE vouchers SET status = 'paused', is_active = 0, updated_at = NOW() WHERE id = :id")
                    ->execute([':id' => $id]);
                if (!empty($voucher['campaign_id'])) {
                    $pdo->prepare("UPDATE campaigns SET status = 'paused', updated_at = NOW() WHERE id = :cid")
                        ->execute([':cid' => (int)$voucher['campaign_id']]);
                }
                log_activity($adminId, 'pause_promotion', 'promotions', $id, "Tạm dừng mã {$voucher['code']}");
                json_response(['success' => true, 'message' => "Đã tạm dừng chương trình \"{$voucher['code']}\"."]);
            }

            if ($targetAction === 'resume') {
                if ($voucher['end_at'] < $now || $voucher['status'] === 'ended') {
                    json_response(['success' => false, 'message' => 'Không thể kích hoạt lại chương trình đã hết hạn. Vui lòng gia hạn thời gian kết thúc trước.'], 400);
                }
                $newStatus = ($voucher['start_at'] > $now) ? 'scheduled' : 'active';
                $pdo->prepare("UPDATE vouchers SET status = :st, is_active = 1, updated_at = NOW() WHERE id = :id")
                    ->execute([':st' => $newStatus, ':id' => $id]);
                if (!empty($voucher['campaign_id'])) {
                    $pdo->prepare("UPDATE campaigns SET status = :st, approval_status = 'approved', updated_at = NOW() WHERE id = :cid")
                        ->execute([':st' => $newStatus, ':cid' => (int)$voucher['campaign_id']]);
                }
                log_activity($adminId, 'resume_promotion', 'promotions', $id, "Kích hoạt lại mã {$voucher['code']} ({$newStatus})");
                json_response(['success' => true, 'message' => $newStatus === 'scheduled'
                    ? "Đã chuyển mã \"{$voucher['code']}\" về trạng thái Đã lên lịch."
                    : "Đã kích hoạt lại mã \"{$voucher['code']}\" (Đang diễn ra)."
                ]);
            }

            if ($targetAction === 'end_now') {
                $pdo->prepare("UPDATE vouchers SET status = 'ended', is_active = 0, end_at = LEAST(end_at, :now), updated_at = NOW() WHERE id = :id")
                    ->execute([':now' => $now, ':id' => $id]);
                log_activity($adminId, 'end_promotion', 'promotions', $id, "Kết thúc sớm mã {$voucher['code']}");
                json_response(['success' => true, 'message' => "Đã kết thúc chương trình \"{$voucher['code']}\"."]);
            }
        }

        // Keep legacy create_campaign / approve / pause_campaign actions functional
        if ($action === 'create_campaign') {
            $required = ['name', 'slug', 'campaign_type', 'discount_type', 'discount_value', 'start_at', 'end_at'];
            foreach ($required as $f) {
                if (!isset($in[$f]) || trim((string)$in[$f]) === '') {
                    json_response(['success' => false, 'message' => "Trường '{$f}' là bắt buộc."], 400);
                }
            }
            $chk = $pdo->prepare('SELECT COUNT(*) FROM campaigns WHERE slug = :s');
            $chk->execute([':s' => trim((string)$in['slug'])]);
            if ((int)$chk->fetchColumn() > 0) {
                json_response(['success' => false, 'message' => 'Slug chiến dịch đã tồn tại.'], 409);
            }
            $st = $pdo->prepare("
                INSERT INTO campaigns (name, slug, description, campaign_type, discount_type, discount_value, min_order_value, max_discount_amount, budget_limit, budget_used, min_margin, start_at, end_at, allow_stacking, status, approval_status, created_by)
                VALUES (:n,:s,:d,:ct,:dt,:dv,:mo,:md,:bl,0,:mm,:sa,:ea,:as,:st,'pending',:cb)
            ");
            $st->execute([
                ':n' => trim((string)$in['name']), ':s' => trim((string)$in['slug']), ':d' => $in['description'] ?? null,
                ':ct' => trim((string)$in['campaign_type']), ':dt' => trim((string)$in['discount_type']), ':dv' => (float)$in['discount_value'],
                ':mo' => (float)($in['min_order_value'] ?? 0), ':md' => isset($in['max_discount_amount']) ? (float)$in['max_discount_amount'] : null,
                ':bl' => (float)($in['budget_limit'] ?? 0), ':mm' => (float)($in['min_margin'] ?? 20),
                ':sa' => trim((string)$in['start_at']), ':ea' => trim((string)$in['end_at']),
                ':as' => (int)($in['allow_stacking'] ?? 0), ':st' => trim((string)($in['status'] ?? 'draft')),
                ':cb' => $adminId ?: null
            ]);
            json_response(['success' => true, 'message' => 'Tạo chiến dịch thành công!', 'data' => ['id' => (int)$pdo->lastInsertId()]], 201);
        }

        if ($action === 'approve' || $action === 'pause_campaign') {
            $cid = (int)($in['campaign_id'] ?? 0);
            if ($cid <= 0) {
                json_response(['success' => false, 'message' => 'Thiếu campaign_id.'], 400);
            }
            if ($action === 'pause_campaign') {
                $pdo->prepare("UPDATE campaigns SET status='paused', updated_at=NOW() WHERE id=:id")->execute([':id' => $cid]);
                json_response(['success' => true, 'message' => 'Đã tạm dừng chiến dịch.']);
            }
            if (($_SESSION['role_name'] ?? '') !== 'admin') {
                json_response(['success' => false, 'message' => 'Chỉ admin mới có quyền duyệt.'], 403);
            }
            $appAct = trim((string)($in['approve_action'] ?? $in['action_type'] ?? $in['approval'] ?? ''));
            if (!in_array($appAct, ['approved', 'rejected'], true)) {
                json_response(['success' => false, 'message' => 'Hành động duyệt không hợp lệ.'], 400);
            }
            $pdo->prepare('UPDATE campaigns SET approval_status=:ap, status=:st, updated_at=NOW() WHERE id=:id')
                ->execute([':ap' => $appAct, ':st' => $appAct === 'approved' ? 'active' : 'draft', ':id' => $cid]);
            json_response(['success' => true, 'message' => 'Cập nhật trạng thái duyệt thành công.']);
        }

        json_response(['success' => false, 'message' => "Hành động '{$action}' không được hỗ trợ."], 400);
    }

    // =========================================================================
    // DELETE: Safe delete only when voucher has zero usages
    // =========================================================================
    if ($method === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            json_response(['success' => false, 'message' => 'Thiếu ID khuyến mãi.'], 400);
        }

        $pdo->beginTransaction();
        $st = $pdo->prepare('SELECT id, code, used_count FROM vouchers WHERE id = :id LIMIT 1 FOR UPDATE');
        $st->execute([':id' => $id]);
        $voucher = $st->fetch(PDO::FETCH_ASSOC);
        if (!$voucher) {
            $pdo->rollBack();
            json_response(['success' => false, 'message' => 'Mã khuyến mãi không tồn tại.'], 404);
        }

        $uChk = $pdo->prepare('SELECT COUNT(*) FROM voucher_usages WHERE voucher_id = :vid');
        $uChk->execute([':vid' => $id]);
        if ((int)$voucher['used_count'] > 0 || (int)$uChk->fetchColumn() > 0) {
            $pdo->rollBack();
            json_response([
                'success' => false,
                'message' => "Không thể xóa mã \"{$voucher['code']}\" vì đã có lịch sử sử dụng trên đơn hàng. Vui lòng Tạm dừng hoặc Kết thúc chương trình để bảo toàn dữ liệu đối soát."
            ], 409);
        }

        $pdo->prepare('DELETE FROM vouchers WHERE id = :id')->execute([':id' => $id]);
        log_activity($adminId, 'delete_promotion', 'promotions', $id, "Xóa mã khuyến mãi {$voucher['code']}");
        $pdo->commit();

        json_response(['success' => true, 'message' => "Đã xóa mã khuyến mãi \"{$voucher['code']}\"."]);
    }

    json_response(['success' => false, 'message' => 'Phương thức HTTP không được hỗ trợ.'], 405);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[Admin Promotions Error] ' . $e->getMessage());
    json_response(['success' => false, 'message' => 'Lỗi hệ thống khi xử lý khuyến mãi.'], 500);
}
