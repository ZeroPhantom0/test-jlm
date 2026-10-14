// resources/js/utils/logout.js
export function logout() {
    const form = document.getElementById('logout-form');
    if (form) {
        form.submit();
    } else {
        console.error('Logout form not found. Add it to your Blade layout.');
    }
}