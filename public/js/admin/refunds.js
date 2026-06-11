let refundModal, refundAction = 'approve';
document.addEventListener('DOMContentLoaded', () => {
    if (!requireAdmin()) return;
    refundModal = new bootstrap.Modal(document.getElementById('refundModal'));
    document.getElementById('btn-refund-submit').addEventListener('click', submitRefund);
    document.getElementById('search-q')?.addEventListener('input', debounce(() => loadRefunds(), 400));
    loadRefunds();
});

function debounce(fn, ms) { let t; return () => { clearTimeout(t); t = setTimeout(fn, ms); }; }

async function loadRefunds() {
    renderTableLoading('refunds-tbody');
    const q = document.getElementById('search-q')?.value || '';
    try {
        const res = await apiFetch(`/admin/refunds${buildQuery({ q })}`);
        document.getElementById('refunds-tbody').innerHTML = (res.data.data || []).map(b => {
            const r = b.trip?.route || {};
            return `<tr>
                <td>${escapeHtml(b.booking_code)}</td>
                <td>${escapeHtml(b.user?.name || '')}</td>
                <td>${escapeHtml(r.from_location || '')} → ${escapeHtml(r.to_location || '')}</td>
                <td>${formatMoney(b.total_amount)}</td>
                <td>${statusBadge(b.status)}</td>
                <td>${b.status === 'refund_requested' ? `
                    <button class="btn btn-sm btn-success" onclick="openRefund(${b.id},'approve')">Duyệt</button>
                    <button class="btn btn-sm btn-danger" onclick="openRefund(${b.id},'reject')">Từ chối</button>` : '—'}</td>
            </tr>`;
        }).join('');
    } catch (e) { showToast(e.message, 'danger'); }
}

window.openRefund = (id, action) => {
    refundAction = action;
    document.getElementById('refund-booking-id').value = id;
    document.getElementById('refundModalTitle').textContent = action === 'approve' ? 'Duyệt hoàn vé' : 'Từ chối hoàn vé';
    document.getElementById('refund-label').textContent = action === 'approve' ? 'Ghi chú (tuỳ chọn)' : 'Lý do từ chối *';
    document.getElementById('refund-note').value = '';
    refundModal.show();
};

async function submitRefund() {
    const id = document.getElementById('refund-booking-id').value;
    const note = document.getElementById('refund-note').value.trim();
    if (refundAction === 'reject' && !note) return showToast('Vui lòng nhập lý do.', 'warning');
    const path = refundAction === 'approve' ? `/admin/bookings/${id}/approve-refund` : `/admin/bookings/${id}/reject-refund`;
    const body = refundAction === 'approve' ? { note: note || null } : { reason: note };
    try {
        await apiFetch(path, { method: 'POST', body });
        refundModal.hide();
        showToast('Xử lý thành công.', 'success');
        loadRefunds();
    } catch (e) { showToast(e.message, 'danger'); }
}
