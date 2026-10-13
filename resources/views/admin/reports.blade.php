@extends('layouts.admin', ['active' => 'reports', 'pageTitle' => 'Reports'])
@section('title', 'Reports')

@section('content')
<div class="p-4 md:p-8 max-w-[1400px] mx-auto space-y-5">

    {{-- Page Title + Controls --}}
    <div class="flex flex-col md:flex-row justify-between md:items-start gap-4">
        <div class="flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center text-lg">
                <i class="fa-regular fa-file-lines"></i>
            </div>
            <div>
                <h2 class="text-[22px] font-extrabold text-slate-900">Reports & Analytics</h2>
                <p class="text-[13px] text-slate-500 mt-0.5">Data insights for JLM Cozy Cabin</p>
            </div>
        </div>
        <div class="flex items-center gap-2.5 flex-wrap">
            <button class="bg-white border border-slate-300 rounded-[10px] px-3.5 py-2 text-[13px] font-medium text-slate-700 flex items-center gap-2">
                Last 6 Months <i class="fa-solid fa-chevron-down text-xs"></i>
            </button>
            <button class="bg-white border border-slate-300 rounded-[10px] px-4 py-2 text-[13px] font-semibold text-emerald-500 flex items-center gap-2">
                <i class="fa-solid fa-file-excel"></i> Export CSV
            </button>
            <button class="bg-[#021838] text-white rounded-[10px] px-4.5 py-2 text-[13px] font-semibold flex items-center gap-2">
                <i class="fa-solid fa-download"></i> Export PDF
            </button>
        </div>
    </div>

    {{-- 4 Metric Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-emerald-50 border border-emerald-200 rounded-2xl px-4 py-4">
            <div class="text-xs font-semibold text-emerald-600">Total Revenue (Mar)</div>
            <div class="text-[26px] font-extrabold text-emerald-600 leading-tight mt-2">₱156,700</div>
            <div class="text-[11.5px] text-emerald-500 mt-2">+10.4% vs last month</div>
        </div>
        <div class="bg-sky-50 border border-sky-200 rounded-2xl px-4 py-4">
            <div class="text-xs font-semibold text-sky-600">Occupancy Rate</div>
            <div class="text-[26px] font-extrabold text-sky-600 leading-tight mt-2">75%</div>
            <div class="text-[11.5px] text-sky-400 mt-2">9 of 12 rooms occupied</div>
        </div>
        <div class="bg-violet-50 border border-violet-200 rounded-2xl px-4 py-4">
            <div class="text-xs font-semibold text-violet-700">Active Tenants</div>
            <div class="text-[26px] font-extrabold text-violet-700 leading-tight mt-2">4</div>
            <div class="text-[11.5px] text-violet-400 mt-2">Currently residing</div>
        </div>
        <div class="bg-amber-50 border border-amber-200 rounded-2xl px-4 py-4">
            <div class="text-xs font-semibold text-amber-700">Open Requests</div>
            <div class="text-[26px] font-extrabold text-amber-700 leading-tight mt-2">3</div>
            <div class="text-[11.5px] text-amber-500 mt-2">Pending maintenance</div>
        </div>
    </div>

    {{-- Tabs Card --}}
    <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden">
        <div class="flex border-b border-slate-100">
            <button class="reports-tab-btn active px-6 py-4 text-[13.5px] font-semibold text-emerald-500 border-b-2 border-emerald-500 bg-emerald-50/50 inline-flex items-center gap-2" data-tab="occupancy">
                <i class="fa-solid fa-house"></i> Occupancy
            </button>
            <button class="reports-tab-btn px-6 py-4 text-[13.5px] font-semibold text-slate-600 border-b-2 border-transparent inline-flex items-center gap-2" data-tab="revenue">
                <i class="fa-solid fa-peso-sign"></i> Revenue
            </button>
            <button class="reports-tab-btn px-6 py-4 text-[13.5px] font-semibold text-slate-600 border-b-2 border-transparent inline-flex items-center gap-2" data-tab="service">
                <i class="fa-solid fa-wrench"></i> Service Requests
            </button>
            <button class="reports-tab-btn px-6 py-4 text-[13.5px] font-semibold text-slate-600 border-b-2 border-transparent inline-flex items-center gap-2" data-tab="payments">
                <i class="fa-solid fa-chart-line"></i> Payments
            </button>
        </div>

        {{-- Tab 1: Occupancy --}}
        <div class="reports-panel p-6" id="panel-occupancy">
            <h3 class="text-base font-bold text-slate-900">Monthly Occupancy Trend</h3>
            <p class="text-xs text-slate-500 mt-0.5 mb-5">Number of rooms by status per month</p>

            <div class="flex items-center justify-center gap-5 text-xs text-slate-500 mb-4">
                <div class="flex items-center gap-1.5"><div class="w-2.5 h-2.5 rounded-sm bg-emerald-500"></div> Occupied</div>
                <div class="flex items-center gap-1.5"><div class="w-2.5 h-2.5 rounded-sm bg-indigo-500"></div> Reserved</div>
                <div class="flex items-center gap-1.5"><div class="w-2.5 h-2.5 rounded-sm bg-slate-200"></div> Vacant</div>
            </div>

            <div class="flex items-end justify-around h-[180px] border-b border-dashed border-slate-300 pl-6 gap-3">
                @foreach([
                    ['label' => 'Oct 25', 'vacant' => 25, 'reserved' => 15, 'occupied' => 60],
                    ['label' => 'Nov 25', 'vacant' => 20, 'reserved' => 15, 'occupied' => 65],
                    ['label' => 'Dec 25', 'vacant' => 20, 'reserved' => 15, 'occupied' => 65],
                    ['label' => 'Jan 26', 'vacant' => 20, 'reserved' => 10, 'occupied' => 70],
                    ['label' => 'Feb 26', 'vacant' => 20, 'reserved' => 15, 'occupied' => 65],
                    ['label' => 'Mar 26', 'vacant' => 20, 'reserved' => 15, 'occupied' => 65],
                ] as $bar)
                <div class="flex flex-col items-center gap-2">
                    <div class="w-[50px] h-[160px] flex flex-col justify-end rounded-md overflow-hidden">
                        <div class="w-full bg-slate-200" style="height: {{ $bar['vacant'] }}%"></div>
                        <div class="w-full bg-indigo-500" style="height: {{ $bar['reserved'] }}%"></div>
                        <div class="w-full bg-emerald-500" style="height: {{ $bar['occupied'] }}%"></div>
                    </div>
                    <span class="text-[11px] text-slate-500">{{ $bar['label'] }}</span>
                </div>
                @endforeach
            </div>

            <div class="mt-8">
                <h4 class="text-base font-bold text-slate-900 mb-4">Occupancy by Room Type</h4>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-[13px]">
                        <thead>
                            <tr class="bg-slate-50 text-[11px] text-slate-500 uppercase tracking-wide">
                                <th class="py-3 px-4">Room Type</th>
                                <th class="py-3 px-4">Occupied</th>
                                <th class="py-3 px-4">Reserved</th>
                                <th class="py-3 px-4">Vacant</th>
                                <th class="py-3 px-4">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach([
                                ['type' => 'Solo Room', 'occ' => 5, 'res' => 0, 'vac' => 1, 'total' => 6],
                                ['type' => 'Barkada Room', 'occ' => 3, 'res' => 2, 'vac' => 1, 'total' => 6],
                                ['type' => 'Bedspace', 'occ' => 1, 'res' => 0, 'vac' => 1, 'total' => 2],
                            ] as $row)
                            <tr class="border-b border-slate-50">
                                <td class="py-3.5 px-4 text-slate-800">{{ $row['type'] }}</td>
                                <td class="py-3.5 px-4"><span class="bg-emerald-100 text-emerald-700 w-6 h-6 rounded-full inline-flex items-center justify-center text-[11px] font-bold">{{ $row['occ'] }}</span></td>
                                <td class="py-3.5 px-4"><span class="bg-indigo-100 text-indigo-700 w-6 h-6 rounded-full inline-flex items-center justify-center text-[11px] font-bold">{{ $row['res'] }}</span></td>
                                <td class="py-3.5 px-4"><span class="bg-slate-100 text-slate-600 w-6 h-6 rounded-full inline-flex items-center justify-center text-[11px] font-bold">{{ $row['vac'] }}</span></td>
                                <td class="py-3.5 px-4 font-bold text-slate-900">{{ $row['total'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Tab 2: Revenue (hidden by default) --}}
        <div class="reports-panel p-6 hidden" id="panel-revenue">
            <h3 class="text-base font-bold text-slate-900">Monthly Revenue Breakdown</h3>
            <p class="text-xs text-slate-500 mt-0.5 mb-5">Rent, utilities and other income in ₱</p>

            <div class="mt-6 overflow-x-auto">
                <table class="w-full text-left text-[13px]">
                    <thead>
                        <tr class="bg-slate-50 text-[11px] text-slate-500 uppercase tracking-wide">
                            <th class="py-3 px-4">Month</th>
                            <th class="py-3 px-4">Rent</th>
                            <th class="py-3 px-4">Utilities</th>
                            <th class="py-3 px-4">Other</th>
                            <th class="py-3 px-4">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach([
                            ['month' => 'Oct 25', 'rent' => '₱76,200', 'util' => '₱12,000', 'other' => '₱5,000', 'total' => '₱93,200'],
                            ['month' => 'Nov 25', 'rent' => '₱84,700', 'util' => '₱14,500', 'other' => '₱3,200', 'total' => '₱102,400'],
                            ['month' => 'Dec 25', 'rent' => '₱88,300', 'util' => '₱15,200', 'other' => '₱6,100', 'total' => '₱109,600'],
                            ['month' => 'Jan 26', 'rent' => '₱92,400', 'util' => '₱13,800', 'other' => '₱4,500', 'total' => '₱110,700'],
                            ['month' => 'Feb 26', 'rent' => '₱96,000', 'util' => '₱14,200', 'other' => '₱5,800', 'total' => '₱116,000'],
                            ['month' => 'Mar 26', 'rent' => '₱130,000', 'util' => '₱16,200', 'other' => '₱10,500', 'total' => '₱156,700'],
                        ] as $row)
                        <tr class="border-b border-slate-50">
                            <td class="py-3.5 px-4 text-slate-800">{{ $row['month'] }}</td>
                            <td class="py-3.5 px-4 text-slate-700">{{ $row['rent'] }}</td>
                            <td class="py-3.5 px-4 text-slate-700">{{ $row['util'] }}</td>
                            <td class="py-3.5 px-4 text-slate-700">{{ $row['other'] }}</td>
                            <td class="py-3.5 px-4 font-bold text-slate-900">{{ $row['total'] }}</td>
                        </tr>
                        @endforeach
                        <tr class="bg-[#02122b] text-white font-bold">
                            <td class="py-3.5 px-4">TOTAL</td>
                            <td class="py-3.5 px-4">₱567,600</td>
                            <td class="py-3.5 px-4">₱85,900</td>
                            <td class="py-3.5 px-4">₱35,100</td>
                            <td class="py-3.5 px-4 text-emerald-400">₱688,600</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Tab 3: Service (hidden) --}}
        <div class="reports-panel p-6 hidden" id="panel-service">
            <h3 class="text-base font-bold text-slate-900">Monthly Service Requests by Category</h3>
            <p class="text-xs text-slate-500 mt-0.5 mb-5">Breakdown of maintenance issues reported</p>
            <div class="text-center py-12 text-slate-400 text-sm">
                <i class="fa-solid fa-chart-column text-4xl mb-3 opacity-30"></i>
                <p>Service request chart — data visualization placeholder</p>
            </div>
        </div>

        {{-- Tab 4: Payments (hidden) --}}
        <div class="reports-panel p-6 hidden" id="panel-payments">
            <h3 class="text-base font-bold text-slate-900">Payment Methods Distribution</h3>
            <p class="text-xs text-slate-500 mt-0.5 mb-5">Percentage of payments by method</p>
            <div class="text-center py-12 text-slate-400 text-sm">
                <i class="fa-solid fa-chart-pie text-4xl mb-3 opacity-30"></i>
                <p>Payment distribution chart — data visualization placeholder</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const tabs = document.querySelectorAll('.reports-tab-btn');
    const panels = document.querySelectorAll('.reports-panel');

    tabs.forEach(tab => tab.addEventListener('click', () => {
        tabs.forEach(t => {
            t.classList.remove('text-emerald-500','border-emerald-500','bg-emerald-50/50');
            t.classList.add('text-slate-600','border-transparent');
        });
        tab.classList.add('text-emerald-500','border-emerald-500','bg-emerald-50/50');
        tab.classList.remove('text-slate-600','border-transparent');

        panels.forEach(p => p.classList.add('hidden'));
        document.getElementById('panel-' + tab.dataset.tab).classList.remove('hidden');
    }));
})();
</script>
@endpush