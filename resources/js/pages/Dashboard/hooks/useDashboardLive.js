// resources/js/pages/Dashboard/hooks/useDashboardLive.js
// Keeps the dashboard payload fresh over the existing Pusher channels and
// reacts to *real* changes (approval, new notice, new message, bill paid…).
import { useCallback, useEffect, useRef, useState } from "react";
import { confettiBurst } from "../../../utils/motion";
import { toast } from "../components/Toast";

const ENDPOINT = "/tenants/dashboard/data";

export default function useDashboardLive(initial, { onApproved, onRoomsChanged } = {}) {
    const [d, setD] = useState(initial);
    const dRef = useRef(d);
    dRef.current = d;
    const cbRef = useRef({ onApproved, onRoomsChanged });
    cbRef.current = { onApproved, onRoomsChanged };

    const refresh = useCallback(async () => {
        try {
            const res = await fetch(ENDPOINT, {
                headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
                credentials: "same-origin",
            });
            if (!res.ok) return;
            const next = await res.json();
            const prev = dRef.current;

            // Reservation decided
            if (prev.tenantState === "pending" && next.tenantState === "resident") {
                confettiBurst();
                toast("Your reservation was approved — welcome home!", { icon: "fa-solid fa-circle-check text-emerald-600", tone: "success", duration: 7000 });
                cbRef.current.onApproved?.(next);
            } else if (prev.tenantState === "pending" && next.tenantState === "explorer") {
                toast("Your reservation wasn't approved. Check Reservations for the admin's note.", { icon: "fa-solid fa-circle-info text-[#E11D48]", duration: 8000 });
            }

            // New announcement(s)
            const known = new Set((prev.announcements || []).map((a) => a.id));
            (next.announcements || []).filter((a) => !known.has(a.id)).slice(0, 1)
                .forEach((a) => toast(`New announcement: ${a.title}`, { icon: "fa-solid fa-bullhorn text-sky-600" }));

            // New message(s)
            if ((next.unreadMessages || 0) > (prev.unreadMessages || 0)) {
                toast("You have a new message from the admin", { icon: "fa-regular fa-comment-dots text-violet-600" });
            }

            // Bills
            if (prev.bills && !next.bills) {
                toast("All paid up — thank you!", { icon: "fa-solid fa-circle-check text-emerald-600", tone: "success" });
            } else if (!prev.bills && next.bills) {
                toast("A new bill was added to your account", { icon: "fa-solid fa-wallet text-[#BF5600]" });
            }

            // Profile reached 100%
            if ((prev.profile?.percent ?? 0) < 100 && next.profile?.percent >= 100) {
                confettiBurst();
                toast("Your profile is complete!", { icon: "fa-solid fa-user-check text-emerald-600", tone: "success" });
            }

            if (prev.tenantState !== next.tenantState) cbRef.current.onRoomsChanged?.(next);

            // Room listings come from their own endpoint — keep what we have
            setD((cur) => ({ ...next, rooms: cur.rooms }));
        } catch {
            // best-effort; the page still works without live updates
        }
    }, []);

    // Pusher subscriptions (existing channels only)
    useEffect(() => {
        let timer;
        const onEvent = () => {
            clearTimeout(timer);
            timer = setTimeout(refresh, 400);
        };
        const unsubs = [];
        const L = window.LiveTenantChannels;
        try {
            if (L) {
                unsubs.push(L.reservations?.(onEvent));
                unsubs.push(L.billing?.(onEvent));
                unsubs.push(L.announcements?.(onEvent));
                unsubs.push(L.services?.(onEvent));
                unsubs.push(L.rooms?.(onEvent));   // room / availability changes (hero stats, my room)
                unsubs.push(L.self?.(onEvent));    // admin edited this tenant's stay / record
                const tenantId = window.__appConfig?.tenantId;
                if (tenantId) unsubs.push(L.messages?.(tenantId, onEvent));
            }
        } catch {
            // real-time unavailable
        }
        // Local signals from the existing Pay Now / booking modals
        window.addEventListener("billing:refresh", onEvent);
        window.addEventListener("reservation:refresh", onEvent);
        // Fallback when the socket is down: refresh when the tab regains focus
        const onVisible = () => document.visibilityState === "visible" && window.__liveAvailable !== true && onEvent();
        document.addEventListener("visibilitychange", onVisible);

        return () => {
            clearTimeout(timer);
            unsubs.forEach((u) => typeof u === "function" && u());
            window.removeEventListener("billing:refresh", onEvent);
            window.removeEventListener("reservation:refresh", onEvent);
            document.removeEventListener("visibilitychange", onVisible);
        };
    }, [refresh]);

    // Keep the existing Pay Now modal's invoice in sync (it watches data-invoice)
    useEffect(() => {
        const root = document.getElementById("payment-modal-root");
        if (!root) return;
        if (d.bills?.payNow) root.setAttribute("data-invoice", JSON.stringify(d.bills.payNow));
        else root.removeAttribute("data-invoice");
    }, [d.bills]);

    return { d, refresh };
}
