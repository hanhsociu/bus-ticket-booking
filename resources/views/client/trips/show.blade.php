@extends('layouts.client')
@section('title', 'Chọn ghế')
@section('content')
<div class="bb-page">
    <div class="container">
        <div id="trip-loading" class="text-center py-5">
            <div class="spinner-border text-primary"></div>
            <p class="text-muted mt-2 small">Đang tải sơ đồ ghế...</p>
        </div>
        <div id="trip-content" class="d-none">
            <div class="bb-page-header">
                <a href="/trips" class="text-muted small text-decoration-none mb-2 d-inline-block">← Quay lại danh sách</a>
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                    <div>
                        <h1 class="bb-page-title mb-1" id="trip-title"></h1>
                        <div id="trip-meta" class="bb-page-subtitle"></div>
                    </div>
                    <div class="text-end">
                        <div class="text-muted small">Giá mỗi ghế</div>
                        <div class="bb-trip-price" id="trip-price"></div>
                    </div>
                </div>
            </div>

            <div id="login-cta" class="bb-login-cta mb-4 d-none">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div>
                        <strong>Đăng nhập để đặt vé</strong>
                        <p class="mb-0 small text-muted">Bạn cần tài khoản để giữ ghế và thanh toán.</p>
                    </div>
                    <a href="/login" id="login-cta-link" class="btn btn-warning btn-sm">Đăng nhập ngay</a>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="seat-map-container">
                        <div class="seat-map-bus-front">▲ PHÍA TRƯỚC XE — TÀI XẾ</div>
                        <div class="seat-legend">
                            <span><span class="dot" style="background:#fff;border-color:#e2e8f0"></span> Còn trống</span>
                            <span><span class="dot" style="background:#0ea5e9;border-color:#0ea5e9"></span> Đang chọn</span>
                            <span><span class="dot" style="background:#fef3c7;border-color:#f59e0b"></span> Đang giữ</span>
                            <span><span class="dot" style="background:#e2e8f0;border-color:#94a3b8"></span> Đã đặt</span>
                            <span><span class="dot" style="background:#f1f5f9;border-color:#cbd5e1"></span> Khóa</span>
                        </div>
                        <div id="seat-map" class="seat-grid"></div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="bb-card bb-summary-card">
                        <div class="bb-card-body">
                            <h5 class="fw-bold mb-3">Tóm tắt đặt vé</h5>
                            <div id="summary-route" class="small text-muted mb-3"></div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Ghế đã chọn</span>
                                <strong id="selected-count">0</strong>
                            </div>
                            <div id="selected-seats" class="mb-3 small text-muted">Chưa chọn ghế nào</div>
                            <hr>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="fw-semibold">Tổng thanh toán</span>
                                <span class="bb-trip-price" id="total-amount">0 đ</span>
                            </div>
                            <div class="alert alert-warning py-2 px-3 small mb-3">
                                <span class="icon-clock me-1"></span>
                                Ghế được <strong>giữ 10 phút</strong> sau khi đặt. Vui lòng thanh toán kịp thời.
                            </div>
                            <button id="btn-book" class="btn btn-bb-primary w-100 btn-lg" disabled>
                                Giữ ghế &amp; thanh toán
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>window.TRIP_ID = {{ (int) $tripId }};</script>
<script src="{{ asset('js/client/trip-show.js') }}"></script>
@endpush
