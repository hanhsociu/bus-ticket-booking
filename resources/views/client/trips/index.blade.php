@extends('layouts.client')
@section('title', 'Chuyến xe')
@section('content')
<div class="bb-page">
    <div class="container">
        <div class="bb-page-header">
            <h1 class="bb-page-title">Chuyến xe đang mở bán</h1>
            <p class="bb-page-subtitle">Chọn chuyến phù hợp và đặt ghế trực tuyến</p>
        </div>

        <div class="bb-filter-bar">
            <form id="filter-form" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small fw-semibold text-muted">Tuyến xe</label>
                    <select id="filter-route" class="form-select">
                        <option value="">Tất cả tuyến</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold text-muted">Ngày khởi hành</label>
                    <input type="date" id="filter-date" class="form-control" min="{{ date('Y-m-d') }}">
                </div>
                <div class="col-md-3">
                    <button type="button" id="btn-refresh" class="btn btn-outline-secondary w-100">
                        <span class="icon-refresh"></span> Làm mới
                    </button>
                </div>
            </form>
        </div>

        <div id="trips-loading" class="row d-none"></div>
        <div class="row g-4" id="trips-list"></div>
        <nav id="trips-pagination" class="mt-4"></nav>
    </div>
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/client/trips.js') }}"></script>
@endpush
