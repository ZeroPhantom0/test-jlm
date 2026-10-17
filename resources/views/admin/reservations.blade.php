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

            {{-- Toolbar --}}
            <form method="GET" action="{{ route('admin.reservations') }}" class="flex flex-col md:flex-row gap-4">
                <div
                    class="flex-1 bg-slate-50 border border-slate-200 rounded-[10px] px-4 py-2.5 flex items-center gap-2.5">
                    <i class="fa-solid fa-magnifying-glass text-slate-400 text-sm"></i>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                        placeholder="Search by name, email, or room..."
                        class="w-full text-[13.5px] outline-none bg-transparent">
                </div>
                <select name="status" onchange="this.form.submit()"
                    class="bg-white border border-slate-200 rounded-[10px] px-4 py-2.5 text-[13.5px] text-slate-700 outline-none cursor-pointer min-w-[180px]">
                    <option value="">All Statuses</option>
                    <option value="Pending" {{ ($filters['status'] ?? '') === 'Pending' ? 'selected' : '' }}>Pending
                    </option>
                    <option value="Approved" {{ ($filters['status'] ?? '') === 'Approved' ? 'selected' : '' }}>Approved
                    </option>
                    <option value="Rejected" {{ ($filters['status'] ?? '') === 'Rejected' ? 'selected' : '' }}>Rejected
                    </option>
                    <option value="Expired" {{ ($filters['status'] ?? '') === 'Expired' ? 'selected' : '' }}>Expired
                    </option>
                </select>
            </form>

            {{-- Table --}}
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-[13px] font-bold text-slate-600 border-b border-slate-100 whitespace-nowrap">
                            <th class="py-3 px-4">Resident</th>
                            <th class="py-3 px-4">Contact</th>
                            <th class="py-3 px-4">Desired Room</th>
                            <th class="py-3 px-4">Check-in</th>
                            <th class="py-3 px-4">Check-out</th> {{-- NEW --}}
                            <th class="py-3 px-4">Reservation Fee</th>
                            <th class="py-3 px-4">Submitted</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reservations as $res)
                            <tr class="border-b border-slate-50 last:border-0">
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

                                {{-- Check-out (NEW) --}}
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
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- React Modal Mount Point with data --}}
    <div id="admin-reservations-modals" data-reservations='@json($reservationsForReact)'></div>
@endsection
