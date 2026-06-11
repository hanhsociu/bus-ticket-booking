@extends('layouts.client')
@section('title', 'Vé của tôi')
@section('content')
<div class="bb-page">
    <div class="container">
        <div class="bb-page-header">
            <h1 class="bb-page-title">Vé của tôi</h1>
            <p class="bb-page-subtitle">Quản lý và theo dõi tất cả booking của bạn</p>
        </div>

        <div class="bb-filter-pills mb-4 d-flex flex-wrap gap-2" id="status-filters">
            <button type="button" class="btn btn-sm btn-primary" data-status="">Tất cả</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-status="pending_payment">Chờ thanh toán</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-status="confirmed">Đã xác nhận</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-status="refund_requested">Hoàn vé</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-status="cancelled">Đã hủy</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-status="expired">Hết hạn</button>
        </div>

        <div id="bookings-loading" class="d-none"></div>
        <div id="bookings-list"></div>
        <nav id="bookings-pagination" class="mt-4"></nav>
    </div>
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/client/bookings.js') }}"></script>
@endpush
