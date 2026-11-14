// resources/js/utils/logout.js
import { clearShellCache } from "./shellCache";

/**
 * Where should we send the user after logout?
 * - Admin  → /admin/login
 * - Tenant → /  (landing page)
 */
function getLogoutRedirectUrl() {
    const role = window.__appConfig?.role || 'tenant';
    const isAdmin =
        role === 'admin' ||
        role === 'super_admin' ||
        role === 'manager' ||
        window.location.pathname.startsWith('/admin');

    return isAdmin ? '/admin/login' : '/';
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
    const redirectTo = getLogoutRedirectUrl();

    // Don't keep the signed-in sidebar/header snapshots around after logout
    clearShellCache();

    fetch('/logout', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
        },
    })
        .then(() => {
            // Do NOT trust res.url — always go to the role-aware target.
            // Admin → /admin/login, tenant → landing page.
            window.location.href = redirectTo;
        })
        .catch((err) => {
            console.error('Logout failed:', err);
            // Even on failure, take the user to the correct page
            window.location.href = redirectTo;
        });
}
