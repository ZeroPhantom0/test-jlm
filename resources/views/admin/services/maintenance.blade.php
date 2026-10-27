{{-- resources/views/admin/services/maintenance.blade.php --}}
@extends('layouts.admin', ['active' => 'services.maintenance', 'pageTitle' => 'Services'])
@section('title', 'Maintenance Requests')

@section('content')
@php
    // Hide finalized statuses (Completed & Cancelled)
    $activeRequests = $requests->filter(function ($req) {
        return !in_array($req->status, ['Completed', 'Cancelled']);
    })->values();
@endphp

<div class="p-4 md:p-8 max-w-[1400px] mx-auto space-y-5">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <div class="w-11 h-11 rounded-xl bg-orange-100 text-orange-600 flex items-center justify-center text-lg">
                <i class="fa-solid fa-wrench"></i>
            </div>
            <div>
                <h2 class="text-[22px] font-extrabold text-slate-900">Maintenance Requests</h2>
                <p class="text-[13px] text-slate-500 mt-0.5">Track and resolve active maintenance issues submitted by tenants</p>
            </div>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl px-5 py-4 border border-slate-200 shadow-sm">
            <span class="text-[13px] text-slate-500 font-medium">Total</span>
            <div id="metric-total" class="text-[22px] font-bold text-slate-900 mt-2">{{ $metrics['total'] }}</div>
        </div>
        <div class="bg-white rounded-2xl px-5 py-4 border border-slate-200 shadow-sm">
            <span class="text-[13px] text-slate-500 font-medium">Pending</span>
            <div class="mt-2">
                <div id="metric-pending" class="w-7 h-7 rounded-full bg-yellow-100 text-amber-700 flex items-center justify-center text-[13px] font-bold">{{ $metrics['pending'] }}</div>
            </div>
        </div>
        <div class="bg-white rounded-2xl px-5 py-4 border border-slate-200 shadow-sm">
            <span class="text-[13px] text-slate-500 font-medium">In Progress</span>
            <div class="mt-2">
                <div id="metric-in_progress" class="w-7 h-7 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-[13px] font-bold">{{ $metrics['in_progress'] }}</div>
            </div>
        </div>
        <div class="bg-white rounded-2xl px-5 py-4 border border-slate-200 shadow-sm">
            <span class="text-[13px] text-slate-500 font-medium">Completed</span>
            <div class="mt-2">
                <div id="metric-completed" class="w-7 h-7 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-[13px] font-bold">{{ $metrics['completed'] }}</div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-slate-50 text-[11px] font-bold text-slate-500 tracking-wide uppercase border-b border-slate-200">
                    <th class="py-3.5 px-5 w-[28%]">Request</th>
                    <th class="py-3.5 px-5 w-[22%]">Room / Tenant</th>
                    <th class="py-3.5 px-5 w-[18%]">Assigned</th>
                    <th class="py-3.5 px-5 w-[14%]">Status</th>
                    <th class="py-3.5 px-5 w-[12%]">Date</th>
                    <th class="py-3.5 px-5 w-[6%]"></th>
                </tr>
            </thead>
            <tbody id="maintenance-tbody"></tbody>
        </table>
    </div>
</div>

<div id="admin-services-modals" data-requests='@json($requests)'></div>
@endsection

@push('scripts')
<script>
    (function () {
        const DATA_URL = "{{ route('admin.services.maintenance.data') }}";
        const tbody = document.getElementById('maintenance-tbody');
        const modalsEl = document.getElementById('admin-services-modals');

        async function refresh() {
            try {
                const res = await fetch(DATA_URL, { headers: { 'Accept': 'application/json' } });
                if (!res.ok) return;
                const data = await res.json();

                // Update metrics
                for (const [key, val] of Object.entries(data.metrics)) {
                    const el = document.getElementById(`metric-${key}`);
                    if (el) el.textContent = val;
                }

                // Filter active requests
                const active = data.rows.filter(r => !r.is_finalized);
                let html = '';

                if (active.length === 0) {
                    html = `<tr><td colspan="6" class="py-12 text-center text-slate-400 text-[13px]"><i class="fa-solid fa-inbox text-3xl mb-3 block opacity-30"></i>No active maintenance requests found.</td></tr>`;
                } else {
                    active.forEach(r => {
                        const staff = r.assigned_staff ? `<span class="text-slate-600 font-medium text-[13px]">${r.assigned_staff}</span>` : `<span class="text-slate-400 italic text-[13px]">Unassigned</span>`;
                        html += `
                            <tr class="border-b border-slate-50 last:border-0">
                                <td class="py-4 px-5">
                                    <div class="font-bold text-slate-900 text-sm">${r.title}</div>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-[10px] font-extrabold px-1.5 py-0.5 rounded uppercase tracking-wide ${r.priority_class}">${r.priority}</span>
                                        <span class="text-[12px] text-slate-400">${r.category}</span>
                                    </div>
                                </td>
                                <td class="py-4 px-5">
                                    <div class="font-semibold text-slate-700 text-[13px]">${r.room_number}</div>
                                    <div class="text-xs text-slate-400 mt-0.5">${r.tenant_name}</div>
                                </td>
                                <td class="py-4 px-5">${staff}</td>
                                <td class="py-4 px-5"><span class="inline-block px-3 py-1 rounded-full text-xs font-semibold ${r.status_class}">${r.status}</span></td>
                                <td class="py-4 px-5 text-[13px] text-slate-500">${r.date}</td>
                                <td class="py-4 px-5">
                                    <button data-request-id="${r.id}" data-request='${JSON.stringify(r)}' class="text-emerald-600 hover:text-emerald-700 text-[13px] font-semibold inline-flex items-center gap-1.5 admin-view-request-btn">
                                        <i class="fa-regular fa-eye"></i> View
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                }
                tbody.innerHTML = html;

                // Update React data
                if (modalsEl) {
                    modalsEl.dataset.requests = JSON.stringify(data.rows);
                    window.dispatchEvent(new CustomEvent('maintenance:data-updated', { detail: data.rows }));
                }
            } catch (e) { /* silent */ }
        }

        if (window.Echo) {
            window.Echo.channel('admin.services').listen('.maintenance.updated', () => refresh());
        }

        window.addEventListener('maintenance:refresh', () => refresh());
        
        setInterval(refresh, 3000);
        refresh();
    })();
</script>
@endpush