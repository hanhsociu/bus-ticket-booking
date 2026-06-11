@extends('layouts.client')
@section('title', 'Thanh toán')
@section('content')
<div class="bb-page">
    <div class="container">
        <a href="/customer/bookings/{{ $bookingId }}" class="text-muted small text-decoration-none mb-3 d-inline-block">← Chi tiết vé</a>

        <div id="payment-loading" class="text-center py-5">
            <div class="spinner-border text-primary"></div>
            <p class="text-muted mt-2 small">Đang tạo link thanh toán PayOS...</p>
        </div>

        <div id="payment-content" class="d-none">
            <div class="bb-card bb-payment-card">
                <div class="bb-card-body p-4 p-md-5 text-center">
                    <div class="mb-3">
                        <span class="badge bg-primary-subtle text-primary px-3 py-2">Thanh toán PayOS</span>
                    </div>
                    <p class="text-muted small mb-1">Mã booking</p>
                    <div class="booking-code-display mb-3" id="pay-booking-code" style="font-size:1.5rem"></div>
                    <p class="h3 fw-bold text-primary mb-4" id="pay-amount"></p>

                    <div class="alert alert-warning text-start small mb-4">
                        <strong><span class="icon-clock"></span> Giữ ghế 10 phút</strong>
                        <p class="mb-0 mt-1">Hết hạn thanh toán: <strong id="pay-expired"></strong>. Sau thời gian này ghế sẽ tự động được nhả.</p>
                    </div>

                    <div id="qr-container" class="mb-4"></div>

                    <a id="pay-checkout-link" href="#" target="_blank" rel="noopener" class="btn btn-bb-primary btn-lg w-100 mb-3">
                        Mở trang thanh toán PayOS
                    </a>

                    <div class="bb-payment-steps mb-4">
                        <strong class="d-block mb-2 small">Hướng dẫn thanh toán</strong>
                        <ol class="mb-0 ps-3">
                            <li>Nhấn nút trên hoặc quét mã QR bằng app ngân hàng</li>
                            <li>Hoàn tất chuyển khoản trên PayOS</li>
                            <li>Hệ thống tự xác nhận vé — kiểm tra tại "Vé của tôi"</li>
                        </ol>
                    </div>

                    <a href="/customer/bookings/{{ $bookingId }}" class="btn btn-outline-secondary">Quay lại chi tiết vé</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>window.BOOKING_ID = {{ (int) $bookingId }};</script>
<script src="{{ asset('js/client/payment.js') }}"></script>
@endpush
