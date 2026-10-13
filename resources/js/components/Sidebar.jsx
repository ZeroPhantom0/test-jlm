import React, { useState, useEffect } from "react";

const NAV_ITEMS = [
    {
        key: "dashboard",
        label: "Dashboard",
        icon: "fa-solid fa-border-all",
        href: "/tenants",
    },
    {
        key: "services",
        label: "Services",
        icon: "fa-solid fa-wrench",
        href: "/tenants/services/maintenance",
        hasSub: true,
    },
    {
        key: "billing",
        label: "Billing",
        icon: "fa-regular fa-credit-card",
        href: "/tenants/billing",
    },
    {
        key: "reservations",
        label: "Reservations",
        icon: "fa-regular fa-calendar-check",
        href: "/tenants/reservations",
    },
    {
        key: "messages",
        label: "Messages",
        icon: "fa-regular fa-comment-dots",
        href: "/tenants/messages",
    },
    {
        key: "announcements",
        label: "Announcements",
        icon: "fa-solid fa-bullhorn",
        href: "/tenants/announcements",
    },
    {
        key: "history",
        label: "History",
        icon: "fa-solid fa-clock-rotate-left",
        href: "/tenants/history",
    },
    {
        key: "settings",
        label: "Settings",
        icon: "fa-solid fa-gear",
        href: "/tenants/settings",
    },
];

const SERVICES_SUB = [
    {
        key: "services.maintenance",
        label: "Maintenance",
        icon: "fa-solid fa-wrench",
        href: "/tenants/services/maintenance",
    },
    {
        key: "services.room-change",
        label: "Room Change",
        icon: "fa-solid fa-house",
        href: "/tenants/services/room-change",
    },
    {
        key: "services.stay-extension",
        label: "Stay Extension",
        icon: "fa-regular fa-calendar",
        href: "/tenants/services/stay-extension",
    },
];

export default function Sidebar({ active = "dashboard" }) {
    const [collapsed, setCollapsed] = useState(false);
    const [mobileOpen, setMobileOpen] = useState(false);
    const [servicesOpen, setServicesOpen] = useState(
        active.startsWith("services"),
    );

    useEffect(() => {
        const handler = () => setMobileOpen(true);
        window.addEventListener("open-mobile-sidebar", handler);
        return () => window.removeEventListener("open-mobile-sidebar", handler);
    }, []);

    useEffect(() => {
        if (active.startsWith("services")) {
            setServicesOpen(true);
        }
    }, [active]);

    useEffect(() => {
        if (mobileOpen) {
            document.body.style.overflow = "hidden";
        } else {
            document.body.style.overflow = "";
        }
    }, [mobileOpen]);

    const isServicesActive = active.startsWith("services");

    return (
        <>
            {/* Mobile Sidebar Overlay */}
            <div
                className={`
                    fixed inset-0 bg-navy-dark/50 backdrop-blur-sm z-1000
                    transition-all duration-300
                    ${mobileOpen ? "opacity-100 visible" : "opacity-0 invisible pointer-events-none"}
                `}
                onClick={() => setMobileOpen(false)}
            />

            {/* Sidebar Container */}
            <aside
                className={`
                    bg-navy-dark text-white flex flex-col shrink-0 z-1001
                    transition-all duration-300
                    fixed md:sticky md:top-0 top-0 left-0 h-screen
                    ${mobileOpen ? "translate-x-0" : "-translate-x-full"} md:translate-x-0
                    ${collapsed ? "md:w-20 w-65" : "w-62.5"}
                `}
            >
                {/* Brand Header */}
                <div
                    className={`
                        px-4.5 pt-5 pb-3.75 flex items-center justify-between whitespace-nowrap shrink-0
                        ${collapsed ? "md:justify-center md:px-0 md:py-5" : ""}
                    `}
                >
                    <div className="flex items-center gap-3">
                        <div className="w-9.5 h-9.5 rounded-full bg-white flex items-center justify-center text-brand-orange text-lg flex-shrink-0">
                            <i className="fa-solid fa-house-chimney" />
                        </div>
                        <div
                            className={`transition-opacity duration-200 ${collapsed ? "md:hidden" : ""}`}
                        >
                            <h3 className="text-sm font-bold leading-tight text-white">
                                JLM Cozy Cabin
                            </h3>
                            <p className="text-[11px] text-[#8fa0b5]">
                                Tenant Portal
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        onClick={() => setMobileOpen(false)}
                        className="md:hidden text-[#8fa0b5] text-lg p-1 hover:text-white"
                        aria-label="Close menu"
                    >
                        <i className="fa-solid fa-xmark" />
                    </button>
                </div>

                {/* User Pill */}
                <div
                    className={`
                        bg-[#e06800] text-white font-semibold text-[13px] flex items-center gap-2.5
                        mx-3.5 mb-3.75 transition-all duration-300 flex-shrink-0 whitespace-nowrap
                        ${
                            collapsed
                                ? "md:w-10 md:h-10 md:mx-auto md:my-2.5 md:p-0 md:justify-center rounded-full px-3.5 py-2"
                                : "px-3.5 py-2 rounded-[20px]"
                        }
                    `}
                >
                    <i className="fa-regular fa-user-circle text-base" />
                    <span className={collapsed ? "md:hidden" : ""}>
                        Elle Dela Cruz
                    </span>
                </div>

                {/* Navigation Menu */}
                <ul className="flex-1 overflow-y-auto px-3 space-y-0.75 scrollbar-thin scrollbar-thumb-white/20">
                    {NAV_ITEMS.map((item) => {
                        const isActive =
                            active === item.key ||
                            (item.key === "services" && isServicesActive);

                        if (item.hasSub) {
                            return (
                                <li
                                    key={item.key}
                                    className="flex flex-col text-[#8fa0b5] rounded-lg text-[13.5px] font-medium"
                                >
                                    <div
                                        className={`
                                            flex items-center justify-between w-full py-2.5 px-3 rounded-lg cursor-pointer transition-colors
                                            ${isActive ? "text-[#e06800] bg-[#e06800]/10" : "hover:text-white"}
                                            ${collapsed ? "md:justify-center md:py-3 md:px-0" : ""}
                                        `}
                                    >
                                        {/* Direct link routing to /tenants/services/maintenance */}
                                        <a
                                            href={item.href}
                                            className={`flex items-center gap-3 flex-1 ${collapsed ? "md:gap-0 md:flex-initial" : ""}`}
                                        >
                                            <i
                                                className={`${item.icon} w-[18px] text-center text-[15px] flex-shrink-0`}
                                            />
                                            <span
                                                className={`nav-text ${collapsed ? "md:hidden" : ""}`}
                                            >
                                                {item.label}
                                            </span>
                                        </a>

                                        {/* Dropdown toggle arrow */}
                                        <button
                                            type="button"
                                            onClick={(e) => {
                                                e.preventDefault();
                                                e.stopPropagation();
                                                setServicesOpen(!servicesOpen);
                                            }}
                                            aria-label="Toggle services submenu"
                                            className={`p-1 hover:text-white ${collapsed ? "md:hidden" : ""}`}
                                        >
                                            <i
                                                className={`fa-solid fa-chevron-${servicesOpen ? "down" : "right"} text-[10px] transition-transform`}
                                            />
                                        </button>
                                    </div>

                                    {/* Submenu Items */}
                                    {servicesOpen && (
                                        <ul
                                            className={`pl-5 space-y-0.75 mt-0.5 mb-1 ${collapsed ? "md:hidden" : ""}`}
                                        >
                                            {SERVICES_SUB.map((sub) => {
                                                const isSubActive =
                                                    active === sub.key;
                                                return (
                                                    <li key={sub.key}>
                                                        <a
                                                            href={sub.href}
                                                            className={`
                                                                flex items-center gap-2.5 py-2 px-3 rounded-lg text-[13px] transition-colors
                                                                ${
                                                                    isSubActive
                                                                        ? "bg-[#e06800] text-white font-semibold"
                                                                        : "text-[#8fa0b5] hover:text-white"
                                                                }
                                                            `}
                                                        >
                                                            <i
                                                                className={`${sub.icon} text-[12px] w-[18px] text-center`}
                                                            />
                                                            <span>
                                                                {sub.label}
                                                            </span>
                                                        </a>
                                                    </li>
                                                );
                                            })}
                                        </ul>
                                    )}
                                </li>
                            );
                        }

                        return (
                            <li
                                key={item.key}
                                className="flex flex-col text-[#8fa0b5] rounded-lg text-[13.5px] font-medium"
                            >
                                <a
                                    href={item.href}
                                    className={`
                                        flex items-center justify-between w-full py-2.5 px-3 rounded-lg transition-colors
                                        ${isActive ? "text-[#e06800] bg-[#e06800]/10" : "hover:text-white"}
                                        ${collapsed ? "md:justify-center md:py-3 md:px-0" : ""}
                                    `}
                                >
                                    <div
                                        className={`flex items-center gap-3 ${collapsed ? "md:gap-0" : ""}`}
                                    >
                                        <i
                                            className={`${item.icon} w-[18px] text-center text-[15px] flex-shrink-0`}
                                        />
                                        <span
                                            className={`nav-text ${collapsed ? "md:hidden" : ""}`}
                                        >
                                            {item.label}
                                        </span>
                                    </div>
                                    {!collapsed && (
                                        <i className="fa-solid fa-chevron-right text-[10px] opacity-0 group-hover:opacity-100 md:hidden" />
                                    )}
                                </a>
                            </li>
                        );
                    })}
                </ul>

                {/* Footer */}
                <div className="p-3 border-t border-white/5 flex flex-col gap-0.5 flex-shrink-0">
                    <a
                        href="/logout"
                        className={`
                            flex items-center py-2.5 px-3 rounded-lg text-[13.5px] font-medium text-[#8fa0b5] hover:text-white transition-colors
                            ${collapsed ? "md:justify-center md:px-0" : ""}
                        `}
                    >
                        <div
                            className={`flex items-center gap-3 ${collapsed ? "md:gap-0" : ""}`}
                        >
                            <i className="fa-solid fa-right-from-bracket w-[18px] text-center text-[15px] flex-shrink-0" />
                            <span className={collapsed ? "md:hidden" : ""}>
                                Logout
                            </span>
                        </div>
                    </a>

                    <button
                        type="button"
                        onClick={() => setCollapsed(!collapsed)}
                        className={`
                            hidden md:flex w-full items-center py-2.5 px-3 rounded-lg text-[13.5px] font-medium
                            text-[#8fa0b5] hover:text-white transition-colors
                            ${collapsed ? "md:justify-center md:px-0" : ""}
                        `}
                    >
                        <div
                            className={`flex items-center gap-3 ${collapsed ? "md:gap-0" : ""}`}
                        >
                            <i className="fa-solid fa-bars-staggered w-[18px] text-center text-[15px] flex-shrink-0" />
                            <span className={collapsed ? "md:hidden" : ""}>
                                Collapse Menu
                            </span>
                        </div>
                    </button>
                </div>
            </aside>
        </>
    );
}
