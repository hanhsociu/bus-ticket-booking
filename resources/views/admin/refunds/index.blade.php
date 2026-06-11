@extends('layouts.admin')
@section('title', 'Hoàn vé')
@section('page-title', 'Quản lý hoàn vé')
@section('content')
<div class="mb-3"><input type="text" id="search-q" class="form-control w-auto d-inline-block" placeholder="Tìm kiếm..."></div>
<div class="card stat-card"><div class="table-responsive">
<table class="table table-hover mb-0"><thead><tr>
<th>Mã vé</th><th>Khách</th><th>Tuyến</th><th>Tiền</th><th>TT</th><th></th>
</tr></thead><tbody id="refunds-tbody"></tbody></table></div></div>
<div class="modal fade" id="refundModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<div class="modal-header"><h5 id="refundModalTitle">Xử lý hoàn vé</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><input type="hidden" id="refund-booking-id">
<label class="form-label" id="refund-label">Ghi chú / Lý do</label>
<textarea id="refund-note" class="form-control" rows="3"></textarea></div>
<div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button><button class="btn btn-primary" id="btn-refund-submit">Xác nhận</button></div>
</div></div></div>
@endsection
@push('scripts')
<script src="{{ asset('js/admin/common.js') }}"></script>
<script src="{{ asset('js/admin/refunds.js') }}"></script>
@endpush
