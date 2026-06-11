let currentFilter = '';
let currentPage = 1;

document.addEventListener('DOMContentLoaded', () => {
    if (!requireAuth()) return;
    document.querySelectorAll('#status-filters [data-status]').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('#status-filters .btn').forEach(b => {
                b.classList.remove('btn-primary');
                b.classList.add('btn-outline-secondary');
            });
            btn.classList.remove('btn-outline-secondary');
            btn.classList.add('btn-primary');
            currentFilter = btn.dataset.status;
            currentPage = 1;
            loadBookings();
        });
    });
    loadBookings();
});

async function loadBookings(page = 1) {
    const loading = document.getElementById('bookings-loading');
    const list = document.getElementById('bookings-list');
    loading.classList.remove('d-none');
    loading.innerHTML = '<div class="bb-skeleton" style="height:120px;margin-bottom:1rem"></div>'.repeat(3);
    list.innerHTML = '';
    try {
        const res = await apiFetch(`/my/bookings?page=${page}`);
        const paginated = res.data;
        let bookings = paginated.data || [];
        if (currentFilter) bookings = bookings.filter(b => b.status === currentFilter);
        loading.classList.add('d-none');
        if (!bookings.length) {
            list.innerHTML = emptyState({
                icon: 'icon-ticket',
                title: currentFilter ? 'Không có vé ở trạng thái này' : 'Bạn chưa có vé nào',
                text: currentFilter
                    ? 'Thử chọn bộ lọc khác hoặc đặt vé mới.'
                    : 'Hãy tìm chuyến xe và đặt vé để bắt đầu hành trình của bạn.',
                buttons: `
                    <a href="/trips" class="btn btn-bb-primary btn-sm me-2">Tìm chuyến xe</a>
                    ${currentFilter ? '<button class="btn btn-outline-secondary btn-sm" onclick="document.querySelector(\'[data-status=\\\'\\\']\').click()">Xem tất cả</button>' : ''}`,
            });
            document.getElementById('bookings-pagination').innerHTML = '';
            return;
        }
        list.innerHTML = bookings.map(b => bookingCard(b)).join('');
        renderPagination(paginated, page);
    } catch (e) {
        loading.classList.add('d-none');
        showToast(e.message, 'danger');
    }
}

function bookingCard(b) {
    const route = b.trip?.route;
    const seats = (b.items || []).map(i => i.seat_number).join(', ');
    const dep = b.trip?.departure_time;
    return `
    <div class="bb-card bb-booking-card mb-3">
        <div class="bb-card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div class="flex-grow-1">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <a href="/customer/bookings/${b.id}" class="bb-booking-code text-decoration-none h5 mb-0">${escapeHtml(b.booking_code)}</a>
                        ${statusBadge(b.status)}
                    </div>
                    <p class="mb-1 fw-semibold">${route ? escapeHtml(route.from_location) + ' → ' + escapeHtml(route.to_location) : '—'}</p>
                    <div class="small text-muted">
                        ${dep ? `<span class="me-3"><span class="icon-calendar me-1"></span>${formatDateTime(dep)}</span>` : ''}
                        <span class="me-3">Ghế: <strong>${escapeHtml(seats || '—')}</strong></span>
                        <span>${formatMoney(b.total_amount)}</span>
                    </div>
                </div>
                <div class="d-flex gap-2 flex-shrink-0">
                    ${b.status === 'pending_payment' ? `<a href="/customer/bookings/${b.id}/payment" class="btn btn-warning btn-sm">Thanh toán ngay</a>` : ''}
                    <a href="/customer/bookings/${b.id}" class="btn btn-outline-primary btn-sm">Chi tiết</a>
                </div>
            </div>
        </div>
    </div>`;
}

function renderPagination(paginated, page) {
    const nav = document.getElementById('bookings-pagination');
    if (!paginated.last_page || paginated.last_page <= 1) { nav.innerHTML = ''; return; }
    let html = '<ul class="pagination justify-content-center mb-0">';
    for (let i = 1; i <= paginated.last_page; i++) {
        html += `<li class="page-item ${i === page ? 'active' : ''}">
            <a class="page-link" href="#" data-p="${i}">${i}</a></li>`;
    }
    html += '</ul>';
    nav.innerHTML = html;
    nav.querySelectorAll('[data-p]').forEach(a => a.addEventListener('click', e => {
        e.preventDefault();
        currentPage = parseInt(a.dataset.p);
        loadBookings(currentPage);
    }));
}
