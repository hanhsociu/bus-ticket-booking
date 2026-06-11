const selected = new Set();
let tripData = null;
let basePrice = 0;

document.addEventListener('DOMContentLoaded', () => {
    if (typeof isLoggedIn === 'function' && !isLoggedIn()) {
        const cta = document.getElementById('login-cta');
        const link = document.getElementById('login-cta-link');
        cta?.classList.remove('d-none');
        if (link) link.href = `/login?redirect=${encodeURIComponent(window.location.pathname)}`;
    }
    loadTrip();
});

async function loadTrip() {
    try {
        const [tripRes, seatsRes] = await Promise.all([
            apiFetch(`/trips/${window.TRIP_ID}`),
            apiFetch(`/trips/${window.TRIP_ID}/seats`),
        ]);
        tripData = tripRes.data;
        basePrice = parseFloat(tripData.base_price) || 0;
        const route = tripData.route || {};
        const title = `${route.from_location} → ${route.to_location}`;
        document.getElementById('trip-title').textContent = title;
        document.getElementById('trip-meta').innerHTML = `
            Mã chuyến <strong>${escapeHtml(tripData.code)}</strong>
            · ${formatDateTime(tripData.departure_time)} → ${formatDateTime(tripData.arrival_time)}
            · Xe: ${escapeHtml(tripData.bus?.name || '')} (${escapeHtml(tripData.bus?.license_plate || '')})`;
        document.getElementById('trip-price').textContent = formatMoney(basePrice);
        document.getElementById('summary-route').textContent = title;
        renderSeats(seatsRes.data.seats || []);
        document.getElementById('trip-loading').classList.add('d-none');
        document.getElementById('trip-content').classList.remove('d-none');
    } catch (e) {
        document.getElementById('trip-loading').innerHTML = emptyState({
            icon: 'icon-warning',
            title: 'Không tải được chuyến xe',
            text: e.message,
            buttons: '<a href="/trips" class="btn btn-bb-primary btn-sm">Quay lại danh sách</a>',
        });
    }
}

function renderSeats(seats) {
    const map = {};
    seats.forEach(s => {
        const row = s.seat_row || 0;
        if (!map[row]) map[row] = [];
        map[row].push(s);
    });
    const rows = Object.keys(map).sort((a, b) => a - b);
    document.getElementById('seat-map').innerHTML = rows.length
        ? rows.map(row => `
            <div class="seat-row">${map[row].sort((a, b) => (a.seat_column || 0) - (b.seat_column || 0)).map(seatBtn).join('')}</div>
        `).join('')
        : emptyState({ icon: 'icon-bus', title: 'Chưa có ghế', text: 'Chuyến xe chưa được cấu hình sơ đồ ghế.' });
}

function seatBtn(s) {
    const disabled = s.status !== 'available';
    const cls = disabled ? s.status : 'available';
    return `<button type="button" class="seat-btn ${cls}" data-id="${s.trip_seat_id}" data-num="${escapeHtml(s.seat_number)}" ${disabled ? 'disabled' : ''} title="${STATUS_LABELS[s.status] || s.status}">${escapeHtml(s.seat_number)}</button>`;
}

document.getElementById('seat-map')?.addEventListener('click', e => {
    const btn = e.target.closest('.seat-btn.available, .seat-btn.selected');
    if (!btn || btn.disabled) return;
    const id = parseInt(btn.dataset.id);
    if (selected.has(id)) {
        selected.delete(id);
        btn.classList.remove('selected');
        btn.classList.add('available');
    } else {
        selected.add(id);
        btn.classList.add('selected');
        btn.classList.remove('available');
    }
    updateSummary();
});

function updateSummary() {
    const nums = [...document.querySelectorAll('.seat-btn.selected')].map(b => b.dataset.num);
    document.getElementById('selected-count').textContent = selected.size;
    document.getElementById('selected-seats').innerHTML = nums.length
        ? nums.map(n => `<span class="badge bg-primary me-1">${escapeHtml(n)}</span>`).join('')
        : 'Chưa chọn ghế nào';
    document.getElementById('total-amount').textContent = formatMoney(basePrice * selected.size);
    document.getElementById('btn-book').disabled = selected.size === 0;
}

document.getElementById('btn-book')?.addEventListener('click', async () => {
    if (!isLoggedIn()) {
        showToast('Vui lòng đăng nhập để đặt vé.', 'warning');
        window.location.href = `/login?redirect=${encodeURIComponent(window.location.pathname)}`;
        return;
    }
    const btn = document.getElementById('btn-book');
    setLoading(btn, true, 'Đang giữ ghế...');
    try {
        const res = await apiFetch('/bookings', {
            method: 'POST',
            body: { trip_id: window.TRIP_ID, trip_seat_ids: [...selected] },
        });
        showToast('Giữ ghế thành công! Chuyển sang thanh toán...', 'success');
        window.location.href = `/customer/bookings/${res.data.id}/payment`;
    } catch (e) {
        showToast(e.message, 'danger');
        setLoading(btn, false);
    }
});
