<?php
declare(strict_types=1);

/**
 * GET /api/categories.php
 * Trả về các danh mục đang hiển thị cho giao diện Client (không trả danh mục bị ẩn).
 */
require_once __DIR__ . '/../core/bootstrap.php';

api_only_method('GET');

try {
    $pdo = getDBConnection();

    $sql = "
        SELECT c.id, c.name, c.code, c.slug, c.parent_id, c.description,
               c.icon, c.image, c.badge, c.display_order,
               COUNT(p.id) AS product_count
        FROM categories c
        LEFT JOIN products p ON p.category_id = c.id AND p.is_active = 1
        WHERE c.is_active = 1 AND c.status = 'active'
        GROUP BY c.id
        ORDER BY c.display_order ASC, c.id ASC
    ";
    $categories = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    foreach ($categories as &$c) {
        $c['id'] = (int)$c['id'];
        $c['parent_id'] = $c['parent_id'] !== null ? (int)$c['parent_id'] : null;
        $c['display_order'] = (int)$c['display_order'];
        $c['product_count'] = (int)$c['product_count'];
    }
    unset($c);

    $totalActiveProducts = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE is_active = 1")->fetchColumn();

    array_unshift($categories, [
        'id'            => 'all',
        'name'          => 'Tất Cả Mẫu Hoa',
        'code'          => 'ALL',
        'slug'          => 'all',
        'parent_id'     => null,
        'description'   => 'Toàn bộ bộ sưu tập hoa tươi đang phục vụ tại Bloomora',
        'icon'          => 'ph-flower-lotus',
        'image'         => null,
        'badge'         => null,
        'display_order' => 0,
        'product_count' => $totalActiveProducts
    ]);

    json_response(['success' => true, 'data' => $categories]);
} catch (Throwable $e) {
    error_log('[Categories API Error] ' . $e->getMessage());
    json_response(['success' => false, 'message' => 'Lỗi tải danh mục sản phẩm.'], 500);
}
