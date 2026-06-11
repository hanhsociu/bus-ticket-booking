let userModal;
document.addEventListener('DOMContentLoaded', () => {
    if (!requireAdmin()) return;
    userModal = new bootstrap.Modal(document.getElementById('userModal'));
    ['search-q','filter-role'].forEach(id => document.getElementById(id)?.addEventListener('change', () => loadUsers()));
    document.getElementById('search-q')?.addEventListener('input', debounce(() => loadUsers(), 400));
    loadUsers();
});

function debounce(fn, ms) { let t; return () => { clearTimeout(t); t = setTimeout(fn, ms); }; }

async function loadUsers() {
    renderTableLoading('users-tbody');
    const q = buildQuery({
        q: document.getElementById('search-q')?.value,
        role: document.getElementById('filter-role')?.value,
    });
    try {
        const res = await apiFetch(`/admin/users${q}`);
        document.getElementById('users-tbody').innerHTML = (res.data.data || []).map(u => `
            <tr>
                <td><a href="#" onclick="showUser(${u.id});return false">${escapeHtml(u.name)}</a></td>
                <td>${escapeHtml(u.email)}</td>
                <td><span class="badge bg-${u.role === 'admin' ? 'primary' : 'secondary'}">${u.role}</span></td>
                <td>${u.is_active ? statusBadge('scheduled','Active') : statusBadge('cancelled','Locked')}</td>
                <td>${u.bookings_count ?? 0}</td>
                <td>
                    ${u.is_active
                        ? `<button class="btn btn-sm btn-outline-danger" onclick="lockUser(${u.id})">Khóa</button>`
                        : `<button class="btn btn-sm btn-outline-success" onclick="unlockUser(${u.id})">Mở khóa</button>`}
                </td>
            </tr>`).join('');
    } catch (e) { showToast(e.message, 'danger'); }
}

window.showUser = async id => {
    try {
        const res = await apiFetch(`/admin/users/${id}`);
        const u = res.data;
        document.getElementById('user-detail-body').innerHTML = `
            <p><strong>${escapeHtml(u.name)}</strong> · ${escapeHtml(u.email)} · ${escapeHtml(u.phone || '')}</p>
            <p>Role: ${u.role} · ${u.is_active ? 'Active' : 'Locked'}</p>
            <h6>Bookings gần đây</h6>
            ${(u.bookings||[]).map(b => `<div class="small border-bottom py-1">${escapeHtml(b.booking_code)} ${statusBadge(b.status)} — ${formatMoney(b.total_amount)}</div>`).join('') || '<p class="text-muted">—</p>'}`;
        userModal.show();
    } catch (e) { showToast(e.message, 'danger'); }
};

window.lockUser = async id => {
    if (!confirmAction('Khóa user này?')) return;
    try { await apiFetch(`/admin/users/${id}/lock`, { method: 'POST' }); showToast('Đã khóa.', 'success'); loadUsers(); }
    catch (e) { showToast(e.message, 'danger'); }
};
window.unlockUser = async id => {
    try { await apiFetch(`/admin/users/${id}/unlock`, { method: 'POST' }); showToast('Đã mở khóa.', 'success'); loadUsers(); }
    catch (e) { showToast(e.message, 'danger'); }
};
