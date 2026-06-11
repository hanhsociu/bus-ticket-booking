let lastVerification = null;

document.addEventListener('DOMContentLoaded', () => {
    if (!requireAdmin()) return;
    document.getElementById('btn-verify').addEventListener('click', verifyTicket);
    document.getElementById('btn-checkin').addEventListener('click', checkInTicket);
    document.getElementById('ticket-code').addEventListener('keypress', e => { if (e.key === 'Enter') verifyTicket(); });
});

async function verifyTicket() {
    const code = document.getElementById('ticket-code').value.trim();
    if (!code) return showToast('Nhập mã vé.', 'warning');
    const btn = document.getElementById('btn-verify');
    setLoading(btn, true);
    try {
        const res = await apiFetch(`/admin/tickets/verify?code=${encodeURIComponent(code)}`);
        lastVerification = res.data;
        renderResult(res.data);
        const canCheckIn = res.data.verification?.can_check_in;
        document.getElementById('btn-checkin').classList.toggle('d-none', !canCheckIn);
    } catch (e) {
        document.getElementById('ticket-result').classList.remove('d-none');
        document.getElementById('ticket-result').innerHTML = `
            <div class="ticket-result-invalid p-4 text-center">
                <i class="bx bx-x-circle text-danger" style="font-size:4rem"></i>
                <h4 class="mt-3 text-danger">Vé không hợp lệ</h4>
                <p>${escapeHtml(e.message)}</p>
            </div>`;
        document.getElementById('btn-checkin').classList.add('d-none');
    } finally {
        setLoading(btn, false);
    }
}

function renderResult(data) {
    const b = data.booking;
    const v = data.verification || {};
    const valid = v.is_valid_ticket;
    const el = document.getElementById('ticket-result');
    el.classList.remove('d-none');
    el.innerHTML = `
        <div class="${valid ? 'ticket-result-valid' : 'ticket-result-invalid'} p-4">
            <div class="row align-items-center">
                <div class="col-md-4 text-center border-end">
                    <i class="bx ${valid ? 'bx-check-shield text-success' : 'bx-error text-danger'}" style="font-size:4rem"></i>
                    <h4 class="mt-2">${valid ? 'VÉ HỢP LỆ' : 'VÉ KHÔNG HỢP LỆ'}</h4>
                    <div class="booking-code-display text-dark">${escapeHtml(b.booking_code)}</div>
                    <div class="mt-2">${statusBadge(b.status)}</div>
                    ${v.reason ? `<p class="text-muted small mt-2">${escapeHtml(v.reason)}</p>` : ''}
                </div>
                <div class="col-md-8">
                    <h5>Khách hàng</h5>
                    <p>${escapeHtml(b.customer?.name)} · ${escapeHtml(b.customer?.phone || b.customer?.email)}</p>
                    <h5>Chuyến xe</h5>
                    <p>${escapeHtml(b.trip?.route?.from_location)} → ${escapeHtml(b.trip?.route?.to_location)}</p>
                    <p>${formatDateTime(b.trip?.departure_time)} · Xe: ${escapeHtml(b.trip?.bus?.name || '')}</p>
                    <h5>Ghế (${v.checked_in_count || 0}/${v.total_seat_count || 0} check-in)</h5>
                    <div>${(b.seats || []).map(s => `
                        <span class="badge ${s.is_checked_in ? 'bg-success' : 'bg-secondary'} me-1 p-2">
                            ${escapeHtml(s.seat_number)} ${s.is_checked_in ? '✓' : ''}
                        </span>`).join('')}</div>
                    <p class="mt-3 mb-0"><strong>Tổng tiền:</strong> ${formatMoney(b.total_amount)}</p>
                </div>
            </div>
        </div>`;
}

async function checkInTicket() {
    const code = document.getElementById('ticket-code').value.trim();
    if (!confirmAction('Check-in toàn bộ ghế chưa check-in?')) return;
    try {
        await apiFetch('/admin/tickets/check-in', { method: 'POST', body: { code } });
        showToast('Check-in thành công!', 'success');
        verifyTicket();
    } catch (e) { showToast(e.message, 'danger'); }
}
