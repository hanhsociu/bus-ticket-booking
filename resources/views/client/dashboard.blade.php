@extends('layouts.client')
@section('title', 'Dashboard')
@section('content')
<div class="bb-page">
    <div class="container">
        <div class="bb-page-header d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <h1 class="bb-page-title">Xin chào, <span id="user-name" class="text-primary">...</span></h1>
                <p class="bb-page-subtitle">Tổng quan vé và thông báo của bạn</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="/trips" class="bb-quick-link"><span class="icon-search"></span> Tìm chuyến</a>
                <a href="/customer/bookings" class="bb-quick-link"><span class="icon-ticket"></span> Vé của tôi</a>
                <a href="/customer/notifications" class="bb-quick-link"><span class="icon-bell"></span> Thông báo</a>
            </div>
        </div>

        <div id="dashboard-loading" class="text-center py-5">
            <div class="spinner-border text-primary"></div>
        </div>

        <div id="dashboard-content" class="d-none">
            <div class="row g-3 mb-4" id="summary-cards"></div>
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="bb-card h-100">
                        <div class="bb-card-body">
                            <h5 class="fw-bold mb-3"><span class="icon-calendar me-1"></span> Vé sắp đi</h5>
                            <div id="upcoming-list"></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="bb-card h-100">
                        <div class="bb-card-body">
                            <h5 class="fw-bold mb-3"><span class="icon-clock me-1"></span> Chờ thanh toán</h5>
                            <div id="pending-list"></div>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="bb-card">
                        <div class="bb-card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="fw-bold mb-0"><span class="icon-bell me-1"></span> Thông báo gần đây</h5>
                                <a href="/customer/notifications" class="btn btn-sm btn-outline-primary">Xem tất cả</a>
                            </div>
                            <div id="notifications-list"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/client/dashboard.js') }}"></script>
@endpush
