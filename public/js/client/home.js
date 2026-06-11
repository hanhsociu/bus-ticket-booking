document.addEventListener('DOMContentLoaded', async () => {
    try {
        const res = await apiFetch('/routes');
        const routes = res.data || [];
        const fromSel = document.getElementById('from-route');
        routes.forEach(r => {
            const label = `${r.from_location} → ${r.to_location}`;
            fromSel.insertAdjacentHTML('beforeend',
                `<option value="${r.id}">${escapeHtml(label)}</option>`);
        });
        renderPopular(routes.slice(0, 4));
    } catch (e) {
        document.getElementById('popular-routes').innerHTML = emptyState({
            icon: 'icon-warning',
            title: 'Không tải được tuyến',
            text: e.message,
            buttons: '<button class="btn btn-outline-primary btn-sm" onclick="location.reload()">Thử lại</button>',
        });
    }

    document.getElementById('search-form')?.addEventListener('submit', e => {
        e.preventDefault();
        const routeId = document.getElementById('from-route').value;
        const date = document.getElementById('search-date').value;
        window.location.href = `/trips${buildQuery({ route_id: routeId, date })}`;
    });
});

function renderPopular(routes) {
    const el = document.getElementById('popular-routes');
    if (!routes.length) {
        el.innerHTML = emptyState({
            icon: 'icon-map',
            title: 'Chưa có tuyến nào',
            text: 'Admin cần tạo tuyến xe đang hoạt động để hiển thị tại đây.',
            buttons: `<a href="/trips" class="btn btn-bb-primary btn-sm">Xem tất cả chuyến</a>`,
        });
        return;
    }
    el.innerHTML = routes.map(r => `
        <div class="col-md-6 col-lg-3">
            <div class="bb-card bb-route-card bb-card-body h-100">
                <div class="bb-route-card-icon"><span class="icon-bus"></span></div>
                <h6 class="fw-bold mb-1">${escapeHtml(r.from_location)}</h6>
                <p class="text-muted small mb-2">→ ${escapeHtml(r.to_location)}</p>
                <p class="text-muted small mb-3">${escapeHtml(r.code)}${r.distance_km ? ` · ${r.distance_km} km` : ''}</p>
                <a href="/trips?route_id=${r.id}" class="btn btn-bb-primary btn-sm w-100">Xem chuyến</a>
            </div>
        </div>`).join('');
}
