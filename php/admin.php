<?php
declare(strict_types=1);

require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/database.php';

init_secure_session();

// Kiểm tra quyền Admin/Staff (nếu chưa đăng nhập thì tự gán thông tin mẫu để tiện chạy thử nghiệm)
$adminName = $_SESSION['user_name'] ?? 'Admin Chủ Tiệm';
$adminRole = $_SESSION['role_name'] ?? 'admin';
$adminRoleDisplay = ($adminRole === 'florist' || $adminRole === 'staff') ? 'Nghệ Nhân Florist' : 'Quản Trị Viên';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard (PHP) | Quản Trị Bloomora</title>

  <!-- Google Fonts Chuẩn Tiếng Việt -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">

  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Phosphor Icons -->
  <script src="https://unpkg.com/@phosphor-icons/web"></script>

  <style>
    body { font-family: 'Be Vietnam Pro', sans-serif; }
    .font-serif-title { font-family: 'Playfair Display', Georgia, serif; }
  </style>

  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            rose: {
              50: '#fff1f2', 100: '#ffe4e6', 200: '#fecdd3',
              500: '#f43f5e', 600: '#e11d48', 700: '#be123c', 800: '#9f1239', 900: '#881337',
            }
          }
        }
      }
    }
  </script>
</head>

<body class="bg-stone-100 text-stone-800 antialiased min-h-screen flex flex-col">

  <!-- ================= BANNER THÔNG BÁO QUYỀN ADMIN ================= -->
  <div id="adminNoticeBanner" class="hidden bg-amber-500 text-stone-900 px-4 py-2 text-xs font-semibold flex items-center justify-between shadow-sm">
    <div class="flex items-center space-x-2">
      <i class="ph-bold ph-shield-warning text-base"></i>
      <span>Bạn đang xem chế độ Thử nghiệm Quản trị. Để kích hoạt tài khoản Admin chính thức:</span>
    </div>
    <button onclick="loginAsAdminQuickly()" class="bg-stone-900 hover:bg-stone-800 text-white px-3 py-1 rounded-lg text-xs font-bold transition">
      Đăng Nhập Admin 1-Click
    </button>
  </div>

  <!-- ================= TOPBAR QUẢN TRỊ ADMIN PHP ================= -->
  <header class="bg-stone-900 text-white border-b border-stone-800 sticky top-0 z-30">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
      
      <!-- Brand & Version -->
      <div class="flex items-center space-x-3">
        <a href="admin.php" class="flex items-center space-x-2">
          <div class="w-9 h-9 rounded-xl bg-rose-600 flex items-center justify-center text-white shadow-md shadow-rose-600/30">
            <i class="ph ph-flower-lotus text-xl"></i>
          </div>
          <span class="font-serif-title text-xl font-bold tracking-wide">BLOOMORA ADMIN</span>
        </a>
        <span class="text-[10px] bg-rose-900/60 text-rose-300 px-2 py-0.5 rounded-full font-semibold border border-rose-700/50">PHP v2.0</span>
      </div>

      <!-- 3 Tab Phân Hệ Quản Trị (Desktop) -->
      <nav class="hidden md:flex items-center space-x-1 bg-stone-800 p-1 rounded-2xl text-xs font-semibold border border-stone-700/80">
        <a href="admin.php" class="px-3.5 py-1.5 rounded-xl bg-rose-600 text-white font-bold shadow-sm transition">
          <i class="ph ph-receipt mr-1"></i>
          <span>Đơn Hàng</span>
        </a>
        <a href="../html/admin-products.html" class="px-3.5 py-1.5 rounded-xl text-stone-300 hover:text-white hover:bg-stone-700/50 transition">
          <i class="ph ph-flower mr-1"></i>
          <span>Sản Phẩm Hoa</span>
        </a>
        <a href="../html/admin-inventory.html" class="px-3.5 py-1.5 rounded-xl text-stone-300 hover:text-white hover:bg-stone-700/50 transition">
          <i class="ph ph-package mr-1"></i>
          <span>Kho & Vật Tư</span>
        </a>
      </nav>

      <!-- Mobile Hamburger -->
      <button onclick="document.getElementById('adminMobileNav').classList.toggle('hidden')" class="md:hidden text-stone-300 hover:text-white p-2 rounded-lg border border-stone-700">
        <i class="ph ph-list text-xl"></i>
      </button>

      <!-- Điều hướng & Tài khoản PHP Session -->
      <div class="flex items-center space-x-4">
        <!-- Xem Cửa Hàng -->
        <a href="index.php" class="text-xs text-stone-300 hover:text-white flex items-center space-x-1.5 bg-stone-800 hover:bg-stone-700 px-3 py-1.5 rounded-xl border border-stone-700 transition">
          <i class="ph ph-arrow-square-out text-sm"></i>
          <span>Xem Cửa Hàng</span>
        </a>

        <!-- Admin Profile -->
        <div class="flex items-center space-x-2 text-xs">
          <div id="adminAvatar" class="w-8 h-8 rounded-full bg-rose-600 text-white font-bold flex items-center justify-center text-xs shadow">
            <?php echo mb_substr($adminName, 0, 1); ?>
          </div>
          <div class="hidden sm:block text-left">
            <span id="adminName" class="block font-semibold text-white leading-tight"><?php echo e($adminName); ?></span>
            <span id="adminRoleBadge" class="text-[10px] text-rose-400 block font-semibold uppercase"><?php echo e($adminRoleDisplay); ?></span>
          </div>
        </div>

        <!-- Đăng Xuất -->
        <a href="logout.php" class="text-stone-400 hover:text-rose-400 p-1.5 rounded-lg border border-stone-700 transition" title="Đăng xuất">
          <i class="ph ph-sign-out text-base"></i>
        </a>
      </div>

    </div>

    <!-- Mobile Menu Dropdown -->
    <div id="adminMobileNav" class="hidden md:hidden border-t border-stone-800 bg-stone-900 px-4 py-3 space-y-1">
      <a href="admin.php" class="block px-3 py-2 rounded-xl bg-rose-600 text-white font-bold text-xs">
        <i class="ph ph-receipt mr-1"></i> Đơn Hàng
      </a>
      <a href="../html/admin-products.html" class="block px-3 py-2 rounded-xl text-stone-300 hover:text-white hover:bg-stone-800 text-xs">
        <i class="ph ph-flower mr-1"></i> Sản Phẩm Hoa
      </a>
      <a href="../html/admin-inventory.html" class="block px-3 py-2 rounded-xl text-stone-300 hover:text-white hover:bg-stone-800 text-xs">
        <i class="ph ph-package mr-1"></i> Kho & Vật Tư
      </a>
    </div>
  </header>

  <!-- ================= NỘI DUNG CHÍNH (MAIN DASHBOARD) ================= -->
  <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 flex-1 w-full space-y-6 sm:space-y-8">

    <!-- Chào mừng & Hành động -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-3xl border border-stone-200/90 shadow-sm">
      <div>
        <h1 class="font-serif-title text-xl sm:text-2xl font-bold text-stone-900">Bảng Điều Khiển & Hoạt Động Cửa Hàng (PHP)</h1>
        <p class="text-xs text-stone-500 mt-1">Theo dõi số liệu doanh thu thực tế, tiến độ đơn cắm hoa và cảnh báo tồn kho trong ngày tại Đắk Lắk.</p>
      </div>
      <div class="flex items-center space-x-2 self-start sm:self-auto">
        <a href="../html/admin.html" class="bg-stone-100 hover:bg-stone-200 text-stone-700 text-xs font-bold px-3.5 py-2.5 rounded-xl border border-stone-200 transition flex items-center space-x-1.5">
          <i class="ph ph-browsers text-sm"></i>
          <span>Bản HTML Tĩnh</span>
        </a>
        <button id="btnRefresh" onclick="refreshDashboard()" class="bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-bold px-3.5 py-2.5 rounded-xl border border-rose-200 transition flex items-center space-x-1.5 cursor-pointer">
          <i id="refreshIcon" class="ph ph-arrows-clockwise text-sm"></i>
          <span>Làm Mới</span>
        </button>
      </div>
    </div>

    <!-- 4 THẺ CHỈ SỐ KPI TRỌNG ĐIỂM -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
      
      <!-- Thẻ 1: Doanh thu thực -->
      <div class="bg-white rounded-3xl p-5 border border-stone-200 shadow-sm space-y-2">
        <div class="flex items-center justify-between">
          <span class="text-xs font-bold text-stone-400 uppercase tracking-wider">Doanh Thu Hôm Nay</span>
          <div class="w-9 h-9 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg">
            <i class="ph ph-currency-circle-dollar"></i>
          </div>
        </div>
        <div class="text-2xl font-black text-stone-900">28.450.000₫</div>
        <div class="flex items-center text-[11px] text-emerald-600 font-semibold">
          <i class="ph ph-trend-up mr-1"></i>
          <span>+18.4% so với hôm qua</span>
        </div>
      </div>

      <!-- Thẻ 2: Tổng số đơn -->
      <div class="bg-white rounded-3xl p-5 border border-stone-200 shadow-sm space-y-2">
        <div class="flex items-center justify-between">
          <span class="text-xs font-bold text-stone-400 uppercase tracking-wider">Tổng Đơn Hàng</span>
          <div class="w-9 h-9 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-lg">
            <i class="ph ph-receipt"></i>
          </div>
        </div>
        <div id="kpiTotalOrders" class="text-2xl font-black text-stone-900">24 Đơn</div>
        <div class="text-[11px] text-stone-500 font-medium">
          <span>19 đơn VietQR | 5 đơn COD</span>
        </div>
      </div>

      <!-- Thẻ 3: Đơn cần cắm trong ca -->
      <div class="bg-white rounded-3xl p-5 border border-stone-200 shadow-sm space-y-2">
        <div class="flex items-center justify-between">
          <span class="text-xs font-bold text-stone-400 uppercase tracking-wider">Cần Cắm & Chụp Ảnh</span>
          <div class="w-9 h-9 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg">
            <i class="ph ph-camera"></i>
          </div>
        </div>
        <div id="kpiUrgentOrders" class="text-2xl font-black text-amber-600">3 Đơn Trong Ca</div>
        <div class="flex items-center text-[11px] text-amber-600 font-semibold">
          <i class="ph ph-clock mr-1"></i>
          <span>Giao khung 15:30 - 17:30</span>
        </div>
      </div>

      <!-- Thẻ 4: Shipper đang giao -->
      <div class="bg-white rounded-3xl p-5 border border-stone-200 shadow-sm space-y-2">
        <div class="flex items-center justify-between">
          <span class="text-xs font-bold text-stone-400 uppercase tracking-wider">Shipper Đang Giao</span>
          <div class="w-9 h-9 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg">
            <i class="ph ph-moped"></i>
          </div>
        </div>
        <div id="kpiDelivering" class="text-2xl font-black text-indigo-600">5 Chuyến</div>
        <div class="text-[11px] text-stone-500 font-medium">
          <span>Đang di chuyển giao hỏa tốc</span>
        </div>
      </div>

    </div>

    <!-- KHU VỰC DƯỚI: BẢNG TIẾN ĐỘ ĐƠN & CẢNH BÁO TỒN KHO -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 sm:gap-8">
      
      <!-- BẢNG ĐƠN HÀNG TRONG CA (8 CỘT) -->
      <div class="lg:col-span-8 bg-white rounded-3xl p-5 sm:p-6 border border-stone-200 shadow-sm space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-stone-100 pb-3 gap-2">
          <div>
            <h2 class="font-bold text-sm text-stone-900">Danh Sách Đơn Cần Cắm Hoa Trong Ca</h2>
            <p class="text-xs text-stone-400">Nghệ nhân Florist Buôn Ma Thuột cắm hoa, chụp ảnh Photo Proof và giao theo khung giờ</p>
          </div>
          <span id="orderCountBadge" class="text-[11px] bg-rose-50 text-rose-600 font-bold px-2.5 py-1 rounded-full self-start sm:self-auto">3 đơn</span>
        </div>

        <!-- Bộ Lọc Phân Loại Tiến Độ Đơn Trong Ca -->
        <div class="flex items-center space-x-1.5 overflow-x-auto pb-1 text-xs font-semibold no-scrollbar">
          <button onclick="filterAdminOrders('all', this)" class="admin-order-filter-btn px-3 py-1.5 rounded-xl bg-rose-600 text-white font-bold transition flex-shrink-0 cursor-pointer">Tất Cả Đơn</button>
          <button onclick="filterAdminOrders('received', this)" class="admin-order-filter-btn px-3 py-1.5 rounded-xl bg-stone-100 text-stone-600 hover:bg-stone-200 transition flex-shrink-0 cursor-pointer">1. Chờ Xác Nhận / Tiền</button>
          <button onclick="filterAdminOrders('preparing', this)" class="admin-order-filter-btn px-3 py-1.5 rounded-xl bg-stone-100 text-stone-600 hover:bg-stone-200 transition flex-shrink-0 cursor-pointer">2. Đang Cắm Hoa (Florist)</button>
          <button onclick="filterAdminOrders('photo_ready', this)" class="admin-order-filter-btn px-3 py-1.5 rounded-xl bg-stone-100 text-stone-600 hover:bg-stone-200 transition flex-shrink-0 cursor-pointer">3. Chờ Khách Duyệt Ảnh</button>
          <button onclick="filterAdminOrders('delivering', this)" class="admin-order-filter-btn px-3 py-1.5 rounded-xl bg-stone-100 text-stone-600 hover:bg-stone-200 transition flex-shrink-0 cursor-pointer">4. Đang Giao Hàng</button>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="text-stone-400 uppercase tracking-wider border-b border-stone-100">
              <tr>
                <th class="py-2.5 font-bold">Mã Đơn</th>
                <th class="py-2.5 font-bold">Mẫu Hoa & Băng Rôn</th>
                <th class="py-2.5 font-bold">Khung Giờ</th>
                <th class="py-2.5 font-bold">Trạng Thái</th>
                <th class="py-2.5 font-bold text-right">Thao Tác</th>
              </tr>
            </thead>
            <tbody id="ordersTableBody" class="divide-y divide-stone-100 text-stone-700">
              <!-- Nội dung render động qua JavaScript -->
            </tbody>
          </table>
        </div>
      </div>

      <!-- CẢNH BÁO TỒN KHO THẤP (4 CỘT) -->
      <div class="lg:col-span-4 bg-white rounded-3xl p-5 sm:p-6 border border-stone-200 shadow-sm space-y-4">
        <div class="flex items-center justify-between border-b border-stone-100 pb-3">
          <div class="flex items-center space-x-2">
            <i class="ph ph-warning-circle text-amber-500 text-lg"></i>
            <h2 class="font-bold text-sm text-stone-900">Cảnh Báo Tồn Kho Thấp</h2>
          </div>
          <span class="text-[10px] bg-amber-50 text-amber-700 font-bold px-2 py-0.5 rounded-full uppercase">&lt; 5 Đơn Vị</span>
        </div>

        <!-- Danh sách kho hàng render động -->
        <div id="inventoryContainer" class="space-y-3">
          <!-- Render động qua BloomoraAdmin.getInventory() -->
        </div>

        <button onclick="handleRestockAll()" class="w-full text-center text-xs font-bold text-stone-700 hover:text-rose-600 bg-stone-100 hover:bg-stone-200 py-2.5 rounded-xl transition flex items-center justify-center space-x-1.5 cursor-pointer">
          <i class="ph ph-package text-sm"></i>
          <span>Kiểm Kê & Nhập Toàn Bộ Kho</span>
        </button>
      </div>

    </div>

  </main>

  <!-- ================= MODAL CẬP NHẬT TIẾN ĐỘ ĐƠN HÀNG ================= -->
  <div id="orderModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-stone-900/60 backdrop-blur-sm p-4">
    <div class="bg-white w-full max-w-lg rounded-3xl p-6 sm:p-7 border border-stone-200 shadow-2xl space-y-5 animate-in fade-in zoom-in-95 duration-200">
      
      <div class="flex items-center justify-between border-b border-stone-100 pb-3">
        <div class="flex items-center space-x-2">
          <div class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center font-bold text-sm">
            <i class="ph ph-pencil-simple"></i>
          </div>
          <div>
            <h3 class="font-bold text-sm text-stone-900">Cập Nhật Trạng Thái Đơn Hàng</h3>
            <span id="modalOrderCode" class="font-mono text-xs font-bold text-rose-600">BL00000</span>
          </div>
        </div>
        <button onclick="closeOrderModal()" class="text-stone-400 hover:text-stone-700 text-lg p-1">
          <i class="ph ph-x"></i>
        </button>
      </div>

      <!-- Tên mẫu hoa & ghi chú -->
      <div class="bg-stone-50 p-3.5 rounded-2xl border border-stone-100 text-xs space-y-1">
        <div id="modalOrderFlower" class="font-bold text-stone-900">Tên Mẫu Hoa</div>
        <div id="modalOrderRibbon" class="text-stone-500">Băng rôn: ...</div>
      </div>

      <!-- Ảnh thật Photo Proof nếu có -->
      <div id="modalPhotoContainer" class="hidden space-y-1.5">
        <label class="block text-xs font-semibold text-stone-700">Ảnh thật hoa hoàn thiện (Photo Proof):</label>
        <div class="relative rounded-2xl overflow-hidden border border-stone-200 aspect-video bg-stone-100 flex items-center justify-center">
          <img id="modalOrderPhoto" src="" alt="Hoa thật" class="w-full h-full object-cover">
        </div>
      </div>

      <!-- Ảnh thật Photo Proof (Florist tải lên) -->
      <div class="space-y-1.5">
        <div class="flex items-center justify-between">
          <label class="block text-xs font-semibold text-stone-700">Ảnh hoa thành phẩm (Photo Proof):</label>
          <button type="button" onclick="sampleFloristProofPhoto()" class="text-[10px] text-rose-600 font-bold bg-rose-50 hover:bg-rose-100 border border-rose-200 px-2 py-0.5 rounded-lg transition cursor-pointer">
            📸 Gán Ảnh Mẫu Đã Cắm Xong
          </button>
        </div>
        <input type="url" id="modalPhotoInput" placeholder="https://images.unsplash.com/photo-..."
          class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 py-2 text-xs text-stone-800 font-medium focus:bg-white focus:border-rose-500 focus:outline-none transition">
      </div>

      <!-- Lựa chọn trạng thái mới -->
      <div class="space-y-1.5">
        <label class="block text-xs font-semibold text-stone-700">Chọn trạng thái thực tế: *</label>
        <select id="modalStatusSelect" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 py-2.5 text-xs text-stone-800 font-semibold focus:bg-white focus:border-rose-500 focus:outline-none transition">
          <option value="received">1. Đã Tiếp Nhận Yêu Cầu</option>
          <option value="preparing">2. Florist Đang Cắm Hoa</option>
          <option value="photo_ready">3. Đã Chụp Ảnh Thật (Chờ Khách Duyệt)</option>
          <option value="delivering">4. Shipper Đang Giao Hàng</option>
          <option value="completed">5. Hoàn Tất Giao Hoa Thành Công</option>
        </select>
      </div>

      <!-- Ghi chú tiến độ cho Florist -->
      <div class="space-y-1.5">
        <label class="block text-xs font-semibold text-stone-700">Ghi chú cho khách & nghệ nhân:</label>
        <textarea id="modalOrderNote" rows="2" class="w-full bg-stone-50 border border-stone-200 rounded-xl p-3 text-xs text-stone-800 font-medium focus:bg-white focus:border-rose-500 focus:outline-none transition" placeholder="Ví dụ: Hoa đã cắm xong, chờ shipper lấy hàng..."></textarea>
      </div>

      <!-- Nút hành động -->
      <div class="flex items-center justify-between pt-2 border-t border-stone-100">
        <button type="button" onclick="printOrderSlip()" class="px-3.5 py-2.5 text-xs font-bold text-stone-700 bg-stone-100 hover:bg-stone-200 rounded-xl transition flex items-center space-x-1.5 cursor-pointer" title="In phiếu giao hàng cho shipper & dải in băng rôn vi tính">
          <i class="ph ph-printer text-base text-stone-600"></i>
          <span>In Phiếu & Băng Rôn</span>
        </button>
        <div class="flex items-center space-x-2">
          <button onclick="closeOrderModal()" class="px-4 py-2.5 text-xs font-bold text-stone-600 hover:bg-stone-100 rounded-xl transition cursor-pointer">
            Hủy Bỏ
          </button>
          <button onclick="saveOrderStatus()" class="bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold px-5 py-2.5 rounded-xl shadow-md shadow-rose-600/30 transition flex items-center space-x-1.5 cursor-pointer">
            <i class="ph ph-floppy-disk text-sm"></i>
            <span>Lưu Trạng Thái</span>
          </button>
        </div>
      </div>

    </div>
  </div>

  <!-- FOOTER -->
  <footer class="bg-white border-t border-stone-200 py-4 text-center text-xs text-stone-400">
    Bloomora Floral E-Commerce Master Platform &copy; 2026. Phân hệ Quản trị PHP.
  </footer>

  <!-- FILE XỬ LÝ AUTH & APP ADMIN RIÊNG BIỆT -->
  <script src="../html/auth.js"></script>
  <script src="../html/app.js"></script>

</body>
</html>
