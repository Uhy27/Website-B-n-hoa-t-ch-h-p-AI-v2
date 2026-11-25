<?php
declare(strict_types=1);

require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/database.php';

init_secure_session();

// Nếu đã đăng nhập thì chuyển hướng về trang chủ
if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$errorMessage = '';

// Xử lý gửi Form Đăng Ký (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName        = trim((string)($_POST['full_name'] ?? ''));
    $email           = trim((string)($_POST['email'] ?? ''));
    $phone           = trim((string)($_POST['phone'] ?? ''));
    $password        = (string)($_POST['password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');
    $terms           = isset($_POST['terms']);

    if (empty($fullName) || empty($email) || empty($phone) || empty($password)) {
        $errorMessage = 'Vui lòng điền đầy đủ tất cả các trường thông tin bắt buộc.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'Địa chỉ Email không đúng định dạng. Ví dụ: tenban@gmail.com';
    } elseif (strlen($password) < 6) {
        $errorMessage = 'Mật khẩu phải có độ dài tối thiểu từ 6 ký tự trở lên.';
    } elseif ($password !== $confirmPassword) {
        $errorMessage = 'Mật khẩu xác nhận không khớp với mật khẩu đã tạo.';
    } elseif (!$terms) {
        $errorMessage = 'Bạn cần đồng ý với Điều khoản dịch vụ và Chính sách của Bloomora.';
    } else {
        $pdo = getDBConnection();
        if ($pdo === null) {
            $errorMessage = 'Chưa thể kết nối CSDL MySQL. Vui lòng mở bảng điều khiển XAMPP và bật MySQL.';
        } else {
            // Kiểm tra trùng Email
            $checkStmt = $pdo->prepare("SELECT `id` FROM `users` WHERE `email` = :email LIMIT 1");
            $checkStmt->execute(['email' => $email]);
            if ($checkStmt->fetch()) {
                $errorMessage = 'Địa chỉ Email này đã được đăng ký bởi tài khoản khác.';
            } else {
                $passwordHash = password_hash($password, PASSWORD_BCRYPT);

                $insertStmt = $pdo->prepare("
                    INSERT INTO `users` (`role_id`, `email`, `password_hash`, `full_name`, `phone`, `status`) 
                    VALUES (3, :email, :password_hash, :full_name, :phone, 'active')
                ");
                $insertStmt->execute([
                    'email'         => $email,
                    'password_hash' => $passwordHash,
                    'full_name'     => $fullName,
                    'phone'         => $phone
                ]);

                // Lưu tên để trang thành công hiển thị
                $_SESSION['registered_name'] = $fullName;
                header('Location: register-success.php');
                exit;
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
  <title>Đăng Ký PHP | Bloomora</title>

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
    <a href="index.php" class="inline-flex items-center space-x-1.5 text-xs font-semibold text-stone-600 hover:text-rose-600 bg-white/80 hover:bg-white px-3.5 py-1.5 rounded-full border border-stone-200 shadow-sm transition">
      <i class="ph ph-arrow-left text-sm"></i>
      <span>Về trang chủ</span>
    </a>
    <div class="flex items-center space-x-1 text-xs text-stone-500 font-medium">
      <i class="ph ph-gift text-rose-600 text-base"></i>
      <span>Ưu đãi thành viên mới -10%</span>
    </div>
  </div>

  <!-- Card Register -->
  <main class="max-w-md w-full mx-auto bg-white rounded-3xl p-6 sm:p-8 border border-stone-200 shadow-2xl shadow-rose-950/5">
    
    <div class="text-center mb-6">
      <a href="index.php" class="inline-flex items-center justify-center space-x-2 group">
        <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-rose-700 to-rose-500 flex items-center justify-center text-white shadow-md shadow-rose-600/30">
          <i class="ph ph-flower-lotus text-2xl"></i>
        </div>
        <span class="font-serif-title text-2xl font-bold tracking-tight text-stone-900 group-hover:text-rose-600 transition">BLOOMORA</span>
      </a>
      <h1 class="text-base font-bold text-stone-900 mt-3">Đăng Ký Thành Viên Mới</h1>
      <p class="text-xs text-stone-400 mt-1 font-medium">Lưu trữ bảo mật vào CSDL MySQL bằng mã hóa BCRYPT</p>
    </div>

    <!-- Thông báo lỗi nếu có -->
    <?php if (!empty($errorMessage)): ?>
      <div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-xs p-3.5 rounded-2xl flex items-start space-x-2.5">
        <i class="ph-fill ph-warning-circle text-red-600 text-lg flex-shrink-0 mt-0.5"></i>
        <span><?php echo e($errorMessage); ?></span>
      </div>
    <?php endif; ?>

    <form action="register.php" method="POST" class="space-y-3.5">
      <div>
        <label class="block text-xs font-semibold text-stone-700 mb-1">Họ và tên của bạn: *</label>
        <div class="relative">
          <input 
            type="text" 
            name="full_name"
            id="regFullName"
            value="<?php echo e($_POST['full_name'] ?? ''); ?>"
            required
            placeholder="Ví dụ: Nguyễn Văn Hùng"
            class="w-full bg-stone-50 border border-stone-200 rounded-xl pl-10 pr-4 py-2.5 text-xs text-stone-800 font-medium focus:bg-white focus:border-rose-500 focus:ring-2 focus:ring-rose-200 focus:outline-none transition"
          >
          <i class="ph ph-identification-badge text-stone-400 absolute left-3.5 top-3 text-base"></i>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-semibold text-stone-700 mb-1">Email: *</label>
          <input 
            type="email" 
            name="email"
            id="regEmail"
            value="<?php echo e($_POST['email'] ?? ''); ?>"
            required
            placeholder="email@example.com"
            class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 py-2.5 text-xs text-stone-800 font-medium focus:bg-white focus:border-rose-500 focus:outline-none transition"
          >
        </div>
        <div>
          <label class="block text-xs font-semibold text-stone-700 mb-1">Số điện thoại: *</label>
          <input 
            type="tel" 
            name="phone"
            id="regPhone"
            value="<?php echo e($_POST['phone'] ?? ''); ?>"
            required
            placeholder="0912 345 678"
            class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 py-2.5 text-xs text-stone-800 font-medium focus:bg-white focus:border-rose-500 focus:outline-none transition"
          >
        </div>
      </div>

      <div>
        <label class="block text-xs font-semibold text-stone-700 mb-1">Tạo mật khẩu: *</label>
        <div class="relative">
          <input 
            type="password" 
            name="password"
            id="regPassword"
            required
            oninput="checkPasswordStrength(this.value)"
            placeholder="Tối thiểu 6 ký tự"
            class="w-full bg-stone-50 border border-stone-200 rounded-xl pl-10 pr-10 py-2.5 text-xs text-stone-800 font-medium focus:bg-white focus:border-rose-500 focus:ring-2 focus:ring-rose-200 focus:outline-none transition"
          >
          <i class="ph ph-lock-key text-stone-400 absolute left-3.5 top-3 text-base"></i>
          <button 
            type="button" 
            onclick="togglePassword('regPassword', this)"
            class="absolute right-3.5 top-2.5 text-stone-400 hover:text-stone-600 text-base"
          >
            <i class="ph ph-eye"></i>
          </button>
        </div>
        
        <div class="mt-1.5 flex items-center space-x-1.5">
          <div class="h-1 flex-1 bg-stone-200 rounded-full overflow-hidden">
            <div id="strengthBar" class="h-full bg-red-500 w-0 transition-all duration-300"></div>
          </div>
          <span id="strengthText" class="text-[10px] text-stone-400 font-semibold">Chưa nhập</span>
        </div>
      </div>

      <div>
        <label class="block text-xs font-semibold text-stone-700 mb-1">Xác nhận lại mật khẩu: *</label>
        <div class="relative">
          <input 
            type="password" 
            name="confirm_password"
            id="regConfirmPassword"
            required
            placeholder="Nhập lại chính xác mật khẩu"
            class="w-full bg-stone-50 border border-stone-200 rounded-xl pl-10 pr-10 py-2.5 text-xs text-stone-800 font-medium focus:bg-white focus:border-rose-500 focus:ring-2 focus:ring-rose-200 focus:outline-none transition"
          >
          <i class="ph ph-check-circle text-stone-400 absolute left-3.5 top-3 text-base"></i>
          <button 
            type="button" 
            onclick="togglePassword('regConfirmPassword', this)"
            class="absolute right-3.5 top-2.5 text-stone-400 hover:text-stone-600 text-base"
          >
            <i class="ph ph-eye"></i>
          </button>
        </div>
      </div>

      <label class="flex items-start space-x-2 text-[11px] text-stone-600 cursor-pointer pt-1">
        <input type="checkbox" name="terms" checked class="w-4 h-4 text-rose-600 rounded border-stone-300 focus:ring-rose-500 mt-0.5">
        <span>Tôi đồng ý với <a href="#" class="text-rose-600 underline font-semibold">Điều khoản dịch vụ</a> & <a href="#" class="text-rose-600 underline font-semibold">Chính sách bảo mật</a> của Bloomora.</span>
      </label>

      <button 
        type="submit" 
        class="w-full bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs py-3 rounded-xl shadow-md shadow-rose-600/30 transition flex items-center justify-center space-x-1.5"
      >
        <i class="ph ph-user-plus text-base"></i>
        <span>Hoàn Tất Đăng Ký</span>
      </button>

      <p class="text-center text-xs text-stone-500 pt-1">
        Đã có tài khoản? 
        <a href="login.php" class="text-rose-600 font-bold hover:underline">Đăng nhập ngay</a>
      </p>
    </form>

  </main>

  <footer class="text-center text-[11px] text-stone-400 py-4">
    Bloomora Floral E-Commerce &copy; 2026. Môi trường PHP 8.0+.
  </footer>

  <script>
    function togglePassword(id, btn) {
      const input = document.getElementById(id);
      const icon = btn.querySelector('i');
      if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'ph ph-eye-slash text-rose-600';
      } else {
        input.type = 'password';
        icon.className = 'ph ph-eye text-stone-400';
      }
    }

    function checkPasswordStrength(val) {
      const bar = document.getElementById('strengthBar');
      const text = document.getElementById('strengthText');

      if (!val) {
        bar.style.width = '0%';
        text.textContent = 'Chưa nhập';
        text.className = 'text-[10px] text-stone-400 font-semibold';
        return;
      }

      if (val.length < 6) {
        bar.style.width = '30%';
        bar.className = 'h-full bg-red-500 transition-all duration-300';
        text.textContent = 'Mật khẩu yếu (<6 ký tự)';
        text.className = 'text-[10px] text-red-500 font-semibold';
      } else if (val.length < 9) {
        bar.style.width = '65%';
        bar.className = 'h-full bg-amber-500 transition-all duration-300';
        text.textContent = 'Mức độ: Trung bình';
        text.className = 'text-[10px] text-amber-500 font-semibold';
      } else {
        bar.style.width = '100%';
        bar.className = 'h-full bg-emerald-500 transition-all duration-300';
        text.textContent = 'Mật khẩu rất mạnh';
        text.className = 'text-[10px] text-emerald-600 font-semibold';
      }
    }
  </script>

</body>
</html>
