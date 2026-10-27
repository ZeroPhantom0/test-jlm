@extends('layouts.tenant', ['active' => 'services.stay-extension', 'pageTitle' => 'Services'])
@section('title', 'Stay Extension Requests')

@section('content')
@php
    // Hide finalized statuses (Approved & Rejected)
    $activeRequests = $requests->filter(function ($r) {
        return $r->status === 'Pending';
    })->values();
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

    <div id="tenant-no-warning" class="bg-amber-50 border border-amber-200 rounded-lg px-5 py-4 text-[13px] text-amber-800 flex items-start gap-2.5 mb-5 @if($tenant) hidden @endif">
        <i class="fa-regular fa-circle-info mt-0.5"></i>
        <div>
            <strong>You're not yet assigned to a room.</strong>
            Please contact the admin to be assigned before submitting stay extension requests.
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <div class="flex justify-between items-center mb-5">
            <span id="tenant-request-count" class="text-[13.5px] text-slate-500">
                {{ $activeRequests->count() }} active request{{ $activeRequests->count() === 1 ? '' : 's' }}
            </span>
            <button id="open-new-stay-extension"
                    class="bg-[#001838] hover:bg-[#0d2347] disabled:opacity-50 text-white px-4 py-2.5 rounded-lg font-medium text-[13px] inline-flex items-center gap-2"
                    {{ !$tenant ? 'disabled' : '' }}>
                <i class="fa-solid fa-plus"></i> New Request
            </button>
        </div>

        <div id="tenant-stay-extension-container">
            {{-- JS will render --}}
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
        const DATA_URL = "{{ route('tenants.services.stay-extension.data') }}";
        const container = document.getElementById('tenant-stay-extension-container');
        const countEl = document.getElementById('tenant-request-count');
        const modalsEl = document.getElementById('services-stay-extension-root');
        const warningEl = document.getElementById('tenant-no-warning');
        const newBtn = document.getElementById('open-new-stay-extension');

        async function refresh() {
            try {
                const res = await fetch(DATA_URL, { headers: { 'Accept': 'application/json' } });
                if (!res.ok) return;
                const data = await res.json();

                if (warningEl) warningEl.classList.toggle('hidden', data.has_tenant);
                if (newBtn) newBtn.disabled = !data.has_tenant;

                const active = data.rows.filter(r => r.is_pending);
                if (countEl) countEl.textContent = `${active.length} active request${active.length === 1 ? '' : 's'}`;

                if (active.length === 0) {
                    container.innerHTML = `
                        <div class="text-center py-12 text-slate-400 text-sm">
                            <i class="fa-solid fa-inbox text-3xl mb-3"></i>
                            <p>No active stay extension requests.</p>
                        </div>`;
                } else {
                    let html = `
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
                                <tbody>`;
                    active.forEach(r => {
                        html += `
                            <tr class="border-b border-slate-50 last:border-0">
                                <td class="py-4 px-4">
                                    <span class="block font-semibold text-slate-900">${r.tenant_name}</span>
                                    <span class="block text-xs text-slate-400 mt-0.5">${r.room_number}</span>
                                </td>
                                <td class="py-4 px-4 text-[13.5px]">${r.current_lease_end}</td>
                                <td class="py-4 px-4 text-[13.5px]">${r.requested_lease_end}</td>
                                <td class="py-4 px-4">
                                    <span class="bg-amber-100 text-amber-700 text-[11.5px] font-semibold px-2.5 py-1 rounded-full inline-flex items-center gap-1">
                                        <i class="fa-regular fa-clock"></i> Pending
                                    </span>
                                </td>
                                <td class="py-4 px-4">
                                    <div class="flex justify-end gap-3 text-slate-400 text-[15px]">
                                        <i class="fa-regular fa-eye cursor-pointer hover:text-slate-600 view-stay-extension-details-btn" data-request-id="${r.id}" title="View Details"></i>
                                        <i class="fa-regular fa-circle-xmark cursor-pointer hover:text-rose-600 cancel-stay-extension-btn" data-request-id="${r.id}" title="Cancel Request"></i>
                                    </div>
                                </td>
                            </tr>`;
                    });
                    html += `</tbody></table></div>`;
                    container.innerHTML = html;
                }

                if (modalsEl) {
                    modalsEl.dataset.requests = JSON.stringify(data.react);
                    window.dispatchEvent(new CustomEvent('stay-extension:data-updated', { detail: data.react }));
                }
            } catch (e) { /* silent */ }
        }

        if (window.Echo) {
            window.Echo.channel('tenant.services').listen('.stay-extension.updated', () => refresh());
        }
        window.addEventListener('maintenance:refresh', () => refresh());
        setInterval(refresh, 3000);
        refresh();
    })();
</script>
@endpush