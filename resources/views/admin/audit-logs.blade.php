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
                <p class="text-[13.5px] text-slate-500 mt-0.5">Track all system activities and user actions</p>
            </div>
        </div>
        <div class="flex gap-3">
            <button class="bg-white border border-slate-300 text-slate-700 rounded-lg px-4 py-2.5 text-[13.5px] font-semibold flex items-center gap-2 hover:bg-slate-50 transition">
                <i class="fa-solid fa-rotate-right"></i> Refresh
            </button>
            <button class="bg-[#02122b] text-white rounded-lg px-4.5 py-2.5 text-[13.5px] font-semibold flex items-center gap-2 hover:bg-[#092042] transition">
                <i class="fa-solid fa-file-csv"></i> Export CSV
            </button>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white border border-slate-200 rounded-2xl px-5 py-5">
            <div class="text-[13px] font-semibold text-slate-500">Total Events</div>
            <div class="text-[22px] font-extrabold text-slate-900 mt-2.5">15</div>
        </div>
        <div class="bg-white border border-slate-200 rounded-2xl px-5 py-5">
            <div class="text-[13px] font-semibold text-slate-500">Successful</div>
            <div class="w-11 h-11 rounded-full bg-green-100 text-green-600 flex items-center justify-center text-base font-extrabold mt-2.5">12</div>
        </div>
        <div class="bg-white border border-slate-200 rounded-2xl px-5 py-5">
            <div class="text-[13px] font-semibold text-slate-500">Warnings</div>
            <div class="w-11 h-11 rounded-full bg-yellow-100 text-amber-700 flex items-center justify-center text-base font-extrabold mt-2.5">2</div>
        </div>
        <div class="bg-white border border-slate-200 rounded-2xl px-5 py-5">
            <div class="text-[13px] font-semibold text-slate-500">Errors</div>
            <div class="w-11 h-11 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center text-base font-extrabold mt-2.5">1</div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white border border-slate-200 rounded-2xl px-5 py-4 flex flex-col md:flex-row gap-4 items-stretch md:items-center">
        <div class="flex-1 bg-slate-50 border border-slate-200 rounded-[10px] px-4 py-2.5 flex items-center gap-2.5">
            <i class="fa-solid fa-magnifying-glass text-slate-400 text-sm"></i>
            <input type="text" placeholder="Search description, user, or action..." class="w-full text-[13.5px] outline-none bg-transparent">
        </div>
        <select class="bg-white border border-slate-300 rounded-[10px] px-4 py-2.5 text-[13.5px] font-medium text-slate-700 outline-none cursor-pointer min-w-[140px]">
            <option>All Modules</option>
        </select>
        <select class="bg-white border border-slate-300 rounded-[10px] px-4 py-2.5 text-[13.5px] font-medium text-slate-700 outline-none cursor-pointer min-w-[140px]">
            <option>All Statuses</option>
        </select>
        <select class="bg-white border border-slate-300 rounded-[10px] px-4 py-2.5 text-[13.5px] font-medium text-slate-700 outline-none cursor-pointer min-w-[140px]">
            <option>All Roles</option>
        </select>
    </div>

    {{-- Table --}}
    <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden">
        <div class="px-6 py-4 flex justify-between items-center border-b border-slate-100">
            <span class="text-[13.5px] text-slate-500 font-medium">15 events found</span>
            <span class="text-[13.5px] text-slate-400">Sorted by most recent</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="text-[11px] text-slate-500 uppercase tracking-wider font-bold border-b border-slate-100">
                        <th class="py-3.5 px-6">Status</th>
                        <th class="py-3.5 px-6">Timestamp</th>
                        <th class="py-3.5 px-6">Action</th>
                        <th class="py-3.5 px-6">Module</th>
                        <th class="py-3.5 px-6">Description</th>
                        <th class="py-3.5 px-6">User</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach([
                        ['status' => 'success', 'date' => 'Mar 25, 2026', 'time' => '6:00 PM', 'action' => 'LOGIN', 'actionIcon' => 'fa-solid fa-right-to-bracket', 'module' => 'AUTHENTICATION', 'moduleColor' => 'slate', 'desc' => 'Admin logged in successfully', 'user' => 'Leah Administrator', 'role' => 'Admin', 'initial' => 'L', 'avatarColor' => 'purple'],
                        ['status' => 'success', 'date' => 'Mar 25, 2026', 'time' => '6:00 PM', 'action' => 'CREATE', 'actionIcon' => 'fa-solid fa-plus', 'module' => 'BILLING', 'moduleColor' => 'green', 'desc' => 'Created new invoice inv-105 for Elle Dela Cruz (₱500)', 'user' => 'Leah Administrator', 'role' => 'Admin', 'initial' => 'L', 'avatarColor' => 'purple'],
                        ['status' => 'success', 'date' => 'Mar 25, 2026', 'time' => '6:00 PM', 'action' => 'UPDATE', 'actionIcon' => 'fa-regular fa-pen-to-square', 'module' => 'RESIDENTS', 'moduleColor' => 'blue', 'desc' => 'Updated resident profile for Gabby Maningo', 'user' => 'Leah Administrator', 'role' => 'Admin', 'initial' => 'L', 'avatarColor' => 'purple'],
                        ['status' => 'success', 'date' => 'Mar 24, 2026', 'time' => '6:00 PM', 'action' => 'APPROVE', 'actionIcon' => 'fa-solid fa-check', 'module' => 'RESERVATIONS', 'moduleColor' => 'teal', 'desc' => 'Approved reservation rsv-2 for Ramon Bautista (Room 204)', 'user' => 'Leah Administrator', 'role' => 'Admin', 'initial' => 'L', 'avatarColor' => 'purple'],
                        ['status' => 'success', 'date' => 'Mar 24, 2026', 'time' => '6:00 PM', 'action' => 'VERIFY', 'actionIcon' => 'fa-solid fa-check-double', 'module' => 'BILLING', 'moduleColor' => 'green', 'desc' => 'Verified GCash payment for Nicole Custorio invoice inv-103', 'user' => 'Leah Administrator', 'role' => 'Admin', 'initial' => 'L', 'avatarColor' => 'purple'],
                        ['status' => 'error', 'date' => 'Mar 23, 2026', 'time' => '6:00 PM', 'action' => 'LOGIN FAILED', 'actionIcon' => 'fa-solid fa-xmark', 'module' => 'AUTHENTICATION', 'moduleColor' => 'slate', 'desc' => 'Failed login attempt for unknown@email.com', 'user' => 'Unknown', 'role' => 'Unknown', 'initial' => 'U', 'avatarColor' => 'gray'],
                        ['status' => 'warning', 'date' => 'Mar 23, 2026', 'time' => '6:00 PM', 'action' => 'DELETE', 'actionIcon' => 'fa-regular fa-trash-can', 'module' => 'ROOMS', 'moduleColor' => 'indigo', 'desc' => 'Deleted maintenance record for room 303', 'user' => 'Leah Administrator', 'role' => 'Admin', 'initial' => 'L', 'avatarColor' => 'purple'],
                        ['status' => 'success', 'date' => 'Mar 22, 2026', 'time' => '6:00 PM', 'action' => 'CREATE', 'actionIcon' => 'fa-solid fa-plus', 'module' => 'TENANTS', 'moduleColor' => 'indigo', 'desc' => 'Added new resident Gabby Maningo to Room 101', 'user' => 'Leah Administrator', 'role' => 'Admin', 'initial' => 'L', 'avatarColor' => 'purple'],
                        ['status' => 'warning', 'date' => 'Mar 22, 2026', 'time' => '6:00 PM', 'action' => 'REJECT', 'actionIcon' => 'fa-solid fa-xmark', 'module' => 'RESERVATIONS', 'moduleColor' => 'teal', 'desc' => 'Rejected reservation rsv-3 for Jenny Villanueva (incomplete docs)', 'user' => 'Leah Administrator', 'role' => 'Admin', 'initial' => 'L', 'avatarColor' => 'purple'],
                        ['status' => 'success', 'date' => 'Mar 21, 2026', 'time' => '6:00 PM', 'action' => 'UPDATE', 'actionIcon' => 'fa-regular fa-pen-to-square', 'module' => 'VIRTUAL TOUR', 'moduleColor' => 'pink', 'desc' => 'Updated virtual tour room: Barkada Room – Premium', 'user' => 'Leah Administrator', 'role' => 'Admin', 'initial' => 'L', 'avatarColor' => 'purple'],
                        ['status' => 'success', 'date' => 'Mar 18, 2026', 'time' => '6:00 PM', 'action' => 'SUBMIT', 'actionIcon' => 'fa-solid fa-plus', 'module' => 'SERVICES', 'moduleColor' => 'amber', 'desc' => 'Tenant submitted maintenance request sr-1: Leaky Faucet', 'user' => 'Elle Dela Cruz', 'role' => 'Resident', 'initial' => 'E', 'avatarColor' => 'teal'],
                        ['status' => 'success', 'date' => 'Mar 20, 2026', 'time' => '6:00 PM', 'action' => 'CREATE', 'actionIcon' => 'fa-solid fa-plus', 'module' => 'ANNOUNCEMENTS', 'moduleColor' => 'purple', 'desc' => 'Published announcement: Water Maintenance Scheduled', 'user' => 'Leah Administrator', 'role' => 'Admin', 'initial' => 'L', 'avatarColor' => 'purple'],
                        ['status' => 'success', 'date' => 'Mar 19, 2026', 'time' => '6:00 PM', 'action' => 'LOGIN', 'actionIcon' => 'fa-solid fa-right-to-bracket', 'module' => 'AUTHENTICATION', 'moduleColor' => 'slate', 'desc' => 'Resident logged in successfully', 'user' => 'Mau Santos', 'role' => 'Resident', 'initial' => 'M', 'avatarColor' => 'teal'],
                    ] as $log)
                    @php
                        $statusDot = match($log['status']) {
                            'success' => 'bg-emerald-500',
                            'error' => 'bg-rose-500',
                            'warning' => 'bg-amber-500',
                        };
                        $statusCircle = match($log['status']) {
                            'success' => 'border-emerald-500 text-emerald-500',
                            'error' => 'border-rose-500 text-rose-500',
                            'warning' => 'border-amber-500 text-amber-500',
                        };
                        $statusIcon = match($log['status']) {
                            'success' => 'fa-solid fa-check',
                            'error' => 'fa-solid fa-xmark',
                            'warning' => 'fa-solid fa-triangle-exclamation',
                        };
                        $moduleColors = [
                            'slate' => 'bg-slate-100 text-slate-700',
                            'green' => 'bg-green-100 text-green-700',
                            'blue' => 'bg-blue-100 text-blue-700',
                            'teal' => 'bg-teal-100 text-teal-700',
                            'indigo' => 'bg-indigo-100 text-indigo-700',
                            'pink' => 'bg-pink-100 text-pink-700',
                            'amber' => 'bg-amber-100 text-amber-700',
                            'purple' => 'bg-violet-100 text-violet-700',
                        ];
                        $avatarColors = [
                            'purple' => 'bg-violet-100 text-violet-600',
                            'teal' => 'bg-teal-100 text-teal-600',
                            'gray' => 'bg-slate-200 text-slate-500',
                        ];
                    @endphp
                    <tr class="border-b border-slate-50 last:border-0">
                        <td class="py-4 px-6">
                            <div class="flex items-center gap-2.5">
                                <span class="w-1.5 h-1.5 rounded-full {{ $statusDot }}"></span>
                                <div class="w-5.5 h-5.5 rounded-full border-[1.5px] flex items-center justify-center w-6 h-6 {{ $statusCircle }}">
                                    <i class="{{ $statusIcon }} text-[10px]"></i>
                                </div>
                            </div>
                        </td>
                        <td class="py-4 px-6">
                            <div class="flex items-start gap-2 text-slate-500 text-[13px] leading-tight">
                                <i class="fa-regular fa-clock mt-0.5 text-[13px] text-slate-400"></i>
                                <div>
                                    {{ $log['date'] }}<br>
                                    {{ $log['time'] }}
                                </div>
                            </div>
                        </td>
                        <td class="py-4 px-6">
                            <span class="inline-flex items-center gap-1.5 bg-slate-100 text-slate-700 text-[11px] font-bold px-2.5 py-1 rounded-md tracking-wide">
                                <i class="{{ $log['actionIcon'] }} text-[10px]"></i> {{ $log['action'] }}
                            </span>
                        </td>
                        <td class="py-4 px-6">
                            <span class="inline-block px-2 py-1 rounded-md text-[11px] font-bold tracking-wide {{ $moduleColors[$log['moduleColor']] }}">
                                {{ $log['module'] }}
                            </span>
                        </td>
                        <td class="py-4 px-6 text-[13.5px] text-slate-700 font-medium max-w-[320px] leading-snug">
                            {{ $log['desc'] }}
                        </td>
                        <td class="py-4 px-6">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-[13px] font-bold {{ $avatarColors[$log['avatarColor']] }}">
                                    {{ $log['initial'] }}
                                </div>
                                <div>
                                    <div class="text-[13px] font-bold text-slate-900 leading-tight">{{ $log['user'] }}</div>
                                    <div class="text-[11px] text-slate-500 mt-0.5">{{ $log['role'] }}</div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection