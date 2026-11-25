<?php
declare(strict_types=1);

require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/database.php';

init_secure_session();

// Kiểm tra quyền Admin/Staff (nếu chưa đăng nhập thì tự gán thông tin mẫu để tiện chạy thử nghiệm)
$adminName = $_SESSION['user_name'] ?? 'Admin Chủ Tiệm';
$adminRole = $_SESSION['role_name'] ?? 'admin';
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

  <!-- TOPBAR QUẢN TRỊ ADMIN PHP -->
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

      <!-- Điều hướng & Thông tin Admin PHP Session -->
      <div class="flex items-center space-x-4">
        <a href="index.php" class="text-xs text-stone-300 hover:text-white flex items-center space-x-1.5 bg-stone-800 hover:bg-stone-700 px-3 py-1.5 rounded-xl border border-stone-700 transition">
          <i class="ph ph-arrow-square-out text-sm"></i>
          <span>Xem Cửa Hàng</span>
        </a>

        <div class="flex items-center space-x-2 text-xs">
          <div class="w-8 h-8 rounded-full bg-rose-600 text-white font-bold flex items-center justify-center text-xs shadow">
            <?php echo mb_substr($adminName, 0, 1); ?>
          </div>
          <div class="hidden sm:block text-left">
            <span class="block font-semibold text-white leading-tight"><?php echo e($adminName); ?></span>
            <span class="text-[10px] text-rose-400 block uppercase font-bold"><?php echo e($adminRole); ?></span>
          </div>
        </div>

        <a href="logout.php" class="text-stone-400 hover:text-rose-400 p-1.5 rounded-lg border border-stone-700 transition" title="Đăng xuất">
          <i class="ph ph-sign-out text-base"></i>
        </a>
      </div>

    </div>
  </header>

  <!-- NỘI DUNG CHÍNH (MAIN DASHBOARD) -->
  <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 flex-1 w-full space-y-6 sm:space-y-8">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-3xl border border-stone-200/90 shadow-sm">
      <div>
        <h1 class="font-serif-title text-xl sm:text-2xl font-bold text-stone-900">Bảng Điều Khiển & Hoạt Động Cửa Hàng (PHP)</h1>
        <p class="text-xs text-stone-500 mt-1">Phiên quản trị kích hoạt qua Session PHP & Kết nối cơ sở dữ liệu MySQL.</p>
      </div>
      <div class="flex items-center space-x-2 self-start sm:self-auto">
        <a href="../html/admin.html" class="bg-stone-100 hover:bg-stone-200 text-stone-700 text-xs font-bold px-3.5 py-2.5 rounded-xl border border-stone-200 transition flex items-center space-x-1.5">
          <i class="ph ph-browsers text-sm"></i>
          <span>Xem Bản HTML Tĩnh</span>
        </a>
      </div>
    </div>

    <!-- 4 THẺ CHỈ SỐ KPI -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
      
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

      <div class="bg-white rounded-3xl p-5 border border-stone-200 shadow-sm space-y-2">
        <div class="flex items-center justify-between">
          <span class="text-xs font-bold text-stone-400 uppercase tracking-wider">Tổng Đơn Hàng</span>
          <div class="w-9 h-9 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-lg">
            <i class="ph ph-receipt"></i>
          </div>
        </div>
        <div class="text-2xl font-black text-stone-900">24 Đơn</div>
        <div class="text-[11px] text-stone-500 font-medium">
          <span>19 đơn VietQR | 5 đơn COD</span>
        </div>
      </div>

      <div class="bg-white rounded-3xl p-5 border border-stone-200 shadow-sm space-y-2">
        <div class="flex items-center justify-between">
          <span class="text-xs font-bold text-stone-400 uppercase tracking-wider">Cần Cắm & Chụp Ảnh</span>
          <div class="w-9 h-9 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg">
            <i class="ph ph-camera"></i>
          </div>
        </div>
        <div class="text-2xl font-black text-amber-600">6 Đơn Gấp</div>
        <div class="flex items-center text-[11px] text-amber-600 font-semibold">
          <i class="ph ph-clock mr-1"></i>
          <span>Giao khung 15:30 - 17:30</span>
        </div>
      </div>

      <div class="bg-white rounded-3xl p-5 border border-stone-200 shadow-sm space-y-2">
        <div class="flex items-center justify-between">
          <span class="text-xs font-bold text-stone-400 uppercase tracking-wider">Shipper Đang Giao</span>
          <div class="w-9 h-9 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg">
            <i class="ph ph-moped"></i>
          </div>
        </div>
        <div class="text-2xl font-black text-indigo-600">5 Chuyến</div>
        <div class="text-[11px] text-stone-500 font-medium">
          <span>Đang di chuyển giao hỏa tốc</span>
        </div>
      </div>

    </div>

    <!-- BẢNG ĐƠN HÀNG TRONG CA & TỒN KHO -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 sm:gap-8">
      
      <div class="lg:col-span-8 bg-white rounded-3xl p-5 sm:p-6 border border-stone-200 shadow-sm space-y-4">
        <div class="flex items-center justify-between border-b border-stone-100 pb-3">
          <div>
            <h2 class="font-bold text-sm text-stone-900">Danh Sách Đơn Cần Cắm Hoa Trong Ca</h2>
            <p class="text-xs text-stone-400">Nghệ nhân florist cần cắm hoàn thiện và tải ảnh Photo Proof trước khi shipper giao</p>
          </div>
          <span class="text-[11px] bg-rose-50 text-rose-600 font-bold px-2.5 py-1 rounded-full">3 đơn ưu tiên</span>
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
            <tbody class="divide-y divide-stone-100 text-stone-700">
              
              <tr>
                <td class="py-3 font-mono font-bold text-rose-600">BL89241</td>
                <td class="py-3">
                  <div class="font-semibold text-stone-900">Kệ Khai Trương Đại Cát</div>
                  <div class="text-[11px] text-stone-400 truncate max-w-[170px]">Băng rôn: "CHÚC MỪNG KHAI TRƯƠNG HỒNG PHÁT"</div>
                </td>
                <td class="py-3 font-semibold text-stone-800">15:30 - 17:30</td>
                <td class="py-3">
                  <span class="bg-amber-100 text-amber-700 px-2.5 py-0.5 rounded-full font-bold text-[10px] uppercase">
                    3. Đã Chụp Ảnh Thật
                  </span>
                </td>
                <td class="py-3 text-right">
                  <button onclick="alert('Đơn BL89241: Đang ở trạng thái chờ khách duyệt ảnh Photo Proof')" class="bg-stone-100 hover:bg-rose-50 hover:text-rose-600 px-3 py-1.5 rounded-lg font-semibold transition">
                    Chi Tiết
                  </button>
                </td>
              </tr>

              <tr>
                <td class="py-3 font-mono font-bold text-rose-600">BL17305</td>
                <td class="py-3">
                  <div class="font-semibold text-stone-900">Bó Hồng Đỏ Nồng Nàn 99 Bông</div>
                  <div class="text-[11px] text-stone-400 truncate max-w-[170px]">Thiệp: "Yêu em mãi mãi"</div>
                </td>
                <td class="py-3 font-semibold text-stone-800">15:30 - 17:30</td>
                <td class="py-3">
                  <span class="bg-blue-100 text-blue-700 px-2.5 py-0.5 rounded-full font-bold text-[10px] uppercase">
                    2. Đang Cắm Hoa
                  </span>
                </td>
                <td class="py-3 text-right">
                  <button onclick="alert('Đơn BL17305: Florist Mai Phương đang cắm hoa theo mẫu')" class="bg-stone-100 hover:bg-rose-50 hover:text-rose-600 px-3 py-1.5 rounded-lg font-semibold transition">
                    Chi Tiết
                  </button>
                </td>
              </tr>

              <tr>
                <td class="py-3 font-mono font-bold text-rose-600">BL22419</td>
                <td class="py-3">
                  <div class="font-semibold text-stone-900">Chậu Lan Hồ Điệp Hoàng Kim 5 Cành</div>
                  <div class="text-[11px] text-stone-400 truncate max-w-[170px]">Băng rôn: "Mừng Khai Trương Phát Tài"</div>
                </td>
                <td class="py-3 font-semibold text-stone-800">18:00 - 20:00</td>
                <td class="py-3">
                  <span class="bg-stone-100 text-stone-700 px-2.5 py-0.5 rounded-full font-bold text-[10px] uppercase">
                    1. Đã Tiếp Nhận
                  </span>
                </td>
                <td class="py-3 text-right">
                  <button onclick="alert('Đơn BL22419: Đã tiếp nhận đơn hàng, chuẩn bị vật tư')" class="bg-stone-100 hover:bg-rose-50 hover:text-rose-600 px-3 py-1.5 rounded-lg font-semibold transition">
                    Chi Tiết
                  </button>
                </td>
              </tr>

            </tbody>
          </table>
        </div>
      </div>

      <div class="lg:col-span-4 bg-white rounded-3xl p-5 sm:p-6 border border-stone-200 shadow-sm space-y-4">
        <div class="flex items-center justify-between border-b border-stone-100 pb-3">
          <div class="flex items-center space-x-2">
            <i class="ph ph-warning-circle text-amber-500 text-lg"></i>
            <h2 class="font-bold text-sm text-stone-900">Cảnh Báo Tồn Kho Thấp</h2>
          </div>
          <span class="text-[10px] bg-amber-50 text-amber-700 font-bold px-2 py-0.5 rounded-full uppercase">&lt; 5 Sản Phẩm</span>
        </div>

        <div class="space-y-3">
          <div class="p-3 bg-stone-50 rounded-2xl border border-stone-100 flex items-center justify-between">
            <div>
              <div class="font-bold text-xs text-stone-900">Hoa Hướng Dương Đà Lạt</div>
              <div class="text-[11px] text-stone-400">Nguyên liệu hoa chính</div>
            </div>
            <div class="text-right">
              <span class="text-xs font-black text-rose-600 block">Còn 3 bó</span>
              <span class="text-[10px] text-stone-400">Cần nhập gấp</span>
            </div>
          </div>

          <div class="p-3 bg-stone-50 rounded-2xl border border-stone-100 flex items-center justify-between">
            <div>
              <div class="font-bold text-xs text-stone-900">Hồng Ohara Nhập Khẩu</div>
              <div class="text-[11px] text-stone-400">Mẫu hoa VIP</div>
            </div>
            <div class="text-right">
              <span class="text-xs font-black text-amber-600 block">Còn 4 cành</span>
              <span class="text-[10px] text-stone-400">Sắp hết hàng</span>
            </div>
          </div>

          <div class="p-3 bg-stone-50 rounded-2xl border border-stone-100 flex items-center justify-between">
            <div>
              <div class="font-bold text-xs text-stone-900">Dải Ruy Băng Đỏ Viền Vàng</div>
              <div class="text-[11px] text-stone-400">Vật tư in vi tính</div>
            </div>
            <div class="text-right">
              <span class="text-xs font-black text-emerald-600 block">Còn 12 cuộn</span>
              <span class="text-[10px] text-stone-400">Đủ ca hôm nay</span>
            </div>
          </div>
        </div>

        <button onclick="alert('Đã gửi thông báo kiểm kê kho đến quản trị!')" class="w-full text-center text-xs font-bold text-stone-700 hover:text-rose-600 bg-stone-100 py-2.5 rounded-xl transition">
          Kiểm Kê & Nhập Thêm Hoa
        </button>
      </div>

    </div>

  </main>

  <footer class="bg-white border-t border-stone-200 py-4 text-center text-xs text-stone-400">
    Bloomora Floral E-Commerce Master Platform &copy; 2026. Phân hệ Quản trị PHP.
  </footer>

</body>
</html>
