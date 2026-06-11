<header class="bb-navbar sticky-top">
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand bb-brand" href="{{ url('/') }}">
                <span class="bb-brand-icon"><span class="icon-bus"></span></span>
                Bus<span class="bb-brand-accent">Book</span>
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#bbNav" aria-label="Menu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="bbNav">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('/') ? 'active' : '' }}" href="{{ url('/') }}">Trang chủ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('trips*') ? 'active' : '' }}" href="{{ url('/trips') }}">Chuyến xe</a>
                    </li>
                    <li class="nav-item d-none" id="nav-customer-dashboard">
                        <a class="nav-link {{ request()->is('customer/dashboard') ? 'active' : '' }}" href="{{ url('/customer/dashboard') }}">Dashboard</a>
                    </li>
                    <li class="nav-item d-none" id="nav-my-bookings">
                        <a class="nav-link {{ request()->is('customer/bookings*') ? 'active' : '' }}" href="{{ url('/customer/bookings') }}">Vé của tôi</a>
                    </li>
                    <li class="nav-item d-none" id="nav-notifications">
                        <a class="nav-link {{ request()->is('customer/notifications') ? 'active' : '' }}" href="{{ url('/customer/notifications') }}">
                            Thông báo
                            <span class="badge rounded-pill bg-danger bb-nav-badge d-none" id="nav-unread-badge">0</span>
                        </a>
                    </li>
                    <li class="nav-item d-none" id="nav-admin">
                        <a class="nav-link" href="{{ url('/admin/dashboard') }}">Admin</a>
                    </li>
                    <li class="nav-item d-none" id="nav-login">
                        <a class="nav-link" href="{{ url('/login') }}">Đăng nhập</a>
                    </li>
                    <li class="nav-item d-none" id="nav-register">
                        <a class="btn btn-outline-light btn-sm ms-lg-2" href="{{ url('/register') }}">Đăng ký</a>
                    </li>
                    <li class="nav-item d-none" id="nav-logout">
                        <a class="nav-link text-warning" href="#" onclick="apiLogout(); return false;">Đăng xuất</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</header>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const loggedIn = typeof isLoggedIn === 'function' && isLoggedIn();
    const admin = typeof isAdmin === 'function' && isAdmin();
    const show = id => document.getElementById(id)?.classList.remove('d-none');
    const hide = id => document.getElementById(id)?.classList.add('d-none');
    if (loggedIn) {
        show('nav-customer-dashboard');
        show('nav-my-bookings');
        show('nav-notifications');
        show('nav-logout');
        hide('nav-login');
        hide('nav-register');
        if (admin) show('nav-admin');
        if (typeof apiFetch === 'function') {
            apiFetch('/customer/notifications/unread-count').then(r => {
                const c = r.data?.unread_count || 0;
                const b = document.getElementById('nav-unread-badge');
                if (b && c > 0) {
                    b.textContent = c > 99 ? '99+' : c;
                    b.classList.remove('d-none');
                }
            }).catch(() => {});
        }
    } else {
        show('nav-login');
        show('nav-register');
    }
});
</script>
