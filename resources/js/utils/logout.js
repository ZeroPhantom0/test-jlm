// resources/js/utils/logout.js

export function logout(e) {
    if (e && e.preventDefault) e.preventDefault();
    // Opens the confirmation modal
    window.dispatchEvent(new Event('open-logout-modal'));
}

export function confirmLogout() {
    // 1. Grab the CSRF token from the meta tag in the head
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    // 2. Send a POST request to the Laravel logout route
    fetch('/logout', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
        },
    })
    .then((res) => {
        if (res.redirected) {
            // Laravel sends a redirect to '/login', so let's follow it
            window.location.href = res.url;
        } else {
            // Fallback if there is no redirect
            window.location.href = '/login';
        }
    })
    .catch((err) => {
        console.error('Logout failed:', err);
        // Force reload to login page just in case
        window.location.href = '/login';
    });
}