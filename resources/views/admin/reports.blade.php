@extends('layouts.admin', ['active' => 'reports', 'pageTitle' => 'Reports'])
@section('title', 'Reports')

@section('content')
@php
    $fmt = fn ($n) => '₱' . number_format((float) $n, 0);
    $pct = fn ($n) => number_format((float) $n, 1);
@endphp
<div class="p-4 md:p-8 max-w-[1400px] mx-auto space-y-5">

    {{-- Page Title + Controls --}}
    <div class="flex flex-col md:flex-row justify-between md:items-start gap-4">
        <div class="flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center text-lg">
                <i class="fa-regular fa-file-lines"></i>
            </div>
            <div>
                <h2 class="text-[22px] font-extrabold text-slate-900">Reports &amp; Analytics</h2>
                <p class="text-[13px] text-slate-500 mt-0.5">Data insights for JLM Cozy Cabin</p>
            </div>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap">

            {{-- Date-range dropdown --}}
            <div class="relative" data-dropdown="range">
                <button type="button"
                    class="js-dropdown-toggle bg-white border border-slate-300 rounded-[10px] px-3.5 py-2 text-[13px] font-medium text-slate-700 flex items-center gap-2 hover:bg-slate-50 transition">
                    <i class="fa-regular fa-calendar text-slate-500"></i>
                    <span id="range-label">Last 6 Months</span>
                    <i class="fa-solid fa-chevron-down text-[10px] text-slate-400"></i>
                </button>
                <div class="js-dropdown-menu hidden absolute right-0 mt-2 w-44 bg-white border border-slate-200 rounded-xl shadow-lg p-1.5 z-[2000]">
                    <button type="button" class="range-option w-full text-left px-3 py-2 rounded-lg text-[13px] text-slate-700 hover:bg-slate-100">Daily</button>
                    <button type="button" class="range-option w-full text-left px-3 py-2 rounded-lg text-[13px] text-slate-700 hover:bg-slate-100">Weekly</button>
                    <button type="button" class="range-option w-full text-left px-3 py-2 rounded-lg text-[13px] text-slate-700 hover:bg-slate-100">Monthly</button>
                    <button type="button" class="range-option w-full text-left px-3 py-2 rounded-lg text-[13px] text-slate-700 hover:bg-slate-100">Yearly</button>
                </div>
            </div>

            {{-- Export dropdown --}}
            <div class="relative" data-dropdown="export">
                <button type="button"
                    class="js-dropdown-toggle bg-[#021838] text-white rounded-[10px] px-4 py-2 text-[13px] font-semibold flex items-center gap-2 hover:bg-[#0a2a55] transition">
                    <i class="fa-solid fa-download"></i>
                    Export
                    <i class="fa-solid fa-chevron-down text-[10px] text-white/70"></i>
                </button>
                <div class="js-dropdown-menu hidden absolute right-0 mt-2 w-60 bg-white border border-slate-200 rounded-xl shadow-lg p-1.5 z-[2000]">
                    <div class="px-3 pt-1.5 pb-2 text-[11px] font-bold text-slate-400 uppercase tracking-wider">Export Section</div>

                    <label class="flex items-center gap-2 px-3 py-2 rounded-lg text-[13px] text-slate-700 hover:bg-slate-100 cursor-pointer">
                        <input type="checkbox" class="export-section" value="all" checked> All Sections
                    </label>
                    <label class="flex items-center gap-2 px-3 py-2 rounded-lg text-[13px] text-slate-700 hover:bg-slate-100 cursor-pointer">
                        <input type="checkbox" class="export-section" value="occupancy"> Occupancy
                    </label>
                    <label class="flex items-center gap-2 px-3 py-2 rounded-lg text-[13px] text-slate-700 hover:bg-slate-100 cursor-pointer">
                        <input type="checkbox" class="export-section" value="revenue"> Revenue
                    </label>
                    <label class="flex items-center gap-2 px-3 py-2 rounded-lg text-[13px] text-slate-700 hover:bg-slate-100 cursor-pointer">
                        <input type="checkbox" class="export-section" value="service"> Service Requests
                    </label>
                    <label class="flex items-center gap-2 px-3 py-2 rounded-lg text-[13px] text-slate-700 hover:bg-slate-100 cursor-pointer">
                        <input type="checkbox" class="export-section" value="payments"> Payments
                    </label>

                    <div class="border-t border-slate-100 mt-1.5 pt-1.5">
                        <button type="button" id="export-csv" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-[13px] font-semibold text-emerald-600 hover:bg-emerald-50">
                            <i class="fa-solid fa-file-excel"></i> Export CSV
                        </button>
                        <button type="button" id="export-pdf" class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-[13px] font-semibold text-rose-600 hover:bg-rose-50">
                            <i class="fa-solid fa-file-pdf"></i> Export PDF
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- 4 Metric Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-emerald-50 border border-emerald-200 rounded-2xl px-4 py-4">
            <div class="text-xs font-semibold text-emerald-600">Total Revenue ({{ now()->format('M') }})</div>
            <div class="text-[26px] font-extrabold text-emerald-600 leading-tight mt-2">{{ $fmt($summary['revenue']) }}</div>
            <div class="text-[11.5px] text-emerald-500 mt-2">
                {{ $summary['revenue_change'] >= 0 ? '+' : '' }}{{ $pct($summary['revenue_change']) }}% vs last month
            </div>
        </div>
        <div class="bg-sky-50 border border-sky-200 rounded-2xl px-4 py-4">
            <div class="text-xs font-semibold text-sky-600">Occupancy Rate</div>
            <div class="text-[26px] font-extrabold text-sky-600 leading-tight mt-2">{{ $summary['occupancy_rate'] }}%</div>
            <div class="text-[11.5px] text-sky-400 mt-2">
                {{ $summary['occupied_rooms'] }} of {{ $summary['total_rooms'] }} rooms occupied
            </div>
        </div>
        <div class="bg-violet-50 border border-violet-200 rounded-2xl px-4 py-4">
            <div class="text-xs font-semibold text-violet-700">Active Tenants</div>
            <div class="text-[26px] font-extrabold text-violet-700 leading-tight mt-2">{{ $summary['active_tenants'] }}</div>
            <div class="text-[11.5px] text-violet-400 mt-2">Currently residing</div>
        </div>
        <div class="bg-amber-50 border border-amber-200 rounded-2xl px-4 py-4">
            <div class="text-xs font-semibold text-amber-700">Open Requests</div>
            <div class="text-[26px] font-extrabold text-amber-700 leading-tight mt-2">{{ $summary['open_requests'] }}</div>
            <div class="text-[11.5px] text-amber-500 mt-2">Pending maintenance</div>
        </div>
    </div>

    {{-- Tabs Card --}}
    <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden">
        <div class="flex border-b border-slate-100 overflow-x-auto">
            <button class="reports-tab-btn active px-6 py-4 text-[13.5px] font-semibold text-emerald-500 border-b-2 border-emerald-500 bg-emerald-50/50 inline-flex items-center gap-2 whitespace-nowrap" data-tab="occupancy">
                <i class="fa-solid fa-house"></i> Occupancy
            </button>
            <button class="reports-tab-btn px-6 py-4 text-[13.5px] font-semibold text-slate-600 border-b-2 border-transparent inline-flex items-center gap-2 whitespace-nowrap" data-tab="revenue">
                <i class="fa-solid fa-peso-sign"></i> Revenue
            </button>
            <button class="reports-tab-btn px-6 py-4 text-[13.5px] font-semibold text-slate-600 border-b-2 border-transparent inline-flex items-center gap-2 whitespace-nowrap" data-tab="service">
                <i class="fa-solid fa-wrench"></i> Service Requests
            </button>
            <button class="reports-tab-btn px-6 py-4 text-[13.5px] font-semibold text-slate-600 border-b-2 border-transparent inline-flex items-center gap-2 whitespace-nowrap" data-tab="payments">
                <i class="fa-solid fa-chart-line"></i> Payments
            </button>
        </div>

        {{-- ============================================ --}}
        {{-- TAB 1: OCCUPANCY --}}
        {{-- ============================================ --}}
        <div class="reports-panel p-6" id="panel-occupancy">
            <h3 class="text-base font-bold text-slate-900">Monthly Occupancy Trend</h3>
            <p class="text-xs text-slate-500 mt-0.5 mb-5">Number of rooms by status per month</p>

            <div class="flex items-center justify-center gap-5 text-xs text-slate-500 mb-4">
                <div class="flex items-center gap-1.5"><div class="w-2.5 h-2.5 rounded-sm bg-emerald-500"></div> Occupied</div>
                <div class="flex items-center gap-1.5"><div class="w-2.5 h-2.5 rounded-sm bg-indigo-500"></div> Reserved</div>
                <div class="flex items-center gap-1.5"><div class="w-2.5 h-2.5 rounded-sm bg-slate-200"></div> Vacant</div>
            </div>

            <div class="flex items-end justify-around h-[180px] border-b border-dashed border-slate-300 pl-6 gap-3">
                @foreach ($occupancyTrend as $bar)
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
                            @forelse ($occupancyByType as $row)
                            <tr class="border-b border-slate-50">
                                <td class="py-3.5 px-4 text-slate-800">{{ $row['type'] }}</td>
                                <td class="py-3.5 px-4"><span class="bg-emerald-100 text-emerald-700 w-6 h-6 rounded-full inline-flex items-center justify-center text-[11px] font-bold">{{ $row['occupied'] }}</span></td>
                                <td class="py-3.5 px-4"><span class="bg-indigo-100 text-indigo-700 w-6 h-6 rounded-full inline-flex items-center justify-center text-[11px] font-bold">{{ $row['reserved'] }}</span></td>
                                <td class="py-3.5 px-4"><span class="bg-slate-100 text-slate-600 w-6 h-6 rounded-full inline-flex items-center justify-center text-[11px] font-bold">{{ $row['vacant'] }}</span></td>
                                <td class="py-3.5 px-4 font-bold text-slate-900">{{ $row['total'] }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="py-8 text-center text-slate-400 text-sm">No rooms found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ============================================ --}}
        {{-- TAB 2: REVENUE --}}
        {{-- ============================================ --}}
        <div class="reports-panel p-6 hidden" id="panel-revenue">
            <h3 class="text-base font-bold text-slate-900">Monthly Revenue Breakdown</h3>
            <p class="text-xs text-slate-500 mt-0.5 mb-5">Rent, utilities and other income in ₱</p>

            @php
                $revMax = 1;
                foreach ($revenueBreakdown as $r) {
                    $revMax = max($revMax, (float) $r['total']);
                }
            @endphp

            <div class="flex items-center justify-center gap-5 text-xs text-slate-500 mb-4">
                <div class="flex items-center gap-1.5"><div class="w-2.5 h-2.5 rounded-sm bg-emerald-500"></div> Rent</div>
                <div class="flex items-center gap-1.5"><div class="w-2.5 h-2.5 rounded-sm bg-indigo-500"></div> Utilities</div>
                <div class="flex items-center gap-1.5"><div class="w-2.5 h-2.5 rounded-sm bg-amber-500"></div> Other</div>
            </div>

            <div class="flex items-end justify-around h-[180px] border-b border-dashed border-slate-300 pl-9 gap-3">
                @foreach ($revenueBreakdown as $bar)
                <div class="flex flex-col items-center gap-2">
                    <div class="flex items-end gap-1 h-[160px]">
                        <div class="w-3.5 rounded-t-sm bg-emerald-500" style="height: {{ ($bar['rent'] / $revMax) * 100 }}%"></div>
                        <div class="w-3.5 rounded-t-sm bg-indigo-500" style="height: {{ ($bar['util'] / $revMax) * 100 }}%"></div>
                        <div class="w-3.5 rounded-t-sm bg-amber-500"  style="height: {{ ($bar['other'] / $revMax) * 100 }}%"></div>
                    </div>
                    <span class="text-[11px] text-slate-500">{{ $bar['label'] }}</span>
                </div>
                @endforeach
            </div>

            <div class="mt-8">
                <h4 class="text-base font-bold text-slate-900 mb-4">Revenue Summary Table</h4>
                <div class="overflow-x-auto">
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
                            @php
                                $tRent = 0; $tUtil = 0; $tOther = 0; $tTotal = 0;
                            @endphp
                            @foreach ($revenueBreakdown as $row)
                            @php
                                $tRent  += $row['rent'];
                                $tUtil  += $row['util'];
                                $tOther += $row['other'];
                                $tTotal += $row['total'];
                            @endphp
                            <tr class="border-b border-slate-50">
                                <td class="py-3.5 px-4 text-slate-800">{{ $row['label'] }}</td>
                                <td class="py-3.5 px-4 text-slate-700">{{ $fmt($row['rent']) }}</td>
                                <td class="py-3.5 px-4 text-slate-700">{{ $fmt($row['util']) }}</td>
                                <td class="py-3.5 px-4 text-slate-700">{{ $fmt($row['other']) }}</td>
                                <td class="py-3.5 px-4 font-bold text-slate-900">{{ $fmt($row['total']) }}</td>
                            </tr>
                            @endforeach
                            <tr class="bg-[#02122b] text-white font-bold">
                                <td class="py-3.5 px-4">TOTAL</td>
                                <td class="py-3.5 px-4">{{ $fmt($tRent) }}</td>
                                <td class="py-3.5 px-4">{{ $fmt($tUtil) }}</td>
                                <td class="py-3.5 px-4">{{ $fmt($tOther) }}</td>
                                <td class="py-3.5 px-4 text-emerald-400">{{ $fmt($tTotal) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ============================================ --}}
        {{-- TAB 3: SERVICE REQUESTS --}}
        {{-- ============================================ --}}
        <div class="reports-panel p-6 hidden" id="panel-service">
            <h3 class="text-base font-bold text-slate-900">Monthly Service Requests by Category</h3>
            <p class="text-xs text-slate-500 mt-0.5 mb-5">Breakdown of maintenance issues reported</p>

            <div class="flex flex-col gap-4 mt-3">
                <div class="relative h-[200px] pl-10 flex flex-col justify-end">
                    <div class="absolute top-0 left-10 right-0 bottom-0 flex flex-col justify-between pointer-events-none">
                        <div class="w-full border-b border-dashed border-slate-100"></div>
                        <div class="w-full border-b border-dashed border-slate-100"></div>
                        <div class="w-full border-b border-dashed border-slate-100"></div>
                        <div class="w-full border-b border-dashed border-slate-300"></div>
                    </div>

                    <div class="absolute left-0 -top-1.5 bottom-0 flex flex-col justify-between text-[11px] text-slate-400">
                        <span>{{ $serviceMax }}</span>
                        <span>{{ round($serviceMax * 2 / 3) }}</span>
                        <span>{{ round($serviceMax / 3) }}</span>
                        <span>0</span>
                    </div>

                    <div class="flex items-end justify-around h-full relative z-10">
                        @foreach ($serviceByCategory as $month)
                            <div class="flex flex-col items-center h-full justify-end">
                                <div class="flex items-end gap-1.5 h-full">
                                    <div class="w-3.5 rounded-t-sm transition-opacity hover:opacity-85"
                                         style="background:#6366f1; height: {{ ($month['plumbing'] / $serviceMax) * 100 }}%;"
                                         title="Plumbing: {{ $month['plumbing'] }}"></div>
                                    <div class="w-3.5 rounded-t-sm transition-opacity hover:opacity-85"
                                         style="background:#f59e0b; height: {{ ($month['electrical'] / $serviceMax) * 100 }}%;"
                                         title="Electrical: {{ $month['electrical'] }}"></div>
                                    <div class="w-3.5 rounded-t-sm transition-opacity hover:opacity-85"
                                         style="background:#10b981; height: {{ ($month['hvac'] / $serviceMax) * 100 }}%;"
                                         title="HVAC: {{ $month['hvac'] }}"></div>
                                    <div class="w-3.5 rounded-t-sm transition-opacity hover:opacity-85"
                                         style="background:#94a3b8; height: {{ ($month['general'] / $serviceMax) * 100 }}%;"
                                         title="General: {{ $month['general'] }}"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex justify-around pl-10">
                    @foreach ($serviceByCategory as $month)
                        <span class="text-[11px] text-slate-500 font-medium text-center w-20">{{ $month['label'] }}</span>
                    @endforeach
                </div>

                <div class="flex items-center justify-center gap-5 text-xs text-slate-500 mt-4">
                    <div class="flex items-center gap-1.5"><div class="w-2.5 h-2.5 rounded-sm" style="background:#6366f1;"></div> Plumbing</div>
                    <div class="flex items-center gap-1.5"><div class="w-2.5 h-2.5 rounded-sm" style="background:#f59e0b;"></div> Electrical</div>
                    <div class="flex items-center gap-1.5"><div class="w-2.5 h-2.5 rounded-sm" style="background:#10b981;"></div> HVAC</div>
                    <div class="flex items-center gap-1.5"><div class="w-2.5 h-2.5 rounded-sm" style="background:#94a3b8;"></div> General</div>
                </div>
            </div>
        </div>

        {{-- ============================================ --}}
        {{-- TAB 4: PAYMENTS --}}
        {{-- ============================================ --}}
        @php
            $gcash  = $paymentMethods['gcash'];
            $bank   = $paymentMethods['bank'];
            $otc    = $paymentMethods['otc'];
            $gcashEnd   = $gcash;
            $bankEnd    = $gcash + $bank;
        @endphp
        <div class="reports-panel p-6 hidden" id="panel-payments">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">

                <div class="flex flex-col items-center justify-center gap-5">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 text-center">Payment Methods Distribution</h3>
                        <p class="text-xs text-slate-500 mt-0.5 mb-5 text-center">Percentage of payments by method</p>
                    </div>

                    <div class="w-[180px] h-[180px] rounded-full relative flex items-center justify-center"
                         style="background: conic-gradient(
                            #6366f1 0% {{ $gcashEnd }}%,
                            #10b981 {{ $gcashEnd }}% {{ $bankEnd }}%,
                            #f59e0b {{ $bankEnd }}% 100%
                         );">
                        <div class="w-[110px] h-[110px] bg-white rounded-full"></div>
                    </div>

                    <div class="flex gap-4 text-xs text-slate-600 flex-wrap justify-center">
                        <div><span style="color:#6366f1;" class="font-bold">●</span> GCash <strong>{{ $gcash }}%</strong></div>
                        <div><span style="color:#10b981;" class="font-bold">●</span> Bank Transfer <strong>{{ $bank }}%</strong></div>
                        <div><span style="color:#f59e0b;" class="font-bold">●</span> OTC <strong>{{ $otc }}%</strong></div>
                    </div>
                </div>

                <div class="flex flex-col gap-3">
                    <h3 class="text-base font-bold text-slate-900">Payment Status Summary</h3>

                    <div class="bg-slate-50 rounded-xl px-4 py-3 flex items-center justify-between">
                        <div class="flex items-center gap-3 text-[13px] font-semibold text-slate-700">
                            <span class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 inline-flex items-center justify-center text-[11px] font-bold">{{ $paymentSummary['Paid']['count'] }}</span>
                            <span>Paid</span>
                        </div>
                        <div class="text-[13px] font-bold text-slate-900">{{ $fmt($paymentSummary['Paid']['amount']) }}</div>
                    </div>

                    <div class="bg-slate-50 rounded-xl px-4 py-3 flex items-center justify-between">
                        <div class="flex items-center gap-3 text-[13px] font-semibold text-slate-700">
                            <span class="w-6 h-6 rounded-full bg-amber-100 text-amber-700 inline-flex items-center justify-center text-[11px] font-bold">{{ $paymentSummary['Unpaid']['count'] }}</span>
                            <span>Unpaid</span>
                        </div>
                        <div class="text-[13px] font-bold text-slate-900">{{ $fmt($paymentSummary['Unpaid']['amount']) }}</div>
                    </div>

                    <div class="bg-slate-50 rounded-xl px-4 py-3 flex items-center justify-between">
                        <div class="flex items-center gap-3 text-[13px] font-semibold text-slate-700">
                            <span class="w-6 h-6 rounded-full inline-flex items-center justify-center text-[11px] font-bold" style="background:#ffe4e6; color:#e11d48;">{{ $paymentSummary['Overdue']['count'] }}</span>
                            <span>Overdue</span>
                        </div>
                        <div class="text-[13px] font-bold text-slate-900">{{ $fmt($paymentSummary['Overdue']['amount']) }}</div>
                    </div>

                    <div class="bg-slate-50 rounded-xl px-4 py-3 flex items-center justify-between">
                        <div class="flex items-center gap-3 text-[13px] font-semibold text-slate-700">
                            <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 inline-flex items-center justify-center text-[11px] font-bold">{{ $paymentSummary['Pending']['count'] }}</span>
                            <span>Pending Verification</span>
                        </div>
                        <div class="text-[13px] font-bold text-slate-900">{{ $fmt($paymentSummary['Pending']['amount']) }}</div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    /* -------------------- Tabs -------------------- */
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

    /* -------------------- Dropdowns (UI only) -------------------- */
    document.querySelectorAll('[data-dropdown]').forEach(wrap => {
        const toggle = wrap.querySelector('.js-dropdown-toggle');
        const menu   = wrap.querySelector('.js-dropdown-menu');

        toggle.addEventListener('click', (e) => {
            e.stopPropagation();
            document.querySelectorAll('.js-dropdown-menu').forEach(m => {
                if (m !== menu) m.classList.add('hidden');
            });
            menu.classList.toggle('hidden');
        });
    });

    document.addEventListener('click', () => {
        document.querySelectorAll('.js-dropdown-menu').forEach(m => m.classList.add('hidden'));
    });
})();
</script>
@endpush