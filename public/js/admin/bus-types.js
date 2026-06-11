let btModal;
document.addEventListener('DOMContentLoaded', () => {
    if (!requireAdmin()) return;
    btModal = new bootstrap.Modal(document.getElementById('busTypeModal'));
    document.getElementById('btn-save-bt').addEventListener('click', saveBusType);
    loadBusTypes();
});

async function loadBusTypes() {
    renderTableLoading('bus-types-tbody');
    try {
        const res = await apiFetch('/admin/bus-types');
        document.getElementById('bus-types-tbody').innerHTML = (res.data.data || []).map(b => `
            <tr><td>${escapeHtml(b.name)}</td><td>${b.total_seats}</td>
            <td>${b.is_active ? statusBadge('scheduled','Active') : statusBadge('cancelled','Inactive')}</td>
            <td class="table-actions">
                <button class="btn btn-sm btn-outline-primary" onclick='editBusType(${JSON.stringify(b).replace(/'/g,"&#39;")})'>Sửa</button>
                <button class="btn btn-sm btn-outline-success" onclick="generateSeats(${b.id})">Generate seats</button>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteBusType(${b.id})">Xóa</button>
            </td></tr>`).join('');
    } catch (e) { showToast(e.message, 'danger'); }
}

window.openBusTypeModal = () => {
    document.getElementById('bt-id').value = '';
    document.getElementById('bt-name').value = '';
    document.getElementById('bt-desc').value = '';
    document.getElementById('bt-seats').value = '40';
    document.getElementById('bt-floors').value = '1';
    document.getElementById('bt-rows').value = '10';
    document.getElementById('bt-cols').value = '4';
    document.getElementById('bt-active').checked = true;
};

window.editBusType = b => {
    document.getElementById('bt-id').value = b.id;
    document.getElementById('bt-name').value = b.name;
    document.getElementById('bt-desc').value = b.description || '';
    document.getElementById('bt-seats').value = b.total_seats;
    const layout = b.seat_layout || {};
    document.getElementById('bt-floors').value = layout.floors || 1;
    document.getElementById('bt-rows').value = layout.rows || 10;
    document.getElementById('bt-cols').value = layout.columns || 4;
    document.getElementById('bt-active').checked = !!b.is_active;
    btModal.show();
};

async function saveBusType() {
    const id = document.getElementById('bt-id').value;
    const body = {
        name: document.getElementById('bt-name').value,
        description: document.getElementById('bt-desc').value,
        total_seats: parseInt(document.getElementById('bt-seats').value),
        seat_layout: { floors: +document.getElementById('bt-floors').value, rows: +document.getElementById('bt-rows').value, columns: +document.getElementById('bt-cols').value },
        is_active: document.getElementById('bt-active').checked,
    };
    try {
        if (id) await apiFetch(`/admin/bus-types/${id}`, { method: 'PUT', body });
        else await apiFetch('/admin/bus-types', { method: 'POST', body });
        btModal.hide(); showToast('Lưu thành công.', 'success'); loadBusTypes();
    } catch (e) { showToast(e.message, 'danger'); }
}

window.generateSeats = async id => {
    if (!confirmAction('Generate seats cho loại xe này?')) return;
    try {
        await apiFetch(`/admin/bus-types/${id}/generate-seats`, { method: 'POST' });
        showToast('Đã generate seats.', 'success');
    } catch (e) { showToast(e.message, 'danger'); }
};

window.deleteBusType = async id => {
    if (!confirmAction()) return;
    try { await apiFetch(`/admin/bus-types/${id}`, { method: 'DELETE' }); showToast('Đã xóa.', 'success'); loadBusTypes(); }
    catch (e) { showToast(e.message, 'danger'); }
};
