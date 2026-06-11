<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') | BusBook Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link href="{{ asset('css/custom-admin.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body class="admin-body">
<div id="toast-container"></div>
<div class="admin-wrapper">
    @include('partials.admin.sidebar')
    <div class="admin-main">
        <header class="admin-navbar">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-sm btn-outline-secondary d-lg-none" id="sidebar-toggle" type="button">
                    <i class="bx bx-menu"></i>
                </button>
                <h5 class="mb-0">@yield('page-title', 'Dashboard')</h5>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="text-muted small" id="admin-user-name"></span>
                <button class="btn btn-sm btn-outline-danger" onclick="apiLogout()">
                    <i class="bx bx-log-out"></i> Đăng xuất
                </button>
            </div>
        </header>
        <main class="admin-content">
            @yield('content')
        </main>
        <footer class="admin-footer text-center">
            © {{ date('Y') }} BusBook Admin —
            Made with <a href="https://themeselection.com/" target="_blank" rel="noopener">Sneat</a> free template by ThemeSelection
        </footer>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="{{ asset('js/utils.js') }}"></script>
<script src="{{ asset('js/api.js') }}"></script>
<script src="{{ asset('js/auth.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (!requireAdmin()) return;
    const u = getUser();
    const el = document.getElementById('admin-user-name');
    if (el && u) el.textContent = u.name || u.email;
    document.getElementById('sidebar-toggle')?.addEventListener('click', () => {
        document.querySelector('.admin-sidebar')?.classList.toggle('show');
    });
    const path = window.location.pathname;
    document.querySelectorAll('.admin-menu a[data-path]').forEach(a => {
        if (path === a.getAttribute('data-path') || path.startsWith(a.getAttribute('data-path') + '/')) {
            a.classList.add('active');
        }
    });
});
</script>
@stack('scripts')
</body>
</html>
