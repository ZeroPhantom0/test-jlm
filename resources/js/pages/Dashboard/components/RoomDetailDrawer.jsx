// resources/js/pages/Dashboard/components/RoomDetailDrawer.jsx
import React, { useEffect, useMemo, useRef, useState } from "react";
import { createPortal } from "react-dom";
import { btn, cx, peso, tourHref } from "./ui";
import { BedsChip, PrimaryAction, extraGuestNote, monthly } from "./RoomCard";

const FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]), select, textarea, [tabindex]:not([tabindex="-1"])';

/** Keeps Tab inside `ref`, closes on Esc, restores focus on unmount, locks body scroll. */
export function useDialog(ref, onClose) {
    // Keep the latest onClose without re-running the effect (which would steal focus)
    const closeRef = useRef(onClose);
    closeRef.current = onClose;

    useEffect(() => {
        const prev = document.activeElement;
        const panel = ref.current;
        panel?.querySelector("[data-autofocus]")?.focus() || panel?.querySelector(FOCUSABLE)?.focus();

        const onKey = (e) => {
            if (e.key === "Escape") {
                e.stopPropagation();
                closeRef.current?.();
                return;
            }
            if (e.key !== "Tab" || !panel) return;
            const items = [...panel.querySelectorAll(FOCUSABLE)].filter((el) => el.offsetParent !== null);
            if (!items.length) return;
            const first = items[0];
            const last = items[items.length - 1];
            if (e.shiftKey && document.activeElement === first) {
                e.preventDefault();
                last.focus();
            } else if (!e.shiftKey && document.activeElement === last) {
                e.preventDefault();
                first.focus();
            }
        };
        document.addEventListener("keydown", onKey);
        const overflow = document.body.style.overflow;
        document.body.style.overflow = "hidden";
        return () => {
            document.removeEventListener("keydown", onKey);
            document.body.style.overflow = overflow;
            prev?.focus?.();
        };
    }, [ref]);
}

function RoomDetailDrawerInner({ room, mode, roomChange, houseRulesUrl, onClose }) {
    const [img, setImg] = useState(0);
    const panelRef = useRef(null);
    useDialog(panelRef, onClose);
    const gallery = room.gallery?.length ? room.gallery : [room.img];

    // Group bunk slots by bed number: B1-U / B1-L → Bed 1
    const bunks = useMemo(() => {
        const map = new Map();
        (room.bed_slots || []).forEach((s) => {
            const key = s.id.split("-")[0];
            if (!map.has(key)) map.set(key, []);
            map.get(key).push(s);
        });
        return [...map.entries()];
    }, [room.bed_slots]);

    const Section = ({ title, children }) => (
        <section>
            <h3 className="text-[11.5px] font-bold uppercase tracking-wider text-slate-500 mb-2">{title}</h3>
            {children}
        </section>
    );

    return (
        <div className="fixed inset-0 z-[1200]" role="dialog" aria-modal="true" aria-labelledby="room-drawer-title">
            <div className="absolute inset-0 bg-[#0F1A33]/55 backdrop-blur-sm rb-fade-in" onClick={onClose} />
            <div ref={panelRef}
                className="rb-panel absolute bottom-0 left-0 right-0 max-h-[92dvh] rounded-t-2xl md:rounded-none md:top-0 md:left-auto md:w-[480px] md:max-h-none bg-white flex flex-col shadow-2xl overflow-hidden">
                <div className="flex items-center justify-between px-5 pt-4 pb-3 border-b border-slate-100">
                    <div className="min-w-0">
                        <h2 id="room-drawer-title" className="text-[17px] font-bold text-slate-900">Room {room.num}</h2>
                        <p className="text-[12px] text-slate-500">{room.name} · Floor {room.floor}</p>
                    </div>
                    <button type="button" onClick={onClose} aria-label="Close room details" data-autofocus
                        className="w-11 h-11 rounded-full flex items-center justify-center text-slate-500 hover:bg-slate-100 active:bg-slate-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-300">
                        <i className="fa-solid fa-xmark text-lg" aria-hidden="true" />
                    </button>
                </div>

                <div className="flex-1 overflow-y-auto">
                    {/* Gallery */}
                    <div className="p-5 pb-0">
                        <div className="relative aspect-[16/10] rounded-2xl overflow-hidden bg-gradient-to-br from-slate-200 to-orange-50">
                            <img key={gallery[img]} src={gallery[img]} alt={`Room ${room.num}, photo ${img + 1} of ${gallery.length}`}
                                className="absolute inset-0 w-full h-full object-cover rb-fade-in" />
                            {room.tourId && (
                                <a href={tourHref(room.tourId)}
                                    className="absolute bottom-3 left-3 inline-flex items-center gap-2 rounded-full bg-[#0F1A33]/85 hover:bg-[#0F1A33] text-white px-4 h-11 text-[13px] font-bold backdrop-blur focus:outline-none focus-visible:ring-2 focus-visible:ring-white">
                                    <i className="fa-solid fa-vr-cardboard" aria-hidden="true" /> Take the 360° tour
                                </a>
                            )}
                        </div>
                        {gallery.length > 1 && (
                            <div className="flex gap-2 mt-2.5 overflow-x-auto pb-1" role="group" aria-label="Photos">
                                {gallery.map((src, i) => (
                                    <button key={src} type="button" onClick={() => setImg(i)} aria-label={`Show photo ${i + 1}`} aria-pressed={i === img}
                                        className={cx("w-16 aspect-[4/3] rounded-lg overflow-hidden shrink-0 border-2 transition-opacity focus:outline-none focus-visible:ring-2 focus-visible:ring-[#E8710A]",
                                            i === img ? "border-[#E8710A]" : "border-transparent opacity-70 hover:opacity-100")}>
                                        <img src={src} alt="" loading="lazy" className="w-full h-full object-cover" />
                                    </button>
                                ))}
                            </div>
                        )}
                    </div>

                    <div className="p-5 space-y-6">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <BedsChip room={room} />
                            <div className="tabular-nums text-right leading-tight">
                                <span className="text-[20px] font-extrabold text-slate-900">{peso(room.price)}</span>
                                <span className="text-[12px] text-slate-500">/day</span>
                                <div className="text-[11.5px] text-slate-500">Stay 1 night or longer</div>
                            </div>
                        </div>

                        <p className="text-[13.5px] text-slate-600 leading-relaxed">{room.desc}</p>

                        <Section title="What's included">
                            <ul className="grid grid-cols-2 gap-2 text-[12.5px] text-slate-700">
                                <li className="bg-slate-50 rounded-xl px-3 py-2.5"><i className="fa-solid fa-house text-slate-400 mr-2" aria-hidden="true" />{room.name}</li>
                                <li className="bg-slate-50 rounded-xl px-3 py-2.5"><i className="fa-solid fa-layer-group text-slate-400 mr-2" aria-hidden="true" />Floor {room.floor}</li>
                                <li className="bg-slate-50 rounded-xl px-3 py-2.5"><i className="fa-solid fa-user-group text-slate-400 mr-2" aria-hidden="true" />{room.pax}</li>
                                <li className="bg-slate-50 rounded-xl px-3 py-2.5"><i className="fa-solid fa-bed text-slate-400 mr-2" aria-hidden="true" />{room.beds}</li>
                            </ul>
                        </Section>

                        <Section title="Bed availability">
                            {room.per_bed && bunks.length ? (
                                <>
                                    <div className="grid grid-cols-3 sm:grid-cols-4 gap-2">
                                        {bunks.map(([bed, slots]) => (
                                            <div key={bed} className="rounded-xl border border-slate-200 p-2">
                                                <p className="text-[10.5px] font-bold text-slate-500 mb-1.5 text-center">Bed {bed.replace("B", "")}</p>
                                                {slots.map((s) => {
                                                    const free = s.status === "Available";
                                                    return (
                                                        <div key={s.id} title={`${s.label}: ${free ? "free" : "taken"}`}
                                                            className={cx("mb-1 last:mb-0 rounded-md px-1.5 py-1 text-[10.5px] font-semibold text-center",
                                                                free ? "bg-emerald-50 text-emerald-800" : "bg-slate-100 text-slate-500 line-through")}>
                                                            {s.id.endsWith("U") ? "Upper" : "Lower"}
                                                            <span className="sr-only">{free ? " — free" : " — taken"}</span>
                                                        </div>
                                                    );
                                                })}
                                            </div>
                                        ))}
                                    </div>
                                    <div className="flex gap-4 mt-2 text-[11px] text-slate-500" aria-hidden="true">
                                        <span className="inline-flex items-center gap-1.5"><span className="w-2.5 h-2.5 rounded-sm bg-emerald-200" /> Free</span>
                                        <span className="inline-flex items-center gap-1.5"><span className="w-2.5 h-2.5 rounded-sm bg-slate-200" /> Taken</span>
                                    </div>
                                </>
                            ) : (
                                <p className="text-[12.5px] text-slate-600 bg-slate-50 rounded-xl px-3 py-2.5">
                                    Booked as a whole room — all {room.total_beds} bed{room.total_beds === 1 ? " is" : "s are"} yours for the stay.
                                </p>
                            )}
                        </Section>

                        <Section title="Price breakdown">
                            <dl className="rounded-xl border border-slate-200 divide-y divide-slate-100 text-[12.5px] tabular-nums">
                                <div className="flex justify-between px-3.5 py-2.5"><dt className="text-slate-600">1 night</dt><dd className="font-bold text-slate-900">{peso(room.price)}</dd></div>
                                <div className="flex justify-between px-3.5 py-2.5"><dt className="text-slate-600">2 nights</dt><dd className="font-semibold">{peso(room.price * 2)}</dd></div>
                                <div className="flex justify-between px-3.5 py-2.5"><dt className="text-slate-600">1 week (7 nights)</dt><dd className="font-semibold">{peso(room.price * 7)}</dd></div>
                                <div className="flex justify-between px-3.5 py-2.5"><dt className="text-slate-600">1 month (30 nights)</dt><dd className="font-semibold">{peso(monthly(room.price))}</dd></div>
                                <div className="flex justify-between gap-3 px-3.5 py-2.5"><dt className="text-slate-600">Extra guests</dt><dd className="font-semibold text-right">{extraGuestNote(room)}</dd></div>
                                <div className="flex justify-between px-3.5 py-2.5"><dt className="text-slate-600">Other fees</dt><dd className="font-semibold text-emerald-700">None</dd></div>
                            </dl>
                        </Section>

                        <a href={houseRulesUrl} className="inline-flex items-center gap-2 h-11 text-[13px] font-semibold text-[#0F1A33] hover:text-[#BF5600] rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-[#E8710A]">
                            <i className="fa-solid fa-scroll" aria-hidden="true" /> Read the house rules <i className="fa-solid fa-arrow-up-right-from-square text-[10px]" aria-hidden="true" />
                        </a>
                    </div>
                </div>

                {/* Footer stays attached to the bottom edge (safe-area aware on phones) */}
                <div className="shrink-0 px-4 pt-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] border-t border-slate-200 bg-white shadow-[0_-8px_16px_-12px_rgba(15,26,51,0.25)]">
                    <PrimaryAction room={room} mode={mode} roomChange={roomChange} size="lg" fullWidth onActivate={onClose}
                        label={mode === "change" ? "Request this room" : "Reserve this room"} />
                    <p className="text-[11.5px] text-slate-500 text-center mt-2 leading-snug">
                        {mode === "change"
                            ? "You'll add a reason and payment proof on the next step. The admin approves every move."
                            : "You'll pick your dates and upload your GCash payment proof (50% or full). The admin verifies it before your stay is confirmed."}
                    </p>
                </div>
            </div>
        </div>
    );
}

/** Rendered into <body>: <main> has view-transition-name, which traps fixed overlays inside it. */
export default function RoomDetailDrawer(props) {
    return createPortal(<RoomDetailDrawerInner {...props} />, document.body);
}
