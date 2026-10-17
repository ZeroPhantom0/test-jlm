@extends('layouts.tenant', ['active' => 'history', 'pageTitle' => 'History'])
@section('title', 'History')

@section('content')

@php
    $historyItems = $historyItems ?? [];
    $metrics      = $metrics ?? [
        'total_paid' => 0, 'total_paid_count' => 0,
        'past_reservations' => 0, 'service_requests' => 0,
    ];

    // If controller passed a keyed map, convert back to array for iteration
    if (!array_is_list($historyItems) && !empty($historyItems)) {
        $historyItems = array_values($historyItems);
    }

    $badges = [
        'paid'      => ['bg-green-100 text-green-700',   'fa-regular fa-circle-check'],
        'completed' => ['bg-green-100 text-green-700',   'fa-regular fa-circle-check'],
        'approved'  => ['bg-blue-100 text-blue-600',     'fa-regular fa-circle-check'],
        'cancelled' => ['bg-slate-100 text-slate-600 border border-slate-200', 'fa-regular fa-circle-xmark'],
        'rejected'  => ['bg-red-100 text-red-600',       'fa-regular fa-circle-xmark'],
        'expired'   => ['bg-amber-100 text-amber-600',   'fa-regular fa-clock'],
        'pending'   => ['bg-amber-100 text-amber-700',   'fa-regular fa-clock'],
    ];

    $colorClasses = [
        'green'  => 'bg-green-100 text-emerald-500',
        'blue'   => 'bg-blue-100 text-blue-500',
        'red'    => 'bg-red-100 text-red-500',
        'yellow' => 'bg-amber-100 text-amber-600',
    ];

    $itemsMap = collect($historyItems)->keyBy('key')->toArray();
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
                    <div class="text-2xl font-extrabold leading-none">
                        ₱{{ number_format($metrics['total_paid'], 0) }}
                    </div>
                </div>
                <div class="text-[11px] text-blue-300">
                    {{ $metrics['total_paid_count'] }} transaction{{ $metrics['total_paid_count'] === 1 ? '' : 's' }}
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-5 border border-slate-100 flex items-center gap-4">
            <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-500 flex items-center justify-center">
                <i class="fa-regular fa-calendar-check"></i>
            </div>
            <div class="flex-1 flex justify-between items-center">
                <div>
                    <div class="text-xs text-slate-500 mb-1">Past Reservations</div>
                    <div class="text-[11px] text-slate-400">Total reservations</div>
                </div>
                <div class="text-2xl font-extrabold">{{ $metrics['past_reservations'] }}</div>
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
                <div class="text-2xl font-extrabold">{{ $metrics['service_requests'] }}</div>
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

        @forelse($historyItems as $item)
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
                                @if(!empty($item['amount']))
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

                {{-- Expandable details --}}
                <div class="history-item-details" id="details-{{ $item['key'] }}">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach($item['details'] as $d)
                            <div class="flex flex-col gap-1">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ $d[0] }}</span>
                                <span class="text-[13px] font-semibold text-slate-800">{{ $d[1] }}</span>
                            </div>
                        @endforeach
                    </div>

                    {{-- Receipt actions --}}
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
                                    onclick="event.stopPropagation(); window.print()">
                                <i class="fa-solid fa-print"></i> Print
                            </button>
                        </div>
                    @endif
                </div>

            </div>
        @empty
            <div class="px-6 py-16 text-center text-slate-400">
                <i class="fa-regular fa-clock-rotate-left text-4xl mb-3 block"></i>
                <p class="text-sm font-medium">No activity history yet.</p>
                <p class="text-xs mt-1">Your payments, requests, and reservations will appear here.</p>
            </div>
        @endforelse

    </div>
</div>
@endsection