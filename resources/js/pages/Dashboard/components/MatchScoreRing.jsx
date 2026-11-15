// resources/js/pages/Dashboard/components/MatchScoreRing.jsx
import React from "react";
import { useCountUp } from "../../../utils/motion";
import { cx } from "./ui";

/** Circular ring that draws in (and counts up) once `start` is true. */
export default function MatchScoreRing({ score = 0, size = 52, stroke = 5, start = true, light = false, label, color = "#E8710A" }) {
    const r = (size - stroke) / 2;
    const c = 2 * Math.PI * r;
    const shown = useCountUp(score ?? 0, start);
    return (
        <div className="relative shrink-0" style={{ width: size, height: size }} role="img" aria-label={label || `${score}% match`}>
            <svg width={size} height={size} className="-rotate-90" aria-hidden="true">
                <circle cx={size / 2} cy={size / 2} r={r} fill="none" strokeWidth={stroke}
                    stroke={light ? "rgba(255,255,255,0.25)" : "#E2E8F0"} />
                <circle cx={size / 2} cy={size / 2} r={r} fill="none" strokeWidth={stroke} strokeLinecap="round"
                    stroke={color} className="rb-ring-fill" strokeDasharray={c}
                    strokeDashoffset={start ? c * (1 - (score ?? 0) / 100) : c} />
            </svg>
            <span className={cx("absolute inset-0 flex items-center justify-center font-extrabold tabular-nums",
                size >= 64 ? "text-[15px]" : size >= 52 ? "text-[12px]" : "text-[11px]",
                light ? "text-white" : "text-slate-800")} aria-hidden="true">
                {shown}%
            </span>
        </div>
    );
}
