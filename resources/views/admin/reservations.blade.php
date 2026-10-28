{{-- resources/views/admin/reservations.blade.php --}}
@extends('layouts.admin', ['active' => 'reservations', 'pageTitle' => 'Reservations'])
@section('title', 'Reservations')

@section('content')
    <div class="p-4 md:p-8 max-w-[1400px] mx-auto space-y-6">

        {{-- Page Header --}}
        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-teal-100 text-teal-600 flex items-center justify-center text-xl">
                    <i class="fa-regular fa-calendar-check"></i>
                </div>
                <div>
                    <h1 class="text-[22px] font-extrabold text-slate-900">Manage Reservations</h1>
                    <p class="text-[13px] text-slate-500 mt-0.5">Review and approve tenant reservation requests</p>
                </div>
            </div>
        </div>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            <div class="bg-white rounded-2xl px-5 py-4 border border-slate-100 shadow-sm">
                <div class="text-[13px] font-semibold text-slate-500">Total Pending</div>
                <div id="metric-total" class="text-[26px] font-extrabold text-slate-900 mt-2">
                    {{ $metrics['pending'] }}</div>
            </div>
            <div class="bg-white rounded-2xl px-5 py-4 border border-slate-100 shadow-sm">
                <div class="text-[13px] font-semibold text-slate-500">Pending Review</div>
                <div id="metric-pending"
                    class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-yellow-100 text-amber-700 text-base font-extrabold mt-2">
                    {{ $metrics['pending'] }}</div>
            </div>
            {{-- We keep total historical counts as a subtle reference, but hide them from the main grid 
                 since the admin cannot act on them here --}}
        </div>

        {{-- Table Card --}}
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 space-y-5">

            {{-- Toolbar --}}
            <div class="flex flex-col md:flex-row md:items-center gap-2.5">
                <div class="relative flex-1">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[11px] pointer-events-none"></i>
                    <input type="text" id="res-search"
                        placeholder="Search by name, email, or room..."
                        class="w-full h-9 pl-8 pr-3 rounded-lg border border-slate-200 bg-slate-50/60 text-[12.5px] text-slate-800 placeholder-slate-400 outline-none transition focus:border-teal-400 focus:bg-white focus:ring-2 focus:ring-teal-100">
                </div>
                <select id="res-status-filter"
                    class="h-9 pl-3 pr-8 rounded-lg border border-slate-200 bg-slate-50/60 text-[12.5px] text-slate-700 outline-none cursor-pointer appearance-none bg-[url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 fill=%22none%22 viewBox=%220 0 24 24%22 stroke=%22%2394a3b8%22 stroke-width=%222%22><path stroke-linecap=%22round%22 stroke-linejoin=%22round%22 d=%22M19 9l-7 7-7-7%22/></svg>')] bg-[length:14px] bg-[right_10px_center] bg-no-repeat transition focus:border-teal-400 focus:bg-white focus:ring-2 focus:ring-teal-100 min-w-[180px]">
                    <option value="">All Pending</option>
                    <option value="Pending">Pending</option>
                </select>
            </div>

            {{-- Table --}}
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-[11px] uppercase tracking-wider font-bold text-slate-400 border-b border-slate-200 whitespace-nowrap">
                            <th class="py-3 px-4">Resident</th>
                            <th class="py-3 px-4">Contact</th>
                            <th class="py-3 px-4">Desired Room</th>
                            <th class="py-3 px-4">Check-in</th>
                            <th class="py-3 px-4">Check-out</th>
                            <th class="py-3 px-4">Reservation Fee</th>
                            <th class="py-3 px-4">Submitted</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="res-tbody">
                        {{-- Rows rendered by JS --}}
                    </tbody>
                </table>
                <div id="res-empty-filter" class="hidden text-center py-12 text-slate-400">
                    <i class="fa-solid fa-magnifying-glass text-4xl mb-3 opacity-50"></i>
                    <p class="text-sm">No pending reservations match your current filters.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- React Modal Mount Point --}}
    <div id="admin-reservations-modals" data-reservations='@json($reservationsForReact)'></div>
@endsection

@push('scripts')
<script>
(function () {
    const DATA_URL = "{{ route('admin.reservations.data') }}";
    const tbody    = document.getElementById('res-tbody');
    const searchInput  = document.getElementById('res-search');
    const statusFilter = document.getElementById('res-status-filter');
    const emptyRow     = document.getElementById('res-empty-filter');
    const modalsEl     = document.getElementById('admin-reservations-modals');

    let allRows = [];
    let currentReact = {};

    function escapeHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, (c) => (
            { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
        ));
    }

    function rowTemplate(r) {
        const checkoutClass = r.check_out_is_null ? 'text-slate-400 italic' : 'text-slate-700';
        const checkoutIcon  = r.check_out_is_null ? 'fa-calendar-xmark' : 'fa-calendar-xmark';
        
        // Only Pending items will reach here based on backend filter
        const actions = `
            <i class="fa-regular fa-circle-check text-emerald-500 text-[15px] cursor-pointer hover:text-emerald-600 transition icon-approve"
                title="Approve" data-res-id="${r.id}"></i>
            <i class="fa-regular fa-circle-xmark text-rose-500 text-[15px] cursor-pointer hover:text-rose-600 transition icon-reject"
                title="Reject" data-res-id="${r.id}"></i>
        `;

        return `
            <tr class="res-row border-b border-slate-50 last:border-0"
                data-id="${r.id}"
                data-status="${escapeHtml(r.status)}"
                data-search="${escapeHtml([
                    r.tenant_name, r.email, r.phone, r.desired_room, r.reference_no
                ].join(' ').toLowerCase())}">
                <td class="py-3.5 px-4">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full ${escapeHtml(r.avatar_bg)} ${escapeHtml(r.avatar_color)} flex items-center justify-center text-[12px] font-bold shrink-0">
                            ${escapeHtml(r.tenant_initial)}
                        </div>
                        <div>
                            <div class="font-semibold text-slate-900 text-[13.5px] tracking-tight">${escapeHtml(r.tenant_name)}</div>
                            <div class="text-[11px] text-slate-400 mt-0.5">${escapeHtml(r.reference_no)}</div>
                        </div>
                    </div>
                </td>
                <td class="py-3.5 px-4">
                    <div class="text-[12.5px] text-slate-700">${escapeHtml(r.email)}</div>
                    <div class="text-[11px] text-slate-400 mt-0.5">${escapeHtml(r.phone)}</div>
                </td>
                <td class="py-3.5 px-4">
                    <div class="text-[12.5px] font-medium text-slate-700">${escapeHtml(r.desired_room)}</div>
                </td>
                <td class="py-3.5 px-4">
                    <div class="flex items-center gap-2 text-[12.5px] text-slate-700">
                        <i class="fa-regular fa-calendar text-slate-400 text-[11px]"></i>
                        ${escapeHtml(r.formatted_check_in)}
                    </div>
                </td>
                <td class="py-3.5 px-4">
                    <div class="flex items-center gap-2 text-[12.5px] ${checkoutClass}">
                        <i class="fa-regular ${checkoutIcon} text-slate-400 text-[11px]"></i>
                        ${escapeHtml(r.formatted_check_out)}
                    </div>
                </td>
                <td class="py-3.5 px-4">
                    <div class="font-bold text-emerald-600 text-[13px] tracking-tight">${escapeHtml(r.formatted_fee)}</div>
                </td>
                <td class="py-3.5 px-4 text-[12.5px] text-slate-600 whitespace-nowrap">${escapeHtml(r.created_at ?? '')}</td>
                <td class="py-3.5 px-4">
                    <span class="inline-flex items-center gap-1.5 ${escapeHtml(r.status_class)} px-2.5 py-1 rounded-full text-[10.5px] font-bold whitespace-nowrap">
                        <i class="${escapeHtml(r.status_icon)}"></i> ${escapeHtml(r.status)}
                    </span>
                </td>
                <td class="py-3.5 px-4">
                    <div class="flex items-center justify-end gap-3">
                        ${actions}
                        <i class="fa-regular fa-eye text-slate-400 text-[15px] cursor-pointer hover:text-slate-900 transition icon-view"
                            title="View Details" data-res-key="${escapeHtml(r.reference_no)}"></i>
                    </div>
                </td>
            </tr>
        `;
    }

    function renderRows() {
        const q      = (searchInput?.value || '').toLowerCase().trim();
        const status = (statusFilter?.value || '').trim();

        let visible = 0;
        const html = allRows.map((r) => {
            const haystack  = `${r.tenant_name ?? ''} ${r.email ?? ''} ${r.phone ?? ''} ${r.desired_room ?? ''} ${r.reference_no ?? ''}`.toLowerCase();
            const searchMatch = !q || haystack.includes(q);
            const statusMatch = !status || r.status === status;
            if (searchMatch && statusMatch) visible++;
            return searchMatch && statusMatch ? rowTemplate(r) : '';
        }).join('');

        tbody.innerHTML = html;
        emptyRow.classList.toggle('hidden', !(visible === 0 && allRows.length > 0));
        bindRowActions();
    }

    function bindRowActions() {
        tbody.querySelectorAll('.icon-approve').forEach((el) => {
            el.onclick = () => window.dispatchEvent(new CustomEvent('reservation:approve', { detail: { id: +el.dataset.resId } }));
        });
        tbody.querySelectorAll('.icon-reject').forEach((el) => {
            el.onclick = () => window.dispatchEvent(new CustomEvent('reservation:reject', { detail: { id: +el.dataset.resId } }));
        });
        tbody.querySelectorAll('.icon-view').forEach((el) => {
            el.onclick = () => window.dispatchEvent(new CustomEvent('reservation:view', { detail: { key: el.dataset.resKey } }));
        });
    }

    function setMetrics(m) {
        document.getElementById('metric-total').textContent    = m.pending;
        document.getElementById('metric-pending').textContent  = m.pending;
    }

    async function refresh() {
        try {
            const res = await fetch(DATA_URL, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (!res.ok) return;
            const data = await res.json();
            
            // Defensive filter: ensure we only render pending items in the table
            allRows = (data.rows || []).filter(r => r.status === 'Pending');
            
            currentReact = data.react || {};
            setMetrics(data.metrics || {});
            renderRows();

            if (modalsEl) {
                modalsEl.dataset.reservations = JSON.stringify(currentReact);
                window.dispatchEvent(new CustomEvent('reservation:data-updated', { detail: currentReact }));
            }
        } catch (e) { /* silent */ }
    }

    // --- filters ---
    let t;
    searchInput?.addEventListener('input', () => { clearTimeout(t); t = setTimeout(renderRows, 120); });
    statusFilter?.addEventListener('change', renderRows);

    // --- polling fallback (every 3s) ---
    let pollTimer = setInterval(refresh, 3000);
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) clearInterval(pollTimer);
        else { refresh(); pollTimer = setInterval(refresh, 3000); }
    });

    // --- Echo real-time ---
    if (window.Echo) {
        try {
            window.Echo.channel('admin.reservations')
                .listen('.reservation.updated', () => refresh());
        } catch (e) { /* Echo unavailable → polling covers it */ }
    }

    // --- initial load ---
    refresh();
})();
</script>
@endpush