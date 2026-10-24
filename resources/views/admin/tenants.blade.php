@extends('layouts.admin', ['active' => 'tenants', 'pageTitle' => 'Tenants'])
@section('title', 'Tenants')

@section('content')
    <div class="p-4 md:p-8 max-w-[1400px] mx-auto space-y-5">

        {{-- Page Header --}}
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-xl bg-green-100 text-green-600 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div>
                    <h2 class="text-[22px] font-extrabold text-slate-900 leading-tight">Manage Tenants</h2>
                    <p class="text-[13px] text-slate-500">Directory of current and past tenants</p>
                </div>
            </div>
        </div>

        {{-- Stats Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <div class="bg-white rounded-2xl px-5 py-4 border border-slate-200">
                <div class="text-xs text-slate-500 font-medium">Total</div>
                <div class="text-[26px] font-extrabold text-slate-900 mt-1">{{ $metrics['total'] }}</div>
            </div>
            <div class="bg-white rounded-2xl px-5 py-4 border border-slate-200">
                <div class="text-xs text-slate-500 font-medium">Active</div>
                <div class="text-[26px] font-extrabold text-green-600 mt-1">{{ $metrics['active'] }}</div>
            </div>
            <div class="bg-white rounded-2xl px-5 py-4 border border-slate-200">
                <div class="text-xs text-slate-500 font-medium">Inactive</div>
                <div class="text-[26px] font-extrabold text-slate-500 mt-1">{{ $metrics['inactive'] }}</div>
            </div>
        </div>

        {{-- Table Card --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <div class="flex items-center gap-4 mb-5 flex-wrap">
                <div
                    class="flex-1 bg-slate-50 border border-slate-200 rounded-[10px] px-3.5 py-1.5 flex items-center gap-2.5">
                    <i class="fa-solid fa-magnifying-glass text-slate-400 text-sm"></i>
                    <input type="text" id="tenant-search" placeholder="Search name or email..."
                        class="w-full text-[13px] outline-none bg-transparent">
                </div>
                <select id="tenant-status-filter"
                    class="bg-white border border-slate-300 rounded-[10px] px-4 py-1.5 text-[13px] font-medium text-slate-700 outline-none cursor-pointer min-w-[140px]">
                    <option value="">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-xs font-bold text-slate-500 border-b border-slate-100">
                            <th class="py-3 px-3.5">Name</th>
                            <th class="py-3 px-3.5">Contact</th>
                            <th class="py-3 px-3.5">Room</th>
                            <th class="py-3 px-3.5">Check In</th>
                            <th class="py-3 px-3.5">Check Out</th>
                            <th class="py-3 px-3.5">Rent Rate</th>
                            <th class="py-3 px-3.5">Status</th>
                            <th class="py-3 px-3.5">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="tenant-tbody">
                        @forelse ($tenants as $tenant)
                            @php
                                $reservation = $tenant->approvedReservation;
                                $rowSearch = strtolower(trim(
                                    ($tenant->user->name        ?? '') . ' ' .
                                    ($tenant->user->email       ?? '') . ' ' .
                                    ($tenant->user->phone       ?? '') . ' ' .
                                    ($tenant->room->room_number ?? '')
                                ));
                            @endphp
                            <tr class="tenant-row border-b border-slate-50 last:border-0"
                                data-tenant='@json($tenant->toReactArray())'
                                data-status="{{ $tenant->status }}"
                                data-search="{{ $rowSearch }}">
                                <td class="py-4 px-3.5">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-9 h-9 rounded-full
                                            {{ $tenant->status === 'active' ? 'bg-green-100 text-green-600' : 'bg-slate-100 text-slate-500' }}
                                            flex items-center justify-center font-bold text-[13px]">
                                            {{ $tenant->initial }}
                                        </div>
                                        <div class="font-bold text-slate-900">{{ $tenant->user->name ?? 'Unknown' }}</div>
                                    </div>
                                </td>
                                <td class="py-4 px-3.5">
                                    <div class="flex flex-col gap-0.5 text-xs text-slate-500">
                                        <div class="flex items-center gap-1.5">
                                            <i class="fa-regular fa-envelope text-[11px] w-3"></i>
                                            {{ $tenant->user->email ?? '—' }}
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <i class="fa-solid fa-phone text-[11px] w-3"></i>
                                            {{ $tenant->user->phone ?? '—' }}
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-3.5 text-[13px] font-medium text-slate-900">
                                    {{ $tenant->room->room_number ? 'Room ' . $tenant->room->room_number : '—' }}
                                </td>

                                {{-- CHECK IN --}}
                                <td class="py-4 px-3.5 text-xs text-slate-700">
                                    <div>
                                        {{ $reservation?->check_in ? $reservation->check_in->format('F j, Y') : '—' }}
                                    </div>
                                    <div class="text-slate-500">{{ $tenant->check_in_time_12 }}</div>
                                </td>

                                {{-- CHECK OUT --}}
                                <td class="py-4 px-3.5 text-xs text-slate-700">
                                    <div>
                                        {{ $reservation?->check_out ? $reservation->check_out->format('F j, Y') : '—' }}
                                    </div>
                                    <div class="text-slate-500">{{ $tenant->check_out_time_12 }}</div>
                                </td>

                                <td class="py-4 px-3.5 text-[13px] font-semibold text-slate-900">
                                    ₱{{ number_format($tenant->rent_rate, 0) }}
                                </td>
                                <td class="py-4 px-3.5">
                                    <span
                                        class="inline-block px-2.5 py-1 rounded-xl text-[11px] font-bold
                                    {{ $tenant->status === 'active' ? 'bg-green-100 text-green-600' : 'bg-slate-100 text-slate-500' }}">
                                        {{ ucfirst($tenant->status) }}
                                    </span>
                                </td>
                                <td class="py-4 px-3.5">
                                    <div class="flex items-center gap-3">
                                        <i class="fa-regular fa-eye text-green-500 text-sm cursor-pointer hover:scale-110 transition view-tenant-btn"></i>
                                        <i class="fa-solid fa-pencil text-slate-700 text-sm cursor-pointer hover:scale-110 transition edit-tenant-btn"></i>
                                        <i class="fa-regular fa-trash-can text-red-500 text-sm cursor-pointer hover:scale-110 transition delete-tenant-btn"></i>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-10 text-center text-slate-400 text-sm">
                                    No tenants found.
                                </td>
                            </tr>
                        @endforelse

                        <tr id="tenant-empty-filter" class="hidden">
                            <td colspan="8" class="py-10 text-center text-slate-400 text-sm">
                                <i class="fa-solid fa-magnifying-glass text-3xl mb-2 block opacity-50"></i>
                                No tenants match your current filters.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="admin-tenants-modals"></div>
@endsection

@push('scripts')
<script>
(function () {
    const rows         = Array.from(document.querySelectorAll('.tenant-row'));
    const searchInput  = document.getElementById('tenant-search');
    const statusFilter = document.getElementById('tenant-status-filter');
    const emptyRow     = document.getElementById('tenant-empty-filter');

    function applyFilters() {
        const q      = (searchInput?.value  || '').toLowerCase().trim();
        const status = (statusFilter?.value || '').trim().toLowerCase();

        let visible = 0;
        rows.forEach((row) => {
            const haystack  = row.dataset.search || '';
            const rowStatus = (row.dataset.status || '').toLowerCase();
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