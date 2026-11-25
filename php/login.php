<?php
declare(strict_types=1);

require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/database.php';

init_secure_session();

// Nếu đã đăng nhập thì chuyển hướng về trang chủ php/index.php
if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$errorMessage = '';
$flash = get_flash();

// Xử lý gửi Form Đăng Nhập (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim((string)($_POST['identifier'] ?? ''));
    $password   = (string)($_POST['password'] ?? '');

    if (empty($identifier) || empty($password)) {
        $errorMessage = 'Vui lòng nhập đầy đủ Email/Số điện thoại và Mật khẩu.';
    } else {
        $pdo = getDBConnection();
        if ($pdo === null) {
            $errorMessage = 'Chưa thể kết nối CSDL MySQL. Vui lòng mở bảng điều khiển XAMPP và bật MySQL.';
        } else {
            $stmt = $pdo->prepare("
                SELECT u.*, r.name as role_name 
                FROM `users` u 
                LEFT JOIN `roles` r ON u.role_id = r.id 
                WHERE (u.email = :id_email OR u.phone = :id_phone)
                LIMIT 1
            ");
            $stmt->execute([
                'id_email' => $identifier,
                'id_phone' => $identifier
            ]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                if ($user['status'] === 'banned') {
                    $errorMessage = 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.';
                } else {
                    $_SESSION['user_id']   = (int)$user['id'];
                    $_SESSION['user_name'] = $user['full_name'];
                    $_SESSION['email']     = $user['email'];
                    $_SESSION['role_name'] = $user['role_name'] ?? 'customer';

                    // Đi thẳng vào trang chủ không cần thông báo popup/banner
                    header('Location: index.php');
                    exit;
                }
            } else {
                $errorMessage = 'Email/Số điện thoại hoặc Mật khẩu không chính xác.';
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
  <title>Đăng Nhập PHP | Bloomora</title>

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
      <i class="ph ph-shield-check text-emerald-600 text-base"></i>
      <span>Backend PHP & MySQL</span>
    </div>
  </div>

  <!-- Card Login -->
  <main class="max-w-md w-full mx-auto bg-white rounded-3xl p-6 sm:p-8 border border-stone-200 shadow-2xl shadow-rose-950/5">
    
    <div class="text-center mb-6">
      <a href="index.php" class="inline-flex items-center justify-center space-x-2 group">
        <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-rose-700 to-rose-500 flex items-center justify-center text-white shadow-md shadow-rose-600/30">
          <i class="ph ph-flower-lotus text-2xl"></i>
        </div>
        <span class="font-serif-title text-2xl font-bold tracking-tight text-stone-900 group-hover:text-rose-600 transition">BLOOMORA</span>
      </a>
      <h1 class="text-base font-bold text-stone-900 mt-3">Đăng Nhập Tài Khoản</h1>
      <p class="text-xs text-stone-400 mt-1 font-medium">Xác thực phiên làm việc bằng PHP Session & PDO</p>
    </div>

    <!-- Thông báo lỗi nếu có -->
    <?php if (!empty($errorMessage)): ?>
      <div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-xs p-3.5 rounded-2xl flex items-start space-x-2.5">
        <i class="ph-fill ph-warning-circle text-red-600 text-lg flex-shrink-0 mt-0.5"></i>
        <span><?php echo e($errorMessage); ?></span>
      </div>
    <?php endif; ?>

    <!-- Thông báo flash -->
    <?php if ($flash && $flash['type'] === 'success'): ?>
      <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs p-3.5 rounded-2xl flex items-start space-x-2.5">
        <i class="ph-fill ph-check-circle text-emerald-600 text-lg flex-shrink-0 mt-0.5"></i>
        <span><?php echo e($flash['message']); ?></span>
      </div>
    <?php endif; ?>

    <form action="login.php" method="POST" class="space-y-4">
      <div>
        <label class="block text-xs font-semibold text-stone-700 mb-1">Email hoặc Số điện thoại: *</label>
        <div class="relative">
          <input 
            type="text" 
            name="identifier"
            id="loginIdentifier"
            value="<?php echo e($_POST['identifier'] ?? ''); ?>"
            required
            placeholder="nhapemail@example.com hoặc 0912..."
            class="w-full bg-stone-50 border border-stone-200 rounded-xl pl-10 pr-4 py-2.5 text-xs text-stone-800 font-medium focus:bg-white focus:border-rose-500 focus:ring-2 focus:ring-rose-200 focus:outline-none transition"
          >
          <i class="ph ph-user text-stone-400 absolute left-3.5 top-3 text-base"></i>
        </div>
      </div>

      <div>
        <div class="flex items-center justify-between mb-1">
          <label class="text-xs font-semibold text-stone-700">Mật khẩu: *</label>
          <a href="forgot-password.php" class="text-[11px] font-semibold text-rose-600 hover:text-rose-700 hover:underline transition">
            Quên mật khẩu?
          </a>
        </div>
        <div class="relative">
          <input 
            type="password" 
            name="password"
            id="loginPassword"
            required
            placeholder="Nhập mật khẩu"
            class="w-full bg-stone-50 border border-stone-200 rounded-xl pl-10 pr-10 py-2.5 text-xs text-stone-800 font-medium focus:bg-white focus:border-rose-500 focus:ring-2 focus:ring-rose-200 focus:outline-none transition"
          >
          <i class="ph ph-lock-key text-stone-400 absolute left-3.5 top-3 text-base"></i>
          <button 
            type="button" 
            onclick="togglePassword('loginPassword', this)"
            class="absolute right-3.5 top-2.5 text-stone-400 hover:text-stone-600 text-base"
          >
            <i class="ph ph-eye"></i>
          </button>
        </div>
      </div>

      <button 
        type="submit" 
        class="w-full bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs py-3 rounded-xl shadow-md shadow-rose-600/30 transition flex items-center justify-center space-x-1.5"
      >
        <i class="ph ph-sign-in text-base"></i>
        <span>Đăng Nhập Ngay</span>
      </button>

      <!-- Tài khoản mẫu -->
      <div class="p-3 bg-stone-50 rounded-2xl border border-stone-200/80 text-[11px] space-y-1.5">
        <span class="font-bold text-stone-600 block">Tài khoản mẫu thử nghiệm:</span>
        <div class="flex gap-2">
          <button type="button" onclick="fillAccount('khachhang@gmail.com', '123456')" class="flex-1 bg-white hover:bg-rose-50 hover:text-rose-600 border border-stone-200 py-1.5 rounded-lg font-semibold transition text-stone-700">
            Khách: khachhang@gmail.com
          </button>
          <button type="button" onclick="fillAccount('admin@bloomora.vn', '123456')" class="flex-1 bg-white hover:bg-rose-50 hover:text-rose-600 border border-stone-200 py-1.5 rounded-lg font-semibold transition text-stone-700">
            Admin: admin@bloomora.vn
          </button>
        </div>
      </div>

      <p class="text-center text-xs text-stone-500 pt-1">
        Chưa có tài khoản? 
        <a href="register.php" class="text-rose-600 font-bold hover:underline">Đăng ký thành viên mới</a>
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

    function fillAccount(email, pass) {
      document.getElementById('loginIdentifier').value = email;
      document.getElementById('loginPassword').value = pass;
    }
  </script>

</body>
</html>
