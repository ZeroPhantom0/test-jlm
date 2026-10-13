@extends('layouts.admin', ['active' => 'billing', 'pageTitle' => 'Billing & Payments'])
@section('title', 'Billing & Payments')

@section('content')
<div class="p-4 md:p-8 max-w-[1400px] mx-auto space-y-6">

    {{-- Page Title --}}
    <div class="flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-sky-100 text-sky-600 flex items-center justify-center text-xl">
            <i class="fa-solid fa-credit-card"></i>
        </div>
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900">Billing & Payments</h1>
            <p class="text-[13px] text-slate-500 mt-0.5">Manage invoices and payment verification</p>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div class="bg-white rounded-2xl px-6 py-5 border border-slate-100 shadow-sm flex items-center gap-5">
            <div class="w-13 h-13 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-[22px] shrink-0">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div>
                <p class="text-[13px] font-medium text-slate-500">Collected this month</p>
                <h2 class="text-[26px] font-extrabold text-slate-900 mt-0.5">₱8,700</h2>
            </div>
        </div>

        <div class="bg-white rounded-2xl px-6 py-5 border border-slate-100 shadow-sm flex items-center gap-5">
            <div class="w-13 h-13 rounded-full bg-yellow-100 text-amber-600 flex items-center justify-center text-[22px] shrink-0">
                <i class="fa-regular fa-calendar"></i>
            </div>
            <div>
                <p class="text-[13px] font-medium text-slate-500">Pending Payments</p>
                <h2 class="text-[26px] font-extrabold text-slate-900 mt-0.5">₱5,800</h2>
            </div>
        </div>

        <div class="bg-white rounded-2xl px-6 py-5 border border-slate-100 shadow-sm flex items-center gap-5">
            <div class="w-13 h-13 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center text-[22px] shrink-0">
                <i class="fa-solid fa-circle-exclamation"></i>
            </div>
            <div>
                <p class="text-[13px] font-medium text-slate-500">Overdue</p>
                <h2 class="text-[26px] font-extrabold text-slate-900 mt-0.5">₱8,500</h2>
            </div>
        </div>
    </div>

    {{-- Table Card --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 space-y-5">
        <div class="flex flex-col md:flex-row gap-4">
            <div class="flex-1 bg-white border border-slate-300 rounded-[10px] px-4 py-2.5 flex items-center gap-2.5">
                <i class="fa-solid fa-magnifying-glass text-slate-400 text-sm"></i>
                <input type="text" placeholder="Search invoices..." class="w-full text-[13px] outline-none bg-transparent">
            </div>
            <select class="bg-white border border-slate-300 rounded-[10px] px-4 py-2.5 text-[13px] font-medium text-slate-700 outline-none cursor-pointer min-w-[160px]">
                <option>All Statuses</option>
                <option>Unpaid</option>
                <option>Overdue</option>
                <option>Partial</option>
                <option>Pending</option>
                <option>Paid</option>
            </select>
            <select class="bg-white border border-slate-300 rounded-[10px] px-4 py-2.5 text-[13px] font-medium text-slate-700 outline-none cursor-pointer min-w-[160px]">
                <option>All Methods</option>
                <option>GCash</option>
                <option>Over The Counter</option>
                <option>Bank Transfer</option>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="text-[13px] font-bold text-slate-600 border-b border-slate-100 whitespace-nowrap">
                        <th class="py-3 px-4">Transaction No.</th>
                        <th class="py-3 px-4">Tenant / Room</th>
                        <th class="py-3 px-4">Total Amount</th>
                        <th class="py-3 px-4">Amount Paid</th>
                        <th class="py-3 px-4">Balance</th>
                        <th class="py-3 px-4">Due Date</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach([
                        ['tn' => 'tn-101', 'desc' => 'Solo - March 20-22, 2026', 'tenant' => 'Nicole Custorio', 'room' => 'R202', 'total' => '₱1,900', 'rate' => '₱ 950/day', 'paid' => '₱0', 'days' => '2 days', 'balance' => '₱1,900', 'due' => 'Mar 22, 2026', 'status' => 'unpaid'],
                        ['tn' => 'tn-102', 'desc' => 'Bedspace - March 20-21, 2026', 'tenant' => 'Elle Dela Cruz', 'room' => 'R101-B1-U', 'total' => '₱600', 'rate' => '₱ 300/day', 'paid' => '₱0', 'days' => '2 days', 'balance' => '₱600', 'due' => 'Mar 21, 2026', 'status' => 'overdue'],
                        ['tn' => 'tn-103', 'desc' => 'Solo - March 20-21, 2026', 'tenant' => 'Mau Santos', 'room' => 'R201', 'total' => '₱600', 'rate' => '₱ 600/day', 'paid' => '₱300', 'days' => '2 days', 'balance' => '₱300', 'due' => 'Mar 21, 2026', 'status' => 'partial'],
                        ['tn' => 'inv-104', 'desc' => 'Bedspace - March 20-25, 2026', 'tenant' => 'Gab Maningo', 'room' => 'R101-B2-L', 'total' => '₱1,000', 'rate' => '₱ 200/day', 'paid' => '₱1,000', 'days' => '5 days', 'balance' => '₱1,000', 'due' => 'Mar 25, 2026', 'status' => 'pending'],
                        ['tn' => 'tn-105', 'desc' => 'Solo - January 15-22, 2026', 'tenant' => 'Francis Sabiniano', 'room' => 'R203', 'total' => '₱4,200', 'rate' => '₱ 600/day', 'paid' => '₱4,200', 'days' => '7 days', 'balance' => '₱0', 'due' => 'Jan 22, 2026', 'status' => 'paid'],
                    ] as $inv)
                    <tr class="border-b border-slate-50 last:border-0">
                        <td class="py-4 px-4">
                            <div class="font-semibold text-slate-700 text-[13px]">{{ $inv['tn'] }}</div>
                            <div class="text-[11px] text-slate-400 mt-0.5">{{ $inv['desc'] }}</div>
                        </td>
                        <td class="py-4 px-4">
                            <div class="font-bold text-slate-900 text-[13px]">{{ $inv['tenant'] }}</div>
                            <div class="text-[11px] text-slate-400 mt-0.5">{{ $inv['room'] }}</div>
                        </td>
                        <td class="py-4 px-4">
                            <div class="font-bold text-slate-900 text-[13px]">{{ $inv['total'] }}</div>
                            <div class="text-[11px] text-slate-400 mt-0.5">{{ $inv['rate'] }}</div>
                        </td>
                        <td class="py-4 px-4">
                            <div class="font-bold text-slate-900 text-[13px]">{{ $inv['paid'] }}</div>
                            <div class="text-[11px] text-slate-400 mt-0.5">{{ $inv['days'] }}</div>
                        </td>
                        <td class="py-4 px-4 font-bold text-slate-900 text-[13px]">{{ $inv['balance'] }}</td>
                        <td class="py-4 px-4 text-[13px] text-slate-600">{{ $inv['due'] }}</td>
                        <td class="py-4 px-4">
                            @php
                                $badge = match($inv['status']) {
                                    'unpaid' => 'bg-amber-100 text-amber-700',
                                    'overdue' => 'bg-rose-100 text-rose-600',
                                    'partial' => 'bg-yellow-100 text-yellow-700',
                                    'pending' => 'bg-blue-100 text-blue-600',
                                    'paid' => 'bg-green-100 text-green-600',
                                };
                            @endphp
                            <span class="inline-block px-3.5 py-1 rounded-full text-[11px] font-bold {{ $badge }}">
                                {{ ucfirst($inv['status']) }}
                            </span>
                        </td>
                        <td class="py-4 px-4">
                            <div class="flex items-center gap-3">
                                <i class="fa-solid fa-pen text-slate-700 text-[15px] cursor-pointer hover:text-orange-500 transition icon-edit" title="Edit Invoice"></i>
                                <i class="fa-solid fa-bell text-slate-700 text-[15px] cursor-pointer hover:text-orange-500 transition" title="Send Reminder"></i>
                                <i class="fa-solid fa-eye text-slate-700 text-[15px] cursor-pointer hover:text-orange-500 transition icon-view" title="View Summary"></i>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="admin-billing-modals"></div>
@endsection