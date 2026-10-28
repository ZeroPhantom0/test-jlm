{{-- resources/views/admin/tenants.blade.php --}}
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
            <div class="bg-white rounded-2xl px-5 py-4 border border-slate-100 shadow-sm">
                <div class="text-[12px] text-slate-500 font-medium mb-1.5">Total</div>
                <div class="text-[26px] font-extrabold text-slate-900">{{ $metrics['total'] }}</div>
            </div>
            <div class="bg-white rounded-2xl px-5 py-4 border border-slate-100 shadow-sm">
                <div class="text-[12px] text-slate-500 font-medium mb-1.5">Active</div>
                <div class="text-[26px] font-extrabold text-green-600">{{ $metrics['active'] }}</div>
            </div>
            <div class="bg-white rounded-2xl px-5 py-4 border border-slate-100 shadow-sm">
                <div class="text-[12px] text-slate-500 font-medium mb-1.5">Inactive</div>
                <div class="text-[26px] font-extrabold text-slate-500">{{ $metrics['inactive'] }}</div>
            </div>
        </div>

        {{-- Table Card --}}
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
            <div class="flex items-center gap-4 mb-5 flex-wrap">
                <div class="flex-1 bg-white border border-slate-300 rounded-[10px] px-3.5 py-1.5 flex items-center gap-2.5">
                    <i class="fa-solid fa-magnifying-glass text-slate-400 text-[13px]"></i>
                    <input type="text" id="tenant-search" placeholder="Search name or email..." class="w-full text-[13px] outline-none bg-transparent">
                </div>
                <select id="tenant-status-filter" class="bg-white border border-slate-300 rounded-[10px] px-3.5 py-1.5 text-[13px] text-slate-700 outline-none cursor-pointer min-w-[160px]">
                    <option value="">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-[11px] uppercase tracking-wider font-bold text-slate-400 border-b border-slate-200">
                            <th class="py-3 px-4">Name</th>
                            <th class="py-3 px-4">Contact</th>
                            <th class="py-3 px-4">Room</th>
                            <th class="py-3 px-4">Rent Rate</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4 text-right">Actions</th>
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
                            <tr class="tenant-row border-b border-slate-100 last:border-0 hover:bg-slate-50/50 transition-colors"
                                data-tenant='@json($tenant->toReactArray())'
                                data-status="{{ $tenant->status }}"
                                data-search="{{ $rowSearch }}">
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full {{ $tenant->status === 'active' ? 'bg-green-100 text-green-600' : 'bg-slate-100 text-slate-500' }} flex items-center justify-center font-bold text-[12px]">
                                            {{ $tenant->initial }}
                                        </div>
                                        <div class="text-[14px] font-semibold text-slate-900 tracking-tight">{{ $tenant->user->name ?? 'Unknown' }}</div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="flex flex-col gap-0.5 text-[11.5px] text-slate-500">
                                        <div class="flex items-center gap-1.5">
                                            <i class="fa-regular fa-envelope text-[10px] w-3 text-slate-400"></i>
                                            {{ $tenant->user->email ?? '—' }}
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <i class="fa-solid fa-phone text-[10px] w-3 text-slate-400"></i>
                                            {{ $tenant->user->phone ?? '—' }}
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-[13px] font-semibold text-slate-900 tracking-tight">
                                    {{ $tenant->room->room_number ? 'Room ' . $tenant->room->room_number : '—' }}
                                </td>
                                <td class="py-3.5 px-4 text-[13.5px] font-bold text-slate-900 tracking-tight">
                                    ₱{{ number_format($tenant->rent_rate, 0) }}
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="inline-block px-2.5 py-1 rounded-md text-[10.5px] font-bold tracking-wide uppercase {{ $tenant->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-600' }}">
                                        {{ ucfirst($tenant->status) }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center justify-end gap-3.5 text-slate-400">
                                        <i class="fa-regular fa-eye text-[14px] cursor-pointer hover:text-slate-900 transition-colors view-tenant-btn"></i>
                                        <i class="fa-solid fa-pencil text-[13px] cursor-pointer hover:text-slate-900 transition-colors edit-tenant-btn"></i>
                                        <i class="fa-regular fa-trash-can text-[14px] cursor-pointer hover:text-rose-600 transition-colors delete-tenant-btn"></i>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-10 text-center text-slate-400 text-sm">
                                    No tenants found.
                                </td>
                            </tr>
                        @endforelse

                        <tr id="tenant-empty-filter" class="hidden">
                            <td colspan="6" class="py-10 text-center text-slate-400 text-sm">
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