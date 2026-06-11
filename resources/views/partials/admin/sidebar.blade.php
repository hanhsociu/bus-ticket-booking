<aside class="admin-sidebar">
    <div class="brand">
        <i class="bx bx-bus text-primary" style="font-size:1.75rem"></i>
        <span class="brand-text">BusBook</span>
    </div>
    <ul class="admin-menu">
        <li class="menu-header">Tổng quan</li>
        <li><a href="{{ url('/admin/dashboard') }}" data-path="/admin/dashboard"><i class="bx bx-home-circle"></i> Dashboard</a></li>

        <li class="menu-header">Vận hành</li>
        <li><a href="{{ url('/admin/routes') }}" data-path="/admin/routes"><i class="bx bx-map"></i> Tuyến xe</a></li>
        <li><a href="{{ url('/admin/bus-types') }}" data-path="/admin/bus-types"><i class="bx bx-grid-alt"></i> Loại xe</a></li>
        <li><a href="{{ url('/admin/buses') }}" data-path="/admin/buses"><i class="bx bx-bus-school"></i> Xe</a></li>
        <li><a href="{{ url('/admin/trips') }}" data-path="/admin/trips"><i class="bx bx-trip"></i> Chuyến xe</a></li>
        <li><a href="{{ url('/admin/bookings') }}" data-path="/admin/bookings"><i class="bx bx-book-bookmark"></i> Booking</a></li>
        <li><a href="{{ url('/admin/refunds') }}" data-path="/admin/refunds"><i class="bx bx-undo"></i> Hoàn vé</a></li>
        <li><a href="{{ url('/admin/tickets') }}" data-path="/admin/tickets"><i class="bx bx-qr-scan"></i> Soát vé</a></li>

        <li class="menu-header">Hệ thống</li>
        <li><a href="{{ url('/admin/users') }}" data-path="/admin/users"><i class="bx bx-user"></i> Người dùng</a></li>
        <li><a href="{{ url('/') }}" target="_blank"><i class="bx bx-globe"></i> Xem website</a></li>
    </ul>
</aside>
