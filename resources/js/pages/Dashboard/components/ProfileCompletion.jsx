// resources/js/pages/Dashboard/components/ProfileCompletion.jsx
// T11 — real profile items only; hidden at 100%.
import React from "react";
import { useInView } from "../../../utils/motion";
import MatchScoreRing from "./MatchScoreRing";
import { BentoTile, cx, TileHeader } from "./ui";

export default function ProfileCompletion({ profile, xl = 4, md = 3, index = 0 }) {
    const [ref, inView] = useInView();
    if (!profile || profile.percent >= 100) return null;

    return (
        <BentoTile xl={xl} md={md} index={index} className="p-5" aria-labelledby="profile-title">
            <TileHeader icon="fa-solid fa-user-check" iconClass="bg-emerald-50 text-emerald-700" title={<span id="profile-title">Getting started</span>}
                subtitle="A complete profile gets approved faster." />
            <div ref={ref} className="flex items-center gap-3 mb-3">
                <MatchScoreRing score={profile.percent} size={56} stroke={6} start={inView} color="#16A34A" label={`Profile ${profile.percent}% complete`} />
                <p className="text-[13px] text-slate-700">Your profile is <span className="font-extrabold text-slate-900">{profile.percent}%</span> complete</p>
            </div>
            <ul className="space-y-1">
                {profile.items.map((it) => {
                    const content = (
                        <>
                            <span className={cx("w-5 h-5 rounded-full flex items-center justify-center shrink-0 border-2",
                                it.done ? "bg-[#16A34A] border-[#16A34A] text-white" : "border-slate-300")} aria-hidden="true">
                                {it.done && <i className="fa-solid fa-check text-[9px]" />}
                            </span>
                            <span className={cx("flex-1 text-[13px]", it.done ? "text-slate-400 line-through" : "text-slate-700 font-medium")}>
                                {it.label}<span className="sr-only">{it.done ? " (done)" : ""}</span>
                            </span>
                            {!it.done && (it.href
                                ? <i className="fa-solid fa-chevron-right text-[10px] text-slate-400" aria-hidden="true" />
                                : <span className="text-[11px] text-slate-500">Ask the admin</span>)}
                        </>
                    );
                    return (
                        <li key={it.key}>
                            {!it.done && it.href ? (
                                <a href={it.href} className="flex items-center gap-3 rounded-xl px-2 min-h-11 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#E8710A]">{content}</a>
                            ) : (
                                <div className="flex items-center gap-3 px-2 min-h-11">{content}</div>
                            )}
                        </li>
                    );
                })}
            </ul>
        </BentoTile>
    );
}
