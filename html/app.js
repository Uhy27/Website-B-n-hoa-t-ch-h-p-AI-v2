/**
 * BLOOMORA - FILE XỬ LÝ ỨNG DỤNG & QUẢN TRỊ ADMIN (APP.JS)
 * Chuyên trách: Bảng Điều Khiển Admin, Quản Lý Đơn Hàng Florist, Quản Lý Sản Phẩm, Quản Lý Kho & Cửa Hàng
 */

(function () {
  'use strict';

  // 1. Khởi tạo kho dữ liệu trong LocalStorage
  function initAppData() {
    // 1.1 Đơn hàng trong ca
    if (!localStorage.getItem('bloomora_orders')) {
      const defaultOrders = [
        {
          code: 'BL89241',
          flower: 'Kệ Khai Trương Đại Cát',
          ribbon: 'CHÚC MỪNG KHAI TRƯƠNG HỒNG PHÁT',
          slot: '15:30 - 17:30',
          status: 'photo_ready',
          statusText: '3. Đã Chụp Ảnh Thật',
          photo: 'https://images.unsplash.com/photo-1561181286-d3fee7d55364?auto=format&fit=crop&w=400&q=80',
          note: 'Hoa hồng nhập khẩu đỏ tươi, nơ đỏ viền kim tuyến vàng'
        },
        {
          code: 'BL17305',
          flower: 'Bó Hồng Đỏ Nồng Nàn 99 Bông',
          ribbon: 'Yêu em mãi mãi',
          slot: '15:30 - 17:30',
          status: 'preparing',
          statusText: '2. Đang Cắm Hoa',
          photo: '',
          note: 'Florist Mai Phương đang tuyển chọn hoa tại xưởng'
        },
        {
          code: 'BL22419',
          flower: 'Chậu Lan Hồ Điệp Hoàng Kim 5 Cành',
          ribbon: 'Mừng Khai Trương Phát Tài',
          slot: '18:00 - 20:00',
          status: 'received',
          statusText: '1. Đã Tiếp Nhận',
          photo: '',
          note: 'Đã tiếp nhận yêu cầu, chuẩn bị chậu gốm mộc'
        }
      ];
      localStorage.setItem('bloomora_orders', JSON.stringify(defaultOrders));
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

  window.renderOrdersTable = function () {
    const tbody = document.getElementById('ordersTableBody');
    if (!tbody) return;

    const orders = window.BloomoraAdmin.getOrders();
    const badge = document.getElementById('orderCountBadge');
    const kpiTotal = document.getElementById('kpiTotalOrders');
    const kpiUrgent = document.getElementById('kpiUrgentOrders');

    if (badge) badge.textContent = orders.length + ' đơn trong ca';
    if (kpiTotal) kpiTotal.textContent = (orders.length + 21) + ' Đơn';

    const urgentCount = orders.filter(o => o.status === 'preparing' || o.status === 'received').length;
    if (kpiUrgent) kpiUrgent.textContent = urgentCount + ' Đơn Trong Ca';

    tbody.innerHTML = '';

    if (orders.length === 0) {
      tbody.innerHTML = '<tr><td colspan="5" class="py-6 text-center text-stone-400">Không có đơn hàng nào trong ca hiện tại.</td></tr>';
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
      tr.innerHTML = `
        <td class="py-3 font-mono font-bold text-rose-600">${order.code}</td>
        <td class="py-3">
          <div class="font-semibold text-stone-900">${order.flower}</div>
          <div class="text-[11px] text-stone-400 truncate max-w-[200px]">${order.ribbon}</div>
        </td>
        <td class="py-3 font-semibold text-stone-800">${order.slot}</td>
        <td class="py-3">${statusBadge}</td>
        <td class="py-3 text-right">
          <button onclick="openOrderModal('${order.code}')" class="bg-stone-100 hover:bg-rose-50 hover:text-rose-600 px-3 py-1.5 rounded-lg font-semibold transition text-xs">
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

    let newPhoto = null;
    if (newStatus === 'photo_ready') {
      newPhoto = 'https://images.unsplash.com/photo-1561181286-d3fee7d55364?auto=format&fit=crop&w=400&q=80';
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

  window.renderCartDrawer = function () {
    const list = document.getElementById('cartDrawerList');
    const subtotalEl = document.getElementById('cartDrawerSubtotal');
    const totalEl = document.getElementById('cartDrawerTotal');
    if (!list) return;

    const items = window.BloomoraCart.getItems();
    const total = window.BloomoraCart.getTotal();

    if (subtotalEl) subtotalEl.textContent = total.toLocaleString('vi-VN') + '₫';
    if (totalEl) totalEl.textContent = total.toLocaleString('vi-VN') + '₫';

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
      status: 'received',
      statusText: '1. Đã Tiếp Nhận',
      photo: '',
      note: 'Đơn mới đặt qua website Bloomora. Khách yêu cầu chụp ảnh duyệt trước khi giao.'
    };

    orders.unshift(newOrder);
    localStorage.setItem('bloomora_orders', JSON.stringify(orders));

    window.BloomoraCart.clear();
    window.closeCartDrawer();

    alert(`🎉 ĐẶT HOA THÀNH CÔNG!\n\nMã đơn hàng của bạn: ${orderCode}\nFlorist Bloomora đã nhận đơn và đang chuẩn bị hoa.\nTrước khi giao hàng, chúng tôi sẽ chụp ảnh hoa thật gửi Zalo/SMS cho quý khách duyệt trước 100%!`);
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

  // 12. TỰ ĐỘNG KHỞI TẠO THEO TRANG ĐANG MỞ
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