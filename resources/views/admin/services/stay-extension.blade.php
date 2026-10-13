@extends('layouts.admin', ['active' => 'services.stay-extension', 'pageTitle' => 'Services'])
@section('title', 'Stay Extension Requests')

@section('content')
<div class="p-4 md:p-8 max-w-[1400px] mx-auto space-y-5">

    {{-- Page Title --}}
    <div class="flex items-center gap-4">
        <div class="w-11 h-11 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center text-lg">
            <i class="fa-regular fa-calendar-plus"></i>
        </div>
        <div>
            <h2 class="text-[22px] font-extrabold text-slate-900">Stay Extension Requests</h2>
            <p class="text-[13px] text-slate-500 mt-0.5">Manage tenant lease extension applications</p>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl px-5 py-4 border border-slate-200 shadow-sm">
            <span class="text-[13px] text-slate-500 font-medium">Total</span>
            <div class="text-[22px] font-bold text-slate-900 mt-2">2</div>
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
            <div class="mt-2"><div class="w-7 h-7 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center text-[13px] font-bold">0</div></div>
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

    {{-- Table --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-slate-50 text-[11px] font-bold text-slate-500 uppercase tracking-wide border-b border-slate-200">
                        <th class="py-3.5 px-5">Resident</th>
                        <th class="py-3.5 px-5">Current Lease End</th>
                        <th class="py-3.5 px-5">Requested End</th>
                        <th class="py-3.5 px-5">Extension</th>
                        <th class="py-3.5 px-5">Status</th>
                        <th class="py-3.5 px-5"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach([
                        ['name' => 'Nicole Custorio', 'room' => 'Room 101', 'current' => 'Mar 22, 2026', 'requested' => 'Mar 26, 2026', 'extension' => '+4 days', 'status' => 'Pending', 'statusClass' => 'bg-yellow-100 text-amber-700'],
                        ['name' => 'Elle Dela Cruz', 'room' => 'Room 102', 'current' => 'Mar 21, 2026', 'requested' => 'Mar 23, 2026', 'extension' => '+2 days', 'status' => 'Approved', 'statusClass' => 'bg-emerald-100 text-emerald-600'],
                    ] as $req)
                    <tr class="border-b border-slate-50 last:border-0">
                        <td class="py-4 px-5">
                            <div class="text-sm font-bold text-slate-900">{{ $req['name'] }}</div>
                            <div class="text-xs text-slate-400 mt-0.5">{{ $req['room'] }}</div>
                        </td>
                        <td class="py-4 px-5">
                            <div class="flex items-center gap-2 text-[13px] text-slate-700">
                                <i class="fa-regular fa-calendar text-slate-400 text-[13px]"></i>
                                {{ $req['current'] }}
                            </div>
                        </td>
                        <td class="py-4 px-5">
                            <div class="flex items-center gap-2 text-[13px] text-purple-700 font-semibold">
                                <i class="fa-regular fa-calendar text-purple-600 text-[13px]"></i>
                                {{ $req['requested'] }}
                            </div>
                        </td>
                        <td class="py-4 px-5">
                            <span class="inline-block bg-purple-100 text-purple-700 text-[11px] font-bold px-2.5 py-1 rounded-xl">{{ $req['extension'] }}</span>
                        </td>
                        <td class="py-4 px-5">
                            <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold {{ $req['statusClass'] }}">{{ $req['status'] }}</span>
                        </td>
                        <td class="py-4 px-5">
                            <div class="flex items-center justify-end gap-3 text-slate-400 text-sm">
                                @if($req['status'] === 'Pending')
                                <i class="fa-regular fa-circle-check text-emerald-500 cursor-pointer hover:text-emerald-600"></i>
                                <i class="fa-regular fa-circle-xmark text-rose-500 cursor-pointer hover:text-rose-600"></i>
                                @endif
                                <i class="fa-regular fa-eye cursor-pointer hover:text-slate-700"></i>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection