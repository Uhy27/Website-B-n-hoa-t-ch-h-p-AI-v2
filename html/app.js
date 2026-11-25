/**
 * BLOOMORA - FILE XỬ LÝ ỨNG DỤNG & QUẢN TRỊ ADMIN (APP.JS)
 * Chuyên trách: Bảng Điều Khiển Admin, Quản Lý Đơn Hàng Florist, Quản Lý Sản Phẩm, Quản Lý Kho & Cửa Hàng
 */

(function () {
  'use strict';

  // 1. Khởi tạo kho dữ liệu trong LocalStorage
  function initAppData() {
    // 1.1 Đơn hàng trong ca (Phiên bản thị trường Đắk Lắk chuẩn 5 giai đoạn)
    const ordersVersion = localStorage.getItem('bloomora_orders_version');
    if (!localStorage.getItem('bloomora_orders') || ordersVersion !== 'v2') {
      const defaultOrders = [
        {
          code: 'BL89241',
          flower: 'Kệ Khai Trương Đại Cát Hồng Phát',
          ribbon: 'CÔNG TY XÂY DỰNG CHÚC MỪNG KHAI TRƯƠNG HỒNG PHÁT',
          slot: '15:30 - 17:30',
          status: 'photo_ready',
          statusText: '3. Đã Chụp Ảnh Thật',
          phone: '0912345678',
          sender: 'Nguyễn Văn Khách',
          receiver: 'Công ty Xây Dựng Đắk Lắk',
          address: '120 Phan Chu Trinh, P. Thắng Lợi, TP. Buôn Ma Thuột, Đắk Lắk',
          total: 1815000,
          paymentMethod: 'vietqr',
          paymentStatus: 'paid',
          photo: 'https://images.unsplash.com/photo-1561181286-d3fee7d55364?auto=format&fit=crop&w=400&q=80',
          photoStatus: 'pending_approval',
          note: 'Kệ hoa đã cắm xong đúng tone đỏ hồng phát, chờ khách duyệt'
        },
        {
          code: 'BL17305',
          flower: 'Bó Hồng Đỏ Nồng Nàn 99 Bông (Forever Love)',
          ribbon: 'Thiệp: Chúc em sinh nhật luôn rạng ngời hạnh phúc',
          slot: '15:30 - 17:30',
          status: 'preparing',
          statusText: '2. Đang Cắm Hoa',
          phone: '0988776655',
          sender: 'Trần Minh Hoàng',
          receiver: 'Lê Thu Thảo',
          address: '45 Lê Duẩn, P. Tự An, TP. Buôn Ma Thuột, Đắk Lắk',
          total: 1265000,
          paymentMethod: 'vietqr',
          paymentStatus: 'paid',
          photo: '',
          photoStatus: 'none',
          note: 'Khách gửi tặng ẩn danh, tuyển chọn 99 bông hồng đỏ tươi đẹp'
        },
        {
          code: 'BL22419',
          flower: 'Chậu Lan Hồ Điệp Hoàng Kim 5 Cành',
          ribbon: 'VIETCOMBANK ĐẮK LẮK CHÚC MỪNG PHÁT TÀI THỊNH VƯỢNG',
          slot: '18:00 - 20:00',
          status: 'received',
          statusText: '1. Đã Tiếp Nhận',
          phone: '0909112233',
          sender: 'Ngân hàng Vietcombank Đắk Lắk',
          receiver: 'Văn phòng Công chứng Ban Mê',
          address: '88 Nguyễn Tất Thành, P. Tân Lợi, TP. Buôn Ma Thuột, Đắk Lắk',
          total: 2515000,
          paymentMethod: 'vietqr',
          paymentStatus: 'unpaid',
          photo: '',
          photoStatus: 'none',
          note: 'Đơn mới đặt qua VietQR, chờ đối soát tiền chuyển khoản'
        }
      ];
      localStorage.setItem('bloomora_orders', JSON.stringify(defaultOrders));
      localStorage.setItem('bloomora_orders_version', 'v2');
    }

    // 1.2 Kho nguyên vật liệu florist
    if (!localStorage.getItem('bloomora_inventory')) {
      const defaultInventory = [
        {
          id: 1,
          name: 'Hoa Hướng Dương Đà Lạt',
          category: 'Nguyên liệu hoa chính',
          qty: 3,
          unit: 'bó',
          minThreshold: 5,
          status: 'Cần nhập gấp',
          level: 'danger'
        },
        {
          id: 2,
          name: 'Hồng Ohara Nhập Khẩu',
          category: 'Mẫu hoa VIP',
          qty: 4,
          unit: 'cành',
          minThreshold: 5,
          status: 'Sắp hết hàng',
          level: 'warning'
        },
        {
          id: 3,
          name: 'Dải Ruy Băng Đỏ Viền Vàng',
          category: 'Vật tư in vi tính',
          qty: 12,
          unit: 'cuộn',
          minThreshold: 5,
          status: 'Đủ ca hôm nay',
          level: 'safe'
        },
        {
          id: 4,
          name: 'Chậu Gốm Mộc Bát Tràng',
          category: 'Chậu & Giỏ cắm hoa',
          qty: 8,
          unit: 'cái',
          minThreshold: 4,
          status: 'Đủ ca hôm nay',
          level: 'safe'
        }
      ];
      localStorage.setItem('bloomora_inventory', JSON.stringify(defaultInventory));
    }

    // 1.3 Danh mục sản phẩm hoa tươi (Phiên bản Wish Florist Đài Loan)
    const existingProducts = localStorage.getItem('bloomora_products');
    const productsVersion = localStorage.getItem('bloomora_products_version');
    if (!existingProducts || productsVersion !== 'v2') {
      const defaultProducts = [
        {
          id: 1,
          sku: 'WF-KT01',
          name: 'Kệ Khai Trương Đại Cát Hồng Phát',
          category: 'Hoa Khai Trương',
          price: 1850000,
          originalPrice: 2200000,
          stock: 12,
          image: 'https://images.unsplash.com/photo-1561181286-d3fee7d55364?auto=format&fit=crop&w=600&q=80',
          badge: 'HOT Khai Trương',
          rating: '5.0 (52)',
          description: 'Kệ hoa 2 tầng thiết kế theo phong cách Wish Florist Đài Loan, phối hoa hồng Ecuador, hướng dương và đồng tiền vàng rực rỡ mang lại may mắn, tài lộc khởi sắc.',
          status: 'Còn hàng'
        },
        {
          id: 2,
          sku: 'WF-HT02',
          name: 'Bó Hồng Đỏ Nồng Nàn 99 Bông',
          category: 'Hoa Tình Yêu & Hoa Hồng',
          price: 1450000,
          originalPrice: 1750000,
          stock: 18,
          image: 'https://images.unsplash.com/photo-1582794543139-8ac9cb0f7b11?auto=format&fit=crop&w=600&q=80',
          badge: 'Bán Chạy Nhất',
          rating: '4.9 (84)',
          description: 'Bó hoa 99 bông hồng đỏ nhung thắm tượng trưng cho tình yêu vĩnh cửu trường tồn. Bọc giấy lụa mờ cao cấp phong cách Hàn Quốc kèm dải ruy băng nơ đỏ.',
          status: 'Còn hàng'
        },
        {
          id: 3,
          sku: 'WF-LH03',
          name: 'Chậu Lan Hồ Điệp Hoàng Kim Phú Quý',
          category: 'Lan Hồ Điệp',
          price: 2800000,
          originalPrice: 3200000,
          stock: 6,
          image: 'https://images.unsplash.com/photo-1525310072745-f49212b5ac6d?auto=format&fit=crop&w=600&q=80',
          badge: 'VIP Doanh Nghiệp',
          rating: '5.0 (39)',
          description: 'Chậu 5 cành Lan Hồ Điệp vàng Đài Loan cắm trong chậu gốm nghệ thuật, cành uốn phong thủy tụ tài, độ tươi bền hơn 6-8 tuần.',
          status: 'Còn hàng'
        },
        {
          id: 4,
          sku: 'WF-SN04',
          name: 'Giỏ Hoa Hướng Dương Nắng Mai Rạng Rỡ',
          category: 'Hoa Sinh Nhật',
          price: 850000,
          originalPrice: 990000,
          stock: 15,
          image: 'https://images.unsplash.com/photo-1597848212624-a19eb35e2651?auto=format&fit=crop&w=600&q=80',
          badge: 'Giao 60 Phút',
          rating: '4.9 (46)',
          description: 'Hướng dương rạng rỡ kết hợp cát tường kem và baby trắng cắm giỏ mây mộc mạc, lời chúc tuổi mới luôn tràn đầy sinh khí và may mắn.',
          status: 'Còn hàng'
        },
        {
          id: 5,
          sku: 'WF-VC05',
          name: 'Hộp Kính Hoa Hồng Vĩnh Cửu 3 Năm',
          category: 'Hoa Vĩnh Cửu',
          price: 950000,
          originalPrice: 1150000,
          stock: 14,
          image: 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?auto=format&fit=crop&w=600&q=80',
          badge: 'Bất Tử 3 Năm',
          rating: '5.0 (61)',
          description: 'Hoa hồng công nghệ ướp tươi Nhật Bản đặt trong vòm kính pha lê trong suốt có đèn LED đom đóm phát sáng ban đêm, lưu giữ vẻ đẹp suốt 3-5 năm.',
          status: 'Còn hàng'
        },
        {
          id: 6,
          sku: 'WF-KT06',
          name: 'Lẵng Khai Trương Để Bàn Khởi Sắc',
          category: 'Hoa Khai Trương',
          price: 1250000,
          originalPrice: 1450000,
          stock: 9,
          image: 'https://images.unsplash.com/photo-1563245372-f21724e3856d?auto=format&fit=crop&w=600&q=80',
          badge: 'Để Bàn Sang Trọng',
          rating: '4.8 (29)',
          description: 'Lẵng hoa để bàn cao cấp tone màu cam san hô phối hồng pastel hiện đại, phù hợp đặt tại quầy lễ tân khai trương, văn phòng hội nghị.',
          status: 'Còn hàng'
        },
        {
          id: 7,
          sku: 'WF-BT07',
          name: 'Bó Hoa Tulip Hà Lan Trắng Tinh Khiết',
          category: 'Hoa Tình Yêu & Hoa Hồng',
          price: 1150000,
          originalPrice: 1350000,
          stock: 10,
          image: 'https://images.unsplash.com/photo-1526047932273-341f2a7631f9?auto=format&fit=crop&w=600&q=80',
          badge: 'Nhập Khẩu Hà Lan',
          rating: '4.9 (33)',
          description: '15 bông hoa Tulip trắng thuần khiết nhập khẩu nguyên cành từ Hà Lan, phối lá khuynh diệp và nơ lụa satin thanh nhã.',
          status: 'Còn hàng'
        },
        {
          id: 8,
          sku: 'WF-LH08',
          name: 'Chậu Lan Hồ Điệp Bạch Tuyết Song Hỷ',
          category: 'Lan Hồ Điệp',
          price: 1950000,
          originalPrice: 2300000,
          stock: 7,
          image: 'https://images.unsplash.com/photo-1508610048659-a06b669e3321?auto=format&fit=crop&w=600&q=80',
          badge: 'Thuần Khiết',
          rating: '5.0 (18)',
          description: 'Cặp 3 cành lan hồ điệp trắng tuyết bông to dày dặn, đính nơ lụa ngọc bích tinh tế, hoàn hảo để chúc mừng tân gia hoặc mừng sinh nhật sếp.',
          status: 'Còn hàng'
        }
      ];
      localStorage.setItem('bloomora_products', JSON.stringify(defaultProducts));
      localStorage.setItem('bloomora_products_version', 'v2');
    }
  }

  initAppData();

  // 2. Đối tượng quản trị tổng thể BloomoraAdmin
  window.BloomoraAdmin = {
    // --- ĐƠN HÀNG ---
    getOrders: function () {
      try {
        return JSON.parse(localStorage.getItem('bloomora_orders')) || [];
      } catch (e) {
        return [];
      }
    },

    updateOrder: function (code, newStatus, newStatusText, newNote, newPhoto) {
      const orders = this.getOrders();
      const idx = orders.findIndex(o => o.code === code);
      if (idx !== -1) {
        orders[idx].status = newStatus;
        orders[idx].statusText = newStatusText;
        if (newNote) orders[idx].note = newNote;
        if (newPhoto) orders[idx].photo = newPhoto;
        localStorage.setItem('bloomora_orders', JSON.stringify(orders));
        return true;
      }
      return false;
    },

    // --- KHO NGUYÊN VẬT LIỆU ---
    getInventory: function () {
      try {
        return JSON.parse(localStorage.getItem('bloomora_inventory')) || [];
      } catch (e) {
        return [];
      }
    },

    saveInventoryItem: function (itemData) {
      const inv = this.getInventory();
      if (itemData.id) {
        // Cập nhật
        const idx = inv.findIndex(i => i.id === itemData.id);
        if (idx !== -1) {
          inv[idx] = Object.assign(inv[idx], itemData);
        }
      } else {
        // Thêm mới
        itemData.id = Date.now();
        inv.push(itemData);
      }
      this.recalculateInventoryLevels(inv);
      localStorage.setItem('bloomora_inventory', JSON.stringify(inv));
      return true;
    },

    adjustInventoryQty: function (id, delta) {
      const inv = this.getInventory();
      const item = inv.find(i => i.id === id);
      if (item) {
        item.qty = Math.max(0, item.qty + delta);
        this.recalculateInventoryLevels(inv);
        localStorage.setItem('bloomora_inventory', JSON.stringify(inv));
        return true;
      }
      return false;
    },

    deleteInventoryItem: function (id) {
      let inv = this.getInventory();
      inv = inv.filter(i => i.id !== id);
      localStorage.setItem('bloomora_inventory', JSON.stringify(inv));
      return true;
    },

    restockAll: function () {
      const inv = this.getInventory();
      inv.forEach(item => {
        item.qty += 15;
      });
      this.recalculateInventoryLevels(inv);
      localStorage.setItem('bloomora_inventory', JSON.stringify(inv));
      return true;
    },

    recalculateInventoryLevels: function (inv) {
      inv.forEach(item => {
        const threshold = item.minThreshold || 5;
        if (item.qty <= 3) {
          item.status = 'Cần nhập gấp';
          item.level = 'danger';
        } else if (item.qty <= threshold) {
          item.status = 'Sắp hết hàng';
          item.level = 'warning';
        } else {
          item.status = 'Đầy kho';
          item.level = 'safe';
        }
      });
    },

    // --- SẢN PHẨM HOA ---
    getProducts: function () {
      try {
        return JSON.parse(localStorage.getItem('bloomora_products')) || [];
      } catch (e) {
        return [];
      }
    },

    saveProduct: function (prodData) {
      const products = this.getProducts();
      if (prodData.id) {
        const idx = products.findIndex(p => p.id === prodData.id);
        if (idx !== -1) {
          products[idx] = Object.assign(products[idx], prodData);
        }
      } else {
        prodData.id = Date.now();
        products.push(prodData);
      }
      localStorage.setItem('bloomora_products', JSON.stringify(products));
      return true;
    },

    deleteProduct: function (id) {
      let products = this.getProducts();
      products = products.filter(p => p.id !== id);
      localStorage.setItem('bloomora_products', JSON.stringify(products));
      return true;
    }
  };

  // 3. TIỆN ÍCH DÙNG CHUNG CHO CẢ 3 TRANG ADMIN
  window.checkAdminSession = function () {
    const user = window.BloomoraAuth ? window.BloomoraAuth.getCurrentUser() : null;
    const banner = document.getElementById('adminNoticeBanner');
    const nameEl = document.getElementById('adminName');
    const avatarEl = document.getElementById('adminAvatar');

    if (!nameEl) return;

    if (!user || user.role !== 'admin') {
      if (banner) banner.classList.remove('hidden');
      nameEl.textContent = user ? user.name : 'Khách Xem Thử';
      if (avatarEl) avatarEl.textContent = user ? user.name.charAt(0).toUpperCase() : 'K';
    } else {
      if (banner) banner.classList.add('hidden');
      nameEl.textContent = user.name;
      if (avatarEl) avatarEl.textContent = user.name.charAt(0).toUpperCase();
    }
  };

  window.loginAsAdminQuickly = function () {
    if (window.BloomoraAuth) {
      window.BloomoraAuth.login('admin@bloomora.vn', '123456');
    }
    window.checkAdminSession();
  };

  window.handleAdminLogout = function () {
    if (window.BloomoraAuth) {
      window.BloomoraAuth.logout();
    }
    window.location.href = 'login.html';
  };

  // 4. LOGIC TRANG 1: ĐƠN HÀNG (ADMIN.HTML)
  let activeEditingCode = null;
  let currentAdminOrderFilter = 'all';

  window.filterAdminOrders = function (status, btnEl) {
    currentAdminOrderFilter = status;
    document.querySelectorAll('.admin-order-filter-btn').forEach(b => {
      b.className = 'admin-order-filter-btn px-3 py-1.5 rounded-xl bg-stone-100 text-stone-600 hover:bg-stone-200 transition flex-shrink-0 cursor-pointer font-semibold';
    });
    if (btnEl) {
      btnEl.className = 'admin-order-filter-btn px-3 py-1.5 rounded-xl bg-rose-600 text-white font-bold transition flex-shrink-0 cursor-pointer';
    }
    window.renderOrdersTable();
  };

  window.renderOrdersTable = function () {
    const tbody = document.getElementById('ordersTableBody');
    if (!tbody) return;

    const allOrders = window.BloomoraAdmin.getOrders();
    const badge = document.getElementById('orderCountBadge');
    const kpiTotal = document.getElementById('kpiTotalOrders');
    const kpiUrgent = document.getElementById('kpiUrgentOrders');

    if (badge) badge.textContent = allOrders.length + ' đơn trong ca';
    if (kpiTotal) kpiTotal.textContent = (allOrders.length + 21) + ' Đơn';

    const urgentCount = allOrders.filter(o => o.status === 'preparing' || o.status === 'received').length;
    if (kpiUrgent) kpiUrgent.textContent = urgentCount + ' Đơn Trong Ca';

    const orders = currentAdminOrderFilter === 'all'
      ? allOrders
      : allOrders.filter(o => o.status === currentAdminOrderFilter);

    tbody.innerHTML = '';

    if (orders.length === 0) {
      tbody.innerHTML = '<tr><td colspan="5" class="py-6 text-center text-stone-400">Không có đơn hàng nào phù hợp với bộ lọc hiện tại.</td></tr>';
      return;
    }

    orders.forEach(order => {
      let statusBadge = '';
      if (order.status === 'received') {
        statusBadge = '<span class="bg-stone-100 text-stone-700 px-2.5 py-0.5 rounded-full font-bold text-[10px] uppercase">1. Đã Tiếp Nhận</span>';
      } else if (order.status === 'preparing') {
        statusBadge = '<span class="bg-blue-100 text-blue-700 px-2.5 py-0.5 rounded-full font-bold text-[10px] uppercase">2. Đang Cắm Hoa</span>';
      } else if (order.status === 'photo_ready') {
        statusBadge = '<span class="bg-amber-100 text-amber-700 px-2.5 py-0.5 rounded-full font-bold text-[10px] uppercase">3. Đã Chụp Ảnh Thật</span>';
      } else if (order.status === 'delivering') {
        statusBadge = '<span class="bg-indigo-100 text-indigo-700 px-2.5 py-0.5 rounded-full font-bold text-[10px] uppercase">4. Đang Giao Hàng</span>';
      } else {
        statusBadge = '<span class="bg-emerald-100 text-emerald-700 px-2.5 py-0.5 rounded-full font-bold text-[10px] uppercase">5. Đã Hoàn Tất</span>';
      }

      const tr = document.createElement('tr');
      const isPaid = order.paymentStatus === 'paid';
      const paymentActionHtml = isPaid
        ? '<span class="inline-flex items-center space-x-1 text-[10px] text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-md font-bold"><i class="ph-bold ph-check"></i><span>Đã Nhận Tiền</span></span>'
        : `<button onclick="window.confirmOrderPayment('${order.code}')" class="inline-flex items-center space-x-1 text-[10px] text-amber-700 bg-amber-50 hover:bg-amber-100 border border-amber-300 px-2 py-0.5 rounded-md font-bold transition cursor-pointer" title="Admin đối soát tiền VietQR"><i class="ph-bold ph-arrows-clockwise"></i><span>Đối Soát VietQR</span></button>`;

      tr.innerHTML = `
        <td class="py-3">
          <div class="font-mono font-bold text-rose-600">${order.code}</div>
          <div class="text-[10px] text-stone-400 flex items-center space-x-1"><i class="ph ph-phone"></i><span>${order.phone || '0912345678'}</span></div>
        </td>
        <td class="py-3">
          <div class="font-semibold text-stone-900">${order.flower}</div>
          <div class="text-[10px] text-stone-500 truncate max-w-[220px] flex items-center space-x-1">
            <i class="ph ph-map-pin text-rose-500 flex-shrink-0"></i>
            <span class="truncate">${order.address || 'TP. Buôn Ma Thuột, Đắk Lắk'}</span>
          </div>
          <div class="text-[10px] text-stone-400 italic truncate max-w-[220px]">Băng rôn: ${order.ribbon}</div>
        </td>
        <td class="py-3 font-semibold text-stone-800">${order.slot}</td>
        <td class="py-3 space-y-1">
          <div>${statusBadge}</div>
          <div>${paymentActionHtml}</div>
        </td>
        <td class="py-3 text-right">
          <button onclick="openOrderModal('${order.code}')" class="bg-stone-100 hover:bg-rose-50 hover:text-rose-600 px-3 py-1.5 rounded-lg font-semibold transition text-xs cursor-pointer">
            Cập Nhật
          </button>
        </td>
      `;
      tbody.appendChild(tr);
    });
  };

  window.renderInventory = function () {
    const container = document.getElementById('inventoryContainer');
    if (!container) return;

    const inv = window.BloomoraAdmin.getInventory();
    container.innerHTML = '';

    inv.slice(0, 3).forEach(item => {
      let colorClass = 'text-rose-600';
      if (item.level === 'warning') colorClass = 'text-amber-600';
      if (item.level === 'safe') colorClass = 'text-emerald-600';

      const div = document.createElement('div');
      div.className = 'p-3 bg-stone-50 rounded-2xl border border-stone-100 flex items-center justify-between';
      div.innerHTML = `
        <div>
          <div class="font-bold text-xs text-stone-900">${item.name}</div>
          <div class="text-[11px] text-stone-400">${item.category}</div>
        </div>
        <div class="flex items-center space-x-2">
          <div class="text-right">
            <span class="text-xs font-black ${colorClass} block">Còn ${item.qty} ${item.unit}</span>
            <span class="text-[10px] text-stone-400">${item.status}</span>
          </div>
          <button onclick="handleRestockItem(${item.id})" class="p-1 rounded-lg bg-white border border-stone-200 hover:bg-rose-50 hover:text-rose-600 text-stone-600 text-xs font-bold transition" title="Nhập thêm +10">
            <i class="ph ph-plus"></i>
          </button>
        </div>
      `;
      container.appendChild(div);
    });
  };

  window.handleRestockItem = function (id) {
    window.BloomoraAdmin.adjustInventoryQty(id, 10);
    window.renderInventory();
    if (document.getElementById('fullInventoryTableBody')) {
      window.renderFullInventoryTable();
    }
  };

  window.handleRestockAll = function () {
    window.BloomoraAdmin.restockAll();
    window.renderInventory();
    if (document.getElementById('fullInventoryTableBody')) {
      window.renderFullInventoryTable();
    }
  };

  window.sampleFloristProofPhoto = function () {
    const proofUrl = 'https://images.unsplash.com/photo-1561181286-d3fee7d55364?auto=format&fit=crop&w=600&q=80';
    const inp = document.getElementById('modalPhotoInput');
    const select = document.getElementById('modalStatusSelect');
    const photoBox = document.getElementById('modalPhotoContainer');
    const photoImg = document.getElementById('modalOrderPhoto');

    if (inp) inp.value = proofUrl;
    if (select) select.value = 'photo_ready';
    if (photoImg) photoImg.src = proofUrl;
    if (photoBox) photoBox.classList.remove('hidden');
  };

  window.printOrderSlip = function () {
    if (!activeEditingCode) return;
    const orders = window.BloomoraAdmin.getOrders();
    const order = orders.find(o => o.code === activeEditingCode);
    if (!order) return;

    const senderText = order.isAnonymous ? '*** (Gửi tặng ẩn danh) ***' : `${order.sender || 'Khách Đặt Hoa'} (${order.phone || '0912345678'})`;
    const printWindow = window.open('', '_blank', 'width=700,height=750');
    if (!printWindow) {
      alert('Trình duyệt đang chặn mở cửa sổ in. Vui lòng cho phép popup để in phiếu!');
      return;
    }

    printWindow.document.write(`
      <!DOCTYPE html>
      <html lang="vi">
      <head>
        <meta charset="UTF-8">
        <title>Phiếu Giao Hoa & Băng Rôn - ${order.code}</title>
        <style>
          body { font-family: 'Segoe UI', Arial, sans-serif; padding: 24px; color: #1c1917; }
          .header { border-bottom: 2px dashed #dc2626; padding-bottom: 12px; margin-bottom: 16px; text-align: center; }
          .title { font-size: 20px; font-weight: bold; color: #dc2626; margin: 0; }
          .sub { font-size: 12px; color: #78716c; margin-top: 4px; }
          .code { font-size: 18px; font-weight: bold; font-family: monospace; background: #fee2e2; color: #991b1b; padding: 4px 8px; border-radius: 6px; display: inline-block; margin-top: 6px; }
          .row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13px; }
          .label { font-weight: bold; color: #44403c; width: 150px; }
          .val { flex: 1; }
          .ribbon-box { background: #b91c1c; color: #fef08a; padding: 12px; border-radius: 8px; font-weight: bold; font-size: 16px; text-align: center; margin: 16px 0; border: 2px solid #ca8a04; letter-spacing: 0.5px; }
          .footer { font-size: 11px; text-align: center; color: #a8a29e; margin-top: 24px; border-top: 1px solid #e7e5e4; padding-top: 12px; }
        </style>
      </head>
      <body>
        <div class="header">
          <div class="title">TIỆM HOA TƯƠI BLOOMORA ĐẮK LẮK</div>
          <div class="sub">120 Phan Chu Trinh, P. Thắng Lợi, TP. Buôn Ma Thuột - Hotline: 0946618960</div>
          <div class="code">MÃ ĐƠN HÀNG: ${order.code}</div>
        </div>
        <div class="row"><div class="label">Mẫu hoa tươi:</div><div class="val"><strong>${order.flower}</strong></div></div>
        <div class="row"><div class="label">Khung giờ giao:</div><div class="val"><strong>${order.slot}</strong></div></div>
        <div class="row"><div class="label">Người nhận:</div><div class="val"><strong>${order.receiver || 'Người Nhận'}</strong> (${order.receiverPhone || order.phone || '09xxxxxxxx'})</div></div>
        <div class="row"><div class="label">Địa chỉ giao:</div><div class="val"><strong>${order.address || 'TP. Buôn Ma Thuột, Đắk Lắk'}</strong></div></div>
        <div class="row"><div class="label">Người gửi:</div><div class="val">${senderText}</div></div>
        <div class="row"><div class="label">Thanh toán:</div><div class="val"><strong>${order.paymentStatus === 'paid' ? 'ĐÃ THANH TOÁN VIETQR' : 'THU TIỀN KHI GIAO (COD)'} - ${(order.total || 1850000).toLocaleString('vi-VN')}₫</strong></div></div>
        
        <div style="font-size: 12px; font-weight: bold; color: #b91c1c; margin-top: 12px;">NỘI DUNG CHỮ IN TRÊN BĂNG RÔN / THIỆP:</div>
        <div class="ribbon-box">
          ${order.ribbon}
        </div>

        <div style="font-size: 11px; color: #57534e; background: #fafaf9; padding: 8px; border-radius: 6px; border: 1px solid #e7e5e4;">
          * Shipper chú ý: Cầm hoa cẩn thận, không làm gãy cành. Chụp ảnh người nhận hoa gửi về tiệm khi hoàn thành giao hàng.
        </div>

        <div class="footer">
          Bloomora Floral E-Commerce Platform © 2026.
        </div>
        <script>
          window.onload = function() { window.print(); }
        </script>
      </body>
      </html>
    `);
    printWindow.document.close();
  };

  window.openOrderModal = function (code) {
    const orders = window.BloomoraAdmin.getOrders();
    const order = orders.find(o => o.code === code);
    if (!order) return;

    activeEditingCode = code;
    document.getElementById('modalOrderCode').textContent = order.code;
    document.getElementById('modalOrderFlower').textContent = order.flower;
    document.getElementById('modalOrderRibbon').textContent = 'Băng rôn: ' + order.ribbon;
    document.getElementById('modalStatusSelect').value = order.status;
    document.getElementById('modalOrderNote').value = order.note || '';

    const photoInput = document.getElementById('modalPhotoInput');
    if (photoInput) photoInput.value = order.photo || '';

    const photoBox = document.getElementById('modalPhotoContainer');
    const photoImg = document.getElementById('modalOrderPhoto');
    if (order.photo) {
      photoImg.src = order.photo;
      photoBox.classList.remove('hidden');
    } else {
      photoBox.classList.add('hidden');
    }

    document.getElementById('orderModal')?.classList.remove('hidden');
  };

  window.closeOrderModal = function () {
    document.getElementById('orderModal')?.classList.add('hidden');
    activeEditingCode = null;
  };

  window.saveOrderStatus = function () {
    if (!activeEditingCode) return;

    const select = document.getElementById('modalStatusSelect');
    const newStatus = select.value;
    const newStatusText = select.options[select.selectedIndex].text;
    const newNote = document.getElementById('modalOrderNote').value.trim();
    const photoInput = document.getElementById('modalPhotoInput')?.value.trim();

    let newPhoto = photoInput || null;
    if (newStatus === 'photo_ready' && !newPhoto) {
      newPhoto = 'https://images.unsplash.com/photo-1561181286-d3fee7d55364?auto=format&fit=crop&w=600&q=80';
    }

    window.BloomoraAdmin.updateOrder(activeEditingCode, newStatus, newStatusText, newNote, newPhoto);
    window.closeOrderModal();
    window.renderOrdersTable();
  };

  window.refreshDashboard = function () {
    const icon = document.getElementById('refreshIcon');
    if (icon) icon.classList.add('animate-spin');
    setTimeout(() => {
      window.checkAdminSession();
      window.renderOrdersTable();
      window.renderInventory();
      if (icon) icon.classList.remove('animate-spin');
    }, 300);
  };

  // 5. LOGIC TRANG 2: QUẢN LÝ SẢN PHẨM HOA (ADMIN-PRODUCTS.HTML)
  let activeFilterCategory = 'all';

  window.renderProductsTable = function () {
    const tbody = document.getElementById('productsTableBody');
    if (!tbody) return;

    let products = window.BloomoraAdmin.getProducts();
    const countBadge = document.getElementById('productCountBadge');
    if (countBadge) countBadge.textContent = products.length + ' sản phẩm';

    if (activeFilterCategory !== 'all') {
      products = products.filter(p => p.category === activeFilterCategory);
    }

    tbody.innerHTML = '';

    if (products.length === 0) {
      tbody.innerHTML = '<tr><td colspan="6" class="py-8 text-center text-stone-400">Không tìm thấy sản phẩm nào trong danh mục này.</td></tr>';
      return;
    }

    products.forEach(p => {
      const formattedPrice = new Intl.NumberFormat('vi-VN').format(p.price) + '₫';
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td class="py-3.5">
          <div class="flex items-center space-x-3">
            <img src="${p.image}" alt="${p.name}" class="w-11 h-11 rounded-xl object-cover border border-stone-200 shadow-sm flex-shrink-0">
            <div>
              <div class="font-bold text-stone-900">${p.name}</div>
              <div class="text-[11px] text-stone-400 truncate max-w-[200px]">${p.description || ''}</div>
            </div>
          </div>
        </td>
        <td class="py-3.5 font-medium text-stone-600">${p.category}</td>
        <td class="py-3.5 font-bold text-rose-600">${formattedPrice}</td>
        <td class="py-3.5 font-semibold text-stone-800">${p.stock} bó/chậu</td>
        <td class="py-3.5">
          <span class="bg-emerald-50 text-emerald-700 px-2.5 py-0.5 rounded-full font-bold text-[10px] uppercase">
            ${p.status || 'Còn hàng'}
          </span>
        </td>
        <td class="py-3.5 text-right space-x-1.5">
          <button onclick="openProductModal(${p.id})" class="p-1.5 rounded-lg bg-stone-100 hover:bg-rose-50 hover:text-rose-600 text-stone-600 transition" title="Sửa sản phẩm">
            <i class="ph ph-pencil-simple text-sm"></i>
          </button>
          <button onclick="handleDeleteProduct(${p.id})" class="p-1.5 rounded-lg bg-stone-100 hover:bg-red-50 hover:text-red-600 text-stone-600 transition" title="Xóa">
            <i class="ph ph-trash text-sm"></i>
          </button>
        </td>
      `;
      tbody.appendChild(tr);
    });
  };

  window.filterProducts = function (cat, btn) {
    activeFilterCategory = cat;
    document.querySelectorAll('.prod-filter-btn').forEach(b => {
      b.className = 'prod-filter-btn px-3 py-1.5 rounded-xl text-xs font-semibold text-stone-600 hover:bg-stone-100 transition';
    });
    if (btn) {
      btn.className = 'prod-filter-btn px-3 py-1.5 rounded-xl text-xs font-bold bg-rose-600 text-white shadow-sm transition';
    }
    window.renderProductsTable();
  };

  let editingProductId = null;

  window.openProductModal = function (id) {
    editingProductId = id || null;
    const modalTitle = document.getElementById('prodModalTitle');
    const nameInp = document.getElementById('prodName');
    const catInp = document.getElementById('prodCategory');
    const priceInp = document.getElementById('prodPrice');
    const stockInp = document.getElementById('prodStock');
    const imgInp = document.getElementById('prodImage');
    const descInp = document.getElementById('prodDesc');

    if (id) {
      const products = window.BloomoraAdmin.getProducts();
      const p = products.find(x => x.id === id);
      if (p) {
        if (modalTitle) modalTitle.textContent = 'Chỉnh Sửa Sản Phẩm Hoa';
        nameInp.value = p.name;
        catInp.value = p.category;
        priceInp.value = p.price;
        stockInp.value = p.stock;
        imgInp.value = p.image || '';
        descInp.value = p.description || '';
      }
    } else {
      if (modalTitle) modalTitle.textContent = 'Thêm Mới Mẫu Hoa Tươi';
      nameInp.value = '';
      catInp.value = 'Hoa Khai Trương';
      priceInp.value = '';
      stockInp.value = 10;
      imgInp.value = 'https://images.unsplash.com/photo-1561181286-d3fee7d55364?auto=format&fit=crop&w=400&q=80';
      descInp.value = '';
    }

    document.getElementById('productModal')?.classList.remove('hidden');
  };

  window.closeProductModal = function () {
    document.getElementById('productModal')?.classList.add('hidden');
    editingProductId = null;
  };

  window.saveProduct = function (e) {
    if (e) e.preventDefault();
    const name = document.getElementById('prodName')?.value.trim();
    const category = document.getElementById('prodCategory')?.value;
    const price = parseInt(document.getElementById('prodPrice')?.value, 10);
    const stock = parseInt(document.getElementById('prodStock')?.value, 10) || 10;
    const image = document.getElementById('prodImage')?.value.trim() || 'https://images.unsplash.com/photo-1561181286-d3fee7d55364?auto=format&fit=crop&w=400&q=80';
    const description = document.getElementById('prodDesc')?.value.trim();

    if (!name || isNaN(price)) {
      alert('Vui lòng nhập tên hoa và giá bán hợp lệ!');
      return;
    }

    const prodData = {
      name: name,
      category: category,
      price: price,
      originalPrice: price,
      stock: stock,
      image: image,
      description: description,
      badge: 'Mẫu Mới',
      rating: 5.0,
      sold: 0,
      status: stock > 0 ? 'Còn hàng' : 'Hết hàng'
    };

    if (editingProductId) {
      prodData.id = editingProductId;
    }

    window.BloomoraAdmin.saveProduct(prodData);
    window.closeProductModal();
    window.renderProductsTable();
  };

  // Tính Năng AI 3 (Tuỳ Chọn Admin): AI Viết Mô Tả Mẫu Hoa Mới
  window.generateAIProductDesc = async function () {
    const name = document.getElementById('prodName')?.value.trim();
    const category = document.getElementById('prodCategory')?.value || 'Hoa Khai Trương';
    const price = document.getElementById('prodPrice')?.value || '1000000';
    const descEl = document.getElementById('prodDesc');
    if (!descEl) return;

    if (!name) {
      alert('Vui lòng nhập Tên mẫu hoa trước để AI có thể sinh mô tả phù hợp!');
      document.getElementById('prodName')?.focus();
      return;
    }

    descEl.value = 'AI đang sáng tạo mô tả nghệ thuật cho mẫu hoa...';

    try {
      const res = await fetch('../php/api/ai/product-desc.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name: name, category: category, price: price })
      });
      const data = await res.json();
      if (data && data.success && data.description) {
        descEl.value = data.description;
        return;
      }
    } catch (e) {}

    // Fallback heuristic nếu không qua server
    descEl.value = `Mẫu hoa ${name} thuộc phân loại ${category}, được các nghệ nhân florist tại tiệm Bloomora Buôn Ma Thuột thiết kế tinh tế. Tuyển chọn hoa tươi mới trong ngày nhập từ Đà Lạt, kết hợp giấy bọc cao cấp và ruy-băng in vi tính. Cam kết chụp ảnh hoa thật (Photo Proof) gửi khách duyệt trước khi giao hàng trong 60-90 phút.`;
  };

  window.handleDeleteProduct = function (id) {
    if (confirm('Bạn có chắc chắn muốn xóa mẫu hoa này khỏi danh mục không?')) {
      window.BloomoraAdmin.deleteProduct(id);
      window.renderProductsTable();
    }
  };

  // 6. LOGIC TRANG 3: QUẢN LÝ KHO & NGUYÊN VẬT LIỆU (ADMIN-INVENTORY.HTML)
  window.renderFullInventoryTable = function () {
    const tbody = document.getElementById('fullInventoryTableBody');
    if (!tbody) return;

    const inv = window.BloomoraAdmin.getInventory();
    const countBadge = document.getElementById('invTotalCountBadge');
    const dangerCountEl = document.getElementById('invDangerCount');
    const safeCountEl = document.getElementById('invSafeCount');

    if (countBadge) countBadge.textContent = inv.length + ' mặt hàng';

    const dangerCount = inv.filter(i => i.level === 'danger').length;
    const safeCount = inv.filter(i => i.level === 'safe').length;
    if (dangerCountEl) dangerCountEl.textContent = dangerCount + ' Cần Nhập';
    if (safeCountEl) safeCountEl.textContent = safeCount + ' Mặt Hàng';

    tbody.innerHTML = '';

    if (inv.length === 0) {
      tbody.innerHTML = '<tr><td colspan="6" class="py-8 text-center text-stone-400">Kho vật tư hiện đang trống.</td></tr>';
      return;
    }

    inv.forEach(item => {
      let statusBadge = '';
      if (item.level === 'danger') {
        statusBadge = '<span class="bg-red-100 text-red-700 px-2.5 py-0.5 rounded-full font-bold text-[10px] uppercase">Cần nhập gấp</span>';
      } else if (item.level === 'warning') {
        statusBadge = '<span class="bg-amber-100 text-amber-700 px-2.5 py-0.5 rounded-full font-bold text-[10px] uppercase">Sắp hết hàng</span>';
      } else {
        statusBadge = '<span class="bg-emerald-100 text-emerald-700 px-2.5 py-0.5 rounded-full font-bold text-[10px] uppercase">Đầy kho</span>';
      }

      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td class="py-3.5 font-bold text-stone-900">${item.name}</td>
        <td class="py-3.5 text-stone-600">${item.category}</td>
        <td class="py-3.5 font-black text-rose-600 text-sm">${item.qty} <span class="text-xs font-normal text-stone-500">${item.unit}</span></td>
        <td class="py-3.5 text-stone-500">${item.minThreshold || 5} ${item.unit}</td>
        <td class="py-3.5">${statusBadge}</td>
        <td class="py-3.5 text-right space-x-1">
          <button onclick="handleAdjustInventory(${item.id}, 5)" class="px-2 py-1 rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 font-bold text-xs transition" title="Nhập thêm +5">
            +5
          </button>
          <button onclick="handleAdjustInventory(${item.id}, -1)" class="px-2 py-1 rounded-lg bg-stone-100 text-stone-700 hover:bg-stone-200 font-bold text-xs transition" title="Xuất dùng -1">
            -1
          </button>
          <button onclick="handleDeleteInventory(${item.id})" class="p-1 rounded-lg bg-stone-100 hover:bg-red-50 hover:text-red-600 text-stone-400 transition" title="Xóa">
            <i class="ph ph-trash text-sm"></i>
          </button>
        </td>
      `;
      tbody.appendChild(tr);
    });
  };

  window.handleAdjustInventory = function (id, delta) {
    window.BloomoraAdmin.adjustInventoryQty(id, delta);
    window.renderFullInventoryTable();
  };

  window.handleDeleteInventory = function (id) {
    if (confirm('Bạn có muốn xóa vật tư này khỏi kho không?')) {
      window.BloomoraAdmin.deleteInventoryItem(id);
      window.renderFullInventoryTable();
    }
  };


  window.openInventoryModal = function () {
    document.getElementById('invName').value = '';
    document.getElementById('invCategory').value = 'Hoa cành chính';
    document.getElementById('invQty').value = 10;
    document.getElementById('invUnit').value = 'bó';
    document.getElementById('invMin').value = 5;
    document.getElementById('inventoryModal')?.classList.remove('hidden');
  };

  window.closeInventoryModal = function () {
    document.getElementById('inventoryModal')?.classList.add('hidden');
  };

  window.saveInventoryItemFromModal = function (e) {
    if (e) e.preventDefault();
    const name = document.getElementById('invName')?.value.trim();
    const category = document.getElementById('invCategory')?.value;
    const qty = parseInt(document.getElementById('invQty')?.value, 10) || 0;
    const unit = document.getElementById('invUnit')?.value.trim() || 'bó';
    const minThreshold = parseInt(document.getElementById('invMin')?.value, 10) || 5;

    if (!name) {
      alert('Vui lòng nhập tên nguyên vật liệu!');
      return;
    }

    window.BloomoraAdmin.saveInventoryItem({
      name: name,
      category: category,
      qty: qty,
      unit: unit,
      minThreshold: minThreshold
    });

    window.closeInventoryModal();
    window.renderFullInventoryTable();
  };

  // 7. HỆ THỐNG GIỎ HÀNG BLOOMORA (BLOOMORA CART)
  window.BloomoraCart = {
    getItems: function () {
      try {
        return JSON.parse(localStorage.getItem('bloomora_cart')) || [];
      } catch (e) {
        return [];
      }
    },
    addItem: function (item) {
      const cart = this.getItems();
      const existing = cart.find(c => c.id === item.id && c.slot === item.slot && c.ribbon === item.ribbon);
      if (existing) {
        existing.qty += item.qty;
      } else {
        cart.push(item);
      }
      localStorage.setItem('bloomora_cart', JSON.stringify(cart));
      window.updateCartBadge();
      return true;
    },
    updateQty: function (index, delta) {
      const cart = this.getItems();
      if (cart[index]) {
        cart[index].qty += delta;
        if (cart[index].qty <= 0) {
          cart.splice(index, 1);
        }
        localStorage.setItem('bloomora_cart', JSON.stringify(cart));
        window.updateCartBadge();
        window.renderCartDrawer();
      }
    },
    removeItem: function (index) {
      const cart = this.getItems();
      if (cart[index]) {
        cart.splice(index, 1);
        localStorage.setItem('bloomora_cart', JSON.stringify(cart));
        window.updateCartBadge();
        window.renderCartDrawer();
      }
    },
    clear: function () {
      localStorage.removeItem('bloomora_cart');
      window.updateCartBadge();
      window.renderCartDrawer();
    },
    getTotal: function () {
      return this.getItems().reduce((sum, i) => sum + (i.price * i.qty), 0);
    },
    getCount: function () {
      return this.getItems().reduce((sum, i) => sum + i.qty, 0);
    }
  };

  // 8. HIỂN THỊ DANH MỤC HOA TƯƠI WISH FLORIST (CATALOG)
  let currentCatalogCategory = 'all';

  window.renderCatalog = function (filterCategory = 'all') {
    const grid = document.getElementById('catalogGrid');
    if (!grid) return;

    currentCatalogCategory = filterCategory;
    const products = window.BloomoraAdmin.getProducts();
    const filtered = filterCategory === 'all'
      ? products
      : products.filter(p => p.category === filterCategory);

    grid.innerHTML = '';

    if (filtered.length === 0) {
      grid.innerHTML = `
        <div class="col-span-full py-16 text-center text-stone-400 bg-white rounded-3xl border border-stone-200">
          <i class="ph ph-flower-lotus text-4xl text-stone-300 mb-2"></i>
          <p class="text-sm font-semibold">Chưa có mẫu hoa nào trong danh mục này.</p>
        </div>
      `;
      return;
    }

    filtered.forEach(p => {
      const discountPercent = p.originalPrice && p.originalPrice > p.price
        ? Math.round(((p.originalPrice - p.price) / p.originalPrice) * 100)
        : null;

      const card = document.createElement('div');
      card.className = 'group bg-white rounded-3xl border border-stone-200/90 shadow-sm hover:shadow-xl hover:border-rose-300 transition-all duration-300 flex flex-col overflow-hidden';
      card.innerHTML = `
        <!-- Ảnh Sản Phẩm & Huy Hiệu Wish Florist -->
        <div class="relative overflow-hidden aspect-[4/5] bg-stone-100 cursor-pointer" onclick="openQuickOrderModal(${p.id})">
          <img 
            src="${p.image}" 
            alt="${p.name}" 
            loading="lazy"
            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 ease-out"
          >
          <div class="absolute top-3 left-3 flex flex-col gap-1 items-start">
            <span class="bg-rose-600 text-white text-[10px] font-extrabold px-2.5 py-0.5 rounded-full uppercase shadow-md shadow-rose-600/30">
              ${p.badge || 'Mẫu Mới'}
            </span>
            ${discountPercent ? `<span class="bg-amber-500 text-white text-[9px] font-bold px-2 py-0.5 rounded-full uppercase shadow">- ${discountPercent}%</span>` : ''}
          </div>
          <div class="absolute top-3 right-3 bg-white/90 backdrop-blur-sm text-stone-800 text-[10px] font-bold px-2 py-0.5 rounded-full shadow flex items-center space-x-1">
            <i class="ph-fill ph-camera text-rose-500"></i>
            <span>Duyệt Ảnh</span>
          </div>
        </div>

        <!-- Thông Tin Mẫu Hoa -->
        <div class="p-5 flex-1 flex flex-col justify-between space-y-3">
          <div>
            <div class="flex items-center justify-between text-[11px] mb-1">
              <span class="font-semibold text-stone-400 uppercase tracking-wider">${p.category}</span>
              <span class="flex items-center space-x-1 text-amber-500 font-bold">
                <i class="ph-fill ph-star text-xs"></i>
                <span class="text-stone-700">${p.rating ? p.rating.split(' ')[0] : '5.0'}</span>
              </span>
            </div>
            
            <h3 onclick="openQuickOrderModal(${p.id})" class="font-bold text-stone-900 text-sm group-hover:text-rose-600 transition cursor-pointer line-clamp-2 leading-snug">
              ${p.name}
            </h3>

            <!-- Cam kết dịch vụ thu nhỏ -->
            <div class="flex items-center space-x-1 text-[10px] text-emerald-700 bg-emerald-50/80 px-2 py-1 rounded-lg mt-2 font-medium">
              <i class="ph ph-shield-check text-xs text-emerald-600"></i>
              <span>📸 Ảnh thật gửi Zalo • 🎀 Băng rôn 0đ</span>
            </div>
          </div>

          <!-- Giá & Nút Thao Tác -->
          <div class="pt-3 border-t border-stone-100 flex items-center justify-between gap-2">
            <div>
              <div class="text-base font-extrabold text-rose-600 leading-none">
                ${p.price.toLocaleString('vi-VN')}₫
              </div>
              ${p.originalPrice ? `<div class="text-[11px] text-stone-400 line-through mt-0.5 leading-none">${p.originalPrice.toLocaleString('vi-VN')}₫</div>` : ''}
            </div>

            <div class="flex items-center space-x-1.5">
              <button 
                onclick="quickAddToCart(${p.id})" 
                title="Thêm nhanh vào giỏ"
                class="w-9 h-9 rounded-xl bg-stone-100 hover:bg-rose-50 hover:text-rose-600 text-stone-700 flex items-center justify-center transition text-base">
                <i class="ph ph-shopping-bag"></i>
              </button>
              <button 
                onclick="openQuickOrderModal(${p.id})" 
                class="bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold px-3 py-2 rounded-xl shadow-md shadow-rose-600/20 transition flex items-center space-x-1">
                <span>Đặt Nhanh</span>
              </button>
            </div>
          </div>
        </div>
      `;
      grid.appendChild(card);
    });
  };

  window.filterCatalog = function (category, btn) {
    const buttons = document.querySelectorAll('.catalog-filter-btn');
    buttons.forEach(b => {
      b.className = 'catalog-filter-btn flex-shrink-0 px-4 py-2.5 rounded-2xl text-xs font-semibold bg-stone-100 text-stone-600 hover:bg-stone-200 transition flex items-center space-x-1.5';
    });
    if (btn) {
      btn.className = 'catalog-filter-btn flex-shrink-0 px-4 py-2.5 rounded-2xl text-xs font-bold bg-rose-600 text-white shadow-md shadow-rose-600/20 transition flex items-center space-x-1.5';
    }
    window.renderCatalog(category);
  };

  // 9. MODAL ĐẶT HOA NHANH CHUẨN WISH FLORIST
  let activeModalProduct = null;

  window.openQuickOrderModal = function (productId) {
    const products = window.BloomoraAdmin.getProducts();
    const product = products.find(p => p.id === productId);
    if (!product) return;

    activeModalProduct = product;

    const modal = document.getElementById('quickOrderModal');
    if (!modal) return;

    const imgEl = document.getElementById('modalProductImage');
    if (imgEl) {
      imgEl.src = product.image;
      imgEl.alt = product.name;
    }

    const catEl = document.getElementById('modalProductCategory');
    if (catEl) catEl.textContent = product.category;

    const nameEl = document.getElementById('modalProductName');
    if (nameEl) nameEl.textContent = product.name;

    const priceEl = document.getElementById('modalProductPrice');
    if (priceEl) priceEl.textContent = product.price.toLocaleString('vi-VN') + '₫';

    const origPriceEl = document.getElementById('modalProductOriginalPrice');
    if (origPriceEl) {
      if (product.originalPrice) {
        origPriceEl.textContent = product.originalPrice.toLocaleString('vi-VN') + '₫';
        origPriceEl.classList.remove('hidden');
      } else {
        origPriceEl.classList.add('hidden');
      }
    }

    const badgeEl = document.getElementById('modalProductBadge');
    if (badgeEl) {
      badgeEl.textContent = product.badge || 'Mẫu Mới';
    }

    const descEl = document.getElementById('modalProductDesc');
    if (descEl) descEl.textContent = product.description || 'Hoa tươi thiết kế cao cấp, cam kết hoa tươi nguyên vẹn khi đến tay người nhận.';

    const qtyInput = document.getElementById('modalQtyInput');
    if (qtyInput) qtyInput.value = 1;

    const ribbonInput = document.getElementById('modalRibbonText');
    if (ribbonInput) ribbonInput.value = '';

    const slotRadios = document.querySelectorAll('input[name="modalDeliverySlot"]');
    if (slotRadios.length > 0) slotRadios[0].checked = true;

    modal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
  };

  window.closeQuickOrderModal = function () {
    const modal = document.getElementById('quickOrderModal');
    if (modal) {
      modal.classList.add('hidden');
      document.body.classList.remove('overflow-hidden');
    }
  };

  window.changeModalQty = function (delta) {
    const input = document.getElementById('modalQtyInput');
    if (!input) return;
    let val = parseInt(input.value, 10) || 1;
    val = Math.max(1, Math.min(99, val + delta));
    input.value = val;
  };

  window.submitModalAddToCart = function (isBuyNow = false) {
    if (!activeModalProduct) return;

    const qty = parseInt(document.getElementById('modalQtyInput')?.value, 10) || 1;
    const ribbon = document.getElementById('modalRibbonText')?.value.trim() || 'Không in băng rôn (Thiệp trắng)';
    const selectedSlotRadio = document.querySelector('input[name="modalDeliverySlot"]:checked');
    const slot = selectedSlotRadio ? selectedSlotRadio.value : '08:00 - 10:00';

    const cartItem = {
      id: activeModalProduct.id,
      name: activeModalProduct.name,
      category: activeModalProduct.category,
      price: activeModalProduct.price,
      image: activeModalProduct.image,
      ribbon: ribbon,
      slot: slot,
      qty: qty
    };

    window.BloomoraCart.addItem(cartItem);
    window.closeQuickOrderModal();

    if (isBuyNow) {
      window.openCartDrawer();
    } else {
      window.showToast(`Đã thêm "${activeModalProduct.name}" vào giỏ hàng!`);
    }
  };

  window.quickAddToCart = function (productId) {
    const products = window.BloomoraAdmin.getProducts();
    const product = products.find(p => p.id === productId);
    if (!product) return;

    const cartItem = {
      id: product.id,
      name: product.name,
      category: product.category,
      price: product.price,
      image: product.image,
      ribbon: 'Chúc mừng (In băng rôn vi tính miễn phí)',
      slot: 'Giao trong ngày (60-90p)',
      qty: 1
    };

    window.BloomoraCart.addItem(cartItem);
    window.showToast(`Đã thêm "${product.name}" vào giỏ hàng!`);
  };

  // 10. GIỎ HÀNG SLIDE-OVER DRAWER
  window.openCartDrawer = function () {
    const drawer = document.getElementById('cartDrawer');
    if (drawer) {
      window.renderCartDrawer();
      drawer.classList.remove('hidden');
      document.body.classList.add('overflow-hidden');
    }
  };

  window.closeCartDrawer = function () {
    const drawer = document.getElementById('cartDrawer');
    if (drawer) {
      drawer.classList.add('hidden');
      document.body.classList.remove('overflow-hidden');
    }
  };

  let appliedCartVoucher = null;
  let latestCheckoutOrder = null;

  window.applyCartVoucher = function () {
    const input = document.getElementById('cartVoucherInput');
    const code = input?.value.trim().toUpperCase();
    if (!code) {
      alert('Vui lòng nhập mã giảm giá!');
      return;
    }

    const subtotal = window.BloomoraCart.getTotal();
    if (subtotal <= 0) {
      alert('Giỏ hàng chưa có sản phẩm để áp dụng mã giảm giá!');
      return;
    }

    if (code === 'BMT50K') {
      appliedCartVoucher = { code: 'BMT50K', discount: 50000, desc: 'Giảm 50.000₫ cho khách Đắk Lắk' };
    } else if (code === 'BLOOMNEW10') {
      const disc = Math.round(subtotal * 0.1);
      appliedCartVoucher = { code: 'BLOOMNEW10', discount: disc, desc: 'Giảm 10% đơn hàng đầu tiên' };
    } else if (code === 'FREESHIPBMT') {
      appliedCartVoucher = { code: 'FREESHIPBMT', discount: 15000, desc: 'Miễn phí giao hàng nội thành Buôn Ma Thuột' };
    } else {
      alert(`Mã giảm giá "${code}" không hợp lệ hoặc đã hết hạn! Bạn có thể thử mã BMT50K hoặc BLOOMNEW10.`);
      return;
    }

    const box = document.getElementById('voucherNoticeBox');
    const text = document.getElementById('voucherNoticeText');
    if (box && text) {
      box.classList.remove('hidden');
      text.innerHTML = `<i class="ph-bold ph-check"></i> Đã áp dụng: ${appliedCartVoucher.desc} (-${appliedCartVoucher.discount.toLocaleString('vi-VN')}₫)`;
    }

    window.renderCartDrawer();
  };

  window.removeCartVoucher = function () {
    appliedCartVoucher = null;
    const box = document.getElementById('voucherNoticeBox');
    const input = document.getElementById('cartVoucherInput');
    if (box) box.classList.add('hidden');
    if (input) input.value = '';
    window.renderCartDrawer();
  };

  window.renderCartDrawer = function () {
    const list = document.getElementById('cartDrawerList');
    const subtotalEl = document.getElementById('cartDrawerSubtotal');
    const discountRow = document.getElementById('cartDiscountRow');
    const discountEl = document.getElementById('cartDrawerDiscount');
    const totalEl = document.getElementById('cartDrawerTotal');
    if (!list) return;

    // Tự điền thông tin người gửi nếu là thành viên đã đăng nhập (US10)
    try {
      const authUser = JSON.parse(localStorage.getItem('bloomora_auth_user'));
      if (authUser) {
        const sName = document.getElementById('cartSenderName');
        const sPhone = document.getElementById('cartSenderPhone');
        if (sName && !sName.value) sName.value = authUser.name || authUser.fullName || 'Nguyễn Văn Khách';
        if (sPhone && !sPhone.value) sPhone.value = authUser.phone || '0912345678';
      }
    } catch (e) {}

    const items = window.BloomoraCart.getItems();
    const subtotal = window.BloomoraCart.getTotal();
    let discount = 0;

    if (appliedCartVoucher) {
      discount = Math.min(appliedCartVoucher.discount, subtotal);
      if (discountRow) discountRow.classList.remove('hidden');
      if (discountEl) discountEl.textContent = `-${discount.toLocaleString('vi-VN')}₫`;
    } else {
      if (discountRow) discountRow.classList.add('hidden');
    }

    const finalTotal = Math.max(0, subtotal - discount);

    if (subtotalEl) subtotalEl.textContent = subtotal.toLocaleString('vi-VN') + '₫';
    if (totalEl) totalEl.textContent = finalTotal.toLocaleString('vi-VN') + '₫';

    list.innerHTML = '';

    if (items.length === 0) {
      list.innerHTML = `
        <div class="py-16 text-center text-stone-400 space-y-3">
          <div class="w-16 h-16 rounded-full bg-stone-100 text-stone-300 mx-auto flex items-center justify-center text-3xl">
            <i class="ph ph-shopping-bag"></i>
          </div>
          <p class="text-xs font-semibold text-stone-600">Giỏ hàng của bạn đang trống</p>
          <button onclick="closeCartDrawer()" class="text-xs font-bold text-rose-600 hover:underline">
            Khám phá các mẫu hoa tươi &rarr;
          </button>
        </div>
      `;
      return;
    }

    items.forEach((item, index) => {
      const itemEl = document.createElement('div');
      itemEl.className = 'p-3.5 bg-stone-50 rounded-2xl border border-stone-200/80 flex space-x-3 text-xs';
      itemEl.innerHTML = `
        <img src="${item.image}" alt="${item.name}" class="w-16 h-20 object-cover rounded-xl flex-shrink-0 bg-stone-200">
        <div class="flex-1 flex flex-col justify-between">
          <div>
            <div class="flex items-start justify-between">
              <h4 class="font-bold text-stone-900 leading-snug">${item.name}</h4>
              <button onclick="BloomoraCart.removeItem(${index})" class="text-stone-400 hover:text-rose-600 p-0.5 ml-1" title="Xóa mẫu hoa">
                <i class="ph ph-trash text-sm"></i>
              </button>
            </div>
            <div class="text-[10px] text-stone-500 mt-1 space-y-0.5">
              <div class="flex items-center space-x-1 text-emerald-700">
                <i class="ph ph-clock"></i>
                <span>Giờ giao: <strong>${item.slot}</strong></span>
              </div>
              <div class="flex items-start space-x-1 text-rose-700">
                <i class="ph ph-ribbon mt-0.5"></i>
                <span class="truncate max-w-[190px]">Băng rôn: <em>${item.ribbon}</em></span>
              </div>
            </div>
          </div>

          <div class="flex items-center justify-between pt-2 border-t border-stone-200/60 mt-2">
            <div class="flex items-center border border-stone-200 bg-white rounded-lg px-1 py-0.5">
              <button onclick="BloomoraCart.updateQty(${index}, -1)" class="w-5 h-5 flex items-center justify-center font-bold text-stone-600 hover:text-rose-600">-</button>
              <span class="w-6 text-center font-bold text-[11px]">${item.qty}</span>
              <button onclick="BloomoraCart.updateQty(${index}, 1)" class="w-5 h-5 flex items-center justify-center font-bold text-stone-600 hover:text-rose-600">+</button>
            </div>
            <span class="font-extrabold text-rose-600 text-xs">${(item.price * item.qty).toLocaleString('vi-VN')}₫</span>
          </div>
        </div>
      `;
      list.appendChild(itemEl);
    });
  };

  window.checkoutCart = function () {
    const items = window.BloomoraCart.getItems();
    if (items.length === 0) {
      alert('Giỏ hàng của bạn đang trống! Vui lòng chọn mẫu hoa trước.');
      return;
    }

    const senderName = document.getElementById('cartSenderName')?.value.trim() || 'Nguyễn Văn Khách';
    const senderPhone = document.getElementById('cartSenderPhone')?.value.trim() || '0912345678';
    const receiverName = document.getElementById('cartReceiverName')?.value.trim();
    const receiverPhone = document.getElementById('cartReceiverPhone')?.value.trim();
    const deliveryAddress = document.getElementById('cartDeliveryAddress')?.value.trim();
    const isAnonymous = document.getElementById('cartIsAnonymous')?.checked || false;

    if (!receiverName) {
      alert('Vui lòng nhập Tên người nhận hoa!');
      document.getElementById('cartReceiverName')?.focus();
      return;
    }

    if (!receiverPhone) {
      alert('Vui lòng nhập Số điện thoại người nhận hoa!');
      document.getElementById('cartReceiverPhone')?.focus();
      return;
    }

    if (!deliveryAddress) {
      alert('Vui lòng nhập Địa chỉ giao hoa tại Buôn Ma Thuột / Đắk Lắk!');
      document.getElementById('cartDeliveryAddress')?.focus();
      return;
    }

    const subtotal = window.BloomoraCart.getTotal();
    let discount = 0;
    if (appliedCartVoucher) {
      discount = Math.min(appliedCartVoucher.discount, subtotal);
    }
    const finalTotal = Math.max(0, subtotal - discount);

    const orders = window.BloomoraAdmin.getOrders();
    const orderCode = 'BL' + Math.floor(10000 + Math.random() * 90000);
    const summaryFlower = items.map(i => `${i.name} (x${i.qty})`).join(', ');
    const primaryRibbon = items[0].ribbon;
    const primarySlot = items[0].slot;

    const newOrder = {
      code: orderCode,
      flower: summaryFlower,
      ribbon: primaryRibbon,
      slot: primarySlot,
      phone: senderPhone,
      sender: senderName,
      receiver: receiverName,
      receiverPhone: receiverPhone,
      address: deliveryAddress,
      isAnonymous: isAnonymous,
      subtotal: subtotal,
      discount: discount,
      total: finalTotal,
      voucherCode: appliedCartVoucher ? appliedCartVoucher.code : null,
      paymentMethod: 'vietqr',
      paymentStatus: 'unpaid',
      status: 'received',
      statusText: '1. Đã Tiếp Nhận',
      photo: '',
      photoStatus: 'none',
      note: isAnonymous ? 'Gửi tặng ẩn danh. Che tên người gửi trên phiếu giao hoa.' : 'Đơn mới đặt qua website Bloomora Đắk Lắk.'
    };

    orders.unshift(newOrder);
    localStorage.setItem('bloomora_orders', JSON.stringify(orders));

    latestCheckoutOrder = newOrder;
    window.BloomoraCart.clear();
    appliedCartVoucher = null;
    window.closeCartDrawer();

    // Mở popup thanh toán VietQR Napas
    window.openVietQRModal(orderCode, finalTotal);
  };

  // 7.1. TIỆN ÍCH THANH TOÁN VIETQR NAPAS
  window.openVietQRModal = function (orderCode, amount) {
    const modal = document.getElementById('vietQRModal');
    const qrImg = document.getElementById('vietQRImage');
    const amountEl = document.getElementById('vietQRAmount');
    const contentEl = document.getElementById('vietQRContent');
    if (!modal) return;

    if (amountEl) amountEl.textContent = amount.toLocaleString('vi-VN') + '₫';
    if (contentEl) contentEl.textContent = orderCode;

    // Sinh link ảnh VietQR chuẩn Napas 24/7
    if (qrImg) {
      qrImg.src = `https://api.vietqr.io/image/970422-0946618960-compact2.jpg?amount=${amount}&addInfo=${orderCode}&accountName=TIEM%20HOA%20BLOOMORA%20DAK%20LAK`;
    }

    modal.classList.remove('hidden');
  };

  window.closeVietQRModal = function () {
    document.getElementById('vietQRModal')?.classList.add('hidden');
  };

  window.handleFinishVietQRPayment = function () {
    window.closeVietQRModal();
    if (!latestCheckoutOrder) return;

    alert(`🎉 CẢM ƠN QUÝ KHÁCH!\n\nĐơn hàng ${latestCheckoutOrder.code} đã được chuyển sang ca trực Florist Mai Phương tại Buôn Ma Thuột.\nTiệm sẽ tuyển chọn hoa tươi mới và chụp ảnh hoa thật gửi bạn duyệt trước khi giao!`);

    // Mở ngay cửa sổ tra cứu đơn hàng để khách theo dõi
    const codeInp = document.getElementById('trackOrderCode');
    const phoneInp = document.getElementById('trackOrderPhone');
    if (codeInp && phoneInp) {
      codeInp.value = latestCheckoutOrder.code;
      phoneInp.value = latestCheckoutOrder.phone;
      window.openTrackOrderModal();
      window.handleTrackOrder();
    }
  };

  window.copyText = function (text, msg) {
    if (navigator.clipboard) {
      navigator.clipboard.writeText(text).then(() => {
        window.showToast(msg || 'Đã sao chép vào bộ nhớ tạm!');
      });
    } else {
      prompt('Nhấn Ctrl+C để sao chép:', text);
    }
  };

  // 7.2. TIỆN ÍCH HỒ SƠ THÀNH VIÊN & LỊCH SỬ ĐƠN HÀNG (US10 - US15)
  window.openCustomerProfileModal = function () {
    const modal = document.getElementById('customerProfileModal');
    if (!modal) return;

    try {
      const authUser = JSON.parse(localStorage.getItem('bloomora_auth_user'));
      if (authUser) {
        document.getElementById('profileName').textContent = authUser.name || authUser.fullName || 'Nguyễn Văn Khách';
        document.getElementById('profileEmail').textContent = authUser.email || 'khachhang@gmail.com';
        document.getElementById('infoFullName').value = authUser.name || authUser.fullName || 'Nguyễn Văn Khách';
        document.getElementById('infoPhone').value = authUser.phone || '0912345678';
      }
    } catch (e) {}

    window.switchProfileTab('orders');
    window.renderCustomerOrders();
    modal.classList.remove('hidden');
  };

  window.closeCustomerProfileModal = function () {
    document.getElementById('customerProfileModal')?.classList.add('hidden');
  };

  window.switchProfileTab = function (tab) {
    const tabOrders = document.getElementById('profileTabOrders');
    const tabInfo = document.getElementById('profileTabInfo');
    const btnOrders = document.getElementById('tabBtnOrders');
    const btnInfo = document.getElementById('tabBtnInfo');

    if (tab === 'orders') {
      tabOrders?.classList.remove('hidden');
      tabInfo?.classList.add('hidden');
      if (btnOrders) btnOrders.className = 'px-3.5 py-1.5 rounded-xl bg-rose-600 text-white transition cursor-pointer';
      if (btnInfo) btnInfo.className = 'px-3.5 py-1.5 rounded-xl text-stone-600 hover:bg-stone-100 transition cursor-pointer';
      window.renderCustomerOrders();
    } else {
      tabOrders?.classList.add('hidden');
      tabInfo?.classList.remove('hidden');
      if (btnOrders) btnOrders.className = 'px-3.5 py-1.5 rounded-xl text-stone-600 hover:bg-stone-100 transition cursor-pointer';
      if (btnInfo) btnInfo.className = 'px-3.5 py-1.5 rounded-xl bg-rose-600 text-white transition cursor-pointer';
    }
  };

  window.renderCustomerOrders = function () {
    const list = document.getElementById('customerOrdersList');
    if (!list) return;

    const orders = window.BloomoraAdmin.getOrders();
    // Lấy các đơn của khách
    let userPhone = '0912345678';
    try {
      const authUser = JSON.parse(localStorage.getItem('bloomora_auth_user'));
      if (authUser && authUser.phone) userPhone = authUser.phone;
    } catch (e) {}

    const myOrders = orders.filter(o => !o.phone || o.phone === userPhone || o.phone === '0912345678');

    if (myOrders.length === 0) {
      list.innerHTML = '<div class="py-8 text-center text-stone-400 text-xs">Bạn chưa có đơn đặt hoa nào gần đây.</div>';
      return;
    }

    list.innerHTML = myOrders.map(o => {
      const canCancel = o.status === 'received';
      let statusBadge = '<span class="bg-stone-100 text-stone-700 px-2 py-0.5 rounded-full text-[10px] font-bold">1. Chờ Xác Nhận</span>';
      if (o.status === 'preparing') statusBadge = '<span class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full text-[10px] font-bold">2. Đang Cắm Hoa</span>';
      else if (o.status === 'photo_ready') statusBadge = '<span class="bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full text-[10px] font-bold ring-1 ring-amber-400">3. Đã Chụp Ảnh Thật</span>';
      else if (o.status === 'delivering') statusBadge = '<span class="bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-full text-[10px] font-bold">4. Đang Giao Hàng</span>';
      else if (o.status === 'completed') statusBadge = '<span class="bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full text-[10px] font-bold">5. Đã Giao Thành Công</span>';
      else if (o.status === 'cancelled') statusBadge = '<span class="bg-red-100 text-red-700 px-2 py-0.5 rounded-full text-[10px] font-bold">Đã Hủy Đơn</span>';

      return `
        <div class="p-3.5 bg-stone-50 rounded-2xl border border-stone-200/90 text-xs space-y-2">
          <div class="flex items-center justify-between font-bold">
            <span class="font-mono text-rose-600">${o.code}</span>
            <div>${statusBadge}</div>
          </div>
          <div class="font-semibold text-stone-900">${o.flower}</div>
          <div class="text-[11px] text-stone-500 flex items-center justify-between">
            <span>Giờ giao: ${o.slot}</span>
            <span class="font-extrabold text-rose-600">${(o.total || 1850000).toLocaleString('vi-VN')}₫</span>
          </div>
          <div class="pt-2 border-t border-stone-200/70 flex items-center justify-between gap-2">
            <button onclick="window.closeCustomerProfileModal(); document.getElementById('trackOrderCode').value='${o.code}'; document.getElementById('trackOrderPhone').value='${o.phone || '0912345678'}'; window.openTrackOrderModal(); window.handleTrackOrder();" class="bg-rose-50 hover:bg-rose-100 text-rose-600 font-bold px-3 py-1.5 rounded-xl text-[11px] transition cursor-pointer">
              <i class="ph ph-magnifying-glass mr-1"></i> Theo Dõi Tiến Độ & Duyệt Ảnh
            </button>
            ${canCancel ? `
              <button onclick="cancelOrderCustomer('${o.code}')" class="text-stone-400 hover:text-red-600 font-bold text-[11px] px-2 py-1 transition cursor-pointer">
                Hủy Đơn Hàng
              </button>
            ` : ''}
          </div>
        </div>
      `;
    }).join('');
  };

  window.cancelOrderCustomer = function (code) {
    if (!confirm(`Bạn có chắc chắn muốn hủy đơn hàng ${code} không? Thao tác này chỉ thực hiện được khi đơn chưa bắt đầu cắm hoa.`)) return;

    const orders = window.BloomoraAdmin.getOrders();
    const order = orders.find(o => o.code === code);
    if (order && order.status === 'received') {
      order.status = 'cancelled';
      order.statusText = 'Đã Hủy';
      localStorage.setItem('bloomora_orders', JSON.stringify(orders));
      window.renderCustomerOrders();
      alert(`Đã hủy đơn hàng ${code} thành công.`);
    } else {
      alert('Đơn hàng đã bắt đầu cắm hoa hoặc đang giao, không thể tự hủy trên website. Vui lòng liên hệ Hotline 0946618960 để được hỗ trợ.');
    }
  };

  window.saveCustomerProfileInfo = function () {
    const name = document.getElementById('infoFullName')?.value.trim();
    const phone = document.getElementById('infoPhone')?.value.trim();
    const addr = document.getElementById('infoAddress')?.value.trim();

    try {
      const authUser = JSON.parse(localStorage.getItem('bloomora_auth_user')) || {};
      authUser.name = name;
      authUser.fullName = name;
      authUser.phone = phone;
      authUser.address = addr;
      localStorage.setItem('bloomora_auth_user', JSON.stringify(authUser));
      localStorage.setItem('bloomora_user', JSON.stringify(authUser));
      localStorage.setItem('bloomora_current_user', JSON.stringify(authUser));
      window.showToast('Đã lưu thông tin tài khoản và sổ địa chỉ thành công!');
    } catch (e) {}
  };

  window.updateCartBadge = function () {
    const badge = document.getElementById('cartCountBadge');
    if (!badge) return;
    const count = window.BloomoraCart.getCount();
    badge.textContent = count;
  };

  window.showToast = function (message) {
    const toast = document.getElementById('toastNotification');
    const msgEl = document.getElementById('toastMessage');
    if (!toast || !msgEl) return;

    msgEl.textContent = message;
    toast.classList.remove('hidden');
    setTimeout(() => {
      toast.classList.add('hidden');
    }, 3000);
  };

  // 11. TÌM KIẾM NHANH TRANG CHỦ & ĐĂNG XUẤT
  window.handleQuickSearch = function (e) {
    if (e) e.preventDefault();
    const occasion = document.getElementById('quickOccasion')?.value;
    const price = document.getElementById('quickPrice')?.value;
    const location = document.getElementById('quickLocation')?.value;

    let targetCategory = 'all';
    if (occasion === 'khai-truong') targetCategory = 'Hoa Khai Trương';
    else if (occasion === 'sinh-nhat') targetCategory = 'Hoa Sinh Nhật';
    else if (occasion === 'tinh-yeu') targetCategory = 'Hoa Tình Yêu & Hoa Hồng';
    else if (occasion === 'chia-buon') targetCategory = 'all';

    let targetBtn = null;
    document.querySelectorAll('.catalog-filter-btn').forEach(function(btn) {
      if (btn.dataset.category === targetCategory) targetBtn = btn;
    });
    window.filterCatalog(targetCategory, targetBtn);

    const notice = document.getElementById('searchResultNotice');
    const text = document.getElementById('searchResultText');
    if (notice && text) {
      notice.classList.remove('hidden');
      text.innerHTML = `Đã tìm thấy các mẫu hoa phù hợp! Mời bạn lựa chọn các mẫu bên dưới:`;
    }

    const prodSection = document.getElementById('productsSection');
    if (prodSection) {
      prodSection.scrollIntoView({ behavior: 'smooth' });
    }
  };

  window.logoutHTML = function () {
    if (window.BloomoraAuth) {
      window.BloomoraAuth.logout();
    } else {
      localStorage.removeItem('bloomora_current_user');
      localStorage.removeItem('bloomora_user');
    }
    window.location.reload();
  };

  // 12. TÍNH NĂNG AI 1: TRỢ LÝ TƯ VẤN CHỌN HOA (CHATBOT AI THEO CSDL HOA ĐẮK LẮK)
  window.toggleAIChatbot = function () {
    const win = document.getElementById('aiChatbotWindow');
    if (!win) return;
    win.classList.toggle('hidden');
    if (!win.classList.contains('hidden')) {
      document.getElementById('aiChatInput')?.focus();
    }
  };

  window.quickAIChat = function (occasion, recipient, budget) {
    const msg = `Tư vấn cho mình mẫu hoa tặng ${recipient} dịp ${occasion} tầm giá ${(budget/1000000).toFixed(1)} triệu`;
    const input = document.getElementById('aiChatInput');
    if (input) {
      input.value = msg;
      window.handleSendAIChat(new Event('submit'));
    }
  };

  function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }

  window.handleSendAIChat = async function (e) {
    if (e && e.preventDefault) e.preventDefault();
    const input = document.getElementById('aiChatInput');
    const msg = input?.value.trim();
    if (!msg) return;

    const chatBox = document.getElementById('aiChatMessages');
    if (!chatBox) return;

    // Hiển thị tin nhắn người dùng
    const userBubble = document.createElement('div');
    userBubble.className = 'flex justify-end';
    userBubble.innerHTML = `
      <div class="bg-rose-600 text-white p-3 rounded-2xl rounded-tr-sm max-w-[85%] leading-relaxed shadow-sm">
        ${escapeHtml(msg)}
      </div>
    `;
    chatBox.appendChild(userBubble);
    input.value = '';
    chatBox.scrollTop = chatBox.scrollHeight;

    // Loading indicator từ AI
    const loadingId = 'aiLoading_' + Date.now();
    const loadingBubble = document.createElement('div');
    loadingBubble.id = loadingId;
    loadingBubble.className = 'flex items-start space-x-2';
    loadingBubble.innerHTML = `
      <div class="w-6 h-6 rounded-full bg-rose-600 text-white flex items-center justify-center text-xs flex-shrink-0 mt-0.5 animate-pulse">
        <i class="ph-bold ph-sparkle"></i>
      </div>
      <div class="bg-stone-100 text-stone-500 p-3 rounded-2xl rounded-tl-sm max-w-[85%] text-xs flex items-center space-x-2">
        <i class="ph-bold ph-spinner animate-spin"></i>
        <span>AI đang tra cứu CSDL hoa Đắk Lắk...</span>
      </div>
    `;
    chatBox.appendChild(loadingBubble);
    chatBox.scrollTop = chatBox.scrollHeight;

    try {
      // Gọi API Backend PHP (api/ai/consult.php)
      const res = await fetch('../php/api/ai/consult.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: msg, occasion: '', recipient: '', budget: 0 })
      });
      const data = await res.json();
      document.getElementById(loadingId)?.remove();

      if (data && data.success) {
        let productCardsHtml = '';
        if (data.products && data.products.length > 0) {
          productCardsHtml = `
            <div class="mt-2.5 space-y-2">
              <span class="text-[10px] font-bold text-stone-500 uppercase block">Gợi ý từ CSDL Bloomora:</span>
              ${data.products.map(p => `
                <div class="bg-white border border-stone-200 rounded-xl p-2 flex items-center justify-between gap-2 shadow-xs">
                  <img src="${p.image}" class="w-11 h-11 rounded-lg object-cover flex-shrink-0" alt="${p.name}">
                  <div class="flex-1 min-w-0 text-left">
                    <div class="font-bold text-stone-800 text-[11px] truncate">${p.name}</div>
                    <div class="text-rose-600 font-extrabold text-xs">${parseInt(p.price, 10).toLocaleString('vi-VN')}₫</div>
                  </div>
                  <button onclick="window.openQuickOrderModal(${p.id}); window.toggleAIChatbot();" class="bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 font-bold px-2 py-1.5 rounded-lg text-[10px] transition cursor-pointer flex-shrink-0">
                    Đặt Ngay
                  </button>
                </div>
              `).join('')}
            </div>
          `;
        }

        const aiBubble = document.createElement('div');
        aiBubble.className = 'flex items-start space-x-2';
        aiBubble.innerHTML = `
          <div class="w-6 h-6 rounded-full bg-rose-600 text-white flex items-center justify-center text-xs flex-shrink-0 mt-0.5">
            <i class="ph-bold ph-sparkle"></i>
          </div>
          <div class="bg-stone-100 text-stone-800 p-3 rounded-2xl rounded-tl-sm max-w-[85%] leading-relaxed shadow-sm">
            <div>${data.message.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>').replace(/\n/g, '<br>')}</div>
            ${productCardsHtml}
          </div>
        `;
        chatBox.appendChild(aiBubble);
      }
    } catch (err) {
      document.getElementById(loadingId)?.remove();
      // Heuristic fallback nếu chạy file:// không qua server
      const localProducts = window.BloomoraAdmin.getProducts();
      const topP = localProducts[0];
      const aiBubble = document.createElement('div');
      aiBubble.className = 'flex items-start space-x-2';
      aiBubble.innerHTML = `
        <div class="w-6 h-6 rounded-full bg-rose-600 text-white flex items-center justify-center text-xs flex-shrink-0 mt-0.5">
          <i class="ph-bold ph-sparkle"></i>
        </div>
        <div class="bg-stone-100 text-stone-800 p-3 rounded-2xl rounded-tl-sm max-w-[85%] leading-relaxed shadow-sm">
          <div>Dạ em gợi ý bạn mẫu <strong>${topP.name}</strong> (${topP.price.toLocaleString('vi-VN')}₫) đang rất được chuộng tại Buôn Ma Thuột. Tiệm cam kết chụp ảnh hoa thật gửi bạn duyệt trước khi giao! 🌸</div>
          <div class="mt-2.5">
            <div class="bg-white border border-stone-200 rounded-xl p-2 flex items-center justify-between gap-2 shadow-xs">
              <img src="${topP.image}" class="w-11 h-11 rounded-lg object-cover flex-shrink-0" alt="${topP.name}">
              <div class="flex-1 min-w-0 text-left">
                <div class="font-bold text-stone-800 text-[11px] truncate">${topP.name}</div>
                <div class="text-rose-600 font-extrabold text-xs">${topP.price.toLocaleString('vi-VN')}₫</div>
              </div>
              <button onclick="window.openQuickOrderModal(${topP.id}); window.toggleAIChatbot();" class="bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 font-bold px-2 py-1.5 rounded-lg text-[10px] transition cursor-pointer flex-shrink-0">
                Đặt Ngay
              </button>
            </div>
          </div>
        </div>
      `;
      chatBox.appendChild(aiBubble);
    }

    chatBox.scrollTop = chatBox.scrollHeight;
  };

  // 13. TÍNH NĂNG AI 2: GỢI Ý LỜI CHÚC CHO THIỆP & BĂNG RÔN (LIVE PREVIEW)
  window.toggleAIWishesPanel = function () {
    const box = document.getElementById('aiWishesBox');
    if (!box) return;
    box.classList.toggle('hidden');
    if (!box.classList.contains('hidden')) {
      window.generateAIWishes();
    }
  };

  window.generateAIWishes = async function () {
    const occasion = document.getElementById('aiWishOccasion')?.value || 'Khai Trương';
    const tone = document.getElementById('aiWishTone')?.value || 'trang_trong';
    const listEl = document.getElementById('aiWishList');
    if (!listEl) return;

    listEl.innerHTML = '<div class="text-[11px] text-stone-400 py-1 flex items-center space-x-1"><i class="ph-bold ph-spinner animate-spin"></i><span>AI đang sáng tạo lời chúc...</span></div>';

    try {
      const res = await fetch('../php/api/ai/wishes.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ occasion: occasion, tone: tone, format: 'ribbon' })
      });
      const data = await res.json();
      if (data && data.success && data.wishes) {
        listEl.innerHTML = data.wishes.map((w) => `
          <div onclick="window.applyAIWish('${w.replace(/'/g, "\\'")}')" class="bg-white hover:bg-rose-50 hover:border-rose-300 border border-stone-200/90 rounded-xl p-2 cursor-pointer transition flex items-center justify-between text-xs group">
            <span class="font-bold text-stone-800 group-hover:text-rose-600 text-[11px] leading-tight">${w}</span>
            <span class="text-[10px] bg-stone-100 group-hover:bg-rose-200 text-stone-600 group-hover:text-rose-800 font-bold px-2 py-0.5 rounded-lg ml-2 flex-shrink-0 transition">Chọn mẫu này</span>
          </div>
        `).join('');
        return;
      }
    } catch (e) {}

    // Fallback nếu chạy file:// không qua HTTP server
    const fallbacks = [
      'CHÚC MỪNG KHAI TRƯƠNG HỒNG PHÁT',
      'ĐẠI CÁT ĐẠI LỢI - VẠN SỰ HANH THÔNG',
      'PHÁT TÀI PHÁT LỘC - MUA MAY BÁN ĐẮT'
    ];
    listEl.innerHTML = fallbacks.map(w => `
      <div onclick="window.applyAIWish('${w}')" class="bg-white hover:bg-rose-50 hover:border-rose-300 border border-stone-200/90 rounded-xl p-2 cursor-pointer transition flex items-center justify-between text-xs group">
        <span class="font-bold text-stone-800 group-hover:text-rose-600 text-[11px] leading-tight">${w}</span>
        <span class="text-[10px] bg-stone-100 group-hover:bg-rose-200 text-stone-600 group-hover:text-rose-800 font-bold px-2 py-0.5 rounded-lg ml-2 flex-shrink-0 transition">Chọn mẫu này</span>
      </div>
    `).join('');
  };

  window.applyAIWish = function (text) {
    const input = document.getElementById('modalRibbonText');
    if (input) {
      input.value = text;
      // Cập nhật Live Preview chữ vàng trên băng rôn đỏ
      const preview = document.getElementById('liveRibbonPreview');
      if (preview) {
        preview.textContent = text || 'NỘI DUNG BĂNG RÔN IN VI TÍNH';
      }
      window.toggleAIWishesPanel();
    }
  };

  // 14. TÍNH NĂNG TRA CỨU ĐƠN HÀNG (MÃ ĐƠN + SỐ ĐIỆN THOẠI) & DUYỆT ẢNH THẬT
  let currentTrackedOrder = null;

  window.openTrackOrderModal = function () {
    document.getElementById('trackOrderModal')?.classList.remove('hidden');
    document.getElementById('trackResultBox')?.classList.add('hidden');
  };

  window.closeTrackOrderModal = function () {
    document.getElementById('trackOrderModal')?.classList.add('hidden');
    currentTrackedOrder = null;
  };

  window.handleTrackOrder = function (e) {
    if (e && e.preventDefault) e.preventDefault();
    const code = document.getElementById('trackOrderCode')?.value.trim().toUpperCase();
    const phone = document.getElementById('trackOrderPhone')?.value.trim();

    if (!code || !phone) {
      alert('Vui lòng nhập cả Mã đơn hàng và Số điện thoại người đặt!');
      return;
    }

    const orders = window.BloomoraAdmin.getOrders();
    const order = orders.find(o => o.code === code);

    if (!order) {
      alert(`Không tìm thấy đơn hàng mã ${code}. Quý khách vui lòng kiểm tra lại!`);
      return;
    }

    // Bảo mật: Yêu cầu khớp số điện thoại
    if (order.phone && order.phone !== phone) {
      alert('⛔ BẢO MẬT: Số điện thoại không khớp với thông tin đặt đơn này! Vui lòng kiểm tra lại số điện thoại người đặt.');
      return;
    }

    currentTrackedOrder = order;
    const resBox = document.getElementById('trackResultBox');
    if (!resBox) return;

    resBox.classList.remove('hidden');
    document.getElementById('trackResultCode').textContent = order.code;
    document.getElementById('trackResultFlowerName').textContent = order.flower;
    document.getElementById('trackResultAddress').textContent = order.address || '120 Phan Chu Trinh, P. Thắng Lợi, TP. Buôn Ma Thuột';
    document.getElementById('trackResultSlot').textContent = order.slot;

    const payBadge = document.getElementById('trackResultPayBadge');
    if (payBadge) {
      if (order.paymentStatus === 'paid') {
        payBadge.className = 'bg-emerald-100 text-emerald-800 font-bold px-2 py-0.5 rounded-full text-[10px]';
        payBadge.textContent = 'Đã thanh toán VietQR (Đã đối soát)';
      } else {
        payBadge.className = 'bg-amber-100 text-amber-800 font-bold px-2 py-0.5 rounded-full text-[10px]';
        payBadge.textContent = 'Chờ xác nhận thanh toán VietQR';
      }
    }

    // Cập nhật 5 dots tiến độ
    const stepDots = [
      document.getElementById('step1Dot'),
      document.getElementById('step2Dot'),
      document.getElementById('step3Dot'),
      document.getElementById('step4Dot'),
      document.getElementById('step5Dot')
    ];

    let currentStep = 1;
    if (order.status === 'received') currentStep = 1;
    else if (order.status === 'preparing') currentStep = 2;
    else if (order.status === 'photo_ready') currentStep = 3;
    else if (order.status === 'delivering') currentStep = 4;
    else if (order.status === 'completed') currentStep = 5;

    stepDots.forEach((dot, idx) => {
      if (!dot) return;
      if (idx + 1 < currentStep) {
        dot.className = 'p-2 rounded-xl bg-emerald-100 text-emerald-800 font-bold';
      } else if (idx + 1 === currentStep) {
        dot.className = 'p-2 rounded-xl bg-amber-100 text-amber-800 font-bold ring-2 ring-amber-400';
      } else {
        dot.className = 'p-2 rounded-xl bg-stone-100 text-stone-400 font-semibold';
      }
    });

    // Khối Photo Proof
    const photoBox = document.getElementById('trackPhotoProofBox');
    const photoImg = document.getElementById('trackPhotoImg');
    const photoStatusBadge = document.getElementById('photoStatusBadge');
    const photoBtns = document.getElementById('photoActionButtons');

    if (order.photo) {
      photoBox?.classList.remove('hidden');
      if (photoImg) photoImg.src = order.photo;

      if (order.photoStatus === 'approved') {
        if (photoStatusBadge) {
          photoStatusBadge.className = 'text-[10px] bg-emerald-600 text-white font-bold px-2 py-0.5 rounded-full';
          photoStatusBadge.textContent = 'Đã duyệt ảnh - Đang giao hoa';
        }
        photoBtns?.classList.add('hidden');
      } else {
        if (photoStatusBadge) {
          photoStatusBadge.className = 'text-[10px] bg-amber-500 text-white font-bold px-2 py-0.5 rounded-full';
          photoStatusBadge.textContent = 'Chờ bạn duyệt ảnh hoa thật';
        }
        photoBtns?.classList.remove('hidden');
      }
    } else {
      photoBox?.classList.add('hidden');
    }
  };

  window.approvePhotoProof = function () {
    if (!currentTrackedOrder) return;
    currentTrackedOrder.photoStatus = 'approved';
    currentTrackedOrder.status = 'delivering';
    currentTrackedOrder.statusText = '4. Đang Giao Hàng';

    const orders = window.BloomoraAdmin.getOrders();
    const idx = orders.findIndex(o => o.code === currentTrackedOrder.code);
    if (idx !== -1) orders[idx] = currentTrackedOrder;
    localStorage.setItem('bloomora_orders', JSON.stringify(orders));

    alert('✅ CẢM ƠN BẠN ĐÃ DUYỆT ẢNH HOA!\n\nFlorist Bloomora đã ghi nhận bạn hài lòng với mẫu hoa cắm thật. Shipper của tiệm bắt đầu xuất phát giao đến người nhận ngay bây giờ!');
    window.handleTrackOrder();
  };

  window.requestPhotoRevision = function () {
    if (!currentTrackedOrder) return;
    const note = prompt('Vui lòng nhập chi tiết yêu cầu chỉnh sửa (Ví dụ: Thêm chút hoa bi trắng, đổi sang nơ đỏ tươi...):', 'Cần thêm hoa bi trắng viền xung quanh');
    if (!note) return;

    currentTrackedOrder.photoStatus = 'revision_requested';
    currentTrackedOrder.note = 'Khách yêu cầu chỉnh sửa: ' + note;

    const orders = window.BloomoraAdmin.getOrders();
    const idx = orders.findIndex(o => o.code === currentTrackedOrder.code);
    if (idx !== -1) orders[idx] = currentTrackedOrder;
    localStorage.setItem('bloomora_orders', JSON.stringify(orders));

    alert('📝 ĐÃ GỬI YÊU CẦU CHỈNH SỬA ĐẾN FLORIST!\n\nNghệ nhân Florist tại xưởng Buôn Ma Thuột đang tiến hành điều chỉnh mẫu hoa theo ghi chú của bạn và sẽ chụp lại ảnh mới.');
    window.handleTrackOrder();
  };

  // 15. TÍNH NĂNG ĐỐI SOÁT XÁC NHẬN THANH TOÁN VIETQR CHO ADMIN
  window.confirmOrderPayment = function (code) {
    if (!confirm(`Xác nhận đã nhận tiền chuyển khoản cho đơn hàng ${code}?`)) return;

    const orders = window.BloomoraAdmin.getOrders();
    const order = orders.find(o => o.code === code);
    if (order) {
      order.paymentStatus = 'paid';
      if (order.status === 'received') {
        order.status = 'preparing';
        order.statusText = '2. Đang Cắm Hoa';
      }
      localStorage.setItem('bloomora_orders', JSON.stringify(orders));
      window.renderOrdersTable();
      alert(`Đã xác nhận thanh toán thành công cho đơn ${code}! Đơn hàng tự động chuyển sang ca thợ cắm hoa.`);
    }
  };

  // 16. HỖ TRỢ XEM CHÍNH SÁCH FOOTER (POLICY MODAL)
  window.openPolicyModal = function (type) {
    const modal = document.getElementById('policyModal');
    const titleEl = document.getElementById('policyModalTitle');
    const contentEl = document.getElementById('policyModalContent');
    if (!modal || !titleEl || !contentEl) return;

    const policies = {
      delivery: {
        title: 'Chính Sách Giao Hỏa Tốc 60 Phút',
        icon: 'ph-clock-countdown text-rose-600',
        content: `
          <div class="space-y-3 text-xs leading-relaxed text-stone-700">
            <p><strong>1. Phạm vi áp dụng:</strong> Toàn bộ các phường nội thành TP. Buôn Ma Thuột (Thắng Lợi, Tân Lợi, Tự An, Tân Lập, Thành Nhất, Tân Tiến, Tân Thành, Thành Công, Ea Tam...).</p>
            <p><strong>2. Quy trình hỏa tốc:</strong></p>
            <ul class="list-disc pl-5 space-y-1">
              <li>0 - 25 phút: Nghệ nhân florist tại xưởng thực hiện cắm hoa theo mẫu.</li>
              <li>25 - 30 phút: Chụp ảnh Photo Proof gửi khách hàng xem trước.</li>
              <li>30 - 60 phút: Shipper hỏa tốc vận chuyển đến tận tay người nhận bằng thùng chống rung lắc chuyên dụng.</li>
            </ul>
            <p><strong>3. Cam kết thời gian:</strong> Nếu giao trễ quá 15 phút do lỗi từ cửa hàng, Bloomora miễn phí 100% cước giao hàng và tặng voucher 50.000₫ cho đơn hàng kế tiếp.</p>
          </div>
        `
      },
      refund: {
        title: 'Chính Sách Hoàn Tiền & Đổi Mới 100%',
        icon: 'ph-shield-check text-emerald-600',
        content: `
          <div class="space-y-3 text-xs leading-relaxed text-stone-700">
            <p><strong>1. Điều kiện hoàn tiền hoặc đổi mới:</strong></p>
            <ul class="list-disc pl-5 space-y-1">
              <li>Hoa nhận được bị dập nát, gãy cành, héo úa hoặc không tươi mới.</li>
              <li>Sản phẩm giao thực tế khác biệt lớn (trên 20%) so với hình ảnh Photo Proof đã được khách duyệt.</li>
              <li>Giao sai mẫu hoa, sai thông tin nội dung băng rôn/thiệp chúc mừng.</li>
            </ul>
            <p><strong>2. Hình thức xử lý:</strong></p>
            <ul class="list-disc pl-5 space-y-1">
              <li><strong>Đổi mới hỏa tốc:</strong> Cắm lại bó hoa mới và giao lại trong vòng 60 - 90 phút hoàn toàn miễn phí.</li>
              <li><strong>Hoàn tiền 100%:</strong> Hoàn tiền chuyển khoản qua VietQR/Napas ngay trong vòng 30 phút sau khi xác nhận phản hồi.</li>
            </ul>
          </div>
        `
      },
      payment: {
        title: 'Hướng Dẫn Thanh Toán VietQR / MoMo',
        icon: 'ph-qr-code text-indigo-600',
        content: `
          <div class="space-y-3 text-xs leading-relaxed text-stone-700">
            <p><strong>1. Thanh toán quét mã VietQR Napas 247:</strong></p>
            <ul class="list-disc pl-5 space-y-1">
              <li>Mở ứng dụng ngân hàng bất kỳ (Vietcombank, MB, Techcombank, BIDV, VPBank, ACB...).</li>
              <li>Chọn tính năng "Quét mã QR" và hướng camera vào mã QR của đơn hàng.</li>
              <li>Hệ thống tự động điền đúng Số Tiền và Cú pháp (Mã đơn hàng <code>BLxxxxx</code>). Khách hàng không cần nhập tay.</li>
            </ul>
            <p><strong>2. Thanh toán khi nhận hàng (COD):</strong> Áp dụng cho các đơn hàng hoa tươi nội thành Buôn Ma Thuột có giá trị dưới 1.500.000₫.</p>
          </div>
        `
      },
      vat: {
        title: 'Xuất Hóa Đơn Điện Tử VAT Doanh Nghiệp',
        icon: 'ph-receipt text-amber-600',
        content: `
          <div class="space-y-3 text-xs leading-relaxed text-stone-700">
            <p>Bloomora cung cấp đầy đủ hóa đơn điện tử VAT (hóa đơn đỏ hợp lệ 8% - 10%) cho các doanh nghiệp, cơ quan ban ngành tại tỉnh Đắk Lắk và trên toàn quốc.</p>
            <p><strong>Cách thức đăng ký xuất VAT:</strong></p>
            <ul class="list-disc pl-5 space-y-1">
              <li>Tại bước Thanh toán / Giỏ hàng, quý khách nhập Tên Công Ty, Mã Số Thuế và Email nhận hóa đơn vào ô "Ghi chú đơn hàng".</li>
              <li>Hóa đơn điện tử có mã của Cơ quan Thuế sẽ được gửi tự động qua email của quý khách trong vòng 24 giờ sau khi đơn hàng giao thành công.</li>
            </ul>
          </div>
        `
      }
    };

    const target = policies[type] || policies.delivery;
    titleEl.innerHTML = `<i class="ph-bold ${target.icon} text-lg mr-2"></i><span>${target.title}</span>`;
    contentEl.innerHTML = target.content;
    modal.classList.remove('hidden');
  };

  window.closePolicyModal = function () {
    const modal = document.getElementById('policyModal');
    if (modal) modal.classList.add('hidden');
  };

  // 17. TỰ ĐỘNG KHỞI TẠO THEO TRANG ĐANG MỞ
  document.addEventListener('DOMContentLoaded', () => {
    // Trang Chủ: Khởi tạo lưới sản phẩm Wish Florist & Giỏ hàng
    if (document.getElementById('catalogGrid')) {
      window.renderCatalog('all');
      window.updateCartBadge();
    }

    // Trang 1: Đơn Hàng Dashboard (admin.html)
    if (document.getElementById('ordersTableBody')) {
      window.checkAdminSession();
      window.renderOrdersTable();
      window.renderInventory();
    }

    // Trang 2: Quản Lý Sản Phẩm Hoa (admin-products.html)
    if (document.getElementById('productsTableBody')) {
      window.checkAdminSession();
      window.renderProductsTable();
    }

    // Trang 3: Quản Lý Kho & Vật Tư (admin-inventory.html)
    if (document.getElementById('fullInventoryTableBody')) {
      window.checkAdminSession();
      window.renderFullInventoryTable();
    }
  });

})();