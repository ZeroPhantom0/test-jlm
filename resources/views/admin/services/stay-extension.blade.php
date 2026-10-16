@extends('layouts.admin', ['active' => 'services.stay-extension', 'pageTitle' => 'Services'])
@section('title', 'Stay Extension Requests')

@section('content')
<div class="p-4 md:p-8 max-w-[1400px] mx-auto space-y-5">

    {{-- Page Title --}}
    <div class="flex items-center gap-4">
        <div class="w-11 h-11 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center text-lg">
            <i class="fa-regular fa-calendar-plus"></i>
        </div>
        <div>
            <h2 class="text-[22px] font-extrabold text-slate-900">Stay Extension Requests</h2>
            <p class="text-[13px] text-slate-500 mt-0.5">Manage tenant lease extension applications</p>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl px-5 py-4 border border-slate-200 shadow-sm">
            <span class="text-[13px] text-slate-500 font-medium">Total</span>
            <div class="text-[22px] font-bold text-slate-900 mt-2">{{ $metrics['total'] }}</div>
        </div>
        <div class="bg-white rounded-2xl px-5 py-4 border border-slate-200 shadow-sm">
            <span class="text-[13px] text-slate-500 font-medium">Pending</span>
            <div class="mt-2">
                <div class="w-7 h-7 rounded-full bg-yellow-100 text-amber-700 flex items-center justify-center text-[13px] font-bold">{{ $metrics['pending'] }}</div>
            </div>
        </div>
        <div class="bg-white rounded-2xl px-5 py-4 border border-slate-200 shadow-sm">
            <span class="text-[13px] text-slate-500 font-medium">Approved</span>
            <div class="mt-2">
                <div class="w-7 h-7 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-[13px] font-bold">{{ $metrics['approved'] }}</div>
            </div>
        </div>
        <div class="bg-white rounded-2xl px-5 py-4 border border-slate-200 shadow-sm">
            <span class="text-[13px] text-slate-500 font-medium">Rejected</span>
            <div class="mt-2">
                <div class="w-7 h-7 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center text-[13px] font-bold">{{ $metrics['rejected'] }}</div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 flex flex-col md:flex-row gap-4">
        <form method="GET" action="{{ route('admin.services.stay-extension') }}" class="flex-1 flex flex-col md:flex-row gap-4">
            <div class="flex-1 bg-slate-50 border border-slate-200 rounded-[10px] px-4 py-2.5 flex items-center gap-2.5">
                <i class="fa-solid fa-magnifying-glass text-slate-400 text-sm"></i>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search by tenant, room..." class="w-full text-[13.5px] outline-none bg-transparent">
            </div>
            <select name="status" onchange="this.form.submit()" class="bg-white border border-slate-200 rounded-[10px] px-4 py-2.5 text-[13.5px] font-medium text-slate-700 outline-none cursor-pointer min-w-[160px]">
                <option value="">All Statuses</option>
                <option value="Pending" {{ ($filters['status'] ?? '') === 'Pending' ? 'selected' : '' }}>Pending</option>
                <option value="Approved" {{ ($filters['status'] ?? '') === 'Approved' ? 'selected' : '' }}>Approved</option>
                <option value="Rejected" {{ ($filters['status'] ?? '') === 'Rejected' ? 'selected' : '' }}>Rejected</option>
            </select>
        </form>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-slate-50 text-[11px] font-bold text-slate-500 uppercase tracking-wide border-b border-slate-200">
                        <th class="py-3.5 px-5">Resident</th>
                        <th class="py-3.5 px-5">Current Lease End</th>
                        <th class="py-3.5 px-5">Requested End</th>
                        <th class="py-3.5 px-5">Extension</th>
                        <th class="py-3.5 px-5">Status</th>
                        <th class="py-3.5 px-5"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $req)
                    <tr class="border-b border-slate-50 last:border-0">
                        <td class="py-4 px-5">
                            <div class="text-sm font-bold text-slate-900">{{ $req->tenant->user->name ?? 'Unknown' }}</div>
                            <div class="text-xs text-slate-400 mt-0.5">{{ $req->room?->room_number ? 'Room ' . $req->room->room_number : 'N/A' }}</div>
                        </td>
                        <td class="py-4 px-5">
                            <div class="flex items-center gap-2 text-[13px] text-slate-700">
                                <i class="fa-regular fa-calendar text-slate-400 text-[13px]"></i>
                                {{ $req->current_lease_end?->format('M d, Y') }}
                            </div>
                        </td>
                        <td class="py-4 px-5">
                            <div class="flex items-center gap-2 text-[13px] text-purple-700 font-semibold">
                                <i class="fa-regular fa-calendar text-purple-600 text-[13px]"></i>
                                {{ $req->requested_lease_end?->format('M d, Y') }}
                            </div>
                        </td>
                        <td class="py-4 px-5">
                            <span class="inline-block bg-purple-100 text-purple-700 text-[11px] font-bold px-2.5 py-1 rounded-xl">{{ $req->extension_label }}</span>
                        </td>
                        <td class="py-4 px-5">
                            <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold {{ $req->status_class }}">{{ $req->status }}</span>
                        </td>
                        <td class="py-4 px-5">
                            <div class="flex items-center justify-end gap-3 text-slate-400 text-sm">
                                @if($req->status === 'Pending')
                                <i class="fa-regular fa-circle-check text-emerald-500 cursor-pointer hover:text-emerald-600"></i>
                                <i class="fa-regular fa-circle-xmark text-rose-500 cursor-pointer hover:text-rose-600"></i>
                                @endif
                                <i
                                    class="fa-regular fa-eye cursor-pointer hover:text-slate-700 open-details-btn"
                                    data-request-id="{{ $req->id }}"
                                ></i>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-12 text-slate-400">
                            <i class="fa-solid fa-inbox text-4xl mb-3"></i>
                            <p class="text-sm">No stay extension requests found.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- React Modal Mount Point with data --}}
<div id="admin-stay-extension-modals" data-requests='@json($requestsForReact)'></div>
@endsection