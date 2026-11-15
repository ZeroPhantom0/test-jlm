// resources/js/pages/Dashboard/components/RoomFilters.jsx
import React, { useEffect, useRef, useState } from "react";
import { createPortal } from "react-dom";
import { LIMITS } from "../../../utils/inputLimits";
import { activeFilterCount } from "../hooks/useRoomCatalog";
import { useDialog } from "./RoomDetailDrawer";
import { btn, cx, peso } from "./ui";


export const SORTS = [
    { value: "recommended", label: "Recommended" },
    { value: "price_asc", label: "Price: low to high" },
    { value: "price_desc", label: "Price: high to low" },
    { value: "beds", label: "Most beds left" },
    { value: "newest", label: "Newest" },
];

/** Dual-thumb slider over the daily rate. */
export function PriceRange({ facets, filters, onChange }) {
    const lo = facets.priceMin, hi = facets.priceMax;
    const [vals, setVals] = useState([filters.min_price ?? lo, filters.max_price ?? hi]);
    useEffect(() => setVals([filters.min_price ?? lo, filters.max_price ?? hi]), [filters.min_price, filters.max_price, lo, hi]);
    if (!(hi > lo)) return null;
    const step = Math.max(1, Math.round((hi - lo) / 50));
    const pct = (v) => ((v - lo) / (hi - lo)) * 100;
    const commit = ([a, b]) => onChange({ min_price: a <= lo ? null : a, max_price: b >= hi ? null : b });
    return (
        <div className="min-w-[220px]">
            <div className="flex items-center justify-between text-[11.5px] font-semibold text-slate-600 mb-1 tabular-nums">
                <span>Price per day</span><span>{peso(vals[0])} – {peso(vals[1])}</span>
            </div>
            <div className="rb-range">
                <div className="absolute top-1/2 -translate-y-1/2 left-0 right-0 h-1.5 rounded-full bg-slate-200" />
                <div className="absolute top-1/2 -translate-y-1/2 h-1.5 rounded-full bg-[#E8710A]" style={{ left: `${pct(vals[0])}%`, right: `${100 - pct(vals[1])}%` }} />
                <input type="range" min={lo} max={hi} step={step} value={vals[0]} aria-label="Minimum price per day" aria-valuetext={`${peso(vals[0])} per day`}
                    onChange={(e) => setVals(([, b]) => [Math.min(Number(e.target.value), b), b])}
                    onPointerUp={() => commit(vals)} onKeyUp={() => commit(vals)} />
                <input type="range" min={lo} max={hi} step={step} value={vals[1]} aria-label="Maximum price per day" aria-valuetext={`${peso(vals[1])} per day`}
                    onChange={(e) => setVals(([a]) => [a, Math.max(Number(e.target.value), a)])}
                    onPointerUp={() => commit(vals)} onKeyUp={() => commit(vals)} />
            </div>
        </div>
    );
}

const ALL_TYPES = ["Solo Room", "Bedspace", "Barkada Room"];
const TYPE_META = {
    "Solo Room": { label: "Solo", icon: "fa-solid fa-user", hint: "Whole room, 1–2 pax" },
    Bedspace: { label: "Bedspace", icon: "fa-solid fa-bed", hint: "Rent a single bed" },
    "Barkada Room": { label: "Barkada", icon: "fa-solid fa-user-group", hint: "Whole room for groups" },
};

/** All three room types are always shown, with how many are open right now. */
export function TypeChips({ facets, filters, onChange }) {
    const types = facets.types?.length ? facets.types : ALL_TYPES;
    const counts = facets.typeCounts || {};
    const toggle = (t) => onChange({ type: filters.type.includes(t) ? filters.type.filter((x) => x !== t) : [...filters.type, t] });
    const chip = (on) => cx("rb-btn h-11 md:h-10 px-3.5 rounded-full border text-[12.5px] font-semibold inline-flex items-center gap-2 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-[#E8710A]",
        on ? "bg-[#0F1A33] border-[#0F1A33] text-white" : "bg-white border-slate-200 text-slate-700 hover:border-slate-300 active:bg-slate-50");
    const countCls = (on) => cx("min-w-5 h-5 px-1.5 rounded-full text-[10.5px] font-bold inline-flex items-center justify-center tabular-nums",
        on ? "bg-white/20 text-white" : "bg-slate-100 text-slate-600");
    const all = filters.type.length === 0;

    return (
        <div className="flex flex-wrap gap-2" role="group" aria-label="Room type">
            <button type="button" aria-pressed={all} onClick={() => onChange({ type: [] })} className={chip(all)}>
                All types
            </button>
            {types.map((t) => {
                const on = filters.type.includes(t);
                const meta = TYPE_META[t] || { label: t, icon: "fa-solid fa-door-open" };
                const n = counts[t];
                return (
                    <button key={t} type="button" aria-pressed={on} onClick={() => toggle(t)} title={meta.hint} className={chip(on)}>
                        <i className={cx(meta.icon, "text-[11px]")} aria-hidden="true" />
                        {meta.label}
                        {n != null && <span className={countCls(on)} aria-label={`${n} available`}>{n}</span>}
                    </button>
                );
            })}
        </div>
    );
}

function SelectField({ label, value, onChange, children }) {
    return (
        <label className="flex flex-col gap-1 text-[11.5px] font-semibold text-slate-600">
            <span>{label}</span>
            <select value={value} onChange={onChange}
                className="h-11 md:h-9 rounded-lg border border-slate-200 bg-white px-3 text-[13px] text-slate-800 hover:border-slate-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#E8710A]">
                {children}
            </select>
        </label>
    );
}

export function FilterControls({ facets, filters, onChange, showTypes = true }) {
    return (
        <>
            {showTypes && <TypeChips facets={facets} filters={filters} onChange={onChange} />}
            <PriceRange facets={facets} filters={filters} onChange={onChange} />
            {facets.floors?.length > 1 && (
                <SelectField label="Floor" value={filters.floor ?? ""} onChange={(e) => onChange({ floor: e.target.value ? Number(e.target.value) : null })}>
                    <option value="">Any floor</option>
                    {facets.floors.map((fl) => <option key={fl} value={fl}>Floor {fl}</option>)}
                </SelectField>
            )}
            <SelectField label="Sort by" value={filters.sort} onChange={(e) => onChange({ sort: e.target.value })}>
                {SORTS.map((s) => <option key={s.value} value={s.value}>{s.label}</option>)}
            </SelectField>
        </>
    );
}

/** Search box with suggestions (room numbers + types currently available). */
export function SearchBox({ value, onChange, suggestions = [] }) {
    return (
        <label className="relative flex-1 min-w-[180px]">
            <span className="sr-only">Search rooms</span>
            <i className="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[13px]" aria-hidden="true" />
            <input type="search" value={value} maxLength={LIMITS.SEARCH} list="room-search-suggestions"
                onChange={(e) => onChange(e.target.value)} placeholder="Search room number or type…" autoComplete="off"
                className="w-full h-11 md:h-10 pl-9 pr-3 rounded-xl border border-slate-200 bg-white text-[13px] hover:border-slate-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#E8710A]" />
            <datalist id="room-search-suggestions">
                {suggestions.map((s) => <option key={s} value={s} />)}
            </datalist>
        </label>
    );
}

function FilterSheetInner({ facets, filters, onChange, onClear, total, loading, onClose }) {
    const ref = useRef(null);
    useDialog(ref, onClose);
    const count = activeFilterCount(filters);
    return (
        <div className="fixed inset-0 z-[1200] md:hidden" role="dialog" aria-modal="true" aria-labelledby="filter-sheet-title">
            <div className="absolute inset-0 bg-[#0F1A33]/55 backdrop-blur-sm rb-fade-in" onClick={onClose} />
            <div ref={ref} className="rb-sheet absolute bottom-0 left-0 right-0 bg-white rounded-t-2xl max-h-[85dvh] flex flex-col overflow-hidden">
                <div className="flex items-center justify-between px-5 pt-4 pb-2">
                    <h2 id="filter-sheet-title" className="text-[16px] font-bold">Filters</h2>
                    <button type="button" onClick={onClose} aria-label="Close filters" data-autofocus
                        className="w-11 h-11 rounded-full flex items-center justify-center text-slate-500 hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-300">
                        <i className="fa-solid fa-xmark text-lg" aria-hidden="true" />
                    </button>
                </div>
                <div className="flex-1 overflow-y-auto px-5 pb-4 space-y-5">
                    <FilterControls facets={facets} filters={filters} onChange={onChange} showTypes={false} />
                </div>
                <div className="shrink-0 flex gap-2 px-4 pt-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] border-t border-slate-200 bg-white">
                    {count > 0 && <button type="button" onClick={onClear} className={cx(btn.secondary, btn.lg)}>Clear</button>}
                    <button type="button" onClick={onClose} className={cx(btn.primary, btn.lg, "flex-1")}>
                        {loading ? <i className="fa-solid fa-spinner fa-spin" aria-label="Updating" /> : `Show ${total} room${total === 1 ? "" : "s"}`}
                    </button>
                </div>
            </div>
        </div>
    );
}

/** Rendered into <body> (see RoomDetailDrawer). */
export function FilterSheet(props) {
    return createPortal(<FilterSheetInner {...props} />, document.body);
}
