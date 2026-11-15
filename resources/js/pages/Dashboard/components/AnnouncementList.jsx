// resources/js/pages/Dashboard/components/AnnouncementList.jsx
// T9 — pinned first (soft yellow), "New" = posted in the last 72h (no read tracking exists).
import React from "react";
import { BentoTile, cx, relativeTime, TileHeader, ViewAll } from "./ui";

export default function AnnouncementList({ items = [], href, xl = 4, md = 3, index = 0 }) {
    return (
        <BentoTile xl={xl} md={md} index={index} className="p-4 md:p-5 flex flex-col" aria-labelledby="ann-title">
            <TileHeader icon="fa-solid fa-bullhorn" iconClass="bg-sky-50 text-sky-700" title={<span id="ann-title">Announcements</span>} action={<ViewAll href={href} />} />
            {items.length ? (
                // Max 3 — the full list is one click away ("View all")
                <ul className="space-y-2">
                    {items.slice(0, 3).map((a) => (
                        <li key={a.id}>
                            <a href={href}
                                className={cx("block rounded-xl px-3 py-2.5 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-[#E8710A]",
                                    a.pinned ? "bg-[#FEF9E7] border border-amber-200/70 hover:border-amber-300" : "bg-slate-50 hover:bg-slate-100")}>
                                <div className="flex items-center gap-2 mb-0.5">
                                    {a.pinned && <span className="inline-flex items-center gap-1 text-[10px] font-bold text-amber-800 uppercase tracking-wide"><i className="fa-solid fa-thumbtack" aria-hidden="true" /> Pinned</span>}
                                    {a.isNew && <span className="inline-flex items-center gap-1 text-[10px] font-bold text-[#BE123C] uppercase tracking-wide"><span className="w-1.5 h-1.5 rounded-full bg-[#E11D48]" aria-hidden="true" /> New</span>}
                                    {(a.priority === "Urgent" || a.priority === "Important") && <span className="text-[10px] font-bold text-[#934300] uppercase tracking-wide">{a.priority}</span>}
                                    <span className="ml-auto text-[11px] text-slate-500 whitespace-nowrap">{relativeTime(a.date)}</span>
                                </div>
                                <h3 className="text-[13px] font-semibold text-slate-900 line-clamp-1">{a.title}</h3>
                                <p className="text-[12px] text-slate-500 leading-snug line-clamp-1">{a.excerpt}</p>
                            </a>
                        </li>
                    ))}
                </ul>
            ) : (
                <div className="flex-1 flex flex-col items-center justify-center text-center py-6 text-slate-500">
                    <i className="fa-regular fa-bell-slash text-2xl mb-2 text-slate-300" aria-hidden="true" />
                    <p className="text-[13px]">All quiet for now — new notices will show up here.</p>
                </div>
            )}
        </BentoTile>
    );
}
