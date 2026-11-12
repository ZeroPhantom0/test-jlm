{{-- resources/views/admin/reservations.blade.php --}}
@extends('layouts.admin', ['active' => 'reservations', 'pageTitle' => 'Reservations'])
@section('title', 'Reservations')

@section('content')
    <div class="p-4 md:p-8 max-w-[1400px] mx-auto space-y-6">

        {{-- Page Header --}}
        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-teal-100 text-teal-600 flex items-center justify-center text-xl">
                    <i class="fa-regular fa-calendar-check"></i>
                </div>
                <div>
                    <h1 class="text-[22px] font-extrabold text-slate-900">Manage Reservations</h1>
                    <p class="text-[13px] text-slate-500 mt-0.5">Review and approve tenant reservation requests</p>
                </div>
            </div>
        </div>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            <div class="bg-white rounded-2xl px-5 py-4 border border-slate-100 shadow-sm">
                <div class="text-[13px] font-semibold text-slate-500">Total Pending</div>
                <div id="metric-total" class="text-[26px] font-extrabold text-slate-900 mt-2">
                    {{ $metrics['pending'] }}</div>
            </div>
            <div class="bg-white rounded-2xl px-5 py-4 border border-slate-100 shadow-sm">
                <div class="text-[13px] font-semibold text-slate-500">Pending Review</div>
                <div id="metric-pending"
                    class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-yellow-100 text-amber-700 text-base font-extrabold mt-2">
                    {{ $metrics['pending'] }}</div>
            </div>
            {{-- New Expired Card --}}
            <div class="bg-white rounded-2xl px-5 py-4 border border-slate-100 shadow-sm">
                <div class="text-[13px] font-semibold text-slate-500">Expired</div>
                <div id="metric-expired"
                    class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-rose-100 text-rose-700 text-base font-extrabold mt-2">
                    {{ $metrics['expired'] }}</div>
            </div>
        </div>

        {{-- Table Card --}}
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 space-y-5">

            {{-- Toolbar --}}
            <div class="flex flex-col md:flex-row md:items-center gap-2.5">
                <div class="relative flex-1">
                    <i
                        class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[11px] pointer-events-none"></i>
                    <input type="text" id="res-search" placeholder="Search by name, email, or room..."
                        class="w-full h-9 pl-8 pr-3 rounded-lg border border-slate-200 bg-slate-50/60 text-[12.5px] text-slate-800 placeholder-slate-400 outline-none transition focus:border-teal-400 focus:bg-white focus:ring-2 focus:ring-teal-100">
                </div>
            </div>

            {{-- Table --}}
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr
                            class="text-[11px] uppercase tracking-wider font-bold text-slate-400 border-b border-slate-200 whitespace-nowrap">
                            <th class="py-3 px-4">Tenant</th>
                            <th class="py-3 px-4">Desired Room</th>
                            <th class="py-3 px-4">Check-in</th>
                            <th class="py-3 px-4">Check-out</th>
                            <th class="py-3 px-4">Reservation Fee</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="res-tbody" data-paginate data-pager-reset="#res-search">
                        @forelse($reservations as $r)
                            <tr class="res-row border-b border-slate-50 last:border-0" data-id="{{ $r->id }}"
                                data-status="{{ $r->status }}"
                                data-search="{{ strtolower($r->tenant_name . ' ' . $r->email . ' ' . $r->phone . ' ' . $r->desired_room . ' ' . $r->reference_no) }}">
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-8 h-8 rounded-full {{ $r->avatar_bg }} {{ $r->avatar_color }} flex items-center justify-center text-[12px] font-bold">
                                            {{ $r->tenant_initial }}
                                        </div>
                                        <div>
                                            <div class="font-semibold text-slate-900 text-[13.5px]">{{ $r->tenant_name }}
                                            </div>
                                            <div class="text-[11px] text-slate-400 mt-0.5">{{ $r->reference_no }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="text-[12.5px] font-medium text-slate-700">{{ $r->desired_room }}</div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-2 text-[12.5px] text-slate-700"><i
                                            class="fa-regular fa-calendar text-slate-400 text-[11px]"></i>
                                        {{ $r->formatted_check_in }}</div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div
                                        class="flex items-center gap-2 text-[12.5px] {{ is_null($r->check_out) ? 'text-slate-400 italic' : 'text-slate-700' }}">
                                        <i class="fa-regular fa-calendar-xmark text-slate-400 text-[11px]"></i>
                                        {{ $r->formatted_check_out }}
                                    </div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-emerald-600 text-[13px] tracking-tight">
                                        {{ $r->formatted_fee }}</div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span
                                        class="inline-flex items-center gap-1.5 {{ $r->status_class }} px-2.5 py-1 rounded-full text-[10.5px] font-bold whitespace-nowrap">
                                        <i class="{{ $r->status_icon }}"></i> {{ $r->status }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center justify-end gap-3">
                                        @if ($r->status === 'Pending')
                                            <i class="fa-regular fa-circle-check text-emerald-500 icon-approve"
                                                data-res-id="{{ $r->id }}"></i>
                                            <i class="fa-regular fa-circle-xmark text-rose-500 icon-reject"
                                                data-res-id="{{ $r->id }}"></i>
                                        @endif
                                        <i class="fa-regular fa-eye text-slate-400 icon-view"
                                            data-res-key="{{ $r->reference_no }}"></i>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-slate-400">
                                    <div class="flex flex-col items-center justify-center gap-3">
                                        <div class="w-14 h-14 rounded-full bg-slate-100 flex items-center justify-center">
                                            <i class="fa-regular fa-calendar-xmark text-slate-400 text-xl"></i>
                                        </div>
                                        <div class="text-[13px] font-medium">No pending reservations found.</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="admin-reservations-modals" data-reservations='@json($reservationsForReact)'></div>
@endsection

@push('scripts')
    <script>
        (function() {
            window.addEventListener('load', () => {
                const tbody = document.getElementById('res-tbody');
                const searchInput = document.getElementById('res-search');
                
                // --- Filter Logic ---
                function applyFilters() {
                    const q = (searchInput?.value || '').toLowerCase().trim();
                    const rows = tbody.querySelectorAll('.res-row');
                    let visible = 0;
                    rows.forEach((row) => {
                        const searchMatch = !q || (row.dataset.search || '').includes(q);
                        row.style.display = searchMatch ? '' : 'none';
                        if (searchMatch) visible++;
                    });

                    // "No matches" row (re-created after live refreshes replace the tbody)
                    let emptyRow = tbody.querySelector('#res-empty-filter');
                    if (!emptyRow) {
                        emptyRow = document.createElement('tr');
                        emptyRow.id = 'res-empty-filter';
                        emptyRow.innerHTML = `<td colspan="7" class="py-10 text-center text-slate-400 text-sm">
                            <i class="fa-solid fa-magnifying-glass text-3xl mb-2 block opacity-50"></i>
                            No reservations match your search.
                        </td>`;
                        tbody.appendChild(emptyRow);
                    }
                    emptyRow.style.display = rows.length > 0 && visible === 0 ? '' : 'none';
                }
                applyFilters();
                searchInput?.addEventListener('input', applyFilters);

                // --- Event Delegation for Buttons ---
                tbody.addEventListener('click', (e) => {
                    const approveBtn = e.target.closest('.icon-approve');
                    if (approveBtn) {
                        window.dispatchEvent(new CustomEvent('reservation:approve', { detail: { id: +approveBtn.dataset.resId } }));
                        return;
                    }

                    const rejectBtn = e.target.closest('.icon-reject');
                    if (rejectBtn) {
                        window.dispatchEvent(new CustomEvent('reservation:reject', { detail: { id: +rejectBtn.dataset.resId } }));
                        return;
                    }

                    const viewBtn = e.target.closest('.icon-view');
                    if (viewBtn) {
                        window.dispatchEvent(new CustomEvent('reservation:view', { detail: { key: viewBtn.dataset.resKey } }));
                        return;
                    }
                });

                // --- Silent Fetch & Render ---
                async function refreshReservations() {
                    try {
                        const res = await fetch('/admin/reservations/data', {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            cache: 'no-store'
                        });
                        if (!res.ok) return;
                        
                        const data = await res.json();

                        // 1. Update Metrics
                        if (data.metrics) {
                            const elTotal = document.getElementById('metric-total');
                            const elPending = document.getElementById('metric-pending');
                            const elExpired = document.getElementById('metric-expired');
                            if (elTotal) elTotal.textContent = data.metrics.pending;
                            if (elPending) elPending.textContent = data.metrics.pending;
                            if (elExpired) elExpired.textContent = data.metrics.expired;
                        }

                        // 2. Update Table Rows
                        if (data.rows && data.rows.length > 0) {
                            tbody.innerHTML = data.rows.map(r => `
                                <tr class="res-row border-b border-slate-50 last:border-0" data-id="${r.id}" data-status="${r.status}" data-search="${(r.tenant_name + ' ' + r.email + ' ' + r.phone + ' ' + r.desired_room + ' ' + r.reference_no).toLowerCase()}">
                                    <td class="py-3.5 px-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full ${r.avatar_bg} ${r.avatar_color} flex items-center justify-center text-[12px] font-bold">
                                                ${r.tenant_initial}
                                            </div>
                                            <div>
                                                <div class="font-semibold text-slate-900 text-[13.5px]">${r.tenant_name}</div>
                                                <div class="text-[11px] text-slate-400 mt-0.5">${r.reference_no}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="text-[12.5px] font-medium text-slate-700">${r.desired_room}</div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="flex items-center gap-2 text-[12.5px] text-slate-700">
                                            <i class="fa-regular fa-calendar text-slate-400 text-[11px]"></i> ${r.formatted_check_in}
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="flex items-center gap-2 text-[12.5px] ${r.check_out_is_null ? 'text-slate-400 italic' : 'text-slate-700'}">
                                            <i class="fa-regular fa-calendar-xmark text-slate-400 text-[11px]"></i> ${r.formatted_check_out}
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-emerald-600 text-[13px] tracking-tight">${r.formatted_fee}</div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span class="inline-flex items-center gap-1.5 ${r.status_class} px-2.5 py-1 rounded-full text-[10.5px] font-bold whitespace-nowrap">
                                            <i class="${r.status_icon}"></i> ${r.status}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="flex items-center justify-end gap-3">
                                            ${r.status === 'Pending' ? `
                                                <i class="fa-regular fa-circle-check text-emerald-500 icon-approve" data-res-id="${r.id}"></i>
                                                <i class="fa-regular fa-circle-xmark text-rose-500 icon-reject" data-res-id="${r.id}"></i>
                                            ` : ''}
                                            <i class="fa-regular fa-eye text-slate-400 icon-view" data-res-key="${r.reference_no}"></i>
                                        </div>
                                    </td>
                                </tr>
                            `).join('');
                        } else {
                            tbody.innerHTML = `
                                <tr>
                                    <td colspan="7" class="py-12 text-center text-slate-400">
                                        <div class="flex flex-col items-center justify-center gap-3">
                                            <div class="w-14 h-14 rounded-full bg-slate-100 flex items-center justify-center">
                                                <i class="fa-regular fa-calendar-xmark text-slate-400 text-xl"></i>
                                            </div>
                                            <div class="text-[13px] font-medium">No pending reservations found.</div>
                                        </div>
                                    </td>
                                </tr>
                            `;
                        }

                        // 3. Update React Modal's data
                        const modalRoot = document.getElementById('admin-reservations-modals');
                        if (modalRoot && data.react) {
                            modalRoot.dataset.reservations = JSON.stringify(data.react);
                            window.dispatchEvent(new CustomEvent('reservation:data-updated', { detail: data.react }));
                        }

                        applyFilters();
                    } catch (e) {
                        console.error('Failed to refresh admin reservations', e);
                    }
                }

                // --- WebSocket Listener ---
                if (window.Echo) {
                    window.Echo.private('admin.reservations')
                        .listen('.reservation.updated', () => {
                            setTimeout(() => refreshReservations(), 800);
                        });
                }
                
                window.addEventListener("reservation:refresh", refreshReservations);
            });
        })();
    </script>
@endpush
