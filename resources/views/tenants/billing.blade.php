@extends('layouts.tenant', ['active' => 'billing', 'pageTitle' => 'My Billing'])
@section('title', 'My Billing')

@section('content')
<div class="p-4 md:p-8 max-w-6xl mx-auto">

    {{-- Page title --}}
    <div class="flex items-center gap-4 mb-6">
        <div class="w-11 h-11 rounded-full bg-green-100 text-green-600 flex items-center justify-center text-xl font-bold">₱</div>
        <div>
            <h2 class="text-xl font-bold text-slate-800">Billing & Payments</h2>
            <p class="text-[13px] text-slate-500">Track your rent payments and receipts</p>
        </div>
    </div>

    {{-- Summary cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-6">
        <div class="bg-white rounded-xl p-5 border border-slate-100 flex items-center gap-4">
            <div class="w-10 h-10 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center text-sm">
                <i class="fa-solid fa-exclamation"></i>
            </div>
            <div>
                <span class="text-xs text-slate-500 font-medium">Total Amount Due</span>
                <h3 class="text-xl font-bold text-rose-600 mt-0.5">₱600</h3>
            </div>
        </div>
        <div class="bg-white rounded-xl p-5 border border-slate-100 flex items-center gap-4">
            <div class="w-10 h-10 rounded-full bg-yellow-100 text-amber-600 flex items-center justify-center text-sm">
                <i class="fa-regular fa-clock"></i>
            </div>
            <div>
                <span class="text-xs text-slate-500 font-medium">Payment Status</span>
                <h3 class="text-xl font-bold mt-0.5">Unpaid</h3>
            </div>
        </div>
        <div class="bg-white rounded-xl p-5 border border-slate-100 flex items-center gap-4">
            <div class="w-10 h-10 rounded-full bg-green-100 text-green-600 flex items-center justify-center text-sm">
                <i class="fa-regular fa-circle-check"></i>
            </div>
            <div>
                <span class="text-xs text-slate-500 font-medium">Due Date Reminder</span>
                <h3 class="text-xl font-bold text-emerald-700 mt-0.5">March 22, 2026</h3>
            </div>
        </div>
    </div>

    {{-- GCash Instructions --}}
    <div class="bg-[#1d61f2] text-white rounded-2xl p-6 mb-6 shadow-lg">
        <div class="flex items-start gap-4 mb-5">
            <div class="w-11 h-11 rounded-xl bg-white/20 flex items-center justify-center text-xl">
                <i class="fa-solid fa-mobile-screen-button"></i>
            </div>
            <div>
                <h3 class="text-base font-bold">GCash Payment Instructions</h3>
                <p class="text-xs opacity-85 mt-0.5">Follow these steps to pay your rent via GCash</p>
            </div>
        </div>
        <div class="space-y-3.5">
            <div class="flex items-center gap-3 text-[13px] flex-wrap">
                <span class="w-5.5 h-5.5 rounded-full bg-white/25 flex items-center justify-center text-[11px] font-bold w-6 h-6">1</span>
                <span>Transfer to GCash number:</span>
                <span class="text-xl font-extrabold tracking-wide inline-flex items-center gap-2.5 ml-1.5">
                    0917-123-4567
                    <button class="bg-white/20 rounded-md px-2 py-1 text-[13px]"><i class="fa-regular fa-copy"></i></button>
                </span>
            </div>
            <div class="flex items-center gap-3 text-[13px]">
                <span class="w-6 h-6 rounded-full bg-white/25 flex items-center justify-center text-[11px] font-bold">2</span>
                <span>Screenshot your GCash receipt</span>
            </div>
            <div class="flex items-center gap-3 text-[13px]">
                <span class="w-6 h-6 rounded-full bg-white/25 flex items-center justify-center text-[11px] font-bold">3</span>
                <span>Upload the screenshot using the "Pay Now" button on your invoice below</span>
            </div>
        </div>
    </div>

    {{-- Pending payment card --}}
    <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden">
        <div class="bg-red-50 px-6 py-4 border-b border-red-100 flex items-center gap-2.5">
            <i class="fa-solid fa-exclamation-circle text-red-500"></i>
            <h4 class="text-[15px] font-bold text-red-900">Pending Payments
                <span class="bg-red-500 text-white text-[11px] font-bold w-4.5 h-4.5 rounded-full inline-flex items-center justify-center w-5 h-5">1</span>
            </h4>
        </div>
        <div class="p-6 flex flex-wrap items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <h4 class="text-[15px] font-bold">Rent - March 2026</h4>
                    <span class="bg-amber-100 text-amber-700 text-[11px] font-bold px-2.5 py-1 rounded-full inline-flex items-center gap-1">
                        <i class="fa-regular fa-clock"></i> Unpaid
                    </span>
                </div>
                <div class="text-xs text-slate-400 flex items-center gap-1.5 mt-1.5">
                    <i class="fa-regular fa-calendar"></i>
                    <span>Due: Mar 30, 2026 inv-101</span>
                </div>
            </div>
            <div class="flex items-center gap-5">
                <div class="text-2xl font-extrabold text-slate-800">₱600</div>
                <button id="open-payment-modal"
                        class="bg-[#00a86b] hover:bg-emerald-700 text-white px-5 py-2.5 rounded-lg font-bold text-[13px] inline-flex items-center gap-2">
                    <i class="fa-regular fa-credit-card"></i> Pay Now
                </button>
            </div>
        </div>
    </div>
</div>

{{-- React Payment Modal --}}
<div id="payment-modal-root"></div>
@endsection

@push('scripts')
    <script>
        // Pass trigger to a React-rendered modal.
        // See resources/js/pages/Billing/PaymentModal.jsx
        window.__BILLING_MOUNT__ = document.getElementById('payment-modal-root');
        window.__OPEN_PAYMENT__ = document.getElementById('open-payment-modal');
    </script>
@endpush