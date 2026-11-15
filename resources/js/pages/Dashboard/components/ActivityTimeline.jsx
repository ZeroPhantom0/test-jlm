// resources/js/pages/Dashboard/components/ActivityTimeline.jsx
// T10 — last 5 reservations / service requests / payments.
import React from "react";
import { BentoTile, cx, relativeTime, StatusPill, TileHeader, ViewAll } from "./ui";

const TYPE_TINT = {
    reservation: "bg-orange-50 text-[#BF5600]",
    service: "bg-sky-50 text-sky-700",
    room_change: "bg-amber-50 text-amber-700",
    stay_extension: "bg-violet-50 text-violet-700",
    payment: "bg-emerald-50 text-emerald-700",
};

export default function ActivityTimeline({ items = [], href, xl = 4, md = 3, index = 0 }) {
    return (
        <BentoTile xl={xl} md={md} index={index} className="p-4 md:p-5 flex flex-col" aria-labelledby="activity-title">
            <TileHeader icon="fa-solid fa-clock-rotate-left" iconClass="bg-slate-100 text-slate-700" title={<span id="activity-title">History</span>} action={<ViewAll href={href} />} />
            {items.length ? (
                // Max 4 — the full history is one click away ("View all")
                <ol className="relative">
                    <span className="absolute left-[15px] top-2 bottom-2 w-px bg-slate-200" aria-hidden="true" />
                    {items.slice(0, 4).map((it) => (
                        <li key={it.key} className="relative flex items-center gap-3 py-1.5">
                            <span className={cx("relative z-10 w-8 h-8 rounded-full flex items-center justify-center shrink-0 ring-4 ring-white", TYPE_TINT[it.type] || "bg-slate-100 text-slate-600")} aria-hidden="true">
                                <i className={cx(it.icon, "text-[11px]")} />
                            </span>
                            <div className="flex-1 min-w-0">
                                <p className="text-[13px] font-medium text-slate-900 truncate">{it.title}</p>
                                <p className="text-[11.5px] text-slate-500 leading-tight">{relativeTime(it.date)}</p>
                            </div>
                            <StatusPill status={it.status} />
                        </li>
                    ))}
                </ol>
            ) : (
                <div className="flex-1 flex flex-col items-center justify-center text-center py-6 text-slate-500">
                    <i className="fa-regular fa-calendar text-2xl mb-2 text-slate-300" aria-hidden="true" />
                    <p className="text-[13px]">Nothing yet — your bookings and requests will appear here.</p>
                </div>
            )}
        </BentoTile>
    );
}
