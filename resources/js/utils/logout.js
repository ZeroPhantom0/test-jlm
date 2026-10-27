// resources/js/utils/logout.js

/**
 * Dispatch a custom event to open the logout modal.
 * The actual logout logic lives in the LogoutModal component.
 */
export function logout() {
    // Clear client-side caches that could leak stale state back in.
    try {
        sessionStorage.clear();
    } catch {
        // Ignore storage access errors (private mode, etc.)
    }

    window.dispatchEvent(new Event("open-logout-modal"));
}

/**
 * Perform the actual logout after the user confirms.
 *
 * 1. Submits the hidden `#logout-form` (CSRF-protected POST to /logout).
 * 2. After the server redirects, we use `location.replace()` to swap
 *    the current history entry with /login so pressing Back does NOT
 *    restore the logged-in page from bfcache.
 */
export function confirmLogout() {
    const form = document.getElementById('logout-form');

    if (!form) {
        // Fallback: no form — just hard-redirect to /login
        window.location.replace('/login');
        return;
    }

    form.submit();

    // Belt-and-suspenders: after the POST completes, the server will
    // have redirected us. Replace the history entry as a safety net.
    setTimeout(() => {
        try {
            window.location.replace('/login');
        } catch {
            /* ignore */
        }
    }, 50);
}