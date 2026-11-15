// resources/js/components/NavBadge.jsx
// Live count badge for sidebar items: a pill when expanded, a dot when collapsed.
import React from "react";

export default function NavBadge({ count = 0, collapsed = false, label = "" }) {
    if (!count) return null;
    const text = count > 99 ? "99+" : String(count);
    return (
        <>
            <span
                className={`ml-auto shrink-0 min-w-5 h-5 px-1.5 rounded-full bg-[#E11D48] text-white text-[10.5px] font-bold inline-flex items-center justify-center tabular-nums rb-pop-in ${collapsed ? "md:hidden" : ""}`}
                aria-label={`${count} ${label}`.trim()}
            >
                {text}
            </span>
            {collapsed && (
                <span className="hidden md:block absolute top-1.5 right-3 w-2 h-2 rounded-full bg-[#E11D48] ring-2 ring-[#02122b]" aria-hidden="true" />
            )}
        </>
    );
}
