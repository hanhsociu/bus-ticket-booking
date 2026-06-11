<!DOCTYPE html>
<html lang="vi" class="h-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'BusBook') — Đặt vé xe trực tuyến</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/client/fonts/icomoon/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/custom-client.css') }}">
    @stack('styles')
</head>
<body class="bb-body d-flex flex-column min-vh-100">
<div id="toast-container"></div>

@include('partials.client.nav')

<main class="bb-main flex-grow-1">
    @yield('content')
</main>

@include('partials.client.footer')

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/utils.js') }}"></script>
<script src="{{ asset('js/api.js') }}"></script>
<script src="{{ asset('js/auth.js') }}"></script>
@stack('scripts')
</body>
</html>
