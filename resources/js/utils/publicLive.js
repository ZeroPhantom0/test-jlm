// resources/js/utils/publicLive.js
//
// Pusher connection for PUBLIC (guest) pages such as the landing page.
// Guests can't authorise private channels, so this only ever subscribes to
// public channels, which carry nothing but "something changed" signals.

import Pusher from "pusher-js";

let client = null;

function getClient() {
    if (client) return client;

    const cfg = window.__appConfig?.broadcast || {};
    const key = cfg.key || import.meta.env.VITE_PUSHER_APP_KEY;
    const cluster = cfg.cluster || import.meta.env.VITE_PUSHER_APP_CLUSTER || "ap1";

    if (!key) {
        console.warn("[Live] Pusher key missing — public real-time disabled.");
        return null;
    }

    client = new Pusher(key, { cluster, forceTLS: true });
    return client;
}

/**
 * Listen to a public channel event.
 *
 * onChange runs (debounced) when the event fires, when the socket reconnects
 * after a drop, when the tab becomes visible again, and every `pollMs` while
 * Pusher is unavailable — so missed events are always caught up.
 *
 * @returns {() => void} unsubscribe
 */
export function subscribePublic(channelName, eventName, onChange, { debounceMs = 400, pollMs = 60000 } = {}) {
    let timer = null;
    const fire = (delay = debounceMs) => {
        clearTimeout(timer);
        timer = setTimeout(onChange, delay);
    };

    const pusher = getClient();
    let channel = null;
    let pollTimer = null;
    let wasDisconnected = false;

    const startPolling = () => {
        if (pollTimer) return;
        pollTimer = setInterval(() => {
            if (document.visibilityState === "visible") fire(0);
        }, pollMs);
    };
    const stopPolling = () => {
        clearInterval(pollTimer);
        pollTimer = null;
    };

    const onEvent = () => fire();
    const onConnected = () => {
        stopPolling();
        if (wasDisconnected) fire(0);
        wasDisconnected = false;
    };
    const onDown = () => {
        wasDisconnected = true;
        startPolling();
    };
    const onVisible = () => {
        if (document.visibilityState === "visible") fire(0);
    };

    if (pusher) {
        channel = pusher.subscribe(channelName);
        channel.bind(eventName, onEvent);
        pusher.connection.bind("connected", onConnected);
        pusher.connection.bind("unavailable", onDown);
        pusher.connection.bind("failed", onDown);
        pusher.connection.bind("disconnected", onDown);
    } else {
        startPolling();
    }
    document.addEventListener("visibilitychange", onVisible);

    return () => {
        clearTimeout(timer);
        stopPolling();
        document.removeEventListener("visibilitychange", onVisible);
        if (pusher) {
            channel?.unbind(eventName, onEvent);
            pusher.connection.unbind("connected", onConnected);
            pusher.connection.unbind("unavailable", onDown);
            pusher.connection.unbind("failed", onDown);
            pusher.connection.unbind("disconnected", onDown);
        }
    };
}
