@extends('layouts.tenant', ['active' => 'history', 'pageTitle' => 'History'])
@section('title', 'History')

@section('content')

@php
    /* ============================================================
       HISTORY ITEMS
       ============================================================ */
    $historyItems = $historyItems ?? [
        [
            'key' => 'pay-1', 'cat' => 'payments', 'icon' => 'fa-solid fa-peso-sign',
            'color' => 'green', 'title' => 'Rent - Feb 2026',
            'desc' => 'Payment of ₱500 via over the counter',
            'date' => 'Sep 14, 2026 · 09:01 PM', 'amount' => '₱500', 'status' => 'paid',
            'details' => [
                ['Invoice ID',       'inv-105'],
                ['Unit',             '101'],
                ['Amount',           '₱500'],
                ['Payment Method',   'OVER THE COUNTER'],
                ['Reference Number', 'OTC-20260215-0018'],
                ['Paid Date',        'Sep 14, 2026'],
            ],
            'receipt' => [
                'reference'   => 'OTC-20260215-0018',
                'unit'        => '101',
                'description' => 'Rent - Feb 2026',
                'amount'      => '₱500',
                'method'      => 'Over The Counter',
                'paid_date'   => 'Sep 14, 2026',
                'tenant'      => 'Elle Dela Cruz',
            ],
        ],
        [
            'key' => 'mt-1', 'cat' => 'maintenance', 'icon' => 'fa-solid fa-wrench',
            'color' => 'green', 'title' => 'Clogged Shower Drain',
            'desc' => 'Bathroom shower draining very slowly.',
            'date' => 'Mar 27, 2026 · 03:50 PM', 'amount' => null, 'status' => 'completed',
            'details' => [
                ['Request ID',    'sr-6'],
                ['Unit',          '101'],
                ['Category',      'PLUMBING'],
                ['Priority',      'MEDIUM'],
                ['Assigned Staff','Bob Building Staff'],
                ['Created At',    'Mar 27, 2026'],
            ],
        ],
        [
            'key' => 'pay-2', 'cat' => 'payments', 'icon' => 'fa-solid fa-peso-sign',
            'color' => 'green', 'title' => 'Rent - February 2026',
            'desc' => 'Payment of ₱4,500 via gcash',
            'date' => 'Mar 14, 2026 · 03:50 PM', 'amount' => '₱4,500', 'status' => 'paid',
            'details' => [
                ['Invoice ID',       'inv-106'],
                ['Unit',             '101'],
                ['Amount',           '₱4,500'],
                ['Payment Method',   'GCASH'],
                ['Reference Number', 'GC-20260205-4521'],
                ['Paid Date',        'Mar 14, 2026'],
            ],
            'receipt' => [
                'reference'   => 'GC-20260205-4521',
                'unit'        => '101',
                'description' => 'Rent - February 2026',
                'amount'      => '₱4,500',
                'method'      => 'GCash',
                'paid_date'   => 'Mar 14, 2026',
                'tenant'      => 'Elle Dela Cruz',
            ],
        ],
        [
            'key' => 'mt-2', 'cat' => 'maintenance', 'icon' => 'fa-solid fa-wrench',
            'color' => 'green', 'title' => 'Paint Touch-up Request',
            'desc' => 'Wall paint chipped near entrance.',
            'date' => 'Feb 10, 2026 · 03:50 PM', 'amount' => null, 'status' => 'cancelled',
            'details' => [
                ['Request ID',    'sr-9'],
                ['Unit',          '101'],
                ['Category',      'GENERAL'],
                ['Priority',      'LOW'],
                ['Assigned Staff','N/A'],
                ['Created At',    'Feb 10, 2026'],
            ],
        ],
        [
            'key' => 'rc-1', 'cat' => 'room-changes', 'icon' => 'fa-solid fa-house',
            'color' => 'blue', 'title' => 'Room Change: 103 → 101',
            'desc' => 'Wanted unit with better natural light.',
            'date' => 'Oct 23, 2025 · 03:50 PM', 'amount' => null, 'status' => 'approved',
            'details' => [
                ['Request ID', 'rc-4'],
                ['From Room',  '103'],
                ['To Room',    '101'],
                ['Status',     'APPROVED'],
                ['Notes',      'Approved. Moved to Unit 101 successfully. Transfer completed on Feb 15, 2026.'],
                ['Requested At','Oct 23, 2025'],
            ],
        ],
        [
            'key' => 'rc-2', 'cat' => 'room-changes', 'icon' => 'fa-solid fa-house',
            'color' => 'red', 'title' => 'Room Change: 103 → 201',
            'desc' => 'Requested higher floor for less noise.',
            'date' => 'Oct 03, 2025 · 03:50 PM', 'amount' => null, 'status' => 'rejected',
            'details' => [
                ['Request ID', 'rc-5'],
                ['From Room',  '103'],
                ['To Room',    '201'],
                ['Status',     'REJECTED'],
                ['Notes',      'Rejected. Unit 201 was already occupied at the time.'],
                ['Requested At','Oct 03, 2025'],
            ],
        ],
        [
            'key' => 'se-1', 'cat' => 'stay-extensions', 'icon' => 'fa-regular fa-calendar-check',
            'color' => 'blue', 'title' => 'Stay Extension - Unit 101',
            'desc' => 'Work contract extended for 3 more months.',
            'date' => 'Nov 02, 2025 · 03:50 PM', 'amount' => null, 'status' => 'approved',
            'details' => [
                ['Request ID',           'se-3'],
                ['Unit',                 '101'],
                ['Current Lease End',    'Nov 22, 2025'],
                ['Requested Lease End',  'Feb 20, 2026'],
                ['Status',               'APPROVED'],
                ['Notes',                'Approved. Extension granted until March 2026.'],
            ],
        ],
        [
            'key' => 'se-2', 'cat' => 'stay-extensions', 'icon' => 'fa-regular fa-calendar-check',
            'color' => 'red', 'title' => 'Stay Extension - Unit 103',
            'desc' => 'Requested short extension while house hunting.',
            'date' => 'Jun 05, 2025 · 03:50 PM', 'amount' => null, 'status' => 'rejected',
            'details' => [
                ['Request ID',           'se-5'],
                ['Unit',                 '103'],
                ['Current Lease End',    'Jun 25, 2025'],
                ['Requested Lease End',  'Aug 24, 2025'],
                ['Status',               'REJECTED'],
                ['Notes',                'Rejected. Unit already pre-booked for next tenant.'],
            ],
        ],
        [
            'key' => 'rsv-1', 'cat' => 'reservation', 'icon' => 'fa-regular fa-calendar-check',
            'color' => 'blue', 'title' => 'Reservation - Unit 101',
            'desc' => 'Solo Room reservation approved',
            'date' => 'Mar 22, 2025 · 03:50 PM', 'amount' => '₱4,500', 'status' => 'approved',
            'details' => [
                ['Reservation ID',   'rsv-6'],
                ['Tenant Name',      'Elle Dela Cruz'],
                ['Unit Requested',   '101'],
                ['Room Type',        'Solo Room'],
                ['Deposit Amount',   '₱4,500'],
                ['Reservation Date', 'Apr 06, 2025'],
                ['Request Date',     'Mar 22, 2025'],
                ['Status',           'APPROVED'],
                ['Notes',            'Approved. Successfully moved in on Jan 15, 2023.'],
            ],
        ],
        [
            'key' => 'rsv-2', 'cat' => 'reservation', 'icon' => 'fa-regular fa-calendar-check',
            'color' => 'red', 'title' => 'Reservation - Unit 201',
            'desc' => 'Solo Room reservation rejected',
            'date' => 'Mar 02, 2025 · 03:50 PM', 'amount' => '₱4,800', 'status' => 'rejected',
            'details' => [
                ['Reservation ID',   'rsv-7'],
                ['Tenant Name',      'Elle Dela Cruz'],
                ['Unit Requested',   '201'],
                ['Room Type',        'Solo Room'],
                ['Deposit Amount',   '₱4,800'],
                ['Reservation Date', 'Mar 17, 2025'],
                ['Request Date',     'Mar 02, 2025'],
                ['Status',           'REJECTED'],
                ['Notes',            'Rejected. Unit 201 was already reserved by another tenant.'],
            ],
        ],
        [
            'key' => 'rsv-3', 'cat' => 'reservation', 'icon' => 'fa-regular fa-calendar-check',
            'color' => 'yellow', 'title' => 'Reservation - Unit 103',
            'desc' => 'Bedspace reservation expired',
            'date' => 'Oct 03, 2024 · 03:50 PM', 'amount' => '₱2,500', 'status' => 'expired',
            'details' => [
                ['Reservation ID',   'rsv-8'],
                ['Tenant Name',      'Elle Dela Cruz'],
                ['Unit Requested',   '103'],
                ['Room Type',        'Bedspace'],
                ['Deposit Amount',   '₱2,500'],
                ['Reservation Date', 'Oct 18, 2024'],
                ['Request Date',     'Oct 03, 2024'],
                ['Status',           'EXPIRED'],
                ['Notes',            'Expired. Applicant did not respond within 7 days.'],
            ],
        ],
        [
            'key' => 'pay-3', 'cat' => 'payments', 'icon' => 'fa-solid fa-peso-sign',
            'color' => 'green', 'title' => 'Security Deposit',
            'desc' => 'Payment of ₱10,000 via bank transfer',
            'date' => 'Sep 15, 2024 · 10:15 AM', 'amount' => '₱10,000', 'status' => 'paid',
            'details' => [
                ['Invoice ID',       'inv-101'],
                ['Unit',             '101'],
                ['Amount',           '₱10,000'],
                ['Payment Method',   'BANK TRANSFER'],
                ['Reference Number', 'BT-20240915-8812'],
                ['Paid Date',        'Sep 15, 2024'],
            ],
            'receipt' => [
                'reference'   => 'BT-20240915-8812',
                'unit'        => '101',
                'description' => 'Security Deposit',
                'amount'      => '₱10,000',
                'method'      => 'Bank Transfer',
                'paid_date'   => 'Sep 15, 2024',
                'tenant'      => 'Elle Dela Cruz',
            ],
        ],
    ];

    /* Build a map keyed by item.key → passed to the React modal via data-items */
    $itemsMap = collect($historyItems)->keyBy('key')->toArray();

    $badges = [
        'paid'      => ['bg-green-100 text-green-700',   'fa-regular fa-circle-check'],
        'completed' => ['bg-green-100 text-green-700',   'fa-regular fa-circle-check'],
        'approved'  => ['bg-blue-100 text-blue-600',     'fa-regular fa-circle-check'],
        'cancelled' => ['bg-slate-100 text-slate-600 border border-slate-200', 'fa-regular fa-circle-xmark'],
        'rejected'  => ['bg-red-100 text-red-600',       'fa-regular fa-circle-xmark'],
        'expired'   => ['bg-amber-100 text-amber-600',   'fa-regular fa-clock'],
    ];

    $colorClasses = [
        'green'  => 'bg-green-100 text-emerald-500',
        'blue'   => 'bg-blue-100 text-blue-500',
        'red'    => 'bg-red-100 text-red-500',
        'yellow' => 'bg-amber-100 text-amber-600',
    ];
@endphp

{{-- React mount point for History modals --}}
<div id="tenant-history-modals" data-items='@json($itemsMap)'></div>

<div class="p-4 md:p-8 max-w-6xl mx-auto">

    {{-- Page header --}}
    <div class="flex items-center gap-2.5 text-xl font-extrabold text-slate-900 mb-1">
        <i class="fa-solid fa-bolt-lightning text-emerald-500 text-lg"></i>
        <span>Activity History</span>
    </div>
    <p class="text-[13px] text-slate-500 mb-6">Your complete transaction and service history</p>

    {{-- Summary cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-[#022567] text-white rounded-xl p-5 flex items-center gap-4">
            <div class="text-3xl font-light">₱</div>
            <div class="flex-1 flex justify-between items-baseline">
                <div>
                    <div class="text-xs text-blue-300">Total Paid</div>
                    <div class="text-2xl font-extrabold leading-none">₱15,550</div>
                </div>
                <div class="text-[11px] text-blue-300">7 transactions</div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-5 border border-slate-100 flex items-center gap-4">
            <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-500 flex items-center justify-center">
                <i class="fa-regular fa-calendar-check"></i>
            </div>
            <div class="flex-1 flex justify-between items-center">
                <div>
                    <div class="text-xs text-slate-500 mb-1">Past Reservations</div>
                    <div class="text-[11px] text-slate-400">Completed reservations</div>
                </div>
                <div class="text-2xl font-extrabold">3</div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-5 border border-slate-100 flex items-center gap-4">
            <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-500 flex items-center justify-center">
                <i class="fa-regular fa-circle-check"></i>
            </div>
            <div class="flex-1 flex justify-between items-center">
                <div>
                    <div class="text-xs text-slate-500 mb-1">Service Requests</div>
                    <div class="text-[11px] text-slate-400">Completed requests</div>
                </div>
                <div class="text-2xl font-extrabold">3</div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-2xl border border-slate-100 p-4 mb-6">
        <div class="flex gap-4 mb-3.5 flex-col sm:flex-row">
            <div class="flex-1 bg-slate-50 border border-slate-100 rounded-lg px-3.5 py-2.5 flex items-center gap-2.5">
                <i class="fa-solid fa-magnifying-glass text-slate-400 text-sm"></i>
                <input id="history-search" type="text" placeholder="Search history..."
                       class="flex-1 bg-transparent outline-none text-[13px]">
            </div>
            <div class="bg-slate-50 border border-slate-100 rounded-lg px-3.5 py-2.5 flex items-center gap-2.5 text-[13px] font-semibold text-slate-700 cursor-pointer min-w-[150px] justify-between">
                <div class="flex items-center gap-2">
                    <i class="fa-regular fa-calendar"></i>
                    <select id="history-sort" class="bg-transparent outline-none cursor-pointer font-semibold text-slate-700">
                        <option value="newest">Newest First</option>
                        <option value="oldest">Oldest First</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="flex gap-2 overflow-x-auto pb-0.5">
            <div class="history-pill active bg-[#02122c] text-white px-3.5 py-2 rounded-lg text-xs font-semibold flex items-center gap-1.5 cursor-pointer whitespace-nowrap" data-category="all">
                <i class="fa-solid fa-chart-line text-[13px]"></i><span>All History</span>
            </div>
            @foreach([
                ['key' => 'payments',        'icon' => 'fa-solid fa-peso-sign',       'label' => 'Payments'],
                ['key' => 'reservation',     'icon' => 'fa-regular fa-calendar',      'label' => 'Reservation'],
                ['key' => 'maintenance',     'icon' => 'fa-solid fa-wrench',          'label' => 'Maintenance'],
                ['key' => 'room-changes',    'icon' => 'fa-solid fa-house',           'label' => 'Room Changes'],
                ['key' => 'stay-extensions', 'icon' => 'fa-regular fa-calendar-plus', 'label' => 'Stay Extensions'],
            ] as $p)
            <div class="history-pill bg-slate-100 text-slate-600 px-3.5 py-2 rounded-lg text-xs font-semibold flex items-center gap-1.5 cursor-pointer whitespace-nowrap" data-category="{{ $p['key'] }}">
                <i class="{{ $p['icon'] }} text-[13px]"></i><span>{{ $p['label'] }}</span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- List meta --}}
    <div class="flex justify-between items-center mb-4">
        <div id="history-records-count" class="text-[13px] text-slate-500">
            Showing {{ count($historyItems) }} of {{ count($historyItems) }} total records
        </div>
        <a href="#" id="export-history-btn" class="text-[#10b981] text-[13px] font-semibold flex items-center gap-1.5">
            <i class="fa-solid fa-download"></i> Export History
        </a>
    </div>

    {{-- History list --}}
    <div id="history-list" class="bg-white rounded-2xl border border-slate-100 overflow-hidden">

        @foreach($historyItems as $item)
            @php
                [$badgeClass, $badgeIcon] = $badges[$item['status']] ?? ['bg-slate-100 text-slate-500', 'fa-regular fa-circle'];
                $iconColorClass = $colorClasses[$item['color']] ?? 'bg-slate-100 text-slate-500';
            @endphp

            <div class="history-item border-b border-slate-50 last:border-b-0"
                 data-category="{{ $item['cat'] }}"
                 data-item-key="{{ $item['key'] }}">

                {{-- Header (clickable) --}}
                <div class="history-item-header px-5 py-[18px] flex justify-between items-start gap-3 cursor-pointer hover:bg-slate-50/60 transition">
                    <div class="flex gap-3.5 items-start">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5 {{ $iconColorClass }}">
                            <i class="{{ $item['icon'] }} text-[15px]"></i>
                        </div>
                        <div class="flex flex-col gap-1">
                            <div class="text-sm font-bold text-slate-900">{{ $item['title'] }}</div>
                            <div class="text-[13px] text-slate-500">{{ $item['desc'] }}</div>
                            <div class="flex items-center gap-1.5 text-[11.5px] text-slate-400 mt-0.5">
                                <i class="fa-regular fa-clock"></i>
                                <span>{{ $item['date'] }}</span>
                                @if($item['amount'])
                                    <span class="text-emerald-500 font-bold">{{ $item['amount'] }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 flex-shrink-0">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $badgeClass }} inline-flex items-center gap-1.5">
                            <i class="{{ $badgeIcon }}"></i>
                            {{ ucfirst($item['status']) }}
                        </span>
                        <i class="fa-solid fa-chevron-down text-slate-400 text-xs expand-chevron"></i>
                    </div>
                </div>

                {{-- Expandable details panel (toggled by React) --}}
                <div class="history-item-details" id="details-{{ $item['key'] }}">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach($item['details'] as $d)
                            <div class="flex flex-col gap-1">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ $d[0] }}</span>
                                <span class="text-[13px] font-semibold text-slate-800">{{ $d[1] }}</span>
                            </div>
                        @endforeach
                    </div>

                    {{-- Receipt actions (payment items only) --}}
                    @if(!empty($item['receipt']))
                        <div class="flex flex-wrap items-center gap-3 mt-5 pt-4 border-t border-slate-200">
                            <div class="flex items-center gap-1.5 text-xs font-semibold text-emerald-600 mr-auto">
                                <i class="fa-solid fa-file-invoice"></i>
                                <span>Official Receipt Available</span>
                            </div>
                            <button type="button"
                                    class="view-receipt-btn bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded-lg text-xs font-semibold inline-flex items-center gap-1.5 transition"
                                    data-item-key="{{ $item['key'] }}">
                                <i class="fa-regular fa-eye"></i> View Receipt
                            </button>
                            <button type="button"
                                    class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-xs font-semibold inline-flex items-center gap-1.5 transition"
                                    onclick="event.stopPropagation(); alert('PDF download will be wired to the backend.')">
                                <i class="fa-solid fa-download"></i> Download PDF
                            </button>
                            <button type="button"
                                    class="border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 px-4 py-2 rounded-lg text-xs font-semibold inline-flex items-center gap-1.5 transition"
                                    onclick="event.stopPropagation(); window.print()">
                                <i class="fa-solid fa-print"></i> Print
                            </button>
                        </div>
                    @endif
                </div>

            </div>
        @endforeach

    </div>
</div>
@endsection