<?php
declare(strict_types=1);

require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/database.php';

init_secure_session();

$isLoggedIn = !empty($_SESSION['user_id']);
$userName   = $_SESSION['user_name'] ?? 'Khách Hàng';
$userRole   = $_SESSION['role_name'] ?? 'customer';
$flash      = get_flash();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bloomora | Tiệm Hoa Tươi Thuần Việt Tích Hợp AI (PHP)</title>

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
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
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

<body class="bg-stone-50 text-stone-800 antialiased min-h-screen flex flex-col">

  <!-- Topbar thông báo -->
  <div class="bg-stone-900 text-stone-300 text-xs py-2 px-4 border-b border-stone-800">
    <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-2">
      <div class="flex items-center space-x-2">
        <span class="bg-rose-600 text-white font-bold text-[10px] px-2 py-0.5 rounded-full uppercase">Đắk Lắk</span>
        <span>🌸 Điện hoa Buôn Ma Thuột - Miễn phí in băng rôn vi tính & Chụp ảnh hoa thật duyệt trước khi giao!</span>
      </div>
      <div class="flex items-center space-x-4 text-[11px] text-stone-400">
        <span><i class="ph ph-clock mr-1 text-rose-500"></i> Giao 45-60 phút tại BMT</span>
        <span>|</span>
        <a href="tel:0946618960" class="hover:text-white transition"><i class="ph ph-phone-call mr-1 text-emerald-400"></i> Hotline: <strong class="text-white">0946618960</strong></a>
      </div>
    </div>
  </div>

  <!-- Header chính -->
  <header class="sticky top-0 z-40 bg-white/95 backdrop-blur border-b border-stone-200 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between h-20 gap-4">
        
        <!-- Logo Bloomora -->
        <a href="index.php" class="flex items-center space-x-3 group flex-shrink-0">
          <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-rose-700 to-rose-500 flex items-center justify-center text-white shadow-md shadow-rose-600/30">
            <i class="ph ph-flower-lotus text-2xl"></i>
          </div>
          <div>
            <div class="font-serif-title text-2xl font-bold tracking-tight text-stone-900 group-hover:text-rose-600 transition">BLOOMORA</div>
            <div class="text-[9px] uppercase tracking-widest text-stone-400 font-semibold -mt-1">TIỆM HOA TƯƠI ĐẮK LẮK (PHP)</div>
          </div>
        </a>

        <!-- Thanh tìm kiếm nhanh -->
        <div class="hidden md:flex flex-1 max-w-md mx-4 relative">
          <input type="text" id="searchInput" placeholder="Tìm hoa khai trương, hoa sinh nhật, hoa hồng..."
            class="w-full bg-stone-100 hover:bg-stone-50 focus:bg-white text-xs text-stone-800 pl-10 pr-4 py-2.5 rounded-full border border-stone-200 focus:border-rose-500 focus:ring-2 focus:ring-rose-200 focus:outline-none transition">
          <i class="ph ph-magnifying-glass text-stone-400 absolute left-3.5 top-3 text-base pointer-events-none"></i>
        </div>

        <!-- Menu & Giỏ hàng -->
        <div class="flex items-center space-x-3 sm:space-x-4">
          <nav class="hidden lg:flex items-center space-x-4 text-xs font-semibold text-stone-600">
            <a href="index.php" class="text-rose-600 font-bold">Trang Chủ</a>
            <a href="#productsSection" class="hover:text-rose-600 transition">Mẫu Hoa Tươi</a>
            <button type="button" onclick="openTrackOrderModal()" class="hover:text-rose-600 transition flex items-center space-x-1 cursor-pointer">
              <i class="ph ph-magnifying-glass text-rose-600"></i>
              <span>Tra Cứu Đơn</span>
            </button>
            <button type="button" onclick="toggleAIChatbot()" class="text-rose-600 bg-rose-50 border border-rose-200 px-2.5 py-1 rounded-xl font-bold text-[11px] hover:bg-rose-100 flex items-center space-x-1 transition shadow-sm cursor-pointer">
              <i class="ph-bold ph-sparkle text-rose-600"></i>
              <span>Trợ Lý AI</span>
            </button>
            <?php if ($isLoggedIn && $userRole === 'admin'): ?>
              <a href="admin.php" class="text-rose-600 bg-rose-50 border border-rose-200 px-2.5 py-1 rounded-xl font-bold text-[11px] hover:bg-rose-100 flex items-center space-x-1 transition shadow-sm">
                <i class="ph ph-shield-check text-base"></i>
                <span>Vào Admin</span>
              </a>
            <?php endif; ?>
          </nav>

          <!-- Khu vực Auth -->
          <?php if ($isLoggedIn): ?>
            <div id="authUserBox" class="flex items-center space-x-2 bg-stone-100 hover:bg-stone-200 border border-stone-200 py-1.5 px-3 rounded-xl text-xs transition">
              <button type="button" onclick="openCustomerProfileModal()" class="flex items-center space-x-1.5 cursor-pointer text-left">
                <div id="avatarLetter" class="w-6 h-6 rounded-full bg-rose-600 text-white font-bold flex items-center justify-center text-[10px]">
                  <?php echo mb_substr($userName, 0, 1); ?>
                </div>
                <span id="userNameSpan" class="font-bold text-stone-900 leading-none"><?php echo e($userName); ?></span>
              </button>
              <a href="logout.php" class="text-stone-400 hover:text-rose-600 ml-1 p-0.5 cursor-pointer" title="Đăng xuất">
                <i class="ph ph-sign-out text-base"></i>
              </a>
            </div>
          <?php else: ?>
            <div id="authGuestBox">
              <a href="login.php" class="flex items-center space-x-1.5 px-3 py-2 text-stone-700 hover:text-rose-600 hover:bg-rose-50 rounded-xl text-xs font-semibold border border-stone-200 transition">
                <i class="ph ph-user text-base text-rose-600"></i>
                <span>Đăng Nhập</span>
              </a>
            </div>
          <?php endif; ?>

          <!-- Nút Giỏ Hàng -->
          <button onclick="openCartDrawer()" class="relative flex items-center space-x-2 bg-rose-600 hover:bg-rose-700 text-white px-4 py-2 rounded-xl text-xs font-bold shadow-md shadow-rose-600/20 transition cursor-pointer">
            <i class="ph ph-shopping-bag text-base"></i>
            <span class="hidden sm:inline">Giỏ Hàng</span>
            <span id="cartCountBadge" class="bg-white text-rose-600 text-[10px] font-black px-1.5 py-0.5 rounded-full min-w-[18px] text-center">0</span>
          </button>
        </div>

      </div>
    </div>
  </header>

  <!-- Hero Banner & Hộp Tìm Hoa 3 Bước -->
  <main class="flex-1">
    <section class="relative bg-gradient-to-b from-rose-50/60 to-stone-50 py-12 lg:py-20 overflow-hidden">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="text-center max-w-3xl mx-auto mb-10 space-y-3">
          <span class="inline-flex items-center space-x-1 text-xs font-bold tracking-widest text-rose-700 uppercase bg-rose-100/80 px-3 py-1 rounded-full">
            <i class="ph ph-sparkle"></i>
            <span>Nghệ Thuật Điện Hoa Hiện Đại (PHP Backend)</span>
          </span>
          <h1 class="font-serif-title text-3xl sm:text-5xl font-bold text-stone-900 leading-tight">
            Trao Gửi Yêu Thương <br class="hidden sm:inline">Qua Từng Cánh Hoa Tươi
          </h1>
          <p class="text-xs sm:text-sm text-stone-600 max-w-xl mx-auto leading-relaxed">
            100% Hoa tươi tuyển chọn tại Buôn Ma Thuột. Tặng kèm dải băng rôn in vi tính sang trọng và chụp ảnh hoa thật gửi quý khách duyệt trước khi giao.
          </p>
        </div>

        <!-- Hộp tìm hoa 3 bước -->
        <div class="max-w-4xl mx-auto bg-white rounded-3xl p-5 sm:p-7 shadow-xl shadow-stone-200/50 border border-stone-200/80">
          <div class="text-xs font-bold uppercase tracking-wider text-stone-400 mb-4 flex items-center space-x-2">
            <i class="ph ph-funnel text-rose-600 text-base"></i>
            <span>Tìm Bó Hoa Phù Hợp Trong 3 Bước Nhanh:</span>
          </div>

          <form onsubmit="handleQuickSearch(event)" class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-4 gap-3">
            <div>
              <label class="block text-[11px] font-semibold text-stone-500 mb-1">1. Dịp tặng hoa:</label>
              <select id="quickOccasion" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 py-2.5 text-xs font-semibold text-stone-800 focus:bg-white focus:border-rose-500 focus:outline-none">
                <option value="all">Tất cả các dịp</option>
                <option value="khai-truong">Hoa Khai Trương (Phát Tài)</option>
                <option value="sinh-nhat">Hoa Mừng Sinh Nhật</option>
                <option value="tinh-yeu">Bó Hoa Tỏ Tình / Tình Yêu</option>
                <option value="chia-buon">Kệ Hoa Chia Buồn / Tang Lễ</option>
              </select>
            </div>

            <div>
              <label class="block text-[11px] font-semibold text-stone-500 mb-1">2. Khoảng ngân sách:</label>
              <select id="quickPrice" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 py-2.5 text-xs font-semibold text-stone-800 focus:bg-white focus:border-rose-500 focus:outline-none">
                <option value="all">Tất cả mức giá</option>
                <option value="under500">Dưới 500.000₫</option>
                <option value="500to1000">Từ 500.000₫ - 1.000.000₫</option>
                <option value="1000to2000">Từ 1.000.000₫ - 2.000.000₫</option>
                <option value="above2000">Trên 2.000.000₫ (Mẫu VIP)</option>
              </select>
            </div>

            <div>
              <label class="block text-[11px] font-semibold text-stone-500 mb-1">3. Khu vực nhận hoa:</label>
              <select id="quickLocation" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 py-2.5 text-xs font-semibold text-stone-800 focus:bg-white focus:border-rose-500 focus:outline-none">
                <option value="bmt_inner">Nội thành Buôn Ma Thuột (Giao 45p)</option>
                <option value="bmt_outer">Ngoại thành BMT (Hòa Thuận, Cư Êbur - 60p)</option>
                <option value="cumgar">Huyện Cư M'gar (Quảng Phú - 75p)</option>
                <option value="krongpac">Huyện Krông Pắc (Phước An - 90p)</option>
                <option value="buondon">Huyện Buôn Đôn (90p)</option>
                <option value="all">Các huyện khác tại Đắk Lắk</option>
              </select>
            </div>

            <div class="sm:col-span-3 lg:col-span-1 flex items-end">
              <button type="submit" class="w-full bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs py-3 px-4 rounded-xl shadow-md shadow-rose-600/30 transition flex items-center justify-center space-x-1.5 cursor-pointer">
                <i class="ph ph-magnifying-glass text-base"></i>
                <span>Tìm Hoa Ngay</span>
              </button>
            </div>
          </form>

          <div id="searchResultNotice" class="hidden mt-4 pt-3 border-t border-stone-100 text-xs text-rose-700 bg-rose-50/70 p-3 rounded-xl flex items-center space-x-2">
            <i class="ph ph-check-circle text-base"></i>
            <span id="searchResultText">Đang lọc hoa...</span>
          </div>

        </div>

      </div>
    </section>

    <!-- Danh Mục Sản Phẩm Hoa Tươi Wish Florist -->
    <section id="productsSection" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <div class="flex flex-col md:flex-row md:items-end justify-between mb-8 gap-4">
        <div>
          <div class="inline-flex items-center space-x-1.5 text-xs font-bold text-rose-600 uppercase tracking-wider bg-rose-50 px-3 py-1 rounded-full mb-2">
            <i class="ph-bold ph-flower-lotus"></i>
            <span>Bộ Sưu Tập Hoa Tươi Đắk Lắk</span>
          </div>
          <h2 class="font-serif-title text-2xl sm:text-3xl font-bold text-stone-900">Mẫu Hoa Tươi Đẹp Tuyển Chọn</h2>
          <p class="text-xs text-stone-500 mt-1">Cam kết hoa tươi mới trong ngày, thiết kế sang trọng kèm dải băng rôn in vi tính miễn phí.</p>
        </div>
        <div class="text-xs text-stone-400 flex items-center space-x-3">
          <span class="flex items-center space-x-1"><i class="ph-fill ph-check-circle text-emerald-500"></i><span>100% Ảnh hoa thật</span></span>
          <span>•</span>
          <span class="flex items-center space-x-1"><i class="ph-fill ph-shield-check text-rose-500"></i><span>Bảo hành tươi 3+ ngày</span></span>
        </div>
      </div>

      <!-- Thanh Lọc Phân Loại Tabs -->
      <div class="flex items-center space-x-2 overflow-x-auto pb-3 mb-8 no-scrollbar">
        <button onclick="filterCatalog('all', this)" data-category="all" class="catalog-filter-btn flex-shrink-0 px-4 py-2.5 rounded-2xl text-xs font-bold bg-rose-600 text-white shadow-md shadow-rose-600/20 transition flex items-center space-x-1.5 cursor-pointer">
          <i class="ph ph-squares-four text-sm"></i>
          <span>Tất Cả Mẫu Hoa</span>
        </button>
        <button onclick="filterCatalog('Hoa Khai Trương', this)" data-category="Hoa Khai Trương" class="catalog-filter-btn flex-shrink-0 px-4 py-2.5 rounded-2xl text-xs font-semibold bg-stone-100 text-stone-600 hover:bg-stone-200 transition flex items-center space-x-1.5 cursor-pointer">
          <i class="ph ph-storefront text-sm"></i>
          <span>Khai Trương & Sự Kiện</span>
        </button>
        <button onclick="filterCatalog('Hoa Tình Yêu & Hoa Hồng', this)" data-category="Hoa Tình Yêu & Hoa Hồng" class="catalog-filter-btn flex-shrink-0 px-4 py-2.5 rounded-2xl text-xs font-semibold bg-stone-100 text-stone-600 hover:bg-stone-200 transition flex items-center space-x-1.5 cursor-pointer">
          <i class="ph ph-heart text-sm"></i>
          <span>Bó Hoa Tình Yêu</span>
        </button>
        <button onclick="filterCatalog('Lan Hồ Điệp', this)" data-category="Lan Hồ Điệp" class="catalog-filter-btn flex-shrink-0 px-4 py-2.5 rounded-2xl text-xs font-semibold bg-stone-100 text-stone-600 hover:bg-stone-200 transition flex items-center space-x-1.5 cursor-pointer">
          <i class="ph ph-flower-lotus text-sm"></i>
          <span>Lan Hồ Điệp Quý Phái</span>
        </button>
        <button onclick="filterCatalog('Hoa Sinh Nhật', this)" data-category="Hoa Sinh Nhật" class="catalog-filter-btn flex-shrink-0 px-4 py-2.5 rounded-2xl text-xs font-semibold bg-stone-100 text-stone-600 hover:bg-stone-200 transition flex items-center space-x-1.5 cursor-pointer">
          <i class="ph ph-cake text-sm"></i>
          <span>Giỏ Hoa Sinh Nhật</span>
        </button>
        <button onclick="filterCatalog('Hoa Vĩnh Cửu', this)" data-category="Hoa Vĩnh Cửu" class="catalog-filter-btn flex-shrink-0 px-4 py-2.5 rounded-2xl text-xs font-semibold bg-stone-100 text-stone-600 hover:bg-stone-200 transition flex items-center space-x-1.5 cursor-pointer">
          <i class="ph ph-hourglass-high text-sm"></i>
          <span>Hoa Vĩnh Cửu 3 Năm</span>
        </button>
      </div>

      <!-- Lưới Danh Sách Sản Phẩm Hoa -->
      <div id="catalogGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Render từ js app.js -->
      </div>
    </section>
  </main>

  <!-- ================= MODAL ĐẶT HOA NHANH ================= -->
  <div id="quickOrderModal" onclick="if(event.target===this)closeQuickOrderModal()" class="hidden fixed inset-0 z-50 overflow-y-auto bg-stone-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-3xl w-full p-6 sm:p-8 shadow-2xl border border-stone-200 relative max-h-[92vh] flex flex-col md:flex-row gap-6 overflow-y-auto">
      
      <button onclick="closeQuickOrderModal()" class="absolute top-4 right-4 w-9 h-9 rounded-full bg-stone-100 hover:bg-rose-50 hover:text-rose-600 text-stone-500 flex items-center justify-center transition z-10">
        <i class="ph ph-x text-lg"></i>
      </button>

      <div class="md:w-5/12 flex flex-col space-y-3">
        <div class="relative rounded-2xl overflow-hidden aspect-[4/5] bg-stone-100 shadow-inner">
          <img id="modalProductImage" src="" alt="Hoa tươi Bloomora" class="w-full h-full object-cover">
          <span id="modalProductBadge" class="absolute top-3 left-3 bg-rose-600 text-white text-[10px] font-extrabold px-2.5 py-1 rounded-full uppercase shadow">
            HOT Bán Chạy
          </span>
        </div>
        <div class="bg-rose-50/70 border border-rose-100 p-3 rounded-2xl text-[11px] text-rose-800 space-y-1">
          <div class="font-bold flex items-center space-x-1.5">
            <i class="ph ph-camera text-base"></i>
            <span>Quy Trình Chụp Ảnh Duyệt Thật:</span>
          </div>
          <p class="text-stone-600 leading-relaxed text-[11px]">
            Trước khi giao, florist chụp cận cảnh gửi qua hệ thống. Quý khách xác nhận hài lòng mới tiến hành xuất hoa đi giao!
          </p>
        </div>
      </div>

      <div class="md:w-7/12 flex flex-col justify-between space-y-4">
        <div>
          <span id="modalProductCategory" class="text-[11px] font-bold uppercase tracking-wider text-rose-600">Hoa Khai Trương</span>
          <h3 id="modalProductName" class="font-serif-title text-xl sm:text-2xl font-bold text-stone-900 mt-1 leading-snug">Tên mẫu hoa</h3>
          <div class="flex items-baseline space-x-2 mt-2">
            <span id="modalProductPrice" class="text-2xl font-extrabold text-rose-600">1.850.000₫</span>
            <span id="modalProductOriginalPrice" class="text-xs text-stone-400 line-through">2.200.000₫</span>
            <span class="text-[10px] bg-rose-100 text-rose-700 font-bold px-2 py-0.5 rounded-full">-16%</span>
          </div>
          <p id="modalProductDesc" class="text-xs text-stone-600 mt-2 leading-relaxed bg-stone-50 p-3 rounded-xl border border-stone-100">
            Mô tả chi tiết loài hoa và phụ kiện đi kèm...
          </p>
        </div>

        <div>
          <label class="block text-xs font-bold text-stone-800 mb-1.5 flex items-center justify-between">
            <span class="flex items-center space-x-1">
              <i class="ph ph-clock text-rose-600 text-sm"></i>
              <span>1. Chọn Khung Giờ Giao Tận Nơi: *</span>
            </span>
            <span class="text-[10px] text-emerald-600 font-semibold bg-emerald-50 px-2 py-0.5 rounded-full">Đúng hẹn 100%</span>
          </label>
          <div class="grid grid-cols-2 sm:grid-cols-3 gap-1.5 text-[11px]" id="timeSlotContainer">
            <label class="flex items-center space-x-1.5 p-2 rounded-xl border border-stone-200 bg-stone-50 hover:bg-rose-50 cursor-pointer">
              <input type="radio" name="modalDeliverySlot" value="08:00 - 10:00" checked class="text-rose-600 focus:ring-rose-500">
              <span class="font-medium text-stone-700">08:00 - 10:00</span>
            </label>
            <label class="flex items-center space-x-1.5 p-2 rounded-xl border border-stone-200 bg-stone-50 hover:bg-rose-50 cursor-pointer">
              <input type="radio" name="modalDeliverySlot" value="10:00 - 12:00" class="text-rose-600 focus:ring-rose-500">
              <span class="font-medium text-stone-700">10:00 - 12:00</span>
            </label>
            <label class="flex items-center space-x-1.5 p-2 rounded-xl border border-stone-200 bg-stone-50 hover:bg-rose-50 cursor-pointer">
              <input type="radio" name="modalDeliverySlot" value="13:30 - 15:30" class="text-rose-600 focus:ring-rose-500">
              <span class="font-medium text-stone-700">13:30 - 15:30</span>
            </label>
            <label class="flex items-center space-x-1.5 p-2 rounded-xl border border-stone-200 bg-stone-50 hover:bg-rose-50 cursor-pointer">
              <input type="radio" name="modalDeliverySlot" value="15:30 - 17:30" class="text-rose-600 focus:ring-rose-500">
              <span class="font-medium text-stone-700">15:30 - 17:30</span>
            </label>
            <label class="flex items-center space-x-1.5 p-2 rounded-xl border border-stone-200 bg-stone-50 hover:bg-rose-50 cursor-pointer sm:col-span-2">
              <input type="radio" name="modalDeliverySlot" value="18:00 - 20:00" class="text-rose-600 focus:ring-rose-500">
              <span class="font-medium text-stone-700">18:00 - 20:00 (Tối tiệc)</span>
            </label>
          </div>
        </div>

        <!-- 2. Nội Dung In Băng Rôn Vi Tính (Tích hợp AI) -->
        <div>
          <div class="flex items-center justify-between mb-1.5">
            <label class="text-xs font-bold text-stone-800 flex items-center space-x-1">
              <i class="ph ph-ribbon text-rose-600 text-sm"></i>
              <span>2. Nội Dung In Băng Rôn Hoặc Thiệp:</span>
            </label>
            <button type="button" onclick="toggleAIWishesPanel()" class="inline-flex items-center space-x-1 text-[11px] font-bold text-rose-600 bg-rose-50 hover:bg-rose-100 border border-rose-200 px-2.5 py-1 rounded-xl transition cursor-pointer">
              <i class="ph-bold ph-sparkle text-rose-600"></i>
              <span>✨ AI Gợi Ý Lời Chúc</span>
            </button>
          </div>

          <div id="aiWishesBox" class="hidden mb-3 p-3 bg-gradient-to-br from-rose-50/90 to-stone-50 border border-rose-200 rounded-2xl space-y-2.5">
            <div class="flex items-center justify-between text-xs">
              <span class="font-bold text-stone-800 flex items-center space-x-1">
                <i class="ph-fill ph-magic-wand text-rose-600"></i>
                <span>Trợ Lý AI Soạn Câu Chúc Chuẩn Kích Thước Băng Rôn</span>
              </span>
              <button type="button" onclick="toggleAIWishesPanel()" class="text-stone-400 hover:text-stone-700 text-sm">
                <i class="ph ph-x"></i>
              </button>
            </div>
            <div class="grid grid-cols-2 gap-2 text-[11px]">
              <div>
                <span class="text-stone-500 font-medium block mb-1">Dịp tặng hoa:</span>
                <select id="aiWishOccasion" onchange="generateAIWishes()" class="w-full bg-white border border-stone-200 rounded-lg p-1.5 text-xs text-stone-800 font-semibold focus:outline-none focus:border-rose-500">
                  <option value="Khai Trương">Khai Trương Hồng Phát</option>
                  <option value="Sinh Nhật">Chúc Mừng Sinh Nhật</option>
                  <option value="Tình Yêu">Tình Yêu & Kỷ Niệm</option>
                  <option value="Chia Buồn">Kính Viếng & Chia Buồn</option>
                </select>
              </div>
              <div>
                <span class="text-stone-500 font-medium block mb-1">Giọng điệu:</span>
                <select id="aiWishTone" onchange="generateAIWishes()" class="w-full bg-white border border-stone-200 rounded-lg p-1.5 text-xs text-stone-800 font-semibold focus:outline-none focus:border-rose-500">
                  <option value="trang_trong">Trang trọng & Phát tài</option>
                  <option value="hai_huoc">Thân thiết & Hài hước</option>
                  <option value="ngot_ngao">Ngọt ngào & Tình cảm</option>
                  <option value="kinh_can">Kính cẩn & Thành kính</option>
                </select>
              </div>
            </div>
            <div id="aiWishList" class="space-y-1.5 pt-1"></div>
          </div>

          <textarea id="modalRibbonText" rows="2" placeholder="Ví dụ: CÔNG TY XÂY DỰNG ĐẮK LẮK CHÚC MỪNG KHAI TRƯƠNG HỒNG PHÁT"
            class="w-full bg-stone-50 border border-stone-200 rounded-xl p-2.5 text-xs text-stone-800 focus:bg-white focus:border-rose-500 focus:outline-none transition"></textarea>
          <div class="text-[10px] text-stone-400 mt-1 flex items-center justify-between">
            <span class="flex items-center space-x-1"><i class="ph ph-info"></i><span>In vi tính sắc nét trên dải ruy-băng đỏ viền vàng</span></span>
            <span class="text-rose-600 font-semibold">Tối đa 45 ký tự</span>
          </div>
        </div>

        <div class="pt-2 border-t border-stone-100 flex flex-col sm:flex-row items-center justify-between gap-3">
          <div class="flex items-center border border-stone-200 rounded-xl bg-stone-50 p-1">
            <button type="button" onclick="changeModalQty(-1)" class="w-8 h-8 rounded-lg bg-white hover:bg-stone-100 text-stone-700 font-bold flex items-center justify-center shadow-sm text-sm cursor-pointer">-</button>
            <input type="number" id="modalQtyInput" value="1" min="1" max="99" readonly class="w-12 text-center text-xs font-bold bg-transparent border-0 focus:outline-none text-stone-900">
            <button type="button" onclick="changeModalQty(1)" class="w-8 h-8 rounded-lg bg-white hover:bg-stone-100 text-stone-700 font-bold flex items-center justify-center shadow-sm text-sm cursor-pointer">+</button>
          </div>
          <div class="flex items-center space-x-2 w-full sm:w-auto flex-1 justify-end">
            <button type="button" onclick="submitModalAddToCart(false)" class="flex-1 sm:flex-initial bg-stone-100 hover:bg-stone-200 text-stone-800 font-bold text-xs px-4 py-3 rounded-xl transition flex items-center justify-center space-x-1.5 cursor-pointer">
              <i class="ph ph-shopping-bag text-base text-rose-600"></i>
              <span>Thêm Vào Giỏ</span>
            </button>
            <button type="button" onclick="submitModalAddToCart(true)" class="flex-1 sm:flex-initial bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs px-5 py-3 rounded-xl shadow-md shadow-rose-600/30 transition flex items-center justify-center space-x-1.5 cursor-pointer">
              <i class="ph ph-paper-plane-tilt text-base"></i>
              <span>Đặt Giao Ngay</span>
            </button>
          </div>
        </div>

      </div>

    </div>
  </div>

  <!-- ================= GIỎ HÀNG SLIDE-OVER DRAWER ================= -->
  <div id="cartDrawer" onclick="if(event.target===this)closeCartDrawer()" class="hidden fixed inset-0 z-50 overflow-hidden bg-stone-900/60 backdrop-blur-sm transition-opacity">
    <div onclick="if(event.target===this)closeCartDrawer()" class="absolute inset-y-0 right-0 max-w-full flex pl-10">
      <div class="w-screen max-w-md bg-white shadow-2xl flex flex-col">
        
        <div class="p-5 border-b border-stone-200 flex items-center justify-between bg-stone-50">
          <div class="flex items-center space-x-2">
            <div class="w-8 h-8 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center">
              <i class="ph ph-shopping-bag text-lg"></i>
            </div>
            <h3 class="font-serif-title text-base font-bold text-stone-900">Giỏ Hoa Của Bạn</h3>
          </div>
          <button onclick="closeCartDrawer()" class="w-8 h-8 rounded-full hover:bg-stone-200 text-stone-500 flex items-center justify-center transition cursor-pointer">
            <i class="ph ph-x text-base"></i>
          </button>
        </div>

        <div id="cartDrawerList" class="flex-1 overflow-y-auto p-5 space-y-4"></div>

        <!-- Thông Tin Giao Nhận Tại Đắk Lắk -->
        <div class="px-5 py-3 bg-stone-100/70 border-t border-stone-200 text-xs space-y-2.5">
          <div class="flex items-center justify-between font-bold text-stone-800">
            <span class="flex items-center space-x-1">
              <i class="ph ph-map-pin text-rose-600"></i>
              <span>Thông Tin Giao Hoa Tại Đắk Lắk</span>
            </span>
            <span class="text-[10px] text-stone-500 font-normal">Buôn Ma Thuột & lân cận</span>
          </div>
          <div class="grid grid-cols-2 gap-2">
            <div>
              <label class="block text-[10px] font-semibold text-stone-500 mb-0.5">Người gửi: *</label>
              <input type="text" id="cartSenderName" placeholder="Tên bạn" value="<?php echo e($userName); ?>"
                class="w-full bg-white border border-stone-200 rounded-lg px-2 py-1.5 text-xs text-stone-800 focus:outline-none focus:border-rose-500">
            </div>
            <div>
              <label class="block text-[10px] font-semibold text-stone-500 mb-0.5">SĐT người gửi: *</label>
              <input type="tel" id="cartSenderPhone" placeholder="0912345678" value="0912345678"
                class="w-full bg-white border border-stone-200 rounded-lg px-2 py-1.5 text-xs text-stone-800 focus:outline-none focus:border-rose-500">
            </div>
          </div>
          <div class="grid grid-cols-2 gap-2">
            <div>
              <label class="block text-[10px] font-semibold text-stone-500 mb-0.5">Người nhận: *</label>
              <input type="text" id="cartReceiverName" placeholder="Tên người nhận"
                class="w-full bg-white border border-stone-200 rounded-lg px-2 py-1.5 text-xs text-stone-800 focus:outline-none focus:border-rose-500">
            </div>
            <div>
              <label class="block text-[10px] font-semibold text-stone-500 mb-0.5">SĐT người nhận: *</label>
              <input type="tel" id="cartReceiverPhone" placeholder="09xxxxxxxx"
                class="w-full bg-white border border-stone-200 rounded-lg px-2 py-1.5 text-xs text-stone-800 focus:outline-none focus:border-rose-500">
            </div>
          </div>
          <div>
            <label class="block text-[10px] font-semibold text-stone-500 mb-0.5">Địa chỉ giao hoa chi tiết: *</label>
            <input type="text" id="cartDeliveryAddress" placeholder="Ví dụ: 120 Phan Chu Trinh, P. Thắng Lợi, TP. Buôn Ma Thuột"
              class="w-full bg-white border border-stone-200 rounded-lg px-2 py-1.5 text-xs text-stone-800 focus:outline-none focus:border-rose-500">
          </div>
          <label class="flex items-center space-x-1.5 text-[11px] text-stone-600 cursor-pointer pt-0.5">
            <input type="checkbox" id="cartIsAnonymous" class="w-3.5 h-3.5 text-rose-600 rounded border-stone-300 focus:ring-rose-500">
            <span class="font-medium">Gửi tặng ẩn danh (Không in tên người gửi lên thiệp/phiếu giao hoa)</span>
          </label>
        </div>

        <!-- Ô Nhập Mã Giảm Giá Voucher (US16) -->
        <div class="px-5 py-2.5 bg-stone-50 border-t border-stone-200 text-xs">
          <div class="flex items-center space-x-2">
            <input type="text" id="cartVoucherInput" placeholder="Nhập mã voucher (BMT50K, BLOOMNEW10)..."
              class="flex-1 bg-white border border-stone-200 rounded-xl px-3 py-1.5 text-xs uppercase font-mono font-bold text-stone-800 focus:outline-none focus:border-rose-500">
            <button type="button" onclick="applyCartVoucher()" class="bg-stone-900 hover:bg-rose-600 text-white font-bold text-xs px-3 py-1.5 rounded-xl transition cursor-pointer flex-shrink-0">
              Áp Dụng
            </button>
          </div>
          <div id="voucherNoticeBox" class="hidden mt-1 text-[11px] text-emerald-600 font-semibold flex items-center justify-between">
            <span id="voucherNoticeText"><i class="ph-bold ph-check"></i> Đã áp dụng mã BMT50K: -50.000₫</span>
            <button type="button" onclick="removeCartVoucher()" class="text-stone-400 hover:text-rose-600 text-xs underline cursor-pointer">Bỏ mã</button>
          </div>
        </div>

        <!-- Footer Drawer & Thanh Toán -->
        <div class="p-5 border-t border-stone-200 bg-stone-50 space-y-3">
          <div class="space-y-1.5 text-xs">
            <div class="flex items-center justify-between text-stone-500">
              <span>Tạm tính hoa tươi:</span>
              <span id="cartDrawerSubtotal" class="font-semibold text-stone-800">0₫</span>
            </div>
            <div id="cartDiscountRow" class="hidden flex items-center justify-between text-emerald-600 font-bold">
              <span>Giảm giá Voucher:</span>
              <span id="cartDrawerDiscount">-0₫</span>
            </div>
            <div class="flex items-center justify-between text-stone-500">
              <span>Băng rôn vi tính & Thiệp:</span>
              <span class="font-bold text-emerald-600">Miễn Phí (0₫)</span>
            </div>
            <div class="flex items-center justify-between text-stone-500">
              <span>Chụp ảnh duyệt hoa thật:</span>
              <span class="font-bold text-emerald-600">Miễn Phí (0₫)</span>
            </div>
            <div class="pt-2 border-t border-stone-200 flex items-center justify-between font-bold text-stone-900 text-base">
              <span>Tổng thanh toán:</span>
              <span id="cartDrawerTotal" class="text-rose-600 font-extrabold text-lg">0₫</span>
            </div>
          </div>

          <button onclick="checkoutCart()" class="w-full bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs py-3.5 px-4 rounded-xl shadow-lg shadow-rose-600/30 transition flex items-center justify-center space-x-2 cursor-pointer">
            <i class="ph ph-qr-code text-base"></i>
            <span>Thanh Toán VietQR & Đặt Hoa</span>
          </button>
        </div>

      </div>
    </div>
  </div>

  <!-- ================= MODAL TRA CỨU ĐƠN HÀNG (MÃ ĐƠN + SĐT) ================= -->
  <div id="trackOrderModal" onclick="if(event.target===this)closeTrackOrderModal()" class="hidden fixed inset-0 z-50 overflow-y-auto bg-stone-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-xl w-full p-6 sm:p-7 shadow-2xl border border-stone-200 relative max-h-[92vh] overflow-y-auto space-y-5">
      
      <button onclick="closeTrackOrderModal()" class="absolute top-4 right-4 w-9 h-9 rounded-full bg-stone-100 hover:bg-rose-50 hover:text-rose-600 text-stone-500 flex items-center justify-center transition">
        <i class="ph ph-x text-lg"></i>
      </button>

      <div>
        <div class="inline-flex items-center space-x-1.5 text-xs font-bold text-rose-600 uppercase tracking-wider bg-rose-50 px-2.5 py-1 rounded-full mb-1.5">
          <i class="ph ph-shield-check"></i>
          <span>Bảo Mật Thông Tin Đơn Hàng</span>
        </div>
        <h3 class="font-serif-title text-xl font-bold text-stone-900">Tra Cứu Tiến Độ & Duyệt Ảnh Hoa Thật</h3>
        <p class="text-xs text-stone-500 mt-0.5">Nhập chính xác Mã đơn hàng và Số điện thoại người đặt để theo dõi quy trình 5 giai đoạn.</p>
      </div>

      <form onsubmit="handleTrackOrder(event)" class="bg-stone-50 p-4 rounded-2xl border border-stone-200/80 space-y-3">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-[11px] font-bold text-stone-700 mb-1">Mã đơn hàng: *</label>
            <input type="text" id="trackOrderCode" required placeholder="Ví dụ: BL89241"
              class="w-full bg-white border border-stone-200 rounded-xl px-3 py-2 text-xs font-mono font-bold text-rose-600 uppercase focus:border-rose-500 focus:outline-none">
          </div>
          <div>
            <label class="block text-[11px] font-bold text-stone-700 mb-1">Số điện thoại người đặt: *</label>
            <input type="tel" id="trackOrderPhone" required placeholder="Ví dụ: 0912345678"
              class="w-full bg-white border border-stone-200 rounded-xl px-3 py-2 text-xs font-semibold text-stone-800 focus:border-rose-500 focus:outline-none">
          </div>
        </div>
        <button type="submit" class="w-full bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs py-2.5 rounded-xl shadow-md shadow-rose-600/20 transition flex items-center justify-center space-x-1.5 cursor-pointer">
          <i class="ph ph-magnifying-glass text-base"></i>
          <span>Tra Cứu Ngay</span>
        </button>
      </form>

      <div id="trackResultBox" class="hidden space-y-4 pt-1">
        <div class="p-3.5 bg-rose-50/60 border border-rose-200 rounded-2xl text-xs space-y-1.5">
          <div class="flex items-center justify-between font-bold">
            <span class="text-stone-900" id="trackResultFlowerName">Kệ Khai Trương Đại Cát Hồng Phát</span>
            <span class="text-rose-600 font-mono text-sm" id="trackResultCode">BL89241</span>
          </div>
          <div class="text-stone-600 flex items-center space-x-1">
            <i class="ph ph-map-pin text-rose-500"></i>
            <span id="trackResultAddress">120 Phan Chu Trinh, P. Thắng Lợi, TP. Buôn Ma Thuột</span>
          </div>
          <div class="text-stone-600 flex items-center justify-between">
            <span>Khung giờ giao: <strong id="trackResultSlot" class="text-stone-800">15:30 - 17:30</strong></span>
            <span id="trackResultPayBadge" class="bg-emerald-100 text-emerald-800 font-bold px-2 py-0.5 rounded-full text-[10px]">Đã thanh toán VietQR</span>
          </div>
        </div>

        <div>
          <h4 class="text-xs font-bold text-stone-800 uppercase tracking-wider mb-3">Tiến độ quy trình 5 bước:</h4>
          <div class="grid grid-cols-5 gap-1.5 text-center text-[10px]">
            <div id="step1Dot" class="p-2 rounded-xl bg-emerald-100 text-emerald-800 font-bold">
              <i class="ph ph-check-circle text-base block mb-0.5"></i>
              <span>1. Đã nhận</span>
            </div>
            <div id="step2Dot" class="p-2 rounded-xl bg-emerald-100 text-emerald-800 font-bold">
              <i class="ph ph-check-circle text-base block mb-0.5"></i>
              <span>2. Đang cắm</span>
            </div>
            <div id="step3Dot" class="p-2 rounded-xl bg-amber-100 text-amber-800 font-bold ring-2 ring-amber-400">
              <i class="ph ph-camera text-base block mb-0.5"></i>
              <span>3. Duyệt ảnh</span>
            </div>
            <div id="step4Dot" class="p-2 rounded-xl bg-stone-100 text-stone-400 font-semibold">
              <i class="ph ph-moped text-base block mb-0.5"></i>
              <span>4. Đang giao</span>
            </div>
            <div id="step5Dot" class="p-2 rounded-xl bg-stone-100 text-stone-400 font-semibold">
              <i class="ph ph-gift text-base block mb-0.5"></i>
              <span>5. Hoàn tất</span>
            </div>
          </div>
        </div>

        <div id="trackPhotoProofBox" class="p-4 bg-stone-50 border border-stone-200 rounded-2xl space-y-3">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-stone-900 flex items-center space-x-1">
              <i class="ph-fill ph-camera text-rose-600 text-base"></i>
              <span>Ảnh Hoa Thực Tế Tại Xưởng (Photo Proof):</span>
            </span>
            <span id="photoStatusBadge" class="text-[10px] bg-amber-500 text-white font-bold px-2 py-0.5 rounded-full">Chờ bạn duyệt</span>
          </div>
          
          <div class="relative rounded-2xl overflow-hidden border border-stone-200 max-h-56">
            <img id="trackPhotoImg" src="" alt="Photo Proof" class="w-full h-56 object-cover">
          </div>

          <div id="photoActionButtons" class="flex items-center gap-2 pt-1">
            <button onclick="approvePhotoProof()" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs py-2.5 rounded-xl transition flex items-center justify-center space-x-1 cursor-pointer">
              <i class="ph-bold ph-check"></i>
              <span>Duyệt Hoa (Đẹp Rồi, Giao Ngay)</span>
            </button>
            <button onclick="requestPhotoRevision()" class="flex-1 bg-stone-100 hover:bg-stone-200 text-stone-800 font-bold text-xs py-2.5 rounded-xl border border-stone-200 transition flex items-center justify-center space-x-1 cursor-pointer">
              <i class="ph ph-note-pencil"></i>
              <span>Yêu Cầu Chỉnh Sửa</span>
            </button>
          </div>
        </div>

      </div>
    </div>
  </div>

  <!-- ================= MODAL THANH TOÁN VIETQR NAPAS ================= -->
  <div id="vietQRModal" onclick="if(event.target===this)closeVietQRModal()" class="hidden fixed inset-0 z-50 overflow-y-auto bg-stone-900/70 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-7 shadow-2xl border border-stone-200 text-center space-y-4 relative">
      <button onclick="closeVietQRModal()" class="absolute top-4 right-4 w-8 h-8 rounded-full bg-stone-100 hover:bg-rose-50 hover:text-rose-600 text-stone-500 flex items-center justify-center transition">
        <i class="ph ph-x text-base"></i>
      </button>

      <div>
        <div class="inline-flex items-center space-x-1.5 text-xs font-bold text-rose-600 uppercase tracking-wider bg-rose-50 px-3 py-1 rounded-full mb-1">
          <i class="ph ph-qr-code text-base"></i>
          <span>Thanh Toán VietQR Napas Tự Động</span>
        </div>
        <h3 class="font-serif-title text-xl font-bold text-stone-900">Quét Mã Chuyển Khoản Đặt Hoa</h3>
        <p class="text-xs text-stone-500 mt-0.5">Mở App Ngân hàng bất kỳ (Vietcombank, MB, Techcombank...) để quét mã.</p>
      </div>

      <div class="p-3 bg-stone-50 rounded-2xl border border-stone-200 inline-block shadow-inner mx-auto">
        <img id="vietQRImage" src="" alt="Mã VietQR Bloomora" class="w-56 h-56 mx-auto object-contain rounded-xl">
      </div>

      <div class="bg-stone-50 p-3.5 rounded-2xl border border-stone-200 text-left text-xs space-y-2">
        <div class="flex items-center justify-between">
          <span class="text-stone-500">Ngân hàng:</span>
          <span class="font-bold text-stone-900">MB Bank (Quân Đội)</span>
        </div>
        <div class="flex items-center justify-between">
          <span class="text-stone-500">Số tài khoản:</span>
          <div class="flex items-center space-x-1.5">
            <span class="font-mono font-bold text-stone-900">0946618960</span>
            <button onclick="copyText('0946618960', 'Đã chép số tài khoản!')" class="text-rose-600 hover:underline text-[11px] font-semibold cursor-pointer">Chép</button>
          </div>
        </div>
        <div class="flex items-center justify-between">
          <span class="text-stone-500">Chủ tài khoản:</span>
          <span class="font-bold text-stone-900">TIEM HOA BLOOMORA DAK LAK</span>
        </div>
        <div class="flex items-center justify-between">
          <span class="text-stone-500">Số tiền:</span>
          <div class="flex items-center space-x-1.5">
            <span id="vietQRAmount" class="font-bold text-rose-600 text-sm">0₫</span>
            <button onclick="copyText(document.getElementById('vietQRAmount').textContent.replace(/[^\d]/g,''), 'Đã chép số tiền!')" class="text-rose-600 hover:underline text-[11px] font-semibold cursor-pointer">Chép</button>
          </div>
        </div>
        <div class="flex items-center justify-between pt-1 border-t border-stone-200">
          <span class="text-stone-500 font-bold">Nội dung chuyển:</span>
          <div class="flex items-center space-x-1.5">
            <span id="vietQRContent" class="font-mono font-extrabold text-rose-600 bg-rose-50 px-2 py-0.5 rounded-md">BL89241</span>
            <button onclick="copyText(document.getElementById('vietQRContent').textContent, 'Đã chép nội dung chuyển khoản!')" class="text-rose-600 hover:underline text-[11px] font-semibold cursor-pointer">Chép</button>
          </div>
        </div>
      </div>

      <div class="space-y-2 pt-1">
        <button onclick="handleFinishVietQRPayment()" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs py-3 rounded-xl shadow-md transition flex items-center justify-center space-x-1.5 cursor-pointer">
          <i class="ph-bold ph-check"></i>
          <span>Tôi Đã Hoàn Tất Chuyển Khoản</span>
        </button>
        <button onclick="closeVietQRModal()" class="w-full text-stone-400 hover:text-stone-600 text-xs font-semibold py-1 transition cursor-pointer">
          Đóng cửa sổ này
        </button>
      </div>

    </div>
  </div>

  <!-- ================= MODAL HỒ SƠ KHÁCH HÀNG & LỊCH SỬ ĐƠN HÀNG ================= -->
  <div id="customerProfileModal" onclick="if(event.target===this)closeCustomerProfileModal()" class="hidden fixed inset-0 z-50 overflow-y-auto bg-stone-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-2xl w-full p-6 sm:p-7 shadow-2xl border border-stone-200 relative max-h-[92vh] overflow-y-auto space-y-4">
      <button onclick="closeCustomerProfileModal()" class="absolute top-4 right-4 w-9 h-9 rounded-full bg-stone-100 hover:bg-rose-50 hover:text-rose-600 text-stone-500 flex items-center justify-center transition">
        <i class="ph ph-x text-lg"></i>
      </button>

      <div class="flex items-center space-x-3 pb-3 border-b border-stone-200">
        <div class="w-12 h-12 rounded-2xl bg-rose-600 text-white font-bold flex items-center justify-center text-lg shadow-md shadow-rose-600/30">
          <i class="ph ph-user"></i>
        </div>
        <div>
          <h3 id="profileName" class="font-serif-title text-xl font-bold text-stone-900"><?php echo e($userName); ?></h3>
          <p id="profileEmail" class="text-xs text-stone-400 font-medium"><?php echo e($userName); ?>@gmail.com</p>
        </div>
      </div>

      <div class="flex space-x-2 border-b border-stone-200 text-xs font-bold pb-2">
        <button onclick="switchProfileTab('orders')" id="tabBtnOrders" class="px-3.5 py-1.5 rounded-xl bg-rose-600 text-white transition cursor-pointer">
          Lịch Sử Đơn Hàng Của Tôi
        </button>
        <button onclick="switchProfileTab('info')" id="tabBtnInfo" class="px-3.5 py-1.5 rounded-xl text-stone-600 hover:bg-stone-100 transition cursor-pointer">
          Sổ Địa Chỉ & Thông Tin
        </button>
      </div>

      <div id="profileTabOrders" class="space-y-3 pt-1">
        <div id="customerOrdersList" class="space-y-3"></div>
      </div>

      <div id="profileTabInfo" class="hidden space-y-3 pt-1 text-xs">
        <div class="bg-stone-50 p-4 rounded-2xl border border-stone-200 space-y-3">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-[11px] font-bold text-stone-500 mb-1">Họ và tên:</label>
              <input type="text" id="infoFullName" value="<?php echo e($userName); ?>" class="w-full bg-white border border-stone-200 rounded-xl px-3 py-2 text-xs font-semibold text-stone-800">
            </div>
            <div>
              <label class="block text-[11px] font-bold text-stone-500 mb-1">Số điện thoại:</label>
              <input type="tel" id="infoPhone" value="0912345678" class="w-full bg-white border border-stone-200 rounded-xl px-3 py-2 text-xs font-semibold text-stone-800">
            </div>
          </div>
          <div>
            <label class="block text-[11px] font-bold text-stone-500 mb-1">Địa chỉ mặc định tại Buôn Ma Thuột / Đắk Lắk:</label>
            <input type="text" id="infoAddress" value="120 Phan Chu Trinh, P. Thắng Lợi, TP. Buôn Ma Thuột, Đắk Lắk" class="w-full bg-white border border-stone-200 rounded-xl px-3 py-2 text-xs font-semibold text-stone-800">
          </div>
          <button onclick="saveCustomerProfileInfo()" class="bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs px-4 py-2 rounded-xl transition cursor-pointer">
            Lưu Thay Đổi
          </button>
        </div>
      </div>

    </div>
  </div>

  <!-- ================= TRỢ LÝ AI TƯ VẤN HOA ================= -->
  <div id="aiChatbotWindow" class="hidden fixed bottom-36 right-4 sm:right-6 z-50 w-[calc(100vw-32px)] sm:w-96 max-h-[75vh] bg-white rounded-3xl shadow-2xl border border-stone-200 overflow-hidden flex flex-col transition-all">
    <div class="bg-gradient-to-r from-rose-600 to-rose-700 text-white p-4 flex items-center justify-between">
      <div class="flex items-center space-x-2.5">
        <div class="w-9 h-9 rounded-2xl bg-white/20 flex items-center justify-center text-white text-lg shadow-inner">
          <i class="ph-bold ph-sparkle"></i>
        </div>
        <div>
          <h4 class="font-bold text-xs sm:text-sm leading-tight">Trợ Lý AI Bloomora</h4>
          <span class="text-[10px] text-rose-100 flex items-center space-x-1">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 inline-block"></span>
            <span>Tư vấn theo CSDL hoa Đắk Lắk</span>
          </span>
        </div>
      </div>
      <button onclick="toggleAIChatbot()" class="w-7 h-7 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition">
        <i class="ph ph-x text-sm"></i>
      </button>
    </div>

    <div class="bg-stone-50 p-2.5 border-b border-stone-200 text-[11px] space-y-1.5">
      <div class="flex items-center space-x-1 overflow-x-auto no-scrollbar pb-1">
        <span class="text-stone-400 text-[10px] font-bold flex-shrink-0 uppercase mr-1">Dịp:</span>
        <button onclick="quickAIChat('Hoa Khai Trương', 'Đối tác', 2000000)" class="flex-shrink-0 bg-white hover:bg-rose-50 hover:text-rose-600 border border-stone-200 px-2 py-1 rounded-lg font-semibold transition text-stone-700">🎉 Khai Trương</button>
        <button onclick="quickAIChat('Hoa Sinh Nhật', 'Bạn bè', 700000)" class="flex-shrink-0 bg-white hover:bg-rose-50 hover:text-rose-600 border border-stone-200 px-2 py-1 rounded-lg font-semibold transition text-stone-700">🎂 Sinh Nhật</button>
        <button onclick="quickAIChat('Hoa Tình Yêu & Hoa Hồng', 'Người yêu', 1200000)" class="flex-shrink-0 bg-white hover:bg-rose-50 hover:text-rose-600 border border-stone-200 px-2 py-1 rounded-lg font-semibold transition text-stone-700">❤️ Tỏ Tình / Vợ</button>
        <button onclick="quickAIChat('Lan Hồ Điệp', 'Sếp / Đối tác', 2500000)" class="flex-shrink-0 bg-white hover:bg-rose-50 hover:text-rose-600 border border-stone-200 px-2 py-1 rounded-lg font-semibold transition text-stone-700">👑 Lan Hồ Điệp</button>
      </div>
    </div>

    <div id="aiChatMessages" class="flex-1 p-3.5 overflow-y-auto space-y-3 max-h-80 text-xs">
      <div class="flex items-start space-x-2">
        <div class="w-6 h-6 rounded-full bg-rose-600 text-white flex items-center justify-center text-xs flex-shrink-0 mt-0.5">
          <i class="ph-bold ph-sparkle"></i>
        </div>
        <div class="bg-stone-100 text-stone-800 p-3 rounded-2xl rounded-tl-sm max-w-[85%] leading-relaxed shadow-sm">
          Dạ em chào quý khách! Em là Trợ lý AI Bloomora. Em có thể tư vấn mẫu hoa tươi sẵn có tại tiệm Buôn Ma Thuột theo ngân sách và dịp tặng của quý khách. Em cam kết chỉ báo đúng giá và mẫu hoa có thật tại tiệm ạ! 🌸
        </div>
      </div>
    </div>

    <form onsubmit="handleSendAIChat(event)" class="p-2.5 bg-white border-t border-stone-200 flex items-center space-x-2">
      <input type="text" id="aiChatInput" placeholder="Ví dụ: Tìm hoa tặng sinh nhật người yêu dưới 1 triệu..."
        class="flex-1 bg-stone-50 border border-stone-200 rounded-xl px-3 py-2 text-xs text-stone-800 focus:bg-white focus:border-rose-500 focus:outline-none">
      <button type="submit" class="w-9 h-9 rounded-xl bg-rose-600 hover:bg-rose-700 text-white flex items-center justify-center shadow-md shadow-rose-600/30 transition flex-shrink-0">
        <i class="ph-bold ph-paper-plane-right"></i>
      </button>
    </form>
  </div>

  <!-- Toast thông báo nhỏ -->
  <div id="toastNotification" class="fixed bottom-5 right-5 z-50 hidden bg-stone-900 text-white text-xs font-semibold px-4 py-3 rounded-2xl shadow-xl flex items-center space-x-2 transition transform duration-300">
    <i class="ph ph-check-circle text-emerald-400 text-base"></i>
    <span id="toastMessage">Đã thêm vào giỏ hàng!</span>
  </div>

  <!-- ================= MODAL XEM CHI TIẾT CHÍNH SÁCH ================= -->
  <div id="policyModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-stone-900/60 backdrop-blur-sm p-4">
    <div class="bg-white w-full max-w-lg rounded-3xl p-6 sm:p-7 border border-stone-200 shadow-2xl space-y-4 animate-in fade-in zoom-in-95 duration-200">
      <div class="flex items-center justify-between border-b border-stone-100 pb-3">
        <h3 id="policyModalTitle" class="font-bold text-sm text-stone-900 flex items-center">
          <!-- Render tiêu đề chính sách -->
        </h3>
        <button onclick="closePolicyModal()" class="text-stone-400 hover:text-stone-700 text-lg p-1">
          <i class="ph ph-x"></i>
        </button>
      </div>
      <div id="policyModalContent" class="py-1">
        <!-- Render nội dung chính sách -->
      </div>
      <div class="flex justify-end pt-3 border-t border-stone-100">
        <button onclick="closePolicyModal()" class="bg-stone-900 hover:bg-stone-800 text-white text-xs font-bold px-5 py-2.5 rounded-xl transition cursor-pointer">
          Đã Hiểu & Đóng
        </button>
      </div>
    </div>
  </div>

  <!-- ================= FOOTER CAO CẤP BLOOMORA FLORIST ================= -->
  <footer class="bg-[#181615] text-stone-300 pt-16 pb-12 border-t border-stone-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      
      <!-- 4 Cột Nội Dung Chuẩn Như Thiết Kế -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-8 lg:gap-10 pb-12">
        
        <!-- Cột 1: Thương hiệu & Hệ thống địa chỉ (5 cột) -->
        <div class="lg:col-span-5 space-y-4">
          <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-2xl bg-rose-600 flex items-center justify-center text-white shadow-md shadow-rose-600/30">
              <i class="ph ph-flower-lotus text-2xl"></i>
            </div>
            <span class="font-serif-title text-2xl font-bold tracking-wide text-white">BLOOMORA FLORIST</span>
          </div>

          <p class="text-xs text-stone-400 leading-relaxed pr-4">
            Nền tảng thương mại điện tử hoa tươi cao cấp hàng đầu Việt Nam. Kết nối tình cảm, mang niềm vui và vượng khí đến hàng triệu gia đình và doanh nghiệp trên 63 tỉnh thành.
          </p>

          <div class="space-y-2 text-xs text-stone-400 pt-1">
            <div class="flex items-start space-x-2">
              <i class="ph ph-map-pin text-rose-500 text-sm mt-0.5 flex-shrink-0"></i>
              <span><strong class="text-stone-300">Đắk Lắk (Trụ sở chính):</strong> 120 Phan Chu Trinh, P. Thắng Lợi, TP. Buôn Ma Thuột</span>
            </div>
            <div class="flex items-start space-x-2">
              <i class="ph ph-map-pin text-rose-500 text-sm mt-0.5 flex-shrink-0"></i>
              <span><strong class="text-stone-300">TP. HCM:</strong> 352 Nguyễn Đình Chiểu, Phường 4, Quận 3</span>
            </div>
            <div class="flex items-start space-x-2">
              <i class="ph ph-map-pin text-rose-500 text-sm mt-0.5 flex-shrink-0"></i>
              <span><strong class="text-stone-300">Hà Nội:</strong> 128 Phố Huế, P. Hàng Bài, Q. Hoàn Kiếm</span>
            </div>
            <div class="flex items-start space-x-2">
              <i class="ph ph-map-pin text-rose-500 text-sm mt-0.5 flex-shrink-0"></i>
              <span><strong class="text-stone-300">Đà Nẵng:</strong> 86 Nguyễn Văn Linh, P. Nam Dương, Q. Hải Châu</span>
            </div>
          </div>
        </div>

        <!-- Cột 2: DANH MỤC HOA (2 cột) -->
        <div class="lg:col-span-2 space-y-3">
          <h4 class="text-xs font-bold uppercase tracking-wider text-white">DANH MỤC HOA</h4>
          <ul class="space-y-2 text-xs text-stone-400">
            <li><a href="#productsSection" onclick="filterCategory('khai-truong')" class="hover:text-rose-400 transition">Hoa Khai Trương</a></li>
            <li><a href="#productsSection" onclick="filterCategory('sinh-nhat')" class="hover:text-rose-400 transition">Hoa Chúc Mừng Sinh Nhật</a></li>
            <li><a href="#productsSection" onclick="filterCategory('tinh-yeu')" class="hover:text-rose-400 transition">Bó Hoa Tình Yêu / Cầu Hôn</a></li>
            <li><a href="#productsSection" onclick="filterCategory('lan-ho-diep')" class="hover:text-rose-400 transition">Lan Hồ Điệp Hoàng Gia</a></li>
            <li><a href="#productsSection" onclick="filterCategory('chia-buon')" class="hover:text-rose-400 transition">Hoa Tang Lễ / Viếng Phân Ưu</a></li>
          </ul>
        </div>

        <!-- Cột 3: CHÍNH SÁCH & HỖ TRỢ (2.5 cột) -->
        <div class="lg:col-span-2 space-y-3">
          <h4 class="text-xs font-bold uppercase tracking-wider text-white">CHÍNH SÁCH & HỖ TRỢ</h4>
          <ul class="space-y-2 text-xs text-stone-400">
            <li><button type="button" onclick="openTrackOrderModal()" class="hover:text-rose-400 transition text-left cursor-pointer">Tra cứu trạng thái đơn hoa</button></li>
            <li><button type="button" onclick="openPolicyModal('delivery')" class="hover:text-rose-400 transition text-left cursor-pointer">Chính sách giao hỏa tốc 60 phút</button></li>
            <li><button type="button" onclick="openPolicyModal('refund')" class="hover:text-rose-400 transition text-left cursor-pointer">Chính sách hoàn tiền & đổi mới 100%</button></li>
            <li><button type="button" onclick="openPolicyModal('payment')" class="hover:text-rose-400 transition text-left cursor-pointer">Hướng dẫn thanh toán VietQR / MoMo</button></li>
            <li><button type="button" onclick="openPolicyModal('vat')" class="hover:text-rose-400 transition text-left cursor-pointer">Xuất hóa đơn điện tử VAT Doanh nghiệp</button></li>
          </ul>
        </div>

        <!-- Cột 4: TỔNG ĐÀI PHỤC VỤ & THANH TOÁN (2.5 cột) -->
        <div class="lg:col-span-3 space-y-4">
          <h4 class="text-xs font-bold uppercase tracking-wider text-white">TỔNG ĐÀI PHỤC VỤ</h4>
          
          <!-- Hộp thông tin liên hệ nổi bật -->
          <div class="bg-stone-900/90 p-4 rounded-2xl border border-stone-800 space-y-3">
            <div>
              <span class="text-[11px] text-stone-400 block">Hotline 24/7 (Miễn phí cước):</span>
              <a href="tel:19006868" class="text-rose-500 hover:text-rose-400 font-bold text-lg font-mono tracking-tight block">1900 6868</a>
            </div>
            <div>
              <span class="text-[11px] text-stone-400 block">Zalo Hỗ Trợ Nhanh:</span>
              <a href="https://zalo.me/0908123456" target="_blank" class="text-sky-400 hover:text-sky-300 font-bold text-sm font-mono tracking-tight block">0908 123 456</a>
            </div>
          </div>

          <!-- Chấp nhận thanh toán -->
          <div class="space-y-2">
            <span class="text-[11px] text-stone-400 block">Chấp nhận thanh toán:</span>
            <div class="flex items-center space-x-1.5 flex-wrap gap-y-1.5">
              <span class="bg-stone-800/90 text-stone-300 border border-stone-700/80 px-2.5 py-1 rounded-lg text-xs font-bold">VietQR</span>
              <span class="bg-stone-800/90 text-stone-300 border border-stone-700/80 px-2.5 py-1 rounded-lg text-xs font-bold">MoMo</span>
              <span class="bg-stone-800/90 text-stone-300 border border-stone-700/80 px-2.5 py-1 rounded-lg text-xs font-bold">Visa</span>
              <span class="bg-stone-800/90 text-stone-300 border border-stone-700/80 px-2.5 py-1 rounded-lg text-xs font-bold">COD</span>
            </div>
          </div>
        </div>

      </div>

      <!-- Dòng Bản Quyền Dưới Cùng (Bottom Bar) -->
      <div class="border-t border-stone-800/80 pt-6 flex flex-col sm:flex-row items-center justify-between text-xs text-stone-500 gap-3">
        <div>
          &copy; 2026 Bloomora Florist. Bản quyền đã được đăng ký. Cung cấp dịch vụ hoa tươi chất lượng cao.
        </div>
        <div>
          Thiết kế &amp; phát triển với <span class="text-rose-500">❤️</span> dành riêng cho người Việt (Môi trường PHP 8.0+ &amp; MySQL)
        </div>
      </div>

    </div>
  </footer>

  <!-- ================= CÁC NÚT HÀNH ĐỘNG NỔI (FLOATING ACTION STACK) ================= -->
  <div class="fixed bottom-6 right-5 sm:right-6 z-40 flex flex-col items-end space-y-2.5">
    
    <!-- Nút 1: Trợ lý AI Tư Vấn Hoa -->
    <button id="aiChatbotToggleBtn" onclick="toggleAIChatbot()"
      class="bg-gradient-to-r from-rose-600 to-rose-700 hover:from-rose-700 hover:to-rose-800 text-white rounded-full p-2.5 sm:px-4 sm:py-2.5 shadow-xl flex items-center space-x-2 transition transform hover:scale-105 active:scale-95 border border-white/30 cursor-pointer">
      <div class="w-6 h-6 rounded-full bg-white/20 flex items-center justify-center text-white text-xs">
        <i class="ph-bold ph-sparkle"></i>
      </div>
      <span class="hidden sm:inline font-bold text-xs">Trợ Lý AI Tư Vấn Hoa</span>
      <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
    </button>

    <!-- Nút 2: Tư vấn Zalo 24/7 (Màu xanh dương chuẩn nhận diện) -->
    <a href="https://zalo.me/0908123456" target="_blank" rel="noopener noreferrer"
      class="bg-[#1877f2] hover:bg-blue-600 text-white px-4 py-2 rounded-full shadow-xl flex items-center space-x-2 text-xs font-bold transition transform hover:scale-105 border border-white/20">
      <div class="w-5 h-5 rounded-full bg-white text-[#1877f2] flex items-center justify-center font-black text-[11px] shadow-sm">
        Z
      </div>
      <span>Tư vấn Zalo 24/7</span>
    </a>

    <!-- Nút 3: Hotline Gọi Ngay (Màu đỏ hồng chuẩn nhận diện) -->
    <a href="tel:0908123456"
      class="bg-[#e11d48] hover:bg-rose-700 text-white px-4 py-2 rounded-full shadow-xl flex items-center space-x-2 text-xs font-bold transition transform hover:scale-105 border border-white/20">
      <i class="ph-bold ph-phone-call text-sm"></i>
      <span class="font-mono">0908 123 456</span>
    </a>

  </div>

  <!-- SCRIPT RIÊNG BIỆT -->
  <script src="../html/auth.js"></script>
  <script src="../html/app.js"></script>

</body>
</html>
