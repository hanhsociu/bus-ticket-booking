let routeModal;

document.addEventListener('DOMContentLoaded', () => {
    if (!requireAdmin()) return;
    routeModal = new bootstrap.Modal(document.getElementById('routeModal'));
    document.getElementById('btn-save-route').addEventListener('click', saveRoute);
    document.getElementById('search-q')?.addEventListener('input', debounce(() => loadRoutes(), 400));
    loadRoutes();
});

function debounce(fn, ms) { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; }

async function loadRoutes(page = 1) {
    renderTableLoading('routes-tbody');
    const q = document.getElementById('search-q')?.value || '';
    try {
        const res = await apiFetch(`/admin/routes${buildQuery({ q, page })}`);
        const p = res.data;
        document.getElementById('routes-tbody').innerHTML = (p.data || []).map(r => `
            <tr>
                <td>${escapeHtml(r.code)}</td>
                <td>${escapeHtml(r.from_location)}</td>
                <td>${escapeHtml(r.to_location)}</td>
                <td>${r.distance_km || '—'}</td>
                <td>${r.is_active ? statusBadge('scheduled', 'Active') : statusBadge('cancelled', 'Inactive')}</td>
                <td class="table-actions">
                    <button class="btn btn-sm btn-outline-primary" onclick='editRoute(${JSON.stringify(r)})'>Sửa</button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteRoute(${r.id})">Xóa</button>
                </td>
            </tr>`).join('');
        document.getElementById('routes-pagination').innerHTML = paginateLinks(p, loadRoutes);
    } catch (e) { showToast(e.message, 'danger'); }
}

window.openRouteModal = function() {
    document.getElementById('routeModalTitle').textContent = 'Thêm tuyến';
    document.getElementById('route-id').value = '';
    ['route-code','route-from','route-to','route-km','route-mins'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('route-active').checked = true;
};

window.editRoute = function(r) {
    document.getElementById('routeModalTitle').textContent = 'Sửa tuyến';
    document.getElementById('route-id').value = r.id;
    document.getElementById('route-code').value = r.code;
    document.getElementById('route-from').value = r.from_location;
    document.getElementById('route-to').value = r.to_location;
    document.getElementById('route-km').value = r.distance_km || '';
    document.getElementById('route-mins').value = r.estimated_duration_minutes || '';
    document.getElementById('route-active').checked = !!r.is_active;
    routeModal.show();
};

async function saveRoute() {
    const id = document.getElementById('route-id').value;
    const body = {
        code: document.getElementById('route-code').value,
        from_location: document.getElementById('route-from').value,
        to_location: document.getElementById('route-to').value,
        distance_km: document.getElementById('route-km').value || null,
        estimated_duration_minutes: document.getElementById('route-mins').value || null,
        is_active: document.getElementById('route-active').checked,
    };
    try {
        if (id) {
            await apiFetch(`/admin/routes/${id}`, { method: 'PUT', body });
        } else {
            await apiFetch('/admin/routes', { method: 'POST', body });
        }
        routeModal.hide();
        showToast('Lưu thành công.', 'success');
        loadRoutes();
    } catch (e) { showToast(e.message, 'danger'); }
}

window.deleteRoute = async function(id) {
    if (!confirmAction('Xóa tuyến này?')) return;
    try {
        await apiFetch(`/admin/routes/${id}`, { method: 'DELETE' });
        showToast('Đã xóa.', 'success');
        loadRoutes();
    } catch (e) { showToast(e.message, 'danger'); }
};
