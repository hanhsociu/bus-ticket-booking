@extends('layouts.client')
@section('title', 'Thông báo')
@section('content')
<div class="bb-page">
    <div class="container">
        <div class="bb-page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h1 class="bb-page-title">Thông báo</h1>
                <p class="bb-page-subtitle">
                    <span id="unread-label" class="d-none">Bạn có <strong id="unread-count">0</strong> thông báo chưa đọc</span>
                    <span id="all-read-label">Cập nhật về vé và hoàn tiền</span>
                </p>
            </div>
            <button class="btn btn-outline-primary btn-sm" id="btn-mark-all">
                <span class="icon-check me-1"></span> Đánh dấu tất cả đã đọc
            </button>
        </div>

        <div id="notifications-loading" class="text-center py-5 d-none">
            <div class="spinner-border text-primary spinner-border-sm"></div>
        </div>
        <div id="notifications-list"></div>
    </div>
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/client/notifications.js') }}"></script>
@endpush
