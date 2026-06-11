@extends('layouts.admin')
@section('title', 'Tuyến xe')
@section('page-title', 'Quản lý tuyến xe')
@section('content')
<div class="d-flex justify-content-between mb-3">
    <input type="text" id="search-q" class="form-control w-auto" placeholder="Tìm kiếm...">
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#routeModal" onclick="openRouteModal()"><i class="bx bx-plus"></i> Thêm tuyến</button>
</div>
<div class="card stat-card"><div class="table-responsive">
    <table class="table table-hover mb-0"><thead><tr>
        <th>Mã</th><th>Điểm đi</th><th>Điểm đến</th><th>Km</th><th>Trạng thái</th><th></th>
    </tr></thead><tbody id="routes-tbody"></tbody></table>
</div><div class="card-footer" id="routes-pagination"></div></div>
@include('admin.partials.route-modal')
@endsection
@push('scripts')
<script src="{{ asset('js/admin/common.js') }}"></script>
<script src="{{ asset('js/admin/routes.js') }}"></script>
@endpush
