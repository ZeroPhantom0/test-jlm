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
                <h3 class="text-xl font-bold text-rose-600 mt-0.5">
                    ₱{{ number_format($summary['total_due'], 0) }}
                </h3>
            </div>
        </div>
        <div class="bg-white rounded-xl p-5 border border-slate-100 flex items-center gap-4">
            <div class="w-10 h-10 rounded-full bg-yellow-100 text-amber-600 flex items-center justify-center text-sm">
                <i class="fa-regular fa-clock"></i>
            </div>
            <div>
                <span class="text-xs text-slate-500 font-medium">Payment Status</span>
                <h3 class="text-xl font-bold mt-0.5">{{ $summary['status'] }}</h3>
            </div>
        </div>
        <div class="bg-white rounded-xl p-5 border border-slate-100 flex items-center gap-4">
            <div class="w-10 h-10 rounded-full bg-green-100 text-green-600 flex items-center justify-center text-sm">
                <i class="fa-regular fa-circle-check"></i>
            </div>
            <div>
                <span class="text-xs text-slate-500 font-medium">Due Date Reminder</span>
                <h3 class="text-xl font-bold text-emerald-700 mt-0.5">
                    {{ $summary['next_due_date'] }}
                </h3>
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
                            <button type="button"
                                    onclick="navigator.clipboard.writeText('09171234567')"
                                    class="bg-white/20 rounded-md px-2 py-1 text-[13px] hover:bg-white/30">
                                <i class="fa-regular fa-copy"></i>
                            </button>
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
    @if($pendingInvoices->count() > 0)
        @php $next = $pendingInvoices->first(); @endphp
        <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden mb-6">
            <div class="bg-red-50 px-6 py-4 border-b border-red-100 flex items-center gap-2.5">
                <i class="fa-solid fa-exclamation-circle text-red-500"></i>
                <h4 class="text-[15px] font-bold text-red-900 flex items-center gap-2">
                    Pending Payments
                    <span class="bg-red-500 text-white text-[11px] font-bold inline-flex items-center justify-center w-5 h-5 rounded-full">
                        {{ $pendingInvoices->count() }}
                    </span>
                </h4>
            </div>
            <div class="p-6 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3">
                        <h4 class="text-[15px] font-bold">{{ $next->description }}</h4>
                        <span class="bg-amber-100 text-amber-700 text-[11px] font-bold px-2.5 py-1 rounded-full inline-flex items-center gap-1">
                            <i class="fa-regular fa-clock"></i> {{ $next->status }}
                        </span>
                    </div>
                    <div class="text-xs text-slate-400 flex items-center gap-1.5 mt-1.5">
                        <i class="fa-regular fa-calendar"></i>
                        <span>
                            Due: {{ $next->due_date?->format('M d, Y') ?? '—' }}
                            · {{ $next->transaction_no }}
                        </span>
                    </div>
                </div>
                <div class="flex items-center gap-5">
                    <div class="text-2xl font-extrabold text-slate-800">
                        ₱{{ number_format($next->balance, 0) }}
                    </div>
                    <button id="open-payment-modal"
                            class="bg-[#00a86b] hover:bg-emerald-700 text-white px-5 py-2.5 rounded-lg font-bold text-[13px] inline-flex items-center gap-2">
                        <i class="fa-regular fa-credit-card"></i> Pay Now
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Awaiting verification --}}
    @if($verifyingInvoices->count() > 0)
        <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden mb-6">
            <div class="bg-blue-50 px-6 py-4 border-b border-blue-100 flex items-center gap-2.5">
                <i class="fa-solid fa-spinner fa-spin text-blue-500"></i>
                <h4 class="text-[15px] font-bold text-blue-900 flex items-center gap-2">
                    Awaiting Verification
                    <span class="bg-blue-500 text-white text-[11px] font-bold inline-flex items-center justify-center w-5 h-5 rounded-full">
                        {{ $verifyingInvoices->count() }}
                    </span>
                </h4>
            </div>
            @foreach($verifyingInvoices as $inv)
                <div class="p-6 flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 last:border-b-0">
                    <div>
                        <div class="flex items-center gap-3">
                            <h4 class="text-[15px] font-bold">{{ $inv->description }}</h4>
                            <span class="bg-blue-100 text-blue-700 text-[11px] font-bold px-2.5 py-1 rounded-full inline-flex items-center gap-1">
                                <i class="fa-solid fa-clock"></i> Pending
                            </span>
                        </div>
                        <div class="text-xs text-slate-400 flex items-center gap-1.5 mt-1.5">
                            <i class="fa-regular fa-calendar"></i>
                            <span>{{ $inv->transaction_no }}</span>
                            @if($inv->receipt_no) · Ref: {{ $inv->receipt_no }} @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        @if($inv->receipt_url)
                            <a href="{{ $inv->receipt_url }}" target="_blank"
                               class="text-blue-600 hover:text-blue-800 text-[12px] font-semibold px-3 py-1.5 rounded-lg border border-blue-200 hover:bg-blue-50">
                                View Receipt
                            </a>
                        @endif
                        <div class="text-lg font-bold text-slate-600">
                            ₱{{ number_format($inv->balance, 0) }}
                        </div>
                        <form method="POST" action="{{ route('tenants.billing.cancel') }}" class="inline">
                            @csrf
                            <input type="hidden" name="invoice_ref" value="{{ $inv->transaction_no }}">
                            <button type="submit"
                                    class="text-rose-500 hover:text-rose-700 text-[12px] font-semibold px-3 py-1.5 rounded-lg border border-rose-200 hover:bg-rose-50"
                                    onclick="return confirm('Cancel this payment and resubmit?')">
                                Cancel
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Payment History --}}
    <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-2.5">
            <i class="fa-solid fa-receipt text-emerald-600 text-base"></i>
            <h4 class="text-[15px] font-bold text-slate-900">Payment History</h4>
        </div>

        @forelse($paidInvoices as $inv)
            <div class="history-item border-b border-slate-100 last:border-b-0">
                <div class="history-item-header flex items-center justify-between px-6 py-4 cursor-pointer hover:bg-slate-50 transition">
                    <div class="flex flex-col gap-1">
                        <div class="flex items-center gap-3">
                            <h4 class="text-[14.5px] font-bold text-slate-900">{{ $inv->description }}</h4>
                            <span class="bg-emerald-100 text-emerald-600 text-[11px] font-bold px-2.5 py-1 rounded-full inline-flex items-center gap-1">
                                <i class="fa-regular fa-circle-check"></i> Paid
                            </span>
                        </div>
                        <div class="text-xs text-slate-400 flex items-center gap-1.5">
                            <i class="fa-regular fa-circle-check text-emerald-600"></i>
                            <span>
                                Paid: {{ $inv->date_paid?->format('M d, Y') ?? '—' }}
                                · {{ $inv->payment_method }}
                                @if($inv->receipt_no) · {{ $inv->receipt_no }} @endif
                            </span>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="text-[18px] font-extrabold text-slate-900">
                            ₱{{ number_format($inv->amount_paid, 0) }}
                        </div>
                        <i class="fa-solid fa-chevron-down history-expand-icon text-slate-400 text-xs transition-transform duration-300"></i>
                    </div>
                </div>

                <div class="history-details-panel hidden bg-emerald-50/60 border-t border-emerald-100 px-6 py-5">
                    <div class="flex items-center gap-2 text-[14px] font-bold text-emerald-700 mb-4">
                        <i class="fa-solid fa-circle-check text-base"></i>
                        <span>Payment Verified</span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex flex-col gap-1">
                            <span class="text-[11px] text-emerald-600 font-medium uppercase">Invoice No.</span>
                            <p class="text-[13.5px] font-bold text-emerald-900">{{ $inv->transaction_no }}</p>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-[11px] text-emerald-600 font-medium uppercase">Paid Date</span>
                            <p class="text-[13.5px] font-bold text-emerald-900">{{ $inv->date_paid?->format('M d, Y') ?? '—' }}</p>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-[11px] text-emerald-600 font-medium uppercase">Payment Method</span>
                            <p class="text-[13.5px] font-bold text-emerald-900">{{ $inv->payment_method }}</p>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-[11px] text-emerald-600 font-medium uppercase">Reference No.</span>
                            <p class="text-[13.5px] font-bold text-emerald-900">{{ $inv->receipt_no ?? '—' }}</p>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-[11px] text-emerald-600 font-medium uppercase">Total Amount</span>
                            <p class="text-[13.5px] font-bold text-emerald-900">₱{{ number_format($inv->total_amount, 2) }}</p>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-[11px] text-emerald-600 font-medium uppercase">Amount Paid</span>
                            <p class="text-[13.5px] font-bold text-emerald-900">₱{{ number_format($inv->amount_paid, 2) }}</p>
                        </div>
                    </div>
                    @if($inv->receipt_url)
                        <div class="mt-4">
                            <a href="{{ $inv->receipt_url }}" target="_blank"
                               class="inline-flex items-center gap-2 text-emerald-700 hover:text-emerald-900 text-[12px] font-semibold px-3 py-2 rounded-lg border border-emerald-200 hover:bg-emerald-100">
                                <i class="fa-regular fa-image"></i> View Receipt
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="px-6 py-12 text-center text-slate-400">
                <i class="fa-regular fa-receipt text-3xl mb-2 block"></i>
                <p class="text-sm">No payment history yet.</p>
            </div>
        @endforelse
    </div>
</div>

{{-- React Payment Modal Mount --}}
@if($paymentPayload)
<div id="payment-modal-root"
     data-invoice="{{ json_encode($paymentPayload, JSON_HEX_APOS | JSON_HEX_QUOT) }}"></div>
@endif
@endsection

@push('scripts')
<script>
    (function () {
        document.querySelectorAll('.history-item-header').forEach((header) => {
            header.addEventListener('click', () => {
                const panel = header.nextElementSibling;
                const icon  = header.querySelector('.history-expand-icon');
                if (!panel) return;

                panel.classList.toggle('hidden');
                icon.style.transform = panel.classList.contains('hidden')
                    ? 'rotate(0deg)'
                    : 'rotate(180deg)';
            });
        });
    })();
</script>
@endpush