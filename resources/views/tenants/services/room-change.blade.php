@extends('layouts.tenant', ['active' => 'services.room-change', 'pageTitle' => 'Services'])
@section('title', 'Room Change Requests')

@section('content')
<div class="p-4 md:p-8 max-w-6xl">

    {{-- Page Title --}}
    <div class="flex items-center gap-4 mb-6">
        <div class="w-11 h-11 rounded-xl bg-blue-100 text-blue-500 flex items-center justify-center text-lg">
            <i class="fa-regular fa-building"></i>
        </div>
        <div>
            <h1 class="text-xl font-bold text-slate-900">Room Change Requests</h1>
            <p class="text-[13px] text-slate-500 mt-0.5">Request to change your current room</p>
        </div>
    </div>

    {{-- Info Banner --}}
    <div class="bg-blue-50 rounded-xl px-5 py-3.5 flex items-center gap-2.5 text-blue-600 text-[13.5px] mb-6">
        <i class="fa-regular fa-circle-info"></i>
        <span><strong>Note:</strong> You can cancel a request while it is <strong>Pending</strong>. Once approved, it becomes view-only.</span>
    </div>

    {{-- Card --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">

        <div class="flex justify-between items-center mb-5">
            <span class="text-[13.5px] text-slate-500">1 request(s)</span>
            <button id="open-new-room-change"
                    class="bg-[#001838] hover:bg-[#0d2347] text-white px-4 py-2.5 rounded-lg font-medium text-[13px] inline-flex items-center gap-2">
                <i class="fa-solid fa-plus"></i> New Request
            </button>
        </div>

        {{-- Request Row --}}
        <div class="bg-slate-50 border border-slate-200 rounded-xl p-5 relative">
            <div class="flex items-center gap-2 mb-2.5">
                <div class="text-[15px] font-bold text-slate-900">
                    R101-B1-U <span class="text-slate-400 font-normal mx-0.5">&rarr;</span>
                    <span class="text-emerald-600">R301-B1-U</span>
                </div>
                <span class="bg-amber-100 text-amber-700 text-[11px] font-semibold px-2 py-0.5 rounded-full inline-flex items-center gap-1">
                    <i class="fa-regular fa-clock"></i> Pending
                </span>
            </div>

            <div class="absolute right-5 top-4 flex gap-3 text-slate-400 text-sm">
                <i class="fa-regular fa-eye cursor-pointer hover:text-slate-600 view-room-change-details-btn"
                   data-request-id="1"
                   title="View Details"></i>
                <i class="fa-regular fa-circle-xmark cursor-pointer hover:text-slate-600"
                   title="Cancel Request"></i>
            </div>

            <div class="text-slate-600 text-[13.5px] mb-3">Need more space for family.</div>
            <div class="flex gap-4 text-xs text-slate-400">
                <span class="flex items-center gap-1.5"><i class="fa-regular fa-calendar"></i> Mar 21, 2026</span>
                <span>Availability: Available</span>
            </div>
        </div>
    </div>
</div>

{{-- React Modal Mount Point with data --}}
<div id="services-room-change-root"
     data-current-room="R101-B1-U"
     data-requests="{{ json_encode([
        1 => [
            'id'             => 1,
            'ref'            => 'rc-1',
            'current_room'   => 'R101-B1-U',
            'requested_room' => 'R301-B1-U',
            'submitted'      => 'Mar 21, 2026',
            'availability'   => 'Available',
            'reason'         => 'Need more space for family.',
            'status'         => 'Pending',
        ],
     ], JSON_HEX_APOS | JSON_HEX_QUOT) }}"></div>
@endsection