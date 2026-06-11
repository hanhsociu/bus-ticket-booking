@extends('layouts.client')
@section('title', 'Trang chủ')

@section('content')
<section class="bb-hero">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-lg-7">
                <span class="bb-hero-badge">🚌 Đặt vé xe liên tỉnh — nhanh & an toàn</span>
                <h1 class="mb-3">Đặt vé xe <span class="text-info">trực tuyến</span><br>chỉ vài phút</h1>
                <p class="lead opacity-90 mb-4">Tìm chuyến, chọn ghế yêu thích, thanh toán PayOS — hệ thống giữ ghế 10 phút cho bạn.</p>
                <div class="d-flex flex-wrap gap-2 mb-4">
                    <a href="#search-form" class="btn btn-bb-primary btn-lg">Tìm chuyến ngay</a>
                    <a href="{{ url('/customer/bookings') }}" class="btn btn-outline-light btn-lg" id="hero-my-tickets">Vé của tôi</a>
                </div>
            </div>
            <div class="col-lg-5 d-none d-lg-block">
                <div class="bb-hero-visual">
                    <span class="icon-bus d-block"></span>
                    <div class="bb-hero-road"></div>
                    <p class="mb-0 small opacity-75">Hành trình của bạn bắt đầu từ đây</p>
                </div>
            </div>
        </div>
        <div class="row mt-4">
            <div class="col-12">
                <form id="search-form" class="bb-search-card">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label" for="from-route">Điểm đi / Tuyến</label>
                            <select id="from-route" class="form-select"><option value="">Tất cả tuyến</option></select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="search-date">Ngày khởi hành</label>
                            <input type="date" id="search-date" class="form-control" min="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-bb-primary btn-lg w-100">
                                <span class="icon-search me-1"></span> Tìm chuyến xe
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="text-center mb-4">
            <h2 class="bb-section-title">Tuyến phổ biến</h2>
            <p class="text-muted">Các tuyến xe được tìm kiếm nhiều nhất</p>
        </div>
        <div class="row g-4" id="popular-routes">
            <div class="col-12 text-center py-4"><div class="spinner-border text-primary spinner-border-sm"></div></div>
        </div>
    </div>
</section>

<section class="py-5 bg-white">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="bb-section-title">Vì sao chọn BusBook?</h2>
        </div>
        <div class="row g-4">
            @foreach([
                ['icon-clock', 'Giữ ghế 10 phút', 'Đủ thời gian thanh toán mà không mất ghế đã chọn.'],
                ['icon-credit-card', 'Thanh toán PayOS', 'Quét QR hoặc mở link — xác nhận vé tự động.'],
                ['icon-undo', 'Hoàn vé linh hoạt', 'Yêu cầu hoàn vé trước giờ khởi hành, admin duyệt nhanh.'],
                ['icon-check', 'Soát vé bằng mã', 'Xuất trình mã booking — check-in tại bến dễ dàng.'],
            ] as $f)
            <div class="col-md-6 col-lg-3 text-center">
                <div class="bb-feature-icon"><span class="{{ $f[0] }}"></span></div>
                <h5 class="fw-bold">{{ $f[1] }}</h5>
                <p class="text-muted small mb-0">{{ $f[2] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="bb-section-title">Quy trình đặt vé</h2>
            <p class="text-muted">4 bước đơn giản để có vé trên tay</p>
        </div>
        <div class="row g-4">
            @foreach([
                ['1', 'Tìm chuyến', 'Chọn tuyến & ngày đi phù hợp'],
                ['2', 'Chọn ghế', 'Xem sơ đồ ghế, chọn vị trí ưa thích'],
                ['3', 'Thanh toán', 'PayOS — giữ ghế trong 10 phút'],
                ['4', 'Lên xe', 'Xuất mã vé — soát vé tại bến'],
            ] as $step)
            <div class="col-6 col-lg-3">
                <div class="bb-flow-step bb-card bb-card-body h-100">
                    <div class="bb-flow-num">{{ $step[0] }}</div>
                    <h6 class="fw-bold">{{ $step[1] }}</h6>
                    <p class="text-muted small mb-0">{{ $step[2] }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const heroTickets = document.getElementById('hero-my-tickets');
    if (heroTickets && typeof isLoggedIn === 'function' && !isLoggedIn()) {
        heroTickets.href = '/login?redirect=' + encodeURIComponent('/customer/bookings');
    }
});
</script>
<script src="{{ asset('js/client/home.js') }}"></script>
@endpush
