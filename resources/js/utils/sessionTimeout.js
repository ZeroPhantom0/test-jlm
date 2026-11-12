// resources/js/utils/sessionTimeout.js
//
// Client side of the admin idle-session timeout.
//  - While the admin is actually using the page (mouse, keys, clicks,
//    scroll, touch) a heartbeat tells the server at most once a minute, so
//    reading a page without clicking anything doesn't count as idle.
//  - When the chosen timeout passes with no activity, the page reloads; the
//    server then logs the session out and shows the login page with a note.
//
// Enabled only when the admin layout sets window.__appConfig.sessionTimeout.

const HEARTBEAT_EVERY_MS = 60 * 1000;
const ACTIVITY_EVENTS = ["mousemove", "mousedown", "keydown", "scroll", "touchstart", "wheel"];

let started = false;

export function initSessionTimeout() {
    const cfg = window.__appConfig || {};
    if (started || !cfg.sessionTimeout || !cfg.heartbeatUrl) return;
    started = true;

    let lastActivity = Date.now();
    let lastBeat = Date.now();
    let timer = null;

    const timeoutMs = () => (Number(window.__appConfig.sessionTimeout) || 120) * 60 * 1000;

    const csrf = () =>
        document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || cfg.csrf || "";

    const expire = () => {
        // Let the server end the session and redirect to the login page
        window.location.reload();
    };

    const schedule = () => {
        clearTimeout(timer);
        // +5s so the server-side check is definitely past the limit
        timer = setTimeout(() => {
            if (Date.now() - lastActivity >= timeoutMs()) expire();
            else schedule();
        }, Math.max(1000, timeoutMs() - (Date.now() - lastActivity) + 5000));
    };

    const beat = async () => {
        lastBeat = Date.now();
        try {
            const res = await fetch(cfg.heartbeatUrl, {
                method: "POST",
                headers: {
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": csrf(),
                },
                credentials: "same-origin",
            });
            if (res.status === 401 || res.status === 419) {
                const json = await res.json().catch(() => ({}));
                window.location.href = json.redirect || "/admin/login";
            }
        } catch (e) {
            /* offline — the next beat or the timer will handle it */
        }
    };

    const onActivity = () => {
        lastActivity = Date.now();
        if (Date.now() - lastBeat >= HEARTBEAT_EVERY_MS) beat();
        schedule();
    };

    ACTIVITY_EVENTS.forEach((evt) => window.addEventListener(evt, onActivity, { passive: true }));
    document.addEventListener("session-timeout:changed", () => {
        lastActivity = Date.now();
        schedule();
    });

    schedule();
}

export default initSessionTimeout;
