<?php
declare(strict_types=1);

require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/database.php';

init_secure_session();

$errorMessage = '';
$successData = null;

// Xử lý gửi Form Quên Mật Khẩu (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string)($_POST['email'] ?? ''));

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'Vui lòng nhập địa chỉ Email hợp lệ đã đăng ký.';
    } else {
        $pdo = getDBConnection();
        if ($pdo === null) {
            $errorMessage = 'Chưa thể kết nối CSDL MySQL. Vui lòng mở bảng điều khiển XAMPP và bật MySQL.';
        } else {
            $stmt = $pdo->prepare("SELECT `id`, `full_name`, `email` FROM `users` WHERE `email` = :email LIMIT 1");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if ($user) {
                $otp = (string)random_int(100000, 999999);
                $_SESSION['reset_otp']   = $otp;
                $_SESSION['reset_email'] = $email;
                $_SESSION['reset_time']  = time();

                $successData = [
                    'name'  => $user['full_name'],
                    'email' => $user['email'],
                    'otp'   => $otp
                ];
            } else {
                $errorMessage = 'Không tìm thấy tài khoản nào gắn với địa chỉ Email này trong hệ thống.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Khôi Phục Mật Khẩu PHP | Bloomora</title>

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

<body class="bg-gradient-to-br from-rose-50/70 via-stone-50 to-stone-100 text-stone-800 antialiased min-h-screen flex flex-col justify-between p-4 sm:p-6">

  <!-- Top bar -->
  <div class="max-w-md w-full mx-auto flex items-center justify-between pb-4">
    <a href="login.php" class="inline-flex items-center space-x-1.5 text-xs font-semibold text-stone-600 hover:text-rose-600 bg-white/80 hover:bg-white px-3.5 py-1.5 rounded-full border border-stone-200 shadow-sm transition">
      <i class="ph ph-arrow-left text-sm"></i>
      <span>Về trang đăng nhập</span>
    </a>
    <div class="flex items-center space-x-1 text-xs text-stone-500 font-medium">
      <i class="ph ph-shield-check text-emerald-600 text-base"></i>
      <span>Xác minh PHP & MySQL</span>
    </div>
  </div>

  <!-- Card Forgot Password -->
  <main class="max-w-md w-full mx-auto bg-white rounded-3xl p-6 sm:p-8 border border-stone-200 shadow-2xl shadow-rose-950/5">
    
    <div class="text-center mb-6 space-y-2">
      <div class="w-14 h-14 rounded-2xl bg-amber-50 text-amber-600 mx-auto flex items-center justify-center text-3xl shadow-sm border border-amber-100">
        <i class="ph ph-key"></i>
      </div>
      <h1 class="font-serif-title text-2xl font-bold text-stone-900">Khôi Phục Mật Khẩu</h1>
      <p class="text-xs text-stone-500 leading-relaxed max-w-xs mx-auto">
        Nhập email đã đăng ký. Hệ thống PHP sẽ đối soát CSDL và tạo mã OTP 6 số để bạn thiết lập lại mật khẩu.
      </p>
    </div>

    <!-- Thông báo lỗi nếu có -->
    <?php if (!empty($errorMessage)): ?>
      <div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-xs p-3.5 rounded-2xl flex items-start space-x-2.5">
        <i class="ph-fill ph-warning-circle text-red-600 text-lg flex-shrink-0 mt-0.5"></i>
        <span><?php echo e($errorMessage); ?></span>
      </div>
    <?php endif; ?>

    <form action="forgot-password.php" method="POST" class="space-y-4">
      <div>
        <label class="block text-xs font-semibold text-stone-700 mb-1">Email đăng ký tài khoản: *</label>
        <div class="relative">
          <input 
            type="email" 
            name="email"
            id="forgotEmail"
            value="<?php echo e($_POST['email'] ?? ''); ?>"
            required
            placeholder="nhapemail@example.com"
            class="w-full bg-stone-50 border border-stone-200 rounded-xl pl-10 pr-4 py-2.5 text-xs text-stone-800 font-medium focus:bg-white focus:border-rose-500 focus:ring-2 focus:ring-rose-200 focus:outline-none transition"
          >
          <i class="ph ph-envelope text-stone-400 absolute left-3.5 top-3 text-base"></i>
        </div>
      </div>

      <button 
        type="submit" 
        class="w-full bg-stone-900 hover:bg-rose-600 text-white font-bold text-xs py-3 rounded-xl shadow-md transition flex items-center justify-center space-x-1.5"
      >
        <i class="ph ph-paper-plane-tilt text-base"></i>
        <span>Gửi Mã Xác Minh OTP</span>
      </button>

      <!-- Hộp OTP hiển thị mẫu khi gửi thành công -->
      <?php if ($successData !== null): ?>
        <div class="mt-4 bg-emerald-50 border border-emerald-300 text-emerald-900 text-xs p-4 rounded-2xl space-y-2">
          <div class="flex items-center space-x-2 text-emerald-700 font-bold">
            <i class="ph-fill ph-check-circle text-xl"></i>
            <span>Tìm thấy tài khoản: <?php echo e($successData['name']); ?></span>
          </div>
          <p class="text-[11px] text-stone-600">
            Hệ thống đã gửi email xác minh đến <strong><?php echo e($successData['email']); ?></strong>.
          </p>
          <div class="bg-white p-3 rounded-xl border border-emerald-200 text-center">
            <span class="text-[10px] text-stone-400 uppercase tracking-widest block font-bold">Mã xác minh OTP mô phỏng:</span>
            <span class="font-mono text-2xl font-black text-rose-600 tracking-widest"><?php echo e($successData['otp']); ?></span>
            <span class="block text-[10px] text-stone-400 mt-0.5">Có hiệu lực trong vòng 15 phút</span>
          </div>
        </div>
      <?php endif; ?>

      <div class="text-center pt-2">
        <a 
          href="login.php"
          class="inline-flex items-center space-x-1 text-xs font-semibold text-stone-600 hover:text-rose-600 transition"
        >
          <i class="ph ph-arrow-left"></i>
          <span>Quay lại trang Đăng nhập</span>
        </a>
      </div>
    </form>

  </main>

  <footer class="text-center text-[11px] text-stone-400 py-4">
    Bloomora Floral E-Commerce &copy; 2026. Môi trường PHP 8.0+.
  </footer>

</body>
</html>
