let tripModal;
document.addEventListener('DOMContentLoaded', async () => {
    if (!requireAdmin()) return;
    tripModal = new bootstrap.Modal(document.getElementById('tripModal'));
    document.getElementById('btn-save-trip').addEventListener('click', saveTrip);
    const [routes, buses] = await Promise.all([
        apiFetch('/admin/routes?per_page=100').catch(() => ({ data: { data: [] } })),
        apiFetch('/admin/buses?per_page=100').catch(() => ({ data: { data: [] } })),
    ]);
    document.getElementById('trip-route').innerHTML = (routes.data.data || []).filter(r => r.is_active).map(r =>
        `<option value="${r.id}">${escapeHtml(r.from_location)} → ${escapeHtml(r.to_location)}</option>`).join('');
    document.getElementById('trip-bus').innerHTML = (buses.data.data || []).filter(b => b.is_active).map(b =>
        `<option value="${b.id}">${escapeHtml(b.name)} (${escapeHtml(b.license_plate)})</option>`).join('');
    loadTrips();
});

async function loadTrips() {
    renderTableLoading('trips-tbody');
    try {
        const res = await apiFetch('/admin/trips');
        const trips = res.data?.data ?? (Array.isArray(res.data) ? res.data : []);
        document.getElementById('trips-tbody').innerHTML = trips.map(t => {
            const r = t.route || {};
            return `<tr>
                <td><a href="/admin/trips/${t.id}/passengers">${escapeHtml(t.code)}</a></td>
                <td>${escapeHtml(r.from_location || '')} → ${escapeHtml(r.to_location || '')}</td>
                <td>${escapeHtml(t.bus?.name || '')}</td>
                <td>${formatDateTime(t.departure_time)}</td>
                <td>${formatMoney(t.base_price)}</td>
                <td>${statusBadge(t.status)}</td>
                <td class="table-actions">
                    ${t.status === 'scheduled' ? `<button class="btn btn-sm btn-warning" onclick="tripAction(${t.id},'depart')">Depart</button>
                    <button class="btn btn-sm btn-danger" onclick="tripAction(${t.id},'cancel')">Cancel</button>` : ''}
                    ${t.status === 'departed' ? `<button class="btn btn-sm btn-primary" onclick="tripAction(${t.id},'complete')">Complete</button>` : ''}
                    <a href="/admin/trips/${t.id}/passengers" class="btn btn-sm btn-outline-info">HK</a>
                </td></tr>`;
        }).join('');
    } catch (e) { showToast(e.message, 'danger'); }
}

window.openTripModal = () => tripModal.show();

async function saveTrip() {
    const body = {
        route_id: +document.getElementById('trip-route').value,
        bus_id: +document.getElementById('trip-bus').value,
        departure_time: document.getElementById('trip-depart').value,
        arrival_time: document.getElementById('trip-arrive').value,
        base_price: +document.getElementById('trip-price').value,
    };
    try {
        await apiFetch('/admin/trips', { method: 'POST', body });
        tripModal.hide(); showToast('Tạo chuyến thành công.', 'success'); loadTrips();
    } catch (e) { showToast(e.message, 'danger'); }
}

window.tripAction = async (id, action) => {
    if (!confirmAction()) return;
    const path = action === 'cancel' ? `/admin/trips/${id}/cancel` : `/admin/trips/${id}/${action}`;
    try {
        await apiFetch(path, { method: 'POST' });
        showToast('Thành công.', 'success'); loadTrips();
    } catch (e) { showToast(e.message, 'danger'); }
};
