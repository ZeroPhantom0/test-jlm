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

    {{-- No-tenant warning --}}
    @if (!$tenant || !$tenant->room)
        <div class="bg-amber-50 border border-amber-200 rounded-lg px-5 py-4 text-[13px] text-amber-800 flex items-start gap-2.5 mb-5">
            <i class="fa-regular fa-circle-info mt-0.5"></i>
            <div>
                <strong>You're not yet assigned to a room.</strong>
                Please contact the admin to be assigned before submitting room change requests.
            </div>
        </div>
    @endif

    {{-- Card --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">

        <div class="flex justify-between items-center mb-5">
            <span class="text-[13.5px] text-slate-500">
                {{ $requests->count() }} request{{ $requests->count() === 1 ? '' : 's' }}
            </span>
            <button id="open-new-room-change"
                    class="bg-[#001838] hover:bg-[#0d2347] disabled:opacity-50 text-white px-4 py-2.5 rounded-lg font-medium text-[13px] inline-flex items-center gap-2"
                    {{ (!$tenant || !$tenant->room) ? 'disabled' : '' }}>
                <i class="fa-solid fa-plus"></i> New Request
            </button>
        </div>

        @if ($requests->isEmpty())
            <div class="text-center py-12 text-slate-400 text-sm">
                <i class="fa-solid fa-inbox text-3xl mb-3"></i>
                <p>No room change requests yet.</p>
            </div>
        @else
            <div class="flex flex-col gap-4">
                @foreach ($requests as $r)
                    @php
                        $statusClass = match ($r->status) {
                            'Pending'  => 'bg-amber-100 text-amber-700',
                            'Approved' => 'bg-emerald-100 text-emerald-600',
                            'Rejected' => 'bg-rose-100 text-rose-600',
                            default    => 'bg-slate-100 text-slate-500',
                        };
                        $statusIcon = match ($r->status) {
                            'Pending'  => 'fa-regular fa-clock',
                            'Approved' => 'fa-regular fa-circle-check',
                            'Rejected' => 'fa-regular fa-circle-xmark',
                            default    => 'fa-regular fa-circle',
                        };
                        $availabilityLabel = ucfirst(strtolower($r->availability ?? 'Available'));
                    @endphp

                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-5 relative">
                        <div class="flex items-center gap-2 mb-2.5">
                            <div class="text-[15px] font-bold text-slate-900">
                                {{ $r->current_room }}
                                <span class="text-slate-400 font-normal mx-0.5">&rarr;</span>
                                <span class="text-emerald-600">{{ $r->requested_room }}</span>
                            </div>
                            <span class="{{ $statusClass }} text-[11px] font-semibold px-2 py-0.5 rounded-full inline-flex items-center gap-1">
                                <i class="{{ $statusIcon }}"></i> {{ $r->status }}
                            </span>
                        </div>

                        <div class="absolute right-5 top-4 flex gap-3 text-slate-400 text-sm">
                            <i class="fa-regular fa-eye cursor-pointer hover:text-slate-600 view-room-change-details-btn"
                               data-request-id="{{ $r->id }}"
                               title="View Details"></i>
                            @if ($r->status === 'Pending')
                                <i class="fa-regular fa-circle-xmark cursor-pointer hover:text-rose-600 cancel-room-change-btn"
                                   data-request-id="{{ $r->id }}"
                                   title="Cancel Request"></i>
                            @endif
                        </div>

                        <div class="text-slate-600 text-[13.5px] mb-3">{{ $r->reason }}</div>
                        <div class="flex gap-4 text-xs text-slate-400">
                            <span class="flex items-center gap-1.5">
                                <i class="fa-regular fa-calendar"></i>
                                {{ $r->created_at?->format('M d, Y') }}
                            </span>
                            <span>Availability: {{ $availabilityLabel }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

{{-- React Modal Mount Point with data --}}
<div id="services-room-change-root"
     data-current-room="{{ $currentRoom }}"
     data-available-rooms='@json($availableRooms)'
     data-requests='@json($requestsForReact)'></div>
@endsection