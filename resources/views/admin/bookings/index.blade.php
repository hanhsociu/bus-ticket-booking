@extends('layouts.admin')
@section('title', 'Booking')
@section('page-title', 'Quản lý booking')
@section('content')
<div class="row g-2 mb-3">
    <div class="col-md-3"><input type="text" id="search-q" class="form-control" placeholder="Mã vé, tên, email..."></div>
    <div class="col-md-2"><select id="filter-status" class="form-select">
        <option value="">Tất cả TT</option>
        <option value="pending_payment">Chờ TT</option>
        <option value="confirmed">Confirmed</option>
        <option value="cancelled">Cancelled</option>
        <option value="expired">Expired</option>
        <option value="refund_requested">Refund req</option>
        <option value="refunded">Refunded</option>
    </select></div>
    <div class="col-md-2"><input type="date" id="filter-date" class="form-control"></div>
</div>
<div class="card stat-card"><div class="table-responsive">
<table class="table table-hover mb-0"><thead><tr>
<th>Mã</th><th>Khách</th><th>Tuyến</th><th>Tiền</th><th>TT</th><th></th>
</tr></thead><tbody id="bookings-tbody"></tbody></table></div>
<div class="card-footer" id="bookings-pagination"></div></div>
<div class="modal fade" id="bookingDetailModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
<div class="modal-header"><h5>Chi tiết booking</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body" id="booking-detail-body"></div>
</div></div></div>
@endsection
@push('scripts')
<script src="{{ asset('js/admin/common.js') }}"></script>
<script src="{{ asset('js/admin/bookings.js') }}"></script>
@endpush
