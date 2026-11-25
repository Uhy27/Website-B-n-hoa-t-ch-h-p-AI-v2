<?php
declare(strict_types=1);

require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/database.php';

init_secure_session();

$errorMessage = '';
$successMessage = '';
$step = 1;
$displayOtp = '';
$targetEmail = $_SESSION['reset_email'] ?? '';

// Xử lý gửi Form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? 'send_otp');

    // BƯỚC 1: GỬI OTP
    if ($action === 'send_otp') {
        $email = trim((string)($_POST['email'] ?? ''));

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errorMessage = 'Vui lòng nhập địa chỉ Email hợp lệ đã đăng ký.';
        } else {
            $pdo = getDBConnection();
            if ($pdo === null) {
                // Nếu chưa bật MySQL, vẫn cho phép mô phỏng OTP để kiểm tra giao diện
                $displayOtp = (string)random_int(100000, 999999);
                $_SESSION['reset_otp']   = $displayOtp;
                $_SESSION['reset_email'] = $email;
                $targetEmail = $email;
                $step = 2;
            } else {
                $stmt = $pdo->prepare("SELECT `id`, `full_name`, `email` FROM `users` WHERE `email` = :email LIMIT 1");
                $stmt->execute(['email' => $email]);
                $user = $stmt->fetch();

                if ($user) {
                    $displayOtp = (string)random_int(100000, 999999);
                    $_SESSION['reset_otp']   = $displayOtp;
                    $_SESSION['reset_email'] = $email;
                    $targetEmail = $email;
                    $step = 2;
                } else {
                    $errorMessage = 'Không tìm thấy tài khoản nào gắn với địa chỉ Email này trong hệ thống.';
                }
            }
        }
    } 
    // BƯỚC 2: XÁC MINH OTP & ĐỔI MẬT KHẨU
    elseif ($action === 'reset_password') {
        $otp             = trim((string)($_POST['otp'] ?? ''));
        $newPassword     = (string)($_POST['new_password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');
        $targetEmail     = $_SESSION['reset_email'] ?? '';

        $savedOtp = $_SESSION['reset_otp'] ?? '';

        if (empty($otp) || empty($newPassword) || empty($confirmPassword)) {
            $errorMessage = 'Vui lòng nhập đầy đủ Mã OTP và Mật khẩu mới.';
            $step = 2;
        } elseif ($otp !== $savedOtp) {
            $errorMessage = 'Mã OTP xác thực không chính xác. Vui lòng kiểm tra lại.';
            $step = 2;
        } elseif (strlen($newPassword) < 6) {
            $errorMessage = 'Mật khẩu mới phải có độ dài tối thiểu từ 6 ký tự.';
            $step = 2;
        } elseif ($newPassword !== $confirmPassword) {
            $errorMessage = 'Mật khẩu xác nhận không khớp với mật khẩu mới.';
            $step = 2;
        } else {
            // Cập nhật vào CSDL MySQL nếu có kết nối
            $pdo = getDBConnection();
            if ($pdo !== null && !empty($targetEmail)) {
                $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
                $updateStmt = $pdo->prepare("UPDATE `users` SET `password_hash` = :hash WHERE `email` = :email");
                $updateStmt->execute([
                    'hash'  => $newHash,
                    'email' => $targetEmail
                ]);
            }

            // Xóa session OTP
            unset($_SESSION['reset_otp'], $_SESSION['reset_email']);
            $step = 3; // Hoàn tất thành công
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
        Nhập email đã đăng ký. Hệ thống PHP sẽ gửi mã OTP 6 số để bạn thiết lập lại mật khẩu mới.
      </p>
    </div>

    <!-- Thông báo lỗi nếu có -->
    <?php if (!empty($errorMessage)): ?>
      <div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-xs p-3.5 rounded-2xl flex items-start space-x-2.5">
        <i class="ph-fill ph-warning-circle text-red-600 text-lg flex-shrink-0 mt-0.5"></i>
        <span><?php echo e($errorMessage); ?></span>
      </div>
    <?php endif; ?>

    <?php if ($step === 1): ?>
      <!-- BƯỚC 1: NHẬP EMAIL -->
      <form action="forgot-password.php" method="POST" class="space-y-4">
        <input type="hidden" name="action" value="send_otp">
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

    <?php elseif ($step === 2): ?>
      <!-- BƯỚC 2: NHẬP OTP & ĐẶT MẬT KHẨU MỚI -->
      <div class="space-y-4">
        <div class="bg-emerald-50 border border-emerald-300 text-emerald-900 text-xs p-3.5 rounded-2xl text-center space-y-1">
          <span class="text-[10px] text-stone-500 uppercase tracking-widest block font-bold">Mã xác thực OTP đã tạo:</span>
          <span class="font-mono text-2xl font-black text-rose-600 tracking-widest"><?php echo e($displayOtp ?: ($_SESSION['reset_otp'] ?? '')); ?></span>
          <span class="text-[10px] text-stone-500 block">Dành cho tài khoản: <?php echo e($targetEmail); ?></span>
        </div>

        <form action="forgot-password.php" method="POST" class="space-y-3.5">
          <input type="hidden" name="action" value="reset_password">

          <div>
            <label class="block text-xs font-semibold text-stone-700 mb-1">Mã xác nhận OTP (6 số): *</label>
            <input 
              type="text" 
              name="otp"
              required
              maxlength="6"
              value="<?php echo e($displayOtp ?: ($_SESSION['reset_otp'] ?? '')); ?>"
              class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 py-2.5 text-xs text-stone-800 font-mono font-bold tracking-widest focus:bg-white focus:border-rose-500 focus:outline-none transition"
            >
          </div>

          <div>
            <label class="block text-xs font-semibold text-stone-700 mb-1">Mật khẩu mới: *</label>
            <input 
              type="password" 
              name="new_password"
              required
              placeholder="Tối thiểu 6 ký tự"
              class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 py-2.5 text-xs text-stone-800 font-medium focus:bg-white focus:border-rose-500 focus:outline-none transition"
            >
          </div>

          <div>
            <label class="block text-xs font-semibold text-stone-700 mb-1">Xác nhận mật khẩu mới: *</label>
            <input 
              type="password" 
              name="confirm_password"
              required
              placeholder="Nhập lại mật khẩu mới"
              class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 py-2.5 text-xs text-stone-800 font-medium focus:bg-white focus:border-rose-500 focus:outline-none transition"
            >
          </div>

          <button 
            type="submit" 
            class="w-full bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs py-3 rounded-xl shadow-md shadow-rose-600/30 transition flex items-center justify-center space-x-1.5"
          >
            <i class="ph ph-floppy-disk text-base"></i>
            <span>Cập Nhật Mật Khẩu Mới</span>
          </button>

          <div class="text-center pt-1">
            <a href="forgot-password.php" class="text-xs text-stone-500 hover:text-stone-800 font-semibold">
              Nhập lại địa chỉ Email khác
            </a>
          </div>
        </form>
      </div>

    <?php elseif ($step === 3): ?>
      <!-- BƯỚC 3: THÀNH CÔNG -->
      <div class="text-center space-y-4 py-2">
        <div class="w-12 h-12 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-2xl mx-auto shadow-sm">
          <i class="ph-bold ph-check"></i>
        </div>
        <div>
          <h3 class="text-base font-bold text-stone-900">Đổi Mật Khẩu Thành Công!</h3>
          <p class="text-xs text-stone-500 mt-1">Mật khẩu mới đã được cập nhật thành công trong CSDL. Bạn có thể đăng nhập ngay.</p>
        </div>
        <a 
          href="login.php"
          class="block w-full bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs py-3 rounded-xl shadow-md shadow-rose-600/30 transition"
        >
          Đăng Nhập Ngay Với Mật Khẩu Mới
        </a>
      </div>
    <?php endif; ?>

  </main>

  <footer class="text-center text-[11px] text-stone-400 py-4">
    Bloomora Floral E-Commerce &copy; 2026. Môi trường PHP 8.0+.
  </footer>

</body>
</html>