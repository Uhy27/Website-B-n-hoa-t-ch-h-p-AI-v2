<?php
declare(strict_types=1);

/**
 * BLOOMORA ADMIN 2.0 - BÁO CÁO THỐNG KÊ TOÀN DIỆN & BIỂU ĐỒ (DASHBOARD & STATS API)
 */
require_once __DIR__ . '/../../core/bootstrap.php';
require_admin_auth();

try {
    $pdo = getDBConnection();

    // 1. Chỉ số tổng quan (KPIs)
    $totalRevenue = (float)$pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE order_status != 'cancelled'")->fetchColumn();
    $todayRevenue = (float)$pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE order_status != 'cancelled' AND DATE(created_at) = CURDATE()")->fetchColumn();
    $totalOrders  = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $pendingOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE order_status IN ('received', 'preparing')")->fetchColumn();
    $photoReadyOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'photo_ready'")->fetchColumn();
    $deliveringOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'delivering'")->fetchColumn();
    $completedOrders  = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'completed'")->fetchColumn();
    $cancelledOrders  = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'cancelled'")->fetchColumn();

    $totalProducts = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE is_active = 1")->fetchColumn();
    $lowStockProducts = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE is_active = 1 AND stock_quantity <= 5")->fetchColumn();
    
    // Nguyên vật liệu sắp hết
    $lowStockMaterials = 0;
    try {
        $lowStockMaterials = (int)$pdo->query("SELECT COUNT(*) FROM materials WHERE stock_quantity <= min_stock_alert")->fetchColumn();
    } catch (Throwable $e) {}

    // Khách hàng & Chiến dịch
    $totalCustomers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role_id = 3")->fetchColumn();
    $activeCampaigns = (int)$pdo->query("SELECT COUNT(*) FROM campaigns WHERE status = 'active' AND approval_status = 'approved'")->fetchColumn();

    // Đánh giá chờ duyệt
    $pendingReviews = 0;
    try {
        $pendingReviews = (int)$pdo->query("SELECT COUNT(*) FROM reviews WHERE is_approved = 0")->fetchColumn();
    } catch (Throwable $e) {}

    // 2. Xu hướng doanh thu 7 ngày gần nhất (cho biểu đồ)
    $revenueTrend = [];
    $stmtTrend = $pdo->query("
        SELECT 
            DATE(d.date) AS order_date,
            COALESCE(SUM(o.total_amount), 0) AS revenue,
            COUNT(o.id) AS order_count
        FROM (
            SELECT CURDATE() - INTERVAL 6 DAY AS date UNION ALL
            SELECT CURDATE() - INTERVAL 5 DAY UNION ALL
            SELECT CURDATE() - INTERVAL 4 DAY UNION ALL
            SELECT CURDATE() - INTERVAL 3 DAY UNION ALL
            SELECT CURDATE() - INTERVAL 2 DAY UNION ALL
            SELECT CURDATE() - INTERVAL 1 DAY UNION ALL
            SELECT CURDATE()
        ) d
        LEFT JOIN orders o ON DATE(o.created_at) = d.date AND o.order_status != 'cancelled'
        GROUP BY d.date
        ORDER BY d.date ASC
    ");
    $revenueTrend = $stmtTrend->fetchAll(PDO::FETCH_ASSOC);

    // 3. Cơ cấu phương thức thanh toán
    $paymentDist = $pdo->query("
        SELECT payment_method, COUNT(*) AS count, COALESCE(SUM(total_amount), 0) AS total
        FROM orders
        WHERE order_status != 'cancelled'
        GROUP BY payment_method
    ")->fetchAll(PDO::FETCH_ASSOC);

    // 4. Phân bổ trạng thái đơn hàng
    $statusDist = [
        'received'    => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'received'")->fetchColumn(),
        'preparing'   => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'preparing'")->fetchColumn(),
        'photo_ready' => $photoReadyOrders,
        'delivering'  => $deliveringOrders,
        'completed'   => $completedOrders,
        'cancelled'   => $cancelledOrders,
    ];

    // 5. Top 5 sản phẩm hoa bán chạy nhất
    $topProducts = $pdo->query("
        SELECT 
            p.id, p.name, p.thumbnail, p.price,
            COALESCE(SUM(oi.quantity), 0) AS total_sold,
            COALESCE(SUM(oi.total_price), 0) AS total_sales
        FROM products p
        LEFT JOIN order_items oi ON oi.product_id = p.id
        LEFT JOIN orders o ON o.id = oi.order_id AND o.order_status != 'cancelled'
        GROUP BY p.id
        ORDER BY total_sold DESC, total_sales DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

    // 6. Hoạt động hệ thống mới nhất (Recent audit logs)
    $recentActivities = [];
    try {
        $recentActivities = $pdo->query("
            SELECT l.id, l.action, l.module, l.details, l.created_at, u.full_name AS user_name
            FROM activity_logs l
            LEFT JOIN users u ON u.id = l.user_id
            ORDER BY l.id DESC
            LIMIT 8
        ")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {}

    json_response([
        'success' => true,
        'data' => [
            'kpis' => [
                'totalRevenue'      => $totalRevenue,
                'todayRevenue'      => $todayRevenue,
                'totalOrders'       => $totalOrders,
                'pendingOrders'     => $pendingOrders,
                'photoReadyOrders'  => $photoReadyOrders,
                'deliveringOrders'  => $deliveringOrders,
                'completedOrders'   => $completedOrders,
                'cancelledOrders'   => $cancelledOrders,
                'totalProducts'     => $totalProducts,
                'lowStockProducts'  => $lowStockProducts,
                'lowStockMaterials' => $lowStockMaterials,
                'totalCustomers'    => $totalCustomers,
                'activeCampaigns'   => $activeCampaigns,
                'pendingReviews'    => $pendingReviews,
            ],
            'revenueTrend'     => $revenueTrend,
            'paymentDist'      => $paymentDist,
            'statusDist'       => $statusDist,
            'topProducts'      => $topProducts,
            'recentActivities' => $recentActivities,
        ]
    ]);
} catch (Throwable $e) {
    error_log('[Admin Stats Error] ' . $e->getMessage());
    json_response(['success' => false, 'message' => 'Lỗi thống kê hệ thống Bloomora Admin.'], 500);
}
