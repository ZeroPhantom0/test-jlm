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
                <div
                    class="w-13 h-13 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-[22px] shrink-0">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <div>
                    <p class="text-[13px] font-medium text-slate-500">Collected this month</p>
                    <h2 id="metric-collected" class="text-[26px] font-extrabold text-slate-900 mt-0.5">
                        ₱{{ number_format($metrics['collected'], 0) }}</h2>
                </div>
            </div>
            <div class="bg-white rounded-2xl px-6 py-5 border border-slate-100 shadow-sm flex items-center gap-5">
                <div
                    class="w-13 h-13 rounded-full bg-yellow-100 text-amber-600 flex items-center justify-center text-[22px] shrink-0">
                    <i class="fa-regular fa-calendar"></i>
                </div>
                <div>
                    <p class="text-[13px] font-medium text-slate-500">Pending Payments</p>
                    <h2 id="metric-pending" class="text-[26px] font-extrabold text-slate-900 mt-0.5">
                        ₱{{ number_format($metrics['pending'], 0) }}</h2>
                </div>
            </div>
            <div class="bg-white rounded-2xl px-6 py-5 border border-slate-100 shadow-sm flex items-center gap-5">
                <div
                    class="w-13 h-13 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center text-[22px] shrink-0">
                    <i class="fa-solid fa-circle-exclamation"></i>
                </div>
                <div>
                    <p class="text-[13px] font-medium text-slate-500">Overdue</p>
                    <h2 id="metric-overdue" class="text-[26px] font-extrabold text-slate-900 mt-0.5">
                        ₱{{ number_format($metrics['overdue'], 0) }}</h2>
                </div>
            </div>
        </div>

        {{-- Table Card --}}
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 space-y-5">
            <div class="flex flex-col md:flex-row md:items-center md:justify-end gap-4">
                <select id="billing-status-filter"
                    class="bg-white border border-slate-300 rounded-[10px] px-4 py-2.5 text-[13px] font-medium text-slate-700 outline-none cursor-pointer min-w-[160px]">
                    <option value="">All Statuses</option>
                    @foreach ($availableStatuses as $s)
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
                    <tbody id="billing-tbody">
                        @forelse($invoices as $inv)
                            <tr class="border-b border-slate-100 last:border-0" data-tn="{{ $inv->transaction_no }}">
                                <td class="py-3.5 px-4 text-[13px] font-semibold text-slate-800">{{ $inv->transaction_no }}
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="text-[13px] text-slate-600 font-medium">{{ $inv->tenant_name }}</div>
                                    <div class="text-[11px] text-slate-400 mt-0.5">Room {{ $inv->room_number }}</div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="text-[13px] font-bold text-slate-800">{{ $inv->formatted_total }}</div>
                                    <div class="text-[11px] text-slate-400 mt-0.5">{{ $inv->formatted_rate }}</div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="text-[13px] text-emerald-600 font-semibold">{{ $inv->formatted_paid }}
                                    </div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="text-[13px] font-bold text-rose-600">{{ $inv->formatted_balance }}</div>
                                </td>
                                <td class="py-3.5 px-4 text-[13px] text-slate-600">
                                    {{ $inv->due_date?->format('M d, Y') ?? '-' }}</td>
                                <td class="py-3.5 px-4">
                                    <span
                                        class="inline-block px-3 py-1 rounded-full text-[11px] font-bold {{ $inv->status_class }}">
                                        {{ $inv->status }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        @if ($inv->status === 'Pending')
                                            <i class="fa-solid fa-circle-check text-emerald-600 text-[15px] cursor-pointer hover:text-emerald-800 transition icon-verify"
                                                title="Verify Payment" data-tn="{{ $inv->transaction_no }}"></i>
                                            <i class="fa-solid fa-circle-xmark text-rose-500 text-[15px] cursor-pointer hover:text-rose-700 transition icon-reject"
                                                title="Reject Payment" data-tn="{{ $inv->transaction_no }}"></i>
                                        @else
                                            <i class="fa-solid fa-bell text-slate-700 text-[15px] cursor-pointer hover:text-orange-500 transition icon-remind"
                                                title="Send Reminder" data-tn="{{ $inv->transaction_no }}"></i>
                                        @endif
                                        
                                        <i class="fa-solid fa-pen text-slate-700 text-[15px] cursor-pointer hover:text-orange-500 transition icon-edit"
                                            title="Edit Invoice" data-tn="{{ $inv->transaction_no }}"></i>
                                        <i class="fa-solid fa-eye text-slate-700 text-[15px] cursor-pointer hover:text-orange-500 transition icon-view"
                                            title="View Summary" data-tn="{{ $inv->transaction_no }}"></i>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-8 text-slate-400">
                                    <i class="fa-solid fa-receipt text-3xl mb-3 block opacity-40"></i>
                                    <p class="text-sm font-medium">No invoices found.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <div id="billing-empty-filter" class="hidden text-center py-12 text-slate-400">
                    <i class="fa-solid fa-magnifying-glass text-4xl mb-3 opacity-50"></i>
                    <p class="text-sm">No invoices match your current filters.</p>
                </div>
            </div>
        </div>
    </div>

    <div id="admin-billing-modals"
        data-invoices="{{ json_encode($invoicesForReact, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) }}">
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        window.addEventListener('load', () => {
            const csrf = () =>
                document.querySelector('meta[name="csrf-token"]')?.content ||
                document.querySelector('input[name="_token"]')?.value;

            async function refreshBilling() {
                try {
                    const res = await fetch('/admin/billing/data', {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                        cache: 'no-store'
                    });
                    
                    if (!res.ok) return;
                    const data = await res.json();

                    // Update Metrics Cards
                    const elCollected = document.getElementById('metric-collected');
                    if (elCollected && data.metrics) {
                        elCollected.textContent = '₱' + Number(data.metrics.collected).toLocaleString();
                    }
                    const elPending = document.getElementById('metric-pending');
                    if (elPending && data.metrics) {
                        elPending.textContent = '₱' + Number(data.metrics.pending).toLocaleString();
                    }
                    const elOverdue = document.getElementById('metric-overdue');
                    if (elOverdue && data.metrics) {
                        elOverdue.textContent = '₱' + Number(data.metrics.overdue).toLocaleString();
                    }

                    // Update Table Rows
                    const tbody = document.getElementById('billing-tbody');
                    if (tbody && data.rows) {
                        if (data.rows.length === 0) {
                            tbody.innerHTML = `
                                <tr>
                                    <td colspan="8" class="text-center py-8 text-slate-400">
                                        <i class="fa-solid fa-receipt text-3xl mb-3 block opacity-40"></i>
                                        <p class="text-sm font-medium">No invoices found.</p>
                                    </td>
                                </tr>
                            `;
                        } else {
                            tbody.innerHTML = data.rows.map(inv => `
                                <tr class="border-b border-slate-100 last:border-0" data-tn="${inv.transaction_no}">
                                    <td class="py-3.5 px-4 text-[13px] font-semibold text-slate-800">${inv.transaction_no}</td>
                                    <td class="py-3.5 px-4">
                                        <div class="text-[13px] text-slate-600 font-medium">${inv.tenant_name}</div>
                                        <div class="text-[11px] text-slate-400 mt-0.5">Room ${inv.room_number}</div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="text-[13px] font-bold text-slate-800">${inv.formatted_total}</div>
                                        <div class="text-[11px] text-slate-400 mt-0.5">${inv.formatted_rate}</div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="text-[13px] text-emerald-600 font-semibold">${inv.formatted_paid}</div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="text-[13px] font-bold text-rose-600">${inv.formatted_balance}</div>
                                    </td>
                                    <td class="py-3.5 px-4 text-[13px] text-slate-600">${inv.due_date}</td>
                                    <td class="py-3.5 px-4">
                                        <span class="inline-block px-3 py-1 rounded-full text-[11px] font-bold ${inv.status_class}">
                                            ${inv.status}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="flex items-center gap-3">
                                            ${inv.status === 'Pending' ? `
                                                <i class="fa-solid fa-circle-check text-emerald-600 text-[15px] cursor-pointer hover:text-emerald-800 transition icon-verify" title="Verify Payment" data-tn="${inv.transaction_no}"></i>
                                                <i class="fa-solid fa-circle-xmark text-rose-500 text-[15px] cursor-pointer hover:text-rose-700 transition icon-reject" title="Reject Payment" data-tn="${inv.transaction_no}"></i>
                                            ` : `
                                                <i class="fa-solid fa-bell text-slate-700 text-[15px] cursor-pointer hover:text-orange-500 transition icon-remind" title="Send Reminder" data-tn="${inv.transaction_no}"></i>
                                            `}
                                            
                                            <i class="fa-solid fa-pen text-slate-700 text-[15px] cursor-pointer hover:text-orange-500 transition icon-edit" title="Edit Invoice" data-tn="${inv.transaction_no}"></i>
                                            <i class="fa-solid fa-eye text-slate-700 text-[15px] cursor-pointer hover:text-orange-500 transition icon-view" title="View Summary" data-tn="${inv.transaction_no}"></i>
                                        </div>
                                    </td>
                                </tr>
                            `).join('');
                        }
                    }

                    // Update React Modal's data attribute
                    const modalRoot = document.getElementById('admin-billing-modals');
                    if (modalRoot && data.react) {
                        modalRoot.dataset.invoices = JSON.stringify(data.react);
                        window.dispatchEvent(new CustomEvent('billing:data-updated', { detail: data.react }));
                    }

                } catch (e) {
                    console.error('Live refresh failed:', e);
                }
            }

            // Event Delegation for Row Actions
            const tbody = document.getElementById('billing-tbody');
            if (tbody) {
                tbody.addEventListener('click', (e) => {
                    const icon = e.target.closest('i[class*="fa-"]');
                    if (!icon) return;

                    let type = null;
                    if (icon.classList.contains('icon-edit')) type = 'edit';
                    if (icon.classList.contains('icon-remind')) type = 'remind';
                    if (icon.classList.contains('icon-view')) type = 'view';
                    if (icon.classList.contains('icon-verify')) type = 'verify';
                    if (icon.classList.contains('icon-reject')) type = 'reject';

                    if (type) {
                        window.dispatchEvent(new CustomEvent(`billing:${type}`, {
                            detail: { tn: icon.dataset.tn }
                        }));
                    }
                });
            }

            // Real-time listeners
            if (window.Echo) {
                window.Echo.private('admin.billing')
                    .listen('.invoice.updated', () => {
                        const modalRoot = document.getElementById('admin-billing-modals');
                        if (modalRoot && modalRoot.dataset.saving === 'true') return;

                        console.log('[Live] Invoice updated, refreshing...');
                        setTimeout(() => refreshBilling(), 600);
                    });
            }

            window.addEventListener('billing:refresh', refreshBilling);
        });
    })();
</script>
@endpush