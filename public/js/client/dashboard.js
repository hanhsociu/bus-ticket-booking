const STAT_CONFIG = [
    { key: 'total_bookings', label: 'Tổng booking', icon: 'icon-ticket', color: 'primary' },
    { key: 'confirmed_bookings', label: 'Đã xác nhận', icon: 'icon-check', color: 'success' },
    { key: 'pending_bookings', label: 'Chờ thanh toán', icon: 'icon-clock', color: 'warning' },
    { key: 'cancelled_bookings', label: 'Đã hủy', icon: 'icon-close', color: 'danger' },
    { key: 'expired_bookings', label: 'Hết hạn', icon: 'icon-warning', color: 'dark' },
    { key: 'total_paid_amount', label: 'Đã thanh toán', icon: 'icon-credit-card', color: 'info', money: true },
];

document.addEventListener('DOMContentLoaded', async () => {
    if (!requireAuth()) return;
    try {
        const res = await apiFetch('/customer/dashboard/overview');
        const d = res.data;
        document.getElementById('user-name').textContent = d.user?.name || 'bạn';
        const s = d.summary || {};
        document.getElementById('summary-cards').innerHTML = STAT_CONFIG.map(cfg => {
            const val = cfg.money ? formatMoney(s[cfg.key]) : (s[cfg.key] ?? 0);
            return `
            <div class="col-6 col-md-4 col-xl-2">
                <div class="bb-card bb-stat-card">
                    <div class="bb-stat-icon bg-${cfg.color}-subtle text-${cfg.color}">
                        <span class="${cfg.icon}"></span>
                    </div>
                    <div class="bb-stat-value">${val}</div>
                    <div class="bb-stat-label">${cfg.label}</div>
                </div>
            </div>`;
        }).join('');
        document.getElementById('upcoming-list').innerHTML = bookingList(
            d.upcoming_bookings,
            'icon-calendar',
            'Chưa có vé sắp đi',
            'Đặt vé cho chuyến sắp tới để xem tại đây.',
            '/trips'
        );
        document.getElementById('pending-list').innerHTML = bookingList(
            d.pending_payment_bookings,
            'icon-clock',
            'Không có vé chờ thanh toán',
            'Tất cả vé đã được xử lý.',
            null,
            true
        );
        const notifs = d.recent_notifications || [];
        document.getElementById('notifications-list').innerHTML = notifs.length
            ? notifs.map(n => notifRow(n)).join('')
            : emptyState({
                icon: 'icon-bell',
                title: 'Chưa có thông báo',
                text: 'Thông báo về vé và hoàn tiền sẽ hiện tại đây.',
            });
        document.getElementById('dashboard-loading').classList.add('d-none');
        document.getElementById('dashboard-content').classList.remove('d-none');
    } catch (e) {
        showToast(e.message, 'danger');
    }
});

function bookingList(bookings, icon, emptyTitle, emptyText, link, showPay = false) {
    if (!bookings?.length) {
        return emptyState({
            icon,
            title: emptyTitle,
            text: emptyText,
            buttons: link ? `<a href="${link}" class="btn btn-bb-primary btn-sm">Tìm chuyến</a>` : '',
        });
    }
    return bookings.map(b => {
        const route = b.trip?.route;
        const dep = b.trip?.departure_time;
        return `<div class="bb-list-item d-flex justify-content-between align-items-center gap-2">
            <div>
                <a href="/customer/bookings/${b.id}" class="fw-bold text-decoration-none bb-booking-code">${escapeHtml(b.booking_code)}</a>
                ${statusBadge(b.status)}
                <div class="small text-muted mt-1">${route ? escapeHtml(route.from_location) + ' → ' + escapeHtml(route.to_location) : ''}</div>
                ${dep ? `<div class="small text-muted">${formatDateTime(dep)}</div>` : ''}
            </div>
            ${showPay ? `<a href="/customer/bookings/${b.id}/payment" class="btn btn-warning btn-sm flex-shrink-0">Thanh toán</a>` : ''}
        </div>`;
    }).join('');
}

function notifRow(n) {
    const ni = notifIcon(n.type);
    return `<div class="bb-list-item d-flex gap-3 ${n.read_at ? '' : 'bb-notif-item unread'}">
        <div class="bb-notif-icon ${ni.bg}"><span class="${ni.icon}"></span></div>
        <div class="flex-grow-1">
            <strong class="d-block">${escapeHtml(n.title)}</strong>
            <span class="small text-muted">${escapeHtml(n.message)}</span>
            <div class="small text-muted mt-1">${formatDateTime(n.created_at)}</div>
        </div>
    </div>`;
}
