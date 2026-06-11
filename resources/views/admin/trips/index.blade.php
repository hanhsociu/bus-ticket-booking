@extends('layouts.admin')
@section('title', 'Chuyến xe')
@section('page-title', 'Quản lý chuyến xe')
@section('content')
<div class="mb-3 text-end"><button class="btn btn-primary" onclick="openTripModal()"><i class="bx bx-plus"></i> Tạo chuyến</button></div>
<div class="card stat-card"><div class="table-responsive">
<table class="table table-hover mb-0"><thead><tr>
<th>Mã</th><th>Tuyến</th><th>Xe</th><th>Giờ đi</th><th>Giá</th><th>TT</th><th></th>
</tr></thead><tbody id="trips-tbody"></tbody></table></div></div>
<div class="modal fade" id="tripModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
<div class="modal-header"><h5>Tạo chuyến xe</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
<div class="row g-3">
<div class="col-md-6"><label class="form-label">Tuyến</label><select id="trip-route" class="form-select"></select></div>
<div class="col-md-6"><label class="form-label">Xe</label><select id="trip-bus" class="form-select"></select></div>
<div class="col-md-6"><label class="form-label">Giờ đi</label><input type="datetime-local" id="trip-depart" class="form-control"></div>
<div class="col-md-6"><label class="form-label">Giờ đến</label><input type="datetime-local" id="trip-arrive" class="form-control"></div>
<div class="col-md-6"><label class="form-label">Giá vé</label><input type="number" id="trip-price" class="form-control" min="1000"></div>
</div></div>
<div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button><button class="btn btn-primary" id="btn-save-trip">Tạo</button></div>
</div></div></div>
@endsection
@push('scripts')
<script src="{{ asset('js/admin/common.js') }}"></script>
<script src="{{ asset('js/admin/trips.js') }}"></script>
@endpush
