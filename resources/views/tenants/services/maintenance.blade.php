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

    {{-- No-tenant warning --}}
    @if (!$tenant)
        <div class="bg-amber-50 border border-amber-200 rounded-lg px-5 py-4 text-[13px] text-amber-800 flex items-start gap-2.5 mb-5">
            <i class="fa-regular fa-circle-info mt-0.5"></i>
            <div>
                <strong>You're not yet assigned to a room.</strong>
                Please contact the admin to be assigned before submitting maintenance requests.
            </div>
        </div>
    @endif

    {{-- Table card --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
        <div class="flex justify-between items-center mb-5">
            <span class="text-[13px] text-slate-500">
                {{ $requests->count() }} request{{ $requests->count() === 1 ? '' : 's' }}
            </span>
            <button
                id="open-new-request"
                class="bg-[#02122c] hover:bg-[#0d2347] disabled:opacity-50 text-white px-4 py-2.5 rounded-lg text-[13px] font-semibold inline-flex items-center gap-2"
                {{ !$tenant ? 'disabled' : '' }}
            >
                <i class="fa-solid fa-plus"></i> New Request
            </button>
        </div>

        @if ($requests->isEmpty())
            <div class="text-center py-12 text-slate-400 text-sm">
                <i class="fa-solid fa-inbox text-3xl mb-3"></i>
                <p>No maintenance requests yet.</p>
            </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="text-xs font-semibold text-slate-600 border-b border-slate-100">
                        <th class="py-3 px-2 w-[35%]">Request</th>
                        <th class="py-3 px-2 w-[20%]">Assigned Staff</th>
                        <th class="py-3 px-2 w-[15%]">Status</th>
                        <th class="py-3 px-2 w-[18%]">Date</th>
                        <th class="py-3 px-2 w-[12%] text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($requests as $r)
                        @php
                            $statusClasses = match ($r->status) {
                                'Pending'     => 'bg-amber-100 text-amber-700',
                                'In Progress' => 'bg-blue-100 text-blue-600',
                                'Completed'   => 'bg-emerald-100 text-emerald-600',
                                'Cancelled'   => 'bg-slate-100 text-slate-500',
                                default       => 'bg-slate-100 text-slate-500',
                            };
                            $statusIcon = match ($r->status) {
                                'Pending'     => 'fa-regular fa-clock',
                                'In Progress' => 'fa-solid fa-spinner',
                                'Completed'   => 'fa-regular fa-circle-check',
                                'Cancelled'   => 'fa-regular fa-circle-xmark',
                                default       => 'fa-regular fa-circle',
                            };
                        @endphp
                        <tr class="border-b border-slate-50 last:border-0">
                            {{-- Request title + description --}}
                            <td class="py-4 px-2">
                                <div class="font-semibold text-slate-800 text-[13px]">{{ $r->title }}</div>
                                <div class="text-xs text-slate-400 mt-0.5">{{ $r->description }}</div>
                            </td>

                            {{-- Assigned staff --}}
                            <td class="py-4 px-2 {{ $r->assigned_staff ? 'text-slate-700' : 'text-slate-400' }} text-[13px]">
                                {{ $r->assigned_staff ?: '—' }}
                            </td>

                            {{-- Status --}}
                            <td class="py-4 px-2">
                                <span class="{{ $statusClasses }} text-[11px] font-semibold px-2.5 py-1 rounded-full inline-flex items-center gap-1">
                                    <i class="{{ $statusIcon }}"></i> {{ $r->status }}
                                </span>
                            </td>

                            {{-- Date --}}
                            <td class="py-4 px-2 text-slate-600 text-[13px] whitespace-nowrap">
                                {{ $r->created_at?->format('M d, Y') }}
                            </td>

                            {{-- Actions --}}
                            <td class="py-4 px-2">
                                <div class="flex items-center justify-end gap-3 text-slate-400">
                                    <i
                                        class="fa-regular fa-eye cursor-pointer hover:text-slate-600 view-details-btn"
                                        data-request-id="{{ $r->id }}"
                                        title="View details"
                                    ></i>
                                    @if ($r->status === 'Pending')
                                        <i
                                            class="fa-regular fa-circle-xmark cursor-pointer hover:text-rose-600 cancel-request-btn"
                                            data-request-id="{{ $r->id }}"
                                            title="Cancel request"
                                        ></i>
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

{{-- React Modal Mount Point (data now comes from the controller) --}}
<div id="tenant-maintenance-modals"
     data-requests='@json($requestsForReact)'></div>
@endsection