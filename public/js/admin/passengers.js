document.addEventListener('DOMContentLoaded', loadPassengers);

async function loadPassengers() {
    if (!requireAdmin()) return;
    renderTableLoading('passengers-tbody');
    try {
        const res = await apiFetch(`/admin/trips/${window.TRIP_ID}/passengers`);
        const d = res.data;
        const trip = d.trip || {};
        const route = trip.route || {};
        document.getElementById('trip-info').innerHTML = `
            <div class="card stat-card"><div class="card-body">
                <h5>${escapeHtml(trip.code)} ${statusBadge(trip.status)}</h5>
                <p class="mb-0">${escapeHtml(route.from_location)} → ${escapeHtml(route.to_location)} · ${formatDateTime(trip.departure_time)}</p>
                <small class="text-muted">Hành khách: ${trip.passenger_count ?? (d.passengers||[]).length}</small>
            </div></div>`;
        const list = d.passengers || [];
        if (!list.length) {
            document.getElementById('passengers-tbody').innerHTML = '<tr><td colspan="5" class="text-center text-muted">Chưa có hành khách confirmed.</td></tr>';
            return;
        }
        document.getElementById('passengers-tbody').innerHTML = list.map(p => {
            const customer = p.customer || {};
            const seats = p.seats || [];
            const seatsHtml = seats.map(s => `${escapeHtml(s.seat_number)} ${s.is_checked_in ? '✓' : '○'}`).join(', ');
            return `<tr>
                <td><strong>${escapeHtml(p.booking_code)}</strong> ${statusBadge(p.status)}</td>
                <td>${escapeHtml(customer.name || '—')}<br><small>${escapeHtml(customer.phone || customer.email || '')}</small></td>
                <td>${seatsHtml}</td>
                <td>${p.is_fully_checked_in ? statusBadge('confirmed','Đủ') : statusBadge('pending_payment',`${p.checked_in_count}/${p.total_seat_count}`)}</td>
                <td class="table-actions">
                    <button class="btn btn-sm btn-success" onclick="checkInBooking(${p.booking_id})">All</button>
                    ${seats.filter(s => !s.is_checked_in).map(s => `<button class="btn btn-sm btn-outline-success" onclick="checkInItem(${s.booking_item_id})">${escapeHtml(s.seat_number)}</button>`).join('')}
                    ${seats.filter(s => s.is_checked_in).map(s => `<button class="btn btn-sm btn-outline-warning" onclick="undoCheckIn(${s.booking_item_id})">↩${escapeHtml(s.seat_number)}</button>`).join('')}
                </td>
            </tr>`;
        }).join('');
    } catch (e) { showToast(e.message, 'danger'); }
}

window.checkInBooking = async id => {
    try { await apiFetch(`/admin/bookings/${id}/check-in`, { method: 'POST' }); showToast('Check-in thành công.', 'success'); loadPassengers(); }
    catch (e) { showToast(e.message, 'danger'); }
};
window.checkInItem = async id => {
    try { await apiFetch(`/admin/booking-items/${id}/check-in`, { method: 'POST' }); showToast('Check-in ghế thành công.', 'success'); loadPassengers(); }
    catch (e) { showToast(e.message, 'danger'); }
};
window.undoCheckIn = async id => {
    const reason = prompt('Lý do hoàn tác check-in:');
    if (!reason) return;
    try { await apiFetch(`/admin/booking-items/${id}/undo-check-in`, { method: 'POST', body: { reason } }); showToast('Đã hoàn tác.', 'success'); loadPassengers(); }
    catch (e) { showToast(e.message, 'danger'); }
};
