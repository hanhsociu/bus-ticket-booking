@extends('layouts.admin')
@section('title', 'Loại xe')
@section('page-title', 'Quản lý loại xe')
@section('content')
<div class="mb-3 text-end"><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#busTypeModal" onclick="openBusTypeModal()"><i class="bx bx-plus"></i> Thêm loại xe</button></div>
<div class="card stat-card"><div class="table-responsive">
<table class="table table-hover mb-0"><thead><tr><th>Tên</th><th>Ghế</th><th>Trạng thái</th><th></th></tr></thead>
<tbody id="bus-types-tbody"></tbody></table></div></div>
<div class="modal fade" id="busTypeModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<div class="modal-header"><h5 class="modal-title" id="btModalTitle">Loại xe</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
<input type="hidden" id="bt-id">
<div class="mb-3"><label class="form-label">Tên</label><input id="bt-name" class="form-control"></div>
<div class="mb-3"><label class="form-label">Mô tả</label><textarea id="bt-desc" class="form-control"></textarea></div>
<div class="mb-3"><label class="form-label">Tổng ghế</label><input type="number" id="bt-seats" class="form-control" min="1"></div>
<div class="row g-2 mb-3"><div class="col-4"><label class="form-label">Tầng</label><input type="number" id="bt-floors" class="form-control" value="1"></div>
<div class="col-4"><label class="form-label">Hàng</label><input type="number" id="bt-rows" class="form-control" value="10"></div>
<div class="col-4"><label class="form-label">Cột</label><input type="number" id="bt-cols" class="form-control" value="4"></div></div>
<div class="form-check"><input type="checkbox" id="bt-active" class="form-check-input" checked><label class="form-check-label">Kích hoạt</label></div>
</div>
<div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button><button class="btn btn-primary" id="btn-save-bt">Lưu</button></div>
</div></div></div>
@endsection
@push('scripts')
<script src="{{ asset('js/admin/common.js') }}"></script>
<script src="{{ asset('js/admin/bus-types.js') }}"></script>
@endpush
