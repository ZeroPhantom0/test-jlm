@extends('layouts.tenant', ['active' => 'services.maintenance', 'pageTitle' => 'Services'])
@section('title', 'Maintenance Requests')

@section('content')
<div class="p-4 md:p-8 max-w-6xl">

    {{-- Page Title --}}
    <div class="flex items-center gap-4 mb-6">
        <div class="w-11 h-11 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center text-lg">
            <i class="fa-solid fa-wrench"></i>
        </div>
        <div>
            <h2 class="text-xl font-bold text-slate-900">Maintenance Requests</h2>
            <p class="text-[13px] text-slate-500 mt-0.5">Submit and track maintenance issues</p>
        </div>
    </div>

    {{-- Info banner --}}
    <div class="bg-blue-50 text-blue-600 rounded-lg px-5 py-3 flex items-center gap-2.5 mb-5 text-[13px]">
        <i class="fa-regular fa-circle-info"></i>
        <div><strong>Note:</strong> You can cancel a request while it is <strong>Pending</strong>. Once approved or in progress, it becomes view-only.</div>
    </div>

    {{-- Table card --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
        <div class="flex justify-between items-center mb-5">
            <span class="text-[13px] text-slate-500">2 request(s)</span>
            <button id="open-new-request" class="bg-[#02122c] hover:bg-[#0d2347] text-white px-4 py-2.5 rounded-lg text-[13px] font-semibold inline-flex items-center gap-2">
                <i class="fa-solid fa-plus"></i> New Request
            </button>
        </div>

        <table class="w-full text-left">
            <thead>
                <tr class="text-xs font-semibold text-slate-600 border-b border-slate-100">
                    <th class="py-3 px-2 w-[35%]">Request</th>
                    <th class="py-3 px-2 w-[20%]">Assigned Staff</th>
                    <th class="py-3 px-2 w-[15%]">Status</th>
                    <th class="py-3 px-2 w-[18%]">Date</th>
                    <th class="py-3 px-2 w-[12%]">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach([
                    ['title' => 'Leaky Faucet', 'desc' => 'Kitchen sink is dripping constantly.'],
                    ['title' => 'Broken Window Lock', 'desc' => 'Bedroom window lock is stuck.'],
                ] as $r)
                <tr class="border-b border-slate-50 last:border-0">
                    <td class="py-4 px-2">
                        <div class="font-semibold text-slate-800 text-[13px]">{{ $r['title'] }}</div>
                        <div class="text-xs text-slate-400 mt-0.5">{{ $r['desc'] }}</div>
                    </td>
                    <td class="py-4 px-2 text-slate-400">—</td>
                    <td class="py-4 px-2">
                        <span class="bg-amber-100 text-amber-700 text-[11px] font-semibold px-2.5 py-1 rounded-full inline-flex items-center gap-1">
                            <i class="fa-regular fa-clock"></i> Pending
                        </span>
                    </td>
                    <td class="py-4 px-2 text-slate-600 text-[13px]">Mar 20, 2026</td>
                    <td class="py-4 px-2">
                        <div class="flex items-center gap-3 text-slate-400">
                            <i class="fa-regular fa-eye cursor-pointer hover:text-slate-600"></i>
                            <i class="fa-regular fa-circle-xmark cursor-pointer hover:text-slate-600"></i>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Modals mounted by React --}}
<div id="services-maintenance-root"></div>
@endsection