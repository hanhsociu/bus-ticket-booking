<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đăng ký | BusBook</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/client/fonts/icomoon/style.css') }}">
    <link href="{{ asset('css/custom-client.css') }}" rel="stylesheet">
</head>
<body class="auth-page">
<div id="toast-container"></div>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card auth-card p-4">
                <div class="text-center mb-4">
                    <div class="bb-brand-icon d-inline-flex mb-2" style="background:rgba(14,165,233,.15);color:#0ea5e9;width:48px;height:48px;border-radius:12px"><span class="icon-bus"></span></div>
                    <h2 class="fw-bold mb-1">Bus<span style="color:#0ea5e9">Book</span></h2>
                    <p class="text-muted mb-0">Tạo tài khoản để đặt vé xe trực tuyến</p>
                </div>
                <form id="register-form">
                    <div class="mb-3">
                        <label class="form-label">Họ tên</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Số điện thoại</label>
                        <input type="text" name="phone" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mật khẩu</label>
                        <input type="password" name="password" class="form-control" minlength="6" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Xác nhận mật khẩu</label>
                        <input type="password" name="password_confirmation" class="form-control" required>
                    </div>
                    <div class="alert alert-danger d-none" id="register-error"></div>
                    <button type="submit" class="btn btn-bb-primary w-100" id="register-btn">Đăng ký</button>
                </form>
                <p class="text-center mt-3 mb-0">Đã có tài khoản? <a href="{{ url('/login') }}">Đăng nhập</a></p>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/utils.js') }}"></script>
<script src="{{ asset('js/api.js') }}"></script>
<script src="{{ asset('js/auth.js') }}"></script>
<script>
document.getElementById('register-form').addEventListener('submit', async e => {
    e.preventDefault();
    const btn = document.getElementById('register-btn');
    const err = document.getElementById('register-error');
    err.classList.add('d-none');
    setLoading(btn, true);
    try {
        const fd = new FormData(e.target);
        const user = await register({
            name: fd.get('name'),
            email: fd.get('email'),
            phone: fd.get('phone') || null,
            password: fd.get('password'),
            password_confirmation: fd.get('password_confirmation'),
        });
        showToast('Đăng ký thành công!', 'success');
        redirectAfterLogin(user);
    } catch (ex) {
        err.textContent = ex.message;
        err.classList.remove('d-none');
    } finally {
        setLoading(btn, false);
    }
});
</script>
</body>
</html>
