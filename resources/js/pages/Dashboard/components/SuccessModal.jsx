// resources/js/pages/Dashboard/components/SuccessModal.jsx
import React, { useRef } from "react";
import { createPortal } from "react-dom";
import { useDialog } from "./RoomDetailDrawer";
import { Arrow, btn, cx } from "./ui";

const VARIANTS = {
    submitted: {
        title: "Reservation submitted!",
        body: (room) => (room ? `Room ${room} is on hold for you while we check your payment.` : "We're checking your payment."),
        steps: [
            ["fa-solid fa-magnifying-glass-dollar", "We verify your payment", "The admin checks your receipt and reference number."],
            ["fa-regular fa-bell", "You'll be notified", "An email and a dashboard notification once it's approved."],
            ["fa-solid fa-wallet", "Settle any balance", "If you paid 50% down, the rest is due at check-in."],
            ["fa-solid fa-key", "Move in", "Arrive on your check-in date and enjoy your stay!"],
        ],
        cta: { label: "View my reservation", href: "/tenants/reservations" },
    },
    approved: {
        title: "You're approved! 🎉",
        body: (room) => (room ? `Welcome to Room ${room}. Here's what to do next.` : "Your stay is confirmed."),
        steps: [
            ["fa-solid fa-scroll", "Read the house rules", "Quiet hours, visitors and shared spaces."],
            ["fa-solid fa-wallet", "Settle any balance", "Your dashboard shows anything still due."],
            ["fa-solid fa-key", "Move in", "Bring a valid ID on your check-in date."],
        ],
        cta: { label: "See my room", href: "#my-room" },
    },
};

function SuccessModalInner({ variant = "submitted", room, onClose }) {
    const ref = useRef(null);
    useDialog(ref, onClose);
    const v = VARIANTS[variant] || VARIANTS.submitted;
    return (
        <div className="fixed inset-0 z-[1600] flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="success-title">
            <div className="absolute inset-0 bg-[#0F1A33]/55 backdrop-blur-sm rb-fade-in" onClick={onClose} />
            <div ref={ref} className="relative rb-pop-in bg-white rounded-2xl shadow-2xl w-full max-w-[440px] p-6">
                <div className="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl mx-auto mb-3" aria-hidden="true">
                    <i className="fa-solid fa-circle-check" />
                </div>
                <h2 id="success-title" className="text-[19px] font-bold text-center text-slate-900">{v.title}</h2>
                <p className="text-[13.5px] text-slate-500 text-center mt-1">{v.body(room)}</p>
                <ol className="mt-5 space-y-3">
                    {v.steps.map(([icon, title, text], i) => (
                        <li key={title} className="flex gap-3 rb-fade-up" style={{ animationDelay: `${120 + i * 60}ms` }}>
                            <span className="w-9 h-9 rounded-xl bg-orange-50 text-[#BF5600] flex items-center justify-center shrink-0" aria-hidden="true"><i className={icon} /></span>
                            <div><p className="text-[13.5px] font-bold text-slate-900">{title}</p><p className="text-[12.5px] text-slate-500">{text}</p></div>
                        </li>
                    ))}
                </ol>
                <div className="mt-6 flex flex-col sm:flex-row gap-2">
                    <a href={v.cta.href} onClick={v.cta.href.startsWith("#") ? onClose : undefined} data-autofocus className={cx(btn.primary, btn.md, "flex-1")}>
                        {v.cta.label} <Arrow />
                    </a>
                    <button type="button" onClick={onClose} className={cx(btn.secondary, btn.md, "flex-1")}>Back to dashboard</button>
                </div>
            </div>
        </div>
    );
}

/** Rendered into <body> (see RoomDetailDrawer). */
export default function SuccessModal(props) {
    return createPortal(<SuccessModalInner {...props} />, document.body);
}
