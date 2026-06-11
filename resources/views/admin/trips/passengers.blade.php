@extends('layouts.admin')
@section('title', 'Hành khách')
@section('page-title', 'Danh sách hành khách')
@section('content')
<div id="trip-info" class="mb-4"></div>
<div class="card stat-card"><div class="table-responsive">
<table class="table table-hover mb-0"><thead><tr>
<th>Booking</th><th>Khách</th><th>Ghế</th><th>Check-in</th><th></th>
</tr></thead><tbody id="passengers-tbody"></tbody></table></div></div>
@endsection
@push('scripts')
<script>window.TRIP_ID = {{ (int) $tripId }};</script>
<script src="{{ asset('js/admin/common.js') }}"></script>
<script src="{{ asset('js/admin/passengers.js') }}"></script>
@endpush
