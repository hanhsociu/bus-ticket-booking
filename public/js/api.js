/**
 * Bus Ticket Booking - API helper
 * Token Sanctum lưu localStorage cho demo local.
 */
const API_BASE = '/api';
const TOKEN_KEY = 'bus_token';
const USER_KEY = 'bus_user';

function getToken() {
    return localStorage.getItem(TOKEN_KEY);
}

function setToken(token) {
    if (token) {
        localStorage.setItem(TOKEN_KEY, token);
    } else {
        localStorage.removeItem(TOKEN_KEY);
    }
}

function getUser() {
    const raw = localStorage.getItem(USER_KEY);
    if (!raw) return null;
    try {
        return JSON.parse(raw);
    } catch {
        return null;
    }
}

function setUser(user) {
    if (user) {
        localStorage.setItem(USER_KEY, JSON.stringify(user));
    } else {
        localStorage.removeItem(USER_KEY);
    }
}

function logout(redirect = true) {
    setToken(null);
    setUser(null);
    if (redirect) {
        window.location.href = '/login';
    }
}

function isLoggedIn() {
    return !!getToken();
}

function isAdmin() {
    const user = getUser();
    return user && user.role === 'admin';
}

async function apiFetch(path, options = {}) {
    const headers = {
        Accept: 'application/json',
        ...(options.headers || {}),
    };

    if (!(options.body instanceof FormData)) {
        headers['Content-Type'] = headers['Content-Type'] || 'application/json';
    }

    const token = getToken();
    if (token) {
        headers.Authorization = `Bearer ${token}`;
    }

    const config = {
        ...options,
        headers,
    };

    if (config.body && typeof config.body === 'object' && !(config.body instanceof FormData)) {
        config.body = JSON.stringify(config.body);
    }

    const response = await fetch(`${API_BASE}${path}`, config);
    let payload = null;

    try {
        payload = await response.json();
    } catch {
        payload = { success: false, message: 'Phản hồi không hợp lệ từ server.' };
    }

    if (response.status === 401) {
        showToast('Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.', 'warning');
        logout(true);
        throw new Error(payload.message || 'Unauthorized');
    }

    if (!response.ok) {
        const message = payload.message || payload.error || 'Có lỗi xảy ra.';
        const err = new Error(message);
        err.status = response.status;
        err.payload = payload;
        throw err;
    }

    return payload;
}

function requireAuth(role = null) {
    if (!isLoggedIn()) {
        const next = encodeURIComponent(window.location.pathname + window.location.search);
        window.location.href = `/login?redirect=${next}`;
        return false;
    }
    if (role === 'admin' && !isAdmin()) {
        showToast('Bạn không có quyền truy cập trang admin.', 'danger');
        window.location.href = '/customer/dashboard';
        return false;
    }
    if (role === 'customer' && isAdmin()) {
        // Admin có thể xem client pages nếu muốn, không redirect
    }
    return true;
}

function requireAdmin() {
    return requireAuth('admin');
}

function setLoading(el, loading = true, text = 'Đang tải...') {
    if (!el) return;
    if (loading) {
        el.dataset.originalHtml = el.innerHTML;
        el.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>${text}`;
        el.disabled = true;
    } else {
        if (el.dataset.originalHtml) {
            el.innerHTML = el.dataset.originalHtml;
            delete el.dataset.originalHtml;
        }
        el.disabled = false;
    }
}

window.API_BASE = API_BASE;
window.getToken = getToken;
window.setToken = setToken;
window.getUser = getUser;
window.setUser = setUser;
window.logout = logout;
window.isLoggedIn = isLoggedIn;
window.isAdmin = isAdmin;
window.apiFetch = apiFetch;
window.requireAuth = requireAuth;
window.requireAdmin = requireAdmin;
window.setLoading = setLoading;
