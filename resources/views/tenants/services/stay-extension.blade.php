@extends('layouts.tenant', ['active' => 'services.stay-extension', 'pageTitle' => 'Services'])
@section('title', 'Stay Extension Requests')

@section('content')
@php
    $activeRequests = $requests->filter(fn ($r) => $r->status === 'Pending')->values();
@endphp

<div class="p-4 md:p-8 max-w-6xl">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-purple-100 text-purple-500 flex items-center justify-center text-lg">
                <i class="fa-regular fa-calendar-plus"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-slate-900">Stay Extension Requests</h1>
                <p class="text-[13px] text-slate-500 mt-0.5">Request to extend your lease period</p>
            </div>
        </div>
    </div>

    <div class="bg-blue-50 rounded-xl px-5 py-3.5 flex items-center gap-2.5 text-blue-600 text-[13.5px] mb-6">
        <i class="fa-regular fa-circle-info"></i>
        <span><strong>Note:</strong> You can cancel a request while it is <strong>Pending</strong>. Once approved, it becomes view-only.</span>
    </div>

    <div id="tenant-no-warning"
         class="bg-amber-50 border border-amber-200 rounded-lg px-5 py-4 text-[13px] text-amber-800 flex items-start gap-2.5 mb-5 @if($tenant) hidden @endif">
        <i class="fa-regular fa-circle-info mt-0.5"></i>
        <div>
            <strong>You're not yet assigned to a room.</strong>
            Please contact the admin to be assigned before submitting stay extension requests.
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <div class="flex justify-between items-center mb-5">
            <span class="text-[13.5px] text-slate-500">
                {{ $activeRequests->count() }} active request{{ $activeRequests->count() === 1 ? '' : 's' }}
            </span>
            <button id="open-new-stay-extension"
                    class="bg-[#001838] hover:bg-[#0d2347] disabled:opacity-50 text-white px-4 py-2.5 rounded-lg font-medium text-[13px] inline-flex items-center gap-2"
                    {{ !$tenant ? 'disabled' : '' }}>
                <i class="fa-solid fa-plus"></i> New Request
            </button>
        </div>

        <div id="tenant-stay-extension-container">
            @if($activeRequests->isEmpty())
                <div class="text-center py-12 text-slate-400 text-sm">
                    <i class="fa-solid fa-inbox text-3xl mb-3"></i>
                    <p>No active stay extension requests.</p>
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
                            @foreach($activeRequests as $req)
                                <tr class="border-b border-slate-50 last:border-0">
                                    <td class="py-4 px-4">
                                        <span class="block font-semibold text-slate-900">{{ $req->tenant_name }}</span>
                                        <span class="block text-xs text-slate-400 mt-0.5">{{ $req->room_number }}</span>
                                    </td>
                                    <td class="py-4 px-4 text-[13.5px]">{{ $req->current_lease_end }}</td>
                                    <td class="py-4 px-4 text-[13.5px]">{{ $req->requested_lease_end }}</td>
                                    <td class="py-4 px-4">
                                        <span class="bg-amber-100 text-amber-700 text-[11.5px] font-semibold px-2.5 py-1 rounded-full inline-flex items-center gap-1">
                                            <i class="fa-regular fa-clock"></i> Pending
                                        </span>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="flex justify-end gap-3 text-slate-400 text-[15px]">
                                            <i class="fa-regular fa-eye cursor-pointer hover:text-slate-600 view-stay-extension-details-btn"
                                               data-request-id="{{ $req->id }}" title="View Details"></i>
                                            <i class="fa-regular fa-circle-xmark cursor-pointer hover:text-rose-600 cancel-stay-extension-btn"
                                               data-request-id="{{ $req->id }}" title="Cancel Request"></i>
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
</div>

<div id="services-stay-extension-root"
     data-room-number="{{ $roomNumber }}"
     data-current-lease-end="{{ $currentLeaseEnd }}"
     data-min-requested-date="{{ $minRequestedDate }}"
     data-requests='@json($requestsForReact)'></div>
@endsection

@push('scripts')
<script>
(function () {
    window.addEventListener('load', () => {
        async function refresh() {
            try {
                const res = await fetch("{{ route('tenants.services.room-change.data') }}", {
                    headers: { 'Accept': 'application/json' },
                    cache: 'no-store'
                });
                if (!res.ok) return;
                const data = await res.json();

                const container = document.getElementById('tenant-room-change-container');
                const countEl = document.querySelector('.text-\\[13\\.5px\\].text-slate-500');
                const active = data.rows.filter(r => r.is_pending);

                if (countEl) {
                    countEl.textContent = `${active.length} active request${active.length === 1 ? '' : 's'}`;
                }

                if (active.length === 0) {
                    container.innerHTML = `
                        <div class="text-center py-12 text-slate-400 text-sm">
                            <i class="fa-solid fa-inbox text-3xl mb-3"></i>
                            <p>No active room change requests.</p>
                        </div>`;
                } else {
                    container.innerHTML = `<div class="flex flex-col gap-4">` + active.map(r => `
                        <div class="bg-slate-50 border border-slate-200 rounded-xl p-5 relative">
                            <div class="flex items-center gap-2 mb-2.5">
                                <div class="text-[15px] font-bold text-slate-900">
                                    ${r.current_room}
                                    <span class="text-slate-400 font-normal mx-0.5">&rarr;</span>
                                    <span class="text-emerald-600">${r.requested_room}</span>
                                </div>
                                <span class="bg-amber-100 text-amber-700 text-[11px] font-semibold px-2 py-0.5 rounded-full inline-flex items-center gap-1">
                                    <i class="fa-regular fa-clock"></i> Pending
                                </span>
                            </div>
                            <div class="absolute right-5 top-4 flex gap-3 text-slate-400 text-sm">
                                <i class="fa-regular fa-eye cursor-pointer hover:text-slate-600 view-room-change-details-btn" data-request-id="${r.id}" title="View Details"></i>
                                <i class="fa-regular fa-circle-xmark cursor-pointer hover:text-rose-600 cancel-room-change-btn" data-request-id="${r.id}" title="Cancel Request"></i>
                            </div>
                            <div class="text-slate-600 text-[13.5px] mb-3">${r.reason}</div>
                            <div class="flex gap-4 text-xs text-slate-400">
                                <span class="flex items-center gap-1.5">
                                    <i class="fa-regular fa-calendar"></i> ${r.date}
                                </span>
                                <span>Availability: ${r.availability}</span>
                            </div>
                        </div>
                    `).join('') + `</div>`;
                }

                const modalEl = document.getElementById('services-room-change-root');
                if (modalEl && data.react) {
                    modalEl.dataset.requests = JSON.stringify(data.react);
                    window.dispatchEvent(new CustomEvent('room-change:data-updated', { detail: data.react }));
                }
            } catch (e) {
                console.error('Failed to refresh room changes:', e);
            }
        }

        if (window.Echo) {
            window.Echo.private('tenant.services')
                .listen('.room-change.updated', () => {
                    setTimeout(refresh, 800);
                });
        }

        window.addEventListener('room-change:refresh', refresh);
    });
})();
</script>
@endpush