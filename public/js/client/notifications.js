document.addEventListener('DOMContentLoaded', async () => {
    if (!requireAuth()) return;
    await loadUnread();
    await loadNotifications();
    document.getElementById('btn-mark-all')?.addEventListener('click', markAll);
});

async function loadUnread() {
    try {
        const res = await apiFetch('/customer/notifications/unread-count');
        const c = res.data.unread_count || 0;
        const unreadLabel = document.getElementById('unread-label');
        const allReadLabel = document.getElementById('all-read-label');
        const countEl = document.getElementById('unread-count');
        if (c > 0) {
            countEl.textContent = c;
            unreadLabel?.classList.remove('d-none');
            allReadLabel?.classList.add('d-none');
        } else {
            unreadLabel?.classList.add('d-none');
            allReadLabel?.classList.remove('d-none');
        }
    } catch {}
}

async function loadNotifications() {
    const loading = document.getElementById('notifications-loading');
    const el = document.getElementById('notifications-list');
    loading?.classList.remove('d-none');
    try {
        const res = await apiFetch('/customer/notifications');
        const items = res.data.data || res.data || [];
        loading?.classList.add('d-none');
        if (!items.length) {
            el.innerHTML = emptyState({
                icon: 'icon-bell',
                title: 'Chưa có thông báo',
                text: 'Thông báo về xác nhận vé, hoàn tiền sẽ hiển thị tại đây.',
                buttons: '<a href="/customer/dashboard" class="btn btn-outline-primary btn-sm">Về dashboard</a>',
            });
            return;
        }
        el.innerHTML = items.map(n => notifCard(n)).join('');
        el.querySelectorAll('.btn-mark-read').forEach(btn => {
            btn.addEventListener('click', () => markRead(btn.dataset.id, btn.closest('[data-id]')));
        });
    } catch (e) {
        loading?.classList.add('d-none');
        showToast(e.message, 'danger');
    }
}

function notifCard(n) {
    const ni = notifIcon(n.type);
    const isUnread = !n.read_at;
    return `
    <div class="bb-card mb-3 bb-notif-item ${isUnread ? 'unread' : ''}" data-id="${n.id}">
        <div class="bb-card-body d-flex gap-3 align-items-start">
            <div class="bb-notif-icon ${ni.bg}"><span class="${ni.icon}"></span></div>
            <div class="flex-grow-1 min-w-0">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                    <strong>${escapeHtml(n.title)}</strong>
                    ${isUnread ? '<span class="badge bg-primary">Mới</span>' : '<span class="badge bg-secondary">Đã đọc</span>'}
                </div>
                <p class="text-muted small mb-1">${escapeHtml(n.message)}</p>
                <small class="text-muted">${formatDateTime(n.created_at)}${n.read_at ? ' · Đọc lúc ' + formatDateTime(n.read_at) : ''}</small>
            </div>
            ${isUnread ? `<button class="btn btn-sm btn-outline-primary btn-mark-read flex-shrink-0" data-id="${n.id}">Đã đọc</button>` : ''}
        </div>
    </div>`;
}

async function markRead(id, cardEl) {
    const btn = cardEl?.querySelector('.btn-mark-read');
    if (btn) setLoading(btn, true, '...');
    try {
        await apiFetch(`/customer/notifications/${id}/mark-as-read`, { method: 'POST' });
        if (cardEl) {
            cardEl.classList.remove('unread');
            const badge = cardEl.querySelector('.badge.bg-primary');
            if (badge) {
                badge.className = 'badge bg-secondary';
                badge.textContent = 'Đã đọc';
            }
            btn?.remove();
        }
        await loadUnread();
        showToast('Đã đánh dấu đã đọc.', 'success');
    } catch (e) {
        showToast(e.message, 'danger');
        if (btn) setLoading(btn, false);
    }
}

async function markAll() {
    const btn = document.getElementById('btn-mark-all');
    setLoading(btn, true);
    try {
        const res = await apiFetch('/customer/notifications/mark-all-as-read', { method: 'POST' });
        showToast(`Đã đánh dấu ${res.data?.updated_count ?? ''} thông báo.`, 'success');
        await loadUnread();
        await loadNotifications();
    } catch (e) { showToast(e.message, 'danger'); }
    finally { setLoading(btn, false); }
}
