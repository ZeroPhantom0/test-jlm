// resources/js/pages/Dashboard/components/MyRoomTile.jsx
// T6 — resident's room: photo, bed, roommates, stay progress, quick links.
import React from "react";
import { useInView } from "../../../utils/motion";
import { RoomImage } from "./RoomCard";
import { BentoTile, CountUp, cx, formatDate, peso, tourHref } from "./ui";

function Avatar({ person, i }) {
    const initials = person.name.split(" ").map((p) => p[0]).slice(0, 2).join("").toUpperCase();
    return person.avatar ? (
        <img src={person.avatar} alt={person.name} title={person.name} loading="lazy"
            className="w-9 h-9 rounded-full object-cover ring-2 ring-white" style={{ zIndex: 10 - i }} />
    ) : (
        <span title={person.name} className="w-9 h-9 rounded-full ring-2 ring-white bg-[#0F1A33] text-white text-[11px] font-bold flex items-center justify-center" style={{ zIndex: 10 - i }}>
            <span aria-hidden="true">{initials}</span><span className="sr-only">{person.name}</span>
        </span>
    );
}

export default function MyRoomTile({ room, links, access, xl = 8, md = 6, index = 0 }) {
    const [ref, inView] = useInView();
    if (!room) return null;
    const pct = room.totalDays ? Math.min(100, Math.round((room.daysStayed / room.totalDays) * 100)) : null;

    const quick = [
        { icon: "fa-solid fa-scroll", label: "House rules", href: links.houseRules },
        { icon: "fa-solid fa-wrench", label: "Request service", href: links.services, disabled: access.servicesLocked },
        { icon: "fa-solid fa-right-left", label: "Room change", href: links.roomChange, disabled: !access.canRoomChange },
    ];

    return (
        <BentoTile id="my-room" xl={xl} md={md} index={index} className="overflow-hidden flex flex-col md:flex-row scroll-mt-24" aria-labelledby="my-room-title">
            <div className="relative md:w-[42%] aspect-[16/10] md:aspect-auto shrink-0">
                <RoomImage room={{ img: room.img, num: room.num, name: room.type }} className="absolute inset-0" />
                <span className="absolute inset-0 bg-gradient-to-t from-[#0F1A33]/70 to-transparent" aria-hidden="true" />
                <div className="absolute bottom-4 left-4 text-white">
                    <p className="text-[12px] text-white/80">Your room</p>
                    <h2 id="my-room-title" className="text-[22px] font-extrabold leading-none">Room {room.num}</h2>
                    <p className="text-[12.5px] text-white/85 mt-1">{room.type} · Floor {room.floor}{room.bed ? ` · Bed ${room.bed}` : ""}</p>
                </div>
                {room.tourId && (
                    <a href={tourHref(room.tourId)} className="absolute top-3 right-3 inline-flex items-center gap-1.5 rounded-full bg-[#0F1A33]/85 hover:bg-[#0F1A33] text-white px-3 h-9 text-[12px] font-bold backdrop-blur focus:outline-none focus-visible:ring-2 focus-visible:ring-white">
                        <i className="fa-solid fa-vr-cardboard" aria-hidden="true" /> 360° tour
                    </a>
                )}
            </div>

            <div ref={ref} className="flex-1 p-5 flex flex-col gap-4">
                <div className="grid grid-cols-2 gap-4 text-[12.5px]">
                    <div><p className="text-slate-500">Move-in</p><p className="text-[15px] font-bold text-slate-900">{formatDate(room.moveIn)}</p></div>
                    <div><p className="text-slate-500">Contract end</p><p className="text-[15px] font-bold text-slate-900">{room.contractEnd ? formatDate(room.contractEnd) : "Open-ended"}</p></div>
                    <div><p className="text-slate-500">Daily rent</p><p className="text-[15px] font-bold text-slate-900 tabular-nums">{peso(room.dailyRent)}</p></div>
                    <div>
                        <p className="text-slate-500">Roommates</p>
                        {room.roommates.length ? (
                            <div className="flex -space-x-2 mt-1">{room.roommates.slice(0, 4).map((p, i) => <Avatar key={p.name + i} person={p} i={i} />)}
                                {room.roommates.length > 4 && <span className="w-9 h-9 rounded-full ring-2 ring-white bg-slate-100 text-slate-600 text-[11px] font-bold flex items-center justify-center">+{room.roommates.length - 4}</span>}
                            </div>
                        ) : <p className="text-[15px] font-bold text-slate-900">Just you</p>}
                    </div>
                </div>

                {room.moveInInDays != null ? (
                    <div className="rounded-2xl bg-orange-50 px-4 py-3 text-[13px] text-[#7c3a00] flex items-center gap-2">
                        <i className="fa-solid fa-key" aria-hidden="true" />
                        Move-in in <CountUp value={room.moveInInDays} className="font-extrabold" /> day{room.moveInInDays === 1 ? "" : "s"}
                    </div>
                ) : pct != null && (
                    <div>
                        <div className="flex justify-between text-[12px] text-slate-600 mb-1.5">
                            <span>Stay progress</span>
                            <span className="font-semibold tabular-nums">Day {room.daysStayed} of {room.totalDays}</span>
                        </div>
                        <div className="h-2 rounded-full bg-slate-100 overflow-hidden" role="progressbar" aria-valuenow={pct} aria-valuemin={0} aria-valuemax={100} aria-label="Stay progress">
                            <div className="h-full rounded-full bg-gradient-to-r from-[#16A34A] to-[#22c55e] origin-left transition-transform duration-1000 ease-out"
                                style={{ transform: `scaleX(${inView ? pct / 100 : 0})` }} />
                        </div>
                    </div>
                )}

                <div className="mt-auto grid grid-cols-3 gap-2">
                    {quick.map((q) => q.disabled ? (
                        <span key={q.label} className="rounded-xl bg-slate-50 text-slate-400 min-h-[56px] px-2 flex flex-col items-center justify-center gap-1 text-[11.5px] font-semibold text-center cursor-not-allowed" aria-disabled="true"
                            title={q.label === "Room change" ? "Available once your stay is approved and your balance is settled" : "Settle your balance to request services"}>
                            <i className={cx(q.icon, "text-[14px]")} aria-hidden="true" /> {q.label}
                        </span>
                    ) : (
                        <a key={q.label} href={q.href}
                            className="rb-card rounded-xl bg-slate-50 hover:bg-orange-50 text-slate-700 hover:text-[#934300] min-h-[56px] px-2 flex flex-col items-center justify-center gap-1 text-[11.5px] font-semibold text-center focus:outline-none focus-visible:ring-2 focus-visible:ring-[#E8710A]">
                            <i className={cx(q.icon, "text-[14px]")} aria-hidden="true" /> {q.label}
                        </a>
                    ))}
                </div>
            </div>
        </BentoTile>
    );
}
