@extends('layouts.tenant', ['active' => 'billing', 'pageTitle' => 'My Billing'])
@section('title', 'My Billing')

@section('content')
<div class="p-4 md:p-8 max-w-6xl mx-auto">

    {{-- Page title --}}
    <div class="flex items-center gap-4 mb-6">
        <div class="w-11 h-11 rounded-full bg-green-100 text-green-600 flex items-center justify-center text-xl font-bold">₱</div>
        <div>
            <h2 class="text-xl font-bold text-slate-800">Billing &amp; Payments</h2>
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
        <div class="flex flex-col md:flex-row md:items-center gap-6">
            <div class="flex-1 min-w-0">
                <div class="flex items-start gap-4 mb-5">
                    <div class="w-11 h-11 rounded-xl bg-white/20 flex items-center justify-center text-xl shrink-0">
                        <i class="fa-solid fa-mobile-screen-button"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold">GCash Payment Instructions</h3>
                        <p class="text-xs opacity-85 mt-0.5">Follow these steps to pay your rent via GCash</p>
                    </div>
                </div>
                <div class="space-y-3.5">
                    <div class="flex items-center gap-3 text-[13px] flex-wrap">
                        <span class="w-6 h-6 rounded-full bg-white/25 flex items-center justify-center text-[11px] font-bold">1</span>
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

            {{-- QR Code --}}
            <div class="bg-white rounded-xl p-3 flex flex-col items-center justify-center shrink-0 w-[180px] h-[180px] self-center md:self-auto">
                <img
                    src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=GCashPaymentInfo"
                    alt="GCash QR Code"
                    class="w-full h-full object-contain"
                >
                <div class="text-[9px] text-slate-800 font-semibold mt-1 uppercase tracking-wide">LEAH A.</div>
            </div>
        </div>
    </div>

    {{-- Pending payment card --}}
    <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden mb-6">
        <div class="bg-red-50 px-6 py-4 border-b border-red-100 flex items-center gap-2.5">
            <i class="fa-solid fa-exclamation-circle text-red-500"></i>
            <h4 class="text-[15px] font-bold text-red-900 flex items-center gap-2">
                Pending Payments
                <span class="bg-red-500 text-white text-[11px] font-bold inline-flex items-center justify-center w-5 h-5 rounded-full">1</span>
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

    {{-- Payment History Card --}}
    <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden">

        {{-- Header --}}
        <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-2.5">
            <i class="fa-solid fa-receipt text-emerald-600 text-base"></i>
            <h4 class="text-[15px] font-bold text-slate-900">Payment History</h4>
        </div>

        {{-- History Item 1 (Expandable) --}}
        <div class="history-item border-b border-slate-100 last:border-b-0">
            <div class="history-item-header flex items-center justify-between px-6 py-4 cursor-pointer hover:bg-slate-50 transition">
                <div class="flex flex-col gap-1">
                    <div class="flex items-center gap-3">
                        <h4 class="text-[14.5px] font-bold text-slate-900">Reservation Fee Mar</h4>
                        <span class="bg-emerald-100 text-emerald-600 text-[11px] font-bold px-2.5 py-1 rounded-full inline-flex items-center gap-1">
                            <i class="fa-regular fa-circle-check"></i> Paid
                        </span>
                    </div>
                    <div class="text-xs text-slate-400 flex items-center gap-1.5">
                        <i class="fa-regular fa-circle-check text-emerald-600"></i>
                        <span>Paid: Mar 11, 2026 Over The_counter OTC-20260215-0018</span>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <div class="text-[18px] font-extrabold text-slate-900">₱100</div>
                    <i class="fa-solid fa-chevron-down history-expand-icon text-slate-400 text-xs transition-transform duration-300"></i>
                </div>
            </div>

            {{-- Expandable Details Panel --}}
            <div class="history-details-panel hidden bg-emerald-50/60 border-t border-emerald-100 px-6 py-5">
                <div class="flex items-center gap-2 text-[14px] font-bold text-emerald-700 mb-4">
                    <i class="fa-solid fa-circle-check text-base"></i>
                    <span>Payment Verified</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1">
                        <span class="text-[11px] text-emerald-600 font-medium uppercase">Due Date</span>
                        <p class="text-[13.5px] font-bold text-emerald-900">Mar 22, 2026</p>
                    </div>
                    <div class="flex flex-col gap-1">
                        <span class="text-[11px] text-emerald-600 font-medium uppercase">Paid Date</span>
                        <p class="text-[13.5px] font-bold text-emerald-900">Mar 21, 2026</p>
                    </div>
                    <div class="flex flex-col gap-1">
                        <span class="text-[11px] text-emerald-600 font-medium uppercase">Payment Method</span>
                        <p class="text-[13.5px] font-bold text-emerald-900">Over The_counter</p>
                    </div>
                    <div class="flex flex-col gap-1">
                        <span class="text-[11px] text-emerald-600 font-medium uppercase">Reference No.</span>
                        <p class="text-[13.5px] font-bold text-emerald-900">OTC-20260215-0018</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- React Payment Modal Mount --}}
<div id="payment-modal-root"
     data-invoice="{{ json_encode([
        'reference' => 'inv-101',
        'title'     => 'Rent - March 2026',
        'amount'    => 600,
        'due_date'  => 'Mar 30, 2026',
        'gcash_number' => '0917-123-4567',
     ], JSON_HEX_APOS | JSON_HEX_QUOT) }}"></div>
@endsection

@push('scripts')
<script>
    (function () {
        // Toggle payment history details
        document.querySelectorAll('.history-item-header').forEach((header) => {
            header.addEventListener('click', () => {
                const panel = header.nextElementSibling;
                const icon  = header.querySelector('.history-expand-icon');
                if (!panel) return;

                panel.classList.toggle('hidden');
                if (panel.classList.contains('hidden')) {
                    icon.style.transform = 'rotate(0deg)';
                } else {
                    icon.style.transform = 'rotate(180deg)';
                }
            });
        });
    })();
</script>
@endpush