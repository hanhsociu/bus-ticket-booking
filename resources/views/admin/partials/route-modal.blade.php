<div class="modal fade" id="routeModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title" id="routeModalTitle">Thêm tuyến</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <input type="hidden" id="route-id">
            <div class="mb-3"><label class="form-label">Mã tuyến</label><input type="text" id="route-code" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Điểm đi</label><input type="text" id="route-from" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Điểm đến</label><input type="text" id="route-to" class="form-control" required></div>
            <div class="row"><div class="col-6 mb-3"><label class="form-label">Km</label><input type="number" id="route-km" class="form-control"></div>
            <div class="col-6 mb-3"><label class="form-label">Phút</label><input type="number" id="route-mins" class="form-control"></div></div>
            <div class="form-check"><input type="checkbox" id="route-active" class="form-check-input" checked><label class="form-check-label">Kích hoạt</label></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
            <button type="button" class="btn btn-primary" id="btn-save-route">Lưu</button>
        </div>
    </div></div>
</div>
