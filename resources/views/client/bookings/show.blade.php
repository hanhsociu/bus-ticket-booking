@extends('layouts.client')
@section('title', 'Chi tiết vé')
@section('content')
<div class="bb-page">
    <div class="container">
        <div id="booking-loading" class="text-center py-5">
            <div class="spinner-border text-primary"></div>
            <p class="text-muted mt-2 small">Đang tải thông tin vé...</p>
        </div>
        <div id="booking-content" class="d-none">
            <a href="/customer/bookings" class="text-muted small text-decoration-none mb-3 d-inline-block">← Vé của tôi</a>

            <div class="bb-ticket-box mb-4">
                <p class="text-muted small mb-2">MÃ VÉ — DÙNG ĐỂ SOÁT VÉ TẠI BẾN</p>
                <div class="booking-code-display" id="booking-code"></div>
                <p class="bb-ticket-hint">Xuất trình mã này khi lên xe hoặc tại quầy soát vé</p>
                <div class="mt-3" id="booking-status"></div>
            </div>

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="bb-card mb-4">
                        <div class="bb-card-body" id="trip-info"></div>
                    </div>
                    <div class="bb-card mb-4">
                        <div class="bb-card-body">
                            <h5 class="fw-bold mb-3">Ghế đã đặt</h5>
                            <div id="seats-info"></div>
                        </div>
                    </div>
                    <div class="bb-card">
                        <div class="bb-card-body">
                            <h5 class="fw-bold mb-3">Lịch sử</h5>
                            <div id="histories-info"></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="bb-card mb-4">
                        <div class="bb-card-body">
                            <h5 class="fw-bold mb-3">Thanh toán</h5>
                            <div id="payments-info"></div>
                        </div>
                    </div>
                    <div id="status-alert" class="mb-3"></div>
                    <div id="action-buttons" class="d-grid gap-2"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="refundModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Yêu cầu hoàn vé</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small">Yêu cầu sẽ được gửi tới admin. Chỉ áp dụng cho vé chưa check-in, chuyến chưa khởi hành.</p>
                <label class="form-label fw-semibold">Lý do hoàn vé <span class="text-danger">*</span></label>
                <textarea id="refund-reason" class="form-control" rows="4" placeholder="Ví dụ: Thay đổi kế hoạch, không thể đi được..."></textarea>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-bb-primary" id="btn-submit-refund">Gửi yêu cầu</button>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>window.BOOKING_ID = {{ (int) $bookingId }};</script>
<script src="{{ asset('js/client/booking-show.js') }}"></script>
@endpush
