document.addEventListener('DOMContentLoaded', async () => {
    if (!requireAuth()) return;
    try {
        await apiFetch(`/bookings/${window.BOOKING_ID}`);
        const payRes = await apiFetch('/payments/payos/create', {
            method: 'POST',
            body: { booking_id: window.BOOKING_ID },
        });
        const d = payRes.data;
        document.getElementById('pay-booking-code').textContent = d.booking_code;
        document.getElementById('pay-amount').textContent = formatMoney(d.amount);
        document.getElementById('pay-expired').textContent = formatDateTime(d.expired_at);
        const link = document.getElementById('pay-checkout-link');
        if (d.checkout_url) {
            link.href = d.checkout_url;
        } else {
            link.classList.add('d-none');
        }
        if (d.qr_code) {
            document.getElementById('qr-container').innerHTML = `
                <div class="bb-qr-frame">
                    <img src="${escapeHtml(d.qr_code)}" alt="QR PayOS" class="img-fluid" style="max-width:240px">
                    <p class="small text-muted mt-2 mb-0">Quét mã bằng app ngân hàng</p>
                </div>`;
        }
        document.getElementById('payment-loading').classList.add('d-none');
        document.getElementById('payment-content').classList.remove('d-none');
    } catch (e) {
        document.getElementById('payment-loading').innerHTML = emptyState({
            icon: 'icon-warning',
            title: 'Không tạo được thanh toán',
            text: e.message,
            buttons: `<a href="/customer/bookings/${window.BOOKING_ID}" class="btn btn-bb-primary btn-sm">Quay lại vé</a>`,
        });
    }
});
