/**
 * BLOOMORA ADMIN 2.0 - MASTER JAVASCRIPT APPLICATION CONTROLLER
 * Full SPA Controller for all 13 Administration Modules
 */

// Global State
let currentView = 'dashboard';
let currentOrders = [];
let currentProducts = [];
let currentCategories = [];
let currentMaterials = [];
let currentStaff = [];
let currentCustomers = [];
let currentDeliveryOrders = [];
let availableStaffList = [];
let deliveryStage = 'all';

// Maps & Formatting Helpers
const STATUS_MAP = {
  'received': { text: '1. Tiếp nhận', bg: 'bg-stone-100 text-stone-700', border: 'border-stone-300' },
  'preparing': { text: '2. Đang cắm hoa', bg: 'bg-amber-100 text-amber-800', border: 'border-amber-300' },
  'photo_ready': { text: '3. Đã chụp ảnh QC', bg: 'bg-blue-100 text-blue-800', border: 'border-blue-300' },
  'delivering': { text: '4. Đang giao hàng', bg: 'bg-purple-100 text-purple-800', border: 'border-purple-300' },
  'completed': { text: '5. Hoàn tất', bg: 'bg-emerald-100 text-emerald-800', border: 'border-emerald-300' },
  'cancelled': { text: 'Đã hủy', bg: 'bg-rose-100 text-rose-800', border: 'border-rose-300' },
};

const PAYMENT_METHOD_MAP = {
  'vietqr': { text: 'VietQR (Ngân hàng)', icon: 'ph-bank' },
  'momo': { text: 'Ví MoMo', icon: 'ph-wallet' },
  'cod': { text: 'Tiền mặt (COD)', icon: 'ph-money' },
  'bank_transfer': { text: 'Chuyển khoản', icon: 'ph-arrows-left-right' }
};

function escapeHtml(str) {
  if (str === null || str === undefined) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function formatMoney(amount) {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(Number(amount) || 0);
}

function showAdminToast(message, type = 'success') {
  let box = document.getElementById('adminToastContainer');
  if (!box) {
    box = document.createElement('div');
    box.id = 'adminToastContainer';
    box.className = 'fixed top-5 right-5 z-[90] flex flex-col items-end space-y-2 pointer-events-none';
    document.body.appendChild(box);
  }
  const el = document.createElement('div');
  const styles = type === 'error'
    ? 'bg-rose-600 text-white'
    : type === 'warning'
      ? 'bg-amber-600 text-white'
      : 'bg-stone-900 text-white';
  const icon = type === 'error' ? 'ph-x-circle' : type === 'warning' ? 'ph-warning-circle' : 'ph-check-circle';
  el.className = `pointer-events-auto px-4 py-3 rounded-2xl shadow-xl text-xs font-semibold flex items-center gap-2 max-w-sm ${styles}`;
  el.innerHTML = `<i class="ph-fill ${icon} text-base flex-shrink-0"></i><span>${escapeHtml(message)}</span>`;
  box.appendChild(el);
  setTimeout(() => {
    el.style.opacity = '0';
    el.style.transition = 'opacity 0.25s ease';
    setTimeout(() => el.remove(), 260);
  }, 3400);
}

function showFormAlert(elId, message, isError = true) {
  const el = document.getElementById(elId);
  if (!el) return;
  if (!message) {
    el.classList.add('hidden');
    el.textContent = '';
    return;
  }
  el.className = isError
    ? 'mb-3 p-3 rounded-xl text-xs font-semibold bg-rose-50 border border-rose-200 text-rose-700 block'
    : 'mb-3 p-3 rounded-xl text-xs font-semibold bg-emerald-50 border border-emerald-200 text-emerald-700 block';
  el.textContent = message;
}

function slugifyVietnamese(text) {
  return String(text || '')
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/đ/g, 'd')
    .replace(/[^a-z0-9\s-]/g, '')
    .trim()
    .replace(/[\s-]+/g, '-')
    .replace(/^-+|-+$/g, '');
}

function formatDateVN(dtStr) {
  if (!dtStr) return '--';
  const s = String(dtStr).replace(' ', 'T');
  const d = new Date(s);
  if (isNaN(d.getTime())) return String(dtStr);
  const pad = n => String(n).padStart(2, '0');
  return `${pad(d.getDate())}/${pad(d.getMonth() + 1)}/${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

function toDatetimeLocalValue(dtStr) {
  if (!dtStr) return '';
  return String(dtStr).trim().replace(' ', 'T').slice(0, 16);
}

// Sidebar toggle for mobile
function toggleSidebar() {
  const sb = document.getElementById('adminSidebar');
  const bd = document.getElementById('sidebarBackdrop');
  if (sb.classList.contains('-translate-x-full')) {
    sb.classList.remove('-translate-x-full');
    bd.classList.remove('hidden');
  } else {
    sb.classList.add('-translate-x-full');
    bd.classList.add('hidden');
  }
}

// Realtime Clock
function startClock() {
  const clockEl = document.getElementById('liveClockDisplay');
  function update() {
    const now = new Date();
    const timeStr = now.toLocaleTimeString('vi-VN', { hour12: false });
    const dateStr = now.toLocaleDateString('vi-VN', { weekday: 'short', day: '2-digit', month: '2-digit' });
    if (clockEl) clockEl.textContent = `${dateStr} • ${timeStr}`;
  }
  update();
  setInterval(update, 1000);
}

// ==========================================================================
// VIEW SWITCHER ENGINE
// ==========================================================================
const VIEW_METADATA = {
  'dashboard':  { title: 'Bảng Điều Khiển Tổng Quan', breadcrumb: 'Tổng Quan / Bảng Điều Khiển' },
  'orders':     { title: 'Quản Lý Đơn Hàng & Tiến Trình Cắm Hoa', breadcrumb: 'Bán Hàng / Quản Lý Đơn Hàng' },
  'payments':   { title: 'Sổ Đối Soát Thanh Toán & Dòng Tiền', breadcrumb: 'Bán Hàng / Thanh Toán & Đối Soát' },
  'delivery':   { title: 'Điều Phối Florist & Kiểm Định QC Giao Hoa', breadcrumb: 'Bán Hàng / Điều Phối & QC' },
  'products':   { title: 'Quản Lý Mẫu Hoa & Bảng Giá', breadcrumb: 'Danh Mục & Kho / Sản Phẩm Hoa Tươi' },
  'categories': { title: 'Danh Mục & Chủ Đề Hoa Tươi', breadcrumb: 'Danh Mục & Kho / Danh Mục Hoa' },
  'inventory':  { title: 'Quản Lý Kho Hoa & Nguyên Phụ Liệu', breadcrumb: 'Danh Mục & Kho / Kho & Nguyên Phụ Liệu' },
  'promotions': { title: 'Chiến Dịch Khuyến Mãi & Vouchers', breadcrumb: 'Marketing / Khuyến Mãi & Vouchers' },
  'reviews':    { title: 'Kiểm Duyệt Đánh Giá & Ảnh Thực Tế', breadcrumb: 'Marketing / Đánh Giá & Ảnh Thực Tế' },
  'customers':  { title: 'Hồ Sơ Khách Hàng & Phân Khúc VIP', breadcrumb: 'Marketing / Khách Hàng & VIP' },
  'reports':    { title: 'Báo Cáo Hoạt Động & Doanh Thu Chi Tiết', breadcrumb: 'Báo Cáo / Doanh Thu & Hiệu Suất' },
  'staff':      { title: 'Quản Lý Nhân Sự & Phân Quyền RBAC', breadcrumb: 'Hệ Thống / Nhân Sự & Phân Quyền' },
  'settings':   { title: 'Cài Đặt Cửa Hàng & Nhật Ký Kiểm Toán', breadcrumb: 'Hệ Thống / Cài Đặt & Nhật Ký' },
};

function switchView(viewName) {
  currentView = viewName;

  // Ẩn tất cả views
  document.querySelectorAll('main > section').forEach(sec => {
    sec.classList.add('hidden');
  });

  // Gỡ active sidebar
  document.querySelectorAll('.sidebar-link').forEach(btn => {
    btn.classList.remove('active');
  });

  // Hiển thị view được chọn
  const targetView = document.getElementById(`view-${viewName}`);
  if (targetView) {
    targetView.classList.remove('hidden');
  }

  // Active button sidebar
  const activeBtn = document.getElementById(`nav-${viewName}`);
  if (activeBtn) {
    activeBtn.classList.add('active');
  }

  // Cập nhật Header & Breadcrumb
  const meta = VIEW_METADATA[viewName] || { title: 'Bloomora Admin', breadcrumb: 'Admin' };
  document.getElementById('pageTitle').textContent = meta.title;
  document.getElementById('breadcrumbSection').textContent = meta.breadcrumb;

  // Đóng sidebar nếu đang mở trên mobile
  if (window.innerWidth < 768) {
    const sb = document.getElementById('adminSidebar');
    const bd = document.getElementById('sidebarBackdrop');
    sb.classList.add('-translate-x-full');
    bd.classList.add('hidden');
  }

  // Cập nhật URL hash
  window.location.hash = viewName;

  // Gọi API tải dữ liệu của view tương ứng
  dispatchViewLoader(viewName);
}

function dispatchViewLoader(viewName) {
  switch (viewName) {
    case 'dashboard':
      loadDashboardData();
      break;
    case 'orders':
      loadAdminOrders();
      break;
    case 'payments':
      loadPaymentsList();
      break;
    case 'delivery':
      loadDeliveryData();
      break;
    case 'products':
      loadCategoryDropdown();
      loadAdminProducts();
      break;
    case 'categories':
      loadAdminCategories(1);
      break;
    case 'inventory':
      loadMaterialsList();
      break;
    case 'promotions':
      loadAdminPromotions(1);
      break;
    case 'reviews':
      loadAdminReviews(1);
      break;
    case 'customers':
      loadAdminCustomers();
      break;
    case 'reports':
      loadReportsData();
      break;
    case 'staff':
      loadAdminStaff();
      break;
    case 'settings':
      loadStoreSettings();
      loadActivityLogs();
      break;
  }
}

function refreshCurrentView() {
  const icon = document.getElementById('refreshIcon');
  if (icon) icon.classList.add('animate-spin');
  dispatchViewLoader(currentView);
  setTimeout(() => {
    if (icon) icon.classList.remove('animate-spin');
  }, 700);
}

// ==========================================================================
// 1. DASHBOARD MODULE
// ==========================================================================
async function loadDashboardData() {
  try {
    const res = await fetch('../api/admin/stats.php');
    if (res.status === 401) { window.location.href = 'login.php'; return; }
    const json = await res.json();
    if (!json.success) return;

    const { kpis, revenueTrend, statusDist, paymentDist, topProducts, recentActivities } = json.data;

    // Update Top 4 KPI Cards
    document.getElementById('dashTotalRevenue').textContent = formatMoney(kpis.totalRevenue);
    document.getElementById('dashTodayRevenue').textContent = formatMoney(kpis.todayRevenue);
    document.getElementById('dashTotalOrders').textContent = kpis.totalOrders;
    document.getElementById('dashPendingOrders').textContent = kpis.pendingOrders;
    document.getElementById('dashDeliveringOrders').textContent = kpis.deliveringOrders;

    const totalLowStock = (Number(kpis.lowStockProducts) || 0) + (Number(kpis.lowStockMaterials) || 0);
    document.getElementById('dashLowStockCount').textContent = `${totalLowStock} mục`;

    // Sidebar Badges
    const badgeOrders = document.getElementById('badgeActiveOrders');
    if (badgeOrders) {
      const activeCount = (Number(kpis.pendingOrders) || 0) + (Number(kpis.deliveringOrders) || 0);
      if (activeCount > 0) {
        badgeOrders.textContent = activeCount;
        badgeOrders.classList.remove('hidden');
      } else {
        badgeOrders.classList.add('hidden');
      }
    }

    const badgeQC = document.getElementById('badgePendingQC');
    if (badgeQC) {
      if (kpis.photoReadyOrders > 0) {
        badgeQC.textContent = kpis.photoReadyOrders;
        badgeQC.classList.remove('hidden');
      } else {
        badgeQC.classList.add('hidden');
      }
    }

    const badgeLow = document.getElementById('badgeLowStock');
    if (badgeLow) {
      if (totalLowStock > 0) {
        badgeLow.textContent = totalLowStock;
        badgeLow.classList.remove('hidden');
      } else {
        badgeLow.classList.add('hidden');
      }
    }

    const badgeReviews = document.getElementById('badgePendingReviews');
    if (badgeReviews) {
      if (kpis.pendingReviews > 0) {
        badgeReviews.textContent = kpis.pendingReviews;
        badgeReviews.classList.remove('hidden');
      } else {
        badgeReviews.classList.add('hidden');
      }
    }

    // Render 7-Day Revenue Trend Chart
    renderRevenueChart(revenueTrend || []);

    // Render Status Breakdown
    renderStatusBreakdown(statusDist || {});

    // Render Payment Breakdown
    renderPaymentBreakdown(paymentDist || []);

    // Render Top Products
    renderTopProducts(topProducts || []);

    // Render Recent Activities
    renderRecentActivities(recentActivities || []);

  } catch (err) {
    console.error('Lỗi tải dữ liệu Dashboard:', err);
  }
}

function renderRevenueChart(trendData) {
  const container = document.getElementById('revenueChartContainer');
  const labelsContainer = document.getElementById('chartDateLabels');
  if (!container || !labelsContainer) return;

  if (trendData.length === 0) {
    container.innerHTML = `<div class="text-center text-xs text-stone-400 w-full py-16">Chưa có giao dịch phát sinh trong 7 ngày qua.</div>`;
    labelsContainer.innerHTML = '';
    return;
  }

  const maxRev = Math.max(...trendData.map(d => Number(d.revenue) || 0), 100000);

  container.innerHTML = trendData.map(d => {
    const rev = Number(d.revenue) || 0;
    const heightPercent = Math.max(8, Math.round((rev / maxRev) * 100));
    return `
      <div class="flex-1 flex flex-col items-center h-full justify-end group relative cursor-pointer">
        <!-- Tooltip -->
        <div class="absolute -top-12 opacity-0 group-hover:opacity-100 transition-opacity bg-stone-900 text-white text-[10px] px-2.5 py-1 rounded-lg pointer-events-none whitespace-nowrap shadow-xl z-10 text-center font-mono">
          <div class="font-bold">${formatMoney(rev)}</div>
          <div class="text-stone-400">${d.order_count} đơn hàng</div>
        </div>
        <!-- Bar -->
        <div style="height: ${heightPercent}%;" class="w-full max-w-[42px] bg-gradient-to-t from-rose-700 to-rose-500 hover:from-rose-600 hover:to-rose-400 rounded-t-xl transition-all shadow-sm"></div>
      </div>
    `;
  }).join('');

  labelsContainer.innerHTML = trendData.map(d => {
    const parts = d.order_date.split('-');
    const label = `${parts[2]}/${parts[1]}`;
    return `<span class="flex-1 text-center truncate">${label}</span>`;
  }).join('');
}

function renderStatusBreakdown(statusDist) {
  const container = document.getElementById('statusBreakdownContainer');
  if (!container) return;

  const total = Object.values(statusDist).reduce((a, b) => a + Number(b), 0) || 1;

  const items = [
    { key: 'received', label: '1. Tiếp nhận', count: statusDist.received || 0, color: 'bg-stone-500' },
    { key: 'preparing', label: '2. Đang cắm hoa', count: statusDist.preparing || 0, color: 'bg-amber-500' },
    { key: 'photo_ready', label: '3. Đã duyệt ảnh QC', count: statusDist.photo_ready || 0, color: 'bg-blue-500' },
    { key: 'delivering', label: '4. Đang giao hàng', count: statusDist.delivering || 0, color: 'bg-purple-500' },
    { key: 'completed', label: '5. Hoàn tất', count: statusDist.completed || 0, color: 'bg-emerald-500' },
    { key: 'cancelled', label: 'Đã hủy', count: statusDist.cancelled || 0, color: 'bg-rose-500' },
  ];

  container.innerHTML = items.map(it => {
    const percent = Math.round((it.count / total) * 100);
    return `
      <div>
        <div class="flex items-center justify-between mb-1">
          <span class="text-stone-700 font-medium">${it.label}</span>
          <span class="font-bold text-stone-900 font-mono">${it.count} <span class="text-stone-400 font-normal">(${percent}%)</span></span>
        </div>
        <div class="w-full bg-stone-100 rounded-full h-2 overflow-hidden">
          <div class="${it.color} h-2 rounded-full" style="width: ${percent}%;"></div>
        </div>
      </div>
    `;
  }).join('');
}

function renderPaymentBreakdown(paymentDist) {
  const container = document.getElementById('paymentBreakdownContainer');
  if (!container) return;

  if (paymentDist.length === 0) {
    container.innerHTML = `<span class="text-stone-400 italic">Chưa có giao dịch.</span>`;
    return;
  }

  container.innerHTML = paymentDist.map(p => {
    const info = PAYMENT_METHOD_MAP[p.payment_method] || { text: p.payment_method, icon: 'ph-credit-card' };
    return `
      <div class="flex items-center justify-between p-2 rounded-xl bg-stone-50 border border-stone-100">
        <div class="flex items-center space-x-2">
          <i class="ph ${info.icon} text-base text-rose-600"></i>
          <span class="font-medium text-stone-700">${info.text}</span>
        </div>
        <div class="text-right">
          <span class="font-bold text-stone-900 block font-mono text-[11px]">${formatMoney(p.total)}</span>
          <span class="text-[10px] text-stone-400">${p.count} đơn</span>
        </div>
      </div>
    `;
  }).join('');
}

function renderTopProducts(products) {
  const container = document.getElementById('topProductsList');
  if (!container) return;

  if (products.length === 0) {
    container.innerHTML = `<div class="text-center py-6 text-stone-400 text-xs">Chưa có số liệu hoa bán chạy.</div>`;
    return;
  }

  container.innerHTML = products.map((p, idx) => {
    return `
      <div class="flex items-center justify-between p-2.5 rounded-2xl hover:bg-stone-50 transition border border-stone-100/80">
        <div class="flex items-center space-x-3">
          <span class="w-6 h-6 rounded-lg bg-stone-100 text-stone-600 font-extrabold flex items-center justify-center text-xs">#${idx + 1}</span>
          <img src="${escapeHtml(p.thumbnail)}" alt="${escapeHtml(p.name)}" class="w-11 h-11 rounded-xl object-cover border border-stone-200" />
          <div>
            <h4 class="font-semibold text-stone-900 text-xs">${escapeHtml(p.name)}</h4>
            <span class="text-[11px] text-stone-400">${formatMoney(p.price)}</span>
          </div>
        </div>
        <div class="text-right">
          <span class="font-bold text-rose-600 text-xs block font-mono">${formatMoney(p.total_sales)}</span>
          <span class="text-[11px] text-stone-500 font-medium">Đã cắm: ${p.total_sold} bó</span>
        </div>
      </div>
    `;
  }).join('');
}

function renderRecentActivities(activities) {
  const container = document.getElementById('recentActivityList');
  if (!container) return;

  if (activities.length === 0) {
    container.innerHTML = `<div class="text-center py-6 text-stone-400 text-xs">Chưa có nhật ký hoạt động nào.</div>`;
    return;
  }

  container.innerHTML = activities.map(act => {
    return `
      <div class="flex items-start space-x-2.5 pb-2.5 border-b border-stone-100 last:border-0 last:pb-0">
        <div class="w-2 h-2 rounded-full bg-rose-500 mt-1.5 flex-shrink-0"></div>
        <div class="flex-1 min-w-0">
          <p class="text-stone-800 leading-snug">${escapeHtml(act.details)}</p>
          <div class="flex items-center space-x-2 mt-0.5 text-[10px] text-stone-400">
            <span class="font-semibold text-stone-600">${escapeHtml(act.user_name || 'Hệ thống')}</span>
            <span>•</span>
            <span class="font-mono">${escapeHtml(act.created_at)}</span>
          </div>
        </div>
      </div>
    `;
  }).join('');
}

// ==========================================================================
// 2. ORDERS MODULE
// ==========================================================================
async function loadAdminOrders() {
  const status = document.getElementById('orderStatusFilter')?.value || 'all';
  const payment = document.getElementById('orderPaymentFilter')?.value || 'all';
  const search = document.getElementById('orderSearchInput')?.value || '';
  const tbody = document.getElementById('ordersTableBody');

  if (tbody) tbody.innerHTML = `<tr><td colspan="9" class="text-center py-10 text-stone-400">Đang tải danh sách đơn hàng...</td></tr>`;

  try {
    const q = new URLSearchParams({ status, payment_status: payment, search });
    const res = await fetch(`../api/admin/orders.php?${q.toString()}`);
    if (res.status === 401) { window.location.href = 'login.php'; return; }
    const json = await res.json();

    if (json.success) {
      currentOrders = json.data;
      renderOrdersTable(json.data);
    } else {
      if (tbody) tbody.innerHTML = `<tr><td colspan="9" class="text-center py-8 text-rose-500">${escapeHtml(json.message)}</td></tr>`;
    }
  } catch (err) {
    if (tbody) tbody.innerHTML = `<tr><td colspan="9" class="text-center py-8 text-rose-500">Lỗi kết nối API đơn hàng.</td></tr>`;
  }
}

function renderOrdersTable(orders) {
  const tbody = document.getElementById('ordersTableBody');
  if (!tbody) return;

  if (orders.length === 0) {
    tbody.innerHTML = `<tr><td colspan="9" class="text-center py-12 text-stone-400">Không có đơn hàng nào phù hợp với bộ lọc hiện tại.</td></tr>`;
    return;
  }

  tbody.innerHTML = orders.map(o => {
    const st = STATUS_MAP[o.order_status] || { text: o.order_status, bg: 'bg-stone-100 text-stone-700' };
    const itemsSummary = (o.items && o.items.length > 0)
      ? o.items.map(it => `<div>• ${escapeHtml(it.product_name)} <span class="text-rose-600 font-bold">x${it.quantity}</span></div>`).join('')
      : '<span class="text-stone-400 italic">Mẫu hoa tiêu chuẩn</span>';

    return `
      <tr class="hover:bg-stone-50 transition">
        <td class="py-3 px-4 font-mono font-bold text-rose-600 whitespace-nowrap">
          #${escapeHtml(o.order_code)}
        </td>
        <td class="py-3 px-4 min-w-[140px]">
          <div class="font-bold text-stone-900">${escapeHtml(o.receiver_name)}</div>
          <div class="text-[11px] text-stone-500">${escapeHtml(o.receiver_phone)}</div>
          <div class="text-[10px] text-stone-400">Người đặt: ${o.is_anonymous == 1 ? '<em class="text-rose-500">Ẩn danh</em>' : escapeHtml(o.sender_name)}</div>
        </td>
        <td class="py-3 px-4 max-w-xs">
          <div class="truncate text-stone-700" title="${escapeHtml(o.receiver_address)}">${escapeHtml(o.receiver_address)}</div>
          <div class="text-[11px] text-stone-500 font-mono">${escapeHtml(o.delivery_date)} • <strong>${escapeHtml(o.delivery_time_slot)}</strong></div>
        </td>
        <td class="py-3 px-4 max-w-xs text-[11px] text-stone-600">
          ${itemsSummary}
        </td>
        <td class="py-3 px-4 max-w-xs truncate text-stone-600 italic" title="${escapeHtml(o.ribbon_message || o.card_message || 'Không in')}">
          "${escapeHtml(o.ribbon_message || o.card_message || 'Không in')}"
        </td>
        <td class="py-3 px-4 font-bold text-stone-900 whitespace-nowrap">
          ${formatMoney(o.total_amount)}
          <span class="block text-[10px] text-stone-400 font-normal uppercase">
            ${escapeHtml(o.payment_method)} • <span class="${o.payment_status === 'paid' ? 'text-emerald-600 font-bold' : 'text-amber-600'}">${escapeHtml(o.payment_status)}</span>
          </span>
        </td>
        <td class="py-3 px-4 whitespace-nowrap">
          <span class="px-2.5 py-1 rounded-full text-[11px] font-semibold ${st.bg}">
            ${st.text}
          </span>
        </td>
        <td class="py-3 px-4 text-center">
          ${o.photo_proof ? `
            <img src="${escapeHtml(o.photo_proof)}" 
                 onclick="openAdminLightbox('${escapeHtml(o.photo_proof)}')" 
                 alt="Photo Proof" 
                 title="Bấm phóng to ảnh hoa thực tế" 
                 class="w-10 h-10 rounded-lg object-cover border border-stone-300 mx-auto cursor-pointer hover:opacity-80 shadow-sm" />
          ` : `<span class="text-stone-300 text-[11px] italic">Chưa chụp</span>`}
        </td>
        <td class="py-3 px-4 text-right whitespace-nowrap space-x-1">
          <button onclick="openOrderDetailModal(${o.id})" class="p-1.5 bg-stone-100 hover:bg-stone-200 text-stone-700 rounded-lg transition" title="Xem chi tiết & In phiếu">
            <i class="ph ph-eye text-sm"></i>
          </button>
          <button onclick="openUpdateOrderModal(${o.id})" class="px-2.5 py-1.5 bg-stone-900 hover:bg-black text-white rounded-lg text-xs font-semibold shadow transition">
            Cập nhật
          </button>
        </td>
      </tr>
    `;
  }).join('');
}

// Order Details & Print Invoice
async function openOrderDetailModal(orderId) {
  const modal = document.getElementById('orderDetailModal');
  const content = document.getElementById('orderDetailContent');
  const header = document.getElementById('detailOrderCodeHeader');

  content.innerHTML = `<div class="text-center py-10 text-stone-400">Đang tải chi tiết đơn hàng...</div>`;
  modal.classList.remove('hidden');

  try {
    const res = await fetch(`../api/admin/orders.php?id=${orderId}`);
    const json = await res.json();
    if (!json.success) {
      content.innerHTML = `<div class="text-rose-500 py-4">${escapeHtml(json.message)}</div>`;
      return;
    }

    const o = json.data;
    header.textContent = `Mã đơn: #${o.order_code} • Ngày đặt: ${o.created_at}`;

    let itemsTable = o.items.map(it => `
      <tr class="border-b border-stone-100">
        <td class="py-2 px-3 font-semibold text-stone-900">${escapeHtml(it.product_name)}</td>
        <td class="py-2 px-3 text-center">${it.quantity}</td>
        <td class="py-2 px-3 text-right">${formatMoney(it.unit_price)}</td>
        <td class="py-2 px-3 text-right font-bold">${formatMoney(it.total_price)}</td>
      </tr>
    `).join('');

    let historyTimeline = (o.history || []).map(h => `
      <div class="flex items-start space-x-2 text-[11px] pb-1.5">
        <i class="ph ph-check-circle text-emerald-600 mt-0.5"></i>
        <div>
          <span class="font-bold text-stone-800">${escapeHtml(h.status)}</span>: 
          <span class="text-stone-600">${escapeHtml(h.note || '')}</span>
          <span class="text-stone-400 text-[10px] ml-1 font-mono">(${escapeHtml(h.created_at)})</span>
        </div>
      </div>
    `).join('');

    content.innerHTML = `
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 rounded-2xl bg-stone-50 border border-stone-200">
        <div>
          <h4 class="font-bold text-stone-800 text-xs mb-1">Thông Tin Người Nhận Hoa:</h4>
          <p class="font-semibold text-stone-900">${escapeHtml(o.receiver_name)} - ${escapeHtml(o.receiver_phone)}</p>
          <p class="text-stone-600">${escapeHtml(o.receiver_address)}, ${escapeHtml(o.city || 'TP. HCM')}</p>
          <p class="text-rose-600 font-semibold mt-1">Giao: ${escapeHtml(o.delivery_date)} (${escapeHtml(o.delivery_time_slot)})</p>
        </div>
        <div>
          <h4 class="font-bold text-stone-800 text-xs mb-1">Người Gửi & Thanh Toán:</h4>
          <p class="text-stone-700">Người đặt: ${o.is_anonymous == 1 ? '<strong class="text-rose-600">Giấu danh tính (Ẩn danh)</strong>' : escapeHtml(o.sender_name + ' - ' + o.sender_phone)}</p>
          <p class="text-stone-700">Phương thức: <strong class="uppercase">${escapeHtml(o.payment_method)}</strong> (<span class="${o.payment_status === 'paid' ? 'text-emerald-600 font-bold' : 'text-amber-600'}">${escapeHtml(o.payment_status)}</span>)</p>
          <p class="text-stone-700">Florist: ${escapeHtml(o.florist_name || 'Chưa gán')} • Shipper: ${escapeHtml(o.shipper_name || 'Chưa gán')}</p>
        </div>
      </div>

      <div class="p-4 rounded-2xl bg-amber-50/70 border border-amber-200 text-stone-800">
        <h4 class="font-bold text-amber-900 text-xs mb-1 flex items-center space-x-1">
          <i class="ph ph-envelope-open text-base"></i>
          <span>Thông Điệp Thiệp / Băng Rôn Cắm Kèm:</span>
        </h4>
        <p class="italic text-xs font-serif">"${escapeHtml(o.ribbon_message || o.card_message || 'Không yêu cầu in thiệp')}"</p>
      </div>

      <div class="border border-stone-200 rounded-2xl overflow-hidden">
        <table class="w-full text-xs text-left">
          <thead class="bg-stone-50 text-[11px] text-stone-400 uppercase border-b border-stone-200">
            <tr>
              <th class="py-2 px-3">Mẫu Hoa</th>
              <th class="py-2 px-3 text-center">SL</th>
              <th class="py-2 px-3 text-right">Đơn Giá</th>
              <th class="py-2 px-3 text-right">Thành Tiền</th>
            </tr>
          </thead>
          <tbody>
            ${itemsTable}
          </tbody>
          <tfoot class="bg-stone-50 font-semibold border-t border-stone-200">
            <tr>
              <td colspan="3" class="py-2 px-3 text-right">Tạm tính:</td>
              <td class="py-2 px-3 text-right">${formatMoney(o.subtotal)}</td>
            </tr>
            <tr>
              <td colspan="3" class="py-2 px-3 text-right">Phí giao hoa:</td>
              <td class="py-2 px-3 text-right">${formatMoney(o.shipping_fee)}</td>
            </tr>
            <tr>
              <td colspan="3" class="py-2 px-3 text-right text-rose-600">Giảm giá voucher${o.voucher_code ? ` (<span class="font-mono font-bold">${escapeHtml(o.voucher_code)}</span>)` : ''}:</td>
              <td class="py-2 px-3 text-right text-rose-600">-${formatMoney(o.discount_amount)}</td>
            </tr>
            <tr class="text-base font-bold text-stone-900">
              <td colspan="3" class="py-2 px-3 text-right">Tổng thanh toán:</td>
              <td class="py-2 px-3 text-right text-rose-700">${formatMoney(o.total_amount)}</td>
            </tr>
          </tfoot>
        </table>
      </div>

      ${o.photo_proof ? `
        <div class="p-3 rounded-2xl bg-stone-50 border border-stone-200 flex items-center space-x-3">
          <img src="${escapeHtml(o.photo_proof)}" onclick="openAdminLightbox('${escapeHtml(o.photo_proof)}')" class="w-14 h-14 rounded-xl object-cover border border-stone-300 cursor-pointer shadow-sm" />
          <div>
            <h5 class="font-bold text-stone-900 text-xs">Ảnh Mẫu Hoa Thực Tế Đã Duyệt QC:</h5>
            <span class="text-[11px] text-stone-500">Khách hàng có thể tra cứu và xem trước ảnh này.</span>
          </div>
        </div>
      ` : ''}

      <div class="p-3 rounded-2xl bg-stone-50 border border-stone-200">
        <h5 class="font-bold text-stone-800 text-xs mb-2">Lịch Sử Vòng Đời Đơn Hàng:</h5>
        <div class="space-y-1">
          ${historyTimeline}
        </div>
      </div>
    `;

    // Chuẩn bị layout in hóa đơn
    preparePrintInvoice(o);

  } catch (err) {
    content.innerHTML = `<div class="text-rose-500 py-4">Lỗi nạp chi tiết đơn.</div>`;
  }
}

function closeOrderDetailModal() {
  document.getElementById('orderDetailModal').classList.add('hidden');
}

function preparePrintInvoice(o) {
  const printSec = document.getElementById('invoicePrintSection');
  if (!printSec) return;

  const itemsHtml = o.items.map((it, idx) => `
    <tr>
      <td style="border: 1px solid #ddd; padding: 6px; text-align: center;">${idx + 1}</td>
      <td style="border: 1px solid #ddd; padding: 6px;"><strong>${escapeHtml(it.product_name)}</strong></td>
      <td style="border: 1px solid #ddd; padding: 6px; text-align: center;">${it.quantity}</td>
      <td style="border: 1px solid #ddd; padding: 6px; text-align: right;">${formatMoney(it.unit_price)}</td>
      <td style="border: 1px solid #ddd; padding: 6px; text-align: right;">${formatMoney(it.total_price)}</td>
    </tr>
  `).join('');

  printSec.innerHTML = `
    <div style="font-family: 'Be Vietnam Pro', sans-serif; font-size: 13px; color: #111; line-height: 1.5;">
      <div style="display: flex; justify-content: space-between; border-bottom: 2px solid #be123c; padding-bottom: 12px; margin-bottom: 16px;">
        <div>
          <h1 style="color: #be123c; font-size: 22px; font-weight: bold; margin: 0;">BLOOMORA FLORIST</h1>
          <p style="margin: 2px 0; font-size: 11px; color: #555;">Tiệm Hoa Tươi Thiết Kế Nghệ Thuật & Quà Tặng</p>
          <p style="margin: 2px 0; font-size: 11px; color: #555;">Hotline: 1900 6868 • contact@bloomora.vn</p>
        </div>
        <div style="text-align: right;">
          <h2 style="font-size: 18px; margin: 0; font-weight: bold;">PHIẾU GIAO HOA</h2>
          <p style="margin: 2px 0; font-family: monospace; font-weight: bold; color: #be123c;">Mã Đơn: #${escapeHtml(o.order_code)}</p>
          <p style="margin: 2px 0; font-size: 11px;">Ngày in: ${new Date().toLocaleString('vi-VN')}</p>
        </div>
      </div>

      <div style="display: flex; justify-content: space-between; margin-bottom: 16px;">
        <div style="width: 48%; border: 1px solid #eee; padding: 10px; border-radius: 8px;">
          <h3 style="font-size: 12px; margin: 0 0 6px 0; text-transform: uppercase; color: #555;">Người Nhận Hoa:</h3>
          <p style="margin: 3px 0;"><strong>${escapeHtml(o.receiver_name)}</strong> - ${escapeHtml(o.receiver_phone)}</p>
          <p style="margin: 3px 0;">Địa chỉ: ${escapeHtml(o.receiver_address)}, ${escapeHtml(o.city || 'TP. HCM')}</p>
          <p style="margin: 3px 0; color: #be123c;"><strong>Giao ngày: ${escapeHtml(o.delivery_date)} (${escapeHtml(o.delivery_time_slot)})</strong></p>
        </div>
        <div style="width: 48%; border: 1px solid #eee; padding: 10px; border-radius: 8px;">
          <h3 style="font-size: 12px; margin: 0 0 6px 0; text-transform: uppercase; color: #555;">Người Đặt & Thanh Toán:</h3>
          <p style="margin: 3px 0;">Người đặt: ${o.is_anonymous == 1 ? '<em>Khách ẩn danh</em>' : escapeHtml(o.sender_name + ' (' + o.sender_phone + ')')}</p>
          <p style="margin: 3px 0;">Phương thức: <strong>${escapeHtml(o.payment_method).toUpperCase()}</strong> (${escapeHtml(o.payment_status)})</p>
          <p style="margin: 3px 0;">Florist: ${escapeHtml(o.florist_name || 'Xưởng Bloomora')} • Shipper: ${escapeHtml(o.shipper_name || 'Đội vận chuyển')}</p>
        </div>
      </div>

      <div style="background: #fff8f8; border: 1px dashed #e11d48; padding: 10px; border-radius: 8px; margin-bottom: 16px;">
        <strong style="color: #be123c;">NỘI DUNG THIỆP / BĂNG RÔN:</strong>
        <p style="font-style: italic; margin: 4px 0 0 0; font-size: 14px;">"${escapeHtml(o.ribbon_message || o.card_message || 'Không in thiệp')}"</p>
      </div>

      <table style="width: 100%; border-collapse: collapse; margin-bottom: 16px;">
        <thead>
          <tr style="background: #f5f5f5;">
            <th style="border: 1px solid #ddd; padding: 6px; width: 40px;">STT</th>
            <th style="border: 1px solid #ddd; padding: 6px;">Tên Sản Phẩm Hoa</th>
            <th style="border: 1px solid #ddd; padding: 6px; width: 60px;">SL</th>
            <th style="border: 1px solid #ddd; padding: 6px; width: 100px;">Đơn Giá</th>
            <th style="border: 1px solid #ddd; padding: 6px; width: 120px;">Thành Tiền</th>
          </tr>
        </thead>
        <tbody>
          ${itemsHtml}
        </tbody>
        <tfoot>
          <tr>
            <td colspan="4" style="border: 1px solid #ddd; padding: 6px; text-align: right;">Tạm tính:</td>
            <td style="border: 1px solid #ddd; padding: 6px; text-align: right;">${formatMoney(o.subtotal)}</td>
          </tr>
          <tr>
            <td colspan="4" style="border: 1px solid #ddd; padding: 6px; text-align: right;">Phí giao hoa:</td>
            <td style="border: 1px solid #ddd; padding: 6px; text-align: right;">${formatMoney(o.shipping_fee)}</td>
          </tr>
          ${o.discount_amount > 0 ? `
            <tr>
              <td colspan="4" style="border: 1px solid #ddd; padding: 6px; text-align: right; color: #be123c;">Voucher giảm giá:</td>
              <td style="border: 1px solid #ddd; padding: 6px; text-align: right; color: #be123c;">-${formatMoney(o.discount_amount)}</td>
            </tr>
          ` : ''}
          <tr style="font-size: 14px; font-weight: bold;">
            <td colspan="4" style="border: 1px solid #ddd; padding: 6px; text-align: right;">TỔNG TIỀN:</td>
            <td style="border: 1px solid #ddd; padding: 6px; text-align: right; color: #be123c;">${formatMoney(o.total_amount)}</td>
          </tr>
        </tfoot>
      </table>

      <div style="display: flex; justify-content: space-between; margin-top: 30px; text-align: center;">
        <div style="width: 30%;">
          <p><strong>Nghệ Nhân Florist</strong></p>
          <p style="font-size: 11px; color: #777;">(Ký & ghi rõ họ tên)</p>
        </div>
        <div style="width: 30%;">
          <p><strong>Nhân Viên Giao Hoa</strong></p>
          <p style="font-size: 11px; color: #777;">(Ký & ghi rõ họ tên)</p>
        </div>
        <div style="width: 30%;">
          <p><strong>Người Nhận Hoa</strong></p>
          <p style="font-size: 11px; color: #777;">(Ký nhận hoa tươi)</p>
        </div>
      </div>
    </div>
  `;
}

function printOrderInvoice() {
  window.print();
}

function openUpdateOrderModal(orderId) {
  const o = currentOrders.find(x => x.id == orderId);
  if (!o) return;

  document.getElementById('modalOrderId').value = o.id;
  document.getElementById('modalOrderCodeDisplay').textContent = `Mã: #${o.order_code} - Khách: ${o.receiver_name}`;
  document.getElementById('modalOrderStatus').value = o.order_status;
  document.getElementById('modalPhotoProof').value = o.photo_proof || '';
  document.getElementById('modalNote').value = `Cập nhật trạng thái đơn #${o.order_code}`;

  document.getElementById('updateOrderModal').classList.remove('hidden');
}

function closeUpdateOrderModal() {
  document.getElementById('updateOrderModal').classList.add('hidden');
}

async function handleUpdateOrderSubmit(e) {
  e.preventDefault();
  const orderId = document.getElementById('modalOrderId').value;
  const status = document.getElementById('modalOrderStatus').value;
  const photoProof = document.getElementById('modalPhotoProof').value.trim();
  const note = document.getElementById('modalNote').value.trim();
  const btn = document.getElementById('modalSaveBtn');

  btn.disabled = true;
  btn.textContent = 'Đang lưu...';

  try {
    const res = await fetch('../api/admin/orders.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ orderId, status, photoProof, note })
    });
    const json = await res.json();
    if (json.success) {
      closeUpdateOrderModal();
      loadAdminOrders();
      alert('Cập nhật trạng thái đơn thành công!');
    } else {
      alert('Lỗi: ' + json.message);
    }
  } catch (err) {
    alert('Lỗi kết nối máy chủ.');
  } finally {
    btn.disabled = false;
    btn.textContent = 'Lưu Thay Đổi';
  }
}

// ==========================================================================
// 3. PAYMENTS & RECONCILIATION MODULE
// ==========================================================================
async function loadPaymentsList() {
  const method = document.getElementById('payMethodFilter')?.value || 'all';
  const status = document.getElementById('payStatusFilter')?.value || 'all';
  const tbody = document.getElementById('paymentsTableBody');

  if (tbody) tbody.innerHTML = `<tr><td colspan="8" class="text-center py-10 text-stone-400">Đang đối soát dữ liệu giao dịch...</td></tr>`;

  try {
    const q = new URLSearchParams({ payment_method: method, payment_status: status });
    const res = await fetch(`../api/admin/payments.php?${q.toString()}`);
    if (res.status === 401) { window.location.href = 'login.php'; return; }
    const json = await res.json();

    if (json.success) {
      // Summary Cards
      const s = json.summary || {};
      document.getElementById('payTotalCollected').textContent = formatMoney(s.total_collected);
      document.getElementById('payPendingCod').textContent = formatMoney(s.pending_cod);
      document.getElementById('payTotalRefunded').textContent = formatMoney(s.total_refunded);
      document.getElementById('payCountPaid').textContent = `${s.count_paid || 0} giao dịch`;

      renderPaymentsTable(json.data.items || []);
    } else {
      if (tbody) tbody.innerHTML = `<tr><td colspan="8" class="text-center py-8 text-rose-500">${escapeHtml(json.message)}</td></tr>`;
    }
  } catch (err) {
    if (tbody) tbody.innerHTML = `<tr><td colspan="8" class="text-center py-8 text-rose-500">Lỗi kết nối máy chủ thanh toán.</td></tr>`;
  }
}

function renderPaymentsTable(items) {
  const tbody = document.getElementById('paymentsTableBody');
  if (!tbody) return;

  if (items.length === 0) {
    tbody.innerHTML = `<tr><td colspan="8" class="text-center py-12 text-stone-400">Không có giao dịch nào phù hợp bộ lọc.</td></tr>`;
    return;
  }

  tbody.innerHTML = items.map(tx => {
    const pInfo = PAYMENT_METHOD_MAP[tx.payment_method] || { text: tx.payment_method, icon: 'ph-credit-card' };
    const isPaid = tx.payment_status === 'paid';
    const isRefunded = tx.payment_status === 'refunded';

    return `
      <tr class="hover:bg-stone-50 transition">
        <td class="py-3 px-4 font-mono font-bold text-rose-600">#${escapeHtml(tx.order_code)}</td>
        <td class="py-3 px-4">
          <div class="font-bold text-stone-900">${escapeHtml(tx.sender_name)}</div>
          <div class="text-[11px] text-stone-400">${escapeHtml(tx.sender_phone)}</div>
        </td>
        <td class="py-3 px-4">
          <div class="flex items-center space-x-1.5 font-medium text-stone-800">
            <i class="ph ${pInfo.icon} text-rose-600 text-sm"></i>
            <span>${pInfo.text}</span>
          </div>
        </td>
        <td class="py-3 px-4 font-bold text-stone-900 font-mono text-sm">
          ${formatMoney(tx.total_amount)}
        </td>
        <td class="py-3 px-4">
          <span class="px-2.5 py-1 rounded-full text-[11px] font-bold ${isPaid ? 'bg-emerald-100 text-emerald-800' : (isRefunded ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800')}">
            ${isPaid ? '✓ Đã thu tiền' : (isRefunded ? '↩ Đã hoàn tiền' : '⏳ Chờ thu')}
          </span>
        </td>
        <td class="py-3 px-4 text-stone-600">
          ${escapeHtml(tx.order_status)}
        </td>
        <td class="py-3 px-4 text-stone-400 font-mono text-[11px]">
          ${escapeHtml(tx.created_at)}
        </td>
        <td class="py-3 px-4 text-right whitespace-nowrap space-x-1">
          ${!isPaid && !isRefunded ? `
            <button onclick="handleVerifyPayment(${tx.id})" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-bold text-xs shadow-sm transition">
              Xác Nhận Đã Thu
            </button>
          ` : ''}
          ${isPaid ? `
            <button onclick="handleRefundPayment(${tx.id})" class="px-3 py-1.5 bg-stone-100 hover:bg-rose-50 text-stone-600 hover:text-rose-600 rounded-lg font-semibold text-xs transition">
              Hoàn Tiền
            </button>
          ` : ''}
        </td>
      </tr>
    `;
  }).join('');
}

async function handleVerifyPayment(orderId) {
  if (!confirm('Xác nhận đã khớp số tiền thanh toán vào tài khoản cửa hàng?')) return;
  try {
    const res = await fetch('../api/admin/payments.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'verify_payment', orderId, note: 'Đối soát sổ phụ ngân hàng khớp' })
    });
    const json = await res.json();
    if (json.success) {
      alert(json.message);
      loadPaymentsList();
    } else {
      alert('Lỗi: ' + json.message);
    }
  } catch (err) {
    alert('Lỗi kết nối máy chủ.');
  }
}

async function handleRefundPayment(orderId) {
  const reason = prompt('Nhập lý do hoàn tiền cho đơn hàng này:');
  if (reason === null) return;
  try {
    const res = await fetch('../api/admin/payments.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'refund_payment', orderId, note: reason })
    });
    const json = await res.json();
    if (json.success) {
      alert(json.message);
      loadPaymentsList();
    } else {
      alert('Lỗi: ' + json.message);
    }
  } catch (err) {
    alert('Lỗi kết nối máy chủ.');
  }
}

// ==========================================================================
// 4. DELIVERY & QUALITY CHECK (QC) MODULE
// ==========================================================================
function switchDeliveryStage(stage) {
  deliveryStage = stage;
  ['btnStageAll', 'btnStageFlorist', 'btnStageQC', 'btnStageShipping'].forEach(id => {
    const btn = document.getElementById(id);
    if (btn) btn.className = 'px-3 py-1.5 rounded-xl font-semibold bg-stone-100 text-stone-600 hover:bg-stone-200';
  });

  const activeMap = {
    'all': 'btnStageAll',
    'florist': 'btnStageFlorist',
    'qc': 'btnStageQC',
    'shipping': 'btnStageShipping'
  };
  const activeBtn = document.getElementById(activeMap[stage]);
  if (activeBtn) activeBtn.className = 'px-3 py-1.5 rounded-xl font-bold bg-stone-900 text-white';

  loadDeliveryData();
}

async function loadDeliveryData() {
  const tbody = document.getElementById('deliveryTableBody');
  if (tbody) tbody.innerHTML = `<tr><td colspan="8" class="text-center py-10 text-stone-400">Đang tải lịch trình điều phối hoa...</td></tr>`;

  try {
    const res = await fetch(`../api/admin/delivery.php?stage=${deliveryStage}`);
    if (res.status === 401) { window.location.href = 'login.php'; return; }
    const json = await res.json();

    if (json.success) {
      currentDeliveryOrders = json.data.orders || [];
      availableStaffList = json.data.staff || [];
      renderDeliveryTable(currentDeliveryOrders);
    } else {
      if (tbody) tbody.innerHTML = `<tr><td colspan="8" class="text-center py-8 text-rose-500">${escapeHtml(json.message)}</td></tr>`;
    }
  } catch (err) {
    if (tbody) tbody.innerHTML = `<tr><td colspan="8" class="text-center py-8 text-rose-500">Lỗi kết nối điều phối.</td></tr>`;
  }
}

function renderDeliveryTable(orders) {
  const tbody = document.getElementById('deliveryTableBody');
  if (!tbody) return;

  if (orders.length === 0) {
    tbody.innerHTML = `<tr><td colspan="8" class="text-center py-12 text-stone-400">Không có đơn hàng nào trong phân đoạn này.</td></tr>`;
    return;
  }

  tbody.innerHTML = orders.map(o => {
    const hasQC = o.flower_freshness === 1 || o.flower_freshness === '1';

    return `
      <tr class="hover:bg-stone-50 transition">
        <td class="py-3 px-4 font-mono font-bold text-rose-600">
          #${escapeHtml(o.order_code)}
          <span class="block text-[10px] text-stone-400 font-normal uppercase">${escapeHtml(o.order_status)}</span>
        </td>
        <td class="py-3 px-4">
          <div class="font-bold text-stone-900">${escapeHtml(o.receiver_name)} (${escapeHtml(o.receiver_phone)})</div>
          <div class="text-[11px] text-stone-500 truncate max-w-xs" title="${escapeHtml(o.receiver_address)}">${escapeHtml(o.receiver_address)}</div>
        </td>
        <td class="py-3 px-4 font-mono text-[11px]">
          <span class="font-bold text-stone-800">${escapeHtml(o.delivery_date)}</span>
          <span class="block text-rose-600 font-semibold">${escapeHtml(o.delivery_time_slot)}</span>
        </td>
        <td class="py-3 px-4">
          <span class="font-medium text-stone-800">${escapeHtml(o.florist_name || 'Chưa phân công')}</span>
          <button onclick="promptAssignStaff(${o.id}, 'florist')" class="text-[10px] text-rose-600 block hover:underline">Đổi Florist</button>
        </td>
        <td class="py-3 px-4">
          <span class="font-medium text-stone-800">${escapeHtml(o.shipper_name || 'Chưa bàn giao')}</span>
          <button onclick="promptAssignStaff(${o.id}, 'shipper')" class="text-[10px] text-rose-600 block hover:underline">Gán Shipper</button>
        </td>
        <td class="py-3 px-4">
          ${hasQC ? `
            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 flex items-center space-x-1 w-max">
              <i class="ph ph-check-circle"></i>
              <span>Đạt chuẩn 5/5 QC</span>
            </span>
          ` : `
            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-amber-100 text-amber-800 w-max block">
              Chờ cắm & kiểm tra
            </span>
          `}
        </td>
        <td class="py-3 px-4 text-center">
          ${o.photo_proof ? `
            <img src="${escapeHtml(o.photo_proof)}" onclick="openAdminLightbox('${escapeHtml(o.photo_proof)}')" class="w-10 h-10 rounded-lg object-cover border border-stone-300 mx-auto cursor-pointer hover:opacity-80 shadow-sm" />
          ` : `<span class="text-stone-300 text-[11px]">Chưa có</span>`}
        </td>
        <td class="py-3 px-4 text-right whitespace-nowrap space-x-1">
          <button onclick="openQCModal(${o.id})" class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-lg font-bold text-xs shadow-sm transition">
            Kiểm Định QC
          </button>
          ${o.order_status === 'photo_ready' ? `
            <button onclick="handleStartDelivery(${o.id})" class="px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-bold text-xs shadow-sm transition">
              Xuất Giao
            </button>
          ` : ''}
          ${o.order_status === 'delivering' ? `
            <button onclick="handleCompleteDelivery(${o.id})" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-bold text-xs shadow-sm transition">
              Hoàn Tất Giao
            </button>
          ` : ''}
        </td>
      </tr>
    `;
  }).join('');
}

function openQCModal(orderId) {
  const o = currentDeliveryOrders.find(x => x.id == orderId);
  if (!o) return;

  document.getElementById('qcOrderId').value = o.id;
  document.getElementById('qcOrderCodeDisplay').textContent = `Đơn: #${o.order_code} • Khách: ${o.receiver_name} (${o.delivery_time_slot})`;
  document.getElementById('qc_photo_url').value = o.photo_proof || '';
  document.getElementById('qc_notes').value = o.qc_notes || 'Hoa nở chuẩn đẹp 80%, cành lá tươi xanh.';

  document.getElementById('qcModal').classList.remove('hidden');
}

function closeQCModal() {
  document.getElementById('qcModal').classList.add('hidden');
}

async function handleQCSubmit(e) {
  e.preventDefault();
  const orderId = document.getElementById('qcOrderId').value;
  const photo_proof_url = document.getElementById('qc_photo_url').value.trim();
  const notes = document.getElementById('qc_notes').value.trim();
  const flower_freshness = document.getElementById('qc_fresh').checked;
  const color_accuracy = document.getElementById('qc_color').checked;
  const stem_count_matched = document.getElementById('qc_stems').checked;
  const wrapping_standard = document.getElementById('qc_wrap').checked;
  const card_message_verified = document.getElementById('qc_card').checked;

  try {
    const res = await fetch('../api/admin/delivery.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        action: 'submit_qc',
        orderId,
        photo_proof_url,
        notes,
        flower_freshness,
        color_accuracy,
        stem_count_matched,
        wrapping_standard,
        card_message_verified
      })
    });
    const json = await res.json();
    if (json.success) {
      alert(json.message);
      closeQCModal();
      loadDeliveryData();
    } else {
      alert('Lỗi: ' + json.message);
    }
  } catch (err) {
    alert('Lỗi kết nối kiểm định QC.');
  }
}

async function handleStartDelivery(orderId) {
  if (!confirm('Xác nhận bàn giao hoa cho Shipper bắt đầu giao đến khách hàng?')) return;
  try {
    const res = await fetch('../api/admin/delivery.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'start_delivery', orderId })
    });
    const json = await res.json();
    if (json.success) {
      alert(json.message);
      loadDeliveryData();
    } else {
      alert('Lỗi: ' + json.message);
    }
  } catch (err) {
    alert('Lỗi kết nối.');
  }
}

async function handleCompleteDelivery(orderId) {
  if (!confirm('Xác nhận Shipper đã giao hoa thành công đến tay người nhận?')) return;
  try {
    const res = await fetch('../api/admin/delivery.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'complete_delivery', orderId })
    });
    const json = await res.json();
    if (json.success) {
      alert(json.message);
      loadDeliveryData();
    } else {
      alert('Lỗi: ' + json.message);
    }
  } catch (err) {
    alert('Lỗi kết nối.');
  }
}

function promptAssignStaff(orderId, type) {
  const staffRole = type === 'florist' ? 'florist' : 'shipper';
  const roleTitle = type === 'florist' ? 'Nghệ nhân Florist' : 'Nhân viên Shipper';
  const options = availableStaffList
    .filter(s => s.role_name === staffRole || s.role_name === 'staff')
    .map(s => `${s.id}: ${s.full_name} (${s.phone})`)
    .join('\n');

  const chosen = prompt(`Nhập ID ${roleTitle} phụ trách đơn này:\nDanh sách sẵn sàng:\n${options}`);
  if (!chosen) return;

  const staffId = parseInt(chosen.trim(), 10);
  if (isNaN(staffId)) { alert('ID không hợp lệ'); return; }

  const payload = { action: 'assign_staff', orderId };
  if (type === 'florist') payload.floristId = staffId;
  else payload.shipperId = staffId;

  fetch('../api/admin/delivery.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  }).then(r => r.json()).then(j => {
    if (j.success) {
      alert('Đã phân công thành công!');
      loadDeliveryData();
    } else {
      alert('Lỗi: ' + j.message);
    }
  });
}

// ==========================================================================
// 5. PRODUCTS MODULE
// ==========================================================================
async function loadCategoryDropdown() {
  try {
    const res = await fetch('../api/admin/categories.php');
    const json = await res.json();
    if (json.success) {
      currentCategories = json.data;
      const filterSel = document.getElementById('productCategoryFilter');
      const modalSel = document.getElementById('prodCategoryId');

      if (filterSel) {
        filterSel.innerHTML = `<option value="0">Tất cả danh mục</option>` +
          json.data.map(c => `<option value="${c.id}">${escapeHtml(c.name)}</option>`).join('');
      }

      if (modalSel) {
        modalSel.innerHTML = json.data.map(c => `<option value="${c.id}">${escapeHtml(c.name)}</option>`).join('');
      }
    }
  } catch (e) {}
}

async function loadAdminProducts() {
  const cid = document.getElementById('productCategoryFilter')?.value || '0';
  const status = document.getElementById('productStatusFilter')?.value || '';
  const stockStatus = document.getElementById('productStockFilter')?.value || '';
  const sort = document.getElementById('productSortFilter')?.value || 'id_desc';
  const search = document.getElementById('productSearchInput')?.value || '';
  const tbody = document.getElementById('productsTableBody');

  if (tbody) tbody.innerHTML = `<tr><td colspan="8" class="text-center py-10 text-stone-400">Đang tải sản phẩm hoa tươi...</td></tr>`;

  try {
    const q = new URLSearchParams({ category_id: cid, status, stock_status: stockStatus, sort, search, limit: 100 });
    const res = await fetch(`../api/admin/products.php?${q.toString()}`);
    if (res.status === 401) { window.location.href = 'login.php'; return; }
    const json = await res.json();

    if (json.success) {
      currentProducts = json.data.items || [];
      renderProductsTable(currentProducts);
    } else {
      if (tbody) tbody.innerHTML = `<tr><td colspan="8" class="text-center py-8 text-rose-500">${escapeHtml(json.message)}</td></tr>`;
    }
  } catch (err) {
    if (tbody) tbody.innerHTML = `<tr><td colspan="8" class="text-center py-8 text-rose-500">Lỗi kết nối sản phẩm.</td></tr>`;
  }
}

function renderProductsTable(products) {
  const tbody = document.getElementById('productsTableBody');
  if (!tbody) return;

  if (products.length === 0) {
    tbody.innerHTML = `<tr><td colspan="8" class="text-center py-12 text-stone-400">Không tìm thấy sản phẩm hoa nào.</td></tr>`;
    return;
  }

  tbody.innerHTML = products.map(p => {
    const margin = Number(p.profit_margin) || 0;
    const marginColor = margin >= 35 ? 'text-emerald-600' : (margin >= 20 ? 'text-amber-600' : 'text-rose-600');
    const isLowStock = Number(p.stock_quantity) <= 5;
    const formLabels = { bo: 'Bó hoa', gio: 'Giỏ hoa', hop: 'Hộp hoa', ke: 'Kệ hoa', lang: 'Lẵng hoa', chau: 'Chậu hoa' };
    const formText = formLabels[p.product_form] || p.product_form || '';

    return `
      <tr class="hover:bg-stone-50 transition">
        <td class="py-3 px-4">
          <div class="flex items-center space-x-3">
            <img src="${escapeHtml(p.thumbnail)}" onclick="openAdminLightbox('${escapeHtml(p.thumbnail)}')" alt="${escapeHtml(p.name)}" class="w-12 h-12 rounded-xl object-cover border border-stone-200 cursor-pointer shadow-sm" />
            <div>
              <h4 class="font-bold text-stone-900 text-xs">${escapeHtml(p.name)}</h4>
              <span class="text-[11px] text-stone-400 block">${escapeHtml(p.primary_flower || 'Hoa tuyển chọn')}${p.stem_count ? ` • ${p.stem_count} cành` : ''}${p.color_name ? ` • ${escapeHtml(p.color_name)}` : ''}</span>
              ${p.short_description ? `<p class="text-[10px] text-stone-500 line-clamp-1 italic max-w-xs">${escapeHtml(p.short_description)}</p>` : ''}
            </div>
          </div>
        </td>
        <td class="py-3 px-4">
          <span class="font-mono font-semibold text-stone-700 block">${escapeHtml(p.sku)}</span>
          <span class="text-[11px] text-stone-500 font-medium">${escapeHtml(p.categoryName || 'Hoa Tươi')}</span>
          ${formText ? `<span class="inline-block mt-0.5 px-1.5 py-0.2 bg-stone-100 text-stone-600 rounded text-[10px]">${formText}</span>` : ''}
        </td>
        <td class="py-3 px-4 font-bold text-stone-900 font-mono">
          ${formatMoney(p.price)}
          ${p.sale_price && p.sale_price > p.price ? `<span class="block text-[10px] text-rose-600 line-through">${formatMoney(p.sale_price)}</span>` : ''}
        </td>
        <td class="py-3 px-4 font-mono text-stone-600">
          ${formatMoney(p.cost_price)}
        </td>
        <td class="py-3 px-4 font-bold ${marginColor} font-mono">
          ${margin}%
        </td>
        <td class="py-3 px-4">
          <span class="font-bold font-mono ${isLowStock ? 'text-rose-600' : 'text-stone-800'}">${p.stock_quantity}</span>
          ${isLowStock ? `<span class="block text-[10px] text-rose-500 font-semibold">Sắp hết hàng!</span>` : ''}
        </td>
        <td class="py-3 px-4">
          <span class="px-2 py-0.5 rounded-full text-[10px] font-bold ${p.is_active == 1 ? 'bg-emerald-100 text-emerald-800' : 'bg-stone-100 text-stone-500'}">
            ${p.is_active == 1 ? 'Đang bán' : 'Tạm ẩn'}
          </span>
        </td>
        <td class="py-3 px-4 text-right whitespace-nowrap space-x-1">
          <button onclick="openProductModal(${p.id})" class="p-1.5 bg-stone-100 hover:bg-stone-200 text-stone-700 rounded-lg transition" title="Chỉnh sửa chi tiết">
            <i class="ph ph-pencil-simple text-sm"></i>
          </button>
          <button onclick="handleToggleProductActive(${p.id}, ${p.is_active})" class="p-1.5 ${p.is_active == 1 ? 'hover:bg-rose-50 text-rose-600' : 'hover:bg-emerald-50 text-emerald-600'} rounded-lg transition" title="${p.is_active == 1 ? 'Tạm ẩn mẫu này' : 'Mở bán lại'}">
            <i class="ph ${p.is_active == 1 ? 'ph-eye-slash' : 'ph-eye'} text-sm"></i>
          </button>
          <button onclick="handleDeleteProduct(${p.id}, '${escapeHtml(p.name).replace(/'/g, "\\'")}')" class="p-1.5 hover:bg-rose-100 text-rose-500 rounded-lg transition" title="Lưu trữ / Xóa">
            <i class="ph ph-trash text-sm"></i>
          </button>
        </td>
      </tr>
    `;
  }).join('');
}

function openProductModal(prodId = null) {
  const modal = document.getElementById('productModal');
  const title = document.getElementById('productModalTitle');

  if (prodId) {
    const p = currentProducts.find(x => x.id == prodId);
    if (!p) return;
    title.textContent = `Chỉnh Sửa Sản Phẩm: ${p.name}`;
    document.getElementById('prodEditId').value = p.id;
    document.getElementById('prodName').value = p.name || '';
    document.getElementById('prodSku').value = p.sku || '';
    document.getElementById('prodCategoryId').value = p.category_id || 1;
    document.getElementById('prodProductForm').value = p.product_form || 'bo';
    document.getElementById('prodPrimaryFlower').value = p.primary_flower || '';
    document.getElementById('prodSecondaryFlower').value = p.secondary_flower || '';
    document.getElementById('prodStemCount').value = (p.stem_count !== null && p.stem_count !== undefined) ? p.stem_count : '';
    document.getElementById('prodColor').value = p.color || 'do';
    document.getElementById('prodColorName').value = p.color_name || '';
    document.getElementById('prodPrice').value = p.price || 0;
    document.getElementById('prodCostPrice').value = p.cost_price || 0;
    document.getElementById('prodSalePrice').value = p.sale_price || '';
    document.getElementById('prodStockQty').value = p.stock_quantity || 0;
    document.getElementById('prodSize').value = p.size_dimensions || '';
    document.getElementById('prodWrappingPaper').value = p.wrapping_paper || '';
    document.getElementById('prodThumbnail').value = p.thumbnail || '';
    document.getElementById('prodShortDesc').value = p.short_description || '';
    document.getElementById('prodMeaning').value = p.meaning || '';
    document.getElementById('prodCareGuide').value = p.care_guide || '';
    document.getElementById('prodIsActive').checked = p.is_active == 1;
    document.getElementById('prodIsFeatured').checked = p.is_featured == 1;
  } else {
    title.textContent = 'Thêm Sản Phẩm Hoa Tươi Mới';
    document.getElementById('prodEditId').value = '';
    document.getElementById('prodName').value = '';
    document.getElementById('prodSku').value = 'FLW-' + Math.floor(1000 + Math.random() * 9000);
    document.getElementById('prodProductForm').value = 'bo';
    document.getElementById('prodPrimaryFlower').value = '';
    document.getElementById('prodSecondaryFlower').value = '';
    document.getElementById('prodStemCount').value = '';
    document.getElementById('prodColor').value = 'do';
    document.getElementById('prodColorName').value = '';
    document.getElementById('prodPrice').value = '';
    document.getElementById('prodCostPrice').value = '';
    document.getElementById('prodSalePrice').value = '';
    document.getElementById('prodStockQty').value = '50';
    document.getElementById('prodSize').value = 'Cao 55cm x Rộng 40cm';
    document.getElementById('prodWrappingPaper').value = 'Giấy xi măng vintage, nơ ruy băng lụa cao cấp';
    document.getElementById('prodThumbnail').value = 'https://images.unsplash.com/photo-1563245372-f21724e3856d?auto=format&fit=crop&w=800&q=80';
    document.getElementById('prodShortDesc').value = '';
    document.getElementById('prodMeaning').value = '';
    document.getElementById('prodCareGuide').value = 'Cắt vát gốc 45 độ, thay nước sạch mỗi ngày, tránh gió máy lạnh trực tiếp.';
    document.getElementById('prodIsActive').checked = true;
    document.getElementById('prodIsFeatured').checked = true;
  }

  modal.classList.remove('hidden');
}

function closeProductModal() {
  document.getElementById('productModal').classList.add('hidden');
}

async function handleProductSubmit(e) {
  e.preventDefault();
  const id = document.getElementById('prodEditId').value;
  const isEdit = id !== '';
  const payload = {
    name: document.getElementById('prodName').value.trim(),
    sku: document.getElementById('prodSku').value.trim(),
    category_id: parseInt(document.getElementById('prodCategoryId').value, 10),
    product_form: document.getElementById('prodProductForm').value,
    primary_flower: document.getElementById('prodPrimaryFlower').value.trim(),
    secondary_flower: document.getElementById('prodSecondaryFlower').value.trim(),
    stem_count: document.getElementById('prodStemCount').value !== '' ? parseInt(document.getElementById('prodStemCount').value, 10) : null,
    color: document.getElementById('prodColor').value,
    color_name: document.getElementById('prodColorName').value.trim(),
    price: parseFloat(document.getElementById('prodPrice').value),
    cost_price: parseFloat(document.getElementById('prodCostPrice').value || 0),
    sale_price: document.getElementById('prodSalePrice').value ? parseFloat(document.getElementById('prodSalePrice').value) : null,
    stock_quantity: parseInt(document.getElementById('prodStockQty').value, 10),
    size_dimensions: document.getElementById('prodSize').value.trim(),
    wrapping_paper: document.getElementById('prodWrappingPaper').value.trim(),
    thumbnail: document.getElementById('prodThumbnail').value.trim(),
    short_description: document.getElementById('prodShortDesc').value.trim(),
    meaning: document.getElementById('prodMeaning').value.trim(),
    care_guide: document.getElementById('prodCareGuide').value.trim(),
    is_active: document.getElementById('prodIsActive').checked ? 1 : 0,
    is_featured: document.getElementById('prodIsFeatured').checked ? 1 : 0,
  };

  const btn = document.getElementById('prodSaveBtn');
  btn.disabled = true;
  btn.textContent = 'Đang lưu...';

  try {
    const url = isEdit ? `../api/admin/products.php?id=${id}` : '../api/admin/products.php';
    const method = isEdit ? 'PUT' : 'POST';
    const res = await fetch(url, {
      method,
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const json = await res.json();
    if (json.success) {
      alert(json.message);
      closeProductModal();
      loadAdminProducts();
    } else {
      alert('Lỗi: ' + json.message);
    }
  } catch (err) {
    alert('Lỗi kết nối máy chủ.');
  } finally {
    btn.disabled = false;
    btn.textContent = 'Lưu Sản Phẩm';
  }
}

async function handleToggleProductActive(id, currentActive) {
  const newActive = currentActive == 1 ? 0 : 1;
  try {
    const res = await fetch(`../api/admin/products.php?id=${id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ is_active: newActive })
    });
    const json = await res.json();
    if (json.success) {
      loadAdminProducts();
    }
  } catch (e) {}
}

async function handleDeleteProduct(id, name) {
  if (!confirm(`Bạn có chắc muốn lưu trữ hoặc xóa mẫu hoa "${name}"?`)) return;
  try {
    const res = await fetch(`../api/admin/products.php?id=${id}`, {
      method: 'DELETE'
    });
    const json = await res.json();
    if (json.success) {
      alert(json.message);
      loadAdminProducts();
    } else {
      alert('Lỗi: ' + json.message);
    }
  } catch (err) {
    alert('Lỗi kết nối máy chủ.');
  }
}

// ==========================================================================
// 6. CATEGORIES MODULE (Full CRUD, View Detail, Hide/Show, Safe Delete)
// ==========================================================================
let currentCategoriesPage = 1;
let allCategoriesCache = [];

async function loadAdminCategories(page = 1) {
  currentCategoriesPage = page;
  const search = document.getElementById('catSearchInput')?.value.trim() || '';
  const status = document.getElementById('catStatusFilter')?.value || 'all';
  const sort = document.getElementById('catSortFilter')?.value || 'order_asc';
  const tbody = document.getElementById('categoriesTableBody');
  if (tbody) tbody.innerHTML = `<tr><td colspan="8" class="text-center py-10 text-stone-400">Đang tải danh mục sản phẩm...</td></tr>`;

  try {
    const q = new URLSearchParams({ search, status, sort, page, limit: 15 });
    const [res, allRes] = await Promise.all([
      fetch(`../api/admin/categories.php?${q.toString()}`),
      fetch('../api/admin/categories.php?all=1')
    ]);
    if (res.status === 401) { window.location.href = 'login.php'; return; }
    const json = await res.json();
    const allJson = await allRes.json().catch(() => ({ success: false }));
    if (allJson.success) {
      allCategoriesCache = allJson.data || [];
    }

    if (json.success) {
      currentCategories = json.data || [];
      if (json.stats) {
        const stTot = document.getElementById('catStatTotal');
        const stAct = document.getElementById('catStatActive');
        const stHid = document.getElementById('catStatHidden');
        if (stTot) stTot.textContent = json.stats.total ?? currentCategories.length;
        if (stAct) stAct.textContent = json.stats.active ?? 0;
        if (stHid) stHid.textContent = json.stats.hidden ?? 0;
      }
      renderCategoriesTable(currentCategories);
      renderCategoriesPagination(json.pagination);
    } else {
      if (tbody) tbody.innerHTML = `<tr><td colspan="8" class="text-center py-8 text-rose-500">${escapeHtml(json.message)}</td></tr>`;
    }
  } catch (err) {
    if (tbody) tbody.innerHTML = `<tr><td colspan="8" class="text-center py-8 text-rose-500">Lỗi kết nối danh mục.</td></tr>`;
  }
}

function renderCategoriesTable(categories) {
  const tbody = document.getElementById('categoriesTableBody');
  if (!tbody) return;

  if (categories.length === 0) {
    tbody.innerHTML = `<tr><td colspan="8" class="text-center py-12 text-stone-400">Không tìm thấy danh mục nào phù hợp.</td></tr>`;
    return;
  }

  tbody.innerHTML = categories.map(c => {
    const isVisible = c.status === 'active' && Number(c.is_active) === 1;
    const imgHtml = c.image
      ? `<img src="${escapeHtml(c.image)}" alt="${escapeHtml(c.name)}" onclick="openAdminLightbox('${escapeHtml(c.image)}')" class="w-11 h-11 rounded-xl object-cover border border-stone-200 cursor-pointer shadow-xs" />`
      : `<div class="w-11 h-11 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-lg border border-rose-100"><i class="ph ${escapeHtml(c.icon || 'ph-flower-lotus')}"></i></div>`;

    return `
      <tr class="hover:bg-stone-50 transition ${!isVisible ? 'opacity-75 bg-stone-50/40' : ''}">
        <td class="py-3 px-4">${imgHtml}</td>
        <td class="py-3 px-4 max-w-xs">
          <div class="flex items-center flex-wrap gap-1.5">
            <button onclick="openCategoryDetailModal(${c.id})" class="font-bold text-stone-900 hover:text-rose-600 transition text-left">${escapeHtml(c.name)}</button>
            ${c.badge ? `<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">${escapeHtml(c.badge)}</span>` : ''}
          </div>
          <div class="text-[11px] text-stone-400 font-mono truncate">${escapeHtml(c.slug)}</div>
          ${c.parent_name ? `<div class="text-[10px] text-purple-600 font-medium mt-0.5"><i class="ph ph-git-branch mr-0.5"></i>Thuộc: ${escapeHtml(c.parent_name)}</div>` : ''}
        </td>
        <td class="py-3 px-4 font-mono font-semibold text-stone-700">${escapeHtml(c.code || ('DM' + String(c.id).padStart(3, '0')))}</td>
        <td class="py-3 px-4">
          <button onclick="openCategoryDetailModal(${c.id})" class="font-bold font-mono text-stone-900 hover:text-rose-600 underline decoration-dotted">
            ${c.product_count || 0} sản phẩm
          </button>
          <span class="block text-[10px] text-stone-400">${c.active_product_count ?? c.product_count ?? 0} đang bán</span>
        </td>
        <td class="py-3 px-4 text-center font-mono text-stone-600 font-semibold">${c.display_order}</td>
        <td class="py-3 px-4">
          <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold ${isVisible ? 'bg-emerald-100 text-emerald-800' : 'bg-stone-200 text-stone-600'}">
            ${isVisible ? 'Hiển thị' : 'Đang ẩn'}
          </span>
        </td>
        <td class="py-3 px-4 text-[11px] font-mono text-stone-500 whitespace-nowrap">${escapeHtml(formatDateVN(c.created_at))}</td>
        <td class="py-3 px-4 text-right whitespace-nowrap space-x-1">
          <button onclick="openCategoryDetailModal(${c.id})" class="p-1.5 bg-stone-100 hover:bg-stone-200 text-stone-700 rounded-lg transition" title="Xem chi tiết">
            <i class="ph ph-eye text-sm"></i>
          </button>
          <button onclick="openCategoryModal(${c.id})" class="p-1.5 bg-stone-100 hover:bg-stone-200 text-stone-700 rounded-lg transition" title="Chỉnh sửa">
            <i class="ph ph-pencil-simple text-sm"></i>
          </button>
          <button onclick="handleToggleCategoryVisibility(${c.id}, '${isVisible ? 'active' : 'hidden'}')" class="p-1.5 ${isVisible ? 'bg-amber-50 hover:bg-amber-100 text-amber-700' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700'} rounded-lg transition" title="${isVisible ? 'Ẩn danh mục khỏi Client' : 'Hiện lại danh mục'}">
            <i class="ph ${isVisible ? 'ph-eye-slash' : 'ph-eye'} text-sm"></i>
          </button>
          <button onclick="openCategoryDeleteModal(${c.id})" class="p-1.5 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-lg transition" title="Xóa danh mục">
            <i class="ph ph-trash text-sm"></i>
          </button>
        </td>
      </tr>
    `;
  }).join('');
}

function renderCategoriesPagination(pagination) {
  const wrap = document.getElementById('categoriesPagination');
  if (!wrap || !pagination) return;
  const { total, page, total_pages } = pagination;
  wrap.innerHTML = `
    <span>Hiển thị trang <strong>${page}</strong> / ${total_pages} (Tổng <strong>${total}</strong> danh mục)</span>
    <div class="flex items-center gap-1.5">
      <button ${page <= 1 ? 'disabled' : ''} onclick="loadAdminCategories(${page - 1})" class="px-3 py-1 rounded-lg border border-stone-200 bg-white hover:bg-stone-100 disabled:opacity-40">Trước</button>
      <button ${page >= total_pages ? 'disabled' : ''} onclick="loadAdminCategories(${page + 1})" class="px-3 py-1 rounded-lg border border-stone-200 bg-white hover:bg-stone-100 disabled:opacity-40">Sau</button>
    </div>
  `;
}

function populateCategoryParentSelect(excludeId = null, selectedParentId = null) {
  const sel = document.getElementById('catParentId');
  if (!sel) return;
  const list = (allCategoriesCache.length ? allCategoriesCache : currentCategories).filter(c => c.id != excludeId);
  sel.innerHTML = `<option value="">-- Danh mục gốc (Không có cha) --</option>` +
    list.map(c => `<option value="${c.id}" ${selectedParentId == c.id ? 'selected' : ''}>${escapeHtml(c.name)} (${escapeHtml(c.code || '')})</option>`).join('');
}

function onCategoryNameInput() {
  const slugInput = document.getElementById('catSlug');
  const nameVal = document.getElementById('catName')?.value || '';
  if (slugInput && slugInput.dataset.manual !== '1') {
    slugInput.value = slugifyVietnamese(nameVal);
  }
}

function previewCategoryImage(url) {
  const img = document.getElementById('catImagePreview');
  if (!img) return;
  if (url && url.trim()) {
    img.src = url.trim();
    img.classList.remove('hidden');
  } else {
    img.src = '';
    img.classList.add('hidden');
  }
}

function openCategoryModal(catId = null) {
  const modal = document.getElementById('categoryModal');
  const title = document.getElementById('catModalTitle');
  const slugEl = document.getElementById('catSlug');
  showFormAlert('catFormAlert', '');

  if (catId) {
    const c = (allCategoriesCache.find(x => x.id == catId) || currentCategories.find(x => x.id == catId));
    if (!c) return;
    title.textContent = `Chỉnh Sửa Danh Mục: ${c.name}`;
    document.getElementById('catEditId').value = c.id;
    document.getElementById('catName').value = c.name || '';
    document.getElementById('catCode').value = c.code || '';
    slugEl.value = c.slug || '';
    slugEl.dataset.manual = '1';
    populateCategoryParentSelect(c.id, c.parent_id);
    document.getElementById('catDisplayOrder').value = c.display_order ?? 0;
    document.getElementById('catStatus').value = (c.status === 'hidden' || Number(c.is_active) === 0) ? 'hidden' : 'active';
    document.getElementById('catIcon').value = c.icon || 'ph-flower-lotus';
    document.getElementById('catBadge').value = c.badge || '';
    document.getElementById('catImage').value = c.image || '';
    document.getElementById('catDescription').value = c.description || '';
    previewCategoryImage(c.image || '');
  } else {
    title.textContent = 'Thêm Danh Mục Mới';
    document.getElementById('catEditId').value = '';
    document.getElementById('catName').value = '';
    document.getElementById('catCode').value = '';
    slugEl.value = '';
    slugEl.dataset.manual = '0';
    populateCategoryParentSelect(null, null);
    document.getElementById('catDisplayOrder').value = '0';
    document.getElementById('catStatus').value = 'active';
    document.getElementById('catIcon').value = 'ph-flower-lotus';
    document.getElementById('catBadge').value = '';
    document.getElementById('catImage').value = '';
    document.getElementById('catDescription').value = '';
    previewCategoryImage('');
  }

  modal.classList.remove('hidden');
}

function closeCategoryModal() {
  document.getElementById('categoryModal').classList.add('hidden');
}

async function handleCategorySubmit(e) {
  e.preventDefault();
  const id = document.getElementById('catEditId').value;
  const isEdit = id !== '';
  const name = document.getElementById('catName').value.trim();
  const slug = document.getElementById('catSlug').value.trim();

  if (!name) {
    showFormAlert('catFormAlert', 'Vui lòng nhập Tên danh mục.');
    return;
  }
  if (!slug) {
    showFormAlert('catFormAlert', 'Slug đường dẫn SEO không được để trống.');
    return;
  }

  const payload = {
    name,
    code: document.getElementById('catCode').value.trim(),
    slug,
    parent_id: document.getElementById('catParentId').value ? parseInt(document.getElementById('catParentId').value, 10) : null,
    display_order: parseInt(document.getElementById('catDisplayOrder').value || '0', 10),
    status: document.getElementById('catStatus').value,
    icon: document.getElementById('catIcon').value.trim() || 'ph-flower-lotus',
    badge: document.getElementById('catBadge').value.trim(),
    image: document.getElementById('catImage').value.trim(),
    description: document.getElementById('catDescription').value.trim(),
  };

  const btn = document.getElementById('catSaveBtn');
  btn.disabled = true;
  btn.textContent = 'Đang lưu...';
  showFormAlert('catFormAlert', '');

  try {
    const url = isEdit ? `../api/admin/categories.php?id=${id}` : '../api/admin/categories.php';
    const method = isEdit ? 'PUT' : 'POST';
    const res = await fetch(url, {
      method,
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const json = await res.json();
    if (json.success) {
      showAdminToast(json.message || 'Lưu danh mục thành công!', 'success');
      closeCategoryModal();
      loadAdminCategories(currentCategoriesPage);
      loadCategoryDropdown();
    } else {
      showFormAlert('catFormAlert', json.message || 'Không thể lưu danh mục.');
    }
  } catch (err) {
    showFormAlert('catFormAlert', 'Lỗi kết nối máy chủ khi lưu danh mục.');
  } finally {
    btn.disabled = false;
    btn.textContent = 'Lưu Danh Mục';
  }
}

async function openCategoryDetailModal(catId) {
  const modal = document.getElementById('categoryDetailModal');
  const body = document.getElementById('categoryDetailBody');
  const sub = document.getElementById('catDetailSubtitle');
  sub.textContent = 'Đang tải...';
  body.innerHTML = `<div class="py-10 text-center text-stone-400">Đang tải thông tin chi tiết danh mục...</div>`;
  modal.classList.remove('hidden');

  try {
    const res = await fetch(`../api/admin/categories.php?id=${catId}`);
    const json = await res.json();
    if (!json.success || !json.data) {
      body.innerHTML = `<div class="py-6 text-center text-rose-600">${escapeHtml(json.message || 'Không tìm thấy danh mục.')}</div>`;
      return;
    }

    const c = json.data;
    const isVisible = c.status === 'active' && Number(c.is_active) === 1;
    sub.textContent = `Mã: ${c.code || '--'} • Slug: ${c.slug}`;

    const prodsHtml = (c.products && c.products.length > 0)
      ? `<table class="w-full text-left text-xs">
          <thead class="bg-stone-50 text-[11px] text-stone-400 uppercase border-b border-stone-200">
            <tr>
              <th class="py-2 px-3">Sản Phẩm</th>
              <th class="py-2 px-3">SKU</th>
              <th class="py-2 px-3">Giá Bán</th>
              <th class="py-2 px-3 text-center">Tồn Kho</th>
              <th class="py-2 px-3">Trạng Thái</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-stone-100">
            ${c.products.map(p => `
              <tr>
                <td class="py-2 px-3 flex items-center gap-2">
                  <img src="${escapeHtml(p.thumbnail)}" class="w-8 h-8 rounded-lg object-cover border border-stone-200" />
                  <span class="font-semibold text-stone-800">${escapeHtml(p.name)}</span>
                </td>
                <td class="py-2 px-3 font-mono text-stone-600">${escapeHtml(p.sku)}</td>
                <td class="py-2 px-3 font-mono font-bold text-rose-600">${formatMoney(p.price)}</td>
                <td class="py-2 px-3 text-center font-mono">${p.stock_quantity}</td>
                <td class="py-2 px-3">
                  <span class="px-2 py-0.5 rounded-full text-[10px] font-bold ${Number(p.is_active) === 1 ? 'bg-emerald-100 text-emerald-700' : 'bg-stone-100 text-stone-500'}">
                    ${Number(p.is_active) === 1 ? 'Đang bán' : 'Tạm ẩn'}
                  </span>
                </td>
              </tr>
            `).join('')}
          </tbody>
        </table>`
      : `<div class="py-6 text-center text-stone-400 italic">Danh mục này chưa có sản phẩm nào.</div>`;

    body.innerHTML = `
      <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 p-4 rounded-2xl bg-stone-50 border border-stone-200">
        <div class="sm:col-span-1">
          ${c.image
            ? `<img src="${escapeHtml(c.image)}" alt="${escapeHtml(c.name)}" class="w-full h-32 rounded-2xl object-cover border border-stone-200" />`
            : `<div class="w-full h-32 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-3xl border border-rose-100"><i class="ph ${escapeHtml(c.icon || 'ph-flower-lotus')}"></i></div>`
          }
        </div>
        <div class="sm:col-span-3 space-y-2">
          <div class="flex items-center justify-between flex-wrap gap-2">
            <h4 class="text-base font-bold text-stone-900 font-serif-title">${escapeHtml(c.name)}</h4>
            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold ${isVisible ? 'bg-emerald-100 text-emerald-800' : 'bg-stone-200 text-stone-600'}">
              ${isVisible ? 'Hiển thị trên web' : 'Đang ẩn'}
            </span>
          </div>
          <p class="text-stone-600 leading-relaxed">${escapeHtml(c.description || 'Chưa có mô tả cho danh mục này.')}</p>
          <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 pt-1 text-[11px] text-stone-500">
            <div>Mã danh mục: <strong class="font-mono text-stone-800">${escapeHtml(c.code || '--')}</strong></div>
            <div>Slug: <strong class="font-mono text-stone-800">${escapeHtml(c.slug)}</strong></div>
            <div>Danh mục cha: <strong class="text-stone-800">${escapeHtml(c.parent_name || 'Danh mục gốc')}</strong></div>
            <div>Thứ tự hiển thị: <strong class="font-mono text-stone-800">${c.display_order}</strong></div>
            <div>Ngày tạo: <strong class="font-mono text-stone-800">${escapeHtml(formatDateVN(c.created_at))}</strong></div>
            <div>Cập nhật: <strong class="font-mono text-stone-800">${escapeHtml(formatDateVN(c.updated_at))}</strong></div>
          </div>
        </div>
      </div>

      ${c.children && c.children.length > 0 ? `
        <div class="p-3 rounded-2xl bg-purple-50/70 border border-purple-100">
          <span class="font-bold text-purple-900 block mb-1.5">Danh Mục Con (${c.children.length}):</span>
          <div class="flex flex-wrap gap-1.5">
            ${c.children.map(ch => `<span class="px-2.5 py-1 bg-white border border-purple-200 rounded-lg text-[11px] font-semibold text-purple-800">${escapeHtml(ch.name)}</span>`).join('')}
          </div>
        </div>
      ` : ''}

      <div class="border border-stone-200 rounded-2xl overflow-hidden">
        <div class="px-4 py-3 bg-stone-50 border-b border-stone-200 flex items-center justify-between">
          <span class="font-bold text-stone-800">Danh Sách Sản Phẩm Thuộc Danh Mục (${c.product_count} sản phẩm)</span>
        </div>
        <div class="max-h-72 overflow-y-auto">${prodsHtml}</div>
      </div>

      <div class="flex items-center justify-end gap-2 pt-2">
        <button onclick="closeCategoryDetailModal(); openCategoryModal(${c.id});" class="px-4 py-2 bg-stone-900 hover:bg-black text-white rounded-xl font-bold transition">Chỉnh Sửa Danh Mục</button>
      </div>
    `;
  } catch (err) {
    body.innerHTML = `<div class="py-6 text-center text-rose-600">Lỗi tải chi tiết danh mục.</div>`;
  }
}

function closeCategoryDetailModal() {
  document.getElementById('categoryDetailModal').classList.add('hidden');
}

async function handleToggleCategoryVisibility(id, currentStatus) {
  const newStatus = currentStatus === 'active' ? 'hidden' : 'active';
  const msg = newStatus === 'hidden'
    ? 'Ẩn danh mục này khỏi trang khách hàng? (Các sản phẩm liên kết vẫn giữ nguyên)'
    : 'Bật hiển thị lại danh mục này trên website?';
  if (!confirm(msg)) return;

  try {
    const res = await fetch(`../api/admin/categories.php?id=${id}`, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ status: newStatus })
    });
    const json = await res.json();
    if (json.success) {
      showAdminToast(json.message, 'success');
      loadAdminCategories(currentCategoriesPage);
      loadCategoryDropdown();
    } else {
      showAdminToast(json.message || 'Không thể đổi trạng thái danh mục.', 'error');
    }
  } catch (err) {
    showAdminToast('Lỗi kết nối máy chủ.', 'error');
  }
}

function openCategoryDeleteModal(catId) {
  const c = currentCategories.find(x => x.id == catId) || allCategoriesCache.find(x => x.id == catId);
  if (!c) return;
  const modal = document.getElementById('categoryDeleteModal');
  const title = document.getElementById('catDeleteTitle');
  const body = document.getElementById('catDeleteBody');

  title.textContent = `${c.name} (${c.code || '#' + c.id})`;
  const count = Number(c.product_count) || 0;
  const others = (allCategoriesCache.length ? allCategoriesCache : currentCategories).filter(x => x.id != c.id);

  if (count > 0) {
    body.innerHTML = `
      <div class="p-3.5 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 space-y-1.5">
        <div class="font-bold flex items-center gap-1.5"><i class="ph ph-warning-circle text-base text-amber-600"></i> Không thể xóa trực tiếp</div>
        <p>Danh mục <strong>"${escapeHtml(c.name)}"</strong> hiện đang có <strong>${count} sản phẩm</strong> liên kết. Hệ thống không cho phép xóa dây chuyền để bảo toàn dữ liệu sản phẩm và đơn hàng.</p>
      </div>

      ${others.length > 0 ? `
        <div class="space-y-2">
          <label class="block font-semibold text-stone-700">Chuyển ${count} sản phẩm sang danh mục khác trước khi xóa:</label>
          <select id="catDeleteReassignTarget" class="w-full bg-stone-50 border border-stone-300 rounded-xl px-3 py-2 text-stone-800 font-medium focus:outline-none">
            ${others.map(o => `<option value="${o.id}">${escapeHtml(o.name)} (${escapeHtml(o.code || '')})</option>`).join('')}
          </select>
        </div>
      ` : ''}

      <div class="flex flex-wrap items-center justify-end gap-2 pt-2 border-t border-stone-100">
        <button type="button" onclick="closeCategoryDeleteModal()" class="px-3.5 py-2 bg-stone-100 hover:bg-stone-200 text-stone-600 rounded-xl font-medium">Hủy</button>
        <button type="button" onclick="closeCategoryDeleteModal(); handleToggleCategoryVisibility(${c.id}, 'active');" class="px-3.5 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-xl font-bold">Ẩn Thay Vì Xóa</button>
        ${others.length > 0 ? `<button type="button" onclick="confirmCategoryDelete(${c.id}, true)" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl font-bold">Chuyển SP & Xóa</button>` : ''}
      </div>
    `;
  } else {
    body.innerHTML = `
      <p class="text-stone-600 leading-relaxed">
        Danh mục <strong>"${escapeHtml(c.name)}"</strong> hiện không có sản phẩm liên kết. Bạn có chắc chắn muốn xóa vĩnh viễn danh mục này?
      </p>
      <div class="flex items-center justify-end gap-2 pt-2 border-t border-stone-100">
        <button type="button" onclick="closeCategoryDeleteModal()" class="px-4 py-2 bg-stone-100 hover:bg-stone-200 text-stone-600 rounded-xl font-medium">Hủy</button>
        <button type="button" onclick="confirmCategoryDelete(${c.id}, false)" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl font-bold">Xác Nhận Xóa</button>
      </div>
    `;
  }

  modal.classList.remove('hidden');
}

function closeCategoryDeleteModal() {
  document.getElementById('categoryDeleteModal').classList.add('hidden');
}

async function confirmCategoryDelete(catId, withReassign) {
  let url = `../api/admin/categories.php?id=${catId}`;
  if (withReassign) {
    const targetId = document.getElementById('catDeleteReassignTarget')?.value;
    if (!targetId) {
      showAdminToast('Vui lòng chọn danh mục đích.', 'warning');
      return;
    }
    url += `&reassign_to_id=${encodeURIComponent(targetId)}`;
  }

  try {
    const res = await fetch(url, { method: 'DELETE' });
    const json = await res.json();
    if (json.success) {
      showAdminToast(json.message, 'success');
      closeCategoryDeleteModal();
      loadAdminCategories(currentCategoriesPage);
      loadCategoryDropdown();
    } else {
      showAdminToast(json.message || 'Không thể xóa danh mục.', 'error');
    }
  } catch (err) {
    showAdminToast('Lỗi kết nối khi xóa danh mục.', 'error');
  }
}

// ==========================================================================
// 7. INVENTORY & MATERIALS MODULE
// ==========================================================================
function switchInventorySubTab(tab) {
  const btnMat = document.getElementById('btnInvMaterials');
  const btnProd = document.getElementById('btnInvProducts');
  const wrapMat = document.getElementById('materialsWrap');
  const wrapProd = document.getElementById('productInventoryWrap');

  if (tab === 'materials') {
    btnMat.className = 'px-4 py-2 rounded-xl text-xs font-bold bg-rose-600 text-white shadow-sm flex items-center space-x-1.5';
    btnProd.className = 'px-4 py-2 rounded-xl text-xs font-bold bg-white text-stone-600 hover:text-stone-900 border border-stone-200 flex items-center space-x-1.5';
    wrapMat.classList.remove('hidden');
    wrapProd.classList.add('hidden');
    loadMaterialsList();
  } else {
    btnProd.className = 'px-4 py-2 rounded-xl text-xs font-bold bg-rose-600 text-white shadow-sm flex items-center space-x-1.5';
    btnMat.className = 'px-4 py-2 rounded-xl text-xs font-bold bg-white text-stone-600 hover:text-stone-900 border border-stone-200 flex items-center space-x-1.5';
    wrapMat.classList.add('hidden');
    wrapProd.classList.remove('hidden');
    loadProductInventory();
  }
}

async function loadMaterialsList() {
  const cat = document.getElementById('matCategoryFilter')?.value || 'all';
  const tbody = document.getElementById('materialsTableBody');

  if (tbody) tbody.innerHTML = `<tr><td colspan="9" class="text-center py-10 text-stone-400">Đang kiểm kê nguyên phụ liệu...</td></tr>`;

  try {
    const q = new URLSearchParams({ category: cat });
    const res = await fetch(`../api/admin/materials.php?${q.toString()}`);
    if (res.status === 401) { window.location.href = 'login.php'; return; }
    const json = await res.json();

    if (json.success) {
      currentMaterials = json.data || [];
      const s = json.summary || {};
      document.getElementById('matTotalItems').textContent = `${s.total_items || 0} mặt hàng`;
      document.getElementById('matLowStockCount').textContent = `${s.low_stock_count || 0} cảnh báo`;
      document.getElementById('matTotalValuation').textContent = formatMoney(s.total_valuation);

      renderMaterialsTable(currentMaterials);
    } else {
      if (tbody) tbody.innerHTML = `<tr><td colspan="9" class="text-center py-8 text-rose-500">${escapeHtml(json.message)}</td></tr>`;
    }
  } catch (err) {
    if (tbody) tbody.innerHTML = `<tr><td colspan="9" class="text-center py-8 text-rose-500">Lỗi kết nối nguyên vật liệu.</td></tr>`;
  }
}

function renderMaterialsTable(materials) {
  const tbody = document.getElementById('materialsTableBody');
  if (!tbody) return;

  if (materials.length === 0) {
    tbody.innerHTML = `<tr><td colspan="9" class="text-center py-12 text-stone-400">Chưa có nguyên vật liệu nào trong nhóm này.</td></tr>`;
    return;
  }

  tbody.innerHTML = materials.map(m => {
    const isLow = m.status === 'low_stock' || m.status === 'out_of_stock';
    return `
      <tr class="hover:bg-stone-50 transition">
        <td class="py-3 px-4 font-mono font-bold text-stone-700">${escapeHtml(m.sku)}</td>
        <td class="py-3 px-4 font-semibold text-stone-900">${escapeHtml(m.name)}</td>
        <td class="py-3 px-4 text-stone-600">${escapeHtml(m.category)}</td>
        <td class="py-3 px-4 font-medium text-stone-800">${escapeHtml(m.unit)}</td>
        <td class="py-3 px-4 font-mono font-bold ${isLow ? 'text-rose-600' : 'text-stone-900'}">
          ${m.stock_quantity}
          <span class="block text-[10px] text-stone-400 font-normal">Cảnh báo khi &le; ${m.min_stock_alert}</span>
        </td>
        <td class="py-3 px-4 font-mono text-stone-800">${formatMoney(m.cost_per_unit)}</td>
        <td class="py-3 px-4 text-stone-500">${escapeHtml(m.supplier || 'Nội địa')}</td>
        <td class="py-3 px-4">
          <span class="px-2 py-0.5 rounded-full text-[10px] font-bold ${m.status === 'in_stock' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'}">
            ${m.status === 'in_stock' ? 'Đủ hàng' : (m.status === 'low_stock' ? 'Sắp hết' : 'Hết hàng')}
          </span>
        </td>
        <td class="py-3 px-4 text-right whitespace-nowrap space-x-1">
          <button onclick="openMaterialAdjustModal(${m.id})" class="px-2.5 py-1.5 bg-stone-900 hover:bg-black text-white rounded-lg text-xs font-semibold shadow-sm transition">
            Điều Chỉnh
          </button>
        </td>
      </tr>
    `;
  }).join('');
}

function openMaterialModal() {
  document.getElementById('matModalTitle').textContent = 'Thêm Nguyên Vật Liệu Hoa Mới';
  document.getElementById('matEditId').value = '';
  document.getElementById('matName').value = '';
  document.getElementById('matSku').value = 'MAT-' + Math.floor(1000 + Math.random() * 9000);
  document.getElementById('matUnit').value = 'Cành';
  document.getElementById('matQty').value = '50';
  document.getElementById('matMinAlert').value = '10';
  document.getElementById('matCost').value = '15000';
  document.getElementById('matSupplier').value = '';
  document.getElementById('materialModal').classList.remove('hidden');
}

function closeMaterialModal() {
  document.getElementById('materialModal').classList.add('hidden');
}

async function handleMaterialSubmit(e) {
  e.preventDefault();
  const payload = {
    name: document.getElementById('matName').value.trim(),
    sku: document.getElementById('matSku').value.trim(),
    category: document.getElementById('matCategory').value,
    unit: document.getElementById('matUnit').value.trim(),
    stock_quantity: parseInt(document.getElementById('matQty').value, 10),
    min_stock_alert: parseInt(document.getElementById('matMinAlert').value, 10),
    cost_per_unit: parseFloat(document.getElementById('matCost').value),
    supplier: document.getElementById('matSupplier').value.trim(),
  };

  try {
    const res = await fetch('../api/admin/materials.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const json = await res.json();
    if (json.success) {
      alert(json.message);
      closeMaterialModal();
      loadMaterialsList();
    } else {
      alert('Lỗi: ' + json.message);
    }
  } catch (err) {
    alert('Lỗi kết nối máy chủ.');
  }
}

function openMaterialAdjustModal(matId) {
  const m = currentMaterials.find(x => x.id == matId);
  if (!m) return;

  document.getElementById('matAdjustId').value = m.id;
  document.getElementById('matAdjustNameDisplay').textContent = `${m.name} (Tồn hiện tại: ${m.stock_quantity} ${m.unit})`;
  document.getElementById('matAdjustQty').value = '';
  document.getElementById('materialAdjustModal').classList.remove('hidden');
}

function closeMaterialAdjustModal() {
  document.getElementById('materialAdjustModal').classList.add('hidden');
}

async function handleMaterialAdjustSubmit(e) {
  e.preventDefault();
  const id = document.getElementById('matAdjustId').value;
  const adjust_quantity = parseInt(document.getElementById('matAdjustQty').value, 10);
  const reason = document.getElementById('matAdjustReason').value;

  if (isNaN(adjust_quantity) || adjust_quantity === 0) {
    alert('Vui lòng nhập số lượng điều chỉnh hợp lệ');
    return;
  }

  try {
    const res = await fetch(`../api/admin/materials.php?id=${id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ adjust_quantity, reason })
    });
    const json = await res.json();
    if (json.success) {
      alert(json.message);
      closeMaterialAdjustModal();
      loadMaterialsList();
    } else {
      alert('Lỗi: ' + json.message);
    }
  } catch (err) {
    alert('Lỗi kết nối máy chủ.');
  }
}

async function loadProductInventory() {
  const container = document.getElementById('prodInvTableContainer');
  if (!container) return;

  container.innerHTML = `<div class="text-center py-8 text-stone-400">Đang tải bảng tồn kho thành phẩm...</div>`;

  try {
    const res = await fetch('../api/admin/inventory.php');
    const json = await res.json();
    if (json.success) {
      const items = json.data.items || [];
      container.innerHTML = `
        <table class="w-full text-left text-xs text-stone-600">
          <thead class="bg-stone-50 text-[11px] text-stone-400 uppercase border-b border-stone-200">
            <tr>
              <th class="py-3 px-4">Ảnh Mẫu Hoa</th>
              <th class="py-3 px-4">Mã SKU</th>
              <th class="py-3 px-4">Danh Mục</th>
              <th class="py-3 px-4">Số Lượng Tồn</th>
              <th class="py-3 px-4">Giá Niêm Yết</th>
              <th class="py-3 px-4 text-right">Nhập / Xuất Kho</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-stone-100">
            ${items.map(p => `
              <tr class="hover:bg-stone-50">
                <td class="py-2.5 px-4">
                  <div class="flex items-center space-x-2">
                    <img src="${escapeHtml(p.thumbnail)}" class="w-10 h-10 rounded-lg object-cover border border-stone-200" />
                    <span class="font-bold text-stone-900">${escapeHtml(p.name)}</span>
                  </div>
                </td>
                <td class="py-2.5 px-4 font-mono font-semibold">${escapeHtml(p.sku)}</td>
                <td class="py-2.5 px-4">${escapeHtml(p.categoryName || 'Hoa')}</td>
                <td class="py-2.5 px-4 font-bold font-mono ${p.low_stock ? 'text-rose-600' : 'text-stone-800'}">
                  ${p.stock_quantity}
                  ${p.low_stock ? '<span class="block text-[10px] text-rose-500 font-semibold">Cần cắm thêm!</span>' : ''}
                </td>
                <td class="py-2.5 px-4 font-mono">${formatMoney(p.price)}</td>
                <td class="py-2.5 px-4 text-right">
                  <button onclick="promptAdjustProductStock(${p.id}, '${escapeHtml(p.name)}')" class="px-2.5 py-1 bg-stone-900 hover:bg-black text-white rounded-lg text-xs font-semibold shadow-sm transition">
                    Nhập/Xuất
                  </button>
                </td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      `;
    }
  } catch (e) {
    container.innerHTML = `<div class="text-rose-500 py-4">Lỗi nạp tồn kho thành phẩm.</div>`;
  }
}

function promptAdjustProductStock(pid, name) {
  const qtyStr = prompt(`Nhập số lượng cắm thêm hoặc xuất hao hụt cho mẫu: ${name}\n(+ để nhập, - để xuất hỏng):`);
  if (!qtyStr) return;
  const quantity = parseInt(qtyStr.trim(), 10);
  if (isNaN(quantity) || quantity === 0) { alert('Số lượng không hợp lệ'); return; }

  const reason = quantity > 0 ? 'import' : 'damage';
  const note = prompt('Ghi chú lý do:') || 'Điều chỉnh từ trang quản trị';

  fetch('../api/admin/inventory.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ product_id: pid, quantity, reason, note })
  }).then(r => r.json()).then(j => {
    if (j.success) {
      alert('Đã cập nhật tồn kho thành phẩm thành công!');
      loadProductInventory();
    } else {
      alert('Lỗi: ' + j.message);
    }
  });
}

// ==========================================================================
// 8. PROMOTIONS & VOUCHERS MODULE (Full Professional Campaign Management)
// ==========================================================================
let currentPromotions = [];
let currentPromotionsPage = 1;
let promoScopeProductsCache = [];
let promoCodeCheckTimer = null;

const PROMO_STATUS_META = {
  draft:     { text: 'Nháp', cls: 'bg-stone-100 text-stone-600' },
  scheduled: { text: 'Đã lên lịch', cls: 'bg-blue-100 text-blue-800' },
  active:    { text: 'Đang diễn ra', cls: 'bg-emerald-100 text-emerald-800' },
  paused:    { text: 'Tạm dừng', cls: 'bg-amber-100 text-amber-800' },
  ended:     { text: 'Đã kết thúc', cls: 'bg-rose-100 text-rose-700' },
};

async function loadAdminVouchers() {
  return loadAdminPromotions(1);
}

async function loadAdminPromotions(page = 1) {
  currentPromotionsPage = page;
  const search = document.getElementById('promoSearchInput')?.value.trim() || '';
  const status = document.getElementById('promoStatusFilter')?.value || 'all';
  const discountType = document.getElementById('promoTypeFilter')?.value || 'all';
  const dateFrom = document.getElementById('promoDateFrom')?.value || '';
  const dateTo = document.getElementById('promoDateTo')?.value || '';
  const sort = document.getElementById('promoSortFilter')?.value || 'created_desc';

  const tbody = document.getElementById('vouchersTableBody');
  if (tbody) tbody.innerHTML = `<tr><td colspan="8" class="text-center py-10 text-stone-400">Đang tải danh sách khuyến mãi...</td></tr>`;

  try {
    const q = new URLSearchParams({ search, status, discount_type: discountType, date_from: dateFrom, date_to: dateTo, sort, page, limit: 15 });
    const res = await fetch(`../api/admin/promotions.php?${q.toString()}`);
    if (res.status === 401) { window.location.href = 'login.php'; return; }
    const json = await res.json();

    if (json.success && json.data) {
      currentPromotions = json.data.items || json.data.vouchers || [];
      const st = json.data.stats || {};
      const elAct = document.getElementById('promoStatActive');
      const elSch = document.getElementById('promoStatScheduled');
      const elUsed = document.getElementById('promoStatTotalUsed');
      const elSaved = document.getElementById('promoStatTotalSaved');
      if (elAct) elAct.textContent = st.active ?? 0;
      if (elSch) elSch.textContent = `${st.scheduled ?? 0} / ${st.paused ?? 0}`;
      if (elUsed) elUsed.textContent = `${st.total_used ?? 0} lượt`;
      if (elSaved) elSaved.textContent = formatMoney(st.total_saved ?? 0);

      renderPromotionsTable(currentPromotions);
      renderPromotionsPagination(json.data.pagination);
    } else {
      if (tbody) tbody.innerHTML = `<tr><td colspan="8" class="text-center py-8 text-rose-500">${escapeHtml(json.message || 'Lỗi tải dữ liệu')}</td></tr>`;
    }
  } catch (err) {
    if (tbody) tbody.innerHTML = `<tr><td colspan="8" class="text-center py-8 text-rose-500">Lỗi kết nối API khuyến mãi.</td></tr>`;
  }
}

function resetPromoFilters() {
  if (document.getElementById('promoSearchInput')) document.getElementById('promoSearchInput').value = '';
  if (document.getElementById('promoStatusFilter')) document.getElementById('promoStatusFilter').value = 'all';
  if (document.getElementById('promoTypeFilter')) document.getElementById('promoTypeFilter').value = 'all';
  if (document.getElementById('promoDateFrom')) document.getElementById('promoDateFrom').value = '';
  if (document.getElementById('promoDateTo')) document.getElementById('promoDateTo').value = '';
  if (document.getElementById('promoSortFilter')) document.getElementById('promoSortFilter').value = 'created_desc';
  loadAdminPromotions(1);
}

function renderPromotionsTable(items) {
  const tbody = document.getElementById('vouchersTableBody');
  if (!tbody) return;

  if (!items || items.length === 0) {
    tbody.innerHTML = `<tr><td colspan="8" class="text-center py-12 text-stone-400">Không có chương trình khuyến mãi nào phù hợp.</td></tr>`;
    return;
  }

  tbody.innerHTML = items.map(v => {
    const isPct = v.discount_type === 'percentage';
    const typeLabel = isPct
      ? '<span class="px-2 py-0.5 rounded bg-purple-50 text-purple-700 font-semibold text-[10px]">Phần trăm (%)</span>'
      : '<span class="px-2 py-0.5 rounded bg-sky-50 text-sky-700 font-semibold text-[10px]">Tiền cố định (VNĐ)</span>';

    const valueStr = isPct ? `${Number(v.discount_value)}%` : formatMoney(v.discount_value);
    const maxCap = v.max_discount_per_order || v.max_discount_amount;
    const scopeMap = { all: 'Toàn bộ SP', category: `${v.scope_cat_count || 0} danh mục`, product: `${v.scope_prod_count || 0} sản phẩm` };
    const custMap = { all: '', new_customer: '• Khách mới', vip: '• VIP' };
    const stMeta = PROMO_STATUS_META[v.status] || PROMO_STATUS_META.paused;

    const totalLim = Number(v.usage_limit) || 0;
    const used = Number(v.used_count) || 0;
    const remStr = totalLim > 0 ? `${Math.max(0, totalLim - used)}` : '∞';
    const limStr = totalLim > 0 ? totalLim : '∞';

    return `
      <tr class="hover:bg-stone-50 transition">
        <td class="py-3 px-4 max-w-[220px]">
          <button onclick="openPromotionDetailModal(${v.id})" class="font-bold text-stone-900 hover:text-rose-600 text-left block truncate w-full">${escapeHtml(v.program_name || v.campaign_name || v.code)}</button>
          <span class="text-[10px] text-stone-400 block">${scopeMap[v.scope_type] || 'Toàn bộ SP'} ${custMap[v.customer_scope] || ''}</span>
        </td>
        <td class="py-3 px-4">
          <span class="font-mono font-bold text-rose-600 bg-rose-50 px-2 py-0.5 rounded-lg border border-rose-100">${escapeHtml(v.code)}</span>
          ${v.batch_code ? `<span class="block text-[10px] text-stone-400 mt-0.5">Mã nhóm</span>` : ''}
        </td>
        <td class="py-3 px-4">${typeLabel}</td>
        <td class="py-3 px-4">
          <span class="font-bold text-stone-900 font-mono">${valueStr}</span>
          ${maxCap ? `<span class="block text-[10px] text-stone-500">Tối đa: ${formatMoney(maxCap)}</span>` : ''}
          <span class="block text-[10px] text-stone-400">Đơn tối thiểu: ${formatMoney(v.min_order_value)}</span>
        </td>
        <td class="py-3 px-4 font-mono text-[11px] text-stone-600 whitespace-nowrap">
          <div>${escapeHtml(formatDateVN(v.start_at))}</div>
          <div class="text-stone-400">&rarr; ${escapeHtml(formatDateVN(v.end_at))}</div>
        </td>
        <td class="py-3 px-4 font-mono text-[11px] whitespace-nowrap">
          <span>Tổng: <strong>${limStr}</strong></span> •
          <span class="text-rose-600">Dùng: <strong>${used}</strong></span> •
          <span class="text-emerald-700">Còn: <strong>${remStr}</strong></span>
          <span class="block text-[10px] text-stone-400">${v.per_user_limit > 0 ? `Tối đa ${v.per_user_limit} lượt/khách` : 'Không giới hạn/khách'}</span>
        </td>
        <td class="py-3 px-4">
          <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold ${stMeta.cls}">${stMeta.text}</span>
        </td>
        <td class="py-3 px-4 text-right whitespace-nowrap space-x-1">
          <button onclick="openPromotionDetailModal(${v.id})" class="p-1.5 bg-stone-100 hover:bg-stone-200 text-stone-700 rounded-lg transition" title="Xem chi tiết & lịch sử"><i class="ph ph-eye text-sm"></i></button>
          <button onclick="openPromotionModal(${v.id})" class="p-1.5 bg-stone-100 hover:bg-stone-200 text-stone-700 rounded-lg transition" title="Chỉnh sửa"><i class="ph ph-pencil-simple text-sm"></i></button>
          ${(v.status === 'active' || v.status === 'scheduled')
            ? `<button onclick="handlePromotionStatusAction(${v.id}, 'pause')" class="p-1.5 bg-amber-50 hover:bg-amber-100 text-amber-700 rounded-lg transition" title="Tạm dừng"><i class="ph ph-pause text-sm"></i></button>`
            : (v.status === 'paused' || v.status === 'draft')
              ? `<button onclick="handlePromotionStatusAction(${v.id}, 'resume')" class="p-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-lg transition" title="Kích hoạt"><i class="ph ph-play text-sm"></i></button>`
              : ''}
          ${v.status !== 'ended'
            ? `<button onclick="handlePromotionStatusAction(${v.id}, 'end_now')" class="p-1.5 bg-stone-100 hover:bg-rose-100 text-stone-600 hover:text-rose-600 rounded-lg transition" title="Kết thúc chương trình"><i class="ph ph-stop-circle text-sm"></i></button>`
            : ''}
          ${used === 0
            ? `<button onclick="handleDeletePromotion(${v.id}, '${escapeHtml(v.code)}')" class="p-1.5 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-lg transition" title="Xóa mã"><i class="ph ph-trash text-sm"></i></button>`
            : ''}
        </td>
      </tr>
    `;
  }).join('');
}

function renderPromotionsPagination(pagination) {
  const wrap = document.getElementById('promotionsPagination');
  if (!wrap || !pagination) return;
  const { total, page, total_pages } = pagination;
  wrap.innerHTML = `
    <span>Trang <strong>${page}</strong> / ${total_pages} (Tổng <strong>${total}</strong> chương trình)</span>
    <div class="flex items-center gap-1.5">
      <button ${page <= 1 ? 'disabled' : ''} onclick="loadAdminPromotions(${page - 1})" class="px-3 py-1 rounded-lg border border-stone-200 bg-white hover:bg-stone-100 disabled:opacity-40">Trước</button>
      <button ${page >= total_pages ? 'disabled' : ''} onclick="loadAdminPromotions(${page + 1})" class="px-3 py-1 rounded-lg border border-stone-200 bg-white hover:bg-stone-100 disabled:opacity-40">Sau</button>
    </div>
  `;
}

function openVoucherModal() {
  return openPromotionModal(null);
}

async function loadPromoScopeSelectors(selectedCategoryIds = [], selectedProductIds = []) {
  try {
    const [catRes, prodRes] = await Promise.all([
      fetch('../api/admin/categories.php?all=1'),
      fetch('../api/admin/products.php?limit=100')
    ]);
    const catJson = await catRes.json();
    const prodJson = await prodRes.json();

    const cats = catJson.success ? (catJson.data || []) : [];
    const prods = prodJson.success && prodJson.data ? (prodJson.data.items || []) : [];
    promoScopeProductsCache = prods;

    const catWrap = document.getElementById('promoCategoryChecklist');
    if (catWrap) {
      catWrap.innerHTML = cats.map(c => `
        <label class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-stone-50 cursor-pointer">
          <input type="checkbox" name="promo_cat_id" value="${c.id}" ${selectedCategoryIds.includes(Number(c.id)) ? 'checked' : ''} class="rounded text-rose-600 focus:ring-rose-500" />
          <span class="truncate">${escapeHtml(c.name)}</span>
        </label>
      `).join('');
    }

    renderPromoProductChecklist(prods, selectedProductIds);
  } catch (e) {}
}

function renderPromoProductChecklist(products, selectedIds = null) {
  const wrap = document.getElementById('promoProductChecklist');
  if (!wrap) return;
  const activeSel = selectedIds || Array.from(document.querySelectorAll('input[name="promo_prod_id"]:checked')).map(el => Number(el.value));
  wrap.innerHTML = products.map(p => `
    <label class="promo-prod-item flex items-center gap-2 p-1.5 rounded-lg hover:bg-stone-50 cursor-pointer" data-search="${escapeHtml((p.name + ' ' + p.sku).toLowerCase())}">
      <input type="checkbox" name="promo_prod_id" value="${p.id}" ${activeSel.includes(Number(p.id)) ? 'checked' : ''} class="rounded text-rose-600 focus:ring-rose-500" />
      <span class="truncate font-medium text-stone-800">${escapeHtml(p.name)}</span>
      <span class="font-mono text-[10px] text-stone-400 ml-auto">${escapeHtml(p.sku)}</span>
    </label>
  `).join('');
}

function filterPromoProductChecklist(kw) {
  const q = String(kw || '').toLowerCase().trim();
  document.querySelectorAll('.promo-prod-item').forEach(el => {
    const match = !q || (el.dataset.search || '').includes(q);
    el.classList.toggle('hidden', !match);
  });
}

function onPromoCodeModeChange() {
  const mode = document.getElementById('promoCodeMode')?.value || 'single';
  const batchWrap = document.getElementById('promoBatchCountWrap');
  const singleWrap = document.getElementById('promoSingleCodeWrap');
  const codeInput = document.getElementById('promoCode');
  if (mode === 'batch') {
    batchWrap?.classList.remove('hidden');
    singleWrap?.classList.add('hidden');
    if (codeInput) codeInput.required = false;
  } else {
    batchWrap?.classList.add('hidden');
    singleWrap?.classList.remove('hidden');
    if (codeInput) codeInput.required = true;
  }
}

function onPromoDiscountTypeChange() {
  const dtype = document.getElementById('promoDiscountType')?.value || 'percentage';
  const unit = document.getElementById('promoValueUnit');
  const maxWrap = document.getElementById('promoMaxDiscountWrap');
  const valInput = document.getElementById('promoDiscountValue');
  if (dtype === 'percentage') {
    if (unit) unit.textContent = '%';
    maxWrap?.classList.remove('hidden');
    if (valInput) { valInput.max = '100'; valInput.placeholder = '10, 15, 20...'; }
  } else {
    if (unit) unit.textContent = 'VNĐ';
    maxWrap?.classList.add('hidden');
    if (valInput) { valInput.removeAttribute('max'); valInput.placeholder = '50000'; }
  }
}

function onPromoScopeTypeChange() {
  const sc = document.getElementById('promoScopeType')?.value || 'all';
  document.getElementById('promoCategorySelectorWrap')?.classList.toggle('hidden', sc !== 'category');
  document.getElementById('promoProductSelectorWrap')?.classList.toggle('hidden', sc !== 'product');
}

async function generatePromotionCode() {
  const prefix = document.getElementById('promoPrefix')?.value.trim() || 'BLOOM';
  const msgEl = document.getElementById('promoCodeCheckMsg');
  try {
    const res = await fetch('../api/admin/promotions.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'generate_code', prefix, count: 1 })
    });
    const json = await res.json();
    if (json.success && json.code) {
      document.getElementById('promoCode').value = json.code;
      if (msgEl) {
        msgEl.className = 'text-[11px] mt-1 block text-emerald-600 font-semibold';
        msgEl.textContent = `Đã tạo mã duy nhất: ${json.code}`;
      }
    }
  } catch (e) {}
}

function checkPromoCodeDuplicate() {
  clearTimeout(promoCodeCheckTimer);
  const code = document.getElementById('promoCode')?.value.trim() || '';
  const excludeId = document.getElementById('promoEditId')?.value || '0';
  const msgEl = document.getElementById('promoCodeCheckMsg');
  if (!msgEl) return;
  if (code.length < 2) {
    msgEl.className = 'text-[11px] mt-1 block text-stone-400';
    msgEl.textContent = 'Nhập tối thiểu 3 ký tự không dấu hoặc bấm "Tạo Mã Tự Động".';
    return;
  }
  promoCodeCheckTimer = setTimeout(async () => {
    try {
      const res = await fetch(`../api/admin/promotions.php?check_code=${encodeURIComponent(code)}&exclude_id=${excludeId}`);
      const json = await res.json();
      if (json.success) {
        msgEl.className = json.available
          ? 'text-[11px] mt-1 block text-emerald-600 font-semibold'
          : 'text-[11px] mt-1 block text-rose-600 font-semibold';
        msgEl.textContent = json.message;
      }
    } catch (e) {}
  }, 300);
}

async function openPromotionModal(promoId = null) {
  const modal = document.getElementById('promotionModal');
  const title = document.getElementById('promoModalTitle');
  const lockNotice = document.getElementById('promoLockNotice');
  showFormAlert('promoFormAlert', '');
  lockNotice?.classList.add('hidden');

  if (promoId) {
    title.textContent = 'Chỉnh Sửa Chương Trình Khuyến Mãi';
    document.getElementById('promoModeWrap')?.classList.add('hidden');
    document.getElementById('promoBatchCountWrap')?.classList.add('hidden');
    document.getElementById('promoSingleCodeWrap')?.classList.remove('hidden');

    try {
      const res = await fetch(`../api/admin/promotions.php?id=${promoId}`);
      const json = await res.json();
      if (!json.success || !json.data) {
        showAdminToast(json.message || 'Không tải được dữ liệu khuyến mãi.', 'error');
        return;
      }
      const p = json.data;
      const locked = !!p.is_locked_financials;

      document.getElementById('promoEditId').value = p.id;
      document.getElementById('promoName').value = p.program_name || '';
      document.getElementById('promoPrefix').value = p.prefix || 'BLOOM';
      document.getElementById('promoCode').value = p.code || '';
      document.getElementById('promoDesc').value = p.description || '';
      document.getElementById('promoDiscountType').value = p.discount_type || 'percentage';
      document.getElementById('promoDiscountValue').value = p.discount_value || '';
      document.getElementById('promoMaxDiscount').value = p.max_discount_amount || '';
      document.getElementById('promoMaxPerOrder').value = p.max_discount_per_order || '';
      document.getElementById('promoMinOrder').value = p.min_order_value ?? 0;
      document.getElementById('promoUsageLimit').value = p.usage_limit ?? 100;
      document.getElementById('promoPerUserLimit').value = p.per_user_limit ?? 1;
      document.getElementById('promoStartAt').value = toDatetimeLocalValue(p.start_at);
      document.getElementById('promoEndAt').value = toDatetimeLocalValue(p.end_at);
      document.getElementById('promoStatus').value = (p.status === 'draft' || p.status === 'paused') ? p.status : 'active';
      document.getElementById('promoScopeType').value = p.scope_type || 'all';
      document.getElementById('promoCustomerScope').value = p.customer_scope || 'all';

      ['promoCode', 'promoDiscountType', 'promoDiscountValue', 'promoMaxDiscount', 'promoMaxPerOrder', 'promoMinOrder', 'promoScopeType', 'promoCustomerScope'].forEach(fid => {
        const el = document.getElementById(fid);
        if (el) el.disabled = locked;
      });
      document.getElementById('btnGenPromoCode').disabled = locked;
      if (locked) lockNotice?.classList.remove('hidden');

      onPromoDiscountTypeChange();
      onPromoScopeTypeChange();
      const catIds = (p.scopes && p.scopes.category_ids) ? p.scopes.category_ids.map(Number) : [];
      const prodIds = (p.scopes && p.scopes.product_ids) ? p.scopes.product_ids.map(Number) : [];
      await loadPromoScopeSelectors(catIds, prodIds);
      modal.classList.remove('hidden');
    } catch (e) {
      showAdminToast('Lỗi tải chi tiết khuyến mãi.', 'error');
    }
  } else {
    title.textContent = 'Tạo Chương Trình Khuyến Mãi Mới';
    document.getElementById('promoEditId').value = '';
    document.getElementById('promoModeWrap')?.classList.remove('hidden');
    document.getElementById('promoCodeMode').value = 'single';

    ['promoCode', 'promoDiscountType', 'promoDiscountValue', 'promoMaxDiscount', 'promoMaxPerOrder', 'promoMinOrder', 'promoScopeType', 'promoCustomerScope'].forEach(fid => {
      const el = document.getElementById(fid);
      if (el) el.disabled = false;
    });
    document.getElementById('btnGenPromoCode').disabled = false;

    document.getElementById('promoName').value = '';
    document.getElementById('promoPrefix').value = 'BLOOM';
    document.getElementById('promoCode').value = '';
    document.getElementById('promoDesc').value = '';
    document.getElementById('promoDiscountType').value = 'percentage';
    document.getElementById('promoDiscountValue').value = '10';
    document.getElementById('promoMaxDiscount').value = '100000';
    document.getElementById('promoMaxPerOrder').value = '';
    document.getElementById('promoMinOrder').value = '300000';
    document.getElementById('promoUsageLimit').value = '100';
    document.getElementById('promoPerUserLimit').value = '1';
    document.getElementById('promoStatus').value = 'active';
    document.getElementById('promoScopeType').value = 'all';
    document.getElementById('promoCustomerScope').value = 'all';

    // Default start = now, end = +7 days (local Vietnam time formatting)
    const now = new Date();
    const end = new Date(now.getTime() + 7 * 86400000);
    const fmt = d => {
      const pad = n => String(n).padStart(2, '0');
      return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
    };
    document.getElementById('promoStartAt').value = fmt(now);
    document.getElementById('promoEndAt').value = fmt(end);

    onPromoCodeModeChange();
    onPromoDiscountTypeChange();
    onPromoScopeTypeChange();
    await loadPromoScopeSelectors([], []);
    await generatePromotionCode();
    modal.classList.remove('hidden');
  }
}

function closePromotionModal() {
  document.getElementById('promotionModal').classList.add('hidden');
}

function closeVoucherModal() {
  closePromotionModal();
}

async function handlePromotionSubmit(e) {
  e.preventDefault();
  const editId = document.getElementById('promoEditId').value;
  const isEdit = editId !== '';

  const programName = document.getElementById('promoName').value.trim();
  const discountType = document.getElementById('promoDiscountType').value;
  const discountValue = parseFloat(document.getElementById('promoDiscountValue').value);
  const startAt = document.getElementById('promoStartAt').value;
  const endAt = document.getElementById('promoEndAt').value;
  const scopeType = document.getElementById('promoScopeType').value;

  if (!programName) { showFormAlert('promoFormAlert', 'Vui lòng nhập Tên chương trình.'); return; }
  if (!discountType) { showFormAlert('promoFormAlert', 'Vui lòng chọn Loại giảm giá.'); return; }
  if (isNaN(discountValue) || discountValue <= 0) { showFormAlert('promoFormAlert', 'Giá trị giảm phải lớn hơn 0.'); return; }
  if (discountType === 'percentage' && discountValue > 100) { showFormAlert('promoFormAlert', 'Mức giảm phần trăm không được vượt quá 100%.'); return; }
  if (!startAt || !endAt || new Date(endAt) <= new Date(startAt)) {
    showFormAlert('promoFormAlert', 'Ngày giờ kết thúc phải sau ngày giờ bắt đầu.');
    return;
  }

  const categoryIds = Array.from(document.querySelectorAll('input[name="promo_cat_id"]:checked')).map(el => parseInt(el.value, 10));
  const productIds = Array.from(document.querySelectorAll('input[name="promo_prod_id"]:checked')).map(el => parseInt(el.value, 10));

  if (!isEdit && scopeType === 'category' && categoryIds.length === 0) {
    showFormAlert('promoFormAlert', 'Vui lòng chọn ít nhất 1 danh mục áp dụng.');
    return;
  }
  if (!isEdit && scopeType === 'product' && productIds.length === 0) {
    showFormAlert('promoFormAlert', 'Vui lòng chọn ít nhất 1 sản phẩm áp dụng.');
    return;
  }

  const payload = {
    action: isEdit ? 'update_promotion' : 'create_promotion',
    id: isEdit ? parseInt(editId, 10) : undefined,
    program_name: programName,
    description: document.getElementById('promoDesc').value.trim(),
    code_mode: document.getElementById('promoCodeMode')?.value || 'single',
    batch_count: parseInt(document.getElementById('promoBatchCount')?.value || '5', 10),
    prefix: document.getElementById('promoPrefix').value.trim().toUpperCase(),
    code: document.getElementById('promoCode').value.trim().toUpperCase(),
    discount_type: discountType,
    discount_value: discountValue,
    max_discount_amount: document.getElementById('promoMaxDiscount').value !== '' ? parseFloat(document.getElementById('promoMaxDiscount').value) : null,
    max_discount_per_order: document.getElementById('promoMaxPerOrder').value !== '' ? parseFloat(document.getElementById('promoMaxPerOrder').value) : null,
    min_order_value: parseFloat(document.getElementById('promoMinOrder').value || '0'),
    usage_limit: parseInt(document.getElementById('promoUsageLimit').value || '0', 10),
    per_user_limit: parseInt(document.getElementById('promoPerUserLimit').value || '0', 10),
    start_at: startAt,
    end_at: endAt,
    status: document.getElementById('promoStatus').value,
    scope_type: scopeType,
    customer_scope: document.getElementById('promoCustomerScope').value,
    category_ids: categoryIds,
    product_ids: productIds,
  };

  const btn = document.getElementById('promoSaveBtn');
  btn.disabled = true;
  btn.textContent = 'Đang lưu...';
  showFormAlert('promoFormAlert', '');

  try {
    const res = await fetch('../api/admin/promotions.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const json = await res.json();
    if (json.success) {
      showAdminToast(json.message, 'success');
      closePromotionModal();
      loadAdminPromotions(currentPromotionsPage);
    } else {
      showFormAlert('promoFormAlert', json.message || 'Không thể lưu chương trình khuyến mãi.');
    }
  } catch (err) {
    showFormAlert('promoFormAlert', 'Lỗi kết nối máy chủ.');
  } finally {
    btn.disabled = false;
    btn.textContent = 'Lưu Chương Trình';
  }
}

async function openPromotionDetailModal(promoId) {
  const modal = document.getElementById('promotionDetailModal');
  const body = document.getElementById('promotionDetailBody');
  const sub = document.getElementById('promoDetailSubtitle');
  sub.textContent = 'Đang tải...';
  body.innerHTML = `<div class="py-10 text-center text-stone-400">Đang tải chi tiết chương trình...</div>`;
  modal.classList.remove('hidden');

  try {
    const res = await fetch(`../api/admin/promotions.php?id=${promoId}`);
    const json = await res.json();
    if (!json.success || !json.data) {
      body.innerHTML = `<div class="py-6 text-center text-rose-600">${escapeHtml(json.message || 'Không tìm thấy dữ liệu.')}</div>`;
      return;
    }

    const p = json.data;
    const stMeta = PROMO_STATUS_META[p.status] || PROMO_STATUS_META.paused;
    const isPct = p.discount_type === 'percentage';
    const valText = isPct ? `${p.discount_value}%` : formatMoney(p.discount_value);
    const scopeCats = (p.scopes && p.scopes.categories) ? p.scopes.categories : [];
    const scopeProds = (p.scopes && p.scopes.products) ? p.scopes.products : [];
    const usages = p.usages || [];

    sub.textContent = `Mã: ${p.code} • Hiệu lực: ${formatDateVN(p.start_at)} -> ${formatDateVN(p.end_at)}`;

    const usagesHtml = usages.length > 0
      ? `<table class="w-full text-left text-xs">
          <thead class="bg-stone-50 text-[11px] text-stone-400 uppercase border-b border-stone-200">
            <tr>
              <th class="py-2 px-3">Mã Đơn</th>
              <th class="py-2 px-3">Khách Hàng</th>
              <th class="py-2 px-3">Số Tiền Giảm</th>
              <th class="py-2 px-3">Giá Trị Đơn</th>
              <th class="py-2 px-3">Trạng Thái Đơn</th>
              <th class="py-2 px-3">Trạng Thái Mã</th>
              <th class="py-2 px-3">Thời Gian</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-stone-100">
            ${usages.map(u => `
              <tr>
                <td class="py-2 px-3 font-mono font-bold text-rose-600">#${escapeHtml(u.order_code || u.order_id)}</td>
                <td class="py-2 px-3">
                  <div class="font-semibold text-stone-800">${escapeHtml(u.customer_name)}</div>
                  <div class="text-[10px] text-stone-400 font-mono">${escapeHtml(u.customer_phone || u.customer_email || '')}</div>
                </td>
                <td class="py-2 px-3 font-mono font-bold text-emerald-700">-${formatMoney(u.discount_amount)}</td>
                <td class="py-2 px-3 font-mono">${formatMoney(u.order_total)}</td>
                <td class="py-2 px-3 text-[11px]">${escapeHtml(u.order_status)}</td>
                <td class="py-2 px-3">
                  <span class="px-2 py-0.5 rounded-full text-[10px] font-bold ${u.status === 'used' ? 'bg-emerald-100 text-emerald-800' : 'bg-stone-200 text-stone-600'}">
                    ${u.status === 'used' ? 'Đã áp dụng' : 'Đã hoàn lượt'}
                  </span>
                </td>
                <td class="py-2 px-3 font-mono text-[11px] text-stone-500">${escapeHtml(formatDateVN(u.used_at))}</td>
              </tr>
            `).join('')}
          </tbody>
        </table>`
      : `<div class="py-8 text-center text-stone-400 italic">Chưa có đơn hàng nào sử dụng mã này.</div>`;

    body.innerHTML = `
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="md:col-span-2 p-4 rounded-2xl bg-stone-50 border border-stone-200 space-y-2.5">
          <div class="flex items-center justify-between flex-wrap gap-2">
            <h4 class="text-base font-bold text-stone-900 font-serif-title">${escapeHtml(p.program_name)}</h4>
            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold ${stMeta.cls}">${stMeta.text}</span>
          </div>
          <p class="text-stone-600">${escapeHtml(p.description || 'Không có mô tả chi tiết.')}</p>
          <div class="grid grid-cols-2 gap-2 pt-1 text-[11px] text-stone-600">
            <div>Mã KM: <strong class="font-mono text-rose-600 text-xs">${escapeHtml(p.code)}</strong></div>
            <div>Mức giảm: <strong class="font-mono text-stone-900">${valText}</strong> (${isPct ? 'Phần trăm %' : 'Tiền cố định VNĐ'})</div>
            <div>Giảm tối đa (%): <strong>${p.max_discount_amount ? formatMoney(p.max_discount_amount) : 'Không giới hạn'}</strong></div>
            <div>Giảm tối đa/đơn: <strong>${p.max_discount_per_order ? formatMoney(p.max_discount_per_order) : 'Không giới hạn'}</strong></div>
            <div>Đơn tối thiểu: <strong>${formatMoney(p.min_order_value)}</strong></div>
            <div>Giới hạn/khách: <strong>${p.per_user_limit > 0 ? `${p.per_user_limit} lượt` : 'Không giới hạn'}</strong></div>
            <div>Bắt đầu: <strong class="font-mono">${escapeHtml(formatDateVN(p.start_at))}</strong></div>
            <div>Kết thúc: <strong class="font-mono">${escapeHtml(formatDateVN(p.end_at))}</strong></div>
          </div>
        </div>

        <div class="p-4 rounded-2xl bg-rose-50/60 border border-rose-100 flex flex-col justify-between space-y-3">
          <div>
            <span class="text-[11px] uppercase font-bold text-rose-700 block">Hiệu Quả Khuyến Mãi</span>
            <div class="text-2xl font-extrabold text-rose-700 font-serif-title mt-1">${formatMoney(p.total_discount_given)}</div>
            <span class="text-[11px] text-stone-500">Tổng số tiền đã giảm cho khách</span>
          </div>
          <div class="space-y-1 text-[11px] text-stone-700 border-t border-rose-200/70 pt-2">
            <div class="flex justify-between"><span>Tổng lượt cho phép:</span><strong class="font-mono">${p.usage_limit > 0 ? p.usage_limit : 'Không giới hạn'}</strong></div>
            <div class="flex justify-between"><span>Đã sử dụng:</span><strong class="font-mono text-rose-600">${p.used_count}</strong></div>
            <div class="flex justify-between"><span>Số lượt còn lại:</span><strong class="font-mono text-emerald-700">${p.remaining_usage !== null ? p.remaining_usage : '∞'}</strong></div>
          </div>
        </div>
      </div>

      <div class="p-4 rounded-2xl bg-stone-50 border border-stone-200 space-y-2">
        <span class="font-bold text-stone-800 block">Phạm Vi & Điều Kiện Áp Dụng:</span>
        <div class="flex flex-wrap gap-2 text-[11px]">
          <span class="px-2.5 py-1 rounded-lg bg-white border border-stone-200 font-semibold">
            Khách hàng: ${p.customer_scope === 'new_customer' ? 'Khách hàng mới' : p.customer_scope === 'vip' ? 'Khách VIP' : 'Tất cả khách'}
          </span>
          ${p.scope_type === 'all' ? '<span class="px-2.5 py-1 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 font-semibold">Toàn bộ sản phẩm</span>' : ''}
          ${scopeCats.map(c => `<span class="px-2.5 py-1 rounded-lg bg-purple-50 border border-purple-200 text-purple-800 font-semibold">Danh mục: ${escapeHtml(c.name)}</span>`).join('')}
          ${scopeProds.map(pr => `<span class="px-2.5 py-1 rounded-lg bg-blue-50 border border-blue-200 text-blue-800 font-semibold">${escapeHtml(pr.sku)} - ${escapeHtml(pr.name)}</span>`).join('')}
        </div>
      </div>

      ${p.batch_codes && p.batch_codes.length > 1 ? `
        <div class="p-4 rounded-2xl bg-stone-50 border border-stone-200">
          <span class="font-bold text-stone-800 block mb-2">Các Mã Riêng Thuộc Cùng Chiến Dịch (${p.batch_codes.length} mã):</span>
          <div class="flex flex-wrap gap-1.5">
            ${p.batch_codes.map(b => `<span class="px-2 py-0.5 rounded bg-white border border-stone-200 font-mono text-[11px] ${b.id == p.id ? 'border-rose-500 text-rose-600 font-bold' : ''}">${escapeHtml(b.code)} (${b.used_count}/${b.usage_limit || '∞'})</span>`).join('')}
          </div>
        </div>
      ` : ''}

      <div class="border border-stone-200 rounded-2xl overflow-hidden">
        <div class="px-4 py-3 bg-stone-50 border-b border-stone-200 font-bold text-stone-800">Lịch Sử Sử Dụng Mã (${usages.length} lượt)</div>
        <div class="max-h-64 overflow-y-auto">${usagesHtml}</div>
      </div>

      <div class="flex flex-wrap items-center justify-end gap-2 pt-2">
        ${(p.status === 'active' || p.status === 'scheduled')
          ? `<button onclick="handlePromotionStatusAction(${p.id}, 'pause', true)" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-xl font-bold">Tạm Dừng</button>`
          : (p.status === 'paused' || p.status === 'draft')
            ? `<button onclick="handlePromotionStatusAction(${p.id}, 'resume', true)" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold">Kích Hoạt Lại</button>`
            : ''}
        ${p.status !== 'ended'
          ? `<button onclick="handlePromotionStatusAction(${p.id}, 'end_now', true)" class="px-4 py-2 bg-stone-200 hover:bg-rose-100 text-stone-700 hover:text-rose-700 rounded-xl font-bold">Kết Thúc Chương Trình</button>`
          : ''}
        <button onclick="closePromotionDetailModal(); openPromotionModal(${p.id});" class="px-4 py-2 bg-stone-900 hover:bg-black text-white rounded-xl font-bold">Chỉnh Sửa</button>
      </div>
    `;
  } catch (e) {
    body.innerHTML = `<div class="py-6 text-center text-rose-600">Lỗi tải chi tiết khuyến mãi.</div>`;
  }
}

function closePromotionDetailModal() {
  document.getElementById('promotionDetailModal').classList.add('hidden');
}

async function handlePromotionStatusAction(id, action, fromDetailModal = false) {
  const confirmTexts = {
    pause: 'Tạm dừng chương trình khuyến mãi này? Khách hàng sẽ tạm thời không áp dụng được mã.',
    resume: 'Kích hoạt lại chương trình khuyến mãi này?',
    end_now: 'Kết thúc ngay chương trình khuyến mãi này? Sau khi kết thúc, mã sẽ hết hiệu lực ngay lập tức.'
  };
  if (confirmTexts[action] && !confirm(confirmTexts[action])) return;

  try {
    const res = await fetch('../api/admin/promotions.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action, id })
    });
    const json = await res.json();
    if (json.success) {
      showAdminToast(json.message, 'success');
      loadAdminPromotions(currentPromotionsPage);
      if (fromDetailModal) openPromotionDetailModal(id);
    } else {
      showAdminToast(json.message || 'Không thể cập nhật trạng thái.', 'error');
    }
  } catch (e) {
    showAdminToast('Lỗi kết nối máy chủ.', 'error');
  }
}

async function handleDeletePromotion(id, code) {
  if (!confirm(`Bạn có chắc chắn muốn xóa mã khuyến mãi "${code}"?`)) return;
  try {
    const res = await fetch(`../api/admin/promotions.php?id=${id}`, { method: 'DELETE' });
    const json = await res.json();
    if (json.success) {
      showAdminToast(json.message, 'success');
      loadAdminPromotions(currentPromotionsPage);
    } else {
      showAdminToast(json.message || 'Không thể xóa mã.', 'error');
    }
  } catch (e) {
    showAdminToast('Lỗi kết nối máy chủ.', 'error');
  }
}

// ==========================================================================
// 9. REVIEWS MODERATION MODULE
// ==========================================================================
let currentReviewsPage = 1;

async function loadAdminReviews(page = 1) {
  currentReviewsPage = page;
  const status = document.getElementById('statusReviewFilter')?.value || 'all';
  const rating = document.getElementById('ratingReviewFilter')?.value || 'all';
  const tbody = document.getElementById('reviewsTableBody');

  if (tbody) tbody.innerHTML = `<tr><td colspan="7" class="text-center py-10 text-stone-400">Đang tải danh sách đánh giá...</td></tr>`;

  try {
    const query = new URLSearchParams({ page, limit: 15, status, rating });
    const res = await fetch(`../api/admin/reviews.php?${query.toString()}`);
    if (res.status === 401) { window.location.href = 'login.php'; return; }
    const json = await res.json();

    if (!json.success) {
      if (tbody) tbody.innerHTML = `<tr><td colspan="7" class="text-center py-8 text-rose-500">${escapeHtml(json.message)}</td></tr>`;
      return;
    }

    if (json.stats) {
      const s = json.stats;
      document.getElementById('statRevTotal').textContent = s.total || 0;
      document.getElementById('statRevPending').textContent = s.pending || 0;
      document.getElementById('statRevApproved').textContent = s.approved || 0;
      document.getElementById('statRevRejected').textContent = (Number(s.rejected || 0) + Number(s.hidden || 0));
    }

    renderAdminReviewsTable(json.data || []);
  } catch (err) {
    if (tbody) tbody.innerHTML = `<tr><td colspan="7" class="text-center py-8 text-rose-500">Lỗi kết nối kiểm duyệt.</td></tr>`;
  }
}

function renderAdminReviewsTable(reviews) {
  const tbody = document.getElementById('reviewsTableBody');
  if (!tbody) return;

  if (reviews.length === 0) {
    tbody.innerHTML = `<tr><td colspan="7" class="text-center py-12 text-stone-400">Không tìm thấy đánh giá nào phù hợp.</td></tr>`;
    return;
  }

  tbody.innerHTML = reviews.map(r => {
    let stars = '';
    for (let i = 1; i <= 5; i++) {
      stars += `<i class="ph-fill ph-star ${i <= r.rating ? 'text-amber-400' : 'text-stone-200'}"></i>`;
    }

    let imagesHtml = '<span class="text-stone-300 italic text-[11px]">Không có ảnh</span>';
    if (r.images && r.images.length > 0) {
      imagesHtml = `<div class="flex items-center space-x-1.5 overflow-x-auto py-1">` +
        r.images.map(img => `
          <img src="../${escapeHtml(img.image_url)}" 
               onclick="openAdminLightbox('../${escapeHtml(img.image_url)}')" 
               alt="Ảnh thực tế" 
               class="w-11 h-11 rounded-lg object-cover border border-stone-200 cursor-pointer hover:opacity-80 transition shadow-sm flex-shrink-0" />
        `).join('') + `</div>`;
    }

    let replyHtml = '';
    if (r.reply) {
      replyHtml = `
        <div class="p-2 bg-rose-50/70 rounded-xl border border-rose-100 text-[11px]">
          <div class="font-bold text-rose-700 flex items-center justify-between mb-0.5">
            <span>Bloomora Florist:</span>
            <span class="text-[10px] text-stone-400 font-normal font-mono">${escapeHtml(r.reply.created_at || '')}</span>
          </div>
          <p class="text-stone-700 line-clamp-2">${escapeHtml(r.reply.reply_content)}</p>
        </div>
      `;
    } else {
      replyHtml = `
        <button onclick="openReplyModal(${r.id}, '${escapeHtml(r.user_name || 'Khách hàng')}', '${escapeHtml(r.comment || '')}')" 
                class="text-[11px] font-semibold text-rose-600 hover:text-rose-700 flex items-center space-x-1 hover:underline">
          <i class="ph ph-chat-text-bold"></i>
          <span>Gửi phản hồi shop</span>
        </button>
      `;
    }

    let actionsHtml = `<div class="flex items-center justify-end space-x-1.5 whitespace-nowrap">`;
    if (r.moderation_status === 'pending') {
      actionsHtml += `
        <button onclick="handleModerateReview(${r.id}, 'approve')" class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-bold text-[11px] shadow-sm transition">
          Duyệt
        </button>
        <button onclick="openRejectModal(${r.id})" class="px-2.5 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-lg font-semibold text-[11px] transition">
          Từ chối
        </button>
      `;
    } else if (r.moderation_status === 'approved') {
      actionsHtml += `
        <button onclick="handleModerateReview(${r.id}, 'hide')" class="px-2.5 py-1 bg-stone-100 hover:bg-stone-200 text-stone-600 rounded-lg font-semibold text-[11px] transition">
          Ẩn
        </button>
      `;
    } else {
      actionsHtml += `
        <button onclick="handleModerateReview(${r.id}, 'approve')" class="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-lg font-semibold text-[11px] transition">
          Duyệt lại
        </button>
      `;
    }
    actionsHtml += `</div>`;

    return `
      <tr class="hover:bg-stone-50 transition">
        <td class="py-3 px-4">
          <div class="font-bold text-stone-900">${escapeHtml(r.product_name)}</div>
          <div class="text-[10px] text-stone-400 font-mono">Đơn: #${escapeHtml(r.order_code)}</div>
        </td>
        <td class="py-3 px-4 font-semibold text-stone-800">
          ${escapeHtml(r.user_name || 'Khách hàng')}
        </td>
        <td class="py-3 px-4 max-w-xs">
          <div class="flex items-center mb-1">${stars}</div>
          <p class="text-stone-700 italic">"${escapeHtml(r.comment || '')}"</p>
        </td>
        <td class="py-3 px-4 min-w-[120px]">
          ${imagesHtml}
        </td>
        <td class="py-3 px-4">
          <span class="px-2 py-0.5 rounded-full text-[10px] font-bold ${r.moderation_status === 'approved' ? 'bg-emerald-100 text-emerald-800' : (r.moderation_status === 'rejected' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800')}">
            ${r.moderation_status}
          </span>
        </td>
        <td class="py-3 px-4 max-w-xs">
          ${replyHtml}
        </td>
        <td class="py-3 px-4 text-right">
          ${actionsHtml}
        </td>
      </tr>
    `;
  }).join('');
}

async function handleModerateReview(reviewId, action) {
  try {
    const res = await fetch('../api/admin/reviews.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ review_id: reviewId, action })
    });
    const json = await res.json();
    if (json.success) {
      loadAdminReviews(currentReviewsPage);
    } else {
      alert('Lỗi: ' + json.message);
    }
  } catch (err) {
    alert('Lỗi kết nối kiểm duyệt.');
  }
}

function openRejectModal(reviewId) {
  document.getElementById('rejectReviewId').value = reviewId;
  document.getElementById('rejectPresetReason').value = '';
  document.getElementById('rejectCustomReason').value = '';
  document.getElementById('rejectReviewModal').classList.remove('hidden');
}

function closeRejectModal() {
  document.getElementById('rejectReviewModal').classList.add('hidden');
}

async function handleRejectSubmit(e) {
  e.preventDefault();
  const reviewId = document.getElementById('rejectReviewId').value;
  const reason = document.getElementById('rejectCustomReason').value.trim();

  try {
    const res = await fetch('../api/admin/reviews.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ review_id: reviewId, action: 'reject', reason })
    });
    const json = await res.json();
    if (json.success) {
      closeRejectModal();
      loadAdminReviews(currentReviewsPage);
    } else {
      alert('Lỗi: ' + json.message);
    }
  } catch (err) {
    alert('Lỗi máy chủ.');
  }
}

function openReplyModal(reviewId, customerName, commentText) {
  document.getElementById('replyReviewId').value = reviewId;
  document.getElementById('replyModalCustomerName').textContent = `Phản hồi khách: ${customerName}`;
  document.getElementById('replyModalReviewText').textContent = `"${commentText}"`;
  document.getElementById('replyContentInput').value = '';
  document.getElementById('replyReviewModal').classList.remove('hidden');
}

function closeReplyModal() {
  document.getElementById('replyReviewModal').classList.add('hidden');
}

async function handleReplySubmit(e) {
  e.preventDefault();
  const reviewId = document.getElementById('replyReviewId').value;
  const reply_content = document.getElementById('replyContentInput').value.trim();

  try {
    const res = await fetch('../api/admin/reviews.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ review_id: reviewId, action: 'reply', reply_content })
    });
    const json = await res.json();
    if (json.success) {
      closeReplyModal();
      loadAdminReviews(currentReviewsPage);
    } else {
      alert('Lỗi: ' + json.message);
    }
  } catch (err) {
    alert('Lỗi máy chủ.');
  }
}

// ==========================================================================
// 10. CUSTOMERS MODULE
// ==========================================================================
async function loadAdminCustomers() {
  const search = document.getElementById('customerSearchInput')?.value || '';
  const tbody = document.getElementById('customersTableBody');

  if (tbody) tbody.innerHTML = `<tr><td colspan="8" class="text-center py-10 text-stone-400">Đang tải danh sách khách hàng...</td></tr>`;

  try {
    const q = new URLSearchParams({ search, limit: 50 });
    const res = await fetch(`../api/admin/customers.php?${q.toString()}`);
    if (res.status === 401) { window.location.href = 'login.php'; return; }
    const json = await res.json();

    if (json.success) {
      currentCustomers = json.data.items || [];
      renderCustomersTable(currentCustomers);
    } else {
      if (tbody) tbody.innerHTML = `<tr><td colspan="8" class="text-center py-8 text-rose-500">${escapeHtml(json.message)}</td></tr>`;
    }
  } catch (err) {
    if (tbody) tbody.innerHTML = `<tr><td colspan="8" class="text-center py-8 text-rose-500">Lỗi kết nối khách hàng.</td></tr>`;
  }
}

function renderCustomersTable(customers) {
  const tbody = document.getElementById('customersTableBody');
  if (!tbody) return;

  if (customers.length === 0) {
    tbody.innerHTML = `<tr><td colspan="8" class="text-center py-12 text-stone-400">Không tìm thấy khách hàng nào.</td></tr>`;
    return;
  }

  tbody.innerHTML = customers.map(u => {
    const spent = Number(u.total_spent) || 0;
    const ordersCount = Number(u.total_orders) || 0;
    let tier = '<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-stone-100 text-stone-600">Khách Mới</span>';

    if (spent >= 3000000 || ordersCount >= 5) {
      tier = '<span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-300">👑 VIP Gold</span>';
    } else if (spent >= 1000000 || ordersCount >= 2) {
      tier = '<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800">🌹 Thân Thiết</span>';
    }

    return `
      <tr class="hover:bg-stone-50 transition">
        <td class="py-3 px-4 font-mono font-bold text-stone-500">#${u.id}</td>
        <td class="py-3 px-4">
          <div class="font-bold text-stone-900">${escapeHtml(u.full_name)}</div>
          <div class="text-[10px] text-stone-400 font-mono">Đăng ký: ${escapeHtml(u.created_at ? u.created_at.substring(0, 10) : '')}</div>
        </td>
        <td class="py-3 px-4">
          <div class="text-stone-800">${escapeHtml(u.email)}</div>
          <div class="text-[11px] text-stone-500 font-mono">${escapeHtml(u.phone || 'Chưa cập nhật')}</div>
        </td>
        <td class="py-3 px-4 font-bold font-mono text-stone-900">${ordersCount} đơn</td>
        <td class="py-3 px-4 font-bold text-rose-600 font-mono">${formatMoney(spent)}</td>
        <td class="py-3 px-4">${tier}</td>
        <td class="py-3 px-4">
          <span class="px-2 py-0.5 rounded-full text-[10px] font-bold ${u.status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'}">
            ${u.status === 'active' ? 'Hoạt động' : 'Bị khóa'}
          </span>
        </td>
        <td class="py-3 px-4 text-right">
          <button onclick="handleToggleCustomer(${u.id}, '${u.status}')" class="px-2.5 py-1 bg-stone-100 hover:bg-stone-200 text-stone-700 rounded-lg text-xs font-semibold transition">
            ${u.status === 'active' ? 'Khóa' : 'Mở khóa'}
          </button>
        </td>
      </tr>
    `;
  }).join('');
}

async function handleToggleCustomer(id, currentStatus) {
  const newStatus = currentStatus === 'active' ? 'banned' : 'active';
  try {
    await fetch(`../api/admin/customers.php?id=${id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ status: newStatus })
    });
    loadAdminCustomers();
  } catch (e) {}
}

// ==========================================================================
// 11. REPORTS MODULE
// ==========================================================================
async function loadReportsData() {
  try {
    const res = await fetch('../api/admin/stats.php');
    const json = await res.json();
    if (!json.success) return;

    const { kpis, paymentDist, statusDist } = json.data;
    const netRev = Number(kpis.totalRevenue) || 0;
    const totalOrders = Number(kpis.totalOrders) || 0;
    const completedOrders = Number(kpis.completedOrders) || 0;

    document.getElementById('repNetRevenue').textContent = formatMoney(netRev);
    const aov = totalOrders > 0 ? Math.round(netRev / totalOrders) : 0;
    document.getElementById('repAOV').textContent = formatMoney(aov);

    const successRate = totalOrders > 0 ? Math.round((completedOrders / totalOrders) * 100) : 100;
    document.getElementById('repSuccessRate').textContent = `${successRate}%`;

    // Payment Channel Table
    const payTable = document.getElementById('repPaymentTable');
    if (payTable) {
      payTable.innerHTML = `
        <table class="w-full text-left text-xs">
          <thead class="bg-stone-50 text-[11px] text-stone-400 uppercase">
            <tr>
              <th class="py-2.5 px-3">Phương Thức</th>
              <th class="py-2.5 px-3 text-center">Số Giao Dịch</th>
              <th class="py-2.5 px-3 text-right">Tổng Tiền</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-stone-100">
            ${paymentDist.map(p => `
              <tr>
                <td class="py-2 px-3 font-semibold text-stone-800 uppercase">${escapeHtml(p.payment_method)}</td>
                <td class="py-2 px-3 text-center font-mono">${p.count}</td>
                <td class="py-2 px-3 text-right font-bold font-mono text-stone-900">${formatMoney(p.total)}</td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      `;
    }

    // Status Table
    const stTable = document.getElementById('repStatusTable');
    if (stTable) {
      stTable.innerHTML = `
        <table class="w-full text-left text-xs">
          <thead class="bg-stone-50 text-[11px] text-stone-400 uppercase">
            <tr>
              <th class="py-2.5 px-3">Trạng Thái Đơn</th>
              <th class="py-2.5 px-3 text-center">Số Lượng Đơn</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-stone-100">
            <tr><td class="py-2 px-3">1. Tiếp nhận</td><td class="py-2 px-3 text-center font-mono">${statusDist.received || 0}</td></tr>
            <tr><td class="py-2 px-3">2. Đang cắm hoa</td><td class="py-2 px-3 text-center font-mono">${statusDist.preparing || 0}</td></tr>
            <tr><td class="py-2 px-3">3. Đã duyệt ảnh QC</td><td class="py-2 px-3 text-center font-mono">${statusDist.photo_ready || 0}</td></tr>
            <tr><td class="py-2 px-3">4. Đang giao hàng</td><td class="py-2 px-3 text-center font-mono">${statusDist.delivering || 0}</td></tr>
            <tr><td class="py-2 px-3 font-bold text-emerald-700">5. Giao hoàn tất</td><td class="py-2 px-3 text-center font-mono font-bold text-emerald-700">${statusDist.completed || 0}</td></tr>
            <tr><td class="py-2 px-3 font-bold text-rose-600">Đã hủy đơn</td><td class="py-2 px-3 text-center font-mono font-bold text-rose-600">${statusDist.cancelled || 0}</td></tr>
          </tbody>
        </table>
      `;
    }
  } catch (e) {}
}

function printReportSummary() {
  window.print();
}

// ==========================================================================
// 12. STAFF & RBAC MODULE
// ==========================================================================
async function loadAdminStaff() {
  const tbody = document.getElementById('staffTableBody');
  if (tbody) tbody.innerHTML = `<tr><td colspan="7" class="text-center py-10 text-stone-400">Đang tải danh sách nhân sự...</td></tr>`;

  try {
    const res = await fetch('../api/admin/staff.php');
    if (res.status === 401) { window.location.href = 'login.php'; return; }
    const json = await res.json();

    if (json.success) {
      currentStaff = json.data || [];
      renderStaffTable(currentStaff);
    } else {
      if (tbody) tbody.innerHTML = `<tr><td colspan="7" class="text-center py-8 text-rose-500">${escapeHtml(json.message)}</td></tr>`;
    }
  } catch (err) {
    if (tbody) tbody.innerHTML = `<tr><td colspan="7" class="text-center py-8 text-rose-500">Lỗi kết nối nhân sự.</td></tr>`;
  }
}

function renderStaffTable(staff) {
  const tbody = document.getElementById('staffTableBody');
  if (!tbody) return;

  const roleBadges = {
    'admin': 'bg-rose-100 text-rose-800 border-rose-200',
    'manager': 'bg-purple-100 text-purple-800 border-purple-200',
    'florist': 'bg-amber-100 text-amber-800 border-amber-200',
    'shipper': 'bg-blue-100 text-blue-800 border-blue-200',
    'warehouse': 'bg-teal-100 text-teal-800 border-teal-200',
    'staff': 'bg-stone-100 text-stone-700 border-stone-200'
  };

  tbody.innerHTML = staff.map(u => {
    const badgeCls = roleBadges[u.role_name] || 'bg-stone-100 text-stone-700';
    return `
      <tr class="hover:bg-stone-50 transition">
        <td class="py-3 px-4">
          <div class="font-bold text-stone-900">${escapeHtml(u.full_name)}</div>
          <div class="text-[10px] text-stone-400 font-mono">Tạo lúc: ${escapeHtml(u.created_at ? u.created_at.substring(0, 10) : '')}</div>
        </td>
        <td class="py-3 px-4 font-mono text-stone-800">${escapeHtml(u.email)}</td>
        <td class="py-3 px-4 font-mono text-stone-600">${escapeHtml(u.phone || '---')}</td>
        <td class="py-3 px-4">
          <span class="px-2.5 py-1 rounded-full text-[11px] font-bold border ${badgeCls}">
            ${escapeHtml(u.role_name.toUpperCase())}
          </span>
        </td>
        <td class="py-3 px-4 text-stone-600 text-[11px]">
          ${escapeHtml(u.role_desc || 'Thao tác nghiệp vụ')}
        </td>
        <td class="py-3 px-4">
          <span class="px-2 py-0.5 rounded-full text-[10px] font-bold ${u.status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'}">
            ${u.status === 'active' ? 'Hoạt động' : 'Đã khóa'}
          </span>
        </td>
        <td class="py-3 px-4 text-right whitespace-nowrap space-x-1">
          <button onclick="openStaffModal(${u.id})" class="p-1.5 bg-stone-100 hover:bg-stone-200 text-stone-700 rounded-lg transition" title="Sửa thông tin / Đổi mật khẩu">
            <i class="ph ph-pencil-simple text-sm"></i>
          </button>
          <button onclick="handleToggleStaffStatus(${u.id}, '${u.status}')" class="p-1.5 bg-stone-100 hover:bg-rose-100 text-stone-600 hover:text-rose-600 rounded-lg transition" title="Khóa/Mở khóa">
            <i class="ph ph-lock text-sm"></i>
          </button>
        </td>
      </tr>
    `;
  }).join('');
}

function openStaffModal(staffId = null) {
  const modal = document.getElementById('staffModal');
  const title = document.getElementById('staffModalTitle');
  const passLabel = document.getElementById('staffPasswordLabel');

  if (staffId) {
    const u = currentStaff.find(x => x.id == staffId);
    if (!u) return;
    title.textContent = `Chỉnh Sửa Nhân Sự: ${u.full_name}`;
    passLabel.textContent = 'Mật Khẩu Mới (Bỏ trống nếu giữ nguyên):';
    document.getElementById('staffEditId').value = u.id;
    document.getElementById('staffName').value = u.full_name;
    document.getElementById('staffEmail').value = u.email;
    document.getElementById('staffEmail').disabled = true;
    document.getElementById('staffPhone').value = u.phone || '';
    document.getElementById('staffRoleId').value = u.role_id;
    document.getElementById('staffPassword').value = '';
  } else {
    title.textContent = 'Cấp Tài Khoản Nhân Sự Mới';
    passLabel.textContent = 'Mật Khẩu Khởi Tạo *';
    document.getElementById('staffEditId').value = '';
    document.getElementById('staffName').value = '';
    document.getElementById('staffEmail').value = '';
    document.getElementById('staffEmail').disabled = false;
    document.getElementById('staffPhone').value = '';
    document.getElementById('staffRoleId').value = '2';
    document.getElementById('staffPassword').value = 'staff123';
  }

  modal.classList.remove('hidden');
}

function closeStaffModal() {
  document.getElementById('staffModal').classList.add('hidden');
}

async function handleStaffSubmit(e) {
  e.preventDefault();
  const id = document.getElementById('staffEditId').value;
  const isEdit = id !== '';
  const payload = {
    full_name: document.getElementById('staffName').value.trim(),
    email: document.getElementById('staffEmail').value.trim(),
    phone: document.getElementById('staffPhone').value.trim(),
    role_id: parseInt(document.getElementById('staffRoleId').value, 10),
  };

  const pass = document.getElementById('staffPassword').value;
  if (isEdit) {
    if (pass) payload.new_password = pass;
  } else {
    payload.password = pass;
  }

  try {
    const url = isEdit ? `../api/admin/staff.php?id=${id}` : '../api/admin/staff.php';
    const method = isEdit ? 'PUT' : 'POST';
    const res = await fetch(url, {
      method,
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const json = await res.json();
    if (json.success) {
      alert(json.message);
      closeStaffModal();
      loadAdminStaff();
    } else {
      alert('Lỗi: ' + json.message);
    }
  } catch (err) {
    alert('Lỗi kết nối máy chủ.');
  }
}

async function handleToggleStaffStatus(id, currentStatus) {
  const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
  try {
    await fetch(`../api/admin/staff.php?id=${id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ status: newStatus })
    });
    loadAdminStaff();
  } catch (e) {}
}

// ==========================================================================
// 13. SETTINGS & SYSTEM AUDIT LOGS MODULE
// ==========================================================================
async function loadStoreSettings() {
  try {
    const res = await fetch('../api/admin/settings.php');
    if (res.status === 401) { window.location.href = 'login.php'; return; }
    const json = await res.json();

    if (json.success) {
      const s = json.data;
      if (s.store_name) document.getElementById('cfg_store_name').value = s.store_name.value || '';
      if (s.store_hotline) document.getElementById('cfg_store_hotline').value = s.store_hotline.value || '';
      if (s.store_address) document.getElementById('cfg_store_address').value = s.store_address.value || '';
      if (s.store_email) document.getElementById('cfg_store_email').value = s.store_email.value || '';
      if (s.store_opening_hours) document.getElementById('cfg_store_opening_hours').value = s.store_opening_hours.value || '';
      if (s.vietqr_bank_id) document.getElementById('cfg_vietqr_bank_id').value = s.vietqr_bank_id.value || '';
      if (s.vietqr_account_no) document.getElementById('cfg_vietqr_account_no').value = s.vietqr_account_no.value || '';
      if (s.vietqr_account_name) document.getElementById('cfg_vietqr_account_name').value = s.vietqr_account_name.value || '';
      if (s.momo_phone) document.getElementById('cfg_momo_phone').value = s.momo_phone.value || '';
    }
  } catch (e) {}
}

async function saveStoreSettings() {
  const payload = {
    store_name: document.getElementById('cfg_store_name').value.trim(),
    store_hotline: document.getElementById('cfg_store_hotline').value.trim(),
    store_address: document.getElementById('cfg_store_address').value.trim(),
    store_email: document.getElementById('cfg_store_email').value.trim(),
    store_opening_hours: document.getElementById('cfg_store_opening_hours').value.trim(),
    vietqr_bank_id: document.getElementById('cfg_vietqr_bank_id').value.trim(),
    vietqr_account_no: document.getElementById('cfg_vietqr_account_no').value.trim(),
    vietqr_account_name: document.getElementById('cfg_vietqr_account_name').value.trim(),
    momo_phone: document.getElementById('cfg_momo_phone').value.trim(),
  };

  try {
    const res = await fetch('../api/admin/settings.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const json = await res.json();
    if (json.success) {
      alert(json.message);
    } else {
      alert('Lỗi: ' + json.message);
    }
  } catch (e) {
    alert('Lỗi lưu cài đặt.');
  }
}

async function loadActivityLogs() {
  const mod = document.getElementById('logModuleFilter')?.value || 'all';
  const tbody = document.getElementById('logsTableBody');
  if (tbody) tbody.innerHTML = `<tr><td colspan="6" class="text-center py-10 text-stone-400">Đang tải nhật ký kiểm toán...</td></tr>`;

  try {
    const q = new URLSearchParams({ module: mod, limit: 30 });
    const res = await fetch(`../api/admin/logs.php?${q.toString()}`);
    const json = await res.json();

    if (json.success) {
      renderActivityLogsTable(json.data.items || []);
    } else {
      if (tbody) tbody.innerHTML = `<tr><td colspan="6" class="text-center py-8 text-rose-500">${escapeHtml(json.message)}</td></tr>`;
    }
  } catch (e) {
    if (tbody) tbody.innerHTML = `<tr><td colspan="6" class="text-center py-8 text-rose-500">Lỗi kết nối nhật ký.</td></tr>`;
  }
}

function renderActivityLogsTable(logs) {
  const tbody = document.getElementById('logsTableBody');
  if (!tbody) return;

  if (logs.length === 0) {
    tbody.innerHTML = `<tr><td colspan="6" class="text-center py-12 text-stone-400">Chưa có nhật ký hoạt động nào được ghi nhận.</td></tr>`;
    return;
  }

  tbody.innerHTML = logs.map(l => {
    return `
      <tr class="hover:bg-stone-50 transition">
        <td class="py-2.5 px-4 font-mono text-stone-400 text-[11px] whitespace-nowrap">${escapeHtml(l.created_at)}</td>
        <td class="py-2.5 px-4 font-semibold text-stone-900 whitespace-nowrap">
          ${escapeHtml(l.user_name || 'Hệ thống')}
          <span class="block text-[10px] text-stone-400 font-normal">${escapeHtml(l.user_email || '')}</span>
        </td>
        <td class="py-2.5 px-4">
          <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-stone-100 text-stone-700 uppercase">
            ${escapeHtml(l.module)}
          </span>
        </td>
        <td class="py-2.5 px-4 font-mono font-semibold text-rose-600">${escapeHtml(l.action)}</td>
        <td class="py-2.5 px-4 text-stone-700 max-w-sm truncate" title="${escapeHtml(l.details || '')}">
          ${escapeHtml(l.details || '')}
        </td>
        <td class="py-2.5 px-4 font-mono text-stone-400 text-[11px]">${escapeHtml(l.ip_address || '127.0.0.1')}</td>
      </tr>
    `;
  }).join('');
}

// Lightbox
function openAdminLightbox(imgUrl) {
  const modal = document.getElementById('adminLightboxModal');
  const img = document.getElementById('adminLightboxImg');
  if (modal && img) {
    img.src = imgUrl;
    modal.classList.remove('hidden');
  }
}

function closeAdminLightbox() {
  const modal = document.getElementById('adminLightboxModal');
  if (modal) modal.classList.add('hidden');
}

// Logout
async function handleAdminLogout() {
  if (!confirm('Đăng xuất khỏi hệ thống Bloomora Admin?')) return;
  try {
    await fetch('../api/auth/logout.php', { method: 'POST' });
  } catch (e) {}
  window.location.href = 'login.php';
}

// ==========================================================================
// APPLICATION INITIALIZATION
// ==========================================================================
document.addEventListener('DOMContentLoaded', () => {
  startClock();

  // Kiểm tra URL hash (vd #orders, #products, ...)
  const initialHash = window.location.hash.replace('#', '');
  if (initialHash && VIEW_METADATA[initialHash]) {
    switchView(initialHash);
  } else {
    switchView('dashboard');
  }
});
