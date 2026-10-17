@extends('layouts.tenant', ['active' => 'services.stay-extension', 'pageTitle' => 'Services'])
@section('title', 'Stay Extension Requests')

@section('content')
<div class="p-4 md:p-8 max-w-6xl">

    {{-- Page Title --}}
    <div class="flex items-center gap-4 mb-6">
        <div class="w-11 h-11 rounded-xl bg-purple-100 text-purple-500 flex items-center justify-center text-lg">
            <i class="fa-regular fa-calendar-plus"></i>
        </div>
        <div>
            <h1 class="text-xl font-bold text-slate-900">Stay Extension Requests</h1>
            <p class="text-[13px] text-slate-500 mt-0.5">Request to extend your lease period</p>
        </div>
    </div>

    {{-- Info Banner --}}
    <div class="bg-blue-50 rounded-xl px-5 py-3.5 flex items-center gap-2.5 text-blue-600 text-[13.5px] mb-6">
        <i class="fa-regular fa-circle-info"></i>
        <span><strong>Note:</strong> You can cancel a request while it is <strong>Pending</strong>. Once approved, it becomes view-only.</span>
    </div>

    {{-- No-tenant warning --}}
    @if (!$tenant)
        <div class="bg-amber-50 border border-amber-200 rounded-lg px-5 py-4 text-[13px] text-amber-800 flex items-start gap-2.5 mb-5">
            <i class="fa-regular fa-circle-info mt-0.5"></i>
            <div>
                <strong>You're not yet assigned to a room.</strong>
                Please contact the admin to be assigned before submitting stay extension requests.
            </div>
        </div>
    @endif

    {{-- Table Card --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">

        <div class="flex justify-between items-center mb-5">
            <span class="text-[13.5px] text-slate-500">
                {{ $requests->count() }} request{{ $requests->count() === 1 ? '' : 's' }}
            </span>
            <button id="open-new-stay-extension"
                    class="bg-[#001838] hover:bg-[#0d2347] disabled:opacity-50 text-white px-4 py-2.5 rounded-lg font-medium text-[13px] inline-flex items-center gap-2"
                    {{ !$tenant ? 'disabled' : '' }}>
                <i class="fa-solid fa-plus"></i> New Request
            </button>
        </div>

        @if ($requests->isEmpty())
            <div class="text-center py-12 text-slate-400 text-sm">
                <i class="fa-solid fa-inbox text-3xl mb-3"></i>
                <p>No stay extension requests yet.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-[13px] text-slate-600 border-b border-slate-100">
                            <th class="py-3 px-4 font-semibold">Name &amp; Room</th>
                            <th class="py-3 px-4 font-semibold">Current Lease End</th>
                            <th class="py-3 px-4 font-semibold">Requested End</th>
                            <th class="py-3 px-4 font-semibold">Status</th>
                            <th class="py-3 px-4 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
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
                            @endphp
                            <tr class="border-b border-slate-50 last:border-0">
                                <td class="py-4 px-4">
                                    <span class="block font-semibold text-slate-900">
                                        {{ $r->tenant->user->name ?? 'Unknown' }}
                                    </span>
                                    <span class="block text-xs text-slate-400 mt-0.5">
                                        {{ $r->room?->room_number ? 'Room ' . $r->room->room_number : 'N/A' }}
                                    </span>
                                </td>
                                <td class="py-4 px-4 text-[13.5px]">
                                    {{ $r->current_lease_end?->format('M j, Y') ?? '—' }}
                                </td>
                                <td class="py-4 px-4 text-[13.5px]">
                                    {{ $r->requested_lease_end?->format('M j, Y') ?? '—' }}
                                </td>
                                <td class="py-4 px-4">
                                    <span class="{{ $statusClass }} text-[11.5px] font-semibold px-2.5 py-1 rounded-full inline-flex items-center gap-1">
                                        <i class="{{ $statusIcon }}"></i> {{ $r->status }}
                                    </span>
                                </td>
                                <td class="py-4 px-4">
                                    <div class="flex justify-end gap-3 text-slate-400 text-[15px]">
                                        <i class="fa-regular fa-eye cursor-pointer hover:text-slate-600 view-stay-extension-details-btn"
                                           data-request-id="{{ $r->id }}"
                                           title="View Details"></i>
                                        @if ($r->status === 'Pending')
                                            <i class="fa-regular fa-circle-xmark cursor-pointer hover:text-rose-600 cancel-stay-extension-btn"
                                               data-request-id="{{ $r->id }}"
                                               title="Cancel Request"></i>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

{{-- React Modal Mount Point with data --}}
<div id="services-stay-extension-root"
     data-room-number="{{ $roomNumber }}"
     data-current-lease-end="{{ $currentLeaseEnd }}"
     data-requests='@json($requestsForReact)'></div>
@endsection