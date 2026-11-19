// resources/js/pages/Dashboard/components/HeroSearch.jsx
// "Find your perfect space to stay." — photo hero with a search bar that
// applies its choices to the room browser below (Explore).
import React, { useEffect, useState } from "react";
import { LIMITS } from "../../../utils/inputLimits";
import { cx } from "./ui";

export const PRICE_OPTIONS = [
    { value: "recommended", label: "Any Price" },
    { value: "price_asc", label: "Lower Price" },
    { value: "price_desc", label: "Higher Price" },
];

export const TYPE_OPTIONS = [
    { value: "", label: "All Types" },
    { value: "Solo Room", label: "Solo Room" },
    { value: "Barkada Room", label: "Barkada Room" },
    { value: "Bedspace", label: "Bedspace" },
];

export const AVAILABILITY_OPTIONS = [
    { value: "", label: "Any Status" },
    { value: "available", label: "Available" },
    { value: "reserved", label: "Reserved" },
    { value: "occupied", label: "Partly occupied" },
];

const fromFilters = (f) => ({
    search: f.search || "",
    sort: ["price_asc", "price_desc"].includes(f.sort) ? f.sort : "recommended",
    type: f.type?.length === 1 ? f.type[0] : "",
    availability: f.availability || "",
});

/** Label on top, value underneath, chevron on the right. Flexible width so the bar never overflows. */
function HeroSelect({ id, label, value, onChange, options, className = "" }) {
    return (
        <label htmlFor={id} className={cx("relative flex flex-col justify-center px-4 py-1.5 min-w-0", className)}>
            <span className="text-[10.5px] font-semibold uppercase tracking-wide text-slate-500">{label}</span>
            <select id={id} value={value} onChange={(e) => onChange(e.target.value)}
                className="w-full appearance-none bg-transparent pr-6 text-[14px] font-bold text-slate-900 cursor-pointer truncate focus:outline-none">
                {options.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
            </select>
            <i className="fa-solid fa-caret-down absolute right-4 bottom-2.5 text-slate-500 text-[12px] pointer-events-none" aria-hidden="true" />
        </label>
    );
}

export default function HeroSearch({ firstName, filters, onExplore }) {
    const [form, setForm] = useState(() => fromFilters(filters));
    // Keep in step when the browser's own filters change
    useEffect(() => setForm(fromFilters(filters)), [filters.search, filters.sort, filters.type, filters.availability]); // eslint-disable-line react-hooks/exhaustive-deps

    const set = (k) => (v) => setForm((f) => ({ ...f, [k]: v }));
    const submit = (e) => {
        e.preventDefault();
        onExplore({
            search: form.search.trim(),
            sort: form.sort,
            type: form.type ? [form.type] : [],
            availability: form.availability || null,
        });
    };

    return (
        <section className="relative overflow-hidden bg-[#0F1A33] text-white" aria-labelledby="dash-hero-title">
            <img src="/images/hero-bg.jpg" alt="" aria-hidden="true"
                className="absolute inset-0 w-full h-full object-cover" />
            <div className="absolute inset-0 bg-gradient-to-r from-[#0F1A33]/90 via-[#0F1A33]/70 to-[#0F1A33]/35" aria-hidden="true" />

            {/* Fills the first screen under the sticky header (≈88px); search bar sits at the bottom */}
            <div className="relative max-w-[1440px] mx-auto px-4 md:px-6 xl:px-8 pt-12 pb-8 sm:pb-12 min-h-[max(560px,calc(100dvh-88px))] flex flex-col">
                <div className="flex-1 flex flex-col justify-center">
                    <p className="text-[14px] sm:text-[15px] text-white/85 mb-3 rb-fade-up">Welcome back, {firstName}!</p>
                    <h1 id="dash-hero-title" className="font-extrabold tracking-tight leading-[1.1] text-[32px] sm:text-[42px] lg:text-[50px] rb-fade-up">
                        Find your perfect
                        <span className="block text-[#FF8A1F]">space to stay.</span>
                    </h1>
                    <p className="mt-4 max-w-[540px] text-[14px] sm:text-[16px] leading-relaxed text-white/80">
                        Discover spaces designed for every way of living, whether you need a quiet solo retreat or a lively group stay.
                    </p>
                </div>

                <form onSubmit={submit} role="search" aria-label="Search rooms"
                    className="mt-10 max-w-[1100px] bg-white text-slate-900 rounded-2xl shadow-xl p-2 flex flex-col lg:flex-row lg:items-stretch gap-1 lg:gap-0">
                    <label className="relative flex items-center lg:flex-[1.4] min-w-0">
                        <span className="sr-only">Search rooms</span>
                        <i className="fa-solid fa-magnifying-glass absolute left-4 text-slate-400 text-[15px]" aria-hidden="true" />
                        <input type="search" value={form.search} onChange={(e) => set("search")(e.target.value)}
                            maxLength={LIMITS.SEARCH} placeholder="Search rooms" autoComplete="off"
                            className="w-full h-12 pl-11 pr-3 rounded-xl bg-transparent text-[14px] placeholder-slate-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#E8710A]" />
                    </label>

                    <div className="grid grid-cols-1 sm:grid-cols-3 lg:flex-[3] min-w-0">
                        <HeroSelect id="hero-price" label="Price" value={form.sort} onChange={set("sort")} options={PRICE_OPTIONS}
                            className="lg:border-l border-slate-200 my-1" />
                        <HeroSelect id="hero-type" label="Room Type" value={form.type} onChange={set("type")} options={TYPE_OPTIONS}
                            className="sm:border-l border-slate-200 my-1" />
                        <HeroSelect id="hero-availability" label="Availability" value={form.availability} onChange={set("availability")} options={AVAILABILITY_OPTIONS}
                            className="sm:border-l border-slate-200 my-1" />
                    </div>

                    <button type="submit"
                        className="rb-btn shrink-0 h-12 px-6 rounded-xl bg-[#F7860F] hover:bg-[#E57700] active:bg-[#CC6A00] text-white text-[14.5px] font-bold inline-flex items-center justify-center gap-2 shadow-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-[#E8710A] lg:self-center">
                        Explore <i className="fa-solid fa-arrow-right text-[13px]" aria-hidden="true" />
                    </button>
                </form>
            </div>
        </section>
    );
}
