import React, { useState, useEffect, useRef } from 'react';

export default function TopHeader({
    title = 'Dashboard',
    userName = 'Elle Dela Cruz',
    userRole = 'Tenant',
    avatar = 'https://i.pravatar.cc/100?img=47',
}) {
    const [dropdownOpen, setDropdownOpen] = useState(false);
    const dropdownRef = useRef(null);

    useEffect(() => {
        const handleClickOutside = (e) => {
            if (dropdownRef.current && !dropdownRef.current.contains(e.target)) {
                setDropdownOpen(false);
            }
        };
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    return (
        <header className="bg-white px-4 md:px-8 py-[14px] flex items-center justify-between border-b border-slate-200 sticky top-0 z-[90] flex-shrink-0">
            <div className="flex items-center gap-3">
                <button
                    type="button"
                    className="md:hidden text-slate-800 text-lg p-1.5 rounded-md hover:bg-slate-100"
                    onClick={() => window.dispatchEvent(new Event('open-mobile-sidebar'))}
                    aria-label="Open menu"
                >
                    <i className="fa-solid fa-bars" />
                </button>
                <h1 className="text-lg md:text-xl font-bold text-slate-800">{title}</h1>
            </div>

            <div className="flex items-center gap-5 md:gap-6">
                {/* Search Bar */}
                <div className="hidden md:flex bg-[#f1f5f9] rounded-full px-4 py-2 items-center gap-2.5 w-[220px]">
                    <i className="fa-solid fa-magnifying-glass text-slate-400 text-xs" />
                    <input
                        type="text"
                        placeholder="Search..."
                        className="bg-transparent border-none outline-none text-[13px] w-full text-slate-800 placeholder-slate-400"
                    />
                </div>

                {/* Announcement Notification Bell */}
                <div className="relative cursor-pointer p-1 text-slate-500 hover:text-slate-700">
                    <i className="fa-solid fa-bullhorn text-base" />
                    <span className="absolute top-1 right-1 w-[6px] h-[6px] bg-red-500 rounded-full" />
                </div>

                {/* Profile Dropdown */}
                <div className="relative" ref={dropdownRef}>
                    <div
                        className="flex items-center gap-2.5 cursor-pointer select-none"
                        onClick={() => setDropdownOpen(!dropdownOpen)}
                    >
                        <div className="hidden md:block text-right">
                            <h4 className="text-[13px] font-bold text-slate-800 leading-tight">{userName}</h4>
                            <p className="text-[11px] text-slate-500 leading-tight">{userRole}</p>
                        </div>
                        <img
                            src={avatar}
                            alt={userName}
                            className="w-[34px] h-[34px] rounded-full object-cover"
                        />
                        <i className="fa-solid fa-chevron-down text-[10px] text-slate-400" />
                    </div>

                    {dropdownOpen && (
                        <div className="absolute right-0 top-[calc(100%+12px)] w-60 bg-white rounded-2xl border border-slate-100 shadow-xl p-3 z-[1000] animate-in fade-in slide-in-from-top-2 duration-150">
                            <div className="flex items-center gap-3 p-2 pb-3 border-b border-slate-100 mb-1.5">
                                <img
                                    src={avatar}
                                    alt={userName}
                                    className="w-[38px] h-[38px] rounded-full object-cover"
                                />
                                <div className="overflow-hidden">
                                    <h4 className="text-[13px] font-bold text-slate-900">{userName}</h4>
                                    <p className="text-[11px] text-slate-400 truncate">elledelacruz@gmail.com</p>
                                </div>
                            </div>

                            <a
                                href="/tenants/settings"
                                className="flex items-center gap-3 px-3 py-2.5 rounded-lg text-[13px] font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors"
                            >
                                <i className="fa-solid fa-gear w-[18px] text-center text-sm" />
                                <span>Account Settings</span>
                            </a>

                            <a
                                href="/logout"
                                className="flex items-center gap-3 px-3 py-2.5 rounded-lg text-[13px] font-medium text-red-600 hover:bg-red-50 transition-colors"
                            >
                                <i className="fa-solid fa-right-from-bracket w-[18px] text-center text-sm" />
                                <span>Logout</span>
                            </a>
                        </div>
                    )}
                </div>
            </div>
        </header>
    );
}