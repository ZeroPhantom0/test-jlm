@extends('layouts.tenant', ['active' => 'history', 'pageTitle' => 'History'])
@section('title', 'History')

@section('content')
<div class="p-4 md:p-8 max-w-6xl mx-auto">

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
            <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-500 flex items-center justify-center"><i class="fa-regular fa-calendar-check"></i></div>
            <div class="flex-1 flex justify-between items-center">
                <div>
                    <div class="text-xs text-slate-500 mb-1">Past Reservations</div>
                    <div class="text-[11px] text-slate-400">Completed reservations</div>
                </div>
                <div class="text-2xl font-extrabold">3</div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-5 border border-slate-100 flex items-center gap-4">
            <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-500 flex items-center justify-center"><i class="fa-regular fa-circle-check"></i></div>
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
        <div class="flex gap-4 mb-3.5">
            <div class="flex-1 bg-slate-50 border border-slate-100 rounded-lg px-3.5 py-2.5 flex items-center gap-2.5">
                <i class="fa-solid fa-magnifying-glass text-slate-400 text-sm"></i>
                <input id="history-search" type="text" placeholder="Search history..." class="flex-1 bg-transparent outline-none text-[13px]">
            </div>
            <div class="bg-slate-50 border border-slate-100 rounded-lg px-3.5 py-2.5 flex items-center gap-2.5 text-[13px] font-semibold text-slate-700 cursor-pointer min-w-[150px] justify-between">
                <div class="flex items-center gap-2"><i class="fa-regular fa-calendar"></i><span>Newest First</span></div>
                <i class="fa-solid fa-chevron-down text-xs text-slate-500"></i>
            </div>
        </div>
        <div class="flex gap-2 overflow-x-auto pb-0.5">
            <div class="history-pill active bg-[#02122c] text-white px-3.5 py-2 rounded-lg text-xs font-semibold flex items-center gap-1.5 cursor-pointer whitespace-nowrap" data-category="all">
                <i class="fa-solid fa-chart-line text-[13px]"></i><span>All History</span>
            </div>
            @foreach([
                ['key' => 'payments', 'icon' => 'fa-solid fa-peso-sign', 'label' => 'Payments'],
                ['key' => 'reservation', 'icon' => 'fa-regular fa-calendar', 'label' => 'Reservation'],
                ['key' => 'maintenance', 'icon' => 'fa-solid fa-wrench', 'label' => 'Maintenance'],
                ['key' => 'room-changes', 'icon' => 'fa-solid fa-house', 'label' => 'Room Changes'],
                ['key' => 'stay-extensions', 'icon' => 'fa-regular fa-calendar-plus', 'label' => 'Stay Extensions'],
            ] as $p)
            <div class="history-pill bg-slate-100 text-slate-600 px-3.5 py-2 rounded-lg text-xs font-semibold flex items-center gap-1.5 cursor-pointer whitespace-nowrap" data-category="{{ $p['key'] }}">
                <i class="{{ $p['icon'] }} text-[13px]"></i><span>{{ $p['label'] }}</span>
            </div>
            @endforeach
        </div>
    </div>

    <div class="flex justify-between items-center mb-4">
        <div id="history-records-count" class="text-[13px] text-slate-500">Showing 12 of 12 total records</div>
        <a href="#" class="text-[#10b981] text-[13px] font-semibold flex items-center gap-1.5">
            <i class="fa-solid fa-download"></i> Export History
        </a>
    </div>

    {{-- History list --}}
    <div id="history-list" class="bg-white rounded-2xl border border-slate-100 overflow-hidden">
        @foreach([
            ['cat' => 'payments', 'icon' => 'fa-solid fa-peso-sign', 'color' => 'green', 'title' => 'Utility Bill - Feb 2026', 'desc' => 'Payment of ₱500 via over the counter', 'date' => 'Apr 07, 2026 · 03:50 PM', 'amount' => '₱500', 'status' => 'paid'],
            ['cat' => 'maintenance', 'icon' => 'fa-solid fa-wrench', 'color' => 'green', 'title' => 'Clogged Shower Drain', 'desc' => 'Bathroom shower draining very slowly.', 'date' => 'Mar 27, 2026 · 03:50 PM', 'amount' => null, 'status' => 'completed'],
            ['cat' => 'payments', 'icon' => 'fa-solid fa-peso-sign', 'color' => 'green', 'title' => 'Rent - February 2026', 'desc' => 'Payment of ₱4,500 via gcash', 'date' => 'Mar 14, 2026 · 03:50 PM', 'amount' => '₱4,500', 'status' => 'paid'],
            ['cat' => 'maintenance', 'icon' => 'fa-solid fa-wrench', 'color' => 'green', 'title' => 'Paint Touch-up Request', 'desc' => 'Wall paint chipped near entrance.', 'date' => 'Feb 10, 2026 · 03:50 PM', 'amount' => null, 'status' => 'cancelled'],
            ['cat' => 'room-changes', 'icon' => 'fa-solid fa-house', 'color' => 'blue', 'title' => 'Room Change: 103 → 101', 'desc' => 'Wanted unit with better natural light.', 'date' => 'Oct 23, 2025 · 03:50 PM', 'amount' => null, 'status' => 'approved'],
            ['cat' => 'room-changes', 'icon' => 'fa-solid fa-house', 'color' => 'red', 'title' => 'Room Change: 103 → 201', 'desc' => 'Requested higher floor for less noise.', 'date' => 'Oct 03, 2025 · 03:50 PM', 'amount' => null, 'status' => 'rejected'],
            ['cat' => 'stay-extensions', 'icon' => 'fa-regular fa-calendar-check', 'color' => 'blue', 'title' => 'Stay Extension - Unit 101', 'desc' => 'Work contract extended for 3 more months.', 'date' => 'Nov 02, 2025 · 03:50 PM', 'amount' => null, 'status' => 'approved'],
            ['cat' => 'stay-extensions', 'icon' => 'fa-regular fa-calendar-check', 'color' => 'red', 'title' => 'Stay Extension - Unit 103', 'desc' => 'Requested short extension while house hunting.', 'date' => 'Jun 05, 2025 · 03:50 PM', 'amount' => null, 'status' => 'rejected'],
            ['cat' => 'reservation', 'icon' => 'fa-regular fa-calendar-check', 'color' => 'blue', 'title' => 'Reservation - Unit 101', 'desc' => 'Solo Room reservation approved', 'date' => 'Mar 22, 2025 · 03:50 PM', 'amount' => '₱4,500', 'status' => 'approved'],
            ['cat' => 'reservation', 'icon' => 'fa-regular fa-calendar-check', 'color' => 'red', 'title' => 'Reservation - Unit 201', 'desc' => 'Solo Room reservation rejected', 'date' => 'Mar 02, 2025 · 03:50 PM', 'amount' => '₱4,800', 'status' => 'rejected'],
            ['cat' => 'reservation', 'icon' => 'fa-regular fa-calendar-check', 'color' => 'yellow', 'title' => 'Reservation - Unit 103', 'desc' => 'Bedspace reservation expired', 'date' => 'Oct 03, 2024 · 03:50 PM', 'amount' => '₱2,500', 'status' => 'expired'],
            ['cat' => 'payments', 'icon' => 'fa-solid fa-peso-sign', 'color' => 'green', 'title' => 'Security Deposit', 'desc' => 'Payment of ₱10,000 via bank transfer', 'date' => 'Sep 15, 2024 · 10:15 AM', 'amount' => '₱10,000', 'status' => 'paid'],
        ] as $item)
        <div class="history-item flex justify-between items-start p-5 border-b border-slate-50 last:border-0 hover:bg-slate-50/50" data-category="{{ $item['cat'] }}">
            <div class="flex gap-3.5 items-start">
                <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5
                    {{ $item['color'] === 'green' ? 'bg-green-100 text-emerald-500' : '' }}
                    {{ $item['color'] === 'blue' ? 'bg-blue-100 text-blue-500' : '' }}
                    {{ $item['color'] === 'red' ? 'bg-red-100 text-red-500' : '' }}
                    {{ $item['color'] === 'yellow' ? 'bg-amber-100 text-amber-600' : '' }}">
                    <i class="{{ $item['icon'] }} text-[15px]"></i>
                </div>
                <div class="flex flex-col gap-1">
                    <div class="text-sm font-bold text-slate-900">{{ $item['title'] }}</div>
                    <div class="text-[13px] text-slate-500">{{ $item['desc'] }}</div>
                    <div class="flex items-center gap-1.5 text-[11.5px] text-slate-400 mt-0.5">
                        <i class="fa-regular fa-clock"></i>
                        <span>{{ $item['date'] }}</span>
                        @if($item['amount'])<span class="text-emerald-500 font-bold">{{ $item['amount'] }}</span>@endif
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-3">
                @php
                    $badges = [
                        'paid' => 'bg-green-100 text-green-700',
                        'completed' => 'bg-green-100 text-green-700',
                        'approved' => 'bg-blue-100 text-blue-600',
                        'cancelled' => 'bg-slate-100 text-slate-600 border border-slate-200',
                        'rejected' => 'bg-red-100 text-red-600',
                        'expired' => 'bg-amber-100 text-amber-600',
                    ];
                @endphp
                <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $badges[$item['status']] }} inline-flex items-center gap-1.5">
                    <i class="fa-regular fa-{{ $item['status'] === 'rejected' || $item['status'] === 'cancelled' ? 'circle-xmark' : ($item['status'] === 'expired' ? 'clock' : 'circle-check') }}"></i>
                    {{ ucfirst($item['status']) }}
                </span>
                <i class="fa-solid fa-chevron-down text-slate-400 text-xs cursor-pointer"></i>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const pills = document.querySelectorAll('.history-pill');
    const items = document.querySelectorAll('.history-item');
    const input = document.getElementById('history-search');
    const counter = document.getElementById('history-records-count');
    let currentCategory = 'all';

    function filterRecords() {
        const q = (input.value || '').toLowerCase().trim();
        let visible = 0;

        items.forEach((item) => {
            const cat = item.dataset.category;
            const text = item.innerText.toLowerCase();
            const show = (currentCategory === 'all' || cat === currentCategory) && text.includes(q);
            item.style.display = show ? 'flex' : 'none';
            if (show) visible++;
        });

        counter.textContent = `Showing ${visible} of ${items.length} total records`;
    }

    pills.forEach(p => p.addEventListener('click', () => {
        pills.forEach(x => {
            x.classList.remove('active','bg-[#02122c]','text-white');
            x.classList.add('bg-slate-100','text-slate-600');
        });
        p.classList.add('active','bg-[#02122c]','text-white');
        p.classList.remove('bg-slate-100','text-slate-600');
        currentCategory = p.dataset.category;
        filterRecords();
    }));

    if (input) input.addEventListener('input', filterRecords);
})();
</script>
@endpush