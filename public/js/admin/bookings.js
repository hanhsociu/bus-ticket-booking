let detailModal;
document.addEventListener('DOMContentLoaded', () => {
    if (!requireAdmin()) return;
    detailModal = new bootstrap.Modal(document.getElementById('bookingDetailModal'));
    ['search-q','filter-status','filter-date'].forEach(id => {
        document.getElementById(id)?.addEventListener('change', () => loadBookings());
        document.getElementById(id)?.addEventListener('input', debounce(() => loadBookings(), 400));
    });
    loadBookings();
});

function debounce(fn, ms) { let t; return () => { clearTimeout(t); t = setTimeout(fn, ms); }; }

async function loadBookings(page = 1) {
    renderTableLoading('bookings-tbody');
    const q = buildQuery({
        q: document.getElementById('search-q')?.value,
        status: document.getElementById('filter-status')?.value,
        date: document.getElementById('filter-date')?.value,
        page,
    });
    try {
        const res = await apiFetch(`/admin/bookings${q}`);
        const p = res.data;
        document.getElementById('bookings-tbody').innerHTML = (p.data || []).map(b => {
            const r = b.trip?.route || {};
            return `<tr>
                <td><a href="#" onclick="showDetail(${b.id});return false">${escapeHtml(b.booking_code)}</a></td>
                <td>${escapeHtml(b.user?.name || '')}</td>
                <td>${escapeHtml(r.from_location || '')} → ${escapeHtml(r.to_location || '')}</td>
                <td>${formatMoney(b.total_amount)}</td>
                <td>${statusBadge(b.status)}</td>
                <td>${b.status === 'pending_payment' ? `<button class="btn btn-sm btn-danger" onclick="cancelBooking(${b.id})">Hủy</button>` : ''}</td>
            </tr>`;
        }).join('');
        document.getElementById('bookings-pagination').innerHTML = paginateLinks(p, loadBookings);
    } catch (e) { showToast(e.message, 'danger'); }
}

window.showDetail = async id => {
    try {
        const res = await apiFetch(`/admin/bookings/${id}`);
        const b = res.data;
        const pendingPay = (b.payments || []).find(p => p.status === 'pending');
        const fakeBtn = b.status === 'pending_payment' && pendingPay
            ? `<button class="btn btn-warning btn-sm mt-2" onclick="fakePayment(${pendingPay.id}, ${b.id})">Fake PayOS success (dev)</button>` : '';
        document.getElementById('booking-detail-body').innerHTML = `
            <p><strong>${escapeHtml(b.booking_code)}</strong> ${statusBadge(b.status)}</p>
            <p>Khách: ${escapeHtml(b.user?.name)} · ${escapeHtml(b.user?.email)}</p>
            <p>Ghế: ${(b.items||[]).map(i => i.seat_number).join(', ')}</p>
            <p>Tổng: ${formatMoney(b.total_amount)}</p>
            ${fakeBtn}
            <h6 class="mt-3">Lịch sử</h6>
            ${(b.histories||[]).map(h => `<div class="small border-bottom py-1">${escapeHtml(h.action)} — ${escapeHtml(h.note||'')}</div>`).join('')}`;
        detailModal.show();
    } catch (e) { showToast(e.message, 'danger'); }
};

window.fakePayment = async (paymentId, bookingId) => {
    if (!confirmAction('Giả lập thanh toán thành công?')) return;
    try {
        await apiFetch(`/admin/payments/${paymentId}/fake-success`, { method: 'POST' });
        showToast('Fake thanh toán thành công.', 'success');
        showDetail(bookingId);
        loadBookings();
    } catch (e) { showToast(e.message, 'danger'); }
};

window.cancelBooking = async id => {
    if (!confirmAction('Hủy booking pending?')) return;
    try { await apiFetch(`/admin/bookings/${id}/cancel`, { method: 'POST' }); showToast('Đã hủy.', 'success'); loadBookings(); }
    catch (e) { showToast(e.message, 'danger'); }
};
