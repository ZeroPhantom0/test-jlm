@extends('layouts.tenant', ['active' => 'services.room-change', 'pageTitle' => 'Services'])
@section('title', 'Room Change Requests')

@section('content')
@php
    $activeRequests = $requests->filter(fn ($r) => $r->status === 'Pending')->values();
@endphp

<div class="p-4 md:p-8 max-w-6xl" data-live-page data-live-region="main" data-live-listen="tenant.services:.room-change.updated tenant.reservations:.reservation.updated tenant.billing:.invoice.updated" data-live-refresh-on="room-change:refresh">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-blue-100 text-blue-500 flex items-center justify-center text-lg">
                <i class="fa-regular fa-building"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-slate-900">Room Change Requests</h1>
                <p class="text-[13px] text-slate-500 mt-0.5">Request to change your current room</p>
            </div>
        </div>
    </div>

    <div class="bg-blue-50 rounded-xl px-5 py-3.5 flex items-center gap-2.5 text-blue-600 text-[13.5px] mb-6">
        <i class="fa-regular fa-circle-info"></i>
        <span><strong>Note:</strong> You can cancel a request while it is <strong>Pending</strong>. Once approved, it becomes view-only.</span>
    </div>

    {{-- ── APPROVED RESERVATION REQUIRED ── --}}
    @if(! $hasApprovedReservation)
        <div id="tenant-no-warning"
             class="bg-amber-50 border border-amber-200 rounded-lg px-5 py-4 text-[13px] text-amber-800 flex items-start gap-2.5 mb-5">
            <i class="fa-regular fa-circle-info mt-0.5"></i>
            <div>
                <strong>Room change requires an approved reservation.</strong>
                Your current booking is still awaiting admin approval. Once your reservation is approved,
                you'll be able to request a room change here. If you need to modify your booking before
                approval, please contact the admin directly.
            </div>
        </div>
    @elseif(! $tenant || ! $tenant->room)
        <div id="tenant-no-warning"
             class="bg-amber-50 border border-amber-200 rounded-lg px-5 py-4 text-[13px] text-amber-800 flex items-start gap-2.5 mb-5">
            <i class="fa-regular fa-circle-info mt-0.5"></i>
            <div>
                <strong>You're not yet assigned to a room.</strong>
                Please contact the admin to be assigned before submitting room change requests.
            </div>
        </div>
    @endif

    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <div class="flex justify-between items-center mb-5">
            <span class="text-[13.5px] text-slate-500">
                {{ $activeRequests->count() }} active request{{ $activeRequests->count() === 1 ? '' : 's' }}
            </span>
            <button id="open-new-room-change"
                    class="bg-[#001838] hover:bg-[#0d2347] disabled:opacity-50 text-white px-4 py-2.5 rounded-lg font-medium text-[13px] inline-flex items-center gap-2"
                    {{ ! $canRequestChange ? 'disabled' : '' }}
                    title="{{ ! $canRequestChange ? 'An approved reservation is required to request a room change.' : '' }}">
                <i class="fa-solid fa-plus"></i> New Request
            </button>
        </div>

        <div id="tenant-room-change-container">
            @if($activeRequests->isEmpty())
                <div class="text-center py-12 text-slate-400 text-sm">
                    <i class="fa-solid fa-inbox text-3xl mb-3"></i>
                    <p>No active room change requests.</p>
                </div>
            @else
                <div class="flex flex-col gap-4">
                    @foreach($activeRequests as $req)
                        <div class="bg-slate-50 border border-slate-200 rounded-xl p-5 relative">
                            <div class="flex items-center gap-2 mb-2.5">
                                <div class="text-[15px] font-bold text-slate-900">
                                    {{ $req->current_room }}
                                    <span class="text-slate-400 font-normal mx-0.5">&rarr;</span>
                                    <span class="text-emerald-600">{{ $req->requested_room }}</span>
                                </div>
                                <span class="bg-amber-100 text-amber-700 text-[11px] font-semibold px-2 py-0.5 rounded-full inline-flex items-center gap-1">
                                    <i class="fa-regular fa-clock"></i> Pending
                                </span>
                            </div>
                            <div class="absolute right-5 top-4 flex gap-3 text-slate-400 text-sm">
                                <i class="fa-regular fa-eye cursor-pointer hover:text-slate-600 view-room-change-details-btn"
                                   data-request-id="{{ $req->id }}" title="View Details"></i>
                                <i class="fa-regular fa-circle-xmark cursor-pointer hover:text-rose-600 cancel-room-change-btn"
                                   data-request-id="{{ $req->id }}" title="Cancel Request"></i>
                            </div>
                            <div class="text-slate-600 text-[13.5px] mb-3">{{ $req->reason }}</div>
                            <div class="flex gap-4 text-xs text-slate-400">
                                <span class="flex items-center gap-1.5">
                                    <i class="fa-regular fa-calendar"></i> {{ $req->date }}
                                </span>
                                <span>Availability: {{ $req->availability }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

<div id="services-room-change-root" data-live-root="room-change:data-updated"
     data-current-room="{{ $currentRoom }}"
     data-available-rooms='@json($availableRooms)'
     data-requests='@json($requestsForReact)'></div>
@endsection

