// resources/js/pages/Dashboard/components/RoomCard.jsx
import React, { useState } from "react";
import { useCountUp, useInView } from "../../../utils/motion";
import { Arrow, btn, cx, peso, tourHref } from "./ui";
import MatchScoreRing from "./MatchScoreRing";

/** Rent is stored per day; a month here = 30 nights at the same daily rate. */
export const monthly = (daily) => Number(daily || 0) * 30;

/**
 * The only add-on charges in the booking flow (TenantReservationController):
 * Barkada rooms: ₱150/night per person beyond the bed count; other rooms:
 * ₱150 one-time when bringing a companion.
 */
export const extraGuestNote = (room) =>
    String(room.name || "").includes("Barkada")
        ? `₱150/night per extra person beyond ${room.total_beds}`
        : "₱150 one-time if you bring a companion";

/** Gradient placeholder behind the photo; the photo paints as it downloads. */
export function RoomImage({ room, className = "", rounded = "" }) {
    const [failed, setFailed] = useState(false);
    // Don't hard-code `relative`: callers pass `absolute inset-0`, and when both
    // classes are present `.relative` wins in the CSS → the box collapses to 0px
    // and the photo is invisible.
    const positioned = /\b(absolute|fixed|sticky)\b/.test(className);
    return (
        <div className={cx(!positioned && "relative", "overflow-hidden bg-gradient-to-br from-slate-200 via-slate-100 to-orange-50", rounded, className)}>
            {failed || !room.img ? (
                <div className="absolute inset-0 flex items-center justify-center text-slate-400" aria-hidden="true">
                    <i className="fa-solid fa-image text-3xl" />
                </div>
            ) : (
                <img src={room.img} alt={`Room ${room.num} — ${room.name || room.type || "room"}`} loading="lazy" decoding="async"
                    onError={() => setFailed(true)}
                    className="rb-img absolute inset-0 w-full h-full object-cover" />
            )}
        </div>
    );
}

export function BedsChip({ room, start = true }) {
    const n = useCountUp(room.empty_beds, start);
    if (!room.per_bed) {
        return (
            <span className="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 text-emerald-800 px-2.5 py-1 text-[11px] font-semibold">
                <i className="fa-solid fa-door-open text-[10px]" aria-hidden="true" /> Whole room · {room.total_beds} bed{room.total_beds === 1 ? "" : "s"}
            </span>
        );
    }
    if (room.empty_beds === 1) {
        return (
            <span className="rb-pulse inline-flex items-center gap-1.5 rounded-full bg-[#BF5600] text-white px-2.5 py-1 text-[11px] font-bold">
                <i className="fa-solid fa-fire text-[10px]" aria-hidden="true" /> Only 1 left!
            </span>
        );
    }
    const low = room.empty_beds <= 2;
    return (
        <span className={cx("inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold tabular-nums",
            low ? "bg-orange-100 text-[#934300]" : "bg-slate-100 text-slate-700")}>
            <i className="fa-solid fa-bed text-[10px]" aria-hidden="true" /> {n} bed{room.empty_beds === 1 ? "" : "s"} left
        </span>
    );
}

/**
 * Rent is charged per day (stays can be 1 night), so the daily rate leads;
 * week / month totals are shown as a guide for longer stays.
 */
export function Price({ room, size = "md" }) {
    return (
        <div className="tabular-nums text-right leading-tight">
            <div>
                <span className={cx("font-extrabold text-slate-900", size === "lg" ? "text-[20px]" : "text-[16px]")}>{peso(room.price)}</span>
                <span className="text-[12px] text-slate-500 font-medium">/day</span>
            </div>
            <div className="text-[11px] text-slate-500">{peso(room.price * 7)}/wk · {peso(monthly(room.price))}/mo</div>
        </div>
    );
}

export function SocialProof({ room }) {
    if (!room.reserved_30d) return null; // only real, non-zero counts
    return (
        <span className="inline-flex items-center gap-1.5 text-[11.5px] text-slate-600">
            <i className="fa-solid fa-arrow-trend-up text-[10px] text-[#BF5600]" aria-hidden="true" />
            Reserved {room.reserved_30d}× this month
        </span>
    );
}

export function RoomFacts({ room }) {
    return (
        <ul className="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11.5px] text-slate-500" aria-label="Room features">
            <li className="inline-flex items-center gap-1"><i className="fa-solid fa-layer-group text-[10px]" aria-hidden="true" /> Floor {room.floor}</li>
            <li className="inline-flex items-center gap-1"><i className="fa-solid fa-user-group text-[10px]" aria-hidden="true" /> {room.pax}</li>
            <li className="inline-flex items-center gap-1"><i className="fa-solid fa-bed text-[10px]" aria-hidden="true" /> {room.beds}</li>
        </ul>
    );
}

/** 360° tour button shown over room images. */
export function TourBadge({ tourId, className = "" }) {
    if (!tourId) return null;
    return (
        <a href={tourHref(tourId)} onClick={(e) => e.stopPropagation()}
            className={cx("inline-flex items-center gap-1.5 rounded-full bg-[#0F1A33]/85 hover:bg-[#0F1A33] text-white px-3 h-9 text-[12px] font-bold backdrop-blur focus:outline-none focus-visible:ring-2 focus-visible:ring-white", className)}
            aria-label="Open the 360° virtual tour">
            <i className="fa-solid fa-vr-cardboard" aria-hidden="true" /> 360° tour
        </a>
    );
}

/** The single orange primary action for a room: reserve, or request a room change. */
export function PrimaryAction({ room, mode, roomChange, size = "md", onActivate, fullWidth = false, label }) {
    const sz = size === "lg" ? btn.lg : btn.md;
    const full = fullWidth ? "w-full" : "";

    if (room.is_current_room) {
        return <button type="button" disabled className={cx(btn.disabled, sz, full)}><i className="fa-solid fa-house-user" aria-hidden="true" /> Your current room</button>;
    }
    if (mode === "change") {
        if (!roomChange?.allowed) {
            return (
                <button type="button" disabled className={cx(btn.disabled, sz, full)}
                    title={roomChange?.locked ? "Settle your balance to request a room change" : "Room changes open once your stay is approved"}>
                    <i className="fa-solid fa-lock" aria-hidden="true" /> Request change
                </button>
            );
        }
        return (
            <a href={`${roomChange.url}?request_room=${room.id}`} className={cx(btn.primary, sz, full)} onClick={onActivate}>
                {label || "Request change"} <Arrow />
            </a>
        );
    }
    if (room.has_reserved) {
        return <button type="button" disabled className={cx(btn.disabled, sz, full)}><i className="fa-regular fa-clock" aria-hidden="true" /> Already reserved</button>;
    }
    if (!room.is_bookable) {
        return <button type="button" disabled className={cx(btn.disabled, sz, full)}>Not available</button>;
    }
    // .open-reserve-modal-btn + data-room → handled by the shared Reservations booking modal
    return (
        <button type="button" className={cx("open-reserve-modal-btn", btn.primary, sz, full)} data-room={JSON.stringify(room)} onClick={onActivate}>
            {label || (size === "lg" ? "Reserve Now" : "Reserve")} <Arrow />
        </button>
    );
}

export function RoomCard({ room, mode, roomChange, view = "grid", delay = 0, onDetails }) {
    const [ref, inView] = useInView();
    const list = view === "list";
    return (
        <article ref={ref} style={{ animationDelay: `${delay}ms` }}
            className={cx("rb-card rb-fade-up group bg-white rounded-2xl border border-slate-200 overflow-hidden flex",
                list ? "flex-col sm:flex-row" : "flex-col")}>
            <div className={cx("relative shrink-0", list ? "sm:w-60 aspect-[16/10] sm:aspect-auto" : "aspect-[16/10]")}>
                <button type="button" onClick={() => onDetails(room)} aria-label={`View details for Room ${room.num}`}
                    className="absolute inset-0 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-[#E8710A]">
                    <RoomImage room={room} className="absolute inset-0" />
                </button>
                {room.matchScore != null && (
                    <span className="absolute top-2.5 left-2.5 rounded-full bg-white/95 text-slate-800 px-2.5 py-1 text-[11px] font-bold shadow-sm tabular-nums pointer-events-none">
                        {room.matchScore}% match
                    </span>
                )}
                <TourBadge tourId={room.tourId} className="absolute bottom-2.5 right-2.5" />
            </div>
            <div className="p-4 flex flex-col gap-2.5 flex-1 min-w-0">
                <div className="flex items-start justify-between gap-3">
                    <div className="min-w-0">
                        <h3 className="text-[15px] font-bold text-slate-900 truncate">Room {room.num}</h3>
                        <p className="text-[12px] text-slate-500">{room.name}</p>
                    </div>
                    <Price room={room} />
                </div>
                <RoomFacts room={room} />
                <div className="flex flex-wrap items-center gap-x-3 gap-y-1.5">
                    <BedsChip room={room} start={inView} />
                    <SocialProof room={room} />
                </div>
                <div className="mt-auto pt-1 grid grid-cols-2 gap-2">
                    <button type="button" onClick={() => onDetails(room)} className={cx(btn.secondary, btn.md)}>View</button>
                    <PrimaryAction room={room} mode={mode} roomChange={roomChange} />
                </div>
            </div>
        </article>
    );
}

export function FeaturedRoomCard({ room, mode, roomChange, onDetails, rank = 1 }) {
    const [ref, inView] = useInView();
    return (
        <article ref={ref} className={cx("rb-card bg-white rounded-2xl border overflow-hidden flex flex-col h-full",
            rank === 1 ? "rb-featured border-orange-200" : "border-slate-200")}>
            {/* Fixed height so the featured image never balloons on wide screens */}
            <div className="relative h-48 md:h-56 shrink-0">
                <button type="button" onClick={() => onDetails(room)} aria-label={`View details for Room ${room.num}`}
                    className="absolute inset-0 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-[#E8710A]">
                    <RoomImage room={room} className="absolute inset-0" />
                    <span className="absolute inset-0 bg-gradient-to-t from-[#0F1A33]/80 via-[#0F1A33]/10 to-transparent" aria-hidden="true" />
                </button>
                <span className={cx("absolute top-3 left-3 inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-[12px] font-bold shadow tabular-nums pointer-events-none",
                    rank === 1 ? "bg-[#BF5600] text-white" : "bg-white/95 text-slate-800")}>
                    <i className={cx("fa-solid text-[10px]", rank === 1 ? "fa-star" : "fa-thumbs-up")} aria-hidden="true" />
                    {rank === 1 ? "Best Match" : "Great Match"} · {room.matchScore}%
                </span>
                <TourBadge tourId={room.tourId} className="absolute top-3 right-3" />
                <div className="absolute bottom-3 left-4 right-4 flex items-end justify-between gap-3 pointer-events-none">
                    <div className="text-white min-w-0">
                        <h3 className="text-[17px] font-bold truncate">Room {room.num}</h3>
                        <p className="text-[12.5px] text-white/85">{room.name} · Floor {room.floor}</p>
                    </div>
                    <MatchScoreRing score={room.matchScore} size={50} stroke={4} start={inView} light />
                </div>
            </div>
            <div className="p-4 flex flex-col gap-3 flex-1">
                {room.reasons?.length > 0 && (
                    <ul className="flex flex-wrap gap-2" aria-label="Why we picked this room">
                        {room.reasons.map((r) => (
                            <li key={r} className="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 text-emerald-800 px-2.5 py-0.5 text-[11.5px] font-semibold">
                                <i className="fa-solid fa-circle-check text-[11px] text-emerald-600" aria-hidden="true" /> {r}
                            </li>
                        ))}
                    </ul>
                )}
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div className="flex flex-col gap-1.5">
                        <BedsChip room={room} start={inView} />
                        <SocialProof room={room} />
                    </div>
                    <Price room={room} />
                </div>
                <div className="mt-auto flex flex-col sm:flex-row gap-2">
                    <PrimaryAction room={room} mode={mode} roomChange={roomChange} fullWidth />
                    <button type="button" onClick={() => onDetails(room)} className={cx(btn.secondary, btn.md, "shrink-0")}>View details</button>
                </div>
            </div>
        </article>
    );
}

/**
 * Compact runner-up row: the whole row opens the room details (where Reserve
 * and the 360° tour live), so nothing has to be squeezed into a narrow column.
 */
export function MiniRoomCard({ room, onDetails, delay = 0, className = "" }) {
    return (
        <button type="button" onClick={() => onDetails(room)} style={{ animationDelay: `${delay}ms` }}
            aria-label={`Room ${room.num}, ${room.name}, ${peso(room.price)} per day${room.matchScore != null ? `, ${room.matchScore}% match` : ""}. View details`}
            className={cx("rb-card rb-fade-up group w-full text-left bg-white rounded-xl border border-slate-200 hover:border-orange-200 p-2.5 flex items-center gap-3 min-w-0 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#E8710A]", className)}>
            <span className="relative w-16 h-16 rounded-lg overflow-hidden shrink-0">
                <RoomImage room={room} className="absolute inset-0" />
            </span>
            <span className="flex-1 min-w-0">
                <span className="flex items-center gap-2 min-w-0">
                    <span className="text-[13.5px] font-bold text-slate-900 whitespace-nowrap">Room {room.num}</span>
                    {room.matchScore != null && (
                        <span className="shrink-0 rounded-full bg-orange-50 text-[#934300] px-1.5 py-0.5 text-[10.5px] font-bold tabular-nums">{room.matchScore}%</span>
                    )}
                </span>
                <span className="block text-[11.5px] text-slate-500 truncate">{room.name} · Floor {room.floor}</span>
                {room.reasons?.[0] && (
                    <span className="mt-0.5 flex items-center gap-1 text-[11.5px] text-emerald-700 font-medium min-w-0">
                        <i className="fa-solid fa-circle-check text-[10px] shrink-0" aria-hidden="true" />
                        <span className="truncate">{room.reasons[0]}</span>
                    </span>
                )}
            </span>
            <span className="shrink-0 text-right tabular-nums leading-tight">
                <span className="block text-[14px] font-extrabold text-slate-900">{peso(room.price)}</span>
                <span className="block text-[10.5px] text-slate-500">/day</span>
            </span>
            <i className="fa-solid fa-chevron-right text-[11px] text-slate-300 group-hover:text-[#BF5600] shrink-0 transition-colors" aria-hidden="true" />
        </button>
    );
}
