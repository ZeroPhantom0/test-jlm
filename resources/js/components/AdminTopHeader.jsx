// resources/js/components/AdminTopHeader.jsx
import React, { useState, useEffect, useRef } from "react";
import { logout } from "../utils/logout";

const STORAGE_KEY = "admin.dismissedNotifications";

function readDismissed() {
    try {
        return new Set(JSON.parse(sessionStorage.getItem(STORAGE_KEY) || "[]"));
    } catch {
        return new Set();
    }
}

function saveDismissed(set) {
    try {
        sessionStorage.setItem(STORAGE_KEY, JSON.stringify([...set]));
    } catch {}
}

function timeAgo(dateStr) {
    if (!dateStr) return "";
    const d = new Date(dateStr.replace(" ", "T"));
    const diff = Math.floor((Date.now() - d.getTime()) / 1000);
    if (diff < 60) return "Just now";
    if (diff < 3600) return `${Math.floor(diff / 60)} min ago`;
    if (diff < 86400) return `${Math.floor(diff / 3600)} hr ago`;
    if (diff < 604800) return `${Math.floor(diff / 86400)} day(s) ago`;
    return d.toLocaleDateString();
}

function csrfToken() {
    return document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute("content") || "";
}

export default function AdminTopHeader({
    title         = "Dashboard",
    userName      = "Administrator",
    userRole      = "Admin",
    userEmail     = "",
    avatar        = "https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&q=80&w=120",
    showHamburger = false,
}) {
    const [dropdownOpen, setDropdownOpen] = useState(false);
    const [notifOpen, setNotifOpen]       = useState(false);
    const [rawNotifications, setRawNotifications] = useState([]);
    const [loading, setLoading]                   = useState(false);
    const [dismissed, setDismissed]               = useState(() => readDismissed());

    const dropdownRef = useRef(null);
    const notifRef    = useRef(null);

    const notifications = rawNotifications.filter((n) => !dismissed.has(n.id));
    const unreadCount   = notifications.filter((n) => n.unread).length;

    const fetchNotifications = async () => {
        try {
            setLoading(true);
            const res = await fetch("/admin/notifications/feed", {
                headers: {
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                },
                credentials: "same-origin",
            });
            if (!res.ok) throw new Error("Failed to load notifications");
            const data = await res.json();
            setRawNotifications(data.notifications || []);
        } catch (err) {
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    // Initial + polling every 30s
    useEffect(() => {
        fetchNotifications();
        const id = setInterval(fetchNotifications, 30000);
        return () => clearInterval(id);
    }, []);

    // Refetch on open
    useEffect(() => {
        if (notifOpen) fetchNotifications();
    }, [notifOpen]);

    useEffect(() => {
        const handleClickOutside = (e) => {
            if (dropdownRef.current && !dropdownRef.current.contains(e.target)) {
                setDropdownOpen(false);
            }
            if (notifRef.current && !notifRef.current.contains(e.target)) {
                setNotifOpen(false);
            }
        };
        document.addEventListener("mousedown", handleClickOutside);
        return () => document.removeEventListener("mousedown", handleClickOutside);
    }, []);

    const dismiss = (id) => {
        setDismissed((prev) => {
            const next = new Set(prev);
            next.add(id);
            saveDismissed(next);
            return next;
        });
    };

    const clearAll = () => {
        const next = new Set(dismissed);
        notifications.forEach((n) => next.add(n.id));
        saveDismissed(next);
        setDismissed(next);
    };

    const handleClickNotification = (n) => {
        // 1. Hide locally
        dismiss(n.id);

        // 2. If it's a message, mark the underlying message as read in DB
        if (n.type === "message" && n.id.startsWith("msg-")) {
            const messageId = n.id.replace("msg-", "");
            fetch("/admin/notifications/message-read", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": csrfToken(),
                },
                credentials: "same-origin",
                body: JSON.stringify({ message_id: messageId }),
            }).catch(console.error);
        }

        // 3. Let the anchor navigate normally
    };

    return (
        <header className="bg-white px-4 md:px-8 py-3.5 flex items-center justify-between border-b border-slate-200 sticky top-0 z-[100]">
            <div className="flex items-center gap-4">
                {showHamburger && (
                    <button
                        className="md:hidden text-slate-700 text-lg p-1"
                        onClick={() => window.dispatchEvent(new Event("open-mobile-sidebar"))}
                        aria-label="Open menu"
                    >
                        <i className="fa-solid fa-bars"></i>
                    </button>
                )}
                <h1 className="text-[20px] md:text-[22px] font-bold text-slate-900">{title}</h1>
            </div>

            <div className="flex items-center gap-5 relative">
                {/* Notification Bell */}
                <div className="relative" ref={notifRef}>
                    <button
                        type="button"
                        onClick={() => setNotifOpen(!notifOpen)}
                        className="relative w-9 h-9 flex items-center justify-center rounded-full text-slate-600 hover:bg-slate-100 transition"
                        aria-label="Notifications"
                    >
                        <i className="fa-solid fa-bell text-[17px]"></i>
                        {unreadCount > 0 && (
                            <span className="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 flex items-center justify-center bg-rose-500 text-white text-[10px] font-bold rounded-full border-2 border-white">
                                {unreadCount > 99 ? "99+" : unreadCount}
                            </span>
                        )}
                    </button>

                    {notifOpen && (
                        <div className="absolute right-0 mt-3 w-[340px] bg-white rounded-2xl border border-slate-200 shadow-xl z-[2000] overflow-hidden">
                            <div className="flex items-center justify-between px-4 py-3 border-b border-slate-100">
                                <div className="flex items-center gap-2">
                                    <h4 className="text-[14px] font-bold text-slate-900">
                                        Notifications
                                    </h4>
                                    {unreadCount > 0 && (
                                        <span className="px-2 py-0.5 bg-rose-100 text-rose-600 text-[10px] font-bold rounded-full">
                                            {unreadCount} new
                                        </span>
                                    )}
                                </div>
                                {notifications.length > 0 && (
                                    <button
                                        type="button"
                                        onClick={clearAll}
                                        className="text-[11px] font-semibold text-orange-600 hover:text-orange-700"
                                    >
                                        Clear all
                                    </button>
                                )}
                            </div>

                            <div className="max-h-[380px] overflow-y-auto">
                                {loading && notifications.length === 0 ? (
                                    <div className="py-12 text-center text-[12px] text-slate-500">
                                        Loading…
                                    </div>
                                ) : notifications.length === 0 ? (
                                    <div className="flex flex-col items-center justify-center py-12 px-4 text-center">
                                        <div className="w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center text-slate-400 text-lg mb-2">
                                            <i className="fa-solid fa-inbox"></i>
                                        </div>
                                        <p className="text-[12px] text-slate-500">
                                            No notifications
                                        </p>
                                    </div>
                                ) : (
                                    notifications.map((n) => (
                                        <a
                                            key={n.id}
                                            href={n.link}
                                            onClick={() => handleClickNotification(n)}
                                            className={`flex items-start gap-3 px-4 py-3 border-b border-slate-50 last:border-b-0 hover:bg-slate-50 transition ${
                                                n.unread ? "bg-orange-50/40" : ""
                                            }`}
                                        >
                                            <div className={`w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0 ${n.color}`}>
                                                <i className={`${n.icon} text-[13px]`}></i>
                                            </div>
                                            <div className="flex-1 min-w-0">
                                                <p className="text-[12.5px] font-semibold text-slate-900 leading-tight">
                                                    {n.title}
                                                </p>
                                                <p className="text-[11.5px] text-slate-500 mt-0.5 truncate">
                                                    {n.description}
                                                </p>
                                                <p className="text-[10.5px] text-slate-400 mt-1">
                                                    {timeAgo(n.time)}
                                                </p>
                                            </div>
                                            {n.unread && (
                                                <span className="w-2 h-2 rounded-full bg-rose-500 mt-1.5 flex-shrink-0" />
                                            )}
                                        </a>
                                    ))
                                )}
                            </div>
                        </div>
                    )}
                </div>

                {/* Profile Dropdown */}
                <div className="relative" ref={dropdownRef}>
                    <div
                        className="flex items-center gap-2.5 cursor-pointer select-none px-2 py-1 rounded-lg transition hover:bg-slate-100"
                        onClick={() => setDropdownOpen(!dropdownOpen)}
                    >
                        <div className="hidden md:block text-right">
                            <h4 className="text-[13px] font-bold text-slate-900 leading-tight">
                                {userName}
                            </h4>
                            <p className="text-[11px] text-slate-500">{userRole}</p>
                        </div>
                        <img
                            src={avatar}
                            alt={userName}
                            className="w-9 h-9 rounded-full object-cover border border-slate-200"
                        />
                        <i
                            className={`fa-solid fa-chevron-down text-[11px] text-slate-500 transition-transform ${dropdownOpen ? "rotate-180" : ""}`}
                        ></i>
                    </div>

                    {dropdownOpen && (
                        <div className="absolute right-0 mt-3 w-[270px] bg-white rounded-2xl border border-slate-200 shadow-xl z-[2000] overflow-hidden">
                            <div className="p-4 bg-slate-50 border-b border-slate-100 flex items-center gap-3">
                                <img src={avatar} alt={userName} className="w-[42px] h-[42px] rounded-full object-cover" />
                                <div className="overflow-hidden">
                                    <h4 className="text-[13.5px] font-bold text-slate-900 leading-tight">{userName}</h4>
                                    <p className="text-[11px] text-slate-500 mt-0.5 truncate">
                                        {userEmail || "admin@jlmcozycabin.com"}
                                    </p>
                                    <span className="inline-block mt-1 px-2 py-0.5 bg-orange-100 text-orange-700 text-[10px] font-bold rounded-[10px]">
                                        Super Admin
                                    </span>
                                </div>
                            </div>
                            <div className="p-2 flex flex-col gap-0.5">
                                <a href="/admin/settings" className="flex items-center gap-3 px-3 py-2.5 rounded-lg text-[13px] font-medium text-slate-700 hover:bg-slate-100 transition">
                                    <i className="fa-regular fa-circle-user w-4 text-center text-slate-500"></i>
                                    <span>Account Settings</span>
                                </a>
                                <a href="#" className="flex items-center gap-3 px-3 py-2.5 rounded-lg text-[13px] font-medium text-slate-700 hover:bg-slate-100 transition">
                                    <i className="fa-solid fa-user-clock w-4 text-center text-slate-500"></i>
                                    <span>My Activity Log</span>
                                </a>
                                <button type="button" onClick={logout} className="flex items-center gap-3 px-3 py-2.5 rounded-lg text-[13px] font-medium text-rose-600 hover:bg-rose-50 transition border-t border-slate-100 mt-1 rounded-b-lg w-full text-left">
                                    <i className="fa-solid fa-arrow-right-from-bracket w-4 text-center text-rose-600"></i>
                                    <span>Logout</span>
                                </button>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </header>
    );
}