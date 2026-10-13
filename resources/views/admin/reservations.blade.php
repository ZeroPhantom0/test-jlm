@extends('layouts.admin', ['active' => 'reservations', 'pageTitle' => 'Reservations'])
@section('title', 'Reservations')

@section('content')
<div class="p-4 md:p-8 max-w-[1400px] mx-auto space-y-6">

    {{-- Page Header --}}
    <div class="flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-teal-100 text-teal-600 flex items-center justify-center text-xl">
            <i class="fa-regular fa-calendar-check"></i>
        </div>
        <div>
            <h1 class="text-[22px] font-extrabold text-slate-900">Manage Reservations</h1>
            <p class="text-[13px] text-slate-500 mt-0.5">Review and approve tenant reservation requests</p>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-white rounded-2xl px-5 py-4 border border-slate-100 shadow-sm">
            <div class="text-[13px] font-semibold text-slate-500">Total</div>
            <div class="text-[26px] font-extrabold text-slate-900 mt-2">5</div>
        </div>
        <div class="bg-white rounded-2xl px-5 py-4 border border-slate-100 shadow-sm">
            <div class="text-[13px] font-semibold text-slate-500">Pending</div>
            <div class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-yellow-100 text-amber-700 text-base font-extrabold mt-2">2</div>
        </div>
        <div class="bg-white rounded-2xl px-5 py-4 border border-slate-100 shadow-sm">
            <div class="text-[13px] font-semibold text-slate-500">Approved</div>
            <div class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-green-100 text-green-600 text-base font-extrabold mt-2">1</div>
        </div>
        <div class="bg-white rounded-2xl px-5 py-4 border border-slate-100 shadow-sm">
            <div class="text-[13px] font-semibold text-slate-500">Rejected</div>
            <div class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-rose-100 text-rose-600 text-base font-extrabold mt-2">1</div>
        </div>
        <div class="bg-white rounded-2xl px-5 py-4 border border-slate-100 shadow-sm">
            <div class="text-[13px] font-semibold text-slate-500">Expired</div>
            <div class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-slate-100 text-slate-500 text-base font-extrabold mt-2">1</div>
        </div>
    </div>

    {{-- Toolbar --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm px-6 py-4 flex flex-col md:flex-row justify-between gap-4">
        <div class="flex-1 bg-slate-50 border border-slate-200 rounded-[10px] px-4 py-2.5 flex items-center gap-2.5">
            <i class="fa-solid fa-magnifying-glass text-slate-400 text-sm"></i>
            <input type="text" placeholder="Search by name, email, or room..." class="w-full text-[13.5px] outline-none bg-transparent">
        </div>
        <div class="flex items-center gap-3">
            <select class="bg-white border border-slate-200 rounded-[10px] px-4 py-2.5 text-[13.5px] text-slate-700 outline-none cursor-pointer min-w-[160px]">
                <option>All Statuses</option>
                <option>Pending</option>
                <option>Approved</option>
                <option>Rejected</option>
                <option>Expired</option>
            </select>
            <div class="flex bg-slate-100 rounded-[10px] p-1">
                <button class="view-btn active bg-white text-slate-900 shadow-sm px-3.5 py-2 rounded-lg text-[13px] font-semibold inline-flex items-center gap-1.5">
                    <i class="fa-solid fa-border-all"></i> Grid
                </button>
                <button class="view-btn text-slate-500 px-3.5 py-2 rounded-lg text-[13px] font-semibold inline-flex items-center gap-1.5">
                    <i class="fa-solid fa-list"></i> List
                </button>
            </div>
        </div>
    </div>

    {{-- Cards Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

        {{-- Card 1 --}}
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 flex flex-col justify-between gap-4">
            <div>
                <div class="flex justify-between items-start mb-4">
                    <div class="flex items-center gap-3.5">
                        <div class="w-11 h-11 rounded-full bg-teal-100 text-teal-600 flex items-center justify-center text-[18px] font-bold">M</div>
                        <div>
                            <h3 class="text-[15px] font-bold text-slate-900">Maria Santos</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Mar 23, 2026</p>
                        </div>
                    </div>
                    <span class="bg-amber-100 text-amber-700 text-xs font-bold px-3.5 py-1.5 rounded-full inline-flex items-center gap-1.5">
                        <i class="fa-regular fa-clock"></i> Pending
                    </span>
                </div>

                <div class="flex flex-col gap-2 mb-5">
                    <div class="flex items-center gap-2.5 text-[13px] text-slate-600">
                        <i class="fa-regular fa-envelope w-4 text-slate-400"></i>
                        <span>maria@email.com</span>
                    </div>
                    <div class="flex items-center gap-2.5 text-[13px] text-slate-600">
                        <i class="fa-solid fa-phone w-4 text-slate-400"></i>
                        <span>09171112222</span>
                    </div>
                    <div class="flex items-center gap-2.5 text-[13px] text-slate-600">
                        <i class="fa-solid fa-house w-4 text-slate-400"></i>
                        <span>Room 302 – Bedspace</span>
                    </div>
                    <div class="flex items-center gap-2.5 text-[13px] text-slate-600">
                        <i class="fa-regular fa-calendar w-4 text-slate-400"></i>
                        <span>Check-in: Apr 1, 2026</span>
                    </div>
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between text-[13px] mb-4">
                    <span class="text-slate-500">Reservation Fee:</span>
                    <span class="font-extrabold text-emerald-600 text-[15px]">₱500</span>
                </div>
                <div class="flex items-center gap-2.5">
                    <button class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2.5 rounded-lg text-[13px] font-semibold inline-flex items-center gap-1.5">
                        <i class="fa-regular fa-circle-check"></i> Approve
                    </button>
                    <button class="bg-rose-50 text-rose-600 px-4 py-2.5 rounded-lg text-[13px] font-semibold inline-flex items-center gap-1.5">
                        <i class="fa-regular fa-circle-xmark"></i> Reject
                    </button>
                    <button class="bg-slate-100 text-slate-600 px-4 py-2.5 rounded-lg text-[13px] font-semibold inline-flex items-center gap-1.5 flex-1 justify-center">
                        <i class="fa-regular fa-eye"></i> Details
                    </button>
                </div>
            </div>
        </div>

        {{-- Card 2 --}}
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 flex flex-col justify-between gap-4">
            <div>
                <div class="flex justify-between items-start mb-4">
                    <div class="flex items-center gap-3.5">
                        <div class="w-11 h-11 rounded-full bg-sky-100 text-sky-600 flex items-center justify-center text-[18px] font-bold">R</div>
                        <div>
                            <h3 class="text-[15px] font-bold text-slate-900">Ramon Bautista</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Mar 20, 2026</p>
                        </div>
                    </div>
                    <span class="bg-green-100 text-green-600 text-xs font-bold px-3.5 py-1.5 rounded-full inline-flex items-center gap-1.5">
                        <i class="fa-regular fa-circle-check"></i> Approved
                    </span>
                </div>

                <div class="flex flex-col gap-2 mb-5">
                    <div class="flex items-center gap-2.5 text-[13px] text-slate-600">
                        <i class="fa-regular fa-envelope w-4 text-slate-400"></i>
                        <span>ramon@email.com</span>
                    </div>
                    <div class="flex items-center gap-2.5 text-[13px] text-slate-600">
                        <i class="fa-solid fa-phone w-4 text-slate-400"></i>
                        <span>09182223333</span>
                    </div>
                    <div class="flex items-center gap-2.5 text-[13px] text-slate-600">
                        <i class="fa-solid fa-house w-4 text-slate-400"></i>
                        <span>Room 204 – Barkada Room</span>
                    </div>
                    <div class="flex items-center gap-2.5 text-[13px] text-slate-600">
                        <i class="fa-regular fa-calendar w-4 text-slate-400"></i>
                        <span>Check-in: Apr 8, 2026</span>
                    </div>
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between text-[13px] mb-4">
                    <span class="text-slate-500">Reservation Fee:</span>
                    <span class="font-extrabold text-emerald-600 text-[15px]">₱500</span>
                </div>
                <div class="bg-sky-50 border border-sky-100 text-sky-600 text-[12.5px] font-medium rounded-[10px] px-4 py-2.5 mb-3">
                    Approved. ₱9,000/mo. Deposit confirmed.
                </div>
                <button class="w-full bg-slate-100 text-slate-600 px-4 py-2.5 rounded-lg text-[13px] font-semibold inline-flex items-center justify-center gap-1.5">
                    <i class="fa-regular fa-eye"></i> Details
                </button>
            </div>
        </div>
    </div>
</div>
@endsection