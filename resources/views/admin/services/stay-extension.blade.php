@extends('layouts.admin', ['active' => 'services.stay-extension', 'pageTitle' => 'Services'])
@section('title', 'Stay Extension Requests')

@section('content')
@php
    // Hide finalized statuses (Approved & Rejected)
    $activeRequests = $requests->filter(function ($req) {
        return $req->status === 'Pending';
    })->values();
@endphp

<div class="p-4 md:p-8 max-w-[1400px] mx-auto space-y-5">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center text-lg">
                <i class="fa-regular fa-calendar-plus"></i>
            </div>
            <div>
                <h2 class="text-[22px] font-extrabold text-slate-900">Stay Extension Requests</h2>
                <p class="text-[13px] text-slate-500 mt-0.5">Manage pending tenant lease extension applications</p>
            </div>
        </div>
        <div class="flex items-center gap-2 text-[11px] font-semibold text-emerald-600">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <span>Live</span>
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl px-5 py-4 border border-slate-200 shadow-sm">
            <span class="text-[13px] text-slate-500 font-medium">Total</span>
            <div id="metric-total" class="text-[22px] font-bold text-slate-900 mt-2">{{ $metrics['total'] }}</div>
        </div>
        <div class="bg-white rounded-2xl px-5 py-4 border border-slate-200 shadow-sm">
            <span class="text-[13px] text-slate-500 font-medium">Pending</span>
            <div class="mt-2"><div id="metric-pending" class="w-7 h-7 rounded-full bg-yellow-100 text-amber-700 flex items-center justify-center text-[13px] font-bold">{{ $metrics['pending'] }}</div></div>
        </div>
        <div class="bg-white rounded-2xl px-5 py-4 border border-slate-200 shadow-sm">
            <span class="text-[13px] text-slate-500 font-medium">Approved</span>
            <div class="mt-2"><div id="metric-approved" class="w-7 h-7 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-[13px] font-bold">{{ $metrics['approved'] }}</div></div>
        </div>
        <div class="bg-white rounded-2xl px-5 py-4 border border-slate-200 shadow-sm">
            <span class="text-[13px] text-slate-500 font-medium">Rejected</span>
            <div class="mt-2"><div id="metric-rejected" class="w-7 h-7 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center text-[13px] font-bold">{{ $metrics['rejected'] }}</div></div>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-slate-50 text-[11px] font-bold text-slate-500 uppercase tracking-wide border-b border-slate-200">
                        <th class="py-3.5 px-5">Resident</th>
                        <th class="py-3.5 px-5">Current Lease End</th>
                        <th class="py-3.5 px-5">Requested End</th>
                        <th class="py-3.5 px-5">Extension</th>
                        <th class="py-3.5 px-5">Status</th>
                        <th class="py-3.5 px-5"></th>
                    </tr>
                </thead>
                <tbody id="stay-extension-tbody"></tbody>
            </table>
        </div>
    </div>
</div>

<div id="admin-stay-extension-modals" data-requests='@json($requestsForReact)'></div>
@endsection

@push('scripts')
<script>
    (function () {
        const DATA_URL = "{{ route('admin.services.stay-extension.data') }}";
        const tbody = document.getElementById('stay-extension-tbody');
        const modalsEl = document.getElementById('admin-stay-extension-modals');

        async function refresh() {
            try {
                const res = await fetch(DATA_URL, { headers: { 'Accept': 'application/json' } });
                if (!res.ok) return;
                const data = await res.json();

                for (const [key, val] of Object.entries(data.metrics)) {
                    const el = document.getElementById(`metric-${key}`);
                    if (el) el.textContent = val;
                }

                const active = data.rows.filter(r => r.is_pending);
                let html = '';

                if (active.length === 0) {
                    html = `<tr><td colspan="6" class="text-center py-12 text-slate-400"><i class="fa-solid fa-inbox text-4xl mb-3"></i><p class="text-sm">No pending stay extension requests found.</p></td></tr>`;
                } else {
                    active.forEach(r => {
                        html += `
                            <tr class="border-b border-slate-50 last:border-0">
                                <td class="py-4 px-5">
                                    <div class="text-sm font-bold text-slate-900">${r.tenant_name}</div>
                                    <div class="text-xs text-slate-400 mt-0.5">${r.room_number}</div>
                                </td>
                                <td class="py-4 px-5">
                                    <div class="flex items-center gap-2 text-[13px] text-slate-700">
                                        <i class="fa-regular fa-calendar text-slate-400 text-[13px]"></i> ${r.current_lease_end}
                                    </div>
                                </td>
                                <td class="py-4 px-5">
                                    <div class="flex items-center gap-2 text-[13px] text-purple-700 font-semibold">
                                        <i class="fa-regular fa-calendar text-purple-600 text-[13px]"></i> ${r.requested_lease_end}
                                    </div>
                                </td>
                                <td class="py-4 px-5">
                                    <span class="inline-block bg-purple-100 text-purple-700 text-[11px] font-bold px-2.5 py-1 rounded-xl">${r.extension_label}</span>
                                </td>
                                <td class="py-4 px-5">
                                    <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold ${r.status_class}">${r.status}</span>
                                </td>
                                <td class="py-4 px-5">
                                    <div class="flex items-center justify-end gap-3 text-slate-400 text-sm">
                                        <i class="fa-regular fa-circle-check text-emerald-500 cursor-pointer hover:text-emerald-600 row-approve-btn" data-request-id="${r.id}" title="Approve"></i>
                                        <i class="fa-regular fa-circle-xmark text-rose-500 cursor-pointer hover:text-rose-600 row-reject-btn" data-request-id="${r.id}" title="Reject"></i>
                                        <i class="fa-regular fa-eye cursor-pointer hover:text-slate-700 open-details-btn" data-request-id="${r.id}" title="View Details"></i>
                                    </div>
                                </td>
                            </tr>
                        `;
                    });
                }
                tbody.innerHTML = html;

                if (modalsEl) {
                    modalsEl.dataset.requests = JSON.stringify(data.rows);
                    window.dispatchEvent(new CustomEvent('stay-extension:data-updated', { detail: data.rows }));
                }
            } catch (e) { /* silent */ }
        }

        if (window.Echo) {
            window.Echo.channel('admin.services').listen('.stay-extension.updated', () => refresh());
        }
        setInterval(refresh, 3000);
        refresh();
    })();
</script>
@endpush