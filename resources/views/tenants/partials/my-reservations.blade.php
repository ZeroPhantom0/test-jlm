{{--
    "My Reservations" rows (Reservations page). Rendered on page load and by
    TenantReservationController@data for live refreshes, so both always match.
      $bills          — TenantDashboardService::outstandingBills()  → Pay Now
      $myReservations — Pending + current Approved stays             → View Details
--}}
@php
    $badge = [
        'Pending'  => ['bg-amber-50 text-amber-700 border border-amber-200', 'fa-regular fa-clock'],
        'Approved' => ['bg-emerald-50 text-emerald-700 border border-emerald-200', 'fa-regular fa-circle-check'],
        'Partial'  => ['bg-amber-50 text-amber-700 border border-amber-200', 'fa-solid fa-circle-half-stroke'],
        'Unpaid'   => ['bg-orange-50 text-orange-700 border border-orange-200', 'fa-regular fa-circle'],
        'Overdue'  => ['bg-rose-50 text-rose-700 border border-rose-200', 'fa-solid fa-triangle-exclamation'],
        'Verifying' => ['bg-sky-50 text-sky-700 border border-sky-200', 'fa-solid fa-hourglass-half'],
    ];
    $btnOutline = 'inline-flex items-center justify-center h-9 px-4 rounded-lg border border-slate-300 bg-white text-[12.5px] font-semibold text-slate-700 hover:bg-slate-50 transition';
    $btnNavy = 'inline-flex items-center justify-center h-9 px-4 rounded-lg bg-[#0A1F44] hover:bg-[#13305f] text-white text-[12.5px] font-semibold transition';
@endphp

@if (empty($bills) && count($myReservations) === 0)
    <div class="px-6 py-8 text-center text-[13px] text-slate-400">
        <i class="fa-regular fa-calendar-check text-3xl mb-2 block opacity-40"></i>
        No reservations yet. Pick a room below to get started.
    </div>
@endif

{{-- ── Bills to settle ── --}}
@foreach ($bills as $bill)
    @php
        $label = $bill['status'] === 'Pending' ? 'Verifying' : $bill['status'];
        [$cls, $icon] = $badge[$label] ?? $badge['Unpaid'];
    @endphp
    <div class="px-6 py-4 flex items-center justify-between flex-wrap gap-4 border-b border-slate-200 last:border-b-0">
        <div class="flex flex-col gap-1.5 min-w-0">
            <div class="flex items-center gap-3 flex-wrap">
                <h4 class="text-[14.5px] font-bold text-slate-900">{{ $bill['title'] }}</h4>
                <span class="text-[11px] font-bold px-2.5 py-[3px] rounded-full inline-flex items-center gap-1 {{ $cls }}">
                    <i class="{{ $icon }}"></i> {{ $label === 'Verifying' ? 'Awaiting verification' : $label }}
                </span>
            </div>
            <div class="text-[12px] text-slate-500">
                Due: {{ $bill['due'] }}
                @if ($bill['paid'] > 0)
                    <span class="mx-1 text-slate-300">|</span> Paid so far: ₱{{ number_format($bill['paid'], 0) }}
                @endif
                @if (!empty($bill['room']))
                    <span class="mx-1 text-slate-300">·</span> {{ $bill['room'] }}
                @endif
            </div>
        </div>
        <div class="flex flex-col items-end gap-2">
            <div class="text-right leading-tight">
                <div class="text-base font-extrabold text-emerald-600">₱{{ number_format($bill['balance'], 0) }}</div>
                <div class="text-[11px] text-slate-500">{{ $label === 'Verifying' ? 'submitted' : 'balance' }}</div>
            </div>
            @if ($bill['payNow'])
                <button type="button" class="open-payment-modal {{ $btnNavy }}"
                    data-invoice="{{ json_encode($bill['payNow'], JSON_HEX_APOS | JSON_HEX_QUOT) }}">
                    Pay Now
                </button>
            @else
                <span class="text-[11.5px] text-slate-500">We'll let you know once it's verified.</span>
            @endif
        </div>
    </div>
@endforeach

{{-- ── Reservations awaiting review / current stays ── --}}
@foreach ($myReservations as $res)
    @php [$cls, $icon] = $badge[$res['status']] ?? $badge['Pending']; @endphp
    <div class="px-6 py-4 flex items-center justify-between flex-wrap gap-4 border-b border-slate-200 last:border-b-0">
        <div class="flex flex-col gap-1.5 min-w-0">
            <div class="flex items-center gap-3 flex-wrap">
                <h4 class="text-[14.5px] font-bold text-slate-900">{{ $res['room'] }}</h4>
                <span class="text-[11px] font-bold px-2.5 py-[3px] rounded-full inline-flex items-center gap-1 {{ $cls }}">
                    <i class="{{ $icon }}"></i> {{ $res['status'] }}
                </span>
            </div>
            <div class="text-[12px] text-slate-500">
                Move-in: {{ $res['move_in'] }}
                <span class="mx-1 text-slate-300">·</span>
                @if ($res['status'] === 'Approved')
                    Move-out: {{ $res['move_out'] }}
                @else
                    Submitted: {{ $res['submitted'] }}
                @endif
            </div>
            @if (!empty($res['note']))
                <div class="bg-green-50 text-green-800 text-[11px] px-2.5 py-1 rounded-md inline-block mt-1 font-medium">
                    {{ $res['note'] }}
                </div>
            @endif
        </div>
        <div class="flex flex-col items-end gap-2">
            @if ($res['status'] === 'Pending')
                <div class="text-right leading-tight">
                    <div class="text-base font-extrabold text-emerald-600">₱{{ $res['deposit'] }}</div>
                    <div class="text-[11px] text-slate-500">deposit</div>
                </div>
            @endif
            <div class="flex items-center gap-2">
                @if ($res['status'] === 'Pending')
                    <button type="button" class="cancel-reservation-btn {{ $btnOutline }}" data-res-key="{{ $res['key'] }}">
                        Cancel Reservation
                    </button>
                @endif
                <button type="button" class="open-booking-details-btn {{ $btnNavy }}"
                    data-details="{{ json_encode($res['details'], JSON_HEX_APOS | JSON_HEX_QUOT) }}">
                    View Details
                </button>
            </div>
        </div>
    </div>
@endforeach
