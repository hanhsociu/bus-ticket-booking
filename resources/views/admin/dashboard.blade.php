@extends('layouts.admin')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('content')
<div class="row g-4 mb-4" id="summary-row"></div>
<div class="row g-4 mb-4">
    <div class="col-lg-8"><div class="card stat-card"><div class="card-body">
        <h6 class="mb-3">Booking theo ngày</h6><canvas id="chart-bookings" height="120"></canvas>
    </div></div></div>
    <div class="col-lg-4"><div class="card stat-card"><div class="card-body">
        <h6 class="mb-3">Trạng thái booking</h6><div id="booking-status-list"></div>
    </div></div></div>
</div>
<div class="row g-4">
    <div class="col-lg-6"><div class="card stat-card"><div class="card-body">
        <h6>Booking gần đây</h6><div id="recent-bookings"></div>
    </div></div></div>
    <div class="col-lg-6"><div class="card stat-card"><div class="card-body">
        <h6>Chuyến sắp khởi hành</h6><div id="upcoming-trips"></div>
    </div></div></div>
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/admin/common.js') }}"></script>
<script src="{{ asset('js/admin/dashboard.js') }}"></script>
@endpush
