@extends('layouts.admin')
@section('title', 'Users')
@section('page-title', 'Quản lý người dùng')
@section('content')
<div class="row g-2 mb-3">
    <div class="col-md-4"><input type="text" id="search-q" class="form-control" placeholder="Tìm tên, email..."></div>
    <div class="col-md-2"><select id="filter-role" class="form-select"><option value="">Tất cả role</option><option value="admin">Admin</option><option value="customer">Customer</option></select></div>
</div>
<div class="card stat-card"><div class="table-responsive">
<table class="table table-hover mb-0"><thead><tr>
<th>Tên</th><th>Email</th><th>Role</th><th>TT</th><th>Bookings</th><th></th>
</tr></thead><tbody id="users-tbody"></tbody></table></div></div>
<div class="modal fade" id="userModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
<div class="modal-header"><h5>Chi tiết user</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body" id="user-detail-body"></div>
</div></div></div>
@endsection
@push('scripts')
<script src="{{ asset('js/admin/common.js') }}"></script>
<script src="{{ asset('js/admin/users.js') }}"></script>
@endpush
