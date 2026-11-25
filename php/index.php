<?php
declare(strict_types=1);

require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/database.php';

init_secure_session();

$isLoggedIn = !empty($_SESSION['user_id']);
$userName   = $_SESSION['user_name'] ?? 'Khách';
$userRole   = $_SESSION['role_name'] ?? 'customer';
$flash      = get_flash();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bloomora | Tiệm Hoa Tươi Thuần Việt (PHP)</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
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

<body class="bg-stone-50 text-stone-800 antialiased min-h-screen flex flex-col">

  <!-- Topbar -->
  <div class="bg-stone-900 text-stone-300 text-xs py-2 px-4 border-b border-stone-800">
    <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-2">
      <div class="flex items-center space-x-2">
        <span class="bg-rose-600 text-white font-bold text-[10px] px-2 py-0.5 rounded-full uppercase">Ưu đãi</span>
        <span>🌸 Miễn phí băng rôn in vi tính & Chụp ảnh hoa thật duyệt trước khi giao!</span>
      </div>
      <div class="flex items-center space-x-4 text-[11px] text-stone-400">
        <span><i class="ph ph-clock mr-1 text-rose-500"></i> Giao 60-90 phút</span>
        <span>|</span>
        <a href="tel:1900633045" class="hover:text-white transition"><i class="ph ph-phone-call mr-1 text-emerald-400"></i> Hotline: <strong class="text-white">1900 633 045</strong></a>
      </div>
    </div>
  </div>

  <!-- Header -->
  <header class="sticky top-0 z-40 bg-white/95 backdrop-blur border-b border-stone-200 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between h-20 gap-4">
        
        <!-- Logo -->
        <a href="index.php" class="flex items-center space-x-3 group flex-shrink-0">
          <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-rose-700 to-rose-500 flex items-center justify-center text-white shadow-md shadow-rose-600/30">
            <i class="ph ph-flower-lotus text-2xl"></i>
          </div>
          <div>
            <div class="font-serif-title text-2xl font-bold tracking-tight text-stone-900 group-hover:text-rose-600 transition">BLOOMORA</div>
            <div class="text-[9px] uppercase tracking-widest text-stone-400 font-semibold -mt-1">ONLINE FLOWER SHOP (PHP)</div>
          </div>
        </a>

        <!-- Thanh tìm kiếm -->
        <div class="hidden md:flex flex-1 max-w-md mx-4 relative">
          <input 
            type="text" 
            id="searchInput" 
            placeholder="Tìm hoa khai trương, hoa sinh nhật, hoa hồng..."
            class="w-full bg-stone-100 hover:bg-stone-50 focus:bg-white text-xs text-stone-800 pl-10 pr-4 py-2.5 rounded-full border border-stone-200 focus:border-rose-500 focus:ring-2 focus:ring-rose-200 focus:outline-none transition"
          >
          <i class="ph ph-magnifying-glass text-stone-400 absolute left-3.5 top-3 text-base pointer-events-none"></i>
        </div>

        <!-- Trạng thái Auth PHP -->
        <div class="flex items-center space-x-3 sm:space-x-4">
          <nav class="hidden lg:flex items-center space-x-5 text-xs font-semibold text-stone-600">
            <a href="index.php" class="text-rose-600">Trang Chủ</a>
            <a href="../html/index.html" class="hover:text-rose-600 transition">Xem Bản HTML</a>
          </nav>

          <?php if ($isLoggedIn): ?>
            <!-- ĐÃ ĐĂNG NHẬP -->
            <div class="flex items-center space-x-2 bg-stone-100 border border-stone-200 py-1.5 px-3 rounded-xl text-xs">
              <div class="w-6 h-6 rounded-full bg-rose-600 text-white font-bold flex items-center justify-center text-[10px]">
                <?php echo mb_substr($userName, 0, 1); ?>
              </div>
              <div class="hidden sm:block text-left">
                <span class="block font-bold text-stone-900 leading-none"><?php echo e($userName); ?></span>
                <span class="text-[9px] text-rose-600 uppercase font-semibold"><?php echo e($userRole); ?></span>
              </div>
              <a href="logout.php" class="text-stone-400 hover:text-rose-600 ml-1 p-1" title="Đăng xuất">
                <i class="ph ph-sign-out text-base"></i>
              </a>
            </div>
          <?php else: ?>
            <!-- CHƯA ĐĂNG NHẬP -->
            <a href="login.php" class="flex items-center space-x-1.5 px-3 py-2 text-stone-700 hover:text-rose-600 hover:bg-rose-50 rounded-xl text-xs font-semibold border border-stone-200 transition">
              <i class="ph ph-user text-base text-rose-600"></i>
              <span>Đăng Nhập</span>
            </a>
          <?php endif; ?>

          <button class="relative flex items-center space-x-2 bg-rose-600 hover:bg-rose-700 text-white px-3.5 py-2 rounded-xl text-xs font-bold shadow-md shadow-rose-600/20 transition">
            <i class="ph ph-shopping-bag text-base"></i>
            <span class="hidden sm:inline">Giỏ Hàng</span>
            <span class="bg-white text-rose-600 text-[10px] font-black px-1.5 py-0.2 rounded-full">0</span>
          </button>
        </div>

      </div>
    </div>
  </header>

  <!-- Hero Section -->
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
            100% Hoa tươi nhập mới mỗi ngày. Tặng kèm dải băng rôn in vi tính sang trọng và chụp ảnh hoa thật gửi quý khách duyệt trước khi giao.
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
                <option value="hcm">TP. Hồ Chí Minh (Giao 60p)</option>
                <option value="hn">Hà Nội (Giao 60p)</option>
                <option value="all">Tỉnh / Thành phố khác</option>
              </select>
            </div>

            <div class="sm:col-span-3 lg:col-span-1 flex items-end">
              <button type="submit" class="w-full bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs py-3 px-4 rounded-xl shadow-md shadow-rose-600/30 transition flex items-center justify-center space-x-1.5">
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
  </main>

  <footer class="bg-white border-t border-stone-200 py-6 text-center text-xs text-stone-400">
    Bloomora Floral E-Commerce &copy; 2026. Môi trường PHP 8.0+ & Apache (XAMPP).
  </footer>

  <script>
    function handleQuickSearch(e) {
      e.preventDefault();
      const occasion = document.getElementById('quickOccasion');
      const price = document.getElementById('quickPrice');
      const location = document.getElementById('quickLocation');

      const notice = document.getElementById('searchResultNotice');
      const text = document.getElementById('searchResultText');

      notice.classList.remove('hidden');
      text.innerHTML = `Bạn vừa chọn tìm: <strong>${occasion.options[occasion.selectedIndex].text}</strong> | Mức giá: <strong>${price.options[price.selectedIndex].text}</strong> | Khu vực: <strong>${location.options[location.selectedIndex].text}</strong>.`;
    }
  </script>

</body>
</html>
