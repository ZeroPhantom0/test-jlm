// resources/js/pages/Dashboard/components/RecommendedRooms.jsx
// T3 — "Picked for you": featured #1 + runner-ups (carousel on mobile).
import React from "react";
import { FeaturedRoomCard, MiniRoomCard } from "./RoomCard";
import { BentoTile, btn, cx, SkeletonCard } from "./ui";

export default function RecommendedRooms({ catalog, mode, onDetails, compact = false, xl = 8, md = 6, index = 0, onBrowse }) {
    const recs = catalog.data?.recommended || [];
    const roomChange = catalog.data?.roomChange;

    return (
        <BentoTile id="recommended" xl={xl} md={md} index={index} className="p-4 md:p-5 scroll-mt-24" aria-labelledby="rec-title">
            <div className="flex items-start justify-between gap-3 mb-3">
                <div>
                    <h2 id="rec-title" className="text-[16px] font-bold text-slate-900 flex items-center gap-2">
                        {compact ? "While you wait" : "Picked for you"} <span aria-hidden="true">✨</span>
                    </h2>
                    <p className="text-[12.5px] text-slate-500">Based on your budget and past bookings</p>
                </div>
                <a href="#browse-rooms" onClick={onBrowse}
                    className="hidden sm:inline-flex items-center gap-1.5 text-[12.5px] font-bold text-[#BF5600] hover:text-[#934300] rounded-lg px-2 py-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#E8710A]">
                    All rooms <i className="fa-solid fa-arrow-right text-[10px]" aria-hidden="true" />
                </a>
            </div>

            {!catalog.data ? (
                <div className="grid gap-5 lg:grid-cols-12"><div className="lg:col-span-7"><SkeletonCard /></div><div className="lg:col-span-5 space-y-3"><SkeletonCard view="list" /></div></div>
            ) : !recs.length ? (
                <div className="rounded-2xl bg-slate-50 p-8 text-center">
                    <i className="fa-regular fa-compass text-3xl text-slate-300" aria-hidden="true" />
                    <p className="mt-2 text-[14px] font-semibold text-slate-700">No picks right now</p>
                    <p className="text-[12.5px] text-slate-500">Every available room is in the list below.</p>
                    <a href="#browse-rooms" onClick={onBrowse} className={cx(btn.primary, btn.md, "mt-4")}>Browse all rooms</a>
                </div>
            ) : !compact && recs.length === 2 ? (
                // Two picks: equal halves, so neither side leaves empty space
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {recs.map((r, i) => (
                        <FeaturedRoomCard key={r.id} room={r} rank={i + 1} mode={mode} roomChange={roomChange} onDetails={onDetails} />
                    ))}
                </div>
            ) : compact ? (
                <div className="grid gap-2.5 sm:grid-cols-2 xl:grid-cols-3">
                    {recs.slice(0, 3).map((r, i) => (
                        <MiniRoomCard key={r.id} room={r} onDetails={onDetails} delay={i * 60} />
                    ))}
                </div>
            ) : (
                <div className="grid grid-cols-1 lg:grid-cols-12 gap-5">
                    <div className={recs.length > 1 ? "lg:col-span-7" : "lg:col-span-12"}>
                        <FeaturedRoomCard room={recs[0]} mode={mode} roomChange={roomChange} onDetails={onDetails} />
                    </div>
                    {recs.length > 1 && (
                        <div className="lg:col-span-5 flex flex-col gap-2.5 min-w-0" aria-label="More picks">
                            <p className="text-[11.5px] font-bold uppercase tracking-wider text-slate-500">More picks</p>
                            {recs.slice(1).map((r, i) => (
                                <MiniRoomCard key={r.id} room={r} onDetails={onDetails} delay={(i + 1) * 60} />
                            ))}
                            <a href="#browse-rooms" onClick={onBrowse}
                                className="mt-auto inline-flex items-center justify-center gap-1.5 h-10 rounded-xl border border-dashed border-slate-300 text-[12.5px] font-semibold text-slate-600 hover:border-[#E8710A] hover:text-[#BF5600] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#E8710A]">
                                Browse all rooms <i className="fa-solid fa-arrow-right text-[10px]" aria-hidden="true" />
                            </a>
                        </div>
                    )}
                </div>
            )}
        </BentoTile>
    );
}
