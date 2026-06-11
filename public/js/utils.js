/**
 * UI utilities: toast, formatters, status badges, empty states
 */
function showToast(message, type = 'info') {
    const container = document.getElementById('toast-container');
    if (!container) {
        alert(message);
        return;
    }

    const id = `toast-${Date.now()}`;
    const bgMap = {
        success: 'text-bg-success',
        danger: 'text-bg-danger',
        warning: 'text-bg-warning',
        info: 'text-bg-info',
        primary: 'text-bg-primary',
        secondary: 'text-bg-secondary',
        dark: 'text-bg-dark',
    };

    const html = `
        <div id="${id}" class="toast align-items-center ${bgMap[type] || bgMap.info} border-0 shadow" role="alert">
            <div class="d-flex">
                <div class="toast-body">${escapeHtml(message)}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>`;

    container.insertAdjacentHTML('beforeend', html);
    const el = document.getElementById(id);
    const toast = new bootstrap.Toast(el, { delay: 4500 });
    toast.show();
    el.addEventListener('hidden.bs.toast', () => el.remove());
}

function formatMoney(amount) {
    const n = Number(amount) || 0;
    return n.toLocaleString('vi-VN') + ' đ';
}

function formatDateTime(value) {
    if (!value) return '—';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return value;
    return d.toLocaleString('vi-VN', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function formatDate(value) {
    if (!value) return '—';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return value;
    return d.toLocaleDateString('vi-VN', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
}

function formatTime(value) {
    if (!value) return '—';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return value;
    return d.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
}

const STATUS_LABELS = {
    pending_payment: 'Chờ thanh toán',
    confirmed: 'Đã xác nhận',
    cancelled: 'Đã hủy',
    expired: 'Hết hạn',
    refund_requested: 'Yêu cầu hoàn vé',
    refunded: 'Đã hoàn vé',
    scheduled: 'Đang mở bán',
    departed: 'Đã khởi hành',
    completed: 'Hoàn thành',
    available: 'Còn trống',
    reserved: 'Đang giữ',
    booked: 'Đã đặt',
    blocked: 'Khóa',
    pending: 'Chờ xử lý',
    success: 'Thành công',
    failed: 'Thất bại',
    sent: 'Đã gửi',
    read: 'Đã đọc',
};

const STATUS_BADGES = {
    pending_payment: 'warning',
    confirmed: 'success',
    cancelled: 'danger',
    expired: 'dark',
    refund_requested: 'info',
    refunded: 'primary',
    scheduled: 'success',
    departed: 'warning',
    completed: 'primary',
    available: 'success',
    reserved: 'warning',
    booked: 'secondary',
    blocked: 'dark',
    pending: 'warning',
    success: 'success',
    failed: 'danger',
    sent: 'success',
    read: 'secondary',
};

function statusBadge(status, label = null) {
    const text = label || STATUS_LABELS[status] || String(status || '').replace(/_/g, ' ');
    const color = STATUS_BADGES[status] || 'secondary';
    return `<span class="badge bg-${color}">${escapeHtml(text)}</span>`;
}

function seatStatusBadge(status) {
    return statusBadge(status, STATUS_LABELS[status] || status);
}

function escapeHtml(str) {
    return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function getQueryParam(name) {
    return new URLSearchParams(window.location.search).get(name);
}

function buildQuery(params) {
    const q = new URLSearchParams();
    Object.entries(params).forEach(([k, v]) => {
        if (v !== null && v !== undefined && v !== '') q.set(k, v);
    });
    const s = q.toString();
    return s ? `?${s}` : '';
}

function emptyState({ icon = 'icon-bus', title, text, buttons = '' }) {
    return `
        <div class="bb-empty">
            <div class="bb-empty-icon"><span class="${icon}"></span></div>
            <div class="bb-empty-title">${escapeHtml(title)}</div>
            <p class="bb-empty-text">${escapeHtml(text)}</p>
            ${buttons}
        </div>`;
}

function skeletonCards(count = 3, cols = 'col-md-6 col-lg-4') {
    return Array.from({ length: count }, () => `
        <div class="${cols} mb-4">
            <div class="bb-skeleton bb-skeleton-card"></div>
        </div>`).join('');
}

function notifIcon(type) {
    const map = {
        booking_confirmed: { icon: 'icon-check', bg: 'bg-success-subtle text-success' },
        refund_approved: { icon: 'icon-undo', bg: 'bg-primary-subtle text-primary' },
        refund_rejected: { icon: 'icon-close', bg: 'bg-danger-subtle text-danger' },
        booking_confirmed_email_failed: { icon: 'icon-warning', bg: 'bg-warning-subtle text-warning' },
    };
    return map[type] || { icon: 'icon-bell', bg: 'bg-info-subtle text-info' };
}

window.showToast = showToast;
window.formatMoney = formatMoney;
window.formatDateTime = formatDateTime;
window.formatDate = formatDate;
window.formatTime = formatTime;
window.statusBadge = statusBadge;
window.seatStatusBadge = seatStatusBadge;
window.escapeHtml = escapeHtml;
window.getQueryParam = getQueryParam;
window.buildQuery = buildQuery;
window.emptyState = emptyState;
window.skeletonCards = skeletonCards;
window.notifIcon = notifIcon;
window.STATUS_LABELS = STATUS_LABELS;
