let currentPage = 1;

document.addEventListener('DOMContentLoaded', async () => {
    const routeId = getQueryParam('route_id');
    const date = getQueryParam('date');
    if (routeId) document.getElementById('filter-route').value = routeId;
    if (date) document.getElementById('filter-date').value = date;

    try {
        const res = await apiFetch('/routes');
        (res.data || []).forEach(r => {
            document.getElementById('filter-route').insertAdjacentHTML('beforeend',
                `<option value="${r.id}">${escapeHtml(r.from_location)} → ${escapeHtml(r.to_location)}</option>`);
        });
    } catch {}

    document.getElementById('filter-form')?.addEventListener('change', () => {
        currentPage = 1;
        loadTrips();
    });
    document.getElementById('btn-refresh')?.addEventListener('click', () => loadTrips(currentPage));
    loadTrips();
});

function showLoading(show) {
    const skel = document.getElementById('trips-loading');
    const list = document.getElementById('trips-list');
    if (show) {
        skel.classList.remove('d-none');
        skel.innerHTML = skeletonCards(6);
        list.innerHTML = '';
    } else {
        skel.classList.add('d-none');
        skel.innerHTML = '';
    }
}

async function loadTrips(page = 1) {
    showLoading(true);
    document.getElementById('trips-pagination').innerHTML = '';
    const q = buildQuery({
        route_id: document.getElementById('filter-route').value,
        date: document.getElementById('filter-date').value,
        page,
    });
    try {
        const res = await apiFetch(`/trips${q}`);
        const paginated = res.data;
        const trips = paginated.data || paginated;
        showLoading(false);
        const list = document.getElementById('trips-list');
        if (!trips.length) {
            list.innerHTML = `<div class="col-12">${emptyState({
                icon: 'icon-bus',
                title: 'Hiện chưa có chuyến đang mở bán',
                text: 'Không tìm thấy chuyến phù hợp bộ lọc. Admin cần tạo chuyến có thời gian khởi hành trong tương lai để mở bán.',
                buttons: `
                    <a href="/" class="btn btn-outline-secondary btn-sm me-2">Về trang chủ</a>
                    <button class="btn btn-bb-primary btn-sm" onclick="document.getElementById('filter-route').value='';document.getElementById('filter-date').value='';loadTrips()">Xóa bộ lọc</button>`,
            })}</div>`;
            return;
        }
        list.innerHTML = trips.map(t => tripCard(t)).join('');
        renderPagination(paginated, page);
    } catch (e) {
        showLoading(false);
        document.getElementById('trips-list').innerHTML = `<div class="col-12">${emptyState({
            icon: 'icon-warning',
            title: 'Không tải được dữ liệu',
            text: e.message,
            buttons: '<button class="btn btn-bb-primary btn-sm" onclick="loadTrips()">Thử lại</button>',
        })}</div>`;
    }
}

function tripCard(t) {
    const route = t.route || {};
    const bus = t.bus || {};
    const avail = t.available_seats_count ?? 0;
    return `
    <div class="col-md-6 col-lg-4">
        <div class="bb-card bb-trip-card">
            <div class="bb-trip-card-header d-flex justify-content-between align-items-start">
                <div>
                    <div class="bb-trip-route">${escapeHtml(route.from_location || '')} → ${escapeHtml(route.to_location || '')}</div>
                    <div class="bb-trip-code">${escapeHtml(t.code)}</div>
                </div>
                ${statusBadge('scheduled', 'Đang mở bán')}
            </div>
            <div class="bb-card-body flex-grow-1 d-flex flex-column">
                <div class="bb-trip-time-row">
                    <div class="bb-trip-time-block">
                        <strong>${formatTime(t.departure_time)}</strong>
                        <small>${formatDate(t.departure_time)}</small>
                    </div>
                    <span class="bb-trip-arrow">→</span>
                    <div class="bb-trip-time-block text-end">
                        <strong>${formatTime(t.arrival_time)}</strong>
                        <small>Đến nơi</small>
                    </div>
                </div>
                <p class="bb-trip-meta mb-2">
                    <span class="icon-bus me-1"></span>${escapeHtml(bus.name || '—')}
                    <span class="mx-1">·</span>${escapeHtml(bus.license_plate || '')}
                </p>
                <div class="d-flex justify-content-between align-items-center mt-auto pt-2">
                    <div>
                        <div class="bb-trip-price">${formatMoney(t.base_price)}</div>
                        <small class="text-success fw-semibold">${avail} ghế trống</small>
                    </div>
                    <a href="/trips/${t.id}" class="btn btn-bb-primary">Chọn ghế</a>
                </div>
            </div>
        </div>
    </div>`;
}

function renderPagination(paginated, page) {
    const nav = document.getElementById('trips-pagination');
    if (!paginated.last_page || paginated.last_page <= 1) { nav.innerHTML = ''; return; }
    let html = '<ul class="pagination justify-content-center mb-0">';
    for (let i = 1; i <= paginated.last_page; i++) {
        html += `<li class="page-item ${i === page ? 'active' : ''}">
            <a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
    }
    html += '</ul>';
    nav.innerHTML = html;
    nav.querySelectorAll('[data-page]').forEach(a => a.addEventListener('click', e => {
        e.preventDefault();
        currentPage = parseInt(a.dataset.page);
        loadTrips(currentPage);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }));
}
