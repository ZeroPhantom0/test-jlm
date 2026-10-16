@extends('layouts.admin', ['active' => 'history', 'pageTitle' => 'History'])
@section('title', 'History')

@section('content')
    <div class="p-4 md:p-8 max-w-[1400px] mx-auto space-y-6">

        {{-- Header --}}
        <div class="flex justify-between items-start flex-wrap gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-900 flex items-center gap-2.5">
                    <i class="fa-solid fa-chart-line text-emerald-500 text-xl"></i> System History
                </h1>
                <p class="text-[13.5px] text-slate-500 mt-1">Complete transaction and activity history for all tenants</p>
            </div>
            <button
                class="flex items-center gap-2 text-[13.5px] font-semibold text-slate-600 px-3 py-1.5 rounded-lg hover:bg-slate-100 transition">
                <i class="fa-solid fa-building text-base"></i>
                <span>All Rooms &amp; Tenants</span>
            </button>
        </div>

        {{-- Metrics --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-5">
            <div class="bg-[#0b1d3a] text-white rounded-2xl px-5 py-5">
                <div class="flex justify-between items-center mb-3">
                    <span class="text-[13px] font-semibold text-slate-400">Total Payments</span>
                    <div class="w-9 h-9 rounded-[10px] bg-white/10 flex items-center justify-center text-lg">₱</div>
                </div>
                <div class="text-[26px] font-extrabold leading-tight">
                    ₱{{ number_format($metrics['total_payments'], 0) }}
                </div>
                <div class="text-[11px] text-slate-400 mt-1">
                    {{ $metrics['total_payments_count'] }} transaction{{ $metrics['total_payments_count'] === 1 ? '' : 's' }}
                </div>
            </div>
            <div class="bg-white rounded-2xl px-5 py-5 border border-slate-100 shadow-sm">
                <div class="flex justify-between items-center mb-3">
                    <span class="text-[13px] font-semibold text-slate-500">Past Reservations</span>
                    <div class="w-9 h-9 rounded-[10px] bg-sky-100 text-sky-600 flex items-center justify-center text-lg">
                        <i class="fa-regular fa-calendar-check"></i>
                    </div>
                </div>
                <div class="text-[26px] font-extrabold text-slate-900 leading-tight">{{ $metrics['past_reservations'] }}</div>
                <div class="text-[11px] text-slate-400 mt-1">All statuses</div>
            </div>
            <div class="bg-white rounded-2xl px-5 py-5 border border-slate-100 shadow-sm">
                <div class="flex justify-between items-center mb-3">
                    <span class="text-[13px] font-semibold text-slate-500">Service Requests</span>
                    <div class="w-9 h-9 rounded-[10px] bg-emerald-100 text-emerald-600 flex items-center justify-center text-lg">
                        <i class="fa-regular fa-circle-check"></i>
                    </div>
                </div>
                <div class="text-[26px] font-extrabold text-slate-900 leading-tight">{{ $metrics['service_requests'] }}</div>
                <div class="text-[11px] text-slate-400 mt-1">Completed</div>
            </div>
            <div class="bg-white rounded-2xl px-5 py-5 border border-slate-100 shadow-sm">
                <div class="flex justify-between items-center mb-3">
                    <span class="text-[13px] font-semibold text-slate-500">Approved Requests</span>
                    <div class="w-9 h-9 rounded-[10px] bg-sky-100 text-sky-600 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-arrow-trend-up"></i>
                    </div>
                </div>
                <div class="text-[26px] font-extrabold text-slate-900 leading-tight">{{ $metrics['approved_requests'] }}</div>
                <div class="text-[11px] text-slate-400 mt-1">Room/stay changes</div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 space-y-3">
            <div class="flex flex-wrap gap-2">
                @php
                    $filterOptions = [
                        ['key' => 'all',            'icon' => 'fa-solid fa-wave-square',           'label' => 'All'],
                        ['key' => 'payment',        'icon' => 'fa-solid fa-peso-sign',             'label' => 'Payments'],
                        ['key' => 'reservation',    'icon' => 'fa-regular fa-calendar-check',      'label' => 'Reservations'],
                        ['key' => 'maintenance',    'icon' => 'fa-solid fa-wrench',                'label' => 'Maintenance'],
                        ['key' => 'room-change',    'icon' => 'fa-solid fa-house',                 'label' => 'Room Changes'],
                        ['key' => 'stay-extension', 'icon' => 'fa-regular fa-calendar-plus',       'label' => 'Stay Extensions'],
                    ];
                    $currentCategory = $filters['category'] ?? 'all';
                @endphp
                @foreach ($filterOptions as $f)
                    <a href="{{ route('admin.history', ['category' => $f['key'], 'search' => $filters['search'] ?? '']) }}"
                       class="filter-btn px-4 py-2 rounded-full text-[13px] font-semibold inline-flex items-center gap-1.5 transition
                       {{ $currentCategory === $f['key'] ? 'bg-[#0b1d3a] text-white shadow-sm' : 'bg-transparent text-slate-600 hover:bg-slate-100' }}">
                        <i class="{{ $f['icon'] }} text-[13px]"></i>
                        <span>{{ $f['label'] }}</span>
                    </a>
                @endforeach
            </div>

            <form method="GET" action="{{ route('admin.history') }}" class="flex flex-col md:flex-row gap-3.5">
                <input type="hidden" name="category" value="{{ $currentCategory }}">
                <div class="flex-1 bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 flex items-center gap-2.5">
                    <i class="fa-solid fa-magnifying-glass text-slate-400 text-sm"></i>
                    <input id="history-search" name="search" type="text" value="{{ $filters['search'] ?? '' }}"
                        placeholder="Search by tenant, room, or description..."
                        class="w-full text-[13.5px] outline-none bg-transparent">
                </div>
                <div class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 flex items-center gap-2 text-[13.5px] font-medium text-slate-700 whitespace-nowrap">
                    <i class="fa-regular fa-calendar text-xs text-slate-500"></i>
                    <span>Newest First</span>
                    <i class="fa-solid fa-chevron-down text-xs text-slate-500"></i>
                </div>
            </form>
        </div>

        <div class="flex justify-between items-center text-[13.5px] text-slate-500 font-medium">
            <span id="records-count">Showing {{ $events->count() }} of {{ $events->count() }} total records</span>
            <a href="#" class="text-emerald-500 font-semibold flex items-center gap-1.5 hover:text-emerald-600">
                <i class="fa-solid fa-download text-sm"></i> Export History
            </a>
        </div>

        {{-- History List --}}
        <div class="flex flex-col gap-3" id="history-list">

            @forelse ($events as $item)
                @php
                    $badges = [
                        'paid'      => 'bg-emerald-100 text-emerald-600',
                        'approved'  => 'bg-sky-100 text-sky-600',
                        'rejected'  => 'bg-rose-100 text-rose-600',
                        'completed' => 'bg-emerald-100 text-emerald-600',
                        'cancelled' => 'bg-slate-100 text-slate-500',
                        'expired'   => 'bg-yellow-100 text-amber-700',
                        'pending'   => 'bg-yellow-100 text-amber-700',
                    ];
                    $iconColor = match ($item['color']) {
                        'green'  => 'bg-emerald-100 text-emerald-600',
                        'blue'   => 'bg-sky-100 text-sky-600',
                        'purple' => 'bg-violet-100 text-violet-600',
                        'red'    => 'bg-rose-100 text-rose-600',
                        'yellow' => 'bg-yellow-100 text-amber-700',
                        'gray'   => 'bg-slate-100 text-slate-500',
                        default  => 'bg-slate-100 text-slate-500',
                    };
                    $badgeIcon = in_array($item['status'], ['rejected', 'cancelled'])
                        ? 'fa-regular fa-circle-xmark'
                        : (in_array($item['status'], ['expired', 'pending'])
                            ? 'fa-regular fa-clock'
                            : 'fa-regular fa-circle-check');
                @endphp

                <div class="history-item bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden flex flex-col"
                    data-category="{{ $item['category'] }}">

                    {{-- Collapsed Header (clickable) --}}
                    <div class="history-item-header flex justify-between items-center p-5 cursor-pointer select-none">
                        <div class="flex items-center gap-4 flex-1 min-w-0">
                            <div class="w-11 h-11 rounded-full flex items-center justify-center text-lg shrink-0 {{ $iconColor }}">
                                <i class="{{ $item['icon'] }}"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-[15px] font-bold text-slate-900">{{ $item['title'] }}</div>
                                <div class="flex items-center gap-3 text-[12.5px] text-slate-500 mt-1 flex-wrap">
                                    <span class="flex items-center gap-1.5">
                                        <i class="fa-regular fa-user text-[11px] text-slate-400"></i>
                                        {{ $item['tenant'] }}
                                    </span>
                                    <span class="flex items-center gap-1.5">
                                        <i class="fa-solid fa-door-open text-[11px] text-slate-400"></i>
                                        {{ $item['room'] }}
                                    </span>
                                </div>
                                <div class="text-[13.5px] text-slate-700 mt-1">{{ $item['desc'] }}</div>
                                <div class="text-xs text-slate-400 mt-1 flex items-center gap-1.5">
                                    <i class="fa-regular fa-clock text-[11px]"></i>
                                    <span>{{ $item['date'] }}</span>
                                    @if (!empty($item['amount']))
                                        <span class="text-emerald-600 font-bold">{{ $item['amount'] }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <span class="px-3.5 py-1 rounded-full text-xs font-bold inline-flex items-center gap-1.5 {{ $badges[$item['status']] ?? 'bg-slate-100 text-slate-500' }}">
                                <i class="{{ $badgeIcon }}"></i>
                                {{ ucfirst($item['status']) }}
                            </span>
                            <i class="fa-solid fa-chevron-down text-slate-400 text-xs chevron-icon transition-transform duration-200"></i>
                        </div>
                    </div>

                    {{-- Expanded Details Panel (hidden by default) --}}
                    <div class="history-item-details hidden border-t border-slate-100 bg-slate-50/50 px-5 pb-5 pt-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach ($item['details'] as $d)
                                <div class="bg-white border border-slate-100 rounded-[10px] px-4 py-3 flex flex-col gap-0.5">
                                    <span class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider">{{ $d['label'] }}</span>
                                    <span class="text-[14px] font-semibold text-slate-900">{{ $d['value'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-white border border-slate-200 rounded-2xl p-12 text-center text-slate-400">
                    <i class="fa-solid fa-clock-rotate-left text-4xl mb-3"></i>
                    <p class="text-sm">No history records found.</p>
                </div>
            @endforelse
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            const items = document.querySelectorAll('.history-item');
            const input = document.getElementById('history-search');
            const count = document.getElementById('records-count');

            // ============================================================
            // EXPAND / COLLAPSE DETAILS
            // ============================================================
            items.forEach((item) => {
                const header = item.querySelector('.history-item-header');
                header.addEventListener('click', (e) => {
                    if (e.target.closest('.px-3\\.5')) return;

                    const details = item.querySelector('.history-item-details');
                    const chevron = item.querySelector('.chevron-icon');
                    const isHidden = details.classList.contains('hidden');

                    if (isHidden) {
                        details.classList.remove('hidden');
                        if (chevron) chevron.style.transform = 'rotate(180deg)';
                    } else {
                        details.classList.add('hidden');
                        if (chevron) chevron.style.transform = 'rotate(0deg)';
                    }
                });
            });

            // ============================================================
            // CLIENT-SIDE SEARCH (instant feedback, server does the real filter)
            // ============================================================
            if (input) {
                input.addEventListener('input', () => {
                    const q = (input.value || '').toLowerCase().trim();
                    let visible = 0;
                    items.forEach((item) => {
                        const text = item.innerText.toLowerCase();
                        const show = q === '' || text.includes(q);
                        item.style.display = show ? 'flex' : 'none';
                        if (!show) {
                            const details = item.querySelector('.history-item-details');
                            const chevron = item.querySelector('.chevron-icon');
                            if (details) details.classList.add('hidden');
                            if (chevron) chevron.style.transform = 'rotate(0deg)';
                        }
                        if (show) visible++;
                    });
                    if (count) count.textContent = `Showing ${visible} of ${items.length} total records`;
                });
            }
        })();
    </script>
@endpush