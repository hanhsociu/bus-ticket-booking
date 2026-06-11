@extends('layouts.admin')
@section('title', 'Soát vé')
@section('page-title', 'Soát vé & Check-in')
@section('content')
<div class="ticket-verify-hero text-center">
    <h3 class="mb-2"><i class="bx bx-qr-scan"></i> Soát vé nhanh</h3>
    <p class="mb-4 opacity-75">Nhập mã booking để xác minh vé và check-in hành khách</p>
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <input type="text" id="ticket-code" class="form-control form-control-lg ticket-verify-input mb-3" placeholder="BK-XXXXXXXX" autofocus>
            <button class="btn btn-light btn-lg me-2" id="btn-verify"><i class="bx bx-search"></i> Kiểm tra vé</button>
            <button class="btn btn-success btn-lg d-none" id="btn-checkin"><i class="bx bx-check-circle"></i> Check-in</button>
        </div>
    </div>
</div>
<div id="ticket-result" class="d-none"></div>
@endsection
@push('styles')
<style>.bg-label-success{background:rgba(113,221,55,.16)!important}.bg-label-primary{background:rgba(105,108,255,.16)!important}.bg-label-warning{background:rgba(255,171,0,.16)!important}.bg-label-info{background:rgba(3,195,236,.16)!important}</style>
@endpush
@push('scripts')
<script src="{{ asset('js/admin/common.js') }}"></script>
<script src="{{ asset('js/admin/tickets.js') }}"></script>
@endpush
