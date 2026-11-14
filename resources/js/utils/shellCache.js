// resources/js/utils/shellCache.js
//
// Keeps the sidebar + top header visible across full page loads.
//
// Every nav click is a real page load, so the layout arrives with empty
// mount points and React only fills them once the JS bundle has run —
// that's the "disappears for a second" flash. To avoid it we snapshot the
// rendered shell HTML into sessionStorage when leaving a page; an inline
// script in the layout (layouts/partials/shell-restore.blade.php) paints
// that snapshot synchronously before first paint, and React replaces it
// with the live component a moment later.

const PREFIX = "shell:";

// Mount points whose HTML is snapshotted. Must match the ids passed to the
// shell-restore partial in the Blade layouts.
const SHELL_IDS = [
    "tenant-sidebar-root",
    "tenant-header-root",
    "admin-sidebar-root",
    "admin-header-root",
];

const userKey = () => window.__appConfig?.userId ?? "anon";

export const shellKey = (name) => `${PREFIX}${userKey()}:${name}`;

export function readShellCache(name, fallback = null) {
    try {
        const raw = sessionStorage.getItem(shellKey(name));
        return raw == null ? fallback : JSON.parse(raw);
    } catch {
        return fallback;
    }
}

export function writeShellCache(name, value) {
    try {
        sessionStorage.setItem(shellKey(name), JSON.stringify(value));
    } catch {
        /* storage full / disabled — ignore */
    }
}

let disabled = false;

function saveSnapshots() {
    if (disabled) return;

    // Let components close dropdowns / drawers (synchronously) first
    window.dispatchEvent(new Event("shell:before-snapshot"));

    SHELL_IDS.forEach((id) => {
        const el = document.getElementById(id);
        if (!el || !el.dataset.mounted || !el.firstElementChild) return;
        try {
            sessionStorage.setItem(`${PREFIX}${userKey()}:${id}`, el.innerHTML);
        } catch {
            /* ignore */
        }
    });
}

let bound = false;
export function initShellSnapshots() {
    if (bound) return;
    bound = true;
    window.addEventListener("pagehide", saveSnapshots);
}

/** Wipe every cached shell snapshot (used on logout). */
export function clearShellCache() {
    disabled = true; // the pagehide after logout must not re-save them
    try {
        Object.keys(sessionStorage)
            .filter((k) => k.startsWith(PREFIX))
            .forEach((k) => sessionStorage.removeItem(k));
    } catch {
        /* ignore */
    }
}

/** Sidebar collapsed state persists across pages so the snapshot matches. */
export function readCollapsed() {
    try {
        return localStorage.getItem(`${PREFIX}sidebar-collapsed`) === "1";
    } catch {
        return false;
    }
}

export function writeCollapsed(value) {
    try {
        localStorage.setItem(`${PREFIX}sidebar-collapsed`, value ? "1" : "0");
    } catch {
        /* ignore */
    }
}
