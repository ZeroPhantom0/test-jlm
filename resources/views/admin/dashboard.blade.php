@extends('layouts.admin', [
    'active'    => 'dashboard',
    'pageTitle' => 'Dashboard',
])
@section('title', 'Dashboard')

@section('content')
<div class="dashboard-container">
    <!-- LEFT SECTION -->
    <div class="left-section">
        <div class="metrics-grid">
            <!-- 1. Total Revenue -->
            <div class="metric-card">
                <div class="metric-header">
                    <div class="metric-icon-box" style="background: var(--mint-bg); color: var(--mint-text)">₱</div>
                </div>
                <div class="metric-value" id="metric-revenue">₱{{ number_format($revenue, 0) }}</div>
                <div class="metric-label">Total Revenue</div>
                <div class="metric-subtext" id="label-revenue">Collected up to {{ $selectedDate->format('M d, Y') }}</div>
            </div>

            <!-- 2. Active Tenants -->
            <div class="metric-card">
                <div class="metric-header">
                    <div class="metric-icon-box" style="background: var(--blue-bg); color: var(--blue-text)">
                        <i class="fa-regular fa-user"></i>
                    </div>
                </div>
                <div class="metric-value" id="metric-active-tenants">{{ $activeTenants }}</div>
                <div class="metric-label">Active Tenants</div>
                <div class="metric-subtext">Currently residing</div>
            </div>

            <!-- 3. Service Requests (ALL 3 services combined) -->
            <div class="metric-card" title="Maintenance: {{ $serviceBreakdown['maintenance'] }} | Room Change: {{ $serviceBreakdown['room_change'] }} | Stay Extension: {{ $serviceBreakdown['stay_extension'] }}">
                <div class="metric-header">
                    <div class="metric-icon-box" style="background: var(--yellow-bg); color: var(--yellow-text)">
                        <i class="fa-solid fa-wrench"></i>
                    </div>
                </div>
                <div class="metric-value" id="metric-service-requests">{{ $serviceRequests }}</div>
                <div class="metric-label">Service Requests</div>
                <div class="metric-subtext" id="label-service-requests">
                    M: {{ $serviceBreakdown['maintenance'] }} ·
                    RC: {{ $serviceBreakdown['room_change'] }} ·
                    SE: {{ $serviceBreakdown['stay_extension'] }}
                </div>
            </div>

            <!-- 4. Pending Payments -->
            <div class="metric-card">
                <div class="metric-header">
                    <div class="metric-icon-box" style="background: var(--pink-bg); color: var(--pink-text)">
                        <i class="fa-solid fa-wave-square"></i>
                    </div>
                </div>
                <div class="metric-value" id="metric-pending-payments">{{ $pendingPaymentsCount }}</div>
                <div class="metric-label">Pending Payments</div>
                <div class="metric-subtext" id="label-pending-payments">₱{{ number_format($pendingPaymentsAmount, 0) }} unpaid</div>
            </div>

            <!-- 5. Occupancy Rate -->
            <div class="metric-card">
                <div class="metric-header">
                    <div class="metric-icon-box" style="background: #e0f2fe; color: #0284c7">
                        <i class="fa-solid fa-house"></i>
                    </div>
                </div>
                <div class="metric-value" id="metric-occupancy">{{ $occupancyRate }}%</div>
                <div class="metric-label">Occupancy Rate</div>
                <div class="metric-subtext" id="label-occupancy">{{ $occupiedRooms }} of {{ $totalRooms }} rooms occupied</div>
            </div>

            <!-- 6. Reservations -->
            <div class="metric-card">
                <div class="metric-header">
                    <div class="metric-icon-box" style="background: var(--purple-bg); color: var(--purple-text)">
                        <i class="fa-regular fa-calendar"></i>
                    </div>
                </div>
                <div class="metric-value" id="metric-reservations">{{ $reservations }}</div>
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

    <!-- RIGHT COLUMN -->
    <div class="right-stack">
        <!-- Dynamic Calendar Card -->
        <div class="card-panel calendar-card">
            <div class="calendar-header">
                <a id="cal-prev" href="#" class="cursor-pointer">
                    <i class="fa-solid fa-chevron-left"></i>
                </a>
                <h4 id="calendar-month-title">{{ $selectedDate->format('F Y') }}</h4>
                <a id="cal-next" href="#" class="cursor-pointer">
                    <i class="fa-solid fa-chevron-right"></i>
                </a>
            </div>

            <div class="calendar-grid" id="calendar-grid">
                <div class="day-head">SUN</div>
                <div class="day-head">MON</div>
                <div class="day-head">TUE</div>
                <div class="day-head">WED</div>
                <div class="day-head">THU</div>
                <div class="day-head">FRI</div>
                <div class="day-head">SAT</div>
                {{-- JS will fill this --}}
            </div>
        </div>

        <!-- Occupancy Status Donut Chart Card -->
        <div class="card-panel">
            <div class="panel-header" style="margin-bottom: 0">
                <div class="panel-title">
                    <h3>Occupancy Status</h3>
                    <p>Total: <span id="occ-total-label">{{ $totalRooms }}</span> rooms</p>
                </div>
            </div>

            <div class="occupancy-chart-wrapper">
                <div style="width: 170px; height: 170px; position: relative">
                    <canvas id="occupancyChart"></canvas>
                    <div class="donut-center-text">
                        <h2 id="occ-rate-center">{{ $occupancyRate }}%</h2>
                        <p>Occupied</p>
                    </div>
                </div>

                <div class="occupancy-legend">
                    <div class="occ-item">
                        <div class="occ-left">
                            <div class="legend-circle" style="background: #10b981"></div>
                            <span>Occupied</span>
                        </div>
                        <div class="occ-right" id="occ-occupied">{{ $occupiedRooms }}/{{ $totalRooms }}</div>
                    </div>
                    <div class="occ-item">
                        <div class="occ-left">
                            <div class="legend-circle" style="background: #7c3aed"></div>
                            <span>Reserved</span>
                        </div>
                        <div class="occ-right" id="occ-reserved">{{ $reservedRooms }}/{{ $totalRooms }}</div>
                    </div>
                    <div class="occ-item">
                        <div class="occ-left">
                            <div class="legend-circle" style="background: #cbd5e1"></div>
                            <span>Available</span>
                        </div>
                        <div class="occ-right" id="occ-available">{{ $availableRooms }}/{{ $totalRooms }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity Card -->
        <div class="card-panel">
            <div class="panel-header flex justify-between items-center">
                <div class="panel-title">
                    <h3>Recent Activity</h3>
                </div>
                <div class="flex items-center gap-2 text-[11px] font-semibold text-emerald-600">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Live</span>
                </div>
            </div>

            <div class="activity-list" id="recent-activity-list">
                @forelse($recentActivity as $act)
                    <div class="activity-item">
                        <div class="activity-left">
                            <div class="act-icon" style="background: #dcfce7; color: #16a34a">
                                <i class="{{ $act['icon'] }}"></i>
                            </div>
                            <div class="act-details">
                                <h5 class="font-bold text-slate-800">{{ $act['action'] }}</h5>
                                <p class="text-slate-500 text-xs">{{ Str::limit($act['description'], 40) }}</p>
                            </div>
                        </div>
                        <div class="act-time">{{ $act['time'] }}</div>
                    </div>
                @empty
                    <div class="text-center text-slate-400 text-sm py-4">No recent activity found.</div>
                @endforelse
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
        // 1. FINANCIAL OVERVIEW CHART
        // -------------------------------------------------------------
        const ctxFin = document.getElementById("financialChart").getContext("2d");
        const incomeGradient = ctxFin.createLinearGradient(0, 0, 0, 250);
        incomeGradient.addColorStop(0, "rgba(16, 185, 129, 0.15)");
        incomeGradient.addColorStop(1, "rgba(16, 185, 129, 0)");
        const expenseGradient = ctxFin.createLinearGradient(0, 0, 0, 250);
        expenseGradient.addColorStop(0, "rgba(244, 63, 94, 0.15)");
        expenseGradient.addColorStop(1, "rgba(244, 63, 94, 0)");

        let finChart = new Chart(ctxFin, {
            type: "line",
            data: {
                labels: @json($chartLabels),
                datasets: [
                    {
                        label: "Income",
                        data: @json($chartIncome),
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
                        data: @json($chartExpenses),
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
                plugins: { legend: { display: false }, tooltip: { mode: "index", intersect: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { color: "#94a3b8", font: { size: 11 } } },
                    y: {
                        min: 0,
                        ticks: { color: "#94a3b8", font: { size: 11 }, callback: (value) => "₱" + value / 1000 + "k" },
                        grid: { color: "#f1f5f9", borderDash: [4, 4] },
                    },
                },
            },
        });

        // -------------------------------------------------------------
        // 2. OCCUPANCY DONUT CHART
        // -------------------------------------------------------------
        const ctxOcc = document.getElementById("occupancyChart").getContext("2d");
        let occChart = new Chart(ctxOcc, {
            type: "doughnut",
            data: {
                labels: ["Occupied", "Reserved", "Available"],
                datasets: [{
                    data: [{{ $occupiedRooms }}, {{ $reservedRooms }}, {{ $availableRooms }}],
                    backgroundColor: ["#10b981", "#7c3aed", "#cbd5e1"],
                    borderWidth: 0,
                    hoverOffset: 4,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: "78%",
                plugins: { legend: { display: false }, tooltip: { enabled: true } },
            },
        });

        // -------------------------------------------------------------
        // 3. CALENDAR RENDERER
        // -------------------------------------------------------------
        const calendarGrid = document.getElementById('calendar-grid');
        const monthTitleEl = document.getElementById('calendar-month-title');
        const prevBtn = document.getElementById('cal-prev');
        const nextBtn = document.getElementById('cal-next');

        let currentDate = "{{ $selectedDateStr }}";
        
        // Get today's date strictly in YYYY-MM-DD format
        const today = new Date();
        const todayStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
        const todayYear = today.getFullYear();
        const todayMonth = today.getMonth() + 1;

        function renderCalendar(selectedDateStr, year, month, daysInMonth, firstDayOfMonth) {
            // Title
            const dt = new Date(selectedDateStr + 'T00:00:00');
            monthTitleEl.textContent = dt.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });

            // Prev/Next month links
            const prev = new Date(dt.getFullYear(), dt.getMonth() - 1, 1);
            const next = new Date(dt.getFullYear(), dt.getMonth() + 1, 1);
            prevBtn.href = `{{ route('admin.dashboard') }}?date=${prev.toISOString().split('T')[0]}`;
            
            // Disable "Next" button if we are already in the current month
            if (year > todayYear || (year === todayYear && month >= todayMonth)) {
                nextBtn.removeAttribute('href');
                nextBtn.classList.add('opacity-30', 'cursor-not-allowed', 'pointer-events-none');
            } else {
                nextBtn.href = `{{ route('admin.dashboard') }}?date=${next.toISOString().split('T')[0]}`;
                nextBtn.classList.remove('opacity-30', 'cursor-not-allowed', 'pointer-events-none');
            }

            // Cells
            const dayHeaders = ['SUN', 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT'];
            let html = dayHeaders.map(d => `<div class="day-head">${d}</div>`).join('');

            for (let i = 0; i < firstDayOfMonth; i++) {
                html += `<div class="day-val opacity-0"></div>`;
            }
            
            const selectedDay = dt.getDate();
            for (let i = 1; i <= daysInMonth; i++) {
                const dateStr = `${year}-${String(month).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
                const isSelected = (i === selectedDay);
                const isToday = (dateStr === todayStr);
                
                // Determine if date is in the future
                const currentLoopDate = new Date(year, month - 1, i);
                const isFuture = currentLoopDate > today;

                // Base classes (matching your project palette)
                let classes = "day-val block text-center leading-8 transition-colors duration-150";
                
                // Highlighting
                if (isSelected) {
                    classes += " active bg-teal-600 text-white rounded-full shadow-sm";
                } else if (isFuture) {
                    classes += " text-slate-300 cursor-not-allowed";
                } else {
                    classes += " text-slate-700 hover:bg-teal-50 hover:text-teal-700 cursor-pointer rounded-full";
                }

                // Today indicator ring (matches brand orange/teal)
                if (isToday && !isSelected) {
                    classes += " ring-2 ring-[#FF8904] ring-offset-1 font-bold rounded-full";
                }

                // Build HTML
                if (isFuture) {
                    // Render as a non-clickable div for future dates
                    html += `<div class="${classes}">${i}</div>`;
                } else {
                    // Render as an anchor tag for clickable dates
                    html += `<a href="{{ route('admin.dashboard') }}?date=${dateStr}" class="${classes}">${i}</a>`;
                }
            }
            calendarGrid.innerHTML = html;
        }

        // Initial render
        renderCalendar("{{ $selectedDateStr }}", {{ $year }}, {{ $month }}, {{ $daysInMonth }}, {{ $firstDayOfMonth }});

        // -------------------------------------------------------------
        // 4. REAL-TIME DASHBOARD REFRESH (Echo + Polling)
        // -------------------------------------------------------------
        const DATA_URL = "{{ route('admin.dashboard.data') }}";

        function escapeHtml(s) {
            return String(s ?? '').replace(/[&<>"']/g, c => (
                { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
            ));
        }

        async function refreshDashboard() {
            const dateQuery = new URLSearchParams(window.location.search).get('date') || '';
            try {
                const res = await fetch(DATA_URL + (dateQuery ? '?date=' + dateQuery : ''), {
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin',
                });
                if (!res.ok) return;
                const data = await res.json();

                // --- Metrics ---
                document.getElementById('metric-revenue').textContent = '₱' + Number(data.revenue).toLocaleString();
                document.getElementById('label-revenue').textContent = 'Collected up to ' + new Date(data.selectedDateStr + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });

                document.getElementById('metric-active-tenants').textContent = data.activeTenants;
                document.getElementById('metric-service-requests').textContent = data.serviceRequests;
                document.getElementById('label-service-requests').textContent =
                    `M: ${data.serviceBreakdown.maintenance} · RC: ${data.serviceBreakdown.room_change} · SE: ${data.serviceBreakdown.stay_extension}`;

                document.getElementById('metric-pending-payments').textContent = data.pendingPaymentsCount;
                document.getElementById('label-pending-payments').textContent = '₱' + Number(data.pendingPaymentsAmount).toLocaleString() + ' unpaid';

                document.getElementById('metric-occupancy').textContent = data.occupancyRate + '%';
                document.getElementById('label-occupancy').textContent = data.occupiedRooms + ' of ' + data.totalRooms + ' rooms occupied';
                document.getElementById('metric-reservations').textContent = data.reservations;

                // --- Occupancy Donut ---
                occChart.data.datasets[0].data = [data.occupiedRooms, data.reservedRooms, data.availableRooms];
                occChart.update();
                document.getElementById('occ-rate-center').textContent = data.occupancyRate + '%';
                document.getElementById('occ-total-label').textContent = data.totalRooms;
                document.getElementById('occ-occupied').textContent = data.occupiedRooms + '/' + data.totalRooms;
                document.getElementById('occ-reserved').textContent = data.reservedRooms + '/' + data.totalRooms;
                document.getElementById('occ-available').textContent = data.availableRooms + '/' + data.totalRooms;

                // --- Financial Chart ---
                finChart.data.labels = data.chartLabels;
                finChart.data.datasets[0].data = data.chartIncome;
                finChart.data.datasets[1].data = data.chartExpenses;
                finChart.update();

                // --- Calendar (auto-swap if date changed via URL) ---
                const newDate = data.selectedDateStr;
                if (newDate !== currentDate) {
                    currentDate = newDate;
                    renderCalendar(newDate, data.year, data.month, data.daysInMonth, data.firstDayOfMonth);
                }

                // --- Recent Activity ---
                const activityList = document.getElementById('recent-activity-list');
                if (data.recentActivity && data.recentActivity.length > 0) {
                    activityList.innerHTML = data.recentActivity.map(act => `
                        <div class="activity-item">
                            <div class="activity-left">
                                <div class="act-icon ${escapeHtml(act.icon_bg)}">
                                    <i class="${escapeHtml(act.icon)}"></i>
                                </div>
                                <div class="act-details">
                                    <h5 class="font-bold text-slate-800">${escapeHtml(act.action)}</h5>
                                    <p class="text-slate-500 text-xs">${escapeHtml(act.description.substring(0, 40))}${act.description.length > 40 ? '...' : ''}</p>
                                </div>
                            </div>
                            <div class="act-time">${escapeHtml(act.time)}</div>
                        </div>
                    `).join('');
                } else {
                    activityList.innerHTML = `<div class="text-center text-slate-400 text-sm py-4">No recent activity found.</div>`;
                }
            } catch (e) {
                console.warn('[Dashboard] Refresh failed:', e);
            }
        }

        // --- Echo channels ---
        if (window.Echo) {
            window.Echo.channel('admin.reservations').listen('.reservation.updated', () => {
                console.info('[Live] Reservation updated → refresh');
                refreshDashboard();
            });
            window.Echo.channel('admin.billing').listen('.invoice.updated', () => {
                console.info('[Live] Invoice updated → refresh');
                refreshDashboard();
            });
            window.Echo.channel('admin.services').listen('.maintenance.updated',     () => refreshDashboard());
            window.Echo.channel('admin.services').listen('.room-change.updated',     () => refreshDashboard());
            window.Echo.channel('admin.services').listen('.stay-extension.updated',  () => refreshDashboard());
        }

        // --- Polling fallback ---
        setInterval(() => {
            if (!window.__liveAvailable) {
                refreshDashboard();
            }
        }, 3000);
    });
</script>
@endpush