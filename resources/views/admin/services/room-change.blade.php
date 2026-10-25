@extends('layouts.admin', ['active' => 'services.room-change', 'pageTitle' => 'Services'])
@section('title', 'Room Change Requests')

@section('content')
@php
    // Hide finalized statuses (Approved & Rejected)
    $activeRequests = $requests->filter(function ($req) {
        return $req->status === 'Pending';
    })->values();
@endphp

<div class="p-4 md:p-8 max-w-[1400px] mx-auto space-y-6">

    {{-- Page Title --}}
    <div class="flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center text-xl">
            <i class="fa-solid fa-house"></i>
        </div>
        <div>
            <h2 class="text-[22px] font-extrabold text-slate-900">Room Change Requests</h2>
            <p class="text-[13px] text-slate-500 mt-0.5">Review and manage pending tenant room transfer requests</p>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl px-5 py-4 border border-slate-200 shadow-sm">
            <span class="text-[13px] text-slate-500 font-medium">Total</span>
            <div class="text-[22px] font-bold text-slate-900 mt-2">{{ $metrics['total'] }}</div>
        </div>
        <div class="bg-white rounded-2xl px-5 py-4 border border-slate-200 shadow-sm">
            <span class="text-[13px] text-slate-500 font-medium">Pending</span>
            <div class="mt-2">
                <div class="w-7 h-7 rounded-full bg-yellow-100 text-amber-700 flex items-center justify-center text-[13px] font-bold">{{ $metrics['pending'] }}</div>
            </div>
        </div>
        <div class="bg-white rounded-2xl px-5 py-4 border border-slate-200 shadow-sm">
            <span class="text-[13px] text-slate-500 font-medium">Approved</span>
            <div class="mt-2">
                <div class="w-7 h-7 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-[13px] font-bold">{{ $metrics['approved'] }}</div>
            </div>
        </div>
        <div class="bg-white rounded-2xl px-5 py-4 border border-slate-200 shadow-sm">
            <span class="text-[13px] text-slate-500 font-medium">Rejected</span>
            <div class="mt-2">
                <div class="w-7 h-7 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center text-[13px] font-bold">{{ $metrics['rejected'] }}</div>
            </div>
        </div>
    </div>

    {{-- Table Card --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-5">

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="text-[13px] font-bold text-slate-600 border-b border-slate-100 whitespace-nowrap">
                        <th class="py-3 px-4">Tenant</th>
                        <th class="py-3 px-4">Reference</th>
                        <th class="py-3 px-4">Room Transfer</th>
                        <th class="py-3 px-4">Availability</th>
                        <th class="py-3 px-4">Reason</th>
                        <th class="py-3 px-4">Submitted</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($activeRequests as $req)
                    <tr class="border-b border-slate-50 last:border-0">
                        {{-- Tenant --}}
                        <td class="py-4 px-4">
                            <div class="font-bold text-slate-900 text-[13px]">{{ $req->tenant->user->name ?? 'Unknown' }}</div>
                            <div class="text-[11px] text-slate-400 mt-0.5">{{ $req->tenant->user->email ?? '' }}</div>
                        </td>

                        {{-- Reference --}}
                        <td class="py-4 px-4">
                            <div class="font-semibold text-slate-700 text-[13px]">{{ $req->reference_no }}</div>
                        </td>

                        {{-- Room Transfer --}}
                        <td class="py-4 px-4">
                            <div class="flex items-center gap-2 text-[13px]">
                                <span class="font-semibold text-slate-700">{{ $req->current_room }}</span>
                                <i class="fa-solid fa-arrow-right text-slate-400 text-[10px]"></i>
                                <span class="font-semibold text-indigo-700">{{ $req->requested_room }}</span>
                            </div>
                        </td>

                        {{-- Availability --}}
                        <td class="py-4 px-4">
                            <span class="inline-block text-[10px] font-bold px-2 py-0.5 rounded tracking-wide {{ $req->availability_class }}">
                                {{ $req->availability }}
                            </span>
                        </td>

                        {{-- Reason --}}
                        <td class="py-4 px-4 max-w-[240px]">
                            <p class="text-[13px] text-slate-600 truncate" title="{{ $req->reason }}">{{ $req->reason }}</p>
                        </td>

                        {{-- Submitted --}}
                        <td class="py-4 px-4 text-[13px] text-slate-600 whitespace-nowrap">
                            {{ $req->created_at?->format('M d, Y') }}
                        </td>

                        {{-- Status --}}
                        <td class="py-4 px-4">
                            <span class="inline-flex items-center gap-1.5 {{ $req->status_class }} text-[11px] font-semibold px-3 py-1 rounded-full whitespace-nowrap">
                                <i class="{{ $req->status_icon }}"></i> {{ $req->status }}
                            </span>
                        </td>

                        {{-- Actions (icon-only) --}}
                        <td class="py-4 px-4">
                            <div class="flex items-center justify-end gap-3">
                                {{-- Approve & Reject are always visible since only Pending is shown --}}
                                <i
                                    class="fa-regular fa-circle-check text-emerald-500 text-[15px] cursor-pointer hover:text-emerald-600 transition icon-approve"
                                    title="Approve"
                                    data-request-id="{{ $req->id }}"
                                ></i>
                                <i
                                    class="fa-regular fa-circle-xmark text-rose-500 text-[15px] cursor-pointer hover:text-rose-600 transition icon-reject"
                                    title="Reject"
                                    data-request-id="{{ $req->id }}"
                                ></i>
                                <i
                                    class="fa-regular fa-eye text-slate-700 text-[15px] cursor-pointer hover:text-orange-500 transition open-details-btn"
                                    title="View Details"
                                    data-request-id="{{ $req->id }}"
                                ></i>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-12 text-slate-400">
                            <i class="fa-solid fa-inbox text-4xl mb-3"></i>
                            <p class="text-sm">No pending room change requests found.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- React Modal Mount Point with data --}}
<div id="admin-room-change-modals" data-requests='@json($requestsForReact)'></div>
@endsection