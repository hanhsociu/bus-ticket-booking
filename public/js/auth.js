/**
 * Auth flows - login, register, logout
 */
async function login(email, password) {
    const res = await apiFetch('/auth/login', {
        method: 'POST',
        body: { email, password },
    });

    setToken(res.data.token);
    setUser(res.data.user);

    return res.data.user;
}

async function register(data) {
    const res = await apiFetch('/auth/register', {
        method: 'POST',
        body: data,
    });

    setToken(res.data.token);
    setUser(res.data.user);

    return res.data.user;
}

async function fetchMe() {
    const res = await apiFetch('/auth/me');
    setUser(res.data);
    return res.data;
}

async function apiLogout() {
    try {
        if (getToken()) {
            await apiFetch('/auth/logout', { method: 'POST' });
        }
    } catch {
        // ignore
    } finally {
        logout(false);
        window.location.href = '/login';
    }
}

function redirectAfterLogin(user) {
    const params = new URLSearchParams(window.location.search);
    const redirect = params.get('redirect');

    if (redirect) {
        window.location.href = decodeURIComponent(redirect);
        return;
    }

    if (user.role === 'admin') {
        window.location.href = '/admin/dashboard';
    } else {
        window.location.href = '/customer/dashboard';
    }
}

window.login = login;
window.register = register;
window.fetchMe = fetchMe;
window.apiLogout = apiLogout;
window.redirectAfterLogin = redirectAfterLogin;
