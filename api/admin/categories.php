<?php
declare(strict_types=1);

/**
 * ADMIN CATEGORIES MANAGEMENT API
 * GET    /api/admin/categories.php         - List with search, filter, pagination, stats
 * GET    /api/admin/categories.php?id=X    - Category detail + linked products & subcategories
 * POST   /api/admin/categories.php         - Create category with auto slug/code & validation
 * PUT    /api/admin/categories.php?id=X    - Update category
 * PATCH  /api/admin/categories.php?id=X    - Toggle or set visibility status (active / hidden)
 * DELETE /api/admin/categories.php?id=X    - Safe delete (blocks if linked products exist unless reassigned)
 */

require_once __DIR__ . '/../../core/bootstrap.php';

require_admin_auth();

/**
 * Generate a unique category code (e.g. DM-HOA-CUOI or DM010).
 */
function generateUniqueCategoryCode(PDO $pdo, string $name, ?int $excludeId = null): string
{
    $slug = bloomora_slugify($name);
    $parts = array_values(array_filter(explode('-', $slug)));
    $acronym = '';
    foreach ($parts as $part) {
        $acronym .= strtoupper(substr($part, 0, 1));
    }
    $acronym = preg_replace('/[^A-Z0-9]/', '', $acronym);
    if ($acronym === '') {
        $acronym = 'FLW';
    }
    $acronym = substr($acronym, 0, 4);

    for ($i = 1; $i <= 999; $i++) {
        $candidate = 'DM-' . $acronym . '-' . str_pad((string)$i, 2, '0', STR_PAD_LEFT);
        $sql = 'SELECT COUNT(*) FROM categories WHERE UPPER(code) = :c';
        $params = [':c' => $candidate];
        if ($excludeId !== null) {
            $sql .= ' AND id != :id';
            $params[':id'] = $excludeId;
        }
        $st = $pdo->prepare($sql);
        $st->execute($params);
        if ((int)$st->fetchColumn() === 0) {
            return $candidate;
        }
    }

    return 'DM-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
}

/**
 * Ensure slug is unique, appending -2, -3... if auto-generated.
 */
function ensureUniqueCategorySlug(PDO $pdo, string $baseSlug, ?int $excludeId = null): string
{
    $slug = $baseSlug !== '' ? $baseSlug : 'danh-muc';
    $orig = $slug;
    $i = 2;
    while (true) {
        $sql = 'SELECT COUNT(*) FROM categories WHERE slug = :s';
        $params = [':s' => $slug];
        if ($excludeId !== null) {
            $sql .= ' AND id != :id';
            $params[':id'] = $excludeId;
        }
        $st = $pdo->prepare($sql);
        $st->execute($params);
        if ((int)$st->fetchColumn() === 0) {
            return $slug;
        }
        $slug = $orig . '-' . $i++;
    }
}

/**
 * Check if setting $newParentId for $categoryId creates a cycle.
 */
function wouldCreateCategoryCycle(PDO $pdo, int $categoryId, int $newParentId): bool
{
    if ($categoryId === $newParentId) {
        return true;
    }
    $curr = $newParentId;
    $visited = [$categoryId => true];
    $stmt = $pdo->prepare('SELECT parent_id FROM categories WHERE id = :id LIMIT 1');
    for ($depth = 0; $depth < 20 && $curr > 0; $depth++) {
        if (isset($visited[$curr])) {
            return true;
        }
        $visited[$curr] = true;
        $stmt->execute([':id' => $curr]);
        $pid = $stmt->fetchColumn();
        if ($pid === false || $pid === null) {
            break;
        }
        $curr = (int)$pid;
    }
    return false;
}

try {
    $pdo = getDBConnection();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $adminId = (int)($_SESSION['user_id'] ?? 0);

    // =========================================================================
    // GET: Detail (?id=X), Availability check (?check_name / ?check_code), or List
    // =========================================================================
    if ($method === 'GET') {
        // Detail view for a specific category
        $detailId = (int)($_GET['id'] ?? 0);
        if ($detailId > 0) {
            $stmt = $pdo->prepare("
                SELECT c.*, pc.name AS parent_name,
                       COUNT(p.id) AS product_count,
                       COALESCE(SUM(CASE WHEN p.is_active = 1 THEN 1 ELSE 0 END), 0) AS active_product_count
                FROM categories c
                LEFT JOIN categories pc ON pc.id = c.parent_id
                LEFT JOIN products p ON p.category_id = c.id
                WHERE c.id = :id
                GROUP BY c.id
                LIMIT 1
            ");
            $stmt->execute([':id' => $detailId]);
            $cat = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$cat) {
                json_response(['success' => false, 'message' => 'Danh mục không tồn tại.'], 404);
            }

            $cat['id'] = (int)$cat['id'];
            $cat['parent_id'] = $cat['parent_id'] !== null ? (int)$cat['parent_id'] : null;
            $cat['display_order'] = (int)$cat['display_order'];
            $cat['is_active'] = (int)$cat['is_active'];
            $cat['product_count'] = (int)$cat['product_count'];
            $cat['active_product_count'] = (int)$cat['active_product_count'];

            // Linked products list
            $pStmt = $pdo->prepare("
                SELECT id, sku, name, slug, price, sale_price, stock_quantity,
                       thumbnail, is_active, is_featured, rating, created_at
                FROM products
                WHERE category_id = :cid
                ORDER BY is_active DESC, id DESC
            ");
            $pStmt->execute([':cid' => $detailId]);
            $products = $pStmt->fetchAll(PDO::FETCH_ASSOC);

            // Child categories if any
            $chStmt = $pdo->prepare("
                SELECT id, name, code, slug, status, is_active, display_order
                FROM categories
                WHERE parent_id = :pid
                ORDER BY display_order ASC, id ASC
            ");
            $chStmt->execute([':pid' => $detailId]);
            $children = $chStmt->fetchAll(PDO::FETCH_ASSOC);

            $cat['products'] = $products;
            $cat['children'] = $children;

            json_response(['success' => true, 'data' => $cat]);
        }

        // List with filters & pagination
        $search = trim((string)($_GET['search'] ?? ''));
        $statusFilter = trim((string)($_GET['status'] ?? 'all'));
        $sort = trim((string)($_GET['sort'] ?? 'order_asc'));
        $fetchAll = ($_GET['all'] ?? '0') === '1';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = $fetchAll ? 500 : max(1, min(100, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $where = [];
        $params = [];

        if ($search !== '') {
            $where[] = '(c.name LIKE :s1 OR c.code LIKE :s2 OR c.slug LIKE :s3 OR c.description LIKE :s4)';
            $kw = '%' . $search . '%';
            $params[':s1'] = $kw;
            $params[':s2'] = $kw;
            $params[':s3'] = $kw;
            $params[':s4'] = $kw;
        }

        if ($statusFilter === 'active') {
            $where[] = "(c.status = 'active' AND c.is_active = 1)";
        } elseif ($statusFilter === 'hidden') {
            $where[] = "(c.status = 'hidden' OR c.is_active = 0)";
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM categories c $whereSql");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $orderBy = 'c.display_order ASC, c.id ASC';
        if ($sort === 'newest') {
            $orderBy = 'c.id DESC';
        } elseif ($sort === 'name_asc') {
            $orderBy = 'c.name ASC';
        } elseif ($sort === 'products_desc') {
            $orderBy = 'product_count DESC, c.display_order ASC';
        }

        $sql = "
            SELECT c.id, c.name, c.code, c.slug, c.parent_id, pc.name AS parent_name,
                   c.description, c.icon, c.image, c.badge, c.display_order,
                   c.is_active, c.status, c.created_at, c.updated_at,
                   COUNT(p.id) AS product_count,
                   COALESCE(SUM(CASE WHEN p.is_active = 1 THEN 1 ELSE 0 END), 0) AS active_product_count
            FROM categories c
            LEFT JOIN categories pc ON pc.id = c.parent_id
            LEFT JOIN products p ON p.category_id = c.id
            $whereSql
            GROUP BY c.id
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
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$r) {
            $r['id'] = (int)$r['id'];
            $r['parent_id'] = $r['parent_id'] !== null ? (int)$r['parent_id'] : null;
            $r['display_order'] = (int)$r['display_order'];
            $r['is_active'] = (int)$r['is_active'];
            $r['product_count'] = (int)$r['product_count'];
            $r['active_product_count'] = (int)$r['active_product_count'];
        }
        unset($r);

        // Summary counts across all categories
        $stats = $pdo->query("
            SELECT COUNT(*) AS total,
                   COALESCE(SUM(CASE WHEN status='active' AND is_active=1 THEN 1 ELSE 0 END), 0) AS active_count,
                   COALESCE(SUM(CASE WHEN status='hidden' OR is_active=0 THEN 1 ELSE 0 END), 0) AS hidden_count
            FROM categories
        ")->fetch(PDO::FETCH_ASSOC);

        json_response([
            'success'    => true,
            'data'       => $rows,
            'stats'      => [
                'total'  => (int)($stats['total'] ?? 0),
                'active' => (int)($stats['active_count'] ?? 0),
                'hidden' => (int)($stats['hidden_count'] ?? 0),
            ],
            'pagination' => [
                'total'       => $total,
                'page'        => $page,
                'limit'       => $limit,
                'total_pages' => max(1, (int)ceil($total / $limit)),
            ]
        ]);
    }

    // =========================================================================
    // POST: Create category (or handle _method=PATCH fallback)
    // =========================================================================
    if ($method === 'POST') {
        $in = get_json_input();

        // Support action-based toggle if client sends POST
        if (($in['action'] ?? '') === 'toggle_status') {
            $id = (int)($in['id'] ?? $_GET['id'] ?? 0);
            if ($id <= 0) {
                json_response(['success' => false, 'message' => 'Thiếu ID danh mục.'], 400);
            }
            $curr = $pdo->prepare('SELECT id, name, status, is_active FROM categories WHERE id = :id LIMIT 1');
            $curr->execute([':id' => $id]);
            $cat = $curr->fetch(PDO::FETCH_ASSOC);
            if (!$cat) {
                json_response(['success' => false, 'message' => 'Danh mục không tồn tại.'], 404);
            }
            $targetStatus = isset($in['status']) && in_array($in['status'], ['active', 'hidden'], true)
                ? $in['status']
                : (($cat['status'] === 'active' && (int)$cat['is_active'] === 1) ? 'hidden' : 'active');
            $targetActive = $targetStatus === 'active' ? 1 : 0;

            $pdo->prepare('UPDATE categories SET status = :st, is_active = :ia, updated_at = NOW() WHERE id = :id')
                ->execute([':st' => $targetStatus, ':ia' => $targetActive, ':id' => $id]);

            $verb = $targetStatus === 'active' ? 'Hiện' : 'Ẩn';
            log_activity($adminId, $targetStatus === 'active' ? 'show_category' : 'hide_category', 'categories', $id, "{$verb} danh mục: {$cat['name']}");

            json_response([
                'success' => true,
                'message' => $targetStatus === 'active'
                    ? "Đã hiển thị lại danh mục \"{$cat['name']}\" trên website."
                    : "Đã ẩn danh mục \"{$cat['name']}\" khỏi trang khách hàng (các sản phẩm liên kết vẫn được giữ nguyên).",
                'data' => ['id' => $id, 'status' => $targetStatus, 'is_active' => $targetActive]
            ]);
        }

        $name = sanitize_string($in['name'] ?? '', 100);
        if ($name === '') {
            json_response(['success' => false, 'message' => 'Tên danh mục là bắt buộc.'], 400);
        }

        // Duplicate name check (case-insensitive)
        $chkName = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE LOWER(TRIM(name)) = LOWER(:n)');
        $chkName->execute([':n' => $name]);
        if ((int)$chkName->fetchColumn() > 0) {
            json_response(['success' => false, 'message' => "Tên danh mục \"{$name}\" đã tồn tại trong hệ thống."], 409);
        }

        // Slug: use provided or auto-generate from name
        $rawSlug = trim((string)($in['slug'] ?? ''));
        if ($rawSlug !== '') {
            $slug = bloomora_slugify($rawSlug);
            if ($slug === '') {
                json_response(['success' => false, 'message' => 'Slug danh mục không hợp lệ.'], 400);
            }
            $chkSlug = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE slug = :s');
            $chkSlug->execute([':s' => $slug]);
            if ((int)$chkSlug->fetchColumn() > 0) {
                json_response(['success' => false, 'message' => "Slug \"{$slug}\" đã được sử dụng cho danh mục khác."], 409);
            }
        } else {
            $slug = ensureUniqueCategorySlug($pdo, bloomora_slugify($name));
        }

        // Code: manual or auto-generated
        $rawCode = strtoupper(trim((string)($in['code'] ?? '')));
        if ($rawCode !== '') {
            $code = preg_replace('/[^A-Z0-9_-]/', '', $rawCode);
            if ($code === '' || strlen($code) > 50) {
                json_response(['success' => false, 'message' => 'Mã danh mục chỉ được chứa chữ cái không dấu, số, gạch ngang hoặc gạch dưới (tối đa 50 ký tự).'], 400);
            }
            $chkCode = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE UPPER(code) = :c');
            $chkCode->execute([':c' => $code]);
            if ((int)$chkCode->fetchColumn() > 0) {
                json_response(['success' => false, 'message' => "Mã danh mục \"{$code}\" đã tồn tại."], 409);
            }
        } else {
            $code = generateUniqueCategoryCode($pdo, $name);
        }

        // Parent category validation
        $parentId = !empty($in['parent_id']) && (int)$in['parent_id'] > 0 ? (int)$in['parent_id'] : null;
        if ($parentId !== null) {
            $chkParent = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE id = :pid');
            $chkParent->execute([':pid' => $parentId]);
            if ((int)$chkParent->fetchColumn() === 0) {
                json_response(['success' => false, 'message' => 'Danh mục cha được chọn không tồn tại.'], 400);
            }
        }

        // Status & is_active sync
        $status = 'active';
        if (isset($in['status']) && in_array($in['status'], ['active', 'hidden'], true)) {
            $status = $in['status'];
        } elseif (array_key_exists('is_active', $in)) {
            $status = ((int)$in['is_active'] === 1) ? 'active' : 'hidden';
        }
        $isActive = $status === 'active' ? 1 : 0;

        $description = isset($in['description']) ? trim((string)$in['description']) : null;
        $image = isset($in['image']) && trim((string)$in['image']) !== '' ? sanitize_string($in['image'], 500) : null;
        $icon = sanitize_string($in['icon'] ?? 'ph-flower-lotus', 50) ?: 'ph-flower-lotus';
        $badge = isset($in['badge']) && trim((string)$in['badge']) !== '' ? sanitize_string($in['badge'], 30) : null;
        $displayOrder = max(0, (int)($in['display_order'] ?? 0));

        $stmt = $pdo->prepare("
            INSERT INTO categories (name, code, description, slug, parent_id, icon, image, badge, display_order, is_active, status)
            VALUES (:n, :code, :desc, :slug, :pid, :icon, :img, :badge, :ord, :ia, :st)
        ");
        $stmt->execute([
            ':n'     => $name,
            ':code'  => $code,
            ':desc'  => $description !== '' ? $description : null,
            ':slug'  => $slug,
            ':pid'   => $parentId,
            ':icon'  => $icon,
            ':img'   => $image,
            ':badge' => $badge,
            ':ord'   => $displayOrder,
            ':ia'    => $isActive,
            ':st'    => $status,
        ]);
        $newId = (int)$pdo->lastInsertId();

        log_activity($adminId, 'create_category', 'categories', $newId, "Tạo danh mục mới: {$name} (Mã: {$code}, Slug: {$slug})");

        json_response([
            'success' => true,
            'message' => 'Tạo danh mục thành công!',
            'data'    => [
                'id'     => $newId,
                'name'   => $name,
                'code'   => $code,
                'slug'   => $slug,
                'status' => $status,
            ]
        ], 201);
    }

    // =========================================================================
    // PUT: Update category
    // =========================================================================
    if ($method === 'PUT') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            json_response(['success' => false, 'message' => 'Thiếu ID danh mục.'], 400);
        }

        $existingStmt = $pdo->prepare('SELECT * FROM categories WHERE id = :id LIMIT 1');
        $existingStmt->execute([':id' => $id]);
        $existing = $existingStmt->fetch(PDO::FETCH_ASSOC);
        if (!$existing) {
            json_response(['success' => false, 'message' => 'Danh mục không tồn tại.'], 404);
        }

        $in = get_json_input();
        if (empty($in)) {
            json_response(['success' => false, 'message' => 'Không có dữ liệu cần cập nhật.'], 400);
        }

        $sets = [];
        $params = [':id' => $id];

        if (array_key_exists('name', $in)) {
            $name = sanitize_string($in['name'], 100);
            if ($name === '') {
                json_response(['success' => false, 'message' => 'Tên danh mục không được để trống.'], 400);
            }
            $chkN = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE LOWER(TRIM(name)) = LOWER(:n) AND id != :id');
            $chkN->execute([':n' => $name, ':id' => $id]);
            if ((int)$chkN->fetchColumn() > 0) {
                json_response(['success' => false, 'message' => "Tên danh mục \"{$name}\" đã tồn tại."], 409);
            }
            $sets[] = '`name` = :name';
            $params[':name'] = $name;
        }

        if (array_key_exists('code', $in)) {
            $rawCode = strtoupper(trim((string)$in['code']));
            if ($rawCode === '') {
                $code = generateUniqueCategoryCode($pdo, (string)($in['name'] ?? $existing['name']), $id);
            } else {
                $code = preg_replace('/[^A-Z0-9_-]/', '', $rawCode);
                if ($code === '' || strlen($code) > 50) {
                    json_response(['success' => false, 'message' => 'Mã danh mục không hợp lệ.'], 400);
                }
                $chkC = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE UPPER(code) = :c AND id != :id');
                $chkC->execute([':c' => $code, ':id' => $id]);
                if ((int)$chkC->fetchColumn() > 0) {
                    json_response(['success' => false, 'message' => "Mã danh mục \"{$code}\" đã tồn tại."], 409);
                }
            }
            $sets[] = '`code` = :code';
            $params[':code'] = $code;
        }

        if (array_key_exists('slug', $in)) {
            $rawSlug = trim((string)$in['slug']);
            $slug = $rawSlug !== ''
                ? bloomora_slugify($rawSlug)
                : ensureUniqueCategorySlug($pdo, bloomora_slugify((string)($in['name'] ?? $existing['name'])), $id);
            if ($slug === '') {
                json_response(['success' => false, 'message' => 'Slug danh mục không hợp lệ.'], 400);
            }
            $chkS = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE slug = :s AND id != :id');
            $chkS->execute([':s' => $slug, ':id' => $id]);
            if ((int)$chkS->fetchColumn() > 0) {
                json_response(['success' => false, 'message' => "Slug \"{$slug}\" đã được dùng cho danh mục khác."], 409);
            }
            $sets[] = '`slug` = :slug';
            $params[':slug'] = $slug;
        }

        if (array_key_exists('parent_id', $in)) {
            $pid = !empty($in['parent_id']) && (int)$in['parent_id'] > 0 ? (int)$in['parent_id'] : null;
            if ($pid !== null) {
                $chkP = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE id = :pid');
                $chkP->execute([':pid' => $pid]);
                if ((int)$chkP->fetchColumn() === 0) {
                    json_response(['success' => false, 'message' => 'Danh mục cha không tồn tại.'], 400);
                }
                if (wouldCreateCategoryCycle($pdo, $id, $pid)) {
                    json_response(['success' => false, 'message' => 'Không thể chọn chính danh mục này hoặc danh mục con làm danh mục cha.'], 400);
                }
            }
            $sets[] = '`parent_id` = :parent_id';
            $params[':parent_id'] = $pid;
        }

        if (array_key_exists('description', $in)) {
            $d = trim((string)$in['description']);
            $sets[] = '`description` = :description';
            $params[':description'] = $d !== '' ? $d : null;
        }

        if (array_key_exists('image', $in)) {
            $img = trim((string)$in['image']);
            $sets[] = '`image` = :image';
            $params[':image'] = $img !== '' ? sanitize_string($img, 500) : null;
        }

        if (array_key_exists('icon', $in)) {
            $sets[] = '`icon` = :icon';
            $params[':icon'] = sanitize_string($in['icon'], 50) ?: 'ph-flower-lotus';
        }

        if (array_key_exists('badge', $in)) {
            $b = trim((string)$in['badge']);
            $sets[] = '`badge` = :badge';
            $params[':badge'] = $b !== '' ? sanitize_string($b, 30) : null;
        }

        if (array_key_exists('display_order', $in)) {
            $sets[] = '`display_order` = :display_order';
            $params[':display_order'] = max(0, (int)$in['display_order']);
        }

        if (array_key_exists('status', $in) || array_key_exists('is_active', $in)) {
            $stVal = $existing['status'];
            if (isset($in['status']) && in_array($in['status'], ['active', 'hidden'], true)) {
                $stVal = $in['status'];
            } elseif (array_key_exists('is_active', $in)) {
                $stVal = ((int)$in['is_active'] === 1) ? 'active' : 'hidden';
            }
            $sets[] = '`status` = :status';
            $sets[] = '`is_active` = :is_active';
            $params[':status'] = $stVal;
            $params[':is_active'] = $stVal === 'active' ? 1 : 0;
        }

        if (empty($sets)) {
            json_response(['success' => false, 'message' => 'Không có trường hợp lệ để cập nhật.'], 400);
        }

        $pdo->prepare('UPDATE categories SET ' . implode(', ', $sets) . ', updated_at = NOW() WHERE id = :id')
            ->execute($params);

        log_activity($adminId, 'update_category', 'categories', $id, "Cập nhật danh mục: " . ($params[':name'] ?? $existing['name']));

        json_response(['success' => true, 'message' => 'Cập nhật danh mục thành công!']);
    }

    // =========================================================================
    // PATCH: Toggle hide / show without affecting linked products
    // =========================================================================
    if ($method === 'PATCH') {
        $id = (int)($_GET['id'] ?? 0);
        $in = get_json_input();
        if ($id <= 0) {
            $id = (int)($in['id'] ?? 0);
        }
        if ($id <= 0) {
            json_response(['success' => false, 'message' => 'Thiếu ID danh mục.'], 400);
        }

        $curr = $pdo->prepare('SELECT id, name, status, is_active FROM categories WHERE id = :id LIMIT 1');
        $curr->execute([':id' => $id]);
        $cat = $curr->fetch(PDO::FETCH_ASSOC);
        if (!$cat) {
            json_response(['success' => false, 'message' => 'Danh mục không tồn tại.'], 404);
        }

        $targetStatus = isset($in['status']) && in_array($in['status'], ['active', 'hidden'], true)
            ? $in['status']
            : (($cat['status'] === 'active' && (int)$cat['is_active'] === 1) ? 'hidden' : 'active');
        $targetActive = $targetStatus === 'active' ? 1 : 0;

        $pdo->prepare('UPDATE categories SET status = :st, is_active = :ia, updated_at = NOW() WHERE id = :id')
            ->execute([':st' => $targetStatus, ':ia' => $targetActive, ':id' => $id]);

        $verb = $targetStatus === 'active' ? 'Hiện' : 'Ẩn';
        log_activity($adminId, $targetStatus === 'active' ? 'show_category' : 'hide_category', 'categories', $id, "{$verb} danh mục: {$cat['name']}");

        json_response([
            'success' => true,
            'message' => $targetStatus === 'active'
                ? "Đã bật hiển thị danh mục \"{$cat['name']}\" trên website."
                : "Đã ẩn danh mục \"{$cat['name']}\" khỏi trang khách hàng (sản phẩm liên kết không bị mất).",
            'data' => ['id' => $id, 'status' => $targetStatus, 'is_active' => $targetActive]
        ]);
    }

    // =========================================================================
    // DELETE: Safe delete — blocks if linked products exist unless reassigned
    // =========================================================================
    if ($method === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        $in = get_json_input();
        if ($id <= 0) {
            $id = (int)($in['id'] ?? 0);
        }
        if ($id <= 0) {
            json_response(['success' => false, 'message' => 'Thiếu ID danh mục.'], 400);
        }

        $reassignToId = (int)($_GET['reassign_to_id'] ?? $in['reassign_to_id'] ?? 0);

        $pdo->beginTransaction();

        $catStmt = $pdo->prepare('SELECT id, name, code FROM categories WHERE id = :id LIMIT 1 FOR UPDATE');
        $catStmt->execute([':id' => $id]);
        $cat = $catStmt->fetch(PDO::FETCH_ASSOC);
        if (!$cat) {
            $pdo->rollBack();
            json_response(['success' => false, 'message' => 'Danh mục không tồn tại.'], 404);
        }

        $prodCountStmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE category_id = :id');
        $prodCountStmt->execute([':id' => $id]);
        $linkedProducts = (int)$prodCountStmt->fetchColumn();

        $childCountStmt = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE parent_id = :id');
        $childCountStmt->execute([':id' => $id]);
        $linkedChildren = (int)$childCountStmt->fetchColumn();

        if ($linkedProducts > 0) {
            if ($reassignToId <= 0) {
                $pdo->rollBack();
                json_response([
                    'success'         => false,
                    'code'            => 'HAS_LINKED_PRODUCTS',
                    'linked_products' => $linkedProducts,
                    'linked_children' => $linkedChildren,
                    'message'         => "Không thể xóa trực tiếp danh mục \"{$cat['name']}\" vì đang có {$linkedProducts} sản phẩm liên kết. Vui lòng chuyển sản phẩm sang danh mục khác trước khi xóa, hoặc chọn Ẩn danh mục."
                ], 409);
            }

            if ($reassignToId === $id) {
                $pdo->rollBack();
                json_response(['success' => false, 'message' => 'Danh mục đích để chuyển sản phẩm phải khác danh mục đang xóa.'], 400);
            }

            $targetStmt = $pdo->prepare('SELECT id, name FROM categories WHERE id = :tid LIMIT 1');
            $targetStmt->execute([':tid' => $reassignToId]);
            $targetCat = $targetStmt->fetch(PDO::FETCH_ASSOC);
            if (!$targetCat) {
                $pdo->rollBack();
                json_response(['success' => false, 'message' => 'Danh mục đích được chọn không tồn tại.'], 404);
            }

            // Reassign products safely without losing any product data
            $pdo->prepare('UPDATE products SET category_id = :tid WHERE category_id = :cid')
                ->execute([':tid' => $reassignToId, ':cid' => $id]);
        }

        // Detach child categories safely (promote to root)
        if ($linkedChildren > 0) {
            $pdo->prepare('UPDATE categories SET parent_id = NULL WHERE parent_id = :cid')
                ->execute([':cid' => $id]);
        }

        $pdo->prepare('DELETE FROM categories WHERE id = :id')->execute([':id' => $id]);

        $detailMsg = "Xóa danh mục: {$cat['name']} (Mã: {$cat['code']})";
        if ($linkedProducts > 0 && isset($targetCat)) {
            $detailMsg .= " — Đã chuyển {$linkedProducts} sản phẩm sang \"{$targetCat['name']}\"";
        }
        log_activity($adminId, 'delete_category', 'categories', $id, $detailMsg);

        $pdo->commit();

        json_response([
            'success' => true,
            'message' => $linkedProducts > 0 && isset($targetCat)
                ? "Đã chuyển {$linkedProducts} sản phẩm sang \"{$targetCat['name']}\" và xóa danh mục \"{$cat['name']}\" an toàn."
                : "Đã xóa danh mục \"{$cat['name']}\" thành công."
        ]);
    }

    json_response(['success' => false, 'message' => 'Phương thức HTTP không được hỗ trợ.'], 405);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[Admin Categories] ' . $e->getMessage());
    json_response(['success' => false, 'message' => 'Lỗi hệ thống khi xử lý danh mục.'], 500);
}
