// resources/js/utils/liveSidebar.js
// Keeps sidebar badges / nav state current over Pusher (Laravel Echo).
import { useCallback, useEffect, useRef, useState } from "react";

/**
 * @param {string}   url        JSON endpoint returning the sidebar state
 * @param {object}   initial    state rendered by the server (no flash on load)
 * @param {Function} subscribe  (handler) => array of unsubscribe fns
 */
export default function useLiveSidebar(url, initial, subscribe) {
    const [state, setState] = useState(initial);
    const inFlight = useRef(false);
    const again = useRef(false);

    const refresh = useCallback(async () => {
        if (inFlight.current) {
            again.current = true; // don't drop an update that arrives mid-request
            return;
        }
        inFlight.current = true;
        try {
            const res = await fetch(url, {
                headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
                credentials: "same-origin",
            });
            if (res.ok) {
                const next = await res.json();
                setState((cur) => ({ ...cur, ...next }));
            }
        } catch {
            // best-effort
        } finally {
            inFlight.current = false;
            if (again.current) {
                again.current = false;
                setTimeout(refresh, 0);
            }
        }
    }, [url]);

    useEffect(() => {
        let timer;
        const onEvent = () => {
            clearTimeout(timer);
            timer = setTimeout(refresh, 400); // coalesce bursts of events
        };

        let unsubs = [];
        try {
            unsubs = (subscribe?.(onEvent) || []).filter(Boolean);
            if (window.LiveUserNotifications) unsubs.push(window.LiveUserNotifications(onEvent));
        } catch {
            // real-time unavailable
        }

        // Local actions on this page (Pay Now, reserve, cancel…)
        const local = ["billing:refresh", "reservation:refresh", "reservation:submitted"];
        local.forEach((e) => window.addEventListener(e, onEvent));

        // Socket reconnected, or tab back in view while the socket is down
        const onReconnect = () => onEvent();
        const onVisible = () => document.visibilityState === "visible" && window.__liveAvailable !== true && onEvent();
        document.addEventListener("echo:connected", onReconnect);
        document.addEventListener("visibilitychange", onVisible);

        return () => {
            clearTimeout(timer);
            unsubs.forEach((u) => typeof u === "function" && u());
            local.forEach((e) => window.removeEventListener(e, onEvent));
            document.removeEventListener("echo:connected", onReconnect);
            document.removeEventListener("visibilitychange", onVisible);
        };
    }, [refresh, subscribe]);

    return state;
}
