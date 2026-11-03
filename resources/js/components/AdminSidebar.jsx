// resources/js/components/AdminSidebar.jsx
import React, { useState, useEffect, useRef } from "react";
import { logout } from "../utils/logout";

const NAV_ITEMS = [
    {
        key: "dashboard",
        label: "Dashboard",
        icon: "fa-solid fa-border-all",
        href: "/admin",
    },
    {
        key: "rooms",
        label: "Rooms",
        icon: "fa-solid fa-bed",
        href: "/admin/rooms",
    },
    {
        key: "tenants",
        label: "Tenants",
        icon: "fa-solid fa-users",
        href: "/admin/tenants",
    },
    {
        key: "services",
        label: "Services",
        icon: "fa-solid fa-wrench",
        href: "/admin/services/maintenance",
        hasSub: true,
    },
    {
        key: "billing",
        label: "Billing",
        icon: "fa-solid fa-credit-card",
        href: "/admin/billing",
    },
    {
        key: "reservations",
        label: "Reservations",
        icon: "fa-regular fa-calendar-check",
        href: "/admin/reservations",
    },
    {
        key: "messages",
        label: "Messages",
        icon: "fa-regular fa-comment-dots",
        href: "/admin/messages",
    },
    {
        key: "announcements",
        label: "Announcements",
        icon: "fa-solid fa-bullhorn",
        href: "/admin/announcements",
    },
    {
        key: "virtual-tour",
        label: "Virtual Room Tour",
        icon: "fa-solid fa-camera",
        href: "/admin/virtual-tour",
    },
    {
        key: "reports",
        label: "Reports",
        icon: "fa-regular fa-file-lines",
        href: "/admin/reports",
    },
    {
        key: "history",
        label: "History",
        icon: "fa-solid fa-clock-rotate-left",
        href: "/admin/history",
    },
    {
        key: "audit-logs",
        label: "Audit Logs",
        icon: "fa-solid fa-shield-halved",
        href: "/admin/audit-logs",
    },
    {
        key: "settings",
        label: "Settings",
        icon: "fa-solid fa-gear",
        href: "/admin/settings",
    },
];

const SERVICES_SUB = [
    {
        key: "services.maintenance",
        label: "Maintenance",
        icon: "fa-solid fa-wrench",
        href: "/admin/services/maintenance",
    },
    {
        key: "services.room-change",
        label: "Room Change",
        icon: "fa-solid fa-house",
        href: "/admin/services/room-change",
    },
    {
        key: "services.stay-extension",
        label: "Stay Extension",
        icon: "fa-regular fa-calendar",
        href: "/admin/services/stay-extension",
    },
];

/* Reusable avatar block */
function SidebarAvatar({ src, name = "User", size = 34 }) {
    if (src && typeof src === "string" && src.trim() !== "" && src !== "null") {
        return (
            <img
                src={src}
                alt={name}
                className="rounded-full object-cover border-[1.5px] border-white shrink-0"
                style={{ width: `${size}px`, height: `${size}px` }}
            />
        );
    }
    return (
        <div
            className="rounded-full bg-white/10 text-white flex items-center justify-center shrink-0 border-[1.5px] border-white"
            style={{ width: `${size}px`, height: `${size}px` }}
            aria-label={name}
        >
            <i className="fa-solid fa-user" style={{ fontSize: size * 0.45 }} />
        </div>
    );
}

export default function AdminSidebar({
    active = "dashboard",
    userName = "Administrator",
    userRole = "Administrator",
    avatar = null,
}) {
    const [collapsed, setCollapsed] = useState(false);
    const [mobileOpen, setMobileOpen] = useState(false);
    const [servicesOpen, setServicesOpen] = useState(
        active.startsWith("services"),
    );

    const activeRef = useRef(null);

    useEffect(() => {
        const handler = () => setMobileOpen(true);
        window.addEventListener("open-mobile-sidebar", handler);
        return () => window.removeEventListener("open-mobile-sidebar", handler);
    }, []);

    useEffect(() => {
        if (active.startsWith("services")) setServicesOpen(true);
    }, [active]);

    useEffect(() => {
        if (activeRef.current) {
            activeRef.current.scrollIntoView({
                behavior: "smooth",
                block: "center",
            });
        }
    }, [active]);

    useEffect(() => {
        const overlay = document.getElementById("admin-sidebar-overlay");
        if (mobileOpen) {
            overlay?.classList.remove("hidden");
            overlay.onclick = () => setMobileOpen(false);
        } else {
            overlay?.classList.add("hidden");
        }
    }, [mobileOpen]);

    const isServicesActive = active.startsWith("services");

    return (
        <aside
            className={`
                bg-[#02122b] text-white flex flex-col shrink-0 h-full z-[1000]
                transition-all duration-300 overflow-x-hidden
                fixed md:sticky md:top-0 top-0 left-0
                ${mobileOpen ? "translate-x-0" : "-translate-x-full"} md:translate-x-0
                ${collapsed ? "md:w-20 w-64" : "w-64"}
            `}
        >
            {/* Brand */}
            <div
                className={`flex items-center shrink-0 whitespace-nowrap px-[18px] pt-5 pb-3.5 ${collapsed ? "md:justify-center md:px-0" : "justify-between"}`}
            >
                <div
                    className={`flex items-center gap-3 min-w-0 ${collapsed ? "md:hidden" : ""}`}
                >
                    <div className="w-[38px] h-[38px] rounded-full bg-white flex items-center justify-center shadow-sm shrink-0 overflow-hidden p-0.5">
                        <img
                            src="/images/jlm-logo.png"
                            alt="JLM Cozy Cabin Logo"
                            className="w-full h-full object-contain"
                            onError={(e) => {
                                e.currentTarget.style.display = "none";
                                e.currentTarget.nextSibling.style.display =
                                    "block";
                            }}
                        />
                        <i
                            className="fa-solid fa-house-key text-[#f97316]"
                            style={{ display: "none", fontSize: "18px" }}
                        ></i>
                    </div>
                    <div className="overflow-hidden min-w-0">
                        <h3
                            className="font-bold leading-tight text-white"
                            style={{ fontSize: "15px" }}
                        >
                            JLM Cozy Cabin
                        </h3>
                        <p
                            className="text-[#8fa0b5]"
                            style={{ fontSize: "11px" }}
                        >
                            Admin Portal
                        </p>
                    </div>
                </div>
                <button
                    onClick={() => setCollapsed(!collapsed)}
                    className={`text-[#8fa0b5] hover:text-white rounded-md hover:bg-white/10 transition hidden md:block shrink-0 ${collapsed ? "mx-auto" : ""}`}
                    style={{ fontSize: "18px", padding: "6px" }}
                    title="Toggle Navigation"
                >
                    <i className="fa-solid fa-bars"></i>
                </button>
            </div>

            {/* Administrator pill */}
            <div
                className={`
                    bg-[#f97316] text-white font-bold flex items-center shrink-0 whitespace-nowrap
                    rounded-xl shadow-[0_2px_6px_rgba(249,115,22,0.25)] transition-all
                    ${collapsed ? "md:w-11 md:h-11 md:mx-auto md:p-0 md:justify-center md:rounded-full md:gap-0" : "gap-3 mx-[14px] mb-4 mt-1"}
                `}
                style={
                    !collapsed ? { padding: "10px 14px", fontSize: "14px" } : {}
                }
            >
                <i
                    className="fa-solid fa-shield-halved shrink-0"
                    style={{ fontSize: "16px" }}
                ></i>
                <span className={collapsed ? "md:hidden" : ""}>
                    Administrator
                </span>
            </div>

            {/* Nav */}
            <ul className="flex-1 overflow-y-auto overflow-x-hidden no-scrollbar flex flex-col min-h-0 px-2.5 gap-0.5">
                {NAV_ITEMS.map((item) => {
                    const isActive =
                        active === item.key ||
                        (item.key === "services" && isServicesActive);

                    if (item.hasSub) {
                        return (
                            <li key={item.key} className="flex flex-col w-full">
                                <a
                                    href={item.href}
                                    onClick={() => setServicesOpen(true)}
                                    ref={isActive ? activeRef : null}
                                    className={`
                                        flex items-center justify-between w-full rounded-[10px]
                                        font-medium whitespace-nowrap transition-all py-2.5 px-3 text-[13.5px]
                                        ${
                                            isActive
                                                ? "text-[#ffaa6e] bg-[#092042] font-semibold border-l-[3px] border-[#f97316]"
                                                : "text-slate-400 hover:text-white hover:bg-white/5"
                                        }
                                        ${collapsed ? "md:justify-center md:px-0" : ""}
                                    `}
                                    title={item.label}
                                >
                                    <div
                                        className={`flex items-center ${collapsed ? "md:justify-center" : ""}`}
                                        style={{
                                            gap: collapsed ? "0" : "14px",
                                        }}
                                    >
                                        <i
                                            className={`${item.icon} text-center shrink-0`}
                                            style={{
                                                width: "20px",
                                                fontSize: "15px",
                                            }}
                                        ></i>
                                        <span
                                            className={
                                                collapsed ? "md:hidden" : ""
                                            }
                                        >
                                            {item.label}
                                        </span>
                                    </div>
                                    <button
                                        type="button"
                                        onClick={(e) => {
                                            e.preventDefault();
                                            e.stopPropagation();
                                            setServicesOpen(!servicesOpen);
                                        }}
                                        className={`rounded hover:bg-white/10 shrink-0 ${collapsed ? "md:hidden" : ""}`}
                                        style={{ padding: "4px" }}
                                        aria-label="Toggle services submenu"
                                    >
                                        <i
                                            className={`fa-solid fa-chevron-${servicesOpen ? "up" : "down"} text-slate-500`}
                                            style={{ fontSize: "11px" }}
                                        ></i>
                                    </button>
                                </a>

                                {servicesOpen && (
                                    <ul
                                        className={`flex flex-col w-full ${collapsed ? "md:hidden" : ""}`}
                                        style={{
                                            paddingLeft: "28px",
                                            marginTop: "2px",
                                            marginBottom: "6px",
                                            gap: "2px",
                                        }}
                                    >
                                        {SERVICES_SUB.map((sub) => (
                                            <li key={sub.key}>
                                                <a
                                                    href={sub.href}
                                                    ref={
                                                        active === sub.key
                                                            ? activeRef
                                                            : null
                                                    }
                                                    className={`
                                                        flex items-center rounded-lg w-full transition-all py-2 px-3 text-[13px] gap-2.5
                                                        ${active === sub.key ? "bg-[#f97316] text-white font-semibold" : "text-slate-400 hover:text-white hover:bg-white/5"}
                                                    `}
                                                >
                                                    <i
                                                        className={`${sub.icon} shrink-0`}
                                                        style={{
                                                            fontSize: "11px",
                                                        }}
                                                    ></i>
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
                        <li key={item.key} className="flex flex-col w-full">
                            <a
                                href={item.href}
                                ref={isActive ? activeRef : null}
                                className={`
                                    flex items-center w-full rounded-[10px]
                                    font-medium whitespace-nowrap transition-all py-2.5 px-3 text-[13.5px]
                                    ${
                                        isActive
                                            ? "text-[#ffaa6e] bg-[#092042] font-semibold border-l-[3px] border-[#f97316]"
                                            : "text-slate-400 hover:text-white hover:bg-white/5"
                                    }
                                    ${collapsed ? "md:justify-center md:px-0" : ""}
                                `}
                                title={item.label}
                            >
                                <div
                                    className={`flex items-center ${collapsed ? "md:justify-center" : ""}`}
                                    style={{ gap: collapsed ? "0" : "14px" }}
                                >
                                    <i
                                        className={`${item.icon} text-center shrink-0`}
                                        style={{
                                            width: "20px",
                                            fontSize: "15px",
                                        }}
                                    ></i>
                                    <span
                                        className={collapsed ? "md:hidden" : ""}
                                    >
                                        {item.label}
                                    </span>
                                </div>
                            </a>
                        </li>
                    );
                })}
            </ul>

            {/* Footer user card */}
            <div
                className={`
                    w-full flex items-center shrink-0 bg-[#02122b] whitespace-nowrap overflow-hidden
                    border-t border-white/10 p-[14px]
                    ${collapsed ? "md:justify-center" : "justify-between"}
                `}
            >
                <div
                    className={`flex items-center gap-3 min-w-0 flex-1 ${collapsed ? "md:hidden" : ""}`}
                >
                    <SidebarAvatar src={avatar} name={userName} size={34} />
                    <div className="min-w-0 flex-1">
                        <h4
                            className="font-bold text-white leading-tight truncate"
                            style={{ fontSize: "13px" }}
                        >
                            {userName}
                        </h4>
                        <p
                            className="text-[#8fa0b5] truncate"
                            style={{ fontSize: "11px" }}
                        >
                            {userRole.toLowerCase()}
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    onClick={(e) => logout(e)}
                    className={`text-[#8fa0b5] hover:text-white transition cursor-pointer shrink-0 ${collapsed ? "md:flex md:mx-auto" : "hidden md:block"}`}
                    style={{ fontSize: "16px" }}
                    title="Logout"
                >
                    <i className="fa-solid fa-right-from-bracket"></i>
                </button>
            </div>
        </aside>
    );
}
