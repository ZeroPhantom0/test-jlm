@extends('layouts.admin', ['active' => 'reservations', 'pageTitle' => 'Reservations'])
@section('title', 'Reservations')

@section('content')
    <div class="p-4 md:p-8 max-w-[1400px] mx-auto space-y-6">

        {{-- Page Header --}}
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-teal-100 text-teal-600 flex items-center justify-center text-xl">
                <i class="fa-regular fa-calendar-check"></i>
            </div>
            <div>
                <h1 class="text-[22px] font-extrabold text-slate-900">Manage Reservations</h1>
                <p class="text-[13px] text-slate-500 mt-0.5">Review and approve tenant reservation requests</p>
            </div>
        </div>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div class="bg-white rounded-2xl px-5 py-4 border border-slate-100 shadow-sm">
                <div class="text-[13px] font-semibold text-slate-500">Total</div>
                <div class="text-[26px] font-extrabold text-slate-900 mt-2">{{ $metrics['total'] }}</div>
            </div>
            <div class="bg-white rounded-2xl px-5 py-4 border border-slate-100 shadow-sm">
                <div class="text-[13px] font-semibold text-slate-500">Pending</div>
                <div
                    class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-yellow-100 text-amber-700 text-base font-extrabold mt-2">
                    {{ $metrics['pending'] }}</div>
            </div>
            <div class="bg-white rounded-2xl px-5 py-4 border border-slate-100 shadow-sm">
                <div class="text-[13px] font-semibold text-slate-500">Approved</div>
                <div
                    class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-green-100 text-green-600 text-base font-extrabold mt-2">
                    {{ $metrics['approved'] }}</div>
            </div>
            <div class="bg-white rounded-2xl px-5 py-4 border border-slate-100 shadow-sm">
                <div class="text-[13px] font-semibold text-slate-500">Rejected</div>
                <div
                    class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-rose-100 text-rose-600 text-base font-extrabold mt-2">
                    {{ $metrics['rejected'] }}</div>
            </div>
            <div class="bg-white rounded-2xl px-5 py-4 border border-slate-100 shadow-sm">
                <div class="text-[13px] font-semibold text-slate-500">Expired</div>
                <div
                    class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-slate-100 text-slate-500 text-base font-extrabold mt-2">
                    {{ $metrics['expired'] }}</div>
            </div>
        </div>

        {{-- Table Card --}}
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 space-y-5">

            {{-- Toolbar: search + status filter (client-side, no reload) --}}
            <div class="flex flex-col md:flex-row md:items-center gap-2.5">
                <div class="relative flex-1">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[11px] pointer-events-none"></i>
                    <input type="text" id="res-search"
                        placeholder="Search by name, email, or room..."
                        class="w-full h-9 pl-8 pr-3 rounded-lg border border-slate-200 bg-slate-50/60 text-[12.5px] text-slate-800 placeholder-slate-400 outline-none transition focus:border-teal-400 focus:bg-white focus:ring-2 focus:ring-teal-100">
                </div>
                <select id="res-status-filter"
                    class="h-9 pl-3 pr-8 rounded-lg border border-slate-200 bg-slate-50/60 text-[12.5px] text-slate-700 outline-none cursor-pointer appearance-none bg-[url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 fill=%22none%22 viewBox=%220 0 24 24%22 stroke=%22%2394a3b8%22 stroke-width=%222%22><path stroke-linecap=%22round%22 stroke-linejoin=%22round%22 d=%22M19 9l-7 7-7-7%22/></svg>')] bg-[length:14px] bg-[right_10px_center] bg-no-repeat transition focus:border-teal-400 focus:bg-white focus:ring-2 focus:ring-teal-100 min-w-[180px]">
                    <option value="">All Statuses</option>
                    <option value="Pending">Pending</option>
                    <option value="Approved">Approved</option>
                    <option value="Rejected">Rejected</option>
                    <option value="Expired">Expired</option>
                </select>
            </div>

            {{-- Table --}}
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-[13px] font-bold text-slate-600 border-b border-slate-100 whitespace-nowrap">
                            <th class="py-3 px-4">Resident</th>
                            <th class="py-3 px-4">Contact</th>
                            <th class="py-3 px-4">Desired Room</th>
                            <th class="py-3 px-4">Check-in</th>
                            <th class="py-3 px-4">Check-out</th>
                            <th class="py-3 px-4">Reservation Fee</th>
                            <th class="py-3 px-4">Submitted</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="res-tbody">
                        @forelse($reservations as $res)
                            @php
                                $rowSearch = strtolower(trim(
                                    ($res->tenant_name   ?? '') . ' ' .
                                    ($res->email         ?? '') . ' ' .
                                    ($res->phone         ?? '') . ' ' .
                                    ($res->desired_room  ?? '') . ' ' .
                                    ($res->reference_no  ?? '')
                                ));
                            @endphp
                            <tr class="res-row border-b border-slate-50 last:border-0"
                                data-status="{{ $res->status }}"
                                data-search="{{ $rowSearch }}">
                                {{-- Resident --}}
                                <td class="py-4 px-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-9 h-9 rounded-full {{ $res->avatar_bg }} {{ $res->avatar_color }} flex items-center justify-center text-[14px] font-bold shrink-0">
                                            {{ $res->tenant_initial }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-900 text-[13px]">{{ $res->tenant_name }}</div>
                                            <div class="text-[11px] text-slate-400 mt-0.5">{{ $res->reference_no }}</div>
                                        </div>
                                    </div>
                                </td>

                                {{-- Contact --}}
                                <td class="py-4 px-4">
                                    <div class="text-[13px] text-slate-700">{{ $res->email }}</div>
                                    <div class="text-[11px] text-slate-400 mt-0.5">{{ $res->phone }}</div>
                                </td>

                                {{-- Desired Room --}}
                                <td class="py-4 px-4">
                                    <div class="text-[13px] font-medium text-slate-700">{{ $res->desired_room }}</div>
                                </td>

                                {{-- Check-in --}}
                                <td class="py-4 px-4">
                                    <div class="flex items-center gap-2 text-[13px] text-slate-700">
                                        <i class="fa-regular fa-calendar text-slate-400 text-[13px]"></i>
                                        {{ $res->formatted_check_in }}
                                    </div>
                                </td>

                                {{-- Check-out --}}
                                <td class="py-4 px-4">
                                    <div
                                        class="flex items-center gap-2 text-[13px] {{ $res->check_out ? 'text-slate-700' : 'text-slate-400 italic' }}">
                                        <i class="fa-regular fa-calendar-xmark text-slate-400 text-[13px]"></i>
                                        {{ $res->formatted_check_out }}
                                    </div>
                                </td>

                                {{-- Fee --}}
                                <td class="py-4 px-4">
                                    <div class="font-extrabold text-emerald-600 text-[13px]">{{ $res->formatted_fee }}
                                    </div>
                                </td>

                                {{-- Submitted --}}
                                <td class="py-4 px-4 text-[13px] text-slate-600 whitespace-nowrap">
                                    {{ $res->created_at?->format('M d, Y') }}
                                </td>

                                {{-- Status --}}
                                <td class="py-4 px-4">
                                    <span
                                        class="inline-flex items-center gap-1.5 {{ $res->status_class }} px-3 py-1 rounded-full text-[11px] font-bold whitespace-nowrap">
                                        <i class="{{ $res->status_icon }}"></i> {{ $res->status }}
                                    </span>
                                </td>

                                {{-- Actions --}}
                                <td class="py-4 px-4">
                                    <div class="flex items-center justify-end gap-3">
                                        @if ($res->status === 'Pending')
                                            <i class="fa-regular fa-circle-check text-emerald-500 text-[15px] cursor-pointer hover:text-emerald-600 transition icon-approve"
                                                title="Approve" data-res-id="{{ $res->id }}"></i>
                                            <i class="fa-regular fa-circle-xmark text-rose-500 text-[15px] cursor-pointer hover:text-rose-600 transition icon-reject"
                                                title="Reject" data-res-id="{{ $res->id }}"></i>
                                        @endif
                                        <i class="fa-regular fa-eye text-slate-700 text-[15px] cursor-pointer hover:text-orange-500 transition icon-view"
                                            title="View Details" data-res-key="{{ $res->reference_no }}"></i>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-12 text-slate-400">
                                    <i class="fa-solid fa-inbox text-4xl mb-3"></i>
                                    <p class="text-sm">No reservations found.</p>
                                </td>
                            </tr>
                        @endforelse

                        <tr id="res-empty-filter" class="hidden">
                            <td colspan="9" class="text-center py-12 text-slate-400">
                                <i class="fa-solid fa-magnifying-glass text-4xl mb-3 opacity-50"></i>
                                <p class="text-sm">No reservations match your current filters.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- React Modal Mount Point with data --}}
    <div id="admin-reservations-modals" data-reservations='@json($reservationsForReact)'></div>
@endsection

@push('scripts')
<script>
(function () {
    const rows         = Array.from(document.querySelectorAll('.res-row'));
    const searchInput  = document.getElementById('res-search');
    const statusFilter = document.getElementById('res-status-filter');
    const emptyRow     = document.getElementById('res-empty-filter');

    function applyFilters() {
        const q      = (searchInput?.value  || '').toLowerCase().trim();
        const status = (statusFilter?.value || '').trim();

        let visible = 0;
        rows.forEach((row) => {
            const haystack  = row.dataset.search || '';
            const rowStatus = row.dataset.status || '';
            const searchMatch = !q || haystack.includes(q);
            const statusMatch = !status || rowStatus === status;
            const show = searchMatch && statusMatch;
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        if (emptyRow) {
            emptyRow.classList.toggle('hidden', !(visible === 0 && rows.length > 0));
        }
    }

    let searchTimer;
    searchInput?.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(applyFilters, 120);
    });

    statusFilter?.addEventListener('change', applyFilters);
    applyFilters();
})();
</script>
@endpush