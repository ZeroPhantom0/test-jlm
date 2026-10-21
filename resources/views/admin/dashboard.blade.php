@extends('layouts.admin', [
    'active'    => 'dashboard',
    'pageTitle' => 'Dashboard',
])
@section('title', 'Dashboard')

@section('content')
<div class="dashboard-container">
    <!-- LEFT SECTION (Grid of 6 Cards + Financial Overview below) -->
    <div class="left-section">
        <!-- METRIC CARDS -->
        <div class="metrics-grid">
            <!-- 1. Total Revenue -->
            <div class="metric-card">
                <div class="metric-header">
                    <div class="metric-icon-box" style="background: var(--mint-bg); color: var(--mint-text)">₱</div>
                </div>
                <div class="metric-value">₱{{ number_format($revenue, 0) }}</div>
                <div class="metric-label">Total Revenue</div>
                <div class="metric-subtext">Collected up to {{ $selectedDate->format('M d, Y') }}</div>
            </div>

            <!-- 2. Active Tenants -->
            <div class="metric-card">
                <div class="metric-header">
                    <div class="metric-icon-box" style="background: var(--blue-bg); color: var(--blue-text)">
                        <i class="fa-regular fa-user"></i>
                    </div>
                </div>
                <div class="metric-value">{{ $activeTenants }}</div>
                <div class="metric-label">Active Tenants</div>
                <div class="metric-subtext">Currently residing</div>
            </div>

            <!-- 3. Service Requests -->
            <div class="metric-card">
                <div class="metric-header">
                    <div class="metric-icon-box" style="background: var(--yellow-bg); color: var(--yellow-text)">
                        <i class="fa-solid fa-wrench"></i>
                    </div>
                </div>
                <div class="metric-value">{{ $serviceRequests }}</div>
                <div class="metric-label">Service Requests</div>
                <div class="metric-subtext">Pending as of {{ $selectedDate->format('M d') }}</div>
            </div>

            <!-- 4. Pending Payments -->
            <div class="metric-card">
                <div class="metric-header">
                    <div class="metric-icon-box" style="background: var(--pink-bg); color: var(--pink-text)">
                        <i class="fa-solid fa-wave-square"></i>
                    </div>
                </div>
                <div class="metric-value">{{ $pendingPaymentsCount }}</div>
                <div class="metric-label">Pending Payments</div>
                <div class="metric-subtext">₱{{ number_format($pendingPaymentsAmount, 0) }} unpaid</div>
            </div>

            <!-- 5. Occupancy Rate -->
            <div class="metric-card">
                <div class="metric-header">
                    <div class="metric-icon-box" style="background: #e0f2fe; color: #0284c7">
                        <i class="fa-solid fa-house"></i>
                    </div>
                </div>
                <div class="metric-value">{{ $occupancyRate }}%</div>
                <div class="metric-label">Occupancy Rate</div>
                <div class="metric-subtext">{{ $occupiedRooms }} of {{ $totalRooms }} rooms occupied</div>
            </div>

            <!-- 6. Reservations -->
            <div class="metric-card">
                <div class="metric-header">
                    <div class="metric-icon-box" style="background: var(--purple-bg); color: var(--purple-text)">
                        <i class="fa-regular fa-calendar"></i>
                    </div>
                </div>
                <div class="metric-value">{{ $reservations }}</div>
                <div class="metric-label">Reservations</div>
                <div class="metric-subtext">Awaiting approval</div>
            </div>
        </div>

        <!-- FINANCIAL OVERVIEW PANEL -->
        <div class="card-panel">
            <div class="panel-header">
                <div class="panel-title">
                    <h3>Financial Overview</h3>
                    <p>Monthly income vs expenses</p>
                </div>
                <select class="dropdown-select">
                    <option>Last 6 Months</option>
                    <option>Last Year</option>
                </select>
            </div>

            <!-- Chart.js Container -->
            <div class="chart-container">
                <canvas id="financialChart"></canvas>
            </div>

            <div class="chart-legend-custom">
                <div class="legend-item">
                    <div class="legend-circle" style="background: #10b981"></div>
                    <span style="color: #10b981">Income</span>
                </div>
                <div class="legend-item">
                    <div class="legend-circle" style="background: #f43f5e"></div>
                    <span style="color: #f43f5e">Expenses</span>
                </div>
            </div>
        </div>
    </div>

    <!-- RIGHT COLUMN (Calendar, Occupancy, Recent Activity Stack) -->
    <div class="right-stack">
        
        <!-- Dynamic Calendar Card -->
        <div class="card-panel calendar-card">
            <div class="calendar-header">
                @php
                    $prevMonth = \Carbon\Carbon::create($year, $month, 1)->subMonth();
                    $nextMonth = \Carbon\Carbon::create($year, $month, 1)->addMonth();
                @endphp
                <a href="{{ route('admin.dashboard', ['date' => $prevMonth->toDateString()]) }}" class="cursor-pointer">
                    <i class="fa-solid fa-chevron-left"></i>
                </a>
                <h4>{{ $selectedDate->format('F Y') }}</h4>
                <a href="{{ route('admin.dashboard', ['date' => $nextMonth->toDateString()]) }}" class="cursor-pointer">
                    <i class="fa-solid fa-chevron-right"></i>
                </a>
            </div>

            <div class="calendar-grid">
                <div class="day-head">SUN</div>
                <div class="day-head">MON</div>
                <div class="day-head">TUE</div>
                <div class="day-head">WED</div>
                <div class="day-head">THU</div>
                <div class="day-head">FRI</div>
                <div class="day-head">SAT</div>

                @for($i = 0; $i < $firstDayOfMonth; $i++)
                    <div class="day-val opacity-0"></div>
                @endfor

                @for($i = 1; $i <= $daysInMonth; $i++)
                    @php
                        $isSelected = ($i == $selectedDate->day);
                        $dateStr = \Carbon\Carbon::create($year, $month, $i)->toDateString();
                    @endphp
                    <a href="{{ route('admin.dashboard', ['date' => $dateStr]) }}" 
                       class="day-val {{ $isSelected ? 'active' : 'hover:bg-slate-100 cursor-pointer' }} block text-center leading-8">
                        {{ $i }}
                    </a>
                @endfor
            </div>
        </div>

        <!-- Occupancy Status Donut Chart Card -->
        <div class="card-panel">
            <div class="panel-header" style="margin-bottom: 0">
                <div class="panel-title">
                    <h3>Occupancy Status</h3>
                    <p>Total: {{ $totalRooms }} rooms</p>
                </div>
            </div>

            <div class="occupancy-chart-wrapper">
                <div style="width: 170px; height: 170px; position: relative">
                    <canvas id="occupancyChart"></canvas>
                    <div class="donut-center-text">
                        <h2>{{ $occupancyRate }}%</h2>
                        <p>Occupied</p>
                    </div>
                </div>

                <div class="occupancy-legend">
                    <div class="occ-item">
                        <div class="occ-left">
                            <div class="legend-circle" style="background: #10b981"></div>
                            <span>Occupied</span>
                        </div>
                        <div class="occ-right">{{ $occupiedRooms }}/{{ $totalRooms }}</div>
                    </div>

                    <div class="occ-item">
                        <div class="occ-left">
                            <div class="legend-circle" style="background: #7c3aed"></div>
                            <span>Reserved</span>
                        </div>
                        <div class="occ-right">{{ $reservedRooms }}/{{ $totalRooms }}</div>
                    </div>

                    <div class="occ-item">
                        <div class="occ-left">
                            <div class="legend-circle" style="background: #cbd5e1"></div>
                            <span>Available</span>
                        </div>
                        <div class="occ-right">{{ $availableRooms }}/{{ $totalRooms }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity Card (Still mock data as requested) -->
        <div class="card-panel">
            <div class="panel-header">
                <div class="panel-title">
                    <h3>Recent Activity</h3>
                </div>
            </div>

            <div class="activity-list">
                <div class="activity-item">
                    <div class="activity-left">
                        <div class="act-icon" style="background: #dcfce7; color: #16a34a">
                            <i class="fa-solid fa-user-check"></i>
                        </div>
                        <div class="act-details">
                            <h5>New reservation approved</h5>
                            <p>Ramon Bautista – Room 204</p>
                        </div>
                    </div>
                    <div class="act-time">1h ago</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    window.addEventListener("DOMContentLoaded", () => {
        // -------------------------------------------------------------
        // 1. FINANCIAL OVERVIEW SMOOTH LINE CHART
        // -------------------------------------------------------------
        const ctxFin = document.getElementById("financialChart").getContext("2d");

        const incomeGradient = ctxFin.createLinearGradient(0, 0, 0, 250);
        incomeGradient.addColorStop(0, "rgba(16, 185, 129, 0.15)");
        incomeGradient.addColorStop(1, "rgba(16, 185, 129, 0)");

        const expenseGradient = ctxFin.createLinearGradient(0, 0, 0, 250);
        expenseGradient.addColorStop(0, "rgba(244, 63, 94, 0.15)");
        expenseGradient.addColorStop(1, "rgba(244, 63, 94, 0)");

        // PASSED FROM CONTROLLER
        const chartLabels = @json($chartLabels);
        const chartIncome = @json($chartIncome);
        const chartExpenses = @json($chartExpenses);

        new Chart(ctxFin, {
            type: "line",
            data: {
                labels: chartLabels,
                datasets: [
                    {
                        label: "Income",
                        data: chartIncome,
                        borderColor: "#10b981",
                        borderWidth: 2.5,
                        backgroundColor: incomeGradient,
                        fill: true,
                        tension: 0.45,
                        pointRadius: 0,
                        pointHoverRadius: 5,
                    },
                    {
                        label: "Expenses",
                        data: chartExpenses,
                        borderColor: "#f43f5e",
                        borderWidth: 2.5,
                        backgroundColor: expenseGradient,
                        fill: true,
                        tension: 0.45,
                        pointRadius: 0,
                        pointHoverRadius: 5,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { mode: "index", intersect: false },
                },
                scales: {
                    x: { grid: { display: false }, ticks: { color: "#94a3b8", font: { size: 11 } } },
                    y: {
                        min: 0,
                        // Let Chart.js automatically scale the Y axis based on DB data now
                        ticks: {
                            color: "#94a3b8",
                            font: { size: 11 },
                            callback: function (value) { return "₱" + value / 1000 + "k"; },
                        },
                        grid: { color: "#f1f5f9", borderDash: [4, 4] },
                    },
                },
            },
        });

        // -------------------------------------------------------------
        // 2. OCCUPANCY STATUS DONUT CHART
        // -------------------------------------------------------------
        const ctxOcc = document.getElementById("occupancyChart").getContext("2d");
        new Chart(ctxOcc, {
            type: "doughnut",
            data: {
                labels: ["Occupied", "Reserved", "Available"],
                datasets: [
                    {
                        data: [{{ $occupiedRooms }}, {{ $reservedRooms }}, {{ $availableRooms }}],
                        backgroundColor: ["#10b981", "#7c3aed", "#cbd5e1"],
                        borderWidth: 0,
                        hoverOffset: 4,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: "78%",
                plugins: {
                    legend: { display: false },
                    tooltip: { enabled: true },
                },
            },
        });
    });
</script>
@endpush