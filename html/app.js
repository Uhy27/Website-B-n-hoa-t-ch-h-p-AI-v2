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

    // 1.3 Danh mục sản phẩm hoa tươi
    if (!localStorage.getItem('bloomora_products')) {
      const defaultProducts = [
        {
          id: 1,
          name: 'Kệ Khai Trương Đại Cát',
          category: 'Hoa Khai Trương',
          price: 1850000,
          originalPrice: 2200000,
          stock: 10,
          image: 'https://images.unsplash.com/photo-1561181286-d3fee7d55364?auto=format&fit=crop&w=400&q=80',
          description: 'Kệ hoa 2 tầng phối hoa hồng Ecuador và hướng dương rực rỡ mang lại may mắn tài lộc.',
          status: 'Còn hàng'
        },
        {
          id: 2,
          name: 'Bó Hồng Đỏ Nồng Nàn 99 Bông',
          category: 'Hoa Tình Yêu & Hoa Hồng',
          price: 1250000,
          originalPrice: 1500000,
          stock: 15,
          image: 'https://images.unsplash.com/photo-1582794543139-8ac9cb0f7b11?auto=format&fit=crop&w=400&q=80',
          description: 'Bó hoa 99 bông hồng đỏ thắm tượng trưng cho tình yêu vĩnh cửu nồng nàn.',
          status: 'Còn hàng'
        },
        {
          id: 3,
          name: 'Chậu Lan Hồ Điệp Hoàng Kim 5 Cành',
          category: 'Lan Hồ Điệp',
          price: 2500000,
          originalPrice: 2800000,
          stock: 8,
          image: 'https://images.unsplash.com/photo-1525310072745-f49212b5ac6d?auto=format&fit=crop&w=400&q=80',
          description: 'Lan hồ điệp vàng hoàng kim cắm chậu gốm nghệ thuật, đẳng cấp sang trọng.',
          status: 'Còn hàng'
        },
        {
          id: 4,
          name: 'Giỏ Hoa Hướng Dương Nắng Mai',
          category: 'Hoa Sinh Nhật',
          price: 850000,
          originalPrice: 950000,
          stock: 12,
          image: 'https://images.unsplash.com/photo-1597848212624-a19eb35e2651?auto=format&fit=crop&w=400&q=80',
          description: 'Hướng dương kết hợp cùng hoa baby trắng tươi tắn mừng tuổi mới.',
          status: 'Còn hàng'
        },
        {
          id: 5,
          name: 'Bó Hoa Baby Trắng Tinh Khôi',
          category: 'Hoa Tình Yêu & Hoa Hồng',
          price: 650000,
          originalPrice: 750000,
          stock: 20,
          image: 'https://images.unsplash.com/photo-1563245372-f21724e3856d?auto=format&fit=crop&w=400&q=80',
          description: 'Bó hoa baby trắng tự nhiên, bọc giấy kraft phong cách Hàn Quốc.',
          status: 'Còn hàng'
        }
      ];
      localStorage.setItem('bloomora_products', JSON.stringify(defaultProducts));
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
      stock: stock,
      image: image,
      description: description,
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

  let editingInvId = null;

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

  // 7. TÌM KIẾM NHANH TRANG CHỦ & ĐĂNG XUẤT
  window.handleQuickSearch = function (e) {
    e.preventDefault();
    const occasion = document.getElementById('quickOccasion');
    const price = document.getElementById('quickPrice');
    const location = document.getElementById('quickLocation');

    const notice = document.getElementById('searchResultNotice');
    const text = document.getElementById('searchResultText');

    if (notice && text && occasion && price && location) {
      notice.classList.remove('hidden');
      text.innerHTML = `Bạn vừa chọn tìm: <strong>${occasion.options[occasion.selectedIndex].text}</strong> | Mức giá: <strong>${price.options[price.selectedIndex].text}</strong> | Khu vực: <strong>${location.options[location.selectedIndex].text}</strong>.`;
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

  // 8. TỰ ĐỘNG KHỞI TẠO THEO TRANG ĐANG MỞ
  document.addEventListener('DOMContentLoaded', () => {
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