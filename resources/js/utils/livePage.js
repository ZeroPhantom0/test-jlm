// resources/js/utils/livePage.js
//
// Real-time page refresh over Pusher (Laravel Echo).
//
// Instead of hand-maintaining a JSON endpoint + JS template that mirrors each
// Blade view, a live page re-fetches its own URL when a broadcast arrives and
// swaps in the regions that changed. The result is always identical to a fresh
// page load (warnings, counts, room info, history …), with no layout drift.
//
// Markup:
//
//   <div data-live-page
//        data-live-listen="tenant.services:.maintenance.updated tenant.reservations:.reservation.updated"
//        data-live-refresh-on="maintenance:refresh">
//
//       <div data-live-region="main"> … server-rendered content … </div>
//   </div>
//
//   <div id="tenant-maintenance-modals" data-live-root="maintenance:data-updated"
//        data-requests='…'></div>
//
//   data-live-listen      space-separated "privateChannel:.event" pairs
//   data-live-refresh-on  space-separated window events that also trigger a
//                         refresh (fired by the React modals after submit/cancel)
//   data-live-region      regions whose innerHTML is replaced when it changed
//   data-live-root        React mount points (by id): their data-* attributes
//                         are synced from the fresh page and the named window
//                         event is dispatched (no detail → the component
//                         re-reads everything from its data attributes)
//
// Missed events are covered by refreshing when the socket reconnects and when
// the tab becomes visible again, plus slow polling while Pusher is unavailable.

const DEBOUNCE_MS = 350;
const FALLBACK_POLL_MS = 30000;
const SKIP_ATTRS = new Set(['id', 'data-mounted', 'data-saving', 'class', 'style']);

class LivePage {
    constructor(el) {
        this.el = el;
        this.timer = null;
        this.inFlight = false;
        this.queued = false;
        this.subscribed = false;
        this.wasDisconnected = false;

        this.listen = (el.dataset.liveListen || '')
            .split(/\s+/)
            .filter(Boolean)
            .map((pair) => {
                const i = pair.indexOf(':');
                return i > 0 ? { channel: pair.slice(0, i), event: pair.slice(i + 1) } : null;
            })
            .filter(Boolean);

        (el.dataset.liveRefreshOn || '')
            .split(/\s+/)
            .filter(Boolean)
            .forEach((name) => window.addEventListener(name, () => this.schedule(0)));

        this.subscribe();
        this.watchConnection();
    }

    // ------------------------------------------------------------------
    // Pusher subscriptions
    // ------------------------------------------------------------------
    subscribe() {
        if (this.subscribed) return;
        if (!window.Echo) {
            // Echo boots in app.jsx; if Pusher isn't configured it never appears.
            this.startPolling();
            return;
        }

        this.listen.forEach(({ channel, event }) => {
            try {
                window.Echo.private(channel).listen(event, () => this.schedule());
            } catch (e) {
                console.warn('[Live] subscribe failed', channel, event, e);
            }
        });
        this.subscribed = true;
    }

    watchConnection() {
        document.addEventListener('echo:connected', () => {
            this.stopPolling();
            // Catch up on anything broadcast while we were offline.
            if (this.wasDisconnected) this.schedule(0);
            this.wasDisconnected = false;
        });
        document.addEventListener('echo:unavailable', () => {
            this.wasDisconnected = true;
            this.startPolling();
        });
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') this.schedule(0);
        });
    }

    startPolling() {
        if (this.pollTimer) return;
        this.pollTimer = setInterval(() => {
            if (document.visibilityState === 'visible') this.schedule(0);
        }, FALLBACK_POLL_MS);
    }

    stopPolling() {
        clearInterval(this.pollTimer);
        this.pollTimer = null;
    }

    // ------------------------------------------------------------------
    // Refresh
    // ------------------------------------------------------------------

    // One admin action often fires several events (e.g. approve → invoice +
    // reservation + service request); coalesce them into a single fetch.
    schedule(delay = DEBOUNCE_MS) {
        clearTimeout(this.timer);
        this.timer = setTimeout(() => this.refresh(), delay);
    }

    isBusy() {
        // Don't yank the DOM out from under a modal that is mid-submit.
        return !!document.querySelector('[data-live-root][data-saving="true"]');
    }

    async refresh() {
        if (this.inFlight) {
            this.queued = true;
            return;
        }
        if (this.isBusy()) {
            this.schedule(1000);
            return;
        }

        this.inFlight = true;
        try {
            const res = await fetch(window.location.href, {
                headers: { Accept: 'text/html' },
                credentials: 'same-origin',
                cache: 'no-store',
            });
            // Session expired / redirected to login → leave the page alone.
            if (!res.ok || (res.redirected && new URL(res.url).pathname !== window.location.pathname)) return;

            const html = await res.text();
            const doc = new DOMParser().parseFromString(html, 'text/html');

            this.swapRegions(doc);
            this.syncRoots(doc);

            document.dispatchEvent(new CustomEvent('live:refreshed', { detail: { page: this.el } }));
        } catch (e) {
            console.warn('[Live] refresh failed', e);
        } finally {
            this.inFlight = false;
            if (this.queued) {
                this.queued = false;
                this.schedule(0);
            }
        }
    }

    swapRegions(doc) {
        const regions = Array.from(this.el.querySelectorAll('[data-live-region]'));
        if (this.el.hasAttribute('data-live-region')) regions.unshift(this.el);

        regions.forEach((region) => {
            if (!region.isConnected) return; // replaced by a parent swap
            const key = region.dataset.liveRegion;
            const fresh = doc.querySelector(`[data-live-region="${CSS.escape(key)}"]`);
            if (fresh && fresh.innerHTML !== region.innerHTML) {
                region.innerHTML = fresh.innerHTML;
            }
        });
    }

    syncRoots(doc) {
        document.querySelectorAll('[data-live-root]').forEach((root) => {
            if (!root.id) return;
            const fresh = doc.getElementById(root.id);
            if (!fresh) return;

            let changed = false;
            const freshNames = new Set();

            for (const { name, value } of Array.from(fresh.attributes)) {
                if (SKIP_ATTRS.has(name) || !name.startsWith('data-') || name === 'data-live-root') continue;
                freshNames.add(name);
                if (root.getAttribute(name) !== value) {
                    root.setAttribute(name, value);
                    changed = true;
                }
            }
            // Attributes that disappeared server-side (e.g. no invoice anymore)
            for (const { name } of Array.from(root.attributes)) {
                if (SKIP_ATTRS.has(name) || !name.startsWith('data-') || name === 'data-live-root') continue;
                if (!freshNames.has(name)) {
                    root.removeAttribute(name);
                    changed = true;
                }
            }

            if (changed && root.dataset.liveRoot) {
                window.dispatchEvent(new CustomEvent(root.dataset.liveRoot));
            }
        });
    }
}

export function initLivePages(root = document) {
    root.querySelectorAll('[data-live-page]').forEach((el) => {
        if (el.__livePage) return;
        el.__livePage = new LivePage(el);
    });
}

export default initLivePages;
