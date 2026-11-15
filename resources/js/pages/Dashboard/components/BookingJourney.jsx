// resources/js/pages/Dashboard/components/BookingJourney.jsx
// T5 — reservation stepper (pending state) and the small "How it works" version.
import React from "react";
import { useInView } from "../../../utils/motion";
import { RoomImage } from "./RoomCard";
import { BentoTile, CountUp, cx, formatDate, peso, relativeTime, TileHeader, tourHref } from "./ui";

const STEPS = ["Requested", "Under Review", "Approved", "Move-in"];

function Stepper({ step }) {
    const [ref, inView] = useInView();
    const pct = (step / (STEPS.length - 1)) * 100;
    return (
        <ol ref={ref} className="relative grid grid-cols-4 gap-2" aria-label="Reservation progress">
            <span className="absolute top-[15px] left-[12.5%] right-[12.5%] h-0.5 rounded-full bg-slate-200" aria-hidden="true" />
            <span className="absolute top-[15px] left-[12.5%] h-0.5 rounded-full bg-[#16A34A] origin-left transition-transform duration-1000 ease-out"
                style={{ width: "75%", transform: `scaleX(${inView ? pct / 100 : 0})` }} aria-hidden="true" />
            {STEPS.map((label, i) => {
                const done = i < step;
                const current = i === step;
                return (
                    <li key={label} className="relative flex flex-col items-center text-center gap-2" aria-current={current ? "step" : undefined}>
                        <span className={cx("w-8 h-8 rounded-full flex items-center justify-center text-[12px] font-bold border-2 bg-white z-10",
                            done && "bg-[#16A34A] border-[#16A34A] text-white",
                            current && "border-[#E8710A] text-[#BF5600] rb-pulse",
                            !done && !current && "border-slate-200 text-slate-400")}>
                            {done ? <i className="fa-solid fa-check text-[12px]" aria-hidden="true" /> : i + 1}
                        </span>
                        <span className={cx("text-[11.5px] font-semibold leading-tight", current ? "text-slate-900" : done ? "text-emerald-700" : "text-slate-500")}>
                            {label}
                            <span className="sr-only">{done ? " (done)" : current ? " (current step)" : ""}</span>
                        </span>
                    </li>
                );
            })}
        </ol>
    );
}

export default function BookingJourney({ reservation: r, profile, links, xl = 8, md = 6, index = 0 }) {
    if (!r) return null;
    const halfPaid = r.paymentOption === "Down Payment" && r.remaining > 0;
    const checklist = [
        { done: false, label: "Read the house rules", href: links.houseRules },
        {
            done: profile.items.find((i) => i.key === "id")?.done ?? false,
            label: "Upload a valid ID",
            href: profile.items.find((i) => i.key === "id")?.href || links.settings,
        },
        halfPaid && { done: false, label: `Prepare your ${peso(r.remaining)} balance — due at check-in`, href: links.reservations },
    ].filter(Boolean);

    return (
        <BentoTile xl={xl} md={md} index={index} className="p-5 flex flex-col gap-5" aria-labelledby="journey-title">
            <TileHeader icon="fa-solid fa-route" title={<span id="journey-title">Your booking journey</span>}
                subtitle={`Submitted ${relativeTime(r.submittedAt)} · Ref ${r.reference}`} />

            <Stepper step={r.step} />

            <div className="flex flex-col sm:flex-row gap-4 rounded-2xl bg-slate-50 p-3">
                <div className="relative w-full sm:w-40 aspect-[16/10] rounded-xl overflow-hidden shrink-0">
                    <RoomImage room={{ img: r.room.img, num: r.room.num, name: r.room.type }} className="absolute inset-0" />
                    {r.room.tourId && (
                        <a href={tourHref(r.room.tourId)} className="absolute bottom-2 right-2 inline-flex items-center gap-1 rounded-full bg-[#0F1A33]/85 text-white px-2.5 h-8 text-[11px] font-bold focus:outline-none focus-visible:ring-2 focus-visible:ring-white">
                            <i className="fa-solid fa-vr-cardboard" aria-hidden="true" /> 360°
                        </a>
                    )}
                </div>
                <div className="flex-1 min-w-0 grid grid-cols-2 gap-3 text-[12.5px]">
                    <div><p className="text-slate-500">Room</p><p className="font-bold text-slate-900">{r.room.num}{r.bed ? ` · ${r.bed}` : ""}</p></div>
                    <div><p className="text-slate-500">Type</p><p className="font-bold text-slate-900">{r.room.type || "—"}</p></div>
                    <div><p className="text-slate-500">Check-in</p><p className="font-bold text-slate-900">{formatDate(r.checkIn)}</p></div>
                    <div><p className="text-slate-500">Check-out</p><p className="font-bold text-slate-900">{formatDate(r.checkOut)}</p></div>
                </div>
                {r.moveInInDays != null && (
                    <div className="sm:w-32 rounded-xl bg-[#0F1A33] text-white flex flex-col items-center justify-center p-3 text-center">
                        <CountUp value={r.moveInInDays} className="text-[24px] font-extrabold leading-none" />
                        <span className="text-[11px] text-slate-300 mt-1">{r.moveInInDays === 1 ? "day" : "days"} to move-in</span>
                    </div>
                )}
            </div>

            <div>
                <h3 className="text-[13px] font-bold text-slate-900 mb-2">What's next</h3>
                <ul className="space-y-1.5">
                    {checklist.map((c) => (
                        <li key={c.label}>
                            <a href={c.href || "#"} className="flex items-center gap-3 rounded-xl px-3 min-h-11 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#E8710A]">
                                <span className={cx("w-5 h-5 rounded-full flex items-center justify-center shrink-0 border-2",
                                    c.done ? "bg-[#16A34A] border-[#16A34A] text-white" : "border-slate-300")} aria-hidden="true">
                                    {c.done && <i className="fa-solid fa-check text-[9px]" />}
                                </span>
                                <span className={cx("flex-1 text-[13px]", c.done ? "text-slate-400 line-through" : "text-slate-700")}>{c.label}</span>
                                <i className="fa-solid fa-chevron-right text-[10px] text-slate-400" aria-hidden="true" />
                            </a>
                        </li>
                    ))}
                </ul>
            </div>
        </BentoTile>
    );
}

/** State A: the three booking steps, one line each, to reduce uncertainty. */
export function HowItWorks({ xl = 4, md = 3, index = 0 }) {
    const steps = [
        ["fa-solid fa-magnifying-glass", "Pick a room", "Compare prices, beds left and the 360° tour."],
        ["fa-solid fa-receipt", "Reserve & upload proof", "Pay 50% or in full by GCash, then upload the receipt."],
        ["fa-solid fa-circle-check", "Get approved & move in", "The admin verifies your payment and confirms your stay."],
    ];
    return (
        <BentoTile xl={xl} md={md} index={index} className="p-5" aria-labelledby="how-title">
            <TileHeader icon="fa-solid fa-lightbulb" iconClass="bg-amber-50 text-amber-700" title={<span id="how-title">How booking works</span>} />
            <ol className="space-y-3">
                {steps.map(([icon, title, text], i) => (
                    <li key={title} className="flex gap-3">
                        <span className="w-8 h-8 rounded-lg bg-orange-50 text-[#BF5600] flex items-center justify-center shrink-0 text-[12px] font-bold" aria-hidden="true">{i + 1}</span>
                        <div><p className="text-[13.5px] font-bold text-slate-900"><i className={cx(icon, "text-[11px] text-slate-400 mr-1.5")} aria-hidden="true" />{title}</p><p className="text-[12.5px] text-slate-500">{text}</p></div>
                    </li>
                ))}
            </ol>
        </BentoTile>
    );
}
