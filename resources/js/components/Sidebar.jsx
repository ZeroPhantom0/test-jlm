// resources/js/components/Sidebar.jsx
import React, { useState, useEffect } from "react";
import { logout } from "../utils/logout";

const NAV_ITEMS = [
    { key: "dashboard",     label: "Dashboard",     icon: "fa-solid fa-border-all",        href: "/tenants" },
    { key: "services",      label: "Services",      icon: "fa-solid fa-wrench",            href: "/tenants/services/maintenance", hasSub: true, requiresTenant: true },
    { key: "billing",       label: "Billing",       icon: "fa-regular fa-credit-card",     href: "/tenants/billing",              requiresTenant: true },
    { key: "reservations",  label: "Reservations",  icon: "fa-regular fa-calendar-check",  href: "/tenants/reservations" },
    { key: "messages",      label: "Messages",      icon: "fa-regular fa-comment-dots",    href: "/tenants/messages" },
    { key: "announcements", label: "Announcements", icon: "fa-solid fa-bullhorn",          href: "/tenants/announcements" },
    // ← History intentionally removed from the sidebar.
    //   Still reachable via Dashboard → History → "View all".
    { key: "settings",      label: "Settings",      icon: "fa-solid fa-gear",              href: "/tenants/settings" },
];

const SERVICES_SUB = [
    { key: "services.maintenance",    label: "Maintenance",    icon: "fa-solid fa-wrench",     href: "/tenants/services/maintenance" },
    { key: "services.room-change",    label: "Room Change",    icon: "fa-solid fa-house",      href: "/tenants/services/room-change" },
    { key: "services.stay-extension", label: "Stay Extension", icon: "fa-regular fa-calendar", href: "/tenants/services/stay-extension" },
];

export default function Sidebar({
    active        = "dashboard",
    userName      = "Tenant",
    userRole      = "Tenant",
    avatar        = "https://i.pravatar.cc/100?img=47",
    isTenant      = false,
}) {
    const [collapsed, setCollapsed]       = useState(false);
    const [mobileOpen, setMobileOpen]     = useState(false);
    const [servicesOpen, setServicesOpen] = useState(active.startsWith("services"));

    useEffect(() => {
        const handler = () => setMobileOpen(true);
        window.addEventListener("open-mobile-sidebar", handler);
        return () => window.removeEventListener("open-mobile-sidebar", handler);
    }, []);

    useEffect(() => {
        if (active.startsWith("services")) setServicesOpen(true);
    }, [active]);

    useEffect(() => {
        document.body.style.overflow = mobileOpen ? "hidden" : "";
    }, [mobileOpen]);

    const isServicesActive = active.startsWith("services");

    const visibleItems = NAV_ITEMS.filter(
        (item) => !item.requiresTenant || isTenant
    );

    return (
        <>
            {/* Mobile overlay */}
            <div
                className={`
                    fixed inset-0 bg-navy-dark/50 backdrop-blur-sm z-[999]
                    transition-all duration-300
                    ${mobileOpen ? "opacity-100 visible" : "opacity-0 invisible pointer-events-none"}
                `}
                onClick={() => setMobileOpen(false)}
            />

            <aside
                className={`
                    bg-navy-dark text-white flex flex-col shrink-0 z-[1000]
                    transition-all duration-300
                    fixed md:sticky md:top-0 top-0 left-0 h-screen
                    ${mobileOpen ? "translate-x-0" : "-translate-x-full"} md:translate-x-0
                    ${collapsed ? "md:w-20 w-65" : "w-62.5"}
                `}
            >
                {/* Brand & Toggle */}
                <div className={`px-4.5 pt-5 pb-3.75 flex items-center justify-between whitespace-nowrap shrink-0 ${collapsed ? "md:justify-center md:px-0 md:py-5" : ""}`}>
                    <div className={`flex items-center gap-3 ${collapsed ? "md:hidden" : ""}`}>
                        <div className="w-9.5 h-9.5 rounded-full bg-white flex items-center justify-center text-brand-orange text-lg shrink-0">
                            <i className="fa-solid fa-house-chimney" />
                        </div>
                        <div className="transition-opacity duration-200">
                            <h3 className="text-sm font-bold leading-tight text-white">JLM Cozy Cabin</h3>
                            <p className="text-[11px] text-[#8fa0b5]">Tenant Portal</p>
                        </div>
                    </div>

                    {/* Desktop Collapse Button */}
                    <button
                        type="button"
                        onClick={() => setCollapsed(!collapsed)}
                        className="hidden md:block text-[#8fa0b5] hover:text-white rounded-md hover:bg-white/10 transition shrink-0"
                        style={{ fontSize: "18px", padding: "6px" }}
                        title="Toggle Navigation"
                    >
                        <i className="fa-solid fa-bars"></i>
                    </button>

                    {/* Mobile Close Button */}
                    <button type="button" onClick={() => setMobileOpen(false)}
                            className="md:hidden text-[#8fa0b5] text-lg p-1 hover:text-white"
                            aria-label="Close menu">
                        <i className="fa-solid fa-xmark" />
                    </button>
                </div>

                {/* User pill — dynamic */}
                <div className={`
                    bg-[#e06800] text-white font-semibold text-[13px] flex items-center gap-2.5
                    mx-3.5 mb-3.75 transition-all duration-300 shrink-0 whitespace-nowrap
                    ${collapsed
                        ? "md:w-10 md:h-10 md:mx-auto md:my-2.5 md:p-0 md:justify-center rounded-full px-3.5 py-2"
                        : "px-3.5 py-2 rounded-[20px]"}
                `}>
                    {avatar ? (
                        <img src={avatar} alt={userName}
                             className="w-[22px] h-[22px] rounded-full object-cover shrink-0 border border-white/30" />
                    ) : (
                        <i className="fa-regular fa-user-circle text-base shrink-0" />
                    )}
                    <span className={`truncate ${collapsed ? "md:hidden" : ""}`}>{userName}</span>
                </div>

                {/* Nav */}
                <ul className="flex-1 overflow-y-auto px-3 space-y-0.75 scrollbar-thin scrollbar-thumb-white/20">
                    {visibleItems.map((item) => {
                        const isActive = active === item.key || (item.key === "services" && isServicesActive);

                        if (item.hasSub) {
                            return (
                                <li key={item.key} className="flex flex-col text-[#8fa0b5] rounded-lg text-[13.5px] font-medium">
                                    <div className={`flex items-center justify-between w-full py-2.5 px-3 rounded-lg cursor-pointer transition-colors
                                        ${isActive ? "text-[#e06800] bg-[#e06800]/10" : "hover:text-white"}
                                        ${collapsed ? "md:justify-center md:py-3 md:px-0" : ""}`}>
                                        <a href={item.href} className={`flex items-center gap-3 flex-1 ${collapsed ? "md:gap-0 md:flex-initial" : ""}`}>
                                            <i className={`${item.icon} w-[18px] text-center text-[15px] shrink-0`} />
                                            <span className={`nav-text ${collapsed ? "md:hidden" : ""}`}>{item.label}</span>
                                        </a>
                                        <button type="button"
                                                onClick={(e) => { e.preventDefault(); e.stopPropagation(); setServicesOpen(!servicesOpen); }}
                                                aria-label="Toggle services submenu"
                                                className={`p-1 hover:text-white ${collapsed ? "md:hidden" : ""}`}>
                                            <i className={`fa-solid fa-chevron-${servicesOpen ? "down" : "right"} text-[10px] transition-transform`} />
                                        </button>
                                    </div>

                                    {servicesOpen && (
                                        <ul className={`pl-5 space-y-0.75 mt-0.5 mb-1 ${collapsed ? "md:hidden" : ""}`}>
                                            {SERVICES_SUB.map((sub) => (
                                                <li key={sub.key}>
                                                    <a href={sub.href}
                                                       className={`flex items-center gap-2.5 py-2 px-3 rounded-lg text-[13px] transition-colors
                                                           ${active === sub.key ? "bg-[#e06800] text-white font-semibold" : "text-[#8fa0b5] hover:text-white"}`}>
                                                        <i className={`${sub.icon} text-[12px] w-[18px] text-center`} />
                                                        <span>{sub.label}</span>
                                                    </a>
                                                </li>
                                            ))}
                                        </ul>
                                    )}
                                </li>
                            );
                        }

                        return (
                            <li key={item.key} className="flex flex-col text-[#8fa0b5] rounded-lg text-[13.5px] font-medium">
                                <a href={item.href}
                                   className={`flex items-center justify-between w-full py-2.5 px-3 rounded-lg transition-colors
                                       ${isActive ? "text-[#e06800] bg-[#e06800]/10" : "hover:text-white"}
                                       ${collapsed ? "md:justify-center md:py-3 md:px-0" : ""}`}>
                                    <div className={`flex items-center gap-3 ${collapsed ? "md:gap-0" : ""}`}>
                                        <i className={`${item.icon} w-[18px] text-center text-[15px] shrink-0`} />
                                        <span className={`nav-text ${collapsed ? "md:hidden" : ""}`}>{item.label}</span>
                                    </div>
                                </a>
                            </li>
                        );
                    })}
                </ul>

                {/* Footer (Only Logout now) */}
                <div className="p-3 border-t border-white/5 flex flex-col gap-0.5 shrink-0">
                    <button type="button" onClick={logout}
                            className={`flex items-center py-2.5 px-3 rounded-lg text-[13.5px] font-medium text-[#8fa0b5] hover:text-white transition-colors w-full ${collapsed ? "md:justify-center md:px-0" : ""}`}>
                        <div className={`flex items-center gap-3 ${collapsed ? "md:gap-0" : ""}`}>
                            <i className="fa-solid fa-right-from-bracket w-[18px] text-center text-[15px] shrink-0" />
                            <span className={collapsed ? "md:hidden" : ""}>Logout</span>
                        </div>
                    </button>
                </div>
            </aside>
        </>
    );
}