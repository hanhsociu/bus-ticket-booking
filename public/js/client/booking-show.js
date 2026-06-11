let booking = null;
const refundModalEl = () => document.getElementById('refundModal');
const getRefundModal = () => bootstrap.Modal.getOrCreateInstance(refundModalEl());

document.addEventListener('DOMContentLoaded', async () => {
    if (!requireAuth()) return;
    await loadBooking();
    document.getElementById('btn-submit-refund')?.addEventListener('click', submitRefund);
});

async function loadBooking() {
    try {
        const res = await apiFetch(`/bookings/${window.BOOKING_ID}`);
        booking = res.data;
        document.getElementById('booking-code').textContent = booking.booking_code;
        document.getElementById('booking-status').innerHTML = statusBadge(booking.status);
        const trip = booking.trip || {};
        const route = trip.route || {};
        document.getElementById('trip-info').innerHTML = `
            <h5 class="fw-bold mb-3">Thông tin chuyến</h5>
            <div class="row g-3">
                <div class="col-sm-6">
                    <div class="text-muted small">Tuyến</div>
                    <strong>${escapeHtml(route.from_location)} → ${escapeHtml(route.to_location)}</strong>
                </div>
                <div class="col-sm-6">
                    <div class="text-muted small">Mã chuyến</div>
                    <strong>${escapeHtml(trip.code || '—')}</strong>
                </div>
                <div class="col-sm-6">
                    <div class="text-muted small">Xe</div>
                    <strong>${escapeHtml(trip.bus?.name || '—')}</strong>
                    <span class="text-muted"> · ${escapeHtml(trip.bus?.license_plate || '')}</span>
                </div>
                <div class="col-sm-6">
                    <div class="text-muted small">Khởi hành</div>
                    <strong>${formatDateTime(trip.departure_time)}</strong>
                </div>
                <div class="col-sm-6">
                    <div class="text-muted small">Đến nơi</div>
                    <strong>${formatDateTime(trip.arrival_time)}</strong>
                </div>
                <div class="col-sm-6">
                    <div class="text-muted small">Tổng tiền</div>
                    <strong class="text-primary">${formatMoney(booking.total_amount)}</strong>
                </div>
            </div>`;
        document.getElementById('seats-info').innerHTML = (booking.items || []).length
            ? (booking.items || []).map(i => `
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle me-2 mb-2 p-2">
                    Ghế ${escapeHtml(i.seat_number)} — ${formatMoney(i.price)}
                    ${i.checked_in_at ? ' <span class="text-success">✓</span>' : ''}
                </span>`).join('')
            : '<p class="text-muted mb-0">—</p>';
        document.getElementById('payments-info').innerHTML = (booking.payments || []).length
            ? (booking.payments || []).map(p => `
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <div>
                        <div class="small fw-semibold">${escapeHtml(p.payment_code)}</div>
                        <div class="small text-muted">${p.paid_at ? formatDateTime(p.paid_at) : 'Chưa thanh toán'}</div>
                    </div>
                    <div class="text-end">${statusBadge(p.status)}<div class="fw-bold">${formatMoney(p.amount)}</div></div>
                </div>`).join('')
            : '<p class="text-muted mb-0">Chưa có giao dịch thanh toán.</p>';
        document.getElementById('histories-info').innerHTML = (booking.histories || []).length
            ? (booking.histories || []).map(h => `
                <div class="bb-list-item">
                    <div class="d-flex justify-content-between gap-2">
                        <strong class="small">${escapeHtml(h.action)}</strong>
                        ${h.new_status ? statusBadge(h.new_status) : ''}
                    </div>
                    <div class="small text-muted">${escapeHtml(h.note || '')}</div>
                    <div class="small text-muted">${formatDateTime(h.created_at)}</div>
                </div>`).join('')
            : '<p class="text-muted mb-0">Chưa có lịch sử.</p>';
        renderStatusAlert();
        renderActions();
        document.getElementById('booking-loading').classList.add('d-none');
        document.getElementById('booking-content').classList.remove('d-none');
    } catch (e) {
        document.getElementById('booking-loading').innerHTML = emptyState({
            icon: 'icon-warning',
            title: 'Không tải được vé',
            text: e.message,
            buttons: '<a href="/customer/bookings" class="btn btn-bb-primary btn-sm">Quay lại</a>',
        });
    }
}

function renderStatusAlert() {
    const el = document.getElementById('status-alert');
    const map = {
        refund_requested: { type: 'info', text: 'Yêu cầu hoàn vé đang chờ admin xử lý.' },
        refunded: { type: 'primary', text: 'Vé đã được hoàn. Không thể sử dụng.' },
        cancelled: { type: 'secondary', text: 'Booking đã bị hủy.' },
        expired: { type: 'dark', text: 'Booking đã hết hạn thanh toán.' },
        confirmed: { type: 'success', text: 'Vé đã xác nhận. Xuất mã vé khi lên xe.' },
        pending_payment: { type: 'warning', text: 'Vui lòng thanh toán trong 10 phút để giữ ghế.' },
    };
    const info = map[booking.status];
    el.innerHTML = info
        ? `<div class="alert alert-${info.type} small mb-0">${info.text}</div>`
        : '';
}

function renderActions() {
    const el = document.getElementById('action-buttons');
    const readonly = ['refunded', 'cancelled', 'expired'];
    if (readonly.includes(booking.status)) {
        el.innerHTML = '<button class="btn btn-light" disabled>Không có thao tác</button>';
        return;
    }
    let html = '';
    if (booking.status === 'pending_payment') {
        html += `<a href="/customer/bookings/${booking.id}/payment" class="btn btn-warning btn-lg">Thanh toán ngay</a>`;
        html += `<button class="btn btn-outline-danger" id="btn-cancel">Hủy booking</button>`;
    }
    if (booking.status === 'confirmed') {
        const hasCheckIn = (booking.items || []).some(i => i.checked_in_at);
        if (!hasCheckIn) {
            html += `<button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#refundModal">Yêu cầu hoàn vé</button>`;
        }
    }
    if (booking.status === 'refund_requested') {
        html += '<button class="btn btn-light" disabled>Đang chờ duyệt hoàn vé</button>';
    }
    el.innerHTML = html;
    document.getElementById('btn-cancel')?.addEventListener('click', cancelBooking);
}

async function cancelBooking() {
    if (!confirm('Bạn chắc chắn muốn hủy booking này? Ghế sẽ được nhả.')) return;
    try {
        await apiFetch(`/bookings/${booking.id}/cancel`, { method: 'POST' });
        showToast('Đã hủy booking.', 'success');
        loadBooking();
    } catch (e) { showToast(e.message, 'danger'); }
}

async function submitRefund() {
    const reason = document.getElementById('refund-reason').value.trim();
    if (!reason) return showToast('Vui lòng nhập lý do hoàn vé.', 'warning');
    const btn = document.getElementById('btn-submit-refund');
    setLoading(btn, true);
    try {
        await apiFetch(`/bookings/${booking.id}/request-refund`, { method: 'POST', body: { reason } });
        getRefundModal().hide();
        showToast('Đã gửi yêu cầu hoàn vé.', 'success');
        loadBooking();
    } catch (e) { showToast(e.message, 'danger'); }
    finally { setLoading(btn, false); }
}
