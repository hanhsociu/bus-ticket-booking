@extends('layouts.admin')
@section('title', 'Xe')
@section('page-title', 'Quản lý xe')
@section('content')
<div class="mb-3 text-end"><button class="btn btn-primary" onclick="openBusModal()"><i class="bx bx-plus"></i> Thêm xe</button></div>
<div class="card stat-card"><div class="table-responsive">
<table class="table table-hover mb-0"><thead><tr><th>Tên</th><th>Biển số</th><th>Loại xe</th><th>Trạng thái</th><th></th></tr></thead>
<tbody id="buses-tbody"></tbody></table></div></div>
<div class="modal fade" id="busModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<div class="modal-header"><h5 class="modal-title">Xe</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
<input type="hidden" id="bus-id">
<div class="mb-3"><label class="form-label">Loại xe</label><select id="bus-type-id" class="form-select"></select></div>
<div class="mb-3"><label class="form-label">Tên xe</label><input id="bus-name" class="form-control"></div>
<div class="mb-3"><label class="form-label">Biển số</label><input id="bus-plate" class="form-control"></div>
<div class="form-check"><input type="checkbox" id="bus-active" class="form-check-input" checked><label class="form-check-label">Kích hoạt</label></div>
</div>
<div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button><button class="btn btn-primary" id="btn-save-bus">Lưu</button></div>
</div></div></div>
@endsection
@push('scripts')
<script src="{{ asset('js/admin/common.js') }}"></script>
<script src="{{ asset('js/admin/buses.js') }}"></script>
@endpush
