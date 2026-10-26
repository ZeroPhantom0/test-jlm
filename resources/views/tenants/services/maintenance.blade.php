@extends('layouts.tenant', ['active' => 'services.maintenance', 'pageTitle' => 'Services'])
@section('title', 'Maintenance Requests')

@section('content')
@php
    // Hide finalized statuses (Completed & Cancelled)
    $activeRequests = $requests->filter(function ($r) {
        return !in_array($r->status, ['Completed', 'Cancelled']);
    })->values();
@endphp

<div class="p-4 md:p-8 max-w-6xl">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center text-lg">
                <i class="fa-solid fa-wrench"></i>
            </div>
            <div>
                <h2 class="text-xl font-bold text-slate-900">Maintenance Requests</h2>
                <p class="text-[13px] text-slate-500 mt-0.5">Track active and in-progress maintenance issues</p>
            </div>
        </div>
        <div class="flex items-center gap-2 text-[11px] font-semibold text-emerald-600">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <span>Live</span>
        </div>
    </div>

    <div class="bg-blue-50 text-blue-600 rounded-lg px-5 py-3 flex items-center gap-2.5 mb-5 text-[13px]">
        <i class="fa-regular fa-circle-info"></i>
        <div><strong>Note:</strong> You can cancel a request while it is <strong>Pending</strong>. Once approved or in progress, it becomes view-only.</div>
    </div>

    <div id="tenant-no-warning" class="bg-amber-50 border border-amber-200 rounded-lg px-5 py-4 text-[13px] text-amber-800 flex items-start gap-2.5 mb-5 @if($tenant) hidden @endif">
        <i class="fa-regular fa-circle-info mt-0.5"></i>
        <div>
            <strong>You're not yet assigned to a room.</strong>
            Please contact the admin to be assigned before submitting maintenance requests.
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
        <div class="flex justify-between items-center mb-5">
            <span id="tenant-request-count" class="text-[13px] text-slate-500">
                {{ $activeRequests->count() }} active request{{ $activeRequests->count() === 1 ? '' : 's' }}
            </span>
            <button
                id="open-new-request"
                class="bg-[#02122c] hover:bg-[#0d2347] disabled:opacity-50 text-white px-4 py-2.5 rounded-lg text-[13px] font-semibold inline-flex items-center gap-2"
                {{ !$tenant ? 'disabled' : '' }}
            >
                <i class="fa-solid fa-plus"></i> New Request
            </button>
        </div>

        <div id="tenant-maintenance-container">
            {{-- JS will render --}}
        </div>
    </div>
</div>

<div id="tenant-maintenance-modals" data-requests='@json($requestsForReact)'></div>
@endsection

@push('scripts')
<script>
    (function () {
        const DATA_URL = "{{ route('tenants.services.maintenance.data') }}";
        const container = document.getElementById('tenant-maintenance-container');
        const countEl = document.getElementById('tenant-request-count');
        const modalsEl = document.getElementById('tenant-maintenance-modals');
        const warningEl = document.getElementById('tenant-no-warning');
        const newBtn = document.getElementById('open-new-request');

        async function refresh() {
            try {
                const res = await fetch(DATA_URL, { headers: { 'Accept': 'application/json' } });
                if (!res.ok) return;
                const data = await res.json();

                if (warningEl) {
                    warningEl.classList.toggle('hidden', data.has_tenant);
                }
                if (newBtn) {
                    newBtn.disabled = !data.has_tenant;
                }

                const active = data.rows.filter(r => !r.is_finalized);
                if (countEl) countEl.textContent = `${active.length} active request${active.length === 1 ? '' : 's'}`;

                if (active.length === 0) {
                    container.innerHTML = `
                        <div class="text-center py-12 text-slate-400 text-sm">
                            <i class="fa-solid fa-inbox text-3xl mb-3"></i>
                            <p>No active maintenance requests.</p>
                        </div>`;
                } else {
                    let html = `
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="text-xs font-semibold text-slate-600 border-b border-slate-100">
                                        <th class="py-3 px-2 w-[45%]">Request</th>
                                        <th class="py-3 px-2 w-[25%]">Assigned Staff</th>
                                        <th class="py-3 px-2 w-[20%]">Status</th>
                                        <th class="py-3 px-2 w-[10%] text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>`;
                    active.forEach(r => {
                        html += `
                            <tr class="border-b border-slate-50 last:border-0">
                                <td class="py-4 px-2">
                                    <div class="font-semibold text-slate-800 text-[13px]">${r.title}</div>
                                    <div class="text-xs text-slate-400 mt-0.5 line-clamp-1">${r.description}</div>
                                </td>
                                <td class="py-4 px-2 ${r.assigned_staff ? 'text-slate-700' : 'text-slate-400'} text-[13px]">${r.assigned_staff || '—'}</td>
                                <td class="py-4 px-2">
                                    <span class="${r.status_classes} text-[11px] font-semibold px-2.5 py-1 rounded-full inline-flex items-center gap-1">
                                        <i class="${r.status_icon}"></i> ${r.status}
                                    </span>
                                </td>
                                <td class="py-4 px-2">
                                    <div class="flex items-center justify-end gap-3 text-slate-400">
                                        <i class="fa-regular fa-eye cursor-pointer hover:text-slate-600 view-details-btn" data-request-id="${r.id}" title="View details"></i>
                                        ${r.is_pending ? `<i class="fa-regular fa-circle-xmark cursor-pointer hover:text-rose-600 cancel-request-btn" data-request-id="${r.id}" title="Cancel request"></i>` : ''}
                                    </div>
                                </td>
                            </tr>`;
                    });
                    html += `</tbody></table></div>`;
                    container.innerHTML = html;
                }

                if (modalsEl) {
                    modalsEl.dataset.requests = JSON.stringify(data.react);
                    window.dispatchEvent(new CustomEvent('maintenance:data-updated', { detail: data.react }));
                }
            } catch (e) { /* silent */ }
        }

        if (window.Echo) {
            window.Echo.channel('tenant.services').listen('.maintenance.updated', () => refresh());
        }
        window.addEventListener('maintenance:refresh', () => refresh());
        setInterval(refresh, 3000);
        refresh();
    })();
</script>
@endpush