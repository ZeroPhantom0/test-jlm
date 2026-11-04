// resources/js/utils/logout.js

/**
 * Which login page should we redirect to after logout?
 * - Admin  → /admin/login
 * - Tenant → /login
 */
function getLoginRedirectUrl() {
    const role = window.__appConfig?.role || 'tenant';
    const isAdmin =
        role === 'admin' ||
        role === 'super_admin' ||
        role === 'manager' ||
        window.location.pathname.startsWith('/admin');

    return isAdmin ? '/admin/login' : '/login';
}

export function logout(e) {
    if (e && e.preventDefault) e.preventDefault();
    window.dispatchEvent(new Event('open-logout-modal'));
}

export function confirmLogout() {
    const csrfToken =
        document.querySelector('meta[name="csrf-token"]')?.content ||
        window.__appConfig?.csrf ||
        '';
    const redirectTo = getLoginRedirectUrl();

    fetch('/logout', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
        },
    })
        .then((res) => {
            // Do NOT trust res.url — always go to the role-aware target.
            // This way admin → /admin/login, tenant → /login.
            window.location.href = redirectTo;
        })
        .catch((err) => {
            console.error('Logout failed:', err);
            // Even on failure, take the user to the correct login page
            window.location.href = redirectTo;
        });
}