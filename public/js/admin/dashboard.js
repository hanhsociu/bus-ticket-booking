document.addEventListener('DOMContentLoaded', async () => {
    if (!requireAdmin()) return;
    try {
        const res = await apiFetch('/admin/dashboard/overview');
        const d = res.data;
        const s = d.summary || {};
        document.getElementById('summary-row').innerHTML = [
            ['Tổng doanh thu', formatMoney(s.total_revenue), 'success', 'bx-dollar'],
            ['Doanh thu hôm nay', formatMoney(s.today_revenue), 'primary', 'bx-trending-up'],
            ['Tổng booking', s.total_bookings, 'info', 'bx-book-bookmark'],
            ['Booking hôm nay', s.today_bookings, 'warning', 'bx-calendar'],
            ['Đã xác nhận', s.confirmed_bookings, 'success', 'bx-check'],
            ['Chờ thanh toán', s.pending_bookings, 'warning', 'bx-time'],
        ].map(([t, v, c, icon]) => `
            <div class="col-md-6 col-xl-4 col-xxl-2">
                <div class="card stat-card"><div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div><p class="mb-1 text-muted small">${t}</p><h4 class="mb-0">${v}</h4></div>
                        <div class="stat-icon bg-label-${c} text-${c}"><i class="bx ${icon}"></i></div>
                    </div>
                </div></div>
            </div>`).join('');

        const bs = d.booking_status || {};
        document.getElementById('booking-status-list').innerHTML = Object.entries(bs).map(([k, v]) =>
            `<div class="d-flex justify-content-between py-1"><span>${statusBadge(k)}</span><strong>${v}</strong></div>`
        ).join('');

        const chartData = d.charts?.booking_by_day || [];
        if (chartData.length && window.Chart) {
            new Chart(document.getElementById('chart-bookings'), {
                type: 'line',
                data: {
                    labels: chartData.map(x => x.date),
                    datasets: [{ label: 'Bookings', data: chartData.map(x => x.count), borderColor: '#696cff', tension: 0.3 }],
                },
                options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } },
            });
        }

        document.getElementById('recent-bookings').innerHTML = (d.recent_bookings || []).map(b =>
            `<div class="d-flex justify-content-between border-bottom py-2">
                <span><strong>${escapeHtml(b.booking_code)}</strong> ${statusBadge(b.status)}</span>
                <span>${formatMoney(b.total_amount)}</span>
            </div>`).join('') || '<p class="text-muted">—</p>';

        document.getElementById('upcoming-trips').innerHTML = (d.upcoming_trips || []).map(t => {
            const r = t.route || {};
            return `<div class="border-bottom py-2">
                <strong>${escapeHtml(t.code)}</strong> ${statusBadge(t.status)}
                <div class="small text-muted">${escapeHtml(r.from_location)} → ${escapeHtml(r.to_location)} · ${formatDateTime(t.departure_time)}</div>
            </div>`;
        }).join('') || '<p class="text-muted">—</p>';
    } catch (e) { showToast(e.message, 'danger'); }
});
