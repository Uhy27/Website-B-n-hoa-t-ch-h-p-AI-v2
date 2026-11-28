<?php
/**
 * BLOOMORA ADMIN 2.0 - HỆ THỐNG TRUNG TÂM ĐIỀU HÀNH & QUẢN TRỊ CỬA HÀNG HOA TƯƠI
 * Toàn diện 13 phân hệ: Dashboard, Orders, Payments, Delivery & QC, Products, Categories,
 * Inventory & Materials, Vouchers, Reviews, Customers, Reports, Staff/RBAC, Settings & Logs.
 */
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/database.php';

if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}

init_secure_session();

// Kiểm tra quyền nội bộ
$allowedRoles = ['admin', 'staff', 'manager', 'florist', 'shipper', 'warehouse'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role_name'] ?? '', $allowedRoles, true)) {
    header('Location: login.php');
    exit;
}

$adminId    = (int)$_SESSION['user_id'];
$adminName  = $_SESSION['full_name'] ?? 'Quản Trị Viên';
$adminEmail = $_SESSION['email'] ?? 'admin@bloomora.vn';
$rawRole    = $_SESSION['role_name'] ?? 'staff';

$roleLabels = [
    'admin'     => 'Quản Trị Viên Tối Cao',
    'manager'   => 'Quản Lý Cửa Hàng',
    'florist'   => 'Nghệ Nhân Florist',
    'shipper'   => 'Nhân Viên Giao Vận',
    'warehouse' => 'Thủ Kho Hoa Tươi',
    'staff'     => 'Nhân Viên Cửa Hàng'
];
$adminRole = $roleLabels[$rawRole] ?? 'Nhân Viên';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bloomora Admin 2.0 | Trung Tâm Điều Hành Quản Trị Hoa Tươi</title>

  <!-- Google Fonts Chuẩn Tiếng Việt -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&family=Playfair+Display:ital,wght@0,600;0,700;0,800;1,600&display=swap" rel="stylesheet">

  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/@phosphor-icons/web"></script>
  <link rel="stylesheet" href="../css/style.css?v=2.4">

  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ["'Be Vietnam Pro'", '-apple-system', 'BlinkMacSystemFont', "'Segoe UI'", 'Roboto', "'Helvetica Neue'", 'Arial', 'sans-serif'],
            serif: ["'Playfair Display'", "'Be Vietnam Pro'", 'Georgia', "'Times New Roman'", 'serif'],
          },
          colors: {
            rose: {
              50: '#fff1f2',
              100: '#ffe4e6',
              200: '#fecdd3',
              500: '#f43f5e',
              600: '#e11d48',
              700: '#be123c',
              800: '#9f1239',
              900: '#881337',
              950: '#4c0519',
            }
          }
        }
      }
    }
  </script>

  <style>
    /* Custom Scrollbar */
    ::-webkit-scrollbar { width: 6px; height: 6px; }
    ::-webkit-scrollbar-track { background: #f1f1f1; }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
    ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    
    .sidebar-link.active {
      background: linear-gradient(90deg, #be123c 0%, #e11d48 100%);
      color: #ffffff !important;
      font-weight: 600;
      box-shadow: 0 4px 12px rgba(225, 29, 72, 0.25);
    }
    .sidebar-link.active i {
      color: #ffffff !important;
    }

    @media print {
      body * { visibility: hidden; }
      #invoicePrintSection, #invoicePrintSection * { visibility: visible; }
      #invoicePrintSection { position: absolute; left: 0; top: 0; width: 100%; margin: 0; padding: 20px; }
    }
  </style>
</head>
<body class="bg-stone-100 text-stone-800 antialiased min-h-screen flex flex-col md:flex-row">

  <!-- ================= SIDEBAR (DESKTOP & MOBILE) ================= -->
  <aside id="adminSidebar" class="w-64 bg-stone-900 text-stone-300 flex-shrink-0 flex flex-col justify-between border-r border-stone-800 fixed md:sticky top-0 h-screen z-40 transition-transform duration-300 -translate-x-full md:translate-x-0 overflow-y-auto">
    <div>
      <!-- Brand Logo -->
      <div class="h-16 flex items-center justify-between px-5 border-b border-stone-800 bg-stone-950/60">
        <a href="index.php" class="flex items-center space-x-2.5">
          <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-rose-700 to-rose-500 flex items-center justify-center text-white shadow-md shadow-rose-600/30">
            <i class="ph ph-flower-lotus text-xl"></i>
          </div>
          <div>
            <span class="font-serif-title text-base font-bold text-white tracking-wide block leading-none">BLOOMORA</span>
            <span class="text-[10px] text-rose-400 font-semibold tracking-wider uppercase">Admin 2.0 Pro</span>
          </div>
        </a>
        <button onclick="toggleSidebar()" class="md:hidden text-stone-400 hover:text-white text-xl">
          <i class="ph ph-x"></i>
        </button>
      </div>

      <!-- Navigation Menu -->
      <nav class="p-3 space-y-6 text-xs">
        <!-- Nhóm 1: Tổng quan -->
        <div>
          <span class="px-3 text-[10px] font-bold tracking-wider text-stone-300 uppercase block mb-1.5">TỔNG QUAN</span>
          <div class="space-y-1">
            <button onclick="switchView('dashboard')" id="nav-dashboard" class="sidebar-link w-full flex items-center space-x-3 px-3 py-2 rounded-xl text-stone-300 hover:text-white hover:bg-stone-800/80 transition">
              <i class="ph ph-squares-four text-lg text-rose-400"></i>
              <span>Bảng Điều Khiển</span>
            </button>
          </div>
        </div>

        <!-- Nhóm 2: Bán hàng & Vận hành -->
        <div>
          <span class="px-3 text-[10px] font-bold tracking-wider text-stone-300 uppercase block mb-1.5">BÁN HÀNG & ĐƠN HÀNG</span>
          <div class="space-y-1">
            <button onclick="switchView('orders')" id="nav-orders" class="sidebar-link w-full flex items-center justify-between px-3 py-2 rounded-xl text-stone-300 hover:text-white hover:bg-stone-800/80 transition">
              <div class="flex items-center space-x-3">
                <i class="ph ph-shopping-cart text-lg text-rose-400"></i>
                <span>Quản Lý Đơn Hàng</span>
              </div>
              <span id="badgeActiveOrders" class="hidden px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-600 text-white">0</span>
            </button>
            <button onclick="switchView('payments')" id="nav-payments" class="sidebar-link w-full flex items-center space-x-3 px-3 py-2 rounded-xl text-stone-300 hover:text-white hover:bg-stone-800/80 transition">
              <i class="ph ph-credit-card text-lg text-emerald-400"></i>
              <span>Thanh Toán & Đối Soát</span>
            </button>
            <button onclick="switchView('delivery')" id="nav-delivery" class="sidebar-link w-full flex items-center justify-between px-3 py-2 rounded-xl text-stone-300 hover:text-white hover:bg-stone-800/80 transition">
              <div class="flex items-center space-x-3">
                <i class="ph ph-truck text-lg text-amber-400"></i>
                <span>Điều Phối & QC Giao Hoa</span>
              </div>
              <span id="badgePendingQC" class="hidden px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-500 text-white">0</span>
            </button>
          </div>
        </div>

        <!-- Nhóm 3: Danh mục & Kho hoa -->
        <div>
          <span class="px-3 text-[10px] font-bold tracking-wider text-stone-300 uppercase block mb-1.5">DANH MỤC & KHO HOA</span>
          <div class="space-y-1">
            <button onclick="switchView('products')" id="nav-products" class="sidebar-link w-full flex items-center space-x-3 px-3 py-2 rounded-xl text-stone-300 hover:text-white hover:bg-stone-800/80 transition">
              <i class="ph ph-flower-tulip text-lg text-rose-400"></i>
              <span>Sản Phẩm Hoa Tươi</span>
            </button>
            <button onclick="switchView('categories')" id="nav-categories" class="sidebar-link w-full flex items-center space-x-3 px-3 py-2 rounded-xl text-stone-300 hover:text-white hover:bg-stone-800/80 transition">
              <i class="ph ph-tag text-lg text-purple-400"></i>
              <span>Danh Mục Hoa</span>
            </button>
            <button onclick="switchView('inventory')" id="nav-inventory" class="sidebar-link w-full flex items-center justify-between px-3 py-2 rounded-xl text-stone-300 hover:text-white hover:bg-stone-800/80 transition">
              <div class="flex items-center space-x-3">
                <i class="ph ph-package text-lg text-blue-400"></i>
                <span>Kho & Nguyên Phụ Liệu</span>
              </div>
              <span id="badgeLowStock" class="hidden px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-500 text-white">0</span>
            </button>
          </div>
        </div>

        <!-- Nhóm 4: Khách hàng & Marketing -->
        <div>
          <span class="px-3 text-[10px] font-bold tracking-wider text-stone-300 uppercase block mb-1.5">MARKETING & CHĂM SÓC</span>
          <div class="space-y-1">
            <button onclick="switchView('promotions')" id="nav-promotions" class="sidebar-link w-full flex items-center space-x-3 px-3 py-2 rounded-xl text-stone-300 hover:text-white hover:bg-stone-800/80 transition">
              <i class="ph ph-ticket text-lg text-amber-400"></i>
              <span>Khuyến Mãi & Vouchers</span>
            </button>
            <button onclick="switchView('reviews')" id="nav-reviews" class="sidebar-link w-full flex items-center justify-between px-3 py-2 rounded-xl text-stone-300 hover:text-white hover:bg-stone-800/80 transition">
              <div class="flex items-center space-x-3">
                <i class="ph ph-star text-lg text-amber-400"></i>
                <span>Đánh Giá & Ảnh Thực Tế</span>
              </div>
              <span id="badgePendingReviews" class="hidden px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-500 text-white">0</span>
            </button>
            <button onclick="switchView('customers')" id="nav-customers" class="sidebar-link w-full flex items-center space-x-3 px-3 py-2 rounded-xl text-stone-300 hover:text-white hover:bg-stone-800/80 transition">
              <i class="ph ph-users text-lg text-teal-400"></i>
              <span>Khách Hàng & VIP</span>
            </button>
          </div>
        </div>

        <!-- Nhóm 5: Báo cáo & Hệ thống -->
        <div>
          <span class="px-3 text-[10px] font-bold tracking-wider text-stone-300 uppercase block mb-1.5">BÁO CÁO & HỆ THỐNG</span>
          <div class="space-y-1">
            <button onclick="switchView('reports')" id="nav-reports" class="sidebar-link w-full flex items-center space-x-3 px-3 py-2 rounded-xl text-stone-300 hover:text-white hover:bg-stone-800/80 transition">
              <i class="ph ph-chart-line-up text-lg text-emerald-400"></i>
              <span>Báo Cáo & Phân Tích</span>
            </button>
            <button onclick="switchView('staff')" id="nav-staff" class="sidebar-link w-full flex items-center space-x-3 px-3 py-2 rounded-xl text-stone-300 hover:text-white hover:bg-stone-800/80 transition">
              <i class="ph ph-shield-check text-lg text-rose-400"></i>
              <span>Nhân Sự & Phân Quyền</span>
            </button>
            <button onclick="switchView('settings')" id="nav-settings" class="sidebar-link w-full flex items-center space-x-3 px-3 py-2 rounded-xl text-stone-300 hover:text-white hover:bg-stone-800/80 transition">
              <i class="ph ph-gear-six text-lg text-stone-400"></i>
              <span>Cài Đặt & Nhật Ký</span>
            </button>
          </div>
        </div>
      </nav>
    </div>

    <!-- Admin User Info & Logout (Bottom) -->
    <div class="p-3 border-t border-stone-800 bg-stone-950/40">
      <div class="flex items-center space-x-3 p-2 rounded-xl bg-stone-800/60 mb-2">
        <div class="w-9 h-9 rounded-xl bg-rose-600 text-white font-bold flex items-center justify-center text-sm shadow">
          <?php echo mb_substr($adminName, 0, 1); ?>
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-xs font-semibold text-white truncate"><?php echo htmlspecialchars($adminName); ?></p>
          <span class="text-[10px] text-rose-400 block truncate"><?php echo htmlspecialchars($adminRole); ?></span>
        </div>
      </div>

      <div class="flex items-center space-x-1">
        <a href="../index.html" target="_blank" class="flex-1 py-1.5 px-2 bg-stone-800 hover:bg-stone-700 text-stone-300 hover:text-white rounded-lg text-[11px] text-center font-medium transition flex items-center justify-center space-x-1">
          <i class="ph ph-arrow-square-out"></i>
          <span>Xem Shop</span>
        </a>
        <button onclick="handleAdminLogout()" class="py-1.5 px-2.5 bg-stone-800 hover:bg-rose-900/60 text-stone-400 hover:text-rose-300 rounded-lg text-[11px] font-medium transition" title="Đăng xuất">
          <i class="ph ph-sign-out text-sm"></i>
        </button>
      </div>
    </div>
  </aside>

  <!-- Backdrop mobile -->
  <div id="sidebarBackdrop" onclick="toggleSidebar()" class="fixed inset-0 bg-stone-900/50 backdrop-blur-sm z-30 hidden md:hidden"></div>

  <!-- ================= MAIN CONTENT WRAPPER ================= -->
  <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    
    <!-- TOPBAR -->
    <header class="bg-white border-b border-stone-200 sticky top-0 z-20 h-16 flex items-center justify-between px-4 sm:px-6">
      <div class="flex items-center space-x-3">
        <button onclick="toggleSidebar()" class="md:hidden text-stone-600 hover:text-stone-900 text-2xl p-1 rounded-lg">
          <i class="ph ph-list"></i>
        </button>
        <div>
          <div class="flex items-center space-x-1.5 text-xs text-stone-400">
            <span>Bloomora Admin</span>
            <span>/</span>
            <span id="breadcrumbSection" class="text-stone-700 font-semibold">Bảng Điều Khiển</span>
          </div>
          <h1 id="pageTitle" class="text-sm sm:text-base font-bold text-stone-900 font-serif-title leading-tight">Trung Tâm Điều Hành Bloomora</h1>
        </div>
      </div>

      <div class="flex items-center space-x-3 text-xs">
        <!-- Live Clock -->
        <div class="hidden sm:flex items-center space-x-1 text-stone-500 bg-stone-50 border border-stone-200 px-3 py-1.5 rounded-xl font-mono text-[11px]">
          <i class="ph ph-clock text-rose-600"></i>
          <span id="liveClockDisplay">--:--:--</span>
        </div>

        <!-- Reload Data Button -->
        <button onclick="refreshCurrentView()" class="p-2 bg-stone-50 hover:bg-stone-100 text-stone-600 hover:text-stone-900 border border-stone-200 rounded-xl transition shadow-sm" title="Làm mới dữ liệu">
          <i id="refreshIcon" class="ph ph-arrows-clockwise text-base"></i>
        </button>

        <a href="../index.html" target="_blank" class="hidden sm:flex items-center space-x-1 px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 font-semibold rounded-xl border border-rose-200 transition">
          <i class="ph ph-shopping-bag"></i>
          <span>Khách Đặt Hoa</span>
        </a>
      </div>
    </header>

    <!-- VIEWS CONTAINER -->
    <main class="flex-1 p-4 sm:p-6 lg:p-8 overflow-y-auto space-y-6">

      <!-- ================= 1. VIEW: DASHBOARD ================= -->
      <section id="view-dashboard" class="space-y-6">
        <!-- TOP 4 KPI CARDS -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
          <!-- Doanh thu thực tế -->
          <div class="bg-white p-5 rounded-2xl border border-stone-200 shadow-sm flex items-center justify-between">
            <div>
              <span class="text-xs text-stone-400 font-semibold block mb-1">Tổng Doanh Thu</span>
              <h3 id="dashTotalRevenue" class="text-2xl font-bold text-stone-900 font-serif-title">-- ₫</h3>
              <span class="text-[11px] text-emerald-600 font-medium">Hôm nay: <strong id="dashTodayRevenue" class="font-bold">-- ₫</strong></span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl shadow-sm">
              <i class="ph ph-money"></i>
            </div>
          </div>

          <!-- Tổng đơn hàng -->
          <div class="bg-white p-5 rounded-2xl border border-stone-200 shadow-sm flex items-center justify-between">
            <div>
              <span class="text-xs text-stone-400 font-semibold block mb-1">Tổng Số Đơn Hàng</span>
              <h3 id="dashTotalOrders" class="text-2xl font-bold text-stone-900 font-serif-title">--</h3>
              <span class="text-[11px] text-blue-600 font-medium">Khách hàng & vãng lai</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-2xl shadow-sm">
              <i class="ph ph-shopping-cart"></i>
            </div>
          </div>

          <!-- Đang xử lý & Giao -->
          <div class="bg-white p-5 rounded-2xl border border-stone-200 shadow-sm flex items-center justify-between">
            <div>
              <span class="text-xs text-stone-400 font-semibold block mb-1">Đang Xử Lý / Cắm Hoa</span>
              <h3 id="dashPendingOrders" class="text-2xl font-bold text-amber-600 font-serif-title">--</h3>
              <span class="text-[11px] text-purple-600 font-medium">Đang giao: <strong id="dashDeliveringOrders">--</strong></span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-2xl shadow-sm">
              <i class="ph ph-flower-lotus"></i>
            </div>
          </div>

          <!-- Tồn kho & Vật tư cảnh báo -->
          <div class="bg-white p-5 rounded-2xl border border-stone-200 shadow-sm flex items-center justify-between">
            <div>
              <span class="text-xs text-stone-400 font-semibold block mb-1">Cảnh Báo Tồn Kho</span>
              <h3 id="dashLowStockCount" class="text-2xl font-bold text-rose-600 font-serif-title">--</h3>
              <span class="text-[11px] text-rose-500 font-medium">Sản phẩm & vật tư sắp hết</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-2xl shadow-sm">
              <i class="ph ph-warning-circle"></i>
            </div>
          </div>
        </div>

        <!-- 7-DAY REVENUE CHART & ORDER STATUS BREAKDOWN -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- 7-Day Trend (2 cols) -->
          <div class="lg:col-span-2 bg-white p-5 sm:p-6 rounded-3xl border border-stone-200 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="font-bold text-stone-900 text-base font-serif-title">Biểu Đồ Doanh Thu 7 Ngày Gần Nhất</h3>
                <p class="text-xs text-stone-400">Doanh thu hoa tươi thực thu theo ngày</p>
              </div>
              <span class="px-2.5 py-1 bg-rose-50 text-rose-700 text-xs font-semibold rounded-lg border border-rose-100">Live Data</span>
            </div>
            <!-- Render Bars Container -->
            <div id="revenueChartContainer" class="h-64 flex items-end justify-between pt-8 pb-2 px-2 border-b border-stone-100 gap-2">
              <div class="text-center text-xs text-stone-400 w-full py-16">Đang tổng hợp dữ liệu doanh thu...</div>
            </div>
            <div id="chartDateLabels" class="flex justify-between text-[11px] text-stone-400 px-2 font-mono"></div>
          </div>

          <!-- Status & Payment Breakdown (1 col) -->
          <div class="bg-white p-5 sm:p-6 rounded-3xl border border-stone-200 shadow-sm space-y-5">
            <div>
              <h3 class="font-bold text-stone-900 text-base font-serif-title">Cơ Cấu Trạng Thái Đơn</h3>
              <p class="text-xs text-stone-400">Tiến trình vận hành hoa hôm nay</p>
            </div>

            <div id="statusBreakdownContainer" class="space-y-3 text-xs">
              <!-- Rendered via JS -->
            </div>

            <div class="pt-4 border-t border-stone-100">
              <h4 class="font-semibold text-stone-800 text-xs mb-2">Kênh Thanh Toán Phổ Biến</h4>
              <div id="paymentBreakdownContainer" class="space-y-2 text-xs">
                <!-- Rendered via JS -->
              </div>
            </div>
          </div>
        </div>

        <!-- TOP SELLING FLOWERS & RECENT ACTIVITY TIMELINE -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Top Products -->
          <div class="bg-white p-5 sm:p-6 rounded-3xl border border-stone-200 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="font-bold text-stone-900 text-base font-serif-title">Top Hoa Tươi Bán Chạy Nhất</h3>
                <p class="text-xs text-stone-400">Xếp hạng theo số lượng cắm và doanh số</p>
              </div>
              <button onclick="switchView('products')" class="text-xs text-rose-600 font-semibold hover:underline">Xem tất cả</button>
            </div>
            <div id="topProductsList" class="space-y-3">
              <!-- Rendered via JS -->
            </div>
          </div>

          <!-- Recent Audit Logs -->
          <div class="bg-white p-5 sm:p-6 rounded-3xl border border-stone-200 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="font-bold text-stone-900 text-base font-serif-title">Nhật Ký Hoạt Động Gần Đây</h3>
                <p class="text-xs text-stone-400">Ghi nhận thao tác kiểm duyệt, đơn hàng và kho</p>
              </div>
              <button onclick="switchView('settings')" class="text-xs text-rose-600 font-semibold hover:underline">Chi tiết</button>
            </div>
            <div id="recentActivityList" class="space-y-3 text-xs">
              <!-- Rendered via JS -->
            </div>
          </div>
        </div>
      </section>

      <!-- ================= 2. VIEW: ORDERS ================= -->
      <section id="view-orders" class="hidden space-y-5">
        <div class="bg-white rounded-3xl border border-stone-200 shadow-sm overflow-hidden">
          <div class="p-5 sm:p-6 border-b border-stone-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
              <h2 class="text-lg font-bold text-stone-900 font-serif-title">Quản Lý Đơn Hàng & Tiến Trình Cắm Hoa</h2>
              <p class="text-xs text-stone-400">Theo dõi chu kỳ 6 bước, chi tiết thiệp tặng và xuất phiếu giao hàng</p>
            </div>

            <!-- Filter Controls -->
            <div class="flex flex-wrap items-center gap-2 text-xs">
              <input 
                type="text" 
                id="orderSearchInput" 
                onkeyup="if(event.key==='Enter') loadAdminOrders()" 
                placeholder="Tìm mã đơn, tên, SĐT..." 
                class="bg-stone-50 border border-stone-200 rounded-xl px-3 py-2 text-stone-700 focus:ring-1 focus:ring-rose-500 focus:outline-none w-44 sm:w-56"
              />
              <select id="orderStatusFilter" onchange="loadAdminOrders()" class="bg-stone-50 border border-stone-200 rounded-xl px-3 py-2 text-stone-700 font-medium focus:ring-1 focus:ring-rose-500 focus:outline-none">
                <option value="all">Tất cả trạng thái</option>
                <option value="received">1. Đã tiếp nhận</option>
                <option value="preparing">2. Đang cắm hoa</option>
                <option value="photo_ready">3. Đã chụp ảnh QC</option>
                <option value="delivering">4. Đang giao hàng</option>
                <option value="completed">5. Hoàn tất</option>
                <option value="cancelled">Đã hủy</option>
              </select>
              <select id="orderPaymentFilter" onchange="loadAdminOrders()" class="bg-stone-50 border border-stone-200 rounded-xl px-3 py-2 text-stone-700 font-medium focus:ring-1 focus:ring-rose-500 focus:outline-none">
                <option value="all">Tất cả thanh toán</option>
                <option value="paid">Đã thanh toán</option>
                <option value="unpaid">Chưa thanh toán</option>
                <option value="refunded">Đã hoàn tiền</option>
              </select>
              <button onclick="loadAdminOrders()" class="px-3.5 py-2 bg-stone-900 hover:bg-black text-white rounded-xl font-semibold transition flex items-center space-x-1">
                <i class="ph ph-magnifying-glass"></i>
                <span>Lọc</span>
              </button>
            </div>
          </div>

          <!-- Table -->
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-stone-600">
              <thead class="bg-stone-50 text-[11px] text-stone-400 uppercase border-b border-stone-200/80">
                <tr>
                  <th class="py-3 px-4 font-semibold">Mã Đơn</th>
                  <th class="py-3 px-4 font-semibold">Khách Hàng / Nhận</th>
                  <th class="py-3 px-4 font-semibold">Giao Hàng</th>
                  <th class="py-3 px-4 font-semibold">Sản Phẩm Hoa</th>
                  <th class="py-3 px-4 font-semibold">Thiệp / Băng Rôn</th>
                  <th class="py-3 px-4 font-semibold">Tổng Tiền</th>
                  <th class="py-3 px-4 font-semibold">Trạng Thái</th>
                  <th class="py-3 px-4 font-semibold">Ảnh Thành Phẩm</th>
                  <th class="py-3 px-4 font-semibold text-right">Thao Tác</th>
                </tr>
              </thead>
              <tbody id="ordersTableBody" class="divide-y divide-stone-100">
                <!-- Rendered via JS -->
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- ================= 3. VIEW: PAYMENTS ================= -->
      <section id="view-payments" class="hidden space-y-5">
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <div class="bg-white p-5 rounded-2xl border border-stone-200 shadow-sm">
            <span class="text-xs text-stone-400 font-semibold block mb-1">Đã Thực Thu (Paid)</span>
            <h3 id="payTotalCollected" class="text-2xl font-bold text-emerald-600 font-serif-title">-- ₫</h3>
            <span class="text-[11px] text-stone-400">VietQR, MoMo & COD đã thu</span>
          </div>
          <div class="bg-white p-5 rounded-2xl border border-stone-200 shadow-sm">
            <span class="text-xs text-stone-400 font-semibold block mb-1">COD Chờ Thu Khi Giao</span>
            <h3 id="payPendingCod" class="text-2xl font-bold text-amber-600 font-serif-title">-- ₫</h3>
            <span class="text-[11px] text-amber-600">Shipper đang thu hộ</span>
          </div>
          <div class="bg-white p-5 rounded-2xl border border-stone-200 shadow-sm">
            <span class="text-xs text-stone-400 font-semibold block mb-1">Đã Hoàn Trả (Refunded)</span>
            <h3 id="payTotalRefunded" class="text-2xl font-bold text-rose-600 font-serif-title">-- ₫</h3>
            <span class="text-[11px] text-rose-400">Các giao dịch hủy & hoàn</span>
          </div>
          <div class="bg-white p-5 rounded-2xl border border-stone-200 shadow-sm">
            <span class="text-xs text-stone-400 font-semibold block mb-1">Tổng Số Giao Dịch Đã Khớp</span>
            <h3 id="payCountPaid" class="text-2xl font-bold text-stone-900 font-serif-title">--</h3>
            <span class="text-[11px] text-emerald-600">Đã đối soát sổ phụ ngân hàng</span>
          </div>
        </div>

        <div class="bg-white rounded-3xl border border-stone-200 shadow-sm overflow-hidden">
          <div class="p-5 sm:p-6 border-b border-stone-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
              <h2 class="text-lg font-bold text-stone-900 font-serif-title">Sổ Đối Soát Thanh Toán & Dòng Tiền</h2>
              <p class="text-xs text-stone-400">Kiểm tra giao dịch VietQR, MoMo, COD và xác nhận đối soát thủ công</p>
            </div>

            <div class="flex flex-wrap items-center gap-2 text-xs">
              <select id="payMethodFilter" onchange="loadPaymentsList()" class="bg-stone-50 border border-stone-200 rounded-xl px-3 py-2 text-stone-700 font-medium focus:outline-none">
                <option value="all">Tất cả phương thức</option>
                <option value="vietqr">VietQR (Ngân hàng)</option>
                <option value="momo">Ví MoMo</option>
                <option value="cod">Tiền mặt (COD)</option>
                <option value="bank_transfer">Chuyển khoản thường</option>
              </select>
              <select id="payStatusFilter" onchange="loadPaymentsList()" class="bg-stone-50 border border-stone-200 rounded-xl px-3 py-2 text-stone-700 font-medium focus:outline-none">
                <option value="all">Tất cả trạng thái</option>
                <option value="paid">Đã thanh toán (Paid)</option>
                <option value="unpaid">Chưa thanh toán (Unpaid)</option>
                <option value="refunded">Đã hoàn tiền (Refunded)</option>
              </select>
              <button onclick="loadPaymentsList()" class="px-3 py-2 bg-stone-900 hover:bg-black text-white rounded-xl font-semibold transition">
                Đối Soát
              </button>
            </div>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-stone-600">
              <thead class="bg-stone-50 text-[11px] text-stone-400 uppercase border-b border-stone-200/80">
                <tr>
                  <th class="py-3 px-4 font-semibold">Mã Giao Dịch</th>
                  <th class="py-3 px-4 font-semibold">Khách Hàng</th>
                  <th class="py-3 px-4 font-semibold">Phương Thức</th>
                  <th class="py-3 px-4 font-semibold">Số Tiền</th>
                  <th class="py-3 px-4 font-semibold">Trạng Thái Tiền</th>
                  <th class="py-3 px-4 font-semibold">Trạng Thái Đơn</th>
                  <th class="py-3 px-4 font-semibold">Ngày Tạo</th>
                  <th class="py-3 px-4 font-semibold text-right">Hành Động Đối Soát</th>
                </tr>
              </thead>
              <tbody id="paymentsTableBody" class="divide-y divide-stone-100">
                <!-- Rendered via JS -->
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- ================= 4. VIEW: DELIVERY & QC ================= -->
      <section id="view-delivery" class="hidden space-y-5">
        <div class="bg-white rounded-3xl border border-stone-200 shadow-sm overflow-hidden">
          <div class="p-5 sm:p-6 border-b border-stone-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
              <h2 class="text-lg font-bold text-stone-900 font-serif-title">Điều Phối Florist Cắm Hoa & Kiểm Định Chất Lượng (QC)</h2>
              <p class="text-xs text-stone-400">Kiểm tra 5 tiêu chí hoa tươi, chụp ảnh mẫu thành phẩm và bàn giao Shipper</p>
            </div>

            <div class="flex items-center space-x-2 text-xs">
              <button onclick="switchDeliveryStage('all')" id="btnStageAll" class="px-3 py-1.5 rounded-xl font-bold bg-stone-900 text-white">Tất Cả Đơn</button>
              <button onclick="switchDeliveryStage('florist')" id="btnStageFlorist" class="px-3 py-1.5 rounded-xl font-semibold bg-stone-100 text-stone-600 hover:bg-stone-200">Cần Cắm Hoa</button>
              <button onclick="switchDeliveryStage('qc')" id="btnStageQC" class="px-3 py-1.5 rounded-xl font-semibold bg-stone-100 text-stone-600 hover:bg-stone-200">Chờ Duyệt Ảnh QC</button>
              <button onclick="switchDeliveryStage('shipping')" id="btnStageShipping" class="px-3 py-1.5 rounded-xl font-semibold bg-stone-100 text-stone-600 hover:bg-stone-200">Đang Giao Hỏa Tốc</button>
            </div>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-stone-600">
              <thead class="bg-stone-50 text-[11px] text-stone-400 uppercase border-b border-stone-200/80">
                <tr>
                  <th class="py-3 px-4 font-semibold">Đơn Hàng</th>
                  <th class="py-3 px-4 font-semibold">Người Nhận & Địa Chỉ</th>
                  <th class="py-3 px-4 font-semibold">Khung Giờ Giao</th>
                  <th class="py-3 px-4 font-semibold">Florist Phụ Trách</th>
                  <th class="py-3 px-4 font-semibold">Shipper Giao</th>
                  <th class="py-3 px-4 font-semibold">Tiêu Chuẩn QC</th>
                  <th class="py-3 px-4 font-semibold">Ảnh Thành Phẩm</th>
                  <th class="py-3 px-4 font-semibold text-right">Quy Trình</th>
                </tr>
              </thead>
              <tbody id="deliveryTableBody" class="divide-y divide-stone-100">
                <!-- Rendered via JS -->
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- ================= 5. VIEW: PRODUCTS ================= -->
      <section id="view-products" class="hidden space-y-5">
        <div class="bg-white rounded-3xl border border-stone-200 shadow-sm overflow-hidden">
          <div class="p-5 sm:p-6 border-b border-stone-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
              <h2 class="text-lg font-bold text-stone-900 font-serif-title">Danh Mục & Bảng Giá Hoa Tươi</h2>
              <p class="text-xs text-stone-400">Quản lý mẫu hoa, giá bán, giá vốn (cost), biên lợi nhuận % và tồn kho</p>
            </div>

            <div class="flex flex-wrap items-center gap-2 text-xs">
              <input 
                type="text" 
                id="productSearchInput" 
                onkeyup="if(event.key==='Enter') loadAdminProducts()" 
                placeholder="Tìm hoa, mã SKU..." 
                class="bg-stone-50 border border-stone-200 rounded-xl px-3 py-2 text-stone-700 focus:outline-none w-48"
              />
              <select id="productCategoryFilter" onchange="loadAdminProducts()" class="bg-stone-50 border border-stone-200 rounded-xl px-3 py-2 text-stone-700 font-medium focus:outline-none">
                <option value="0">Tất cả danh mục</option>
              </select>
              <select id="productStatusFilter" onchange="loadAdminProducts()" class="bg-stone-50 border border-stone-200 rounded-xl px-3 py-2 text-stone-700 font-medium focus:outline-none">
                <option value="">Tất cả trạng thái</option>
                <option value="active">Đang mở bán</option>
                <option value="inactive">Tạm ngừng bán</option>
              </select>
              <select id="productStockFilter" onchange="loadAdminProducts()" class="bg-stone-50 border border-stone-200 rounded-xl px-3 py-2 text-stone-700 font-medium focus:outline-none">
                <option value="">Tất cả tồn kho</option>
                <option value="in_stock">Còn hàng dồi dào (>5)</option>
                <option value="low_stock">Sắp hết hàng (1-5)</option>
                <option value="out_of_stock">Hết hàng (0)</option>
              </select>
              <select id="productSortFilter" onchange="loadAdminProducts()" class="bg-stone-50 border border-stone-200 rounded-xl px-3 py-2 text-stone-700 font-medium focus:outline-none">
                <option value="id_desc">Mới nhất (ID giảm)</option>
                <option value="id_asc">Cũ nhất (ID tăng)</option>
                <option value="price_asc">Giá bán tăng dần</option>
                <option value="price_desc">Giá bán giảm dần</option>
                <option value="stock_asc">Tồn kho ít nhất</option>
                <option value="stock_desc">Tồn kho nhiều nhất</option>
                <option value="name_asc">Tên hoa A-Z</option>
                <option value="rating_desc">Đánh giá cao nhất</option>
              </select>
              <button onclick="openProductModal()" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl font-bold shadow-sm transition flex items-center space-x-1.5">
                <i class="ph ph-plus-circle text-base"></i>
                <span>Thêm Hoa Mới</span>
              </button>
            </div>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-stone-600">
              <thead class="bg-stone-50 text-[11px] text-stone-400 uppercase border-b border-stone-200/80">
                <tr>
                  <th class="py-3 px-4 font-semibold">Ảnh / Mẫu Hoa</th>
                  <th class="py-3 px-4 font-semibold">SKU / Phân Loại</th>
                  <th class="py-3 px-4 font-semibold">Giá Bán</th>
                  <th class="py-3 px-4 font-semibold">Giá Vốn (Cost)</th>
                  <th class="py-3 px-4 font-semibold">Biên Lợi Nhuận</th>
                  <th class="py-3 px-4 font-semibold">Tồn Kho</th>
                  <th class="py-3 px-4 font-semibold">Trạng Thái</th>
                  <th class="py-3 px-4 font-semibold text-right">Thao Tác</th>
                </tr>
              </thead>
              <tbody id="productsTableBody" class="divide-y divide-stone-100">
                <!-- Rendered via JS -->
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- ================= 6. VIEW: CATEGORIES ================= -->
      <section id="view-categories" class="hidden space-y-5">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div class="bg-white p-4 rounded-2xl border border-stone-200 shadow-sm flex items-center justify-between">
            <div>
              <span class="text-xs text-stone-400 font-semibold block mb-0.5">Tổng Danh Mục</span>
              <h4 id="catStatTotal" class="text-xl font-bold text-stone-900 font-serif-title">--</h4>
            </div>
            <div class="w-10 h-10 rounded-xl bg-stone-100 text-stone-700 flex items-center justify-center text-lg"><i class="ph ph-folders"></i></div>
          </div>
          <div class="bg-white p-4 rounded-2xl border border-stone-200 shadow-sm flex items-center justify-between">
            <div>
              <span class="text-xs text-stone-400 font-semibold block mb-0.5">Đang Hiển Thị (Client)</span>
              <h4 id="catStatActive" class="text-xl font-bold text-emerald-600 font-serif-title">--</h4>
            </div>
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg"><i class="ph ph-eye"></i></div>
          </div>
          <div class="bg-white p-4 rounded-2xl border border-stone-200 shadow-sm flex items-center justify-between">
            <div>
              <span class="text-xs text-stone-400 font-semibold block mb-0.5">Đang Ẩn</span>
              <h4 id="catStatHidden" class="text-xl font-bold text-amber-600 font-serif-title">--</h4>
            </div>
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg"><i class="ph ph-eye-slash"></i></div>
          </div>
        </div>

        <div class="bg-white rounded-3xl border border-stone-200 shadow-sm overflow-hidden">
          <div class="p-5 sm:p-6 border-b border-stone-100 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div>
              <h2 class="text-lg font-bold text-stone-900 font-serif-title">Quản Lý Danh Mục Sản Phẩm</h2>
              <p class="text-xs text-stone-400">Phân cấp danh mục, xem sản phẩm liên kết, ẩn/hiện an toàn hoặc chuyển danh mục trước khi xóa</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 text-xs">
              <input
                type="text"
                id="catSearchInput"
                onkeyup="if(event.key==='Enter') loadAdminCategories(1)"
                placeholder="Tìm tên, mã danh mục, slug..."
                class="bg-stone-50 border border-stone-200 rounded-xl px-3 py-2 text-stone-700 focus:ring-1 focus:ring-rose-500 focus:outline-none w-48 sm:w-56"
              />
              <select id="catStatusFilter" onchange="loadAdminCategories(1)" class="bg-stone-50 border border-stone-200 rounded-xl px-3 py-2 text-stone-700 font-medium focus:outline-none">
                <option value="all">Tất cả trạng thái</option>
                <option value="active">Đang hiển thị</option>
                <option value="hidden">Đang ẩn</option>
              </select>
              <select id="catSortFilter" onchange="loadAdminCategories(1)" class="bg-stone-50 border border-stone-200 rounded-xl px-3 py-2 text-stone-700 font-medium focus:outline-none">
                <option value="order_asc">Theo thứ tự hiển thị</option>
                <option value="newest">Mới tạo nhất</option>
                <option value="name_asc">Tên A - Z</option>
                <option value="products_desc">Nhiều sản phẩm nhất</option>
              </select>
              <button onclick="openCategoryModal()" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl font-bold shadow-sm transition flex items-center space-x-1.5">
                <i class="ph ph-plus-circle text-base"></i>
                <span>Thêm Danh Mục</span>
              </button>
            </div>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-stone-600">
              <thead class="bg-stone-50 text-[11px] text-stone-400 uppercase border-b border-stone-200/80">
                <tr>
                  <th class="py-3 px-4 font-semibold">Hình Ảnh</th>
                  <th class="py-3 px-4 font-semibold">Tên Danh Mục</th>
                  <th class="py-3 px-4 font-semibold">Mã Danh Mục</th>
                  <th class="py-3 px-4 font-semibold">Số Sản Phẩm</th>
                  <th class="py-3 px-4 font-semibold text-center">Thứ Tự</th>
                  <th class="py-3 px-4 font-semibold">Trạng Thái</th>
                  <th class="py-3 px-4 font-semibold">Ngày Tạo</th>
                  <th class="py-3 px-4 font-semibold text-right">Thao Tác</th>
                </tr>
              </thead>
              <tbody id="categoriesTableBody" class="divide-y divide-stone-100">
                <!-- Rendered via JS -->
              </tbody>
            </table>
          </div>
          <div id="categoriesPagination" class="p-4 border-t border-stone-100 flex items-center justify-between text-xs text-stone-500"></div>
        </div>
      </section>

      <!-- ================= 7. VIEW: INVENTORY & MATERIALS ================= -->
      <section id="view-inventory" class="hidden space-y-5">
        <!-- Sub Tabs -->
        <div class="flex items-center space-x-2 border-b border-stone-200 pb-2">
          <button onclick="switchInventorySubTab('materials')" id="btnInvMaterials" class="px-4 py-2 rounded-xl text-xs font-bold bg-rose-600 text-white shadow-sm flex items-center space-x-1.5">
            <i class="ph ph-flower-tulip"></i>
            <span>Kho Nguyên Vật Liệu & Hoa Tươi</span>
          </button>
          <button onclick="switchInventorySubTab('products')" id="btnInvProducts" class="px-4 py-2 rounded-xl text-xs font-bold bg-white text-stone-600 hover:text-stone-900 border border-stone-200 flex items-center space-x-1.5">
            <i class="ph ph-package"></i>
            <span>Kho Mẫu Hoa Thành Phẩm</span>
          </button>
        </div>

        <!-- Material Summary & Table -->
        <div id="materialsWrap" class="space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white p-4 rounded-2xl border border-stone-200 shadow-sm">
              <span class="text-xs text-stone-400 font-semibold block mb-0.5">Tổng Nguyên Liệu Đang Quản Lý</span>
              <h4 id="matTotalItems" class="text-xl font-bold text-stone-900 font-serif-title">-- Mặt hàng</h4>
            </div>
            <div class="bg-white p-4 rounded-2xl border border-stone-200 shadow-sm">
              <span class="text-xs text-stone-400 font-semibold block mb-0.5">Vật Tư Cần Nhập Thêm</span>
              <h4 id="matLowStockCount" class="text-xl font-bold text-rose-600 font-serif-title">-- Cảnh báo</h4>
            </div>
            <div class="bg-white p-4 rounded-2xl border border-stone-200 shadow-sm">
              <span class="text-xs text-stone-400 font-semibold block mb-0.5">Ước Tính Giá Trị Tồn Kho</span>
              <h4 id="matTotalValuation" class="text-xl font-bold text-emerald-600 font-serif-title">-- ₫</h4>
            </div>
          </div>

          <div class="bg-white rounded-3xl border border-stone-200 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-stone-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
              <div>
                <h3 class="font-bold text-stone-900 font-serif-title text-base">Danh Mục Cành Hoa, Giấy Gói, Ruy Băng & Phụ Liệu</h3>
                <p class="text-xs text-stone-400">Kiểm soát chặt chẽ tỷ lệ hao hụt hoa tươi và phụ kiện cắm hoa</p>
              </div>

              <div class="flex items-center space-x-2 text-xs">
                <select id="matCategoryFilter" onchange="loadMaterialsList()" class="bg-stone-50 border border-stone-200 rounded-xl px-3 py-2 text-stone-700 focus:outline-none">
                  <option value="all">Tất cả nhóm vật tư</option>
                  <option value="flower_fresh">Hoa tươi cắt cành</option>
                  <option value="wrapping_paper">Giấy gói lụa Hàn Quốc</option>
                  <option value="ribbon">Ruy băng / Nơ</option>
                  <option value="basket_vase">Giỏ gỗ / Bình gốm</option>
                  <option value="accessories">Xốp Oasis / Phụ kiện</option>
                </select>
                <button onclick="openMaterialModal()" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl font-bold shadow-sm transition flex items-center space-x-1">
                  <i class="ph ph-plus-circle"></i>
                  <span>Thêm Vật Tư Mới</span>
                </button>
              </div>
            </div>

            <div class="overflow-x-auto">
              <table class="w-full text-left text-xs text-stone-600">
                <thead class="bg-stone-50 text-[11px] text-stone-400 uppercase border-b border-stone-200/80">
                  <tr>
                    <th class="py-3 px-4 font-semibold">Mã Vật Tư</th>
                    <th class="py-3 px-4 font-semibold">Tên Vật Liệu / Hoa</th>
                    <th class="py-3 px-4 font-semibold">Phân Loại</th>
                    <th class="py-3 px-4 font-semibold">Đơn Vị Tính</th>
                    <th class="py-3 px-4 font-semibold">Số Lượng Tồn</th>
                    <th class="py-3 px-4 font-semibold">Đơn Giá Vốn</th>
                    <th class="py-3 px-4 font-semibold">Nhà Cung Cấp</th>
                    <th class="py-3 px-4 font-semibold">Trạng Thái</th>
                    <th class="py-3 px-4 font-semibold text-right">Điều Chỉnh Kho</th>
                  </tr>
                </thead>
                <tbody id="materialsTableBody" class="divide-y divide-stone-100">
                  <!-- Rendered via JS -->
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Product Inventory Wrap -->
        <div id="productInventoryWrap" class="hidden space-y-4">
          <div class="bg-white rounded-3xl border border-stone-200 shadow-sm overflow-hidden p-5">
            <h3 class="font-bold text-stone-900 font-serif-title text-base mb-4">Tồn Kho Mẫu Hoa & Lịch Sử Xuất/Nhập/Bán</h3>
            <div id="prodInvTableContainer" class="overflow-x-auto">
              <!-- Rendered via JS -->
            </div>
          </div>
        </div>
      </section>

      <!-- ================= 8. VIEW: PROMOTIONS ================= -->
      <section id="view-promotions" class="hidden space-y-5">
        <!-- KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <div class="bg-white p-4 rounded-2xl border border-stone-200 shadow-sm flex items-center justify-between">
            <div>
              <span class="text-xs text-stone-400 font-semibold block mb-0.5">Đang Diễn Ra</span>
              <h4 id="promoStatActive" class="text-xl font-bold text-emerald-600 font-serif-title">--</h4>
            </div>
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg"><i class="ph ph-lightning"></i></div>
          </div>
          <div class="bg-white p-4 rounded-2xl border border-stone-200 shadow-sm flex items-center justify-between">
            <div>
              <span class="text-xs text-stone-400 font-semibold block mb-0.5">Đã Lên Lịch / Tạm Dừng</span>
              <h4 id="promoStatScheduled" class="text-xl font-bold text-blue-600 font-serif-title">--</h4>
            </div>
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg"><i class="ph ph-calendar-check"></i></div>
          </div>
          <div class="bg-white p-4 rounded-2xl border border-stone-200 shadow-sm flex items-center justify-between">
            <div>
              <span class="text-xs text-stone-400 font-semibold block mb-0.5">Tổng Lượt Đã Dùng</span>
              <h4 id="promoStatTotalUsed" class="text-xl font-bold text-stone-900 font-serif-title">--</h4>
            </div>
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg"><i class="ph ph-ticket"></i></div>
          </div>
          <div class="bg-white p-4 rounded-2xl border border-stone-200 shadow-sm flex items-center justify-between">
            <div>
              <span class="text-xs text-stone-400 font-semibold block mb-0.5">Tổng Tiền Đã Giảm</span>
              <h4 id="promoStatTotalSaved" class="text-xl font-bold text-rose-600 font-serif-title">-- ₫</h4>
            </div>
            <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-lg"><i class="ph ph-currency-circle-dollar"></i></div>
          </div>
        </div>

        <div class="bg-white rounded-3xl border border-stone-200 shadow-sm overflow-hidden">
          <div class="p-5 sm:p-6 border-b border-stone-100 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
              <div>
                <h2 class="text-lg font-bold text-stone-900 font-serif-title">Quản Lý Chương Trình Khuyến Mãi & Mã Giảm Giá</h2>
                <p class="text-xs text-stone-400">Hẹn giờ tự động, giới hạn lượt dùng toàn hệ thống & từng tài khoản, phân phạm vi sản phẩm/danh mục</p>
              </div>
              <button onclick="openPromotionModal()" class="px-4 py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl font-bold shadow-sm transition flex items-center space-x-1.5 text-xs w-max">
                <i class="ph ph-plus-circle text-base"></i>
                <span>Tạo Chương Trình Khuyến Mãi</span>
              </button>
            </div>

            <!-- Filters Bar -->
            <div class="flex flex-wrap items-center gap-2 text-xs">
              <input
                type="text"
                id="promoSearchInput"
                onkeyup="if(event.key==='Enter') loadAdminPromotions(1)"
                placeholder="Tìm tên chương trình hoặc mã..."
                class="bg-stone-50 border border-stone-200 rounded-xl px-3 py-2 text-stone-700 focus:ring-1 focus:ring-rose-500 focus:outline-none w-48 sm:w-56"
              />
              <select id="promoStatusFilter" onchange="loadAdminPromotions(1)" class="bg-stone-50 border border-stone-200 rounded-xl px-3 py-2 text-stone-700 font-medium focus:outline-none">
                <option value="all">Tất cả trạng thái</option>
                <option value="active">Đang diễn ra</option>
                <option value="scheduled">Đã lên lịch</option>
                <option value="paused">Tạm dừng</option>
                <option value="draft">Nháp</option>
                <option value="ended">Đã kết thúc</option>
              </select>
              <select id="promoTypeFilter" onchange="loadAdminPromotions(1)" class="bg-stone-50 border border-stone-200 rounded-xl px-3 py-2 text-stone-700 font-medium focus:outline-none">
                <option value="all">Tất cả loại giảm</option>
                <option value="percentage">Giảm theo %</option>
                <option value="fixed_amount">Giảm tiền cố định (VNĐ)</option>
              </select>
              <input type="date" id="promoDateFrom" onchange="loadAdminPromotions(1)" title="Từ ngày" class="bg-stone-50 border border-stone-200 rounded-xl px-2.5 py-1.5 text-stone-700 focus:outline-none" />
              <input type="date" id="promoDateTo" onchange="loadAdminPromotions(1)" title="Đến ngày" class="bg-stone-50 border border-stone-200 rounded-xl px-2.5 py-1.5 text-stone-700 focus:outline-none" />
              <select id="promoSortFilter" onchange="loadAdminPromotions(1)" class="bg-stone-50 border border-stone-200 rounded-xl px-3 py-2 text-stone-700 font-medium focus:outline-none">
                <option value="created_desc">Mới tạo nhất</option>
                <option value="end_asc">Sắp hết hạn</option>
                <option value="end_desc">Kết thúc muộn nhất</option>
                <option value="used_desc">Dùng nhiều nhất</option>
              </select>
              <button onclick="resetPromoFilters()" class="px-3 py-2 bg-stone-100 hover:bg-stone-200 text-stone-600 rounded-xl font-medium transition">Đặt lại</button>
            </div>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-stone-600">
              <thead class="bg-stone-50 text-[11px] text-stone-400 uppercase border-b border-stone-200/80">
                <tr>
                  <th class="py-3 px-4 font-semibold">Chương Trình</th>
                  <th class="py-3 px-4 font-semibold">Mã KM</th>
                  <th class="py-3 px-4 font-semibold">Loại Giảm</th>
                  <th class="py-3 px-4 font-semibold">Giá Trị & Điều Kiện</th>
                  <th class="py-3 px-4 font-semibold">Thời Gian Hiệu Lực</th>
                  <th class="py-3 px-4 font-semibold">Lượt (Tổng / Dùng / Còn)</th>
                  <th class="py-3 px-4 font-semibold">Trạng Thái</th>
                  <th class="py-3 px-4 font-semibold text-right">Thao Tác</th>
                </tr>
              </thead>
              <tbody id="vouchersTableBody" class="divide-y divide-stone-100">
                <!-- Rendered via JS -->
              </tbody>
            </table>
          </div>
          <div id="promotionsPagination" class="p-4 border-t border-stone-100 flex items-center justify-between text-xs text-stone-500"></div>
        </div>
      </section>

      <!-- ================= 9. VIEW: REVIEWS ================= -->
      <section id="view-reviews" class="hidden space-y-5">
        <!-- Review KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <div class="bg-white p-5 rounded-2xl border border-stone-200 shadow-sm flex items-center justify-between">
            <div>
              <span class="text-xs text-stone-400 font-semibold block mb-1">Tổng Số Đánh Giá</span>
              <h3 id="statRevTotal" class="text-2xl font-bold text-stone-900 font-serif-title">--</h3>
              <span class="text-[11px] text-stone-500">Khách hàng đã mua gửi về</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-2xl">
              <i class="ph ph-chat-circle-dots"></i>
            </div>
          </div>

          <div class="bg-white p-5 rounded-2xl border border-stone-200 shadow-sm flex items-center justify-between">
            <div>
              <span class="text-xs text-stone-400 font-semibold block mb-1">Đánh Giá Chờ Duyệt</span>
              <h3 id="statRevPending" class="text-2xl font-bold text-amber-600 font-serif-title">--</h3>
              <span class="text-[11px] text-amber-600 font-medium">Cần kiểm duyệt nội dung</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-2xl">
              <i class="ph ph-hourglass-high"></i>
            </div>
          </div>

          <div class="bg-white p-5 rounded-2xl border border-stone-200 shadow-sm flex items-center justify-between">
            <div>
              <span class="text-xs text-stone-400 font-semibold block mb-1">Đã Duyệt Công Khai</span>
              <h3 id="statRevApproved" class="text-2xl font-bold text-emerald-600 font-serif-title">--</h3>
              <span class="text-[11px] text-emerald-600 font-medium">Đang hiển thị trên web</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl">
              <i class="ph ph-seal-check"></i>
            </div>
          </div>

          <div class="bg-white p-5 rounded-2xl border border-stone-200 shadow-sm flex items-center justify-between">
            <div>
              <span class="text-xs text-stone-400 font-semibold block mb-1">Từ Chối / Ẩn</span>
              <h3 id="statRevRejected" class="text-2xl font-bold text-rose-600 font-serif-title">--</h3>
              <span class="text-[11px] text-rose-500">Vi phạm tiêu chuẩn Bloomora</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-2xl">
              <i class="ph ph-prohibit"></i>
            </div>
          </div>
        </div>

        <!-- Filter & Table -->
        <div class="bg-white rounded-3xl border border-stone-200 shadow-sm overflow-hidden">
          <div class="p-5 sm:p-6 border-b border-stone-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
              <h2 class="text-lg font-bold text-stone-900 font-serif-title">Kiểm Duyệt Đánh Giá & Ảnh Thực Tế Của Khách Hàng</h2>
              <p class="text-xs text-stone-400">Xem ảnh hoa thực tế khách đăng, duyệt hiển thị, từ chối vi phạm và gửi phản hồi chính thức</p>
            </div>

            <div class="flex flex-wrap items-center gap-2 text-xs">
              <select id="statusReviewFilter" onchange="loadAdminReviews(1)" class="bg-stone-50 border border-stone-200 rounded-xl px-3 py-2 text-stone-700 font-medium focus:outline-none">
                <option value="all">Tất cả trạng thái</option>
                <option value="pending">⏳ Chờ duyệt (Pending)</option>
                <option value="approved">✓ Đã duyệt (Approved)</option>
                <option value="hidden">👁️ Tạm ẩn (Hidden)</option>
                <option value="rejected">✕ Bị từ chối (Rejected)</option>
              </select>

              <select id="ratingReviewFilter" onchange="loadAdminReviews(1)" class="bg-stone-50 border border-stone-200 rounded-xl px-3 py-2 text-stone-700 font-medium focus:outline-none">
                <option value="all">Tất cả số sao</option>
                <option value="5">5 Sao (Tuyệt vời)</option>
                <option value="4">4 Sao (Hài lòng)</option>
                <option value="3">3 Sao (Bình thường)</option>
                <option value="2">2 Sao (Chưa ưng ý)</option>
                <option value="1">1 Sao (Thất vọng)</option>
              </select>

              <button onclick="loadAdminReviews(1)" class="p-2 bg-stone-100 hover:bg-stone-200 rounded-xl text-stone-600 transition" title="Làm mới">
                <i class="ph ph-arrows-clockwise text-base"></i>
              </button>
            </div>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-stone-600">
              <thead class="bg-stone-50 text-[11px] text-stone-400 uppercase border-b border-stone-200/80">
                <tr>
                  <th class="py-3 px-4 font-semibold">Sản Phẩm & Đơn</th>
                  <th class="py-3 px-4 font-semibold">Khách Hàng</th>
                  <th class="py-3 px-4 font-semibold">Đánh Giá & Nhận Xét</th>
                  <th class="py-3 px-4 font-semibold">Ảnh Thực Tế</th>
                  <th class="py-3 px-4 font-semibold">Trạng Thái</th>
                  <th class="py-3 px-4 font-semibold">Phản Hồi Shop</th>
                  <th class="py-3 px-4 font-semibold text-right">Kiểm Duyệt</th>
                </tr>
              </thead>
              <tbody id="reviewsTableBody" class="divide-y divide-stone-100">
                <!-- Rendered via JS -->
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- ================= 10. VIEW: CUSTOMERS ================= -->
      <section id="view-customers" class="hidden space-y-5">
        <div class="bg-white rounded-3xl border border-stone-200 shadow-sm overflow-hidden">
          <div class="p-5 sm:p-6 border-b border-stone-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
              <h2 class="text-lg font-bold text-stone-900 font-serif-title">Hồ Sơ Khách Hàng & Phân Khúc VIP</h2>
              <p class="text-xs text-stone-400">Danh sách thành viên đăng ký, tổng chi tiêu hoa tươi và lịch sử giao dịch</p>
            </div>
            <div class="flex items-center space-x-2 text-xs">
              <input 
                type="text" 
                id="customerSearchInput" 
                onkeyup="if(event.key==='Enter') loadAdminCustomers()" 
                placeholder="Tìm tên, email, SĐT..." 
                class="bg-stone-50 border border-stone-200 rounded-xl px-3 py-2 text-stone-700 focus:outline-none w-56"
              />
              <button onclick="loadAdminCustomers()" class="px-3.5 py-2 bg-stone-900 hover:bg-black text-white rounded-xl font-semibold transition">
                Tìm Kiếm
              </button>
            </div>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-stone-600">
              <thead class="bg-stone-50 text-[11px] text-stone-400 uppercase border-b border-stone-200/80">
                <tr>
                  <th class="py-3 px-4 font-semibold">ID</th>
                  <th class="py-3 px-4 font-semibold">Khách Hàng</th>
                  <th class="py-3 px-4 font-semibold">Liên Hệ</th>
                  <th class="py-3 px-4 font-semibold">Tổng Đơn Đã Đặt</th>
                  <th class="py-3 px-4 font-semibold">Tổng Chi Tiêu</th>
                  <th class="py-3 px-4 font-semibold">Phân Khúc</th>
                  <th class="py-3 px-4 font-semibold">Trạng Thái</th>
                  <th class="py-3 px-4 font-semibold text-right">Thao Tác</th>
                </tr>
              </thead>
              <tbody id="customersTableBody" class="divide-y divide-stone-100">
                <!-- Rendered via JS -->
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- ================= 11. VIEW: REPORTS ================= -->
      <section id="view-reports" class="hidden space-y-6">
        <div class="bg-white p-6 rounded-3xl border border-stone-200 shadow-sm space-y-6">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-stone-100 pb-4">
            <div>
              <h2 class="text-lg font-bold text-stone-900 font-serif-title">Báo Cáo Hoạt Động & Doanh Thu Chi Tiết</h2>
              <p class="text-xs text-stone-400">Số liệu tài chính, hiệu suất xử lý đơn và phân tích cơ cấu giỏ hàng</p>
            </div>
            <div class="flex items-center space-x-2 text-xs">
              <button onclick="printReportSummary()" class="px-3.5 py-2 bg-stone-100 hover:bg-stone-200 text-stone-700 font-semibold rounded-xl transition flex items-center space-x-1">
                <i class="ph ph-printer"></i>
                <span>In Báo Cáo</span>
              </button>
            </div>
          </div>

          <!-- Report Key Financial Grid -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
            <div class="p-5 rounded-2xl bg-rose-50/60 border border-rose-100">
              <span class="text-xs text-stone-500 font-semibold block mb-1">Tổng Tiền Thu Về Thực Tế</span>
              <h3 id="repNetRevenue" class="text-2xl font-bold text-rose-700 font-serif-title">-- ₫</h3>
              <p class="text-[11px] text-stone-500 mt-1">Đã khấu trừ các đơn hủy</p>
            </div>
            <div class="p-5 rounded-2xl bg-blue-50/60 border border-blue-100">
              <span class="text-xs text-stone-500 font-semibold block mb-1">Giá Trị Trung Bình / Đơn (AOV)</span>
              <h3 id="repAOV" class="text-2xl font-bold text-blue-700 font-serif-title">-- ₫</h3>
              <p class="text-[11px] text-stone-500 mt-1">Mức chi tiêu trung bình mỗi lần đặt</p>
            </div>
            <div class="p-5 rounded-2xl bg-emerald-50/60 border border-emerald-100">
              <span class="text-xs text-stone-500 font-semibold block mb-1">Tỷ Lệ Giao Thành Công</span>
              <h3 id="repSuccessRate" class="text-2xl font-bold text-emerald-700 font-serif-title">-- %</h3>
              <p class="text-[11px] text-stone-500 mt-1">Đơn hoàn tất / Tổng đơn đặt</p>
            </div>
          </div>

          <!-- Visual Breakdown Tables -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 pt-2">
            <div>
              <h4 class="font-bold text-stone-900 text-sm mb-3">Hiệu Suất Theo Kênh Thanh Toán</h4>
              <div id="repPaymentTable" class="overflow-x-auto border border-stone-100 rounded-2xl">
                <!-- Rendered via JS -->
              </div>
            </div>
            <div>
              <h4 class="font-bold text-stone-900 text-sm mb-3">Thống Kê Đơn Theo Trạng Thái</h4>
              <div id="repStatusTable" class="overflow-x-auto border border-stone-100 rounded-2xl">
                <!-- Rendered via JS -->
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ================= 12. VIEW: STAFF & RBAC ================= -->
      <section id="view-staff" class="hidden space-y-5">
        <div class="bg-white rounded-3xl border border-stone-200 shadow-sm overflow-hidden">
          <div class="p-5 sm:p-6 border-b border-stone-100 flex items-center justify-between">
            <div>
              <h2 class="text-lg font-bold text-stone-900 font-serif-title">Quản Lý Nhân Sự & Phân Quyền (RBAC)</h2>
              <p class="text-xs text-stone-400">Phân quyền chức năng cho Admin, Manager, Florist, Shipper và Thủ kho</p>
            </div>
            <button onclick="openStaffModal()" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl font-bold shadow-sm transition flex items-center space-x-1.5 text-xs">
              <i class="ph ph-user-plus text-base"></i>
              <span>Cấp Tài Khoản Mới</span>
            </button>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-stone-600">
              <thead class="bg-stone-50 text-[11px] text-stone-400 uppercase border-b border-stone-200/80">
                <tr>
                  <th class="py-3 px-4 font-semibold">Nhân Sự</th>
                  <th class="py-3 px-4 font-semibold">Email Đăng Nhập</th>
                  <th class="py-3 px-4 font-semibold">Số Điện Thoại</th>
                  <th class="py-3 px-4 font-semibold">Vai Trò (Role)</th>
                  <th class="py-3 px-4 font-semibold">Quyền Hạn Chức Năng</th>
                  <th class="py-3 px-4 font-semibold">Trạng Thái</th>
                  <th class="py-3 px-4 font-semibold text-right">Thao Tác</th>
                </tr>
              </thead>
              <tbody id="staffTableBody" class="divide-y divide-stone-100">
                <!-- Rendered via JS -->
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- ================= 13. VIEW: SETTINGS & SYSTEM LOGS ================= -->
      <section id="view-settings" class="hidden space-y-6">
        <!-- Store Settings Form -->
        <div class="bg-white p-5 sm:p-6 rounded-3xl border border-stone-200 shadow-sm space-y-5">
          <div class="border-b border-stone-100 pb-3 flex items-center justify-between">
            <div>
              <h2 class="text-lg font-bold text-stone-900 font-serif-title">Cấu Hình Thông Tin Cửa Hàng & Kênh Thanh Toán</h2>
              <p class="text-xs text-stone-400">Thông tin xuất hiện trên hóa đơn giao hoa và tài khoản nhận tiền</p>
            </div>
            <button onclick="saveStoreSettings()" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl font-bold shadow-sm transition text-xs flex items-center space-x-1">
              <i class="ph ph-floppy-disk"></i>
              <span>Lưu Cài Đặt</span>
            </button>
          </div>

          <form id="storeSettingsForm" onsubmit="event.preventDefault(); saveStoreSettings();" class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
            <div>
              <label class="block font-semibold text-stone-700 mb-1">Tên Cửa Hàng / Thương Hiệu</label>
              <input type="text" id="cfg_store_name" class="w-full bg-stone-50 border border-stone-300 rounded-xl px-3 py-2 text-stone-800 focus:ring-1 focus:ring-rose-500 focus:outline-none" />
            </div>
            <div>
              <label class="block font-semibold text-stone-700 mb-1">Hotline Chăm Sóc Khách Hàng</label>
              <input type="text" id="cfg_store_hotline" class="w-full bg-stone-50 border border-stone-300 rounded-xl px-3 py-2 text-stone-800 focus:ring-1 focus:ring-rose-500 focus:outline-none" />
            </div>
            <div class="sm:col-span-2">
              <label class="block font-semibold text-stone-700 mb-1">Địa Chỉ Xưởng Hoa / Showroom</label>
              <input type="text" id="cfg_store_address" class="w-full bg-stone-50 border border-stone-300 rounded-xl px-3 py-2 text-stone-800 focus:ring-1 focus:ring-rose-500 focus:outline-none" />
            </div>
            <div>
              <label class="block font-semibold text-stone-700 mb-1">Email Tiếp Nhận Đơn</label>
              <input type="email" id="cfg_store_email" class="w-full bg-stone-50 border border-stone-300 rounded-xl px-3 py-2 text-stone-800 focus:ring-1 focus:ring-rose-500 focus:outline-none" />
            </div>
            <div>
              <label class="block font-semibold text-stone-700 mb-1">Khung Giờ Hoạt Động</label>
              <input type="text" id="cfg_store_opening_hours" class="w-full bg-stone-50 border border-stone-300 rounded-xl px-3 py-2 text-stone-800 focus:ring-1 focus:ring-rose-500 focus:outline-none" />
            </div>
            <div>
              <label class="block font-semibold text-stone-700 mb-1">Ngân Hàng Nhận Tiền VietQR</label>
              <input type="text" id="cfg_vietqr_bank_id" placeholder="MB, VCB, ACB..." class="w-full bg-stone-50 border border-stone-300 rounded-xl px-3 py-2 text-stone-800 focus:ring-1 focus:ring-rose-500 focus:outline-none" />
            </div>
            <div>
              <label class="block font-semibold text-stone-700 mb-1">Số Tài Khoản VietQR</label>
              <input type="text" id="cfg_vietqr_account_no" class="w-full bg-stone-50 border border-stone-300 rounded-xl px-3 py-2 text-stone-800 focus:ring-1 focus:ring-rose-500 focus:outline-none" />
            </div>
            <div>
              <label class="block font-semibold text-stone-700 mb-1">Tên Chủ Tài Khoản Thụ Hưởng</label>
              <input type="text" id="cfg_vietqr_account_name" class="w-full bg-stone-50 border border-stone-300 rounded-xl px-3 py-2 text-stone-800 focus:ring-1 focus:ring-rose-500 focus:outline-none" />
            </div>
            <div>
              <label class="block font-semibold text-stone-700 mb-1">Số Điện Thoại Ví MoMo Business</label>
              <input type="text" id="cfg_momo_phone" class="w-full bg-stone-50 border border-stone-300 rounded-xl px-3 py-2 text-stone-800 focus:ring-1 focus:ring-rose-500 focus:outline-none" />
            </div>
          </form>
        </div>

        <!-- Audit Activity Logs -->
        <div class="bg-white rounded-3xl border border-stone-200 shadow-sm overflow-hidden">
          <div class="p-5 border-b border-stone-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
              <h3 class="font-bold text-stone-900 font-serif-title text-base">Nhật Ký Kiểm Toán Toàn Bộ Hệ Thống (Audit Trail)</h3>
              <p class="text-xs text-stone-400">Truy vết tất cả thao tác của quản trị viên và nhân viên kèm IP</p>
            </div>
            <div class="flex items-center space-x-2 text-xs">
              <select id="logModuleFilter" onchange="loadActivityLogs()" class="bg-stone-50 border border-stone-200 rounded-xl px-3 py-2 text-stone-700 focus:outline-none">
                <option value="all">Tất cả phân hệ</option>
                <option value="orders">Đơn hàng</option>
                <option value="payments">Thanh toán</option>
                <option value="products">Sản phẩm</option>
                <option value="materials">Nguyên vật liệu</option>
                <option value="staff">Nhân sự</option>
                <option value="settings">Cài đặt</option>
              </select>
              <button onclick="loadActivityLogs()" class="p-2 bg-stone-100 hover:bg-stone-200 rounded-xl text-stone-600 transition">
                <i class="ph ph-arrows-clockwise text-base"></i>
              </button>
            </div>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-stone-600">
              <thead class="bg-stone-50 text-[11px] text-stone-400 uppercase border-b border-stone-200/80">
                <tr>
                  <th class="py-3 px-4 font-semibold">Thời Gian</th>
                  <th class="py-3 px-4 font-semibold">Người Thực Hiện</th>
                  <th class="py-3 px-4 font-semibold">Phân Hệ</th>
                  <th class="py-3 px-4 font-semibold">Hành Động</th>
                  <th class="py-3 px-4 font-semibold">Chi Tiết Thao Tác</th>
                  <th class="py-3 px-4 font-semibold">Địa Chỉ IP</th>
                </tr>
              </thead>
              <tbody id="logsTableBody" class="divide-y divide-stone-100">
                <!-- Rendered via JS -->
              </tbody>
            </table>
          </div>
        </div>
      </section>

    </main>
  </div>

  <!-- ================= MODALS SECTION ================= -->

  <!-- MODAL: ORDER DETAILS & PRINT INVOICE -->
  <div id="orderDetailModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
    <div onclick="closeOrderDetailModal()" class="fixed inset-0 bg-stone-900/60 backdrop-blur-sm"></div>
    <div class="relative bg-white rounded-3xl max-w-2xl w-full p-6 shadow-2xl z-10 border border-stone-200 max-h-[90vh] overflow-y-auto">
      <div class="flex items-center justify-between pb-3 border-b border-stone-100 mb-4">
        <div>
          <h3 class="font-bold text-base text-stone-900 font-serif-title">Chi Tiết Đơn Hàng & Phiếu Giao Hoa</h3>
          <p class="text-xs text-stone-400" id="detailOrderCodeHeader">Mã đơn: --</p>
        </div>
        <div class="flex items-center space-x-2">
          <button onclick="printOrderInvoice()" class="px-3 py-1.5 bg-stone-900 hover:bg-black text-white rounded-xl text-xs font-semibold flex items-center space-x-1 transition">
            <i class="ph ph-printer"></i>
            <span>In Phiếu Giao</span>
          </button>
          <button onclick="closeOrderDetailModal()" class="w-8 h-8 rounded-full hover:bg-stone-100 flex items-center justify-center text-stone-400">
            <i class="ph ph-x"></i>
          </button>
        </div>
      </div>

      <div id="orderDetailContent" class="space-y-4 text-xs">
        <!-- Rendered via JS -->
      </div>
    </div>
  </div>

  <!-- PRINT INVOICE CONTAINER (Hidden until print) -->
  <div id="invoicePrintSection" class="hidden"></div>

  <!-- MODAL: UPDATE ORDER STATUS & PHOTO PROOF -->
  <div id="updateOrderModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
    <div onclick="closeUpdateOrderModal()" class="fixed inset-0 bg-stone-900/50 backdrop-blur-sm"></div>
    <div class="relative bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl z-10 border border-stone-200">
      <div class="flex items-center justify-between pb-3 border-b border-stone-100 mb-4">
        <div>
          <h3 class="font-bold text-base text-stone-900">Cập Nhật Trạng Thái & Ảnh Thành Phẩm</h3>
          <p class="text-xs text-stone-400" id="modalOrderCodeDisplay">Mã đơn: --</p>
        </div>
        <button onclick="closeUpdateOrderModal()" class="w-8 h-8 rounded-full hover:bg-stone-100 flex items-center justify-center text-stone-400">
          <i class="ph ph-x"></i>
        </button>
      </div>

      <form onsubmit="handleUpdateOrderSubmit(event)" class="space-y-4 text-xs">
        <input type="hidden" id="modalOrderId" />

        <div>
          <label class="block font-semibold text-stone-700 mb-1">Trạng Thái Đơn Hàng Mới:</label>
          <select id="modalOrderStatus" class="w-full bg-stone-50 border border-stone-300 rounded-xl px-3 py-2 text-stone-800 font-semibold focus:ring-1 focus:ring-rose-500 focus:outline-none">
            <option value="received">1. Đã tiếp nhận đơn</option>
            <option value="preparing">2. Đang cắm hoa tươi</option>
            <option value="photo_ready">3. Đã chụp ảnh duyệt (Photo Proof Ready)</option>
            <option value="delivering">4. Shipper đang giao hỏa tốc</option>
            <option value="completed">5. Giao hoa thành công (Hoàn tất)</option>
            <option value="cancelled">Hủy đơn hàng (Tự động hoàn kho & voucher)</option>
          </select>
        </div>

        <div>
          <label class="block font-semibold text-stone-700 mb-1">Link Ảnh Thành Phẩm Hoa (Photo Proof):</label>
          <input 
            type="url" 
            id="modalPhotoProof" 
            placeholder="https://... hoặc uploads/photos/..." 
            class="w-full bg-stone-50 border border-stone-300 rounded-xl px-3 py-2 text-stone-800 focus:ring-1 focus:ring-rose-500 focus:outline-none"
          />
          <span class="text-[11px] text-stone-400 mt-1 block">Ảnh này sẽ hiển thị cho khách tra cứu trên website trước khi shipper giao.</span>
        </div>

        <div>
          <label class="block font-semibold text-stone-700 mb-1">Ghi Chú Tiến Độ:</label>
          <input 
            type="text" 
            id="modalNote" 
            placeholder="Ví dụ: Hoa đã cắm xong, in băng rôn rõ đẹp..." 
            class="w-full bg-stone-50 border border-stone-300 rounded-xl px-3 py-2 text-stone-800 focus:ring-1 focus:ring-rose-500 focus:outline-none"
          />
        </div>

        <div class="pt-3 border-t border-stone-100 flex items-center justify-end space-x-2">
          <button type="button" onclick="closeUpdateOrderModal()" class="px-4 py-2 bg-stone-100 hover:bg-stone-200 text-stone-600 rounded-xl font-medium transition">
            Hủy
          </button>
          <button type="submit" id="modalSaveBtn" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl font-bold shadow transition">
            Lưu Thay Đổi
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL: QUALITY CHECK (QC) & PHOTO PROOF -->
  <div id="qcModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
    <div onclick="closeQCModal()" class="fixed inset-0 bg-stone-900/60 backdrop-blur-sm"></div>
    <div class="relative bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl z-10 border border-stone-200">
      <div class="flex items-center justify-between pb-3 border-b border-stone-100 mb-4">
        <div>
          <h3 class="font-bold text-base text-stone-900 font-serif-title">Kiểm Định Chất Lượng Thành Phẩm (QC)</h3>
          <p class="text-xs text-stone-400" id="qcOrderCodeDisplay">Đơn hàng: --</p>
        </div>
        <button onclick="closeQCModal()" class="w-8 h-8 rounded-full hover:bg-stone-100 flex items-center justify-center text-stone-400">
          <i class="ph ph-x"></i>
        </button>
      </div>

      <form onsubmit="handleQCSubmit(event)" class="space-y-4 text-xs">
        <input type="hidden" id="qcOrderId" />

        <div class="p-3 bg-rose-50/70 border border-rose-100 rounded-2xl space-y-2">
          <span class="font-bold text-rose-800 block text-xs">Checklist 5 Tiêu Chí Bắt Buộc Trước Khi Xuất Xưởng:</span>
          
          <label class="flex items-center space-x-2 cursor-pointer">
            <input type="checkbox" id="qc_fresh" checked class="w-4 h-4 rounded text-rose-600 focus:ring-rose-500" />
            <span class="text-stone-700 font-medium">1. Cành hoa tươi mới 100%, không dập cánh, đài hoa săn chắc.</span>
          </label>
          <label class="flex items-center space-x-2 cursor-pointer">
            <input type="checkbox" id="qc_color" checked class="w-4 h-4 rounded text-rose-600 focus:ring-rose-500" />
            <span class="text-stone-700 font-medium">2. Đúng tông màu & loài hoa yêu cầu như mô tả mẫu.</span>
          </label>
          <label class="flex items-center space-x-2 cursor-pointer">
            <input type="checkbox" id="qc_stems" checked class="w-4 h-4 rounded text-rose-600 focus:ring-rose-500" />
            <span class="text-stone-700 font-medium">3. Đủ số lượng bông / cành và đúng form dáng (bó/giỏ/hộp).</span>
          </label>
          <label class="flex items-center space-x-2 cursor-pointer">
            <input type="checkbox" id="qc_wrap" checked class="w-4 h-4 rounded text-rose-600 focus:ring-rose-500" />
            <span class="text-stone-700 font-medium">4. Giấy gói lụa & nơ ruy băng chuẩn phong cách thương hiệu.</span>
          </label>
          <label class="flex items-center space-x-2 cursor-pointer">
            <input type="checkbox" id="qc_card" checked class="w-4 h-4 rounded text-rose-600 focus:ring-rose-500" />
            <span class="text-stone-700 font-medium">5. Thiệp mỹ thuật / Băng rôn in đúng nội dung người gửi.</span>
          </label>
        </div>

        <div>
          <label class="block font-semibold text-stone-700 mb-1">Ảnh Chụp Thành Phẩm Thực Tế (Photo Proof URL):</label>
          <input type="url" id="qc_photo_url" required placeholder="https://... hoặc uploads/photos/..." class="w-full bg-stone-50 border border-stone-300 rounded-xl px-3 py-2 text-stone-800 focus:ring-1 focus:ring-rose-500 focus:outline-none" />
        </div>

        <div>
          <label class="block font-semibold text-stone-700 mb-1">Ghi Chú Kiểm Định:</label>
          <input type="text" id="qc_notes" placeholder="Hoa nở đẹp 80%, giao đúng giờ..." class="w-full bg-stone-50 border border-stone-300 rounded-xl px-3 py-2 text-stone-800 focus:ring-1 focus:ring-rose-500 focus:outline-none" />
        </div>

        <div class="pt-3 border-t border-stone-100 flex items-center justify-end space-x-2">
          <button type="button" onclick="closeQCModal()" class="px-4 py-2 bg-stone-100 hover:bg-stone-200 text-stone-600 rounded-xl font-medium transition">
            Hủy
          </button>
          <button type="submit" id="qcSubmitBtn" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl font-bold shadow transition">
            Duyệt Đạt QC & Sẵn Sàng Giao
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL: ADD / EDIT PRODUCT -->
  <div id="productModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
    <div onclick="closeProductModal()" class="fixed inset-0 bg-stone-900/60 backdrop-blur-sm"></div>
    <div class="relative bg-white rounded-3xl max-w-2xl w-full p-6 shadow-2xl z-10 border border-stone-200 max-h-[90vh] overflow-y-auto">
      <div class="flex items-center justify-between pb-3 border-b border-stone-100 mb-4">
        <div>
          <h3 id="productModalTitle" class="font-bold text-base text-stone-900 font-serif-title">Thêm Sản Phẩm Hoa Tươi Mới</h3>
          <p class="text-xs text-stone-400">Đầy đủ thuộc tính phong cách, kích thước, giá vốn và tồn kho</p>
        </div>
        <button onclick="closeProductModal()" class="w-8 h-8 rounded-full hover:bg-stone-100 flex items-center justify-center text-stone-400">
          <i class="ph ph-x"></i>
        </button>
      </div>

      <form onsubmit="handleProductSubmit(event)" class="space-y-4 text-xs">
        <input type="hidden" id="prodEditId" />

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block font-semibold text-stone-700 mb-1">Tên Sản Phẩm *</label>
            <input type="text" id="prodName" required placeholder="Bó Hoa Hồng Ecuador Đỏ Thắm..." class="w-full bg-stone-50 border border-stone-300 rounded-xl px-3 py-2 text-stone-800 focus:outline-none" />
          </div>
          <div>
            <label class="block font-semibold text-stone-700 mb-1">Mã SKU *</label>
            <input type="text" id="prodSku" required placeholder="FLW-ROSE-01" class="w-full bg-stone-50 border border-stone-300 rounded-xl px-3 py-2 text-stone-800 focus:outline-none" />
          </div>
          <div>
            <label class="block font-semibold text-stone-700 mb-1">Danh Mục Hoa *</label>
            <select id="prodCategoryId" required class="w-full bg-stone-50 border border-stone-300 rounded-xl px-3 py-2 text-stone-800 focus:outline-none">
              <!-- Rendered via JS -->
            </select>
          </div>
          <div>
            <label class="block font-semibold text-stone-700 mb-1">Dáng Cắm Sản Phẩm</label>
            <select id="prodProductForm" class="w-full bg-stone-50 border border-stone-300 rounded-xl px-3 py-2 text-stone-800 focus:outline-none">
              <option value="bo">Bó hoa tươi (Round/Long Bouquet)</option>
              <option value="gio">Giỏ hoa nghệ thuật (Basket)</option>
              <option value="hop">Hộp hoa / Hộp mica cao cấp (Box)</option>
              <option value="ke">Kệ hoa khai trương / chia buồn (Stand)</option>
              <option value="lang">Lẵng hoa / Bình hoa để bàn</option>
              <option value="chau">Chậu hoa (Lan hồ điệp / Cây cảnh)</option>
            </select>
          </div>
          <div>
            <label class="block font-semibold text-stone-700 mb-1">Loài Hoa Chủ Đạo</label>
            <input type="text" id="prodPrimaryFlower" placeholder="Hoa hồng đỏ Ecuador, Hướng dương..." class="w-full bg-stone-50 border border-stone-300 rounded-xl px-3 py-2 text-stone-800 focus:outline-none" />
          </div>
          <div>
            <label class="block font-semibold text-stone-700 mb-1">Hoa & Lá Phụ</label>
            <input type="text" id="prodSecondaryFlower" placeholder="Baby trắng, lá bạc nhập khẩu, cúc tana..." class="w-full bg-stone-50 border border-stone-300 rounded-xl px-3 py-2 text-stone-800 focus:outline-none" />
          </div>
          <div>
            <label class="block font-semibold text-stone-700 mb-1">Số Lượng Cành (Nếu đếm)</label>
            <input type="number" id="prodStemCount" min="0" placeholder="19 (để trống nếu kệ/giỏ tổng hợp)" class="w-full bg-stone-50 border border-stone-300 rounded-xl px-3 py-2 text-stone-800 focus:outline-none" />
          </div>
          <div>
            <label class="block font-semibold text-stone-700 mb-1">Tone Màu & Mã Màu</label>
            <div class="grid grid-cols-2 gap-2">
              <select id="prodColor" class="bg-stone-50 border border-stone-300 rounded-xl px-3 py-2 text-stone-800 focus:outline-none">
                <option value="do">Đỏ</option>
                <option value="hong">Hồng</option>
                <option value="vang">Vàng</option>
                <option value="cam">Cam</option>
                <option value="trang">Trắng</option>
                <option value="tim">Tím</option>
                <option value="xanh">Xanh</option>
                <option value="pastel">Pastel</option>
              </select>
              <input type="text" id="prodColorName" placeholder="Tên màu: Đỏ nhung, Hồng pastel..." class="bg-stone-50 border border-stone-300 rounded-xl px-3 py-2 text-stone-800 focus:outline-none" />
            </div>
          </div>
          <div>
            <label class="block font-semibold text-stone-700 mb-1">Giá Bán Niêm Yết (VND) *</label>
            <input type="number" id="prodPrice" required min="0" step="1000" placeholder="650000" class="w-full bg-stone-50 border border-stone-300 rounded-xl px-3 py-2 text-stone-800 focus:outline-none" />
          </div>
          <div>
            <label class="block font-semibold text-stone-700 mb-1">Giá Vốn Ước Tính (Cost VND) *</label>
            <input type="number" id="prodCostPrice" required min="0" step="1000" placeholder="320000" class="w-full bg-stone-50 border border-stone-300 rounded-xl px-3 py-2 text-stone-800 focus:outline-none" />
          </div>
          <div>
            <label class="block font-semibold text-stone-700 mb-1">Giá Gốc Trước Giảm (Sale Price nếu có)</label>
            <input type="number" id="prodSalePrice" min="0" step="1000" placeholder="590000" class="w-full bg-stone-50 border border-stone-300 rounded-xl px-3 py-2 text-stone-800 focus:outline-none" />
          </div>
          <div>
            <label class="block font-semibold text-stone-700 mb-1">Số Lượng Tồn Sẵn Bán *</label>
            <input type="number" id="prodStockQty" required min="0" value="50" class="w-full bg-stone-50 border border-stone-300 rounded-xl px-3 py-2 text-stone-800 focus:outline-none" />
          </div>
          <div>
            <label class="block font-semibold text-stone-700 mb-1">Kích Thước Thiết Kế</label>
            <input type="text" id="prodSize" placeholder="Cao 55cm x Rộng 40cm" class="w-full bg-stone-50 border border-stone-300 rounded-xl px-3 py-2 text-stone-800 focus:outline-none" />
          </div>
          <div>
            <label class="block font-semibold text-stone-700 mb-1">Quy Cách Đóng Gói / Giấy Gói</label>
            <input type="text" id="prodWrappingPaper" placeholder="Giấy xi măng vintage, nơ ruy băng lụa cao cấp..." class="w-full bg-stone-50 border border-stone-300 rounded-xl px-3 py-2 text-stone-800 focus:outline-none" />
          </div>
        </div>

        <div>
          <label class="block font-semibold text-stone-700 mb-1">Đường Dẫn Ảnh Đại Diện (Thumbnail URL) *</label>
          <input type="text" id="prodThumbnail" required placeholder="https://... hoặc img/products/..." class="w-full bg-stone-50 border border-stone-300 rounded-xl px-3 py-2 text-stone-800 focus:outline-none" />
        </div>

        <div>
          <label class="block font-semibold text-stone-700 mb-1">Mô Tả Ngắn Tinh Tế (Short Description)</label>
          <input type="text" id="prodShortDesc" placeholder="Mẫu hoa tinh tế dành tặng người thương dịp sinh nhật..." class="w-full bg-stone-50 border border-stone-300 rounded-xl px-3 py-2 text-stone-800 focus:outline-none" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block font-semibold text-stone-700 mb-1">Ý Nghĩa & Thông Điệp Bó Hoa</label>
            <textarea id="prodMeaning" rows="2" placeholder="Bày tỏ tình yêu nồng nàn và lòng chân thành..." class="w-full bg-stone-50 border border-stone-300 rounded-xl p-2.5 text-stone-800 focus:outline-none"></textarea>
          </div>
          <div>
            <label class="block font-semibold text-stone-700 mb-1">Hướng Dẫn Chăm Sóc Hoa Tươi</label>
            <textarea id="prodCareGuide" rows="2" placeholder="Cắt gốc vát 45 độ, thay nước sạch mỗi ngày, tránh gió máy lạnh trực tiếp..." class="w-full bg-stone-50 border border-stone-300 rounded-xl p-2.5 text-stone-800 focus:outline-none"></textarea>
          </div>
        </div>

        <div class="flex items-center space-x-6 pt-2">
          <label class="flex items-center space-x-2 cursor-pointer">
            <input type="checkbox" id="prodIsActive" checked class="w-4 h-4 rounded text-rose-600 focus:ring-rose-500" />
            <span class="font-medium text-stone-700">Hiển thị bán trên website</span>
          </label>
          <label class="flex items-center space-x-2 cursor-pointer">
            <input type="checkbox" id="prodIsFeatured" checked class="w-4 h-4 rounded text-rose-600 focus:ring-rose-500" />
            <span class="font-medium text-stone-700">Mẫu hoa nổi bật trang chủ</span>
          </label>
        </div>

        <div class="pt-3 border-t border-stone-100 flex items-center justify-end space-x-2">
          <button type="button" onclick="closeProductModal()" class="px-4 py-2 bg-stone-100 hover:bg-stone-200 text-stone-600 rounded-xl font-medium transition">
            Hủy
          </button>
          <button type="submit" id="prodSaveBtn" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl font-bold shadow transition">
            Lưu Sản Phẩm
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL: ADD / EDIT CATEGORY -->
  <div id="categoryModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
    <div onclick="closeCategoryModal()" class="fixed inset-0 bg-stone-900/60 backdrop-blur-sm"></div>
    <div class="relative bg-white rounded-3xl max-w-2xl w-full p-6 shadow-2xl z-10 border border-stone-200 max-h-[90vh] overflow-y-auto">
      <div class="flex items-center justify-between pb-3 border-b border-stone-100 mb-4">
        <div>
          <h3 id="catModalTitle" class="font-bold text-base text-stone-900 font-serif-title">Thêm Danh Mục Mới</h3>
          <p class="text-xs text-stone-400">Thông tin chuẩn SEO, phân cấp và hiển thị danh mục sản phẩm</p>
        </div>
        <button onclick="closeCategoryModal()" class="w-8 h-8 rounded-full hover:bg-stone-100 flex items-center justify-center text-stone-400">
          <i class="ph ph-x"></i>
        </button>
      </div>

      <div id="catFormAlert" class="hidden mb-3 p-3 rounded-xl text-xs font-semibold"></div>

      <form onsubmit="handleCategorySubmit(event)" class="space-y-4 text-xs">
        <input type="hidden" id="catEditId" />

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block font-semibold text-stone-700 mb-1">Tên Danh Mục <span class="text-rose-600">*</span></label>
            <input type="text" id="catName" required oninput="onCategoryNameInput()" placeholder="Ví dụ: Hoa Khai Trương" class="w-full bg-stone-50 border border-stone-300 rounded-xl px-3 py-2 text-stone-800 focus:ring-1 focus:ring-rose-500 focus:outline-none" />
          </div>

          <div>
            <label class="block font-semibold text-stone-700 mb-1">Mã Danh Mục</label>
            <input type="text" id="catCode" placeholder="Tự động tạo nếu để trống (VD: DM-HKT-01)" class="w-full uppercase font-mono bg-stone-50 border border-stone-300 rounded-xl px-3 py-2 text-stone-800 focus:ring-1 focus:ring-rose-500 focus:outline-none" />
          </div>

          <div>
            <label class="block font-semibold text-stone-700 mb-1">Slug (URL SEO) <span class="text-rose-600">*</span></label>
            <input type="text" id="catSlug" required oninput="this.dataset.manual='1'" placeholder="hoa-khai-truong" class="w-full font-mono bg-stone-50 border border-stone-300 rounded-xl px-3 py-2 text-stone-800 focus:ring-1 focus:ring-rose-500 focus:outline-none" />
          </div>

          <div>
            <label class="block font-semibold text-stone-700 mb-1">Danh Mục Cha (Phân Cấp)</label>
            <select id="catParentId" class="w-full bg-stone-50 border border-stone-300 rounded-xl px-3 py-2 text-stone-800 focus:ring-1 focus:ring-rose-500 focus:outline-none">
              <option value="">-- Danh mục gốc (Không có cha) --</option>
            </select>
          </div>

          <div>
            <label class="block font-semibold text-stone-700 mb-1">Thứ Tự Hiển Thị</label>
            <input type="number" id="catDisplayOrder" min="0" value="0" class="w-full bg-stone-50 border border-stone-300 rounded-xl px-3 py-2 text-stone-800 focus:outline-none" />
          </div>

          <div>
            <label class="block font-semibold text-stone-700 mb-1">Trạng Thái Hiển Thị</label>
            <select id="catStatus" class="w-full bg-stone-50 border border-stone-300 rounded-xl px-3 py-2 text-stone-800 font-semibold focus:outline-none">
              <option value="active">Hiển thị (Hoạt động trên web)</option>
              <option value="hidden">Ẩn (Không hiện bên Client)</option>
            </select>
          </div>

          <div>
            <label class="block font-semibold text-stone-700 mb-1">Icon (Phosphor Class)</label>
            <input type="text" id="catIcon" value="ph-flower-lotus" class="w-full font-mono bg-stone-50 border border-stone-300 rounded-xl px-3 py-2 text-stone-800 focus:outline-none" />
          </div>

          <div>
            <label class="block font-semibold text-stone-700 mb-1">Huy Hiệu (Badge)</label>
            <input type="text" id="catBadge" placeholder="Mới, Hot, VIP..." class="w-full bg-stone-50 border border-stone-300 rounded-xl px-3 py-2 text-stone-800 focus:outline-none" />
          </div>
        </div>

        <div>
          <label class="block font-semibold text-stone-700 mb-1">Hình Ảnh Đại Diện (URL)</label>
          <div class="flex items-center gap-3">
            <input type="text" id="catImage" oninput="previewCategoryImage(this.value)" placeholder="https://..." class="flex-1 bg-stone-50 border border-stone-300 rounded-xl px-3 py-2 text-stone-800 focus:outline-none" />
            <img id="catImagePreview" src="" alt="Preview" class="hidden w-12 h-12 rounded-xl object-cover border border-stone-200 flex-shrink-0" />
          </div>
        </div>

        <div>
          <label class="block font-semibold text-stone-700 mb-1">Mô Tả Ngắn</label>
          <textarea id="catDescription" rows="2" placeholder="Mô tả đặc trưng của danh mục hoa..." class="w-full bg-stone-50 border border-stone-300 rounded-xl p-2.5 text-stone-800 focus:ring-1 focus:ring-rose-500 focus:outline-none"></textarea>
        </div>

        <div class="pt-3 border-t border-stone-100 flex items-center justify-end space-x-2">
          <button type="button" onclick="closeCategoryModal()" class="px-4 py-2 bg-stone-100 hover:bg-stone-200 text-stone-600 rounded-xl font-medium transition">Hủy</button>
          <button type="submit" id="catSaveBtn" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl font-bold shadow transition">Lưu Danh Mục</button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL: CATEGORY DETAIL -->
  <div id="categoryDetailModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
    <div onclick="closeCategoryDetailModal()" class="fixed inset-0 bg-stone-900/60 backdrop-blur-sm"></div>
    <div class="relative bg-white rounded-3xl max-w-3xl w-full p-6 shadow-2xl z-10 border border-stone-200 max-h-[90vh] overflow-y-auto">
      <div class="flex items-center justify-between pb-3 border-b border-stone-100 mb-4">
        <div>
          <h3 class="font-bold text-base text-stone-900 font-serif-title">Chi Tiết Danh Mục Sản Phẩm</h3>
          <p class="text-xs text-stone-400" id="catDetailSubtitle">--</p>
        </div>
        <button onclick="closeCategoryDetailModal()" class="w-8 h-8 rounded-full hover:bg-stone-100 flex items-center justify-center text-stone-400">
          <i class="ph ph-x"></i>
        </button>
      </div>
      <div id="categoryDetailBody" class="space-y-4 text-xs"></div>
    </div>
  </div>

  <!-- MODAL: SAFE DELETE CATEGORY -->
  <div id="categoryDeleteModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
    <div onclick="closeCategoryDeleteModal()" class="fixed inset-0 bg-stone-900/60 backdrop-blur-sm"></div>
    <div class="relative bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl z-10 border border-stone-200">
      <div class="flex items-center justify-between pb-3 border-b border-stone-100 mb-4">
        <div>
          <h3 class="font-bold text-base text-stone-900 font-serif-title">Xác Nhận Xóa Danh Mục</h3>
          <p class="text-xs text-stone-400" id="catDeleteTitle">--</p>
        </div>
        <button onclick="closeCategoryDeleteModal()" class="w-8 h-8 rounded-full hover:bg-stone-100 flex items-center justify-center text-stone-400">
          <i class="ph ph-x"></i>
        </button>
      </div>
      <div id="catDeleteBody" class="space-y-4 text-xs"></div>
    </div>
  </div>

  <!-- MODAL: ADD / EDIT MATERIAL -->
  <div id="materialModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
    <div onclick="closeMaterialModal()" class="fixed inset-0 bg-stone-900/60 backdrop-blur-sm"></div>
    <div class="relative bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl z-10 border border-stone-200">
      <div class="flex items-center justify-between pb-3 border-b border-stone-100 mb-4">
        <div>
          <h3 id="matModalTitle" class="font-bold text-base text-stone-900 font-serif-title">Thêm Nguyên Vật Liệu Hoa Mới</h3>
          <p class="text-xs text-stone-400">Kiểm soát tồn kho hoa cành và phụ liệu xưởng</p>
        </div>
        <button onclick="closeMaterialModal()" class="w-8 h-8 rounded-full hover:bg-stone-100 flex items-center justify-center text-stone-400">
          <i class="ph ph-x"></i>
        </button>
      </div>

      <form onsubmit="handleMaterialSubmit(event)" class="space-y-4 text-xs">
        <input type="hidden" id="matEditId" />

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block font-semibold text-stone-700 mb-1">Tên Nguyên Liệu / Hoa *</label>
            <input type="text" id="matName" required placeholder="Hoa Hồng Đỏ Ecuador..." class="w-full bg-stone-50 border border-stone-300 rounded-xl px-3 py-2 text-stone-800 focus:outline-none" />
          </div>
          <div>
            <label class="block font-semibold text-stone-700 mb-1">Mã SKU *</label>
            <input type="text" id="matSku" placeholder="MAT-HOA-01" class="w-full bg-stone-50 border border-stone-300 rounded-xl px-3 py-2 text-stone-800 focus:outline-none" />
          </div>
          <div>
            <label class="block font-semibold text-stone-700 mb-1">Nhóm Vật Liệu *</label>
            <select id="matCategory" class="w-full bg-stone-50 border border-stone-300 rounded-xl px-3 py-2 text-stone-800 focus:outline-none">
              <option value="flower_fresh">Hoa tươi cắt cành</option>
              <option value="wrapping_paper">Giấy gói lụa Hàn Quốc</option>
              <option value="ribbon">Ruy băng voan / lụa</option>
              <option value="basket_vase">Giỏ gỗ / Bình cắm</option>
              <option value="accessories">Xốp Oasis / Phụ kiện</option>
            </select>
          </div>
          <div>
            <label class="block font-semibold text-stone-700 mb-1">Đơn Vị Tính *</label>
            <input type="text" id="matUnit" required placeholder="Cành, Bó, Cuộn, Tờ..." class="w-full bg-stone-50 border border-stone-300 rounded-xl px-3 py-2 text-stone-800 focus:outline-none" />
          </div>
          <div>
            <label class="block font-semibold text-stone-700 mb-1">Số Lượng Tồn Ban Đầu *</label>
            <input type="number" id="matQty" required min="0" value="50" class="w-full bg-stone-50 border border-stone-300 rounded-xl px-3 py-2 text-stone-800 focus:outline-none" />
          </div>
          <div>
            <label class="block font-semibold text-stone-700 mb-1">Ngưỡng Báo Thiếu Hàng *</label>
            <input type="number" id="matMinAlert" required min="1" value="10" class="w-full bg-stone-50 border border-stone-300 rounded-xl px-3 py-2 text-stone-800 focus:outline-none" />
          </div>
          <div>
            <label class="block font-semibold text-stone-700 mb-1">Đơn Giá Vốn Nhập (VND) *</label>
            <input type="number" id="matCost" required min="0" step="500" placeholder="18000" class="w-full bg-stone-50 border border-stone-300 rounded-xl px-3 py-2 text-stone-800 focus:outline-none" />
          </div>
          <div>
            <label class="block font-semibold text-stone-700 mb-1">Nhà Cung Cấp</label>
            <input type="text" id="matSupplier" placeholder="Dalay Farm Đà Lạt..." class="w-full bg-stone-50 border border-stone-300 rounded-xl px-3 py-2 text-stone-800 focus:outline-none" />
          </div>
        </div>

        <div class="pt-3 border-t border-stone-100 flex items-center justify-end space-x-2">
          <button type="button" onclick="closeMaterialModal()" class="px-4 py-2 bg-stone-100 hover:bg-stone-200 text-stone-600 rounded-xl font-medium transition">
            Hủy
          </button>
          <button type="submit" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl font-bold shadow transition">
            Lưu Vật Tư
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL: ADJUST MATERIAL STOCK -->
  <div id="materialAdjustModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
    <div onclick="closeMaterialAdjustModal()" class="fixed inset-0 bg-stone-900/60 backdrop-blur-sm"></div>
    <div class="relative bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl z-10 border border-stone-200">
      <div class="flex items-center justify-between pb-3 border-b border-stone-100 mb-4">
        <div>
          <h3 class="font-bold text-base text-stone-900 font-serif-title">Điều Chỉnh Tồn Kho Vật Tư</h3>
          <p class="text-xs text-stone-400" id="matAdjustNameDisplay">Vật tư: --</p>
        </div>
        <button onclick="closeMaterialAdjustModal()" class="w-8 h-8 rounded-full hover:bg-stone-100 flex items-center justify-center text-stone-400">
          <i class="ph ph-x"></i>
        </button>
      </div>

      <form onsubmit="handleMaterialAdjustSubmit(event)" class="space-y-4 text-xs">
        <input type="hidden" id="matAdjustId" />

        <div>
          <label class="block font-semibold text-stone-700 mb-1">Số Lượng Thay Đổi (+ Nhập Thêm, - Xuất Hỏng):</label>
          <input type="number" id="matAdjustQty" required placeholder="Ví dụ: 50 hoặc -5" class="w-full bg-stone-50 border border-stone-300 rounded-xl px-3 py-2 text-stone-800 focus:outline-none" />
        </div>

        <div>
          <label class="block font-semibold text-stone-700 mb-1">Lý Do Điều Chỉnh:</label>
          <select id="matAdjustReason" class="w-full bg-stone-50 border border-stone-300 rounded-xl px-3 py-2 text-stone-800 focus:outline-none">
            <option value="Nhập thêm lô hoa mới">Nhập thêm lô hoa mới từ vườn</option>
            <option value="Hao hụt, gãy cành trong vận chuyển">Hao hụt / gãy cành khi vận chuyển</option>
            <option value="Kiểm kê đối soát định kỳ">Kiểm kê định kỳ xưởng hoa</option>
          </select>
        </div>

        <div class="pt-3 border-t border-stone-100 flex items-center justify-end space-x-2">
          <button type="button" onclick="closeMaterialAdjustModal()" class="px-4 py-2 bg-stone-100 hover:bg-stone-200 text-stone-600 rounded-xl font-medium transition">
            Hủy
          </button>
          <button type="submit" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl font-bold shadow transition">
            Xác Nhận Cập Nhật
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL: ADD / EDIT PROMOTION -->
  <div id="promotionModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
    <div onclick="closePromotionModal()" class="fixed inset-0 bg-stone-900/60 backdrop-blur-sm"></div>
    <div class="relative bg-white rounded-3xl max-w-3xl w-full p-6 shadow-2xl z-10 border border-stone-200 max-h-[92vh] overflow-y-auto">
      <div class="flex items-center justify-between pb-3 border-b border-stone-100 mb-4">
        <div>
          <h3 id="promoModalTitle" class="font-bold text-base text-stone-900 font-serif-title">Tạo Chương Trình Khuyến Mãi</h3>
          <p class="text-xs text-stone-400">Thiết lập điều kiện giảm giá, lịch chạy tự động (Asia/Ho_Chi_Minh) & phạm vi áp dụng</p>
        </div>
        <button onclick="closePromotionModal()" class="w-8 h-8 rounded-full hover:bg-stone-100 flex items-center justify-center text-stone-400">
          <i class="ph ph-x"></i>
        </button>
      </div>

      <div id="promoFormAlert" class="hidden mb-3 p-3 rounded-xl text-xs font-semibold"></div>
      <div id="promoLockNotice" class="hidden mb-3 p-3 rounded-xl text-xs bg-amber-50 border border-amber-200 text-amber-800">
        <i class="ph ph-lock-key mr-1"></i> Chương trình đã phát sinh đơn hàng áp dụng nên mã gốc và giá trị giảm được khóa để bảo toàn dữ liệu đối soát. Bạn vẫn có thể sửa tên, mô tả, gia hạn thời gian hoặc tăng số lượt sử dụng.
      </div>

      <form onsubmit="handlePromotionSubmit(event)" class="space-y-5 text-xs">
        <input type="hidden" id="promoEditId" />

        <!-- Nhóm 1: Thông tin chiến dịch & Mã -->
        <div class="p-4 rounded-2xl bg-stone-50/80 border border-stone-200/80 space-y-3">
          <div class="font-bold text-stone-800 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
            <i class="ph ph-megaphone text-rose-600 text-sm"></i>
            <span>1. Thông Tin Chương Trình & Mã Khuyến Mãi</span>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="sm:col-span-2">
              <label class="block font-semibold text-stone-700 mb-1">Tên Chương Trình <span class="text-rose-600">*</span></label>
              <input type="text" id="promoName" required placeholder="Ví dụ: Tuần Lễ Hoa Khai Trương / Sinh Nhật Tháng 11..." class="w-full bg-white border border-stone-300 rounded-xl px-3 py-2 text-stone-800 focus:ring-1 focus:ring-rose-500 focus:outline-none" />
            </div>

            <div id="promoModeWrap">
              <label class="block font-semibold text-stone-700 mb-1">Hình Thức Mã</label>
              <select id="promoCodeMode" onchange="onPromoCodeModeChange()" class="w-full bg-white border border-stone-300 rounded-xl px-3 py-2 text-stone-800 focus:outline-none">
                <option value="single">Một mã chung cho chiến dịch</option>
                <option value="batch">Tạo nhiều mã riêng biệt ngẫu nhiên</option>
              </select>
            </div>

            <div id="promoBatchCountWrap" class="hidden">
              <label class="block font-semibold text-stone-700 mb-1">Số Lượng Mã Riêng (2–50 mã)</label>
              <input type="number" id="promoBatchCount" min="2" max="50" value="5" class="w-full bg-white border border-stone-300 rounded-xl px-3 py-2 text-stone-800 font-mono focus:outline-none" />
            </div>

            <div>
              <label class="block font-semibold text-stone-700 mb-1">Tiền Tố Mã (Prefix)</label>
              <div class="flex gap-2">
                <input type="text" id="promoPrefix" value="BLOOM" maxlength="12" placeholder="BLOOM, FLOWER..." class="w-32 uppercase font-mono bg-white border border-stone-300 rounded-xl px-3 py-2 text-stone-800 focus:outline-none" />
                <button type="button" id="btnGenPromoCode" onclick="generatePromotionCode()" class="flex-1 px-3 py-2 bg-stone-900 hover:bg-black text-white rounded-xl font-semibold transition flex items-center justify-center gap-1">
                  <i class="ph ph-sparkle"></i>
                  <span>Tạo Mã Tự Động</span>
                </button>
              </div>
            </div>

            <div id="promoSingleCodeWrap">
              <label class="block font-semibold text-stone-700 mb-1">Mã Khuyến Mãi (Code) <span class="text-rose-600">*</span></label>
              <input type="text" id="promoCode" oninput="checkPromoCodeDuplicate()" placeholder="BLOOM10, FLOWER20..." class="w-full uppercase font-mono bg-white border border-stone-300 rounded-xl px-3 py-2 text-stone-800 font-bold focus:ring-1 focus:ring-rose-500 focus:outline-none" />
              <span id="promoCodeCheckMsg" class="text-[11px] mt-1 block text-stone-400">Có thể nhập thủ công hoặc bấm "Tạo Mã Tự Động".</span>
            </div>

            <div class="sm:col-span-2">
              <label class="block font-semibold text-stone-700 mb-1">Mô Tả Chương Trình</label>
              <textarea id="promoDesc" rows="2" placeholder="Nội dung hiển thị cho khách hàng về ưu đãi và điều kiện..." class="w-full bg-white border border-stone-300 rounded-xl p-2.5 text-stone-800 focus:outline-none"></textarea>
            </div>
          </div>
        </div>

        <!-- Nhóm 2: Loại giảm giá & Giới hạn tiền -->
        <div class="p-4 rounded-2xl bg-stone-50/80 border border-stone-200/80 space-y-3">
          <div class="font-bold text-stone-800 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
            <i class="ph ph-percent text-rose-600 text-sm"></i>
            <span>2. Mức Giảm Giá & Giới Hạn Đơn Hàng</span>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
              <label class="block font-semibold text-stone-700 mb-1">Loại Giảm Giá <span class="text-rose-600">*</span></label>
              <select id="promoDiscountType" onchange="onPromoDiscountTypeChange()" class="w-full bg-white border border-stone-300 rounded-xl px-3 py-2 text-stone-800 font-semibold focus:outline-none">
                <option value="percentage">Giảm theo phần trăm (%)</option>
                <option value="fixed_amount">Giảm số tiền cố định (VNĐ)</option>
              </select>
            </div>

            <div>
              <label class="block font-semibold text-stone-700 mb-1">Giá Trị Giảm <span class="text-rose-600">*</span></label>
              <div class="relative flex items-center">
                <input type="number" id="promoDiscountValue" required min="1" step="any" placeholder="10" class="w-full bg-white border border-stone-300 rounded-xl px-3 py-2 pr-14 text-stone-900 font-mono font-bold focus:outline-none" />
                <span id="promoValueUnit" class="absolute right-3 font-bold text-rose-600 text-[11px] bg-rose-50 px-2 py-0.5 rounded">%</span>
              </div>
            </div>

            <div id="promoMaxDiscountWrap">
              <label class="block font-semibold text-stone-700 mb-1">Giảm Tối Đa (Khi dùng %)</label>
              <div class="relative flex items-center">
                <input type="number" id="promoMaxDiscount" min="0" step="1000" placeholder="100000" class="w-full bg-white border border-stone-300 rounded-xl px-3 py-2 pr-14 text-stone-800 font-mono focus:outline-none" />
                <span class="absolute right-3 text-stone-400 text-[11px]">VNĐ</span>
              </div>
            </div>

            <div>
              <label class="block font-semibold text-stone-700 mb-1">Giá Trị Đơn Tối Thiểu</label>
              <div class="relative flex items-center">
                <input type="number" id="promoMinOrder" min="0" step="10000" value="0" class="w-full bg-white border border-stone-300 rounded-xl px-3 py-2 pr-14 text-stone-800 font-mono focus:outline-none" />
                <span class="absolute right-3 text-stone-400 text-[11px]">VNĐ</span>
              </div>
            </div>

            <div>
              <label class="block font-semibold text-stone-700 mb-1">Giảm Tối Đa Mỗi Đơn</label>
              <div class="relative flex items-center">
                <input type="number" id="promoMaxPerOrder" min="0" step="1000" placeholder="Không giới hạn" class="w-full bg-white border border-stone-300 rounded-xl px-3 py-2 pr-14 text-stone-800 font-mono focus:outline-none" />
                <span class="absolute right-3 text-stone-400 text-[11px]">VNĐ</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Nhóm 3: Lượt dùng & Lịch chạy -->
        <div class="p-4 rounded-2xl bg-stone-50/80 border border-stone-200/80 space-y-3">
          <div class="font-bold text-stone-800 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
            <i class="ph ph-clock text-rose-600 text-sm"></i>
            <span>3. Lượt Sử Dụng, Hẹn Giờ & Trạng Thái (GMT+7 Việt Nam)</span>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
              <label class="block font-semibold text-stone-700 mb-1">Tổng Số Lượt Sử Dụng</label>
              <input type="number" id="promoUsageLimit" min="0" value="100" placeholder="0 = Không giới hạn" class="w-full bg-white border border-stone-300 rounded-xl px-3 py-2 text-stone-800 font-mono focus:outline-none" />
              <span class="text-[10px] text-stone-400">Nhập 0 nếu không giới hạn tổng lượt.</span>
            </div>

            <div>
              <label class="block font-semibold text-stone-700 mb-1">Số Lượt Mỗi Khách</label>
              <input type="number" id="promoPerUserLimit" min="0" value="1" class="w-full bg-white border border-stone-300 rounded-xl px-3 py-2 text-stone-800 font-mono focus:outline-none" />
              <span class="text-[10px] text-stone-400">Mỗi tài khoản / SĐT (0 = không giới hạn).</span>
            </div>

            <div>
              <label class="block font-semibold text-stone-700 mb-1">Trạng Thái Phát Hành</label>
              <select id="promoStatus" class="w-full bg-white border border-stone-300 rounded-xl px-3 py-2 text-stone-800 font-semibold focus:outline-none">
                <option value="active">Kích hoạt (Tự lên lịch / Đang diễn ra)</option>
                <option value="draft">Lưu Nháp (Chưa cho áp dụng)</option>
                <option value="paused">Tạm dừng</option>
              </select>
            </div>

            <div class="sm:col-span-1">
              <label class="block font-semibold text-stone-700 mb-1">Ngày Giờ Bắt Đầu <span class="text-rose-600">*</span></label>
              <input type="datetime-local" id="promoStartAt" required class="w-full bg-white border border-stone-300 rounded-xl px-3 py-2 text-stone-800 font-mono focus:outline-none" />
            </div>

            <div class="sm:col-span-1">
              <label class="block font-semibold text-stone-700 mb-1">Ngày Giờ Kết Thúc <span class="text-rose-600">*</span></label>
              <input type="datetime-local" id="promoEndAt" required class="w-full bg-white border border-stone-300 rounded-xl px-3 py-2 text-stone-800 font-mono focus:outline-none" />
            </div>
          </div>
        </div>

        <!-- Nhóm 4: Phạm vi & Đối tượng -->
        <div class="p-4 rounded-2xl bg-stone-50/80 border border-stone-200/80 space-y-3">
          <div class="font-bold text-stone-800 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
            <i class="ph ph-funnel text-rose-600 text-sm"></i>
            <span>4. Phạm Vi Sản Phẩm & Điều Kiện Khách Hàng</span>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block font-semibold text-stone-700 mb-1">Phạm Vi Áp Dụng</label>
              <select id="promoScopeType" onchange="onPromoScopeTypeChange()" class="w-full bg-white border border-stone-300 rounded-xl px-3 py-2 text-stone-800 font-semibold focus:outline-none">
                <option value="all">Toàn bộ sản phẩm</option>
                <option value="category">Chỉ áp dụng cho danh mục cụ thể</option>
                <option value="product">Chỉ áp dụng cho sản phẩm cụ thể</option>
              </select>
            </div>

            <div>
              <label class="block font-semibold text-stone-700 mb-1">Đối Tượng Khách Hàng</label>
              <select id="promoCustomerScope" class="w-full bg-white border border-stone-300 rounded-xl px-3 py-2 text-stone-800 font-semibold focus:outline-none">
                <option value="all">Tất cả khách hàng</option>
                <option value="new_customer">Chỉ khách hàng mới (Đơn đầu tiên)</option>
                <option value="vip">Chỉ khách hàng VIP (Từ 5 đơn / 3tr+)</option>
              </select>
            </div>
          </div>

          <div id="promoCategorySelectorWrap" class="hidden pt-2">
            <label class="block font-semibold text-stone-700 mb-1.5">Chọn Các Danh Mục Được Áp Dụng:</label>
            <div id="promoCategoryChecklist" class="grid grid-cols-2 sm:grid-cols-3 gap-2 max-h-40 overflow-y-auto bg-white p-3 rounded-xl border border-stone-200"></div>
          </div>

          <div id="promoProductSelectorWrap" class="hidden pt-2 space-y-2">
            <div class="flex items-center justify-between gap-2">
              <label class="font-semibold text-stone-700">Chọn Các Sản Phẩm Được Áp Dụng:</label>
              <input type="text" id="promoProductSearch" oninput="filterPromoProductChecklist(this.value)" placeholder="Lọc nhanh tên / SKU..." class="bg-white border border-stone-200 rounded-lg px-2.5 py-1 text-[11px] w-44 focus:outline-none" />
            </div>
            <div id="promoProductChecklist" class="grid grid-cols-1 sm:grid-cols-2 gap-1.5 max-h-48 overflow-y-auto bg-white p-3 rounded-xl border border-stone-200"></div>
          </div>
        </div>

        <div class="pt-3 border-t border-stone-100 flex items-center justify-end space-x-2">
          <button type="button" onclick="closePromotionModal()" class="px-4 py-2 bg-stone-100 hover:bg-stone-200 text-stone-600 rounded-xl font-medium transition">Hủy</button>
          <button type="submit" id="promoSaveBtn" class="px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl font-bold shadow transition">Lưu Chương Trình</button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL: PROMOTION DETAIL & USAGE HISTORY -->
  <div id="promotionDetailModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
    <div onclick="closePromotionDetailModal()" class="fixed inset-0 bg-stone-900/60 backdrop-blur-sm"></div>
    <div class="relative bg-white rounded-3xl max-w-4xl w-full p-6 shadow-2xl z-10 border border-stone-200 max-h-[92vh] overflow-y-auto">
      <div class="flex items-center justify-between pb-3 border-b border-stone-100 mb-4">
        <div>
          <h3 class="font-bold text-base text-stone-900 font-serif-title">Chi Tiết Chương Trình Khuyến Mãi</h3>
          <p class="text-xs text-stone-400" id="promoDetailSubtitle">--</p>
        </div>
        <button onclick="closePromotionDetailModal()" class="w-8 h-8 rounded-full hover:bg-stone-100 flex items-center justify-center text-stone-400">
          <i class="ph ph-x"></i>
        </button>
      </div>
      <div id="promotionDetailBody" class="space-y-4 text-xs"></div>
    </div>
  </div>

  <!-- MODAL: ADD / EDIT STAFF -->
  <div id="staffModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
    <div onclick="closeStaffModal()" class="fixed inset-0 bg-stone-900/60 backdrop-blur-sm"></div>
    <div class="relative bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl z-10 border border-stone-200">
      <div class="flex items-center justify-between pb-3 border-b border-stone-100 mb-4">
        <div>
          <h3 id="staffModalTitle" class="font-bold text-base text-stone-900 font-serif-title">Cấp Tài Khoản Nhân Sự Mới</h3>
          <p class="text-xs text-stone-400">Phân quyền chức vụ cửa hàng</p>
        </div>
        <button onclick="closeStaffModal()" class="w-8 h-8 rounded-full hover:bg-stone-100 flex items-center justify-center text-stone-400">
          <i class="ph ph-x"></i>
        </button>
      </div>

      <form onsubmit="handleStaffSubmit(event)" class="space-y-4 text-xs">
        <input type="hidden" id="staffEditId" />

        <div>
          <label class="block font-semibold text-stone-700 mb-1">Họ Và Tên Nhân Sự *</label>
          <input type="text" id="staffName" required placeholder="Nguyễn Văn A..." class="w-full bg-stone-50 border border-stone-300 rounded-xl px-3 py-2 text-stone-800 focus:outline-none" />
        </div>

        <div>
          <label class="block font-semibold text-stone-700 mb-1">Email Đăng Nhập *</label>
          <input type="email" id="staffEmail" required placeholder="staff@bloomora.vn" class="w-full bg-stone-50 border border-stone-300 rounded-xl px-3 py-2 text-stone-800 focus:outline-none" />
        </div>

        <div>
          <label class="block font-semibold text-stone-700 mb-1">Số Điện Thoại</label>
          <input type="text" id="staffPhone" placeholder="0908xxxxxx" class="w-full bg-stone-50 border border-stone-300 rounded-xl px-3 py-2 text-stone-800 focus:outline-none" />
        </div>

        <div>
          <label class="block font-semibold text-stone-700 mb-1">Vai Trò Chức Vụ (RBAC) *</label>
          <select id="staffRoleId" required class="w-full bg-stone-50 border border-stone-300 rounded-xl px-3 py-2 text-stone-800 focus:outline-none">
            <option value="1">1. Quản Trị Viên Tối Cao (Admin)</option>
            <option value="4">4. Quản Lý Cửa Hàng (Manager)</option>
            <option value="2">2. Nghệ Nhân Cắm Hoa (Florist)</option>
            <option value="5">5. Nhân Viên Giao Vận (Shipper)</option>
            <option value="6">6. Thủ Kho Hoa & Vật Liệu (Warehouse)</option>
          </select>
        </div>

        <div>
          <label class="block font-semibold text-stone-700 mb-1" id="staffPasswordLabel">Mật Khẩu *</label>
          <input type="password" id="staffPassword" placeholder="••••••••" class="w-full bg-stone-50 border border-stone-300 rounded-xl px-3 py-2 text-stone-800 focus:outline-none" />
        </div>

        <div class="pt-3 border-t border-stone-100 flex items-center justify-end space-x-2">
          <button type="button" onclick="closeStaffModal()" class="px-4 py-2 bg-stone-100 hover:bg-stone-200 text-stone-600 rounded-xl font-medium transition">
            Hủy
          </button>
          <button type="submit" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl font-bold shadow transition">
            Lưu Nhân Sự
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL: REJECT REVIEW -->
  <div id="rejectReviewModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
    <div onclick="closeRejectModal()" class="fixed inset-0 bg-stone-900/50 backdrop-blur-sm"></div>
    <div class="relative bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl z-10 border border-stone-200">
      <div class="flex items-center justify-between pb-3 border-b border-stone-100 mb-4">
        <div>
          <h3 class="font-bold text-base text-stone-900 font-serif-title">Từ Chối Đánh Giá Vi Phạm</h3>
          <p class="text-xs text-stone-400">Ghi nhận lý do kiểm duyệt không hợp lệ</p>
        </div>
        <button onclick="closeRejectModal()" class="w-8 h-8 rounded-full hover:bg-stone-100 flex items-center justify-center text-stone-400">
          <i class="ph ph-x"></i>
        </button>
      </div>

      <form onsubmit="handleRejectSubmit(event)" class="space-y-4 text-xs">
        <input type="hidden" id="rejectReviewId" />

        <div>
          <label class="block font-semibold text-stone-700 mb-1">Chọn hoặc nhập lý do từ chối:</label>
          <select id="rejectPresetReason" onchange="if(this.value) document.getElementById('rejectCustomReason').value = this.value" class="w-full bg-stone-50 border border-stone-300 rounded-xl px-3 py-2 text-stone-800 mb-2 focus:outline-none">
            <option value="">-- Chọn lý do mẫu --</option>
            <option value="Nội dung chứa từ ngữ thô tục, xúc phạm hoặc kích động">Từ ngữ thô tục, xúc phạm</option>
            <option value="Hình ảnh đính kèm không liên quan đến hoa hoặc không phù hợp">Ảnh không phù hợp / không liên quan</option>
            <option value="Đánh giá có tính chất spam, quảng cáo hoặc thông tin sai lệch">Spam / Quảng cáo</option>
            <option value="Nội dung để lộ thông tin riêng tư cá nhân">Lộ thông tin cá nhân</option>
          </select>

          <textarea id="rejectCustomReason" required rows="3" placeholder="Nhập lý do chi tiết từ chối..." class="w-full bg-stone-50 border border-stone-300 rounded-xl p-2.5 text-stone-800 focus:outline-none"></textarea>
        </div>

        <div class="pt-3 border-t border-stone-100 flex items-center justify-end space-x-2">
          <button type="button" onclick="closeRejectModal()" class="px-4 py-2 bg-stone-100 hover:bg-stone-200 text-stone-600 rounded-xl font-medium transition">
            Hủy
          </button>
          <button type="submit" id="rejectSubmitBtn" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl font-bold shadow transition">
            Xác Nhận Từ Chối
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL: REPLY REVIEW -->
  <div id="replyReviewModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
    <div onclick="closeReplyModal()" class="fixed inset-0 bg-stone-900/50 backdrop-blur-sm"></div>
    <div class="relative bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl z-10 border border-stone-200">
      <div class="flex items-center justify-between pb-3 border-b border-stone-100 mb-4">
        <div>
          <h3 class="font-bold text-base text-stone-900 font-serif-title">Phản Hồi Chính Thức Từ Bloomora</h3>
          <p class="text-xs text-stone-400" id="replyModalCustomerName">Gửi lời cảm ơn hoặc hỗ trợ khách hàng</p>
        </div>
        <button onclick="closeReplyModal()" class="w-8 h-8 rounded-full hover:bg-stone-100 flex items-center justify-center text-stone-400">
          <i class="ph ph-x"></i>
        </button>
      </div>

      <form onsubmit="handleReplySubmit(event)" class="space-y-4 text-xs">
        <input type="hidden" id="replyReviewId" />

        <div class="p-3 bg-stone-50 rounded-xl border border-stone-100 text-stone-700">
          <span class="block text-[11px] text-stone-400 font-semibold mb-1">Nhận xét của khách:</span>
          <p id="replyModalReviewText" class="italic text-xs"></p>
        </div>

        <div>
          <label class="block font-semibold text-stone-700 mb-1">Nội dung phản hồi công khai của Shop:</label>
          <textarea id="replyContentInput" required rows="4" 
                    placeholder="Dạ Bloomora chân thành cảm ơn quý khách đã tin tưởng và ủng hộ..."
                    class="w-full bg-stone-50 border border-stone-300 rounded-xl p-3 text-stone-800 focus:ring-2 focus:ring-rose-500 focus:outline-none"></textarea>
          <span class="text-[11px] text-stone-400 mt-1 block">Phản hồi này sẽ được hiển thị công khai ngay dưới đánh giá của khách trên trang sản phẩm.</span>
        </div>

        <div class="pt-3 border-t border-stone-100 flex items-center justify-end space-x-2">
          <button type="button" onclick="closeReplyModal()" class="px-4 py-2 bg-stone-100 hover:bg-stone-200 text-stone-600 rounded-xl font-medium transition">
            Hủy
          </button>
          <button type="submit" id="replySubmitBtn" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl font-bold shadow transition flex items-center space-x-1.5">
            <i class="ph ph-paper-plane-right-bold"></i>
            <span>Đăng Phản Hồi</span>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL: LIGHTBOX ZOOM PHOTO -->
  <div id="adminLightboxModal" class="fixed inset-0 z-[70] hidden flex items-center justify-center bg-black/85 backdrop-blur-sm p-4" onclick="closeAdminLightbox()">
    <button type="button" onclick="closeAdminLightbox()" class="absolute top-4 right-4 text-white hover:text-rose-400 text-2xl p-2 transition">
      <i class="ph ph-x-bold"></i>
    </button>
    <img id="adminLightboxImg" src="" alt="Ảnh hoa thực tế" class="max-w-full max-h-[90vh] rounded-2xl object-contain shadow-2xl border border-white/20" onclick="event.stopPropagation()" />
  </div>

  <!-- ADMIN TOAST CONTAINER -->
  <div id="adminToastContainer" class="fixed top-5 right-5 z-[90] flex flex-col items-end space-y-2 pointer-events-none"></div>

  <!-- JAVASCRIPT MASTER SPA CONTROLLER -->
  <script src="js/admin_app.js?v=2.4"></script>
</body>
</html>
