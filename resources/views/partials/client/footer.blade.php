<footer class="bb-footer mt-auto">
    <div class="container">
        <div class="row g-4 py-5">
            <div class="col-lg-5">
                <div class="bb-brand bb-brand-footer mb-3">
                    <span class="bb-brand-icon"><span class="icon-bus"></span></span>
                    Bus<span class="bb-brand-accent">Book</span>
                </div>
                <p class="bb-footer-text mb-0">Hệ thống đặt vé xe trực tuyến — tìm chuyến, chọn ghế, thanh toán PayOS, hoàn vé & soát vé bằng mã booking.</p>
            </div>
            <div class="col-6 col-lg-3">
                <h6 class="bb-footer-heading">Khám phá</h6>
                <ul class="list-unstyled bb-footer-links">
                    <li><a href="{{ url('/trips') }}">Tìm chuyến xe</a></li>
                    <li><a href="{{ url('/customer/bookings') }}">Vé của tôi</a></li>
                    <li><a href="{{ url('/customer/dashboard') }}">Dashboard</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-2">
                <h6 class="bb-footer-heading">Tài khoản</h6>
                <ul class="list-unstyled bb-footer-links">
                    <li><a href="{{ url('/login') }}">Đăng nhập</a></li>
                    <li><a href="{{ url('/register') }}">Đăng ký</a></li>
                </ul>
            </div>
            <div class="col-lg-2">
                <h6 class="bb-footer-heading">Hỗ trợ</h6>
                <p class="bb-footer-text small mb-0">Giữ ghế 10 phút · PayOS · Hoàn vé trước giờ đi</p>
            </div>
        </div>
        <div class="bb-footer-bottom text-center py-3">
            <p class="footer-attribution mb-1">
                UI dựa trên <a href="https://untree.co/" target="_blank" rel="noopener">Tour</a> by
                <a href="https://untree.co/" target="_blank" rel="noopener">Untree.co</a>
                — <a href="https://creativecommons.org/licenses/by/3.0/" target="_blank" rel="noopener">CC BY 3.0</a>
            </p>
            <p class="mb-0 small opacity-75">&copy; {{ date('Y') }} BusBook</p>
        </div>
    </div>
</footer>
