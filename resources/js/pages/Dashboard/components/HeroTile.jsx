// resources/js/pages/Dashboard/components/HeroTile.jsx
// T1 — navy hero with drifting glow, grain, word-by-word greeting and the
// state's main CTA (always above the fold).
import React, { useRef } from "react";
import { prefersReducedMotion } from "../../../utils/motion";
import { Arrow, BentoTile, btn, CountUp, cx } from "./ui";

// Inline SVG noise for a soft grain texture (no image request)
const GRAIN = "url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='160' height='160'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.9' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='.55'/%3E%3C/svg%3E\")";

function copyFor(d) {
    if (d.tenantState === "pending") {
        const r = d.activeReservation;
        return {
            line: `Room ${r?.room?.num ?? ""} is being reviewed. We'll notify you as soon as it's approved.`,
            cta: { label: "View Reservation", href: d.links.reservations },
        };
    }
    if (d.tenantState === "resident") {
        const r = d.currentRoom;
        const where = r ? `Room ${r.num}${r.bed ? ` · Bed ${r.bed}` : ""}` : "";
        return {
            line: r?.moveInInDays ? `Your room is ready soon — ${where}.` : `Welcome home. ${where}`,
            cta: { label: "My Room", href: "#my-room" },
        };
    }
    return {
        line: "Your cozy spot is waiting. Find a room that fits your budget in minutes.",
        cta: { label: "Find a Room", href: "#recommended" },
    };
}

export default function HeroTile({ d, index = 0 }) {
    const glowRef = useRef(null);
    const { line, cta } = copyFor(d);
    const words = `${d.greeting}, ${d.user.firstName} 👋`.split(" ");

    // Subtle glow parallax on desktop pointers only
    const onMove = (e) => {
        if (prefersReducedMotion() || !window.matchMedia?.("(pointer: fine)").matches || !glowRef.current) return;
        const rect = e.currentTarget.getBoundingClientRect();
        const x = ((e.clientX - rect.left) / rect.width - 0.5) * 30;
        const y = ((e.clientY - rect.top) / rect.height - 0.5) * 20;
        glowRef.current.style.transform = `translate3d(${x}px, ${y}px, 0)`;
    };

    return (
        <BentoTile xl={12} md={6} index={index} tone="dark"
            className="relative overflow-hidden bg-gradient-to-br from-[#0F1A33] via-[#13234a] to-[#0F1A33] p-5 md:p-6"
            onMouseMove={onMove} aria-label="Welcome">
            <div ref={glowRef} className="absolute inset-0 transition-transform duration-500 ease-out pointer-events-none" aria-hidden="true">
                <span className="rb-hero-glow" style={{ width: 340, height: 340, right: -60, top: -140 }} />
                <span className="rb-hero-glow" style={{ width: 220, height: 220, left: -80, bottom: -120, animationDelay: "-6s", opacity: 0.6 }} />
            </div>
            <div className="absolute inset-0 opacity-[0.07] mix-blend-overlay pointer-events-none" style={{ backgroundImage: GRAIN }} aria-hidden="true" />

            <div className="relative flex flex-col lg:flex-row lg:items-center justify-between gap-5">
                <div className="min-w-0 max-w-2xl">
                    <h1 className="text-[22px] md:text-[26px] font-extrabold leading-tight tracking-tight" aria-label={`${d.greeting}, ${d.user.firstName}`}>
                        {words.map((w, i) => (
                            <span key={i} className="rb-word mr-[0.28em]" style={{ animationDelay: `${100 + i * 90}ms` }} aria-hidden="true">{w}</span>
                        ))}
                    </h1>
                    <p className="mt-1.5 text-[14px] text-slate-300 rb-fade-up" style={{ animationDelay: "380ms" }}>{line}</p>
                    <div className="mt-4 flex flex-wrap gap-2 rb-fade-up" style={{ animationDelay: "460ms" }}>
                        <a href={cta.href} className={cx(btn.primary, btn.md, "focus-visible:ring-offset-[#0F1A33]")}>{cta.label} <Arrow /></a>
                        <a href={d.links.messages} className={cx(btn.ghostDark, btn.md)}>
                            <i className="fa-regular fa-comment-dots" aria-hidden="true" /> Message Admin
                            {d.unreadMessages > 0 && (
                                <span className="ml-0.5 min-w-5 h-5 px-1.5 rounded-full bg-[#E11D48] text-white text-[11px] font-bold inline-flex items-center justify-center tabular-nums"
                                    aria-label={`${d.unreadMessages} unread`}>{d.unreadMessages}</span>
                            )}
                        </a>
                    </div>
                </div>

                {d.stats?.length > 0 && (
                    <ul className="flex flex-wrap lg:flex-col gap-2 lg:min-w-[200px]" aria-label="At a glance">
                        {d.stats.map((s, i) => (
                            <li key={s.label} className="rb-fade-up flex items-center gap-3 rounded-xl bg-white/[0.07] border border-white/10 px-3 py-2 backdrop-blur"
                                style={{ animationDelay: `${520 + i * 60}ms` }}>
                                <i className={cx(s.icon, "text-[#F59E0B] text-[14px] w-4 text-center")} aria-hidden="true" />
                                <span className="text-[12.5px] text-slate-200">
                                    <CountUp value={s.value} prefix={s.prefix || ""} className="text-[15px] font-bold text-white mr-1" />
                                    {s.label}
                                </span>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </BentoTile>
    );
}
