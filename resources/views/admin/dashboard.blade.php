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
                    <div class="badge-growth badge-green">
                        <i class="fa-solid fa-arrow-up-right"></i> 10.4%
                    </div>
                </div>
                <div class="metric-value">₱156,700</div>
                <div class="metric-label">Total Revenue</div>
                <div class="metric-subtext">Current month collections</div>
            </div>

            <!-- 2. Active Tenants -->
            <div class="metric-card">
                <div class="metric-header">
                    <div class="metric-icon-box" style="background: var(--blue-bg); color: var(--blue-text)">
                        <i class="fa-regular fa-user"></i>
                    </div>
                    <div class="badge-growth badge-green">
                        <i class="fa-solid fa-arrow-up-right"></i> 2 new
                    </div>
                </div>
                <div class="metric-value">4</div>
                <div class="metric-label">Active Tenants</div>
                <div class="metric-subtext">Currently residing</div>
            </div>

            <!-- 3. Service Requests -->
            <div class="metric-card">
                <div class="metric-header">
                    <div class="metric-icon-box" style="background: var(--yellow-bg); color: var(--yellow-text)">
                        <i class="fa-solid fa-wrench"></i>
                    </div>
                    <div class="badge-growth badge-pink">
                        <i class="fa-solid fa-arrow-down-right"></i> 5%
                    </div>
                </div>
                <div class="metric-value">3</div>
                <div class="metric-label">Service Requests</div>
                <div class="metric-subtext">Pending attention</div>
            </div>

            <!-- 4. Pending Payments -->
            <div class="metric-card">
                <div class="metric-header">
                    <div class="metric-icon-box" style="background: var(--pink-bg); color: var(--pink-text)">
                        <i class="fa-solid fa-wave-square"></i>
                    </div>
                    <div class="badge-growth badge-green">
                        <i class="fa-solid fa-arrow-up-right"></i> 2
                    </div>
                </div>
                <div class="metric-value">3</div>
                <div class="metric-label">Pending Payments</div>
                <div class="metric-subtext">Invoices awaiting payment</div>
            </div>

            <!-- 5. Occupancy Rate -->
            <div class="metric-card">
                <div class="metric-header">
                    <div class="metric-icon-box" style="background: #e0f2fe; color: #0284c7">
                        <i class="fa-solid fa-house"></i>
                    </div>
                    <div class="badge-growth badge-green">
                        <i class="fa-solid fa-arrow-up-right"></i> 3%
                    </div>
                </div>
                <div class="metric-value">75%</div>
                <div class="metric-label">Occupancy Rate</div>
                <div class="metric-subtext">7 of 12 rooms occupied</div>
            </div>

            <!-- 6. Reservations -->
            <div class="metric-card">
                <div class="metric-header">
                    <div class="metric-icon-box" style="background: var(--purple-bg); color: var(--purple-text)">
                        <i class="fa-regular fa-calendar"></i>
                    </div>
                </div>
                <div class="metric-value">2</div>
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
        <!-- May 2026 Calendar Card -->
        <div class="card-panel calendar-card">
            <div class="calendar-header">
                <i class="fa-solid fa-chevron-left"></i>
                <h4>May 2026</h4>
                <i class="fa-solid fa-chevron-right"></i>
            </div>

            <div class="calendar-grid">
                <div class="day-head">SAN</div>
                <div class="day-head">MON</div>
                <div class="day-head">TUE</div>
                <div class="day-head">WED</div>
                <div class="day-head">THU</div>
                <div class="day-head">FRI</div>
                <div class="day-head">SAT</div>

                @for($i = 1; $i <= 31; $i++)
                    <div class="day-val {{ $i === 21 ? 'active' : '' }}">{{ $i }}</div>
                @endfor
            </div>
        </div>

        <!-- Occupancy Status Donut Chart Card -->
        <div class="card-panel">
            <div class="panel-header" style="margin-bottom: 0">
                <div class="panel-title">
                    <h3>Occupancy Status</h3>
                    <p>Total: 12 rooms</p>
                </div>
            </div>

            <div class="occupancy-chart-wrapper">
                <div style="width: 170px; height: 170px; position: relative">
                    <canvas id="occupancyChart"></canvas>
                    <div class="donut-center-text">
                        <h2>75%</h2>
                        <p>Occupied</p>
                    </div>
                </div>

                <div class="occupancy-legend">
                    <div class="occ-item">
                        <div class="occ-left">
                            <div class="legend-circle" style="background: #10b981"></div>
                            <span>Occupied</span>
                        </div>
                        <div class="occ-right">7/12</div>
                    </div>

                    <div class="occ-item">
                        <div class="occ-left">
                            <div class="legend-circle" style="background: #7c3aed"></div>
                            <span>Reserved</span>
                        </div>
                        <div class="occ-right">2/12</div>
                    </div>

                    <div class="occ-item">
                        <div class="occ-left">
                            <div class="legend-circle" style="background: #cbd5e1"></div>
                            <span>Available</span>
                        </div>
                        <div class="occ-right">3/12</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity Card -->
        <div class="card-panel">
            <div class="panel-header">
                <div class="panel-title">
                    <h3>Recent Activity</h3>
                </div>
            </div>

            <div class="activity-list">
                <!-- Activity 1 -->
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

                <!-- Activity 2 -->
                <div class="activity-item">
                    <div class="activity-left">
                        <div class="act-icon" style="background: #e0f2fe; color: #0284c7">₱</div>
                        <div class="act-details">
                            <h5>Payment verified</h5>
                            <p>Mau Santos – ₱600 via GCash</p>
                        </div>
                    </div>
                    <div class="act-time">2h ago</div>
                </div>

                <!-- Activity 3 -->
                <div class="activity-item">
                    <div class="activity-left">
                        <div class="act-icon" style="background: #fef9c3; color: #ca8a04">
                            <i class="fa-solid fa-wrench"></i>
                        </div>
                        <div class="act-details">
                            <h5>Maintenance request submit</h5>
                            <p>Room 101 – Leaky Faucet</p>
                        </div>
                    </div>
                    <div class="act-time">3h ago</div>
                </div>

                <!-- Activity 4 -->
                <div class="activity-item">
                    <div class="activity-left">
                        <div class="act-icon" style="background: #f3e8ff; color: #9333ea">
                            <i class="fa-solid fa-users"></i>
                        </div>
                        <div class="act-details">
                            <h5>New resident registered</h5>
                            <p>Emily Davis – Room 201</p>
                        </div>
                    </div>
                    <div class="act-time">5h ago</div>
                </div>

                <!-- Activity 5 -->
                <div class="activity-item">
                    <div class="activity-left">
                        <div class="act-icon" style="background: #ccfbf1; color: #0d9488">
                            <i class="fa-regular fa-clock"></i>
                        </div>
                        <div class="act-details">
                            <h5>Stay extension approved</h5>
                            <p>Mau Santos – Room 102</p>
                        </div>
                    </div>
                    <div class="act-time">1d ago</div>
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

        new Chart(ctxFin, {
            type: "line",
            data: {
                labels: ["Oct", "Nov", "Dec", "Jan", "Feb", "Mar"],
                datasets: [
                    {
                        label: "Income",
                        data: [105000, 115000, 100000, 145000, 138000, 156700],
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
                        data: [48000, 42000, 58000, 45000, 44000, 50000],
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
                    tooltip: {
                        mode: "index",
                        intersect: false,
                    },
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: "#94a3b8", font: { size: 11 } },
                    },
                    y: {
                        min: 0,
                        max: 160000,
                        ticks: {
                            stepSize: 40000,
                            color: "#94a3b8",
                            font: { size: 11 },
                            callback: function (value) {
                                return "₱" + value / 1000 + "k";
                            },
                        },
                        grid: {
                            color: "#f1f5f9",
                            borderDash: [4, 4],
                        },
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
                        data: [7, 2, 3],
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