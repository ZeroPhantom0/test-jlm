@extends('layouts.admin', ['active' => 'services.room-change', 'pageTitle' => 'Services'])
@section('title', 'Room Change Requests')

@section('content')
<div class="p-4 md:p-8 max-w-[1400px] mx-auto space-y-5">

    {{-- Page Title --}}
    <div class="flex items-center gap-4">
        <div class="w-11 h-11 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center text-lg">
            <i class="fa-solid fa-house"></i>
        </div>
        <div>
            <h2 class="text-[22px] font-extrabold text-slate-900">Room Change Requests</h2>
            <p class="text-[13px] text-slate-500 mt-0.5">Review and manage tenant room transfer requests</p>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl px-5 py-4 border border-slate-200 shadow-sm">
            <span class="text-[13px] text-slate-500 font-medium">Total</span>
            <div class="text-[22px] font-bold text-slate-900 mt-2">3</div>
        </div>
        <div class="bg-white rounded-2xl px-5 py-4 border border-slate-200 shadow-sm">
            <span class="text-[13px] text-slate-500 font-medium">Pending</span>
            <div class="mt-2"><div class="w-7 h-7 rounded-full bg-yellow-100 text-amber-700 flex items-center justify-center text-[13px] font-bold">1</div></div>
        </div>
        <div class="bg-white rounded-2xl px-5 py-4 border border-slate-200 shadow-sm">
            <span class="text-[13px] text-slate-500 font-medium">Approved</span>
            <div class="mt-2"><div class="w-7 h-7 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-[13px] font-bold">1</div></div>
        </div>
        <div class="bg-white rounded-2xl px-5 py-4 border border-slate-200 shadow-sm">
            <span class="text-[13px] text-slate-500 font-medium">Rejected</span>
            <div class="mt-2"><div class="w-7 h-7 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center text-[13px] font-bold">1</div></div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 flex flex-col md:flex-row gap-4">
        <div class="flex-1 bg-slate-50 border border-slate-200 rounded-[10px] px-4 py-2.5 flex items-center gap-2.5">
            <i class="fa-solid fa-magnifying-glass text-slate-400 text-sm"></i>
            <input type="text" placeholder="Search by tenant, room..." class="w-full text-[13.5px] outline-none bg-transparent">
        </div>
        <select class="bg-white border border-slate-200 rounded-[10px] px-4 py-2.5 text-[13.5px] font-medium text-slate-700 outline-none cursor-pointer min-w-[160px]">
            <option>All Statuses</option>
            <option>Pending</option>
            <option>Approved</option>
            <option>Rejected</option>
        </select>
    </div>

    {{-- Cards Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

        {{-- Pending --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-5 flex flex-col gap-4">
            <div class="flex justify-between items-start">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Nicole Custorio</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Mar 22, 2026</p>
                </div>
                <span class="inline-flex items-center gap-1.5 bg-yellow-100 text-amber-700 text-xs font-semibold px-3 py-1 rounded-full">
                    <i class="fa-regular fa-clock"></i> Pending
                </span>
            </div>

            <div class="bg-slate-50 rounded-xl p-4 flex flex-col gap-2.5">
                <div class="flex items-center justify-center gap-4">
                    <div class="flex-1 bg-white border border-slate-200 rounded-[10px] py-2.5 text-center">
                        <div class="text-[11px] text-slate-400">Current Room</div>
                        <div class="text-xl font-extrabold text-slate-800 mt-1">101</div>
                    </div>
                    <i class="fa-solid fa-arrow-right text-slate-400 text-sm"></i>
                    <div class="flex-1 bg-white border border-slate-200 rounded-[10px] py-2.5 text-center">
                        <div class="text-[11px] text-slate-400">Requested Room</div>
                        <div class="text-xl font-extrabold text-indigo-700 mt-1">301</div>
                    </div>
                </div>
                <span class="self-end text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-100 text-emerald-600 tracking-wide">AVAILABLE</span>
            </div>

            <p class="text-[13.5px] text-slate-600">Need more space for family.</p>

            <div class="flex gap-2.5">
                <button class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-xs font-semibold inline-flex items-center gap-1.5">
                    <i class="fa-regular fa-circle-check"></i> Approve
                </button>
                <button class="bg-rose-50 text-rose-600 px-4 py-2 rounded-lg text-xs font-semibold inline-flex items-center gap-1.5">
                    <i class="fa-regular fa-circle-xmark"></i> Reject
                </button>
                <button class="bg-slate-100 text-slate-600 px-4 py-2 rounded-lg text-xs font-semibold inline-flex items-center gap-1.5">
                    <i class="fa-regular fa-eye"></i> Details
                </button>
            </div>
        </div>

        {{-- Approved --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-5 flex flex-col gap-4">
            <div class="flex justify-between items-start">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Elle Dela Cruz</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Mar 18, 2026</p>
                </div>
                <span class="inline-flex items-center gap-1.5 bg-emerald-100 text-emerald-600 text-xs font-semibold px-3 py-1 rounded-full">
                    <i class="fa-regular fa-circle-check"></i> Approved
                </span>
            </div>

            <div class="bg-slate-50 rounded-xl p-4 flex flex-col gap-2.5">
                <div class="flex items-center justify-center gap-4">
                    <div class="flex-1 bg-white border border-slate-200 rounded-[10px] py-2.5 text-center">
                        <div class="text-[11px] text-slate-400">Current Room</div>
                        <div class="text-xl font-extrabold text-slate-800 mt-1">102</div>
                    </div>
                    <i class="fa-solid fa-arrow-right text-slate-400 text-sm"></i>
                    <div class="flex-1 bg-white border border-slate-200 rounded-[10px] py-2.5 text-center">
                        <div class="text-[11px] text-slate-400">Requested Room</div>
                        <div class="text-xl font-extrabold text-indigo-700 mt-1">204</div>
                    </div>
                </div>
                <span class="self-end text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-100 text-emerald-600 tracking-wide">AVAILABLE</span>
            </div>

            <p class="text-[13.5px] text-slate-600">Prefer higher floor for better view.</p>

            <div class="bg-blue-50 rounded-lg px-3.5 py-2.5 text-xs text-blue-700 font-medium">
                Approved. Move date: end of month.
            </div>

            <button class="bg-slate-100 text-slate-600 px-4 py-2 rounded-lg text-xs font-semibold inline-flex items-center justify-center gap-1.5">
                <i class="fa-regular fa-eye"></i> Details
            </button>
        </div>
    </div>
</div>
@endsection