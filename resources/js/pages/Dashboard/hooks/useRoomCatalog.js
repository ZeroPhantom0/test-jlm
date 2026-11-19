// resources/js/pages/Dashboard/hooks/useRoomCatalog.js
// Room listings for the dashboard: filters (kept in the URL), paging,
// lazy loading, and live availability diffs over Pusher.
import { useCallback, useEffect, useRef, useState } from "react";
import { toast } from "../components/Toast";

const ENDPOINT = "/tenants/dashboard/rooms";
export const PER_PAGE = 12;
export const DEFAULT_FILTERS = { search: "", min_price: null, max_price: null, type: [], floor: null, sort: "recommended", availability: null };

export function toQuery(filters, page) {
    const q = new URLSearchParams();
    if (filters.search?.trim()) q.set("search", filters.search.trim());
    if (filters.min_price != null) q.set("min_price", filters.min_price);
    if (filters.max_price != null) q.set("max_price", filters.max_price);
    (filters.type || []).forEach((t) => q.append("type[]", t));
    if (filters.floor != null) q.set("floor", filters.floor);
    if (filters.sort && filters.sort !== "recommended") q.set("sort", filters.sort);
    if (filters.availability) q.set("availability", filters.availability);
    if (page && page > 1) q.set("page", page);
    return q;
}

export const activeFilterCount = (f) =>
    (f.search?.trim() ? 1 : 0) + (f.min_price != null || f.max_price != null ? 1 : 0) + (f.type?.length || 0) + (f.floor != null ? 1 : 0) + (f.availability ? 1 : 0);

async function fetchRooms(filters, page, signal) {
    const res = await fetch(`${ENDPOINT}?${toQuery(filters, page)}`, {
        headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
        credentials: "same-origin",
        signal,
    });
    if (!res.ok) throw new Error(res.status === 422 ? "Some filters weren't valid — try clearing them." : "We couldn't load rooms right now. Please try again.");
    return res.json();
}

export default function useRoomCatalog(initial) {
    const [data, setData] = useState(initial || null);
    const [rooms, setRooms] = useState(initial?.rooms || []);
    const [page, setPage] = useState(initial?.pagination?.page || 1);
    const [filters, setFilters] = useState({ ...DEFAULT_FILTERS, ...(initial?.filters || {}) });
    const [loading, setLoading] = useState(false);
    const [loadingMore, setLoadingMore] = useState(false);
    const [error, setError] = useState("");
    const [resultsKey, setResultsKey] = useState(0);

    const roomsRef = useRef(rooms);
    roomsRef.current = rooms;
    const pageRef = useRef(page);
    pageRef.current = page;
    const filtersRef = useRef(filters);
    filtersRef.current = filters;
    const loadedRef = useRef(!!initial);
    const ownBookingRef = useRef({ roomId: null, at: 0 });
    const abortRef = useRef(null);
    const skipNextFilterFetch = useRef(true);

    const apply = useCallback((json, append = false) => {
        setData(json);
        setRooms((prev) => (append ? [...prev, ...json.rooms.filter((r) => !prev.some((p) => p.id === r.id))] : json.rooms));
        setPage(json.pagination.page);
        loadedRef.current = true;
    }, []);

    /** Load on demand (residents only fetch when they open the browser). */
    const ensureLoaded = useCallback(async () => {
        if (loadedRef.current) return;
        loadedRef.current = true;
        setLoading(true);
        try {
            apply(await fetchRooms(filtersRef.current, 1));
        } catch (e) {
            loadedRef.current = false;
            setError(e.message);
        } finally {
            setLoading(false);
        }
    }, [apply]);

    // Filters changed → page 1 + keep the URL shareable
    useEffect(() => {
        if (skipNextFilterFetch.current) {
            skipNextFilterFetch.current = false;
            return;
        }
        const q = toQuery(filters).toString();
        window.history.replaceState(window.history.state, "", window.location.pathname + (q ? `?${q}` : "") + window.location.hash);

        abortRef.current?.abort();
        const ctrl = new AbortController();
        abortRef.current = ctrl;
        setLoading(true);
        setError("");
        const t = setTimeout(async () => {
            try {
                apply(await fetchRooms(filters, 1, ctrl.signal));
                setResultsKey((k) => k + 1);
            } catch (e) {
                if (e.name !== "AbortError") setError(e.message);
            } finally {
                if (!ctrl.signal.aborted) setLoading(false);
            }
        }, 250);
        return () => clearTimeout(t);
    }, [filters, apply]);

    const updateFilters = useCallback((patch) => setFilters((f) => ({ ...f, ...patch })), []);
    const clearFilters = useCallback(() => setFilters((f) => ({ ...DEFAULT_FILTERS, sort: f.sort })), []);

    const loadMore = useCallback(async () => {
        if (loadingMore || !data || pageRef.current >= (data.pagination?.lastPage || 1)) return;
        setLoadingMore(true);
        try {
            apply(await fetchRooms(filtersRef.current, pageRef.current + 1), true);
        } catch (e) {
            setError(e.message);
        } finally {
            setLoadingMore(false);
        }
    }, [apply, data, loadingMore]);

    /** Re-fetch the loaded pages, compare beds-left, toast only real changes. */
    const refresh = useCallback(async () => {
        if (!loadedRef.current) return;
        try {
            const f = filtersRef.current;
            const pages = await Promise.all(Array.from({ length: pageRef.current }, (_, i) => fetchRooms(f, i + 1)));
            const fresh = pages.flatMap((p) => p.rooms);
            const before = new Map(roomsRef.current.map((r) => [r.id, r]));
            const after = new Map(fresh.map((r) => [r.id, r]));
            const own = ownBookingRef.current;
            const isOwn = (id) => own.roomId === id && Date.now() - own.at < 15000;
            const allLoaded = pageRef.current >= (pages[0]?.pagination?.lastPage || 1);

            before.forEach((prev, id) => {
                if (isOwn(id)) return;
                const next = after.get(id);
                if (!next && allLoaded) {
                    toast(`Room ${prev.num} is now fully booked`, { icon: "fa-solid fa-door-closed text-slate-500" });
                } else if (next && next.empty_beds < prev.empty_beds) {
                    toast(next.per_bed
                        ? `Room ${prev.num} just got booked — ${next.empty_beds} bed${next.empty_beds === 1 ? "" : "s"} left`
                        : `Room ${prev.num} just got booked`);
                }
            });
            if (allLoaded) {
                after.forEach((next, id) => {
                    if (!before.has(id)) toast(`Room ${next.num} just opened up`, { icon: "fa-solid fa-door-open text-emerald-600" });
                });
            }

            const last = pages[pages.length - 1];
            setData({ ...last, rooms: fresh });
            setRooms(fresh);
        } catch {
            // live refresh is best-effort
        }
    }, []);

    const markOwnBooking = useCallback((roomId) => {
        ownBookingRef.current = { roomId, at: Date.now() };
    }, []);

    return {
        data, rooms, page, filters, loading, loadingMore, error, resultsKey,
        loaded: !!data,
        hasMore: !!data && page < (data.pagination?.lastPage || 1),
        total: data?.pagination?.total ?? rooms.length,
        updateFilters, clearFilters, loadMore, refresh, ensureLoaded, markOwnBooking,
    };
}
