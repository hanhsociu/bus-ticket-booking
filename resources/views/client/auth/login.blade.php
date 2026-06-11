<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đăng nhập | BusBook</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/client/fonts/icomoon/style.css') }}">
    <link href="{{ asset('css/custom-client.css') }}" rel="stylesheet">
</head>
<body class="auth-page">
<div id="toast-container"></div>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card auth-card p-4">
                <div class="text-center mb-4">
                    <div class="bb-brand-icon d-inline-flex mb-2" style="background:rgba(14,165,233,.15);color:#0ea5e9;width:48px;height:48px;border-radius:12px"><span class="icon-bus"></span></div>
                    <h2 class="fw-bold mb-1">Bus<span style="color:#0ea5e9">Book</span></h2>
                    <p class="text-muted mb-0">Đăng nhập để đặt vé & quản lý booking</p>
                </div>
                <form id="login-form">
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mật khẩu</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="alert alert-danger d-none" id="login-error"></div>
                    <button type="submit" class="btn btn-bb-primary w-100" id="login-btn">Đăng nhập</button>
                </form>
                <p class="text-center mt-3 mb-0">Chưa có tài khoản? <a href="{{ url('/register') }}">Đăng ký</a></p>
                <p class="text-center mt-2"><a href="{{ url('/') }}">← Về trang chủ</a></p>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/utils.js') }}"></script>
<script src="{{ asset('js/api.js') }}"></script>
<script src="{{ asset('js/auth.js') }}"></script>
<script>
document.getElementById('login-form').addEventListener('submit', async e => {
    e.preventDefault();
    const btn = document.getElementById('login-btn');
    const err = document.getElementById('login-error');
    err.classList.add('d-none');
    setLoading(btn, true, 'Đang đăng nhập...');
    try {
        const fd = new FormData(e.target);
        const user = await login(fd.get('email'), fd.get('password'));
        showToast('Đăng nhập thành công!', 'success');
        redirectAfterLogin(user);
    } catch (ex) {
        err.textContent = ex.message;
        err.classList.remove('d-none');
    } finally {
        setLoading(btn, false);
    }
});
if (isLoggedIn()) {
    redirectAfterLogin(getUser());
}
</script>
</body>
</html>
