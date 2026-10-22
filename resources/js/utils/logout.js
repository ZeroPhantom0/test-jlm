// resources/js/utils/logout.js

/**
 * Dispatch a custom event to open the logout modal.
 * The actual logout logic lives in the Layout component or LogoutModal component.
 */
export function logout() {
    window.dispatchEvent(new Event("open-logout-modal"));
}