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
            <h1 class="text-2xl font-extrabold text-slate-900">Billing &amp; Payments</h1>
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
                <h2 class="text-[26px] font-extrabold text-slate-900 mt-0.5">₱{{ number_format($metrics['collected'], 0) }}</h2>
            </div>
        </div>

        <div class="bg-white rounded-2xl px-6 py-5 border border-slate-100 shadow-sm flex items-center gap-5">
            <div class="w-13 h-13 rounded-full bg-yellow-100 text-amber-600 flex items-center justify-center text-[22px] shrink-0">
                <i class="fa-regular fa-calendar"></i>
            </div>
            <div>
                <p class="text-[13px] font-medium text-slate-500">Pending Payments</p>
                <h2 class="text-[26px] font-extrabold text-slate-900 mt-0.5">₱{{ number_format($metrics['pending'], 0) }}</h2>
            </div>
        </div>

        <div class="bg-white rounded-2xl px-6 py-5 border border-slate-100 shadow-sm flex items-center gap-5">
            <div class="w-13 h-13 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center text-[22px] shrink-0">
                <i class="fa-solid fa-circle-exclamation"></i>
            </div>
            <div>
                <p class="text-[13px] font-medium text-slate-500">Overdue</p>
                <h2 class="text-[26px] font-extrabold text-slate-900 mt-0.5">₱{{ number_format($metrics['overdue'], 0) }}</h2>
            </div>
        </div>
    </div>

    {{-- Table Card --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 space-y-5">
        <form method="GET" action="{{ route('admin.billing') }}" class="flex flex-col md:flex-row gap-4">
            <div class="flex-1 bg-white border border-slate-300 rounded-[10px] px-4 py-2.5 flex items-center gap-2.5">
                <i class="fa-solid fa-magnifying-glass text-slate-400 text-sm"></i>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search invoices..." class="w-full text-[13px] outline-none bg-transparent">
            </div>
            <select name="status" onchange="this.form.submit()" class="bg-white border border-slate-300 rounded-[10px] px-4 py-2.5 text-[13px] font-medium text-slate-700 outline-none cursor-pointer min-w-[160px]">
                <option value="">All Statuses</option>
                @foreach(['Unpaid','Overdue','Partial','Pending','Paid'] as $s)
                    <option value="{{ $s }}" {{ ($filters['status'] ?? '') === $s ? 'selected' : '' }}>{{ $s }}</option>
                @endforeach
            </select>
            <select name="payment_method" onchange="this.form.submit()" class="bg-white border border-slate-300 rounded-[10px] px-4 py-2.5 text-[13px] font-medium text-slate-700 outline-none cursor-pointer min-w-[160px]">
                <option value="">All Methods</option>
                @foreach(['GCash','Over The Counter','Bank Transfer','Not Set'] as $m)
                    <option value="{{ $m }}" {{ ($filters['payment_method'] ?? '') === $m ? 'selected' : '' }}>{{ $m }}</option>
                @endforeach
            </select>
        </form>

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
                    @forelse($invoices as $inv)
                    <tr class="border-b border-slate-50 last:border-0" data-tn="{{ $inv->transaction_no }}">
                        <td class="py-4 px-4">
                            <div class="font-semibold text-slate-700 text-[13px]">{{ $inv->transaction_no }}</div>
                            <div class="text-[11px] text-slate-400 mt-0.5">{{ $inv->description }}</div>
                        </td>
                        <td class="py-4 px-4">
                            <div class="font-bold text-slate-900 text-[13px]">{{ $inv->tenant_name }}</div>
                            <div class="text-[11px] text-slate-400 mt-0.5">{{ $inv->room_number }}</div>
                        </td>
                        <td class="py-4 px-4">
                            <div class="font-bold text-slate-900 text-[13px]">{{ $inv->formatted_total }}</div>
                            <div class="text-[11px] text-slate-400 mt-0.5">{{ $inv->formatted_rate }}</div>
                        </td>
                        <td class="py-4 px-4">
                            <div class="font-bold text-slate-900 text-[13px]">{{ $inv->formatted_paid }}</div>
                            <div class="text-[11px] text-slate-400 mt-0.5">{{ $inv->formatted_days }}</div>
                        </td>
                        <td class="py-4 px-4 font-bold text-slate-900 text-[13px]">{{ $inv->formatted_balance }}</td>
                        <td class="py-4 px-4 text-[13px] text-slate-600">{{ $inv->due_date?->format('M d, Y') ?? '-' }}</td>
                        <td class="py-4 px-4">
                            <span class="inline-block px-3.5 py-1 rounded-full text-[11px] font-bold {{ $inv->status_class }}">
                                {{ $inv->status }}
                            </span>
                        </td>
                        <td class="py-4 px-4">
                            <div class="flex items-center gap-3">
                                {{-- Edit Invoice (always) --}}
                                <i class="fa-solid fa-pen text-slate-700 text-[15px] cursor-pointer hover:text-orange-500 transition icon-edit" title="Edit Invoice"></i>

                                @if($inv->status === 'Paid')
                                    <i class="fa-solid fa-receipt text-slate-700 text-[15px] cursor-pointer hover:text-orange-500 transition icon-payment" title="View Payment Receipt"></i>
                                @elseif($inv->status === 'Pending')
                                    <i class="fa-solid fa-file-lines text-slate-700 text-[15px] cursor-pointer hover:text-orange-500 transition icon-receipt" title="View Receipt"></i>
                                @else
                                    <i class="fa-solid fa-bell text-slate-700 text-[15px] cursor-pointer hover:text-orange-500 transition icon-remind" title="Send Reminder"></i>
                                @endif

                                <i class="fa-solid fa-eye text-slate-700 text-[15px] cursor-pointer hover:text-orange-500 transition icon-view" title="View Summary"></i>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-12 text-slate-400">
                            <i class="fa-solid fa-inbox text-4xl mb-3"></i>
                            <p class="text-sm">No invoices found.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- React Modal Mount Point with data --}}
<div id="admin-billing-modals" data-invoices='@json($invoicesForReact)'></div>
@endsection