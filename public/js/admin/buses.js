let busModal, busTypes = [];
document.addEventListener('DOMContentLoaded', async () => {
    if (!requireAdmin()) return;
    busModal = new bootstrap.Modal(document.getElementById('busModal'));
    document.getElementById('btn-save-bus').addEventListener('click', saveBus);
    try {
        const res = await apiFetch('/admin/bus-types?per_page=100');
        busTypes = res.data.data || res.data || [];
        document.getElementById('bus-type-id').innerHTML = busTypes.map(t => `<option value="${t.id}">${escapeHtml(t.name)}</option>`).join('');
    } catch {}
    loadBuses();
});

async function loadBuses() {
    renderTableLoading('buses-tbody');
    try {
        const res = await apiFetch('/admin/buses');
        document.getElementById('buses-tbody').innerHTML = (res.data.data || []).map(b => `
            <tr><td>${escapeHtml(b.name)}</td><td>${escapeHtml(b.license_plate)}</td>
            <td>${escapeHtml(b.bus_type?.name || b.bus_type_id)}</td>
            <td>${b.is_active ? statusBadge('scheduled','Active') : statusBadge('cancelled','Inactive')}</td>
            <td><button class="btn btn-sm btn-outline-primary" onclick='editBus(${JSON.stringify(b).replace(/'/g,"&#39;")})'>Sửa</button>
            <button class="btn btn-sm btn-outline-danger" onclick="deleteBus(${b.id})">Xóa</button></td></tr>`).join('');
    } catch (e) { showToast(e.message, 'danger'); }
}

window.openBusModal = () => {
    document.getElementById('bus-id').value = '';
    document.getElementById('bus-name').value = '';
    document.getElementById('bus-plate').value = '';
    document.getElementById('bus-active').checked = true;
    busModal.show();
};

window.editBus = b => {
    document.getElementById('bus-id').value = b.id;
    document.getElementById('bus-type-id').value = b.bus_type_id;
    document.getElementById('bus-name').value = b.name;
    document.getElementById('bus-plate').value = b.license_plate;
    document.getElementById('bus-active').checked = !!b.is_active;
    busModal.show();
};

async function saveBus() {
    const id = document.getElementById('bus-id').value;
    const body = {
        bus_type_id: +document.getElementById('bus-type-id').value,
        name: document.getElementById('bus-name').value,
        license_plate: document.getElementById('bus-plate').value,
        is_active: document.getElementById('bus-active').checked,
    };
    try {
        if (id) await apiFetch(`/admin/buses/${id}`, { method: 'PUT', body });
        else await apiFetch('/admin/buses', { method: 'POST', body });
        busModal.hide(); showToast('Lưu thành công.', 'success'); loadBuses();
    } catch (e) { showToast(e.message, 'danger'); }
}

window.deleteBus = async id => {
    if (!confirmAction()) return;
    try { await apiFetch(`/admin/buses/${id}`, { method: 'DELETE' }); showToast('Đã xóa.', 'success'); loadBuses(); }
    catch (e) { showToast(e.message, 'danger'); }
};
