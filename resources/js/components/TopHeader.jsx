// resources/js/components/TopHeader.jsx
import React, { useState, useEffect, useRef } from "react";
import { logout } from "../utils/logout";

const STORAGE_KEY = "tenant.dismissedNotifications";
const MAX_VISIBLE = 5;

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

/* Reusable avatar block: shows uploaded pic if present, otherwise a default user icon */
function UserAvatar({ src, name = "User", size = 34, className = "" }) {
    const dimension = `${size}px`;
    
    if (src && typeof src === 'string' && src.trim() !== "" && src !== "null") {
        return (
            <img
                src={src}
                alt={name}
                className={`rounded-full object-cover border border-slate-200 ${className}`}
                style={{ width: dimension, height: dimension }}
            />
        );
    }
    
    return (
        <div
            className={`rounded-full bg-slate-100 text-slate-400 flex items-center justify-center border border-slate-200 ${className}`}
            style={{ width: dimension, height: dimension }}
            aria-label={name}
        >
            <i className="fa-solid fa-user" style={{ fontSize: size * 0.45 }} />
        </div>
    );
}

export default function TopHeader({
    title     = "Dashboard",
    userName  = "Tenant",
    userRole  = "Tenant",
    userEmail = "",
    avatar    = null,
    isTenant  = false,
}) {
    const [dropdownOpen, setDropdownOpen] = useState(false);
    const [notifOpen, setNotifOpen]       = useState(false);
    
    const [rawNotifications, setRawNotifications] = useState([]);
    
    const [locallyRead, setLocallyRead] = useState(new Set());
    const [dismissed, setDismissed]     = useState(() => readDismissed());
    
    const [loading, setLoading] = useState(false);

    const dropdownRef = useRef(null);
    const notifRef    = useRef(null);

    const displayRole = isTenant ? "Tenant" : "New Tenant";

    // 🛡️ Coerce PHP/MySQL booleans safely
    const isTruthy = (v) => v === true || v === 1 || v === "1" || v === "true";

    // 1. Process notifications
    const processedNotifications = rawNotifications
        .map(n => ({
            ...n,
            isUnread: isTruthy(n.unread) && !locallyRead.has(n.id) && !dismissed.has(n.id)
        }))
        .filter(n => !dismissed.has(n.id))
        .slice(0, MAX_VISIBLE);

    const unreadCount = processedNotifications.filter((n) => n.isUnread).length;

    const fetchNotifications = async () => {
        try {
            setLoading(true);
            const res = await fetch("/tenants/notifications/feed", {
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

    useEffect(() => {
        fetchNotifications();
        const id = setInterval(fetchNotifications, 5000);
        return () => clearInterval(id);
    }, []);

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

    const markAsRead = (id) => {
        setLocallyRead(prev => {
            const next = new Set(prev);
            next.add(id);
            return next;
        });
    };

    const markAllAsRead = () => {
        setLocallyRead(prev => {
            const next = new Set(prev);
            processedNotifications.forEach(n => next.add(n.id));
            return next;
        });
    };

    const clearAll = () => {
        const nextDismissed = new Set(dismissed);
        processedNotifications.forEach((n) => {
            if (!n.isUnread) {
                nextDismissed.add(n.id);
            }
        });
        saveDismissed(nextDismissed);
        setDismissed(nextDismissed);
    };

    const handleClickNotification = (n) => {
        markAsRead(n.id);
        setNotifOpen(false);

        if (n.type === "message" && n.id.startsWith("msg-")) {
            const messageId = n.id.replace("msg-", "");
            fetch("/tenants/notifications/message-read", {
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
    };

    return (
        <header className="bg-white px-4 md:px-8 py-[14px] flex items-center justify-between border-b border-slate-200 w-full sticky top-0 z-[100]">
            <div className="flex items-center gap-3">
                <button
                    type="button"
                    className="md:hidden text-slate-800 text-lg p-1.5 rounded-md hover:bg-slate-100"
                    onClick={() => window.dispatchEvent(new Event("open-mobile-sidebar"))}
                    aria-label="Open menu"
                >
                    <i className="fa-solid fa-bars" />
                </button>
                <h1 className="text-lg md:text-xl font-bold text-slate-800">{title}</h1>
            </div>

            <div className="flex items-center gap-5 md:gap-6">
                {/* Notification Bell */}
                <div className="relative" ref={notifRef}>
                    <button
                        type="button"
                        onClick={() => setNotifOpen(!notifOpen)}
                        className="relative w-9 h-9 flex items-center justify-center rounded-full text-slate-600 hover:bg-slate-100 transition"
                        aria-label="Notifications"
                    >
                        <i className="fa-solid fa-bell text-[16px]" />
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
                                {processedNotifications.length > 0 && (
                                    <div className="flex items-center gap-2">
                                        {unreadCount > 0 && (
                                            <button
                                                type="button"
                                                onClick={markAllAsRead}
                                                className="text-[11px] font-semibold text-slate-500 hover:text-slate-800"
                                            >
                                                Mark all read
                                            </button>
                                        )}
                                        <button
                                            type="button"
                                            onClick={clearAll}
                                            className="text-[11px] font-semibold text-orange-600 hover:text-orange-700"
                                            title="Dismiss read notifications"
                                        >
                                            Clear read
                                        </button>
                                    </div>
                                )}
                            </div>

                            <div className="max-h-[380px] overflow-y-auto">
                                {loading && processedNotifications.length === 0 ? (
                                    <div className="py-12 text-center text-[12px] text-slate-500">
                                        Loading…
                                    </div>
                                ) : processedNotifications.length === 0 ? (
                                    <div className="flex flex-col items-center justify-center py-12 px-4 text-center">
                                        <div className="w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center text-slate-400 text-lg mb-2">
                                            <i className="fa-solid fa-inbox"></i>
                                        </div>
                                        <p className="text-[12px] text-slate-500">
                                            No notifications
                                        </p>
                                    </div>
                                ) : (
                                    processedNotifications.map((n) => (
                                        <a
                                            key={n.id}
                                            href={n.link}
                                            onClick={() => handleClickNotification(n)}
                                            className={`flex items-start gap-3 px-4 py-3 border-b border-slate-50 last:border-b-0 hover:bg-slate-50 transition ${
                                                n.isUnread ? "bg-orange-50/40" : ""
                                            }`}
                                        >
                                            <div className={`w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0 ${n.color}`}>
                                                <i className={`${n.icon} text-[13px]`}></i>
                                            </div>
                                            <div className="flex-1 min-w-0">
                                                <p className={`text-[12.5px] leading-tight ${n.isUnread ? 'font-bold text-slate-900' : 'font-medium text-slate-600'}`}>
                                                    {n.title}
                                                </p>
                                                <p className="text-[11.5px] text-slate-500 mt-0.5 truncate">
                                                    {n.description}
                                                </p>
                                                <p className="text-[10.5px] text-slate-400 mt-1">
                                                    {timeAgo(n.time)}
                                                </p>
                                            </div>
                                            {n.isUnread && (
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
                        className="flex items-center gap-2.5 cursor-pointer select-none"
                        onClick={() => setDropdownOpen(!dropdownOpen)}
                    >
                        <div className="hidden md:block text-right">
                            <h4 className="text-[13px] font-bold text-slate-800 leading-tight">
                                {userName}
                            </h4>
                            <p className="text-[11px] text-slate-500 leading-tight">
                                {displayRole}
                            </p>
                        </div>
                        <UserAvatar src={avatar} name={userName} size={34} />
                        <i className="fa-solid fa-chevron-down text-[10px] text-slate-400" />
                    </div>

                    {dropdownOpen && (
                        <div className="absolute right-0 top-[calc(100%+12px)] w-60 bg-white rounded-2xl border border-slate-100 shadow-xl p-3 z-[1000] animate-in fade-in slide-in-from-top-2 duration-150">
                            <div className="flex items-center gap-3 p-2 pb-3 border-b border-slate-100 mb-1.5">
                                <UserAvatar src={avatar} name={userName} size={38} />
                                <div className="overflow-hidden">
                                    <h4 className="text-[13px] font-bold text-slate-900">
                                        {userName}
                                    </h4>
                                    <p className="text-[11px] text-slate-400 truncate">
                                        {userEmail || `${displayRole.toLowerCase().replace(/\s+/g, "")}@jlmcozycabin.com`}
                                    </p>
                                </div>
                            </div>

                            <a
                                href="/tenants/settings"
                                className="flex items-center gap-3 px-3 py-2.5 rounded-lg text-[13px] font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors"
                            >
                                <i className="fa-solid fa-gear w-[18px] text-center text-sm" />
                                <span>Account Settings</span>
                            </a>

                            <button
                                type="button"
                                onClick={logout}
                                className="flex items-center gap-3 px-3 py-2.5 rounded-lg text-[13px] font-medium text-red-600 hover:bg-red-50 transition-colors w-full text-left"
                            >
                                <i className="fa-solid fa-right-from-bracket w-[18px] text-center text-sm" />
                                <span>Logout</span>
                            </button>
                        </div>
                    )}
                </div>
            </div>
        </header>
    );
}