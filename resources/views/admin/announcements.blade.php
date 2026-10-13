@extends('layouts.admin', ['active' => 'announcements', 'pageTitle' => 'Announcements'])
@section('title', 'Announcements')

@section('content')
<div class="p-4 md:p-8 max-w-[1400px] mx-auto space-y-5">

    {{-- Banner --}}
    <div class="flex flex-col md:flex-row justify-between md:items-center gap-4">
        <div class="flex items-center gap-4">
            <div class="w-13 h-13 rounded-xl bg-yellow-100 text-amber-600 flex items-center justify-center text-[22px]">
                <i class="fa-solid fa-bullhorn"></i>
            </div>
            <div>
                <h2 class="text-2xl font-extrabold text-slate-900">Post Announcements</h2>
                <p class="text-sm text-slate-500 mt-0.5">Broadcast messages to tenants</p>
            </div>
        </div>
        <button id="admin-open-new-ann" class="bg-[#0b1930] hover:bg-[#162640] text-white px-5 py-2.5 rounded-[10px] text-sm font-semibold inline-flex items-center gap-2 transition shadow-sm">
            <i class="fa-solid fa-plus"></i> New Announcement
        </button>
    </div>

    {{-- Metrics --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl px-5 py-4 border border-slate-100 shadow-sm">
            <div class="text-[13.5px] font-semibold text-slate-500">Total</div>
            <div class="text-2xl font-extrabold text-slate-900 mt-1.5">4</div>
        </div>
        <div class="bg-white rounded-2xl px-5 py-4 border border-slate-100 shadow-sm">
            <div class="text-[13.5px] font-semibold text-slate-500">Pinned</div>
            <span class="inline-flex items-center justify-center bg-amber-100 text-amber-700 text-sm font-bold rounded-full px-2 h-7 min-w-7 mt-1.5">2</span>
        </div>
        <div class="bg-white rounded-2xl px-5 py-4 border border-slate-100 shadow-sm">
            <div class="text-[13.5px] font-semibold text-slate-500">Important</div>
            <span class="inline-flex items-center justify-center bg-amber-100 text-amber-700 text-sm font-bold rounded-full px-2 h-7 min-w-7 mt-1.5">2</span>
        </div>
        <div class="bg-white rounded-2xl px-5 py-4 border border-slate-100 shadow-sm">
            <div class="text-[13.5px] font-semibold text-slate-500">Urgent</div>
            <span class="inline-flex items-center justify-center bg-rose-100 text-rose-600 text-sm font-bold rounded-full px-2 h-7 min-w-7 mt-1.5">0</span>
        </div>
    </div>

    {{-- Filter --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 flex flex-col md:flex-row items-center gap-4">
        <div class="flex-1 w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-2.5 flex items-center gap-2.5">
            <i class="fa-solid fa-magnifying-glass text-slate-400 text-sm"></i>
            <input type="text" placeholder="Search by title or keyword..." class="w-full text-[13.5px] outline-none bg-transparent">
        </div>
        <select class="bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-[13.5px] font-medium text-slate-700 outline-none cursor-pointer w-full md:w-[180px]">
            <option>All Priorities</option>
            <option>Urgent</option>
            <option>Important</option>
            <option>Normal</option>
        </select>
    </div>

    {{-- List --}}
    <div class="flex flex-col gap-4">

        {{-- Card 1 --}}
        <div class="bg-white border-[1.5px] border-amber-300 rounded-2xl p-5 flex gap-4">
            <div class="w-11 h-11 rounded-xl bg-yellow-100 text-amber-600 flex items-center justify-center text-lg font-bold shrink-0">
                <i class="fa-solid fa-exclamation"></i>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex flex-wrap justify-between items-center gap-3 mb-2.5">
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <span class="text-lg font-bold text-slate-900">Water Maintenance Scheduled</span>
                        <span class="inline-flex items-center gap-1.5 bg-yellow-100 text-amber-700 text-[11px] font-bold px-2 py-0.5 rounded-xl uppercase">
                            <i class="fa-solid fa-thumbtack text-[10px]"></i> Pinned
                        </span>
                    </div>
                    <div class="flex items-center gap-3.5 text-slate-400">
                        <button class="hover:text-slate-600 transition toggle-pin-btn"><i class="fa-solid fa-map-pin text-[15px]"></i></button>
                        <button class="hover:text-slate-600 transition"><i class="fa-regular fa-pen-to-square text-[15px]"></i></button>
                        <button class="hover:text-rose-500 transition"><i class="fa-regular fa-trash-can text-[15px]"></i></button>
                    </div>
                </div>
                <div class="text-sm text-slate-600 leading-relaxed mb-2.5">
                    Water supply will be interrupted on Saturday, March 25 from 8 AM to 12 PM for routine maintenance. Please store water in advance.
                </div>
                <div class="flex items-center gap-4 text-[13px]">
                    <span class="inline-flex items-center gap-1.5 bg-orange-100 text-orange-600 text-xs font-semibold px-3 py-1 rounded-[14px]">
                        <i class="fa-solid fa-circle-info text-[10px]"></i> Important
                    </span>
                    <span class="text-slate-400 font-medium">By Leah Administrator</span>
                    <span class="text-slate-400">Mar 24, 2026</span>
                </div>
            </div>
        </div>

        {{-- Card 2 --}}
        <div class="bg-white border-[1.5px] border-amber-300 rounded-2xl p-5 flex gap-4">
            <div class="w-11 h-11 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center text-lg font-bold shrink-0">
                <i class="fa-solid fa-circle-info"></i>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex flex-wrap justify-between items-center gap-3 mb-2.5">
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <span class="text-lg font-bold text-slate-900">Quiet Hours Reminder</span>
                        <span class="inline-flex items-center gap-1.5 bg-yellow-100 text-amber-700 text-[11px] font-bold px-2 py-0.5 rounded-xl uppercase">
                            <i class="fa-solid fa-thumbtack text-[10px]"></i> Pinned
                        </span>
                    </div>
                    <div class="flex items-center gap-3.5 text-slate-400">
                        <button class="hover:text-slate-600 transition toggle-pin-btn"><i class="fa-solid fa-map-pin text-[15px]"></i></button>
                        <button class="hover:text-slate-600 transition"><i class="fa-regular fa-pen-to-square text-[15px]"></i></button>
                        <button class="hover:text-rose-500 transition"><i class="fa-regular fa-trash-can text-[15px]"></i></button>
                    </div>
                </div>
                <div class="text-sm text-slate-600 leading-relaxed mb-2.5">
                    Please observe quiet hours between 10 PM and 6 AM on weekdays to respect fellow residents.
                </div>
                <div class="flex items-center gap-4 text-[13px]">
                    <span class="inline-flex items-center gap-1.5 bg-slate-100 text-slate-600 text-xs font-semibold px-3 py-1 rounded-[14px]">
                        <i class="fa-solid fa-circle-info text-[10px]"></i> Normal
                    </span>
                    <span class="text-slate-400 font-medium">By Leah Administrator</span>
                    <span class="text-slate-400">Mar 20, 2026</span>
                </div>
            </div>
        </div>

        {{-- Card 3 --}}
        <div class="bg-white border border-slate-200 rounded-2xl p-5 flex gap-4">
            <div class="w-11 h-11 rounded-xl bg-yellow-100 text-amber-600 flex items-center justify-center text-lg font-bold shrink-0">
                <i class="fa-solid fa-exclamation"></i>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex flex-wrap justify-between items-center gap-3 mb-2.5">
                    <span class="text-lg font-bold text-slate-900">Monthly Rent Due Reminder</span>
                    <div class="flex items-center gap-3.5 text-slate-400">
                        <button class="hover:text-slate-600 transition toggle-pin-btn"><i class="fa-solid fa-thumbtack text-[15px]"></i></button>
                        <button class="hover:text-slate-600 transition"><i class="fa-regular fa-pen-to-square text-[15px]"></i></button>
                        <button class="hover:text-rose-500 transition"><i class="fa-regular fa-trash-can text-[15px]"></i></button>
                    </div>
                </div>
                <div class="text-sm text-slate-600 leading-relaxed mb-2.5">
                    Rent for April is due by April 5. Please pay on time via GCash or cash at the office to avoid late fees.
                </div>
                <div class="flex items-center gap-4 text-[13px]">
                    <span class="inline-flex items-center gap-1.5 bg-orange-100 text-orange-600 text-xs font-semibold px-3 py-1 rounded-[14px]">
                        <i class="fa-solid fa-circle-info text-[10px]"></i> Important
                    </span>
                    <span class="text-slate-400 font-medium">By Leah Administrator</span>
                    <span class="text-slate-400">Mar 18, 2026</span>
                </div>
            </div>
        </div>

        {{-- Card 4 --}}
        <div class="bg-white border border-slate-200 rounded-2xl p-5 flex gap-4">
            <div class="w-11 h-11 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center text-lg font-bold shrink-0">
                <i class="fa-solid fa-circle-info"></i>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex flex-wrap justify-between items-center gap-3 mb-2.5">
                    <span class="text-lg font-bold text-slate-900">Common Area Cleaning Schedule</span>
                    <div class="flex items-center gap-3.5 text-slate-400">
                        <button class="hover:text-slate-600 transition toggle-pin-btn"><i class="fa-solid fa-thumbtack text-[15px]"></i></button>
                        <button class="hover:text-slate-600 transition"><i class="fa-regular fa-pen-to-square text-[15px]"></i></button>
                        <button class="hover:text-rose-500 transition"><i class="fa-regular fa-trash-can text-[15px]"></i></button>
                    </div>
                </div>
                <div class="text-sm text-slate-600 leading-relaxed mb-2.5">
                    Deep cleaning of the hallway and kitchen areas will take place every Wednesday morning between 9 AM and 11 AM.
                </div>
                <div class="flex items-center gap-4 text-[13px]">
                    <span class="inline-flex items-center gap-1.5 bg-slate-100 text-slate-600 text-xs font-semibold px-3 py-1 rounded-[14px]">
                        <i class="fa-solid fa-circle-info text-[10px]"></i> Normal
                    </span>
                    <span class="text-slate-400 font-medium">By Leah Administrator</span>
                    <span class="text-slate-400">Mar 15, 2026</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="admin-announcements-modals"></div>
@endsection