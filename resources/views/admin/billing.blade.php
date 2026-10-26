@extends('layouts.admin', ['active' => 'billing', 'pageTitle' => 'Billing & Payments'])
@section('title', 'Billing & Payments')

@section('content')
<div class="p-4 md:p-8 max-w-[1400px] mx-auto space-y-6">

    {{-- Page Title --}}
    <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-sky-100 text-sky-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-credit-card"></i>
            </div>
            <div>
                <h1 class="text-2xl font-extrabold text-slate-900">Billing &amp; Payments</h1>
                <p class="text-[13px] text-slate-500 mt-0.5">Manage invoices and payment verification</p>
            </div>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div class="bg-white rounded-2xl px-6 py-5 border border-slate-100 shadow-sm flex items-center gap-5">
            <div class="w-13 h-13 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-[22px] shrink-0">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div>
                <p class="text-[13px] font-medium text-slate-500">Collected this month</p>
                <h2 id="metric-collected" class="text-[26px] font-extrabold text-slate-900 mt-0.5">₱{{ number_format($metrics['collected'], 0) }}</h2>
            </div>
        </div>
        <div class="bg-white rounded-2xl px-6 py-5 border border-slate-100 shadow-sm flex items-center gap-5">
            <div class="w-13 h-13 rounded-full bg-yellow-100 text-amber-600 flex items-center justify-center text-[22px] shrink-0">
                <i class="fa-regular fa-calendar"></i>
            </div>
            <div>
                <p class="text-[13px] font-medium text-slate-500">Pending Payments</p>
                <h2 id="metric-pending" class="text-[26px] font-extrabold text-slate-900 mt-0.5">₱{{ number_format($metrics['pending'], 0) }}</h2>
            </div>
        </div>
        <div class="bg-white rounded-2xl px-6 py-5 border border-slate-100 shadow-sm flex items-center gap-5">
            <div class="w-13 h-13 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center text-[22px] shrink-0">
                <i class="fa-solid fa-circle-exclamation"></i>
            </div>
            <div>
                <p class="text-[13px] font-medium text-slate-500">Overdue</p>
                <h2 id="metric-overdue" class="text-[26px] font-extrabold text-slate-900 mt-0.5">₱{{ number_format($metrics['overdue'], 0) }}</h2>
            </div>
        </div>
    </div>

    {{-- Table Card --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 space-y-5">
        <div class="flex flex-col md:flex-row md:items-center md:justify-end gap-4">
            <select id="billing-status-filter" class="bg-white border border-slate-300 rounded-[10px] px-4 py-2.5 text-[13px] font-medium text-slate-700 outline-none cursor-pointer min-w-[160px]">
                <option value="">All Statuses</option>
                @foreach($availableStatuses as $s)
                    <option value="{{ $s }}">{{ $s }}</option>
                @endforeach
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="text-[13px] font-bold text-slate-600 border-b border-slate-100 whitespace-nowrap">
                        <th class="py-3 px-4">Transaction No.</th>
                        <th class="py-3 px-4">Tenant / Room</th>
                        <th class="py-3 px-4">Total Amount</th>
                        <th class="py-3 px-4">Amount Paid</th>
                        <th class="py-3 px-4">Balance</th>
                        <th class="py-3 px-4">Due Date</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Actions</th>
                    </tr>
                </thead>
                <tbody id="billing-tbody">{{-- JS renders --}}</tbody>
            </table>
            <div id="billing-empty-filter" class="hidden text-center py-12 text-slate-400">
                <i class="fa-solid fa-magnifying-glass text-4xl mb-3 opacity-50"></i>
                <p class="text-sm">No invoices match your current filters.</p>
            </div>
        </div>
    </div>
</div>

<div id="admin-billing-modals"
     data-invoices="{{ json_encode($invoicesForReact, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) }}"></div>
@endsection

@push('scripts')
<script>
(function () {
    const DATA_URL = "{{ route('admin.billing.data') }}";
    const tbody         = document.getElementById('billing-tbody');
    const statusFilter  = document.getElementById('billing-status-filter');
    const emptyRow      = document.getElementById('billing-empty-filter');
    const modalsEl      = document.getElementById('admin-billing-modals');

    let allRows = [];

    function escapeHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, (c) => (
            { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
        ));
    }

    function rowTemplate(inv) {
        let actions = '';
        if (inv.status === 'Paid') {
            actions = `<i class="fa-solid fa-receipt text-slate-700 text-[15px] cursor-pointer hover:text-orange-500 transition icon-payment" title="View Payment Receipt"></i>`;
        } else if (inv.status === 'Pending') {
            actions = `
                <i class="fa-solid fa-circle-check text-emerald-600 text-[15px] cursor-pointer hover:text-emerald-700 transition icon-verify" title="Verify Payment" data-tn="${escapeHtml(inv.transaction_no)}"></i>
                <i class="fa-solid fa-circle-xmark text-rose-600 text-[15px] cursor-pointer hover:text-rose-700 transition icon-reject" title="Reject Payment" data-tn="${escapeHtml(inv.transaction_no)}"></i>
            `;
        } else {
            actions = `<i class="fa-solid fa-bell text-slate-700 text-[15px] cursor-pointer hover:text-orange-500 transition icon-remind" title="Send Reminder"></i>`;
        }

        return `
            <tr class="billing-row border-b border-slate-50 last:border-0"
                data-tn="${escapeHtml(inv.transaction_no)}"
                data-status="${escapeHtml(inv.status)}">
                <td class="py-4 px-4">
                    <div class="font-semibold text-slate-700 text-[13px]">${escapeHtml(inv.transaction_no)}</div>
                    <div class="text-[11px] text-slate-400 mt-0.5">${escapeHtml(inv.description)}</div>
                </td>
                <td class="py-4 px-4">
                    <div class="font-bold text-slate-900 text-[13px]">${escapeHtml(inv.tenant_name)}</div>
                    <div class="text-[11px] text-slate-400 mt-0.5">${escapeHtml(inv.room_number)}</div>
                </td>
                <td class="py-4 px-4">
                    <div class="font-bold text-slate-900 text-[13px]">${escapeHtml(inv.formatted_total)}</div>
                    <div class="text-[11px] text-slate-400 mt-0.5">${escapeHtml(inv.formatted_rate)}</div>
                </td>
                <td class="py-4 px-4">
                    <div class="font-bold text-slate-900 text-[13px]">${escapeHtml(inv.formatted_paid)}</div>
                    <div class="text-[11px] text-slate-400 mt-0.5">${escapeHtml(inv.formatted_days)}</div>
                </td>
                <td class="py-4 px-4 font-bold text-slate-900 text-[13px]">${escapeHtml(inv.formatted_balance)}</td>
                <td class="py-4 px-4 text-[13px] text-slate-600">${escapeHtml(inv.due_date)}</td>
                <td class="py-4 px-4">
                    <span class="inline-block px-3.5 py-1 rounded-full text-[11px] font-bold ${escapeHtml(inv.status_class)}">
                        ${escapeHtml(inv.status)}
                    </span>
                </td>
                <td class="py-4 px-4">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-pen text-slate-700 text-[15px] cursor-pointer hover:text-orange-500 transition icon-edit" title="Edit Invoice"></i>
                        ${actions}
                        <i class="fa-solid fa-eye text-slate-700 text-[15px] cursor-pointer hover:text-orange-500 transition icon-view" title="View Summary"></i>
                    </div>
                </td>
            </tr>
        `;
    }

    function renderRows() {
        const status = (statusFilter?.value || '').trim();
        let visible = 0;
        const html = allRows.map((inv) => {
            if (!status || inv.status === status) { visible++; return rowTemplate(inv); }
            return '';
        }).join('');
        tbody.innerHTML = html;
        emptyRow.classList.toggle('hidden', !(visible === 0 && allRows.length > 0));
        bindActions();
    }

    function bindActions() {
        tbody.querySelectorAll('.icon-verify').forEach((el) => {
            el.onclick = () => window.dispatchEvent(new CustomEvent('billing:verify', { detail: { tn: el.dataset.tn } }));
        });
        tbody.querySelectorAll('.icon-reject').forEach((el) => {
            el.onclick = () => window.dispatchEvent(new CustomEvent('billing:reject', { detail: { tn: el.dataset.tn } }));
        });
        tbody.querySelectorAll('.icon-edit').forEach((el) => {
            el.onclick = () => window.dispatchEvent(new CustomEvent('billing:edit', { detail: { tn: el.closest('tr').dataset.tn } }));
        });
        tbody.querySelectorAll('.icon-view, .icon-payment').forEach((el) => {
            el.onclick = () => window.dispatchEvent(new CustomEvent('billing:view', { detail: { tn: el.closest('tr').dataset.tn } }));
        });
        tbody.querySelectorAll('.icon-remind').forEach((el) => {
            el.onclick = () => window.dispatchEvent(new CustomEvent('billing:remind', { detail: { tn: el.closest('tr').dataset.tn } }));
        });
    }

    function setMetrics(m) {
        document.getElementById('metric-collected').textContent = '₱' + Number(m.collected || 0).toLocaleString();
        document.getElementById('metric-pending').textContent   = '₱' + Number(m.pending || 0).toLocaleString();
        document.getElementById('metric-overdue').textContent   = '₱' + Number(m.overdue || 0).toLocaleString();
    }

    async function refresh() {
        try {
            const qs = statusFilter?.value ? '?status=' + encodeURIComponent(statusFilter.value) : '';
            const res = await fetch(DATA_URL + qs, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (!res.ok) return;
            const data = await res.json();
            allRows = data.rows || [];
            setMetrics(data.metrics || {});
            renderRows();

            if (modalsEl) {
                modalsEl.dataset.invoices = JSON.stringify(data.react || {});
                window.dispatchEvent(new CustomEvent('billing:data-updated', { detail: data.react || {} }));
            }
        } catch (e) { /* silent */ }
    }

    statusFilter?.addEventListener('change', () => { renderRows(); refresh(); });

    let pollTimer = setInterval(refresh, 15000);
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) clearInterval(pollTimer);
        else { refresh(); pollTimer = setInterval(refresh, 15000); }
    });

    if (window.Echo) {
        try {
            window.Echo.channel('admin.billing')
                .listen('.invoice.updated', () => refresh());
        } catch (e) {}
    }

    refresh();
})();
</script>
@endpush