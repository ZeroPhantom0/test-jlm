// resources/js/components/AdminTopHeader.jsx
import React, { useState, useEffect, useRef } from 'react';

export default function AdminTopHeader({
    title = 'Dashboard',
    userName = 'Leah Administrator',
    userRole = 'Admin',
    userEmail = 'leah.admin@jlmcozycabin.com',
    avatar = 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&q=80&w=120',
    showHamburger = false,
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
        <header className="bg-white px-4 md:px-8 py-3.5 flex items-center justify-between
                           border-b border-slate-200 sticky top-0 z-[100]">
            <div className="flex items-center gap-4">
                {showHamburger && (
                    <button
                        className="md:hidden text-slate-700 text-lg p-1"
                        onClick={() => window.dispatchEvent(new Event('open-mobile-sidebar'))}
                        aria-label="Open menu"
                    >
                        <i className="fa-solid fa-bars"></i>
                    </button>
                )}
                <h1 className="text-[20px] md:text-[22px] font-bold text-slate-900">{title}</h1>
            </div>

            <div className="flex items-center gap-5 relative">
                {/* Search Bar */}
                <div className="hidden md:flex bg-slate-100 rounded-full px-4 py-2 items-center gap-2.5 w-[240px]">
                    <i className="fa-solid fa-magnifying-glass text-slate-400 text-[13px]"></i>
                    <input
                        type="text"
                        placeholder="Search..."
                        className="bg-transparent border-none outline-none text-[13px] w-full text-slate-800 placeholder-slate-400"
                    />
                </div>

                {/* Notification Bell */}
                <div className="relative cursor-pointer">
                    <i className="fa-solid fa-bell text-rose-600 text-lg"></i>
                    <span className="absolute -top-0.5 -right-0.5 w-[7px] h-[7px] bg-rose-600 border border-white rounded-full"></span>
                </div>

                <div className="hidden md:block w-px h-6 bg-slate-300"></div>

                {/* Profile Dropdown */}
                <div className="relative" ref={dropdownRef}>
                    <div
                        className="flex items-center gap-2.5 cursor-pointer select-none px-2 py-1 rounded-lg transition hover:bg-slate-100"
                        onClick={() => setDropdownOpen(!dropdownOpen)}
                    >
                        <div className="hidden md:block text-right">
                            <h4 className="text-[13px] font-bold text-slate-900 leading-tight">{userName}</h4>
                            <p className="text-[11px] text-slate-500">{userRole}</p>
                        </div>
                        <img
                            src={avatar}
                            alt={userName}
                            className="w-9 h-9 rounded-full object-cover"
                        />
                        <i className={`fa-solid fa-chevron-down text-[11px] text-slate-500 transition-transform ${dropdownOpen ? 'rotate-180' : ''}`}></i>
                    </div>

                    {dropdownOpen && (
                        <div className="absolute right-0 mt-3 w-[270px] bg-white rounded-2xl
                                        border border-slate-200 shadow-xl z-[2000] overflow-hidden
                                        animate-[fadeIn_0.2s_ease-out]">
                            <div className="p-4 bg-slate-50 border-b border-slate-100 flex items-center gap-3">
                                <img
                                    src={avatar}
                                    alt={userName}
                                    className="w-[42px] h-[42px] rounded-full object-cover"
                                />
                                <div className="overflow-hidden">
                                    <h4 className="text-[13.5px] font-bold text-slate-900 leading-tight">{userName}</h4>
                                    <p className="text-[11px] text-slate-500 mt-0.5 truncate">{userEmail}</p>
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
                                    <i className="fa-solid fa-sliders w-4 text-center text-slate-500"></i>
                                    <span>System Preferences</span>
                                </a>
                                <a href="#" className="flex items-center gap-3 px-3 py-2.5 rounded-lg text-[13px] font-medium text-slate-700 hover:bg-slate-100 transition">
                                    <i className="fa-solid fa-user-clock w-4 text-center text-slate-500"></i>
                                    <span>My Activity Log</span>
                                </a>
                                <a href="/logout" className="flex items-center gap-3 px-3 py-2.5 rounded-lg text-[13px] font-medium text-rose-600 hover:bg-rose-50 transition border-t border-slate-100 mt-1 rounded-b-lg">
                                    <i className="fa-solid fa-arrow-right-from-bracket w-4 text-center text-rose-600"></i>
                                    <span>Logout</span>
                                </a>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </header>
    );
}