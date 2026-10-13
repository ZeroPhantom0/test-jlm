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
        <button class="flex items-center gap-2 text-[13.5px] font-semibold text-slate-600 px-3 py-1.5 rounded-lg hover:bg-slate-100 transition">
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
            <div class="text-[26px] font-extrabold leading-tight">₱60,210</div>
            <div class="text-[11px] text-slate-400 mt-1">16 transactions</div>
        </div>
        <div class="bg-white rounded-2xl px-5 py-5 border border-slate-100 shadow-sm">
            <div class="flex justify-between items-center mb-3">
                <span class="text-[13px] font-semibold text-slate-500">Past Reservations</span>
                <div class="w-9 h-9 rounded-[10px] bg-sky-100 text-sky-600 flex items-center justify-center text-lg">
                    <i class="fa-regular fa-calendar-check"></i>
                </div>
            </div>
            <div class="text-[26px] font-extrabold text-slate-900 leading-tight">10</div>
            <div class="text-[11px] text-slate-400 mt-1">All statuses</div>
        </div>
        <div class="bg-white rounded-2xl px-5 py-5 border border-slate-100 shadow-sm">
            <div class="flex justify-between items-center mb-3">
                <span class="text-[13px] font-semibold text-slate-500">Service Requests</span>
                <div class="w-9 h-9 rounded-[10px] bg-emerald-100 text-emerald-600 flex items-center justify-center text-lg">
                    <i class="fa-regular fa-circle-check"></i>
                </div>
            </div>
            <div class="text-[26px] font-extrabold text-slate-900 leading-tight">11</div>
            <div class="text-[11px] text-slate-400 mt-1">Completed</div>
        </div>
        <div class="bg-white rounded-2xl px-5 py-5 border border-slate-100 shadow-sm">
            <div class="flex justify-between items-center mb-3">
                <span class="text-[13px] font-semibold text-slate-500">Approved Requests</span>
                <div class="w-9 h-9 rounded-[10px] bg-sky-100 text-sky-600 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-arrow-trend-up"></i>
                </div>
            </div>
            <div class="text-[26px] font-extrabold text-slate-900 leading-tight">14</div>
            <div class="text-[11px] text-slate-400 mt-1">Room/stay changes</div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 space-y-3">
        <div class="flex flex-wrap gap-2">
            @foreach([
                ['key' => 'all', 'icon' => 'fa-solid fa-wave-square', 'label' => 'All', 'active' => true],
                ['key' => 'payment', 'icon' => 'fa-solid fa-peso-sign', 'label' => 'Payments'],
                ['key' => 'reservation', 'icon' => 'fa-regular fa-calendar-check', 'label' => 'Reservations'],
                ['key' => 'maintenance', 'icon' => 'fa-solid fa-wrench', 'label' => 'Maintenance'],
                ['key' => 'room-change', 'icon' => 'fa-solid fa-house', 'label' => 'Room Changes'],
                ['key' => 'stay-extension', 'icon' => 'fa-regular fa-calendar-plus', 'label' => 'Stay Extensions'],
            ] as $f)
            <button
                data-filter="{{ $f['key'] }}"
                class="filter-btn px-4 py-2 rounded-full text-[13px] font-semibold inline-flex items-center gap-1.5 transition
                    {{ ($f['active'] ?? false) ? 'bg-[#0b1d3a] text-white shadow-sm' : 'bg-transparent text-slate-600 hover:bg-slate-100' }}">
                <i class="{{ $f['icon'] }} text-[13px]"></i>
                <span>{{ $f['label'] }}</span>
            </button>
            @endforeach
        </div>

        <div class="flex flex-col md:flex-row gap-3.5">
            <div class="flex-1 bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 flex items-center gap-2.5">
                <i class="fa-solid fa-magnifying-glass text-slate-400 text-sm"></i>
                <input id="history-search" type="text" placeholder="Search by tenant, room, or description..." class="w-full text-[13.5px] outline-none bg-transparent">
            </div>
            <div class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 flex items-center gap-2 text-[13.5px] font-medium text-slate-700 cursor-pointer whitespace-nowrap">
                <i class="fa-regular fa-calendar text-xs text-slate-500"></i>
                <span>Newest First</span>
                <i class="fa-solid fa-chevron-down text-xs text-slate-500"></i>
            </div>
        </div>
    </div>

    <div class="flex justify-between items-center text-[13.5px] text-slate-500 font-medium">
        <span id="records-count">Showing 21 of 21 total records</span>
        <a href="#" class="text-emerald-500 font-semibold flex items-center gap-1.5 hover:text-emerald-600">
            <i class="fa-solid fa-download text-sm"></i> Export History
        </a>
    </div>

    {{-- History List --}}
    <div class="flex flex-col gap-3" id="history-list">

        @foreach([
            ['cat' => 'payment', 'icon' => 'fa-solid fa-peso-sign', 'color' => 'green', 'title' => 'Rent - Feb 2026', 'tenant' => 'Elle Dela Cruz', 'room' => 'Room 101', 'desc' => 'Payment of ₱500 via over the counter', 'date' => 'Apr 20, 2026 · 06:29 PM', 'amount' => '₱500', 'status' => 'paid'],
            ['cat' => 'payment', 'icon' => 'fa-solid fa-peso-sign', 'color' => 'green', 'title' => 'Rent - February 2026', 'tenant' => 'Mau Santos', 'room' => 'Room 102', 'desc' => 'Payment of ₱1,500 via bank transfer', 'date' => 'Apr 01, 2026 · 06:29 PM', 'amount' => '₱1,500', 'status' => 'paid'],
            ['cat' => 'payment', 'icon' => 'fa-solid fa-peso-sign', 'color' => 'green', 'title' => 'Rent - February 2026', 'tenant' => 'Gabby Maningo', 'room' => 'Room 201', 'desc' => 'Payment of ₱800 via over the counter', 'date' => 'Mar 29, 2026 · 06:29 PM', 'amount' => '₱800', 'status' => 'paid'],
            ['cat' => 'payment', 'icon' => 'fa-solid fa-peso-sign', 'color' => 'green', 'title' => 'Rent - February 2026', 'tenant' => 'Elle Dela Cruz', 'room' => 'Room 101', 'desc' => 'Payment of ₱300 via gcash', 'date' => 'Mar 27, 2026 · 06:29 PM', 'amount' => '₱300', 'status' => 'paid'],
            ['cat' => 'reservation', 'icon' => 'fa-regular fa-calendar-check', 'color' => 'blue', 'title' => 'Reservation - Room 204', 'tenant' => 'Ramon Bautista', 'room' => 'Room 204', 'desc' => 'Barkada Room reservation approved', 'date' => 'Apr 29, 2026 · 06:29 PM', 'amount' => '₱100', 'status' => 'approved'],
            ['cat' => 'reservation', 'icon' => 'fa-regular fa-calendar-check', 'color' => 'red', 'title' => 'Reservation - Room 103', 'tenant' => 'Jenny Villanueva', 'room' => 'Room 103', 'desc' => 'Bedspace reservation rejected', 'date' => 'Apr 24, 2026 · 06:29 PM', 'amount' => '₱2,500', 'status' => 'rejected'],
            ['cat' => 'reservation', 'icon' => 'fa-regular fa-calendar-check', 'color' => 'yellow', 'title' => 'Reservation - Room 202', 'tenant' => 'Lito Cruz', 'room' => 'Room 202', 'desc' => 'Solo Room reservation expired', 'date' => 'Apr 19, 2026 · 06:29 PM', 'amount' => '₱700', 'status' => 'expired'],
            ['cat' => 'maintenance', 'icon' => 'fa-solid fa-wrench', 'color' => 'blue', 'title' => 'Light Fixture Broken', 'tenant' => 'Mike Callado', 'room' => 'Room 104', 'desc' => 'Hallway light flickering.', 'date' => 'Apr 29, 2026 · 06:29 PM', 'amount' => null, 'status' => 'completed'],
            ['cat' => 'maintenance', 'icon' => 'fa-solid fa-wrench', 'color' => 'blue', 'title' => 'Aircon Filter Cleaning', 'tenant' => 'Gabby Maningo', 'room' => 'Room 201', 'desc' => 'AC unit blowing dusty air.', 'date' => 'Apr 14, 2026 · 06:29 PM', 'amount' => null, 'status' => 'completed'],
            ['cat' => 'maintenance', 'icon' => 'fa-solid fa-wrench', 'color' => 'gray', 'title' => 'Paint Touch-up Request', 'tenant' => 'Elle Dela Cruz', 'room' => 'Room 101', 'desc' => 'Wall paint chipped near entrance.', 'date' => 'Feb 23, 2026 · 06:29 PM', 'amount' => null, 'status' => 'cancelled'],
            ['cat' => 'room-change', 'icon' => 'fa-solid fa-house', 'color' => 'purple', 'title' => 'Room Change: 102 → 204', 'tenant' => 'Mau Santos', 'room' => 'Room 102', 'desc' => 'Prefer higher floor for better view.', 'date' => 'Apr 27, 2026 · 06:29 PM', 'amount' => null, 'status' => 'approved'],
            ['cat' => 'room-change', 'icon' => 'fa-solid fa-house', 'color' => 'red', 'title' => 'Room Change: 104 → 202', 'tenant' => 'Gabby Maningo', 'room' => 'Room 104', 'desc' => 'Noise from upper floors.', 'date' => 'Apr 24, 2026 · 06:29 PM', 'amount' => null, 'status' => 'rejected'],
            ['cat' => 'room-change', 'icon' => 'fa-solid fa-house', 'color' => 'purple', 'title' => 'Room Change: 203 → 201', 'tenant' => 'Nicole Custorio', 'room' => 'Room 203', 'desc' => 'Prefer lower floor for easier access.', 'date' => 'Feb 03, 2026 · 06:29 PM', 'amount' => null, 'status' => 'approved'],
            ['cat' => 'stay-extension', 'icon' => 'fa-regular fa-calendar-plus', 'color' => 'yellow', 'title' => 'Stay Extension - Room 102', 'tenant' => 'Mau Santos', 'room' => 'Room 102', 'desc' => 'New apartment not ready yet.', 'date' => 'Apr 26, 2026 · 06:29 PM', 'amount' => null, 'status' => 'approved'],
            ['cat' => 'stay-extension', 'icon' => 'fa-regular fa-calendar-plus', 'color' => 'yellow', 'title' => 'Stay Extension - Room 104', 'tenant' => 'Gabby Maningo', 'room' => 'Room 104', 'desc' => 'Relocating for work in 3 months.', 'date' => 'Dec 15, 2025 · 06:29 PM', 'amount' => null, 'status' => 'approved'],
            ['cat' => 'stay-extension', 'icon' => 'fa-regular fa-calendar-plus', 'color' => 'red', 'title' => 'Stay Extension - Room 103', 'tenant' => 'Elle Dela Cruz', 'room' => 'Room 103', 'desc' => 'Requested short extension while house hunting.', 'date' => 'Jun 18, 2025 · 06:29 PM', 'amount' => null, 'status' => 'rejected'],
        ] as $item)
        @php
            $badges = [
                'paid' => 'bg-emerald-100 text-emerald-600',
                'approved' => 'bg-sky-100 text-sky-600',
                'rejected' => 'bg-rose-100 text-rose-600',
                'completed' => 'bg-emerald-100 text-emerald-600',
                'cancelled' => 'bg-slate-100 text-slate-500',
                'expired' => 'bg-yellow-100 text-amber-700',
            ];
            $iconColor = match($item['color']) {
                'green' => 'bg-emerald-100 text-emerald-600',
                'blue' => 'bg-sky-100 text-sky-600',
                'purple' => 'bg-violet-100 text-violet-600',
                'red' => 'bg-rose-100 text-rose-600',
                'yellow' => 'bg-yellow-100 text-amber-700',
                'gray' => 'bg-slate-100 text-slate-500',
            };
        @endphp
        <div class="history-item bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden" data-category="{{ $item['cat'] }}">
            <div class="flex justify-between items-center p-5">
                <div class="flex items-center gap-4 flex-1 min-w-0">
                    <div class="w-11 h-11 rounded-full flex items-center justify-center text-lg shrink-0 {{ $iconColor }}">
                        <i class="{{ $item['icon'] }}"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-[15px] font-bold text-slate-900">{{ $item['title'] }}</div>
                        <div class="flex items-center gap-3 text-[12.5px] text-slate-500 mt-1 flex-wrap">
                            <span class="flex items-center gap-1.5"><i class="fa-regular fa-user text-[11px] text-slate-400"></i> {{ $item['tenant'] }}</span>
                            <span class="flex items-center gap-1.5"><i class="fa-solid fa-door-open text-[11px] text-slate-400"></i> {{ $item['room'] }}</span>
                        </div>
                        <div class="text-[13.5px] text-slate-700 mt-1">{{ $item['desc'] }}</div>
                        <div class="text-xs text-slate-400 mt-1 flex items-center gap-1.5">
                            <i class="fa-regular fa-clock text-[11px]"></i>
                            <span>{{ $item['date'] }}</span>
                            @if($item['amount'])<span class="text-emerald-600 font-bold">{{ $item['amount'] }}</span>@endif
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <span class="px-3.5 py-1 rounded-full text-xs font-bold inline-flex items-center gap-1.5 {{ $badges[$item['status']] }}">
                        <i class="fa-regular fa-{{ $item['status'] === 'rejected' || $item['status'] === 'cancelled' ? 'circle-xmark' : ($item['status'] === 'expired' ? 'clock' : 'circle-check') }}"></i>
                        {{ ucfirst($item['status']) }}
                    </span>
                    <i class="fa-solid fa-chevron-down text-slate-400 text-xs cursor-pointer"></i>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const filterBtns = document.querySelectorAll('.filter-btn');
    const items = document.querySelectorAll('.history-item');
    const input = document.getElementById('history-search');
    const count = document.getElementById('records-count');
    let currentFilter = 'all';

    function filter() {
        const q = (input.value || '').toLowerCase().trim();
        let visible = 0;
        items.forEach(item => {
            const cat = item.dataset.category;
            const text = item.innerText.toLowerCase();
            const show = (currentFilter === 'all' || cat === currentFilter) && text.includes(q);
            item.style.display = show ? 'block' : 'none';
            if (show) visible++;
        });
        count.textContent = `Showing ${visible} of ${items.length} total records`;
    }

    filterBtns.forEach(btn => btn.addEventListener('click', () => {
        filterBtns.forEach(b => {
            b.classList.remove('bg-[#0b1d3a]','text-white','shadow-sm');
            b.classList.add('bg-transparent','text-slate-600','hover:bg-slate-100');
        });
        btn.classList.add('bg-[#0b1d3a]','text-white','shadow-sm');
        btn.classList.remove('bg-transparent','text-slate-600','hover:bg-slate-100');
        currentFilter = btn.dataset.filter;
        filter();
    }));

    input.addEventListener('input', filter);
})();
</script>
@endpush