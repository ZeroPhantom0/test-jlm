// resources/js/pages/Dashboard/components/ui.jsx
// Shared building blocks for the tenant dashboard.
import React, { useEffect, useState } from "react";
import { useCountUp, useInView } from "../../../utils/motion";

export const cx = (...c) => c.filter(Boolean).join(" ");

export const peso = (n) =>
    "₱" + Number(n || 0).toLocaleString("en-PH", { maximumFractionDigits: 0 });

/** Brand colors. CTA shades are darkened so white text passes WCAG AA (≥4.5:1). */
export const BRAND = {
    navy: "#0F1A33",
    orange: "#E8710A", // accents, rings, borders
    orangeCta: "#BF5600", // button backgrounds (white text 4.6:1)
    green: "#16A34A",
    greenCta: "#15803D", // Pay Now (white text 5:1)
    red: "#E11D48",
    yellow: "#FEF9E7",
};

const rtf = typeof Intl !== "undefined" && Intl.RelativeTimeFormat
    ? new Intl.RelativeTimeFormat("en", { numeric: "auto" })
    : null;

export function relativeTime(iso) {
    if (!iso) return "";
    const diff = (new Date(iso).getTime() - Date.now()) / 1000;
    const units = [["year", 31536000], ["month", 2592000], ["week", 604800], ["day", 86400], ["hour", 3600], ["minute", 60]];
    for (const [unit, secs] of units) {
        if (Math.abs(diff) >= secs) {
            const v = Math.round(diff / secs);
            return rtf ? rtf.format(v, unit) : `${Math.abs(v)} ${unit}${Math.abs(v) === 1 ? "" : "s"} ${v < 0 ? "ago" : ""}`;
        }
    }
    return "just now";
}

export const formatDate = (iso, opts = { month: "short", day: "numeric", year: "numeric" }) =>
    iso ? new Date(`${iso}T00:00:00`).toLocaleDateString("en-PH", opts) : "—";

// ── Buttons ──────────────────────────────────────────────────
const btnBase =
    "rb-btn inline-flex items-center justify-center gap-2 rounded-xl font-bold select-none " +
    "focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60";

export const btn = {
    primary: cx(btnBase, "bg-[#BF5600] hover:bg-[#A84C00] active:bg-[#934300] text-white shadow-sm hover:shadow-md focus-visible:ring-[#E8710A]"),
    pay: cx(btnBase, "bg-[#15803D] hover:bg-[#166534] active:bg-[#14532d] text-white shadow-sm hover:shadow-md focus-visible:ring-[#16A34A]"),
    secondary: cx(btnBase, "bg-white border border-slate-200 hover:bg-slate-50 hover:border-slate-300 active:bg-slate-100 text-slate-800 focus-visible:ring-slate-300"),
    ghostDark: cx(btnBase, "bg-white/10 hover:bg-white/20 active:bg-white/25 border border-white/20 text-white focus-visible:ring-white/60 focus-visible:ring-offset-[#0F1A33]"),
    disabled: cx(btnBase, "bg-slate-100 text-slate-400 cursor-not-allowed"),
    md: "h-10 px-4 text-[13px]",
    lg: "h-11 px-5 text-[13.5px]",
};

export const Arrow = () => <i className="fa-solid fa-arrow-right rb-arrow text-[12px]" aria-hidden="true" />;

// ── Bento grid ───────────────────────────────────────────────
// 12 columns ≥1280px, 6 columns from 768px, single column on mobile.
const XL = { 3: "xl:col-span-3", 4: "xl:col-span-4", 5: "xl:col-span-5", 6: "xl:col-span-6", 7: "xl:col-span-7", 8: "xl:col-span-8", 12: "xl:col-span-12" };
const MD = { 2: "md:col-span-2", 3: "md:col-span-3", 4: "md:col-span-4", 6: "md:col-span-6" };

export function BentoGrid({ children, className = "" }) {
    return <div className={cx("grid grid-cols-1 md:grid-cols-6 xl:grid-cols-12 gap-4", className)}>{children}</div>;
}

export function BentoTile({ xl = 12, md = 6, index = 0, tone = "light", className = "", as: Tag = "section", children, ...rest }) {
    return (
        <Tag
            {...rest}
            style={{ animationDelay: `${index * 60}ms`, ...(rest.style || {}) }}
            className={cx(
                "rb-fade-up rounded-2xl min-w-0",
                XL[xl], MD[md],
                tone === "dark" ? "bg-[#0F1A33] text-white" : tone === "none" ? "" : "bg-white border border-slate-200/80 shadow-[0_1px_2px_rgba(15,26,51,0.04)]",
                className,
            )}
        >
            {children}
        </Tag>
    );
}

export function TileHeader({ icon, iconClass = "bg-orange-50 text-[#BF5600]", title, subtitle, action }) {
    return (
        <div className="flex items-start justify-between gap-3 mb-3">
            <div className="flex items-center gap-2.5 min-w-0">
                {icon && (
                    <span className={cx("w-9 h-9 rounded-xl flex items-center justify-center shrink-0 text-[14px]", iconClass)} aria-hidden="true">
                        <i className={icon} />
                    </span>
                )}
                <div className="min-w-0">
                    <h2 className="text-[15px] font-bold text-slate-900 leading-tight">{title}</h2>
                    {subtitle && <p className="text-[12.5px] text-slate-500 mt-0.5">{subtitle}</p>}
                </div>
            </div>
            {action}
        </div>
    );
}

export function ViewAll({ href, label = "View all" }) {
    return (
        <a href={href} className="inline-flex items-center gap-1.5 text-[12.5px] font-bold text-[#BF5600] hover:text-[#934300] rounded-lg px-2 py-2 -mr-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#E8710A] whitespace-nowrap">
            {label} <i className="fa-solid fa-arrow-right text-[10px]" aria-hidden="true" />
        </a>
    );
}

/** Number that counts up when it scrolls into view. */
export function CountUp({ value, prefix = "", suffix = "", className = "", format = (n) => n.toLocaleString("en-PH") }) {
    const [ref, inView] = useInView();
    const shown = useCountUp(Math.round(Number(value) || 0), inView);
    return <span ref={ref} className={cx("tabular-nums", className)}>{prefix}{format(shown)}{suffix}</span>;
}

export function SkeletonCard({ view = "grid" }) {
    return (
        <div className={cx("bg-white rounded-2xl border border-slate-200 overflow-hidden flex", view === "list" ? "flex-col sm:flex-row" : "flex-col")} aria-hidden="true">
            <div className={cx("rb-skeleton", view === "list" ? "sm:w-56 aspect-[4/3] sm:aspect-auto" : "aspect-[16/10]")} />
            <div className="p-4 space-y-3 flex-1">
                <div className="rb-skeleton h-4 w-1/2 rounded" />
                <div className="rb-skeleton h-3 w-1/3 rounded" />
                <div className="rb-skeleton h-3 w-2/3 rounded" />
                <div className="grid grid-cols-2 gap-2 pt-2"><div className="rb-skeleton h-11 rounded-xl" /><div className="rb-skeleton h-11 rounded-xl" /></div>
            </div>
        </div>
    );
}

/** Status pill used by the activity timeline. */
export function StatusPill({ status }) {
    const s = String(status || "pending").toLowerCase();
    const tone = {
        approved: "bg-emerald-50 text-emerald-700 ring-emerald-200",
        completed: "bg-emerald-50 text-emerald-700 ring-emerald-200",
        paid: "bg-emerald-50 text-emerald-700 ring-emerald-200",
        rejected: "bg-rose-50 text-rose-700 ring-rose-200",
        cancelled: "bg-rose-50 text-rose-700 ring-rose-200",
        expired: "bg-slate-100 text-slate-600 ring-slate-200",
        partial: "bg-orange-50 text-[#934300] ring-orange-200",
        pending: "bg-orange-50 text-[#934300] ring-orange-200",
    }[s] || "bg-slate-100 text-slate-600 ring-slate-200";
    return <span className={cx("inline-flex shrink-0 items-center rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide ring-1", tone)}>{s}</span>;
}

/** Re-render every minute so relative dates ("5 minutes ago") stay fresh. */
export function useMinuteTick() {
    const [, setT] = useState(0);
    useEffect(() => {
        const id = setInterval(() => setT((t) => t + 1), 60000);
        return () => clearInterval(id);
    }, []);
}

/** 360° virtual tour link (public tour viewer, returns to the dashboard). */
export function tourHref(tourId) {
    const from = window.location.pathname + window.location.search;
    return `/tour/${tourId}?from=${encodeURIComponent(from)}`;
}
