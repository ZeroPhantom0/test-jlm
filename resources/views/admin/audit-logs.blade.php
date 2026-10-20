@extends('layouts.admin', ['active' => 'audit-logs', 'pageTitle' => 'Audit Logs'])
@section('title', 'Audit Logs')

@section('content')
<div class="p-4 md:p-8 max-w-[1400px] mx-auto space-y-5">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row justify-between md:items-center gap-4">
        <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <div>
                <h2 class="text-2xl font-extrabold text-slate-900">Audit Logs</h2>
                <p class="text-[13.5px] text-slate-500 mt-0.5">Immutable record of system activities and user actions</p>
            </div>
        </div>
        <div>
            <button class="bg-[#02122b] text-white rounded-lg px-4 py-2.5 text-[13.5px] font-semibold flex items-center gap-2 hover:bg-[#092042] transition">
                <i class="fa-solid fa-file-csv"></i> Export CSV
            </button>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white border border-slate-200 rounded-2xl px-5 py-4 flex flex-col md:flex-row gap-3 items-stretch md:items-center">
        <div class="flex-1 bg-slate-50 border border-slate-200 rounded-[10px] px-4 py-1.5 flex items-center gap-2.5">
            <i class="fa-solid fa-magnifying-glass text-slate-400 text-sm"></i>
            <input type="text" placeholder="Search by user, action, or description..."
                class="w-full text-[13px] outline-none bg-transparent">
        </div>
        <input type="date" class="bg-white border border-slate-200 rounded-[10px] px-3 py-1.5 text-[13px] text-slate-700 outline-none cursor-pointer">
        <span class="text-slate-400 text-[13px] text-center md:text-left">to</span>
        <input type="date" class="bg-white border border-slate-200 rounded-[10px] px-3 py-1.5 text-[13px] text-slate-700 outline-none cursor-pointer">
    </div>

    {{-- Table --}}
    <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden">
        <div class="px-6 py-3.5 flex justify-between items-center border-b border-slate-100">
            <span class="text-[13px] text-slate-500 font-medium">{{ count($logs ?? []) ?: 13 }} events</span>
            <span class="text-[12px] text-slate-400">Newest first</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="text-[11px] text-slate-500 uppercase tracking-wider font-bold border-b border-slate-100">
                        <th class="py-3.5 px-6">Timestamp</th>
                        <th class="py-3.5 px-6">Action</th>
                        <th class="py-3.5 px-6">Module</th>
                        <th class="py-3.5 px-6">Description</th>
                        <th class="py-3.5 px-6">User</th>
                        <th class="py-3.5 px-6">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach([
                        ['status' => 'success', 'date' => 'Mar 25, 2026', 'time' => '6:00 PM', 'action' => 'LOGIN', 'actionIcon' => 'fa-solid fa-right-to-bracket', 'module' => 'AUTHENTICATION', 'moduleColor' => 'slate', 'desc' => 'Admin logged in successfully', 'user' => 'Leah Administrator', 'role' => 'Admin', 'initial' => 'L', 'avatarColor' => 'purple'],
                        ['status' => 'success', 'date' => 'Mar 25, 2026', 'time' => '6:00 PM', 'action' => 'CREATE', 'actionIcon' => 'fa-solid fa-plus', 'module' => 'BILLING', 'moduleColor' => 'green', 'desc' => 'Created invoice inv-105 for Elle Dela Cruz (₱500)', 'user' => 'Leah Administrator', 'role' => 'Admin', 'initial' => 'L', 'avatarColor' => 'purple'],
                        ['status' => 'success', 'date' => 'Mar 25, 2026', 'time' => '6:00 PM', 'action' => 'UPDATE', 'actionIcon' => 'fa-regular fa-pen-to-square', 'module' => 'RESIDENTS', 'moduleColor' => 'blue', 'desc' => 'Updated resident profile for Gabby Maningo', 'user' => 'Leah Administrator', 'role' => 'Admin', 'initial' => 'L', 'avatarColor' => 'purple'],
                        ['status' => 'success', 'date' => 'Mar 24, 2026', 'time' => '6:00 PM', 'action' => 'APPROVE', 'actionIcon' => 'fa-solid fa-check', 'module' => 'RESERVATIONS', 'moduleColor' => 'teal', 'desc' => 'Approved reservation rsv-2 for Ramon Bautista (Room 204)', 'user' => 'Leah Administrator', 'role' => 'Admin', 'initial' => 'L', 'avatarColor' => 'purple'],
                        ['status' => 'success', 'date' => 'Mar 24, 2026', 'time' => '6:00 PM', 'action' => 'VERIFY', 'actionIcon' => 'fa-solid fa-check-double', 'module' => 'BILLING', 'moduleColor' => 'green', 'desc' => 'Verified GCash payment for Nicole Custorio invoice inv-103', 'user' => 'Leah Administrator', 'role' => 'Admin', 'initial' => 'L', 'avatarColor' => 'purple'],
                        ['status' => 'error', 'date' => 'Mar 23, 2026', 'time' => '6:00 PM', 'action' => 'LOGIN FAILED', 'actionIcon' => 'fa-solid fa-xmark', 'module' => 'AUTHENTICATION', 'moduleColor' => 'slate', 'desc' => 'Failed login attempt for unknown@email.com', 'user' => 'Unknown', 'role' => 'Unknown', 'initial' => 'U', 'avatarColor' => 'gray'],
                        ['status' => 'warning', 'date' => 'Mar 23, 2026', 'time' => '6:00 PM', 'action' => 'DELETE', 'actionIcon' => 'fa-regular fa-trash-can', 'module' => 'ROOMS', 'moduleColor' => 'indigo', 'desc' => 'Deleted maintenance record for room 303', 'user' => 'Leah Administrator', 'role' => 'Admin', 'initial' => 'L', 'avatarColor' => 'purple'],
                        ['status' => 'success', 'date' => 'Mar 22, 2026', 'time' => '6:00 PM', 'action' => 'CREATE', 'actionIcon' => 'fa-solid fa-plus', 'module' => 'TENANTS', 'moduleColor' => 'indigo', 'desc' => 'Added resident Gabby Maningo to Room 101', 'user' => 'Leah Administrator', 'role' => 'Admin', 'initial' => 'L', 'avatarColor' => 'purple'],
                        ['status' => 'warning', 'date' => 'Mar 22, 2026', 'time' => '6:00 PM', 'action' => 'REJECT', 'actionIcon' => 'fa-solid fa-xmark', 'module' => 'RESERVATIONS', 'moduleColor' => 'teal', 'desc' => 'Rejected reservation rsv-3 for Jenny Villanueva (incomplete docs)', 'user' => 'Leah Administrator', 'role' => 'Admin', 'initial' => 'L', 'avatarColor' => 'purple'],
                        ['status' => 'success', 'date' => 'Mar 21, 2026', 'time' => '6:00 PM', 'action' => 'UPDATE', 'actionIcon' => 'fa-regular fa-pen-to-square', 'module' => 'VIRTUAL TOUR', 'moduleColor' => 'pink', 'desc' => 'Updated virtual tour: Barkada Room – Premium', 'user' => 'Leah Administrator', 'role' => 'Admin', 'initial' => 'L', 'avatarColor' => 'purple'],
                        ['status' => 'success', 'date' => 'Mar 20, 2026', 'time' => '6:00 PM', 'action' => 'CREATE', 'actionIcon' => 'fa-solid fa-plus', 'module' => 'ANNOUNCEMENTS', 'moduleColor' => 'purple', 'desc' => 'Published announcement: Water Maintenance Scheduled', 'user' => 'Leah Administrator', 'role' => 'Admin', 'initial' => 'L', 'avatarColor' => 'purple'],
                        ['status' => 'success', 'date' => 'Mar 19, 2026', 'time' => '6:00 PM', 'action' => 'LOGIN', 'actionIcon' => 'fa-solid fa-right-to-bracket', 'module' => 'AUTHENTICATION', 'moduleColor' => 'slate', 'desc' => 'Resident logged in successfully', 'user' => 'Mau Santos', 'role' => 'Resident', 'initial' => 'M', 'avatarColor' => 'teal'],
                        ['status' => 'success', 'date' => 'Mar 18, 2026', 'time' => '6:00 PM', 'action' => 'SUBMIT', 'actionIcon' => 'fa-solid fa-plus', 'module' => 'SERVICES', 'moduleColor' => 'amber', 'desc' => 'Submitted maintenance request sr-1: Leaky Faucet', 'user' => 'Elle Dela Cruz', 'role' => 'Resident', 'initial' => 'E', 'avatarColor' => 'teal'],
                    ] as $log)
                    @php
                        $statusStyle = match($log['status']) {
                            'success' => ['bg-emerald-50 text-emerald-700 border-emerald-200', 'fa-solid fa-circle-check', 'Success'],
                            'error'   => ['bg-rose-50 text-rose-700 border-rose-200', 'fa-solid fa-circle-xmark', 'Failed'],
                            'warning' => ['bg-amber-50 text-amber-700 border-amber-200', 'fa-solid fa-triangle-exclamation', 'Warning'],
                        };
                        $moduleColors = [
                            'slate'  => 'bg-slate-100 text-slate-700',
                            'green'  => 'bg-green-100 text-green-700',
                            'blue'   => 'bg-blue-100 text-blue-700',
                            'teal'   => 'bg-teal-100 text-teal-700',
                            'indigo' => 'bg-indigo-100 text-indigo-700',
                            'pink'   => 'bg-pink-100 text-pink-700',
                            'amber'  => 'bg-amber-100 text-amber-700',
                            'purple' => 'bg-violet-100 text-violet-700',
                        ];
                        $avatarColors = [
                            'purple' => 'bg-violet-100 text-violet-600',
                            'teal'   => 'bg-teal-100 text-teal-600',
                            'gray'   => 'bg-slate-200 text-slate-500',
                        ];
                    @endphp
                    <tr class="border-b border-slate-50 last:border-0 hover:bg-slate-50/50">
                        <td class="py-3.5 px-6 whitespace-nowrap">
                            <div class="text-[13px] text-slate-800 font-medium">{{ $log['date'] }}</div>
                            <div class="text-[11.5px] text-slate-400">{{ $log['time'] }}</div>
                        </td>
                        <td class="py-3.5 px-6">
                            <span class="inline-flex items-center gap-1.5 bg-slate-100 text-slate-700 text-[11px] font-bold px-2.5 py-1 rounded-md tracking-wide">
                                <i class="{{ $log['actionIcon'] }} text-[10px]"></i> {{ $log['action'] }}
                            </span>
                        </td>
                        <td class="py-3.5 px-6">
                            <span class="inline-block px-2 py-1 rounded-md text-[10.5px] font-bold tracking-wider {{ $moduleColors[$log['moduleColor']] }}">
                                {{ $log['module'] }}
                            </span>
                        </td>
                        <td class="py-3.5 px-6 text-[13px] text-slate-700 max-w-[360px] leading-snug">
                            {{ $log['desc'] }}
                        </td>
                        <td class="py-3.5 px-6">
                            <div class="flex items-center gap-2.5">
                                <div class="w-7 h-7 rounded-full flex items-center justify-center text-[12px] font-bold {{ $avatarColors[$log['avatarColor']] }}">
                                    {{ $log['initial'] }}
                                </div>
                                <div>
                                    <div class="text-[13px] font-semibold text-slate-900 leading-tight">{{ $log['user'] }}</div>
                                    <div class="text-[11px] text-slate-500 mt-0.5">{{ $log['role'] }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="py-3.5 px-6">
                            <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold px-2.5 py-1 rounded-full border {{ $statusStyle[0] }}">
                                <i class="{{ $statusStyle[1] }} text-[10px]"></i> {{ $statusStyle[2] }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection