/**
 * BLOOMORA - FILE XỬ LÝ XÁC THỰC RIÊNG BIỆT (AUTH.JS)
 * Chuyên trách: Đăng Nhập, Đăng Ký, Quên Mật Khẩu, Đổi Mật Khẩu & Phiên Người Dùng
 */

(function () {
  'use strict';

  // 1. Khởi tạo danh sách tài khoản mẫu trong LocalStorage
  function initAuthStore() {
    if (!localStorage.getItem('bloomora_users')) {
      const defaultUsers = [
        {
          name: 'Admin Chủ Tiệm',
          email: 'admin@bloomora.vn',
          phone: '0901234567',
          pass: '123456',
          role: 'admin'
        },
        {
          name: 'Nguyễn Văn Khách',
          email: 'khachhang@gmail.com',
          phone: '0912345678',
          pass: '123456',
          role: 'customer'
        }
      ];
      localStorage.setItem('bloomora_users', JSON.stringify(defaultUsers));
    }
  }

  initAuthStore();

  // 2. Đối tượng quản lý tài khoản & phân quyền
  window.BloomoraAuth = {
    getUsers: function () {
      try {
        return JSON.parse(localStorage.getItem('bloomora_users')) || [];
      } catch (e) {
        return [];
      }
    },

    getCurrentUser: function () {
      try {
        return JSON.parse(localStorage.getItem('bloomora_current_user')) || null;
      } catch (e) {
        return null;
      }
    },

    login: function (identifier, password) {
      const users = this.getUsers();
      const user = users.find(u => (u.email.toLowerCase() === identifier.toLowerCase() || u.phone === identifier));

      if (!user) {
        return { success: false, message: 'Tài khoản không tồn tại trong hệ thống. Vui lòng đăng ký.' };
      }

      if (user.pass !== password) {
        return { success: false, message: 'Mật khẩu không chính xác. Vui lòng thử lại hoặc bấm Quên mật khẩu.' };
      }

      localStorage.setItem('bloomora_current_user', JSON.stringify(user));
      return { success: true, user: user };
    },

    register: function (fullName, email, phone, password) {
      const users = this.getUsers();
      const exists = users.some(u => u.email.toLowerCase() === email.toLowerCase());

      if (exists) {
        return { success: false, message: 'Địa chỉ Email này đã được đăng ký. Vui lòng dùng email khác hoặc đăng nhập.' };
      }

      const newUser = {
        name: fullName,
        email: email,
        phone: phone,
        pass: password,
        role: 'customer'
      };

      users.push(newUser);
      localStorage.setItem('bloomora_users', JSON.stringify(users));
      localStorage.setItem('bloomora_last_registered_name', fullName);
      localStorage.setItem('bloomora_current_user', JSON.stringify(newUser));

      return { success: true, user: newUser };
    },

    resetPassword: function (email, newPassword) {
      const users = this.getUsers();
      const userIndex = users.findIndex(u => u.email.toLowerCase() === email.toLowerCase());

      if (userIndex === -1) {
        return { success: false, message: 'Không tìm thấy tài khoản gắn với email này.' };
      }

      users[userIndex].pass = newPassword;
      localStorage.setItem('bloomora_users', JSON.stringify(users));
      return { success: true, message: 'Đặt lại mật khẩu thành công!' };
    },

    logout: function () {
      localStorage.removeItem('bloomora_current_user');
      localStorage.removeItem('bloomora_user');
    }
  };

  // 3. Tiện ích giao diện: Ẩn/hiện mật khẩu & Điền tài khoản thử nghiệm
  window.togglePassword = function (id, btn) {
    const input = document.getElementById(id);
    if (!input) return;
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
      input.type = 'text';
      if (icon) icon.className = 'ph ph-eye-slash text-rose-600';
    } else {
      input.type = 'password';
      if (icon) icon.className = 'ph ph-eye text-stone-400';
    }
  };

  window.fillTestAccount = function (email, pass) {
    const idInput = document.getElementById('loginIdentifier');
    const passInput = document.getElementById('loginPassword');
    const errBox = document.getElementById('loginErrorBox');
    if (idInput && passInput) {
      idInput.value = email;
      passInput.value = pass;
    }
    if (errBox) errBox.classList.add('hidden');
  };

  // 4. Xử lý Form Đăng Nhập
  window.handleLoginSubmit = function (e) {
    e.preventDefault();
    const errBox = document.getElementById('loginErrorBox');
    const errText = document.getElementById('loginErrorText');
    if (errBox) errBox.classList.add('hidden');

    const identifier = document.getElementById('loginIdentifier')?.value.trim();
    const password = document.getElementById('loginPassword')?.value;

    const res = window.BloomoraAuth.login(identifier, password);
    if (!res.success) {
      if (errBox && errText) {
        errText.textContent = res.message;
        errBox.classList.remove('hidden');
      }
      return;
    }

    // Điều hướng trực tiếp không alert: Admin -> admin.html, Khách -> index.html
    if (res.user.role === 'admin') {
      window.location.href = 'admin.html';
    } else {
      window.location.href = 'index.html';
    }
  };

  // 5. Xử lý Form Đăng Ký
  window.checkPasswordStrength = function (val) {
    const bar = document.getElementById('strengthBar');
    const text = document.getElementById('strengthText');
    if (!bar || !text) return;

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
  };

  window.handleRegister = function (e) {
    e.preventDefault();
    const errBox = document.getElementById('regErrorBox');
    const errText = document.getElementById('regErrorText');
    if (errBox) errBox.classList.add('hidden');

    const fullName = document.getElementById('regFullName')?.value.trim();
    const email = document.getElementById('regEmail')?.value.trim();
    const phone = document.getElementById('regPhone')?.value.trim();
    const pass = document.getElementById('regPassword')?.value;
    const confirm = document.getElementById('regConfirmPassword')?.value;

    if (pass !== confirm) {
      if (errBox && errText) {
        errText.textContent = 'Mật khẩu xác nhận không khớp! Vui lòng kiểm tra lại.';
        errBox.classList.remove('hidden');
      }
      return;
    }

    if (pass.length < 6) {
      if (errBox && errText) {
        errText.textContent = 'Mật khẩu phải có độ dài từ 6 ký tự trở lên.';
        errBox.classList.remove('hidden');
      }
      return;
    }

    const res = window.BloomoraAuth.register(fullName, email, phone, pass);
    if (!res.success) {
      if (errBox && errText) {
        errText.textContent = res.message;
        errBox.classList.remove('hidden');
      }
      return;
    }

    window.location.href = 'register-success.html';
  };

  // 6. Xử lý Form Quên Mật Khẩu (2 Bước OTP)
  let generatedOTP = '';
  let targetResetEmail = '';

  function showForgotError(msg) {
    const errBox = document.getElementById('forgotErrorBox');
    const errText = document.getElementById('forgotErrorText');
    if (errBox && errText) {
      errText.textContent = msg;
      errBox.classList.remove('hidden');
    }
  }

  function hideForgotError() {
    const errBox = document.getElementById('forgotErrorBox');
    if (errBox) errBox.classList.add('hidden');
  }

  window.handleSendOTP = function (e) {
    e.preventDefault();
    hideForgotError();

    const email = document.getElementById('forgotEmail')?.value.trim();
    const users = window.BloomoraAuth.getUsers();
    const user = users.find(u => u.email.toLowerCase() === email.toLowerCase());

    if (!user) {
      showForgotError('Không tìm thấy tài khoản nào gắn với email: ' + email + '. Vui lòng kiểm tra lại!');
      return;
    }

    targetResetEmail = email;
    generatedOTP = Math.floor(100000 + Math.random() * 900000).toString();

    const otpDisplay = document.getElementById('otpDisplay');
    const otpInput = document.getElementById('otpInput');
    if (otpDisplay) otpDisplay.textContent = generatedOTP;
    if (otpInput) otpInput.value = generatedOTP;

    document.getElementById('step1Box')?.classList.add('hidden');
    document.getElementById('step2Box')?.classList.remove('hidden');
  };

  window.backToStep1 = function () {
    hideForgotError();
    document.getElementById('step2Box')?.classList.add('hidden');
    document.getElementById('step1Box')?.classList.remove('hidden');
  };

  window.handleResetPassword = function (e) {
    e.preventDefault();
    hideForgotError();

    const otp = document.getElementById('otpInput')?.value.trim();
    const newPass = document.getElementById('newPassword')?.value;
    const confirmPass = document.getElementById('newPasswordConfirm')?.value;

    if (otp !== generatedOTP) {
      showForgotError('Mã OTP xác thực không chính xác. Vui lòng kiểm tra lại!');
      return;
    }

    if (!newPass || newPass.length < 6) {
      showForgotError('Mật khẩu mới phải có tối thiểu 6 ký tự.');
      return;
    }

    if (newPass !== confirmPass) {
      showForgotError('Xác nhận mật khẩu mới không khớp! Vui lòng nhập lại.');
      return;
    }

    const res = window.BloomoraAuth.resetPassword(targetResetEmail, newPass);
    if (res.success) {
      document.getElementById('step2Box')?.classList.add('hidden');
      document.getElementById('stepSuccessBox')?.classList.remove('hidden');
    } else {
      showForgotError(res.message);
    }
  };

  // 7. Tự động khởi tạo giao diện cho các trang Auth
  document.addEventListener('DOMContentLoaded', () => {
    // Trang Đăng Ký Thành Công (register-success.html)
    const welcomeEl = document.getElementById('welcomeUserName');
    if (welcomeEl) {
      const savedName = localStorage.getItem('bloomora_last_registered_name');
      const currentUser = window.BloomoraAuth.getCurrentUser();
      const displayName = savedName || (currentUser ? currentUser.name : '');
      if (displayName) welcomeEl.textContent = displayName;

      const btnMyAccount = document.getElementById('btnMyAccount');
      if (btnMyAccount && currentUser && currentUser.role === 'admin') {
        btnMyAccount.href = 'admin.html';
      }
    }

    // Thanh Header / Navbar hiển thị trạng thái đăng nhập
    const guestBox = document.getElementById('authGuestBox');
    const userBox = document.getElementById('authUserBox');
    if (guestBox && userBox) {
      let user = window.BloomoraAuth.getCurrentUser();
      if (!user) {
        const legacy = localStorage.getItem('bloomora_user');
        if (legacy) user = { name: legacy };
      }
      if (user && user.name) {
        guestBox.classList.add('hidden');
        userBox.classList.remove('hidden');
        const userNameSpan = document.getElementById('userNameSpan');
        const avatarLetter = document.getElementById('avatarLetter');
        if (userNameSpan) userNameSpan.textContent = user.name;
        if (avatarLetter) avatarLetter.textContent = user.name.charAt(0).toUpperCase();
      }
    }
  });

})();