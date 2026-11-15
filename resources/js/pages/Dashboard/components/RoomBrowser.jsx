// resources/js/pages/Dashboard/components/RoomBrowser.jsx
// T4 — Browse All Rooms: [For You] [All Rooms (N)] tabs, sticky filters,
// grid/list, load more, skeletons, empty state. Data comes from useRoomCatalog.
import React, { useEffect, useLayoutEffect, useMemo, useRef, useState } from "react";
import { activeFilterCount, PER_PAGE } from "../hooks/useRoomCatalog";
import { FilterControls, FilterSheet, SearchBox, TypeChips } from "./RoomFilters";
import { RoomCard } from "./RoomCard";
import { btn, cx, SkeletonCard } from "./ui";

export default function RoomBrowser({ catalog, mode, onDetails, defaultTab = "all", id = "browse-rooms" }) {
    const { data, rooms, filters, loading, loadingMore, error, resultsKey, hasMore, total, updateFilters, clearFilters, loadMore } = catalog;
    const [tab, setTab] = useState(activeFilterCount(filters) > 0 ? "all" : defaultTab);
    const [view, setView] = useState(() => localStorage.getItem("rb:view") || "grid");
    const [sheetOpen, setSheetOpen] = useState(false);
    useEffect(() => localStorage.setItem("rb:view", view), [view]);

    // Sticky filter bar sits right under the sticky top header
    const [stickyTop, setStickyTop] = useState(0);
    useLayoutEffect(() => {
        const header = document.getElementById("tenant-header-root");
        const measure = () => setStickyTop(header?.offsetHeight || 0);
        measure();
        window.addEventListener("resize", measure);
        return () => window.removeEventListener("resize", measure);
    }, []);

    // Sliding tab indicator
    const tabRefs = { foryou: useRef(null), all: useRef(null) };
    const [indicator, setIndicator] = useState({ left: 0, width: 0 });
    useLayoutEffect(() => {
        const el = tabRefs[tab]?.current;
        if (el) setIndicator({ left: el.offsetLeft, width: el.offsetWidth });
    }, [tab, data?.totalAvailable]); // eslint-disable-line react-hooks/exhaustive-deps

    const facets = data?.facets || {};
    const filterCount = activeFilterCount(filters);
    const suggestions = useMemo(
        () => [...new Set([...(facets.types || []), ...rooms.map((r) => `Room ${r.num}`)])],
        [facets.types, rooms],
    );
    const forYou = data?.recommended || [];
    const shown = tab === "foryou" ? forYou : rooms;
    const gridCls = cx("grid gap-4", view === "grid" ? "sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4" : "grid-cols-1");

    return (
        <div id={id} className="space-y-4 scroll-mt-24">
            <div className="flex flex-col sm:flex-row sm:items-end justify-between gap-3">
                <div>
                    <h2 className="text-[18px] font-bold text-slate-900">Browse rooms</h2>
                    <p className="text-[12.5px] text-slate-500">Priced per day — stay a night or longer. What you see is what you pay.</p>
                </div>
                <div className="relative inline-flex bg-slate-100 rounded-xl p-1 self-start" role="tablist" aria-label="Room lists">
                    <span aria-hidden="true" className="absolute top-1 bottom-1 rounded-lg bg-white shadow-sm transition-all duration-300 ease-out"
                        style={{ left: indicator.left, width: indicator.width }} />
                    {[["foryou", "For You"], ["all", `All Rooms (${data?.totalAvailable ?? 0})`]].map(([key, label]) => (
                        <button key={key} ref={tabRefs[key]} type="button" role="tab" aria-selected={tab === key} onClick={() => setTab(key)}
                            className={cx("relative z-10 h-10 px-4 rounded-lg text-[13px] font-semibold transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-[#E8710A]",
                                tab === key ? "text-slate-900" : "text-slate-500 hover:text-slate-700")}>
                            {label}
                        </button>
                    ))}
                </div>
            </div>

            {tab === "all" && (
                <div className="sticky z-20 -mx-4 md:mx-0 px-4 py-3 bg-[#F5F7FA]/95 backdrop-blur md:rounded-2xl md:border md:border-slate-200 md:bg-white/95"
                    style={{ top: stickyTop }}>
                    {/* Room type is the main decision → always visible, on every screen size */}
                    <div className="mb-3 overflow-x-auto -mx-1 px-1">
                        <TypeChips facets={facets} filters={filters} onChange={updateFilters} />
                    </div>
                    <div className="flex flex-wrap items-end gap-3">
                        <SearchBox value={filters.search} onChange={(v) => updateFilters({ search: v })} suggestions={suggestions} />
                        <div className="hidden md:flex flex-wrap items-end gap-3">
                            <FilterControls facets={facets} filters={filters} onChange={updateFilters} showTypes={false} />
                        </div>
                        <button type="button" onClick={() => setSheetOpen(true)} className={cx(btn.secondary, btn.md, "md:hidden")}>
                            <i className="fa-solid fa-sliders" aria-hidden="true" /> Filters{filterCount ? ` (${filterCount})` : ""}
                        </button>
                        {filterCount > 0 && (
                            <button type="button" onClick={clearFilters}
                                className="h-11 md:h-9 px-2 text-[12.5px] font-bold text-[#BF5600] hover:text-[#934300] hover:underline rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-[#E8710A]">
                                Clear filters
                            </button>
                        )}
                        <div className="ml-auto inline-flex rounded-xl border border-slate-200 bg-white p-0.5" role="group" aria-label="Layout">
                            {[["grid", "fa-solid fa-table-cells-large", "Grid view"], ["list", "fa-solid fa-list", "List view"]].map(([v, icon, label]) => (
                                <button key={v} type="button" aria-pressed={view === v} aria-label={label} onClick={() => setView(v)}
                                    className={cx("w-11 h-10 md:h-9 rounded-lg flex items-center justify-center transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-[#E8710A]",
                                        view === v ? "bg-[#0F1A33] text-white" : "text-slate-500 hover:bg-slate-100")}>
                                    <i className={cx(icon, "text-[13px]")} aria-hidden="true" />
                                </button>
                            ))}
                        </div>
                    </div>
                </div>
            )}

            {error && (
                <div className="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-[13px] text-rose-800 flex items-center gap-2" role="alert">
                    <i className="fa-solid fa-circle-exclamation" aria-hidden="true" /> {error}
                </div>
            )}

            {tab === "all" && (
                <p className="text-[12.5px] text-slate-500" aria-live="polite">
                    {loading ? "Finding rooms…" : `${total} room${total === 1 ? "" : "s"} match`}
                </p>
            )}

            {loading || !data ? (
                <div className={gridCls}>{Array.from({ length: 3 }).map((_, i) => <SkeletonCard key={i} view={view} />)}</div>
            ) : shown.length ? (
                <div key={`${tab}-${resultsKey}`} className={gridCls}>
                    {shown.map((r, i) => (
                        <RoomCard key={r.id} room={r} mode={mode} roomChange={data.roomChange} view={view}
                            delay={(i % PER_PAGE) * 60} onDetails={onDetails} />
                    ))}
                    {loadingMore && Array.from({ length: 3 }).map((_, i) => <SkeletonCard key={`more-${i}`} view={view} />)}
                </div>
            ) : (
                <div className="bg-white rounded-2xl border border-slate-200 p-8 text-center rb-fade-in">
                    <div className="w-14 h-14 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center text-2xl mx-auto" aria-hidden="true">
                        <i className="fa-solid fa-bed" />
                    </div>
                    <p className="mt-3 text-[15px] font-bold text-slate-800">
                        {tab === "foryou"
                            ? "No picks for you right now"
                            : filters.type.length === 1 && !(facets.typeCounts?.[filters.type[0]])
                                ? `No ${filters.type[0].replace(" Room", "")} rooms are open right now`
                                : "No rooms match your filters"}
                    </p>
                    <p className="text-[12.5px] text-slate-500">
                        {tab === "foryou" ? "Check all rooms — new spots open up often." : "Try widening the price range or another bed type."}
                    </p>
                    {tab === "foryou" ? (
                        <button type="button" onClick={() => setTab("all")} className={cx(btn.primary, btn.md, "mt-4")}>See all rooms</button>
                    ) : filterCount > 0 && (
                        <button type="button" onClick={clearFilters} className={cx(btn.primary, btn.md, "mt-4")}>Clear filters</button>
                    )}
                </div>
            )}

            {tab === "all" && hasMore && !loading && (
                <div className="flex justify-center">
                    <button type="button" onClick={loadMore} disabled={loadingMore} className={cx(btn.secondary, btn.md, "px-6")}>
                        {loadingMore ? <><i className="fa-solid fa-spinner fa-spin" aria-hidden="true" /> Loading…</> : <>Load more rooms <i className="fa-solid fa-arrow-down text-[11px]" aria-hidden="true" /></>}
                    </button>
                </div>
            )}

            {sheetOpen && (
                <FilterSheet facets={facets} filters={filters} onChange={updateFilters} onClear={clearFilters}
                    total={total} loading={loading} onClose={() => setSheetOpen(false)} />
            )}
        </div>
    );
}
