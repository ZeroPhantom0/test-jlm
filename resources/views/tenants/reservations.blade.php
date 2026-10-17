@extends('layouts.tenant', ['active' => 'reservations', 'pageTitle' => 'Reservations'])
@section('title', 'Reservations')

@section('content')

{{-- React mount point for all reservation modals --}}
<div id="tenant-reservations-modals" data-reservations='@json($reservations ?? [])'></div>

<div class="p-4 md:p-8 max-w-6xl mx-auto">

    {{-- ============ BANNER ============ --}}
    <div class="flex items-start gap-4 mb-5">
        <div class="w-10 h-10 rounded-lg bg-emerald-100 text-emerald-500 flex items-center justify-center text-lg">
            <i class="fa-regular fa-calendar-plus"></i>
        </div>
        <div>
            <h2 class="text-xl font-bold">Reserve a Room</h2>
            <p class="text-[13px] text-slate-500">Browse rooms with virtual tours and submit a reservation request</p>
        </div>
    </div>

    {{-- ============ HOW IT WORKS ============ --}}
    <div class="bg-blue-50 rounded-xl px-5 py-4 flex items-start gap-3 mb-7">
        <i class="fa-solid fa-circle-info text-blue-500 mt-0.5"></i>
        <p class="text-[13px] text-blue-800 leading-relaxed">
            <strong>How Reservations Work</strong><br>
            Only rooms with a <strong>published virtual tour</strong> are shown below. View the tour, then submit your reservation with your GCash payment proof.
            <strong>Your reservation will be reviewed by the admin. You'll be notified once approved.</strong>
        </p>
    </div>

    {{-- ============ MY RESERVATIONS ============ --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm mb-8 overflow-hidden">
        <div class="px-6 py-[18px] flex items-center gap-2.5 border-b border-slate-200">
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-regular fa-calendar-check text-emerald-500"></i>
                My Reservations
            </h3>
        </div>

        @forelse($myReservations as $res)
            <div class="px-6 py-4 flex items-center justify-between flex-wrap gap-3 border-b border-slate-200 last:border-b-0">
                <div class="flex flex-col gap-1.5">
                    <div class="flex items-center gap-3 flex-wrap">
                        <h4 class="text-[14.5px] font-bold text-slate-900">{{ $res['room'] }}</h4>
                        <span class="text-[11px] font-bold px-2.5 py-[3px] rounded-full inline-flex items-center gap-1 {{ $res['statusClass'] }}">
                            <i class="{{ $res['statusIcon'] }}"></i> {{ $res['status'] }}
                        </span>
                    </div>
                    <div class="text-[12px] text-slate-500">
                        Move-in: {{ $res['move_in'] }} · Submitted: {{ $res['submitted'] }}
                    </div>
                    @if(!empty($res['note']))
                        <div class="bg-green-50 text-green-800 text-[11px] px-2.5 py-1 rounded-md inline-block mt-1 font-medium">
                            {{ $res['note'] }}
                        </div>
                    @endif
                </div>
                <div class="text-right flex flex-col items-end gap-0.5">
                    <div class="text-base font-extrabold text-emerald-500">₱{{ $res['deposit'] }}</div>
                    <div class="text-[11px] text-slate-500">deposit</div>
                    @if($res['status'] === 'Pending')
                        <button
                            type="button"
                            class="cancel-reservation-btn text-[11px] font-semibold text-rose-500 hover:text-rose-700 mt-1"
                            data-res-key="{{ $res['key'] }}"
                        >
                            Cancel
                        </button>
                    @endif
                </div>
            </div>
        @empty
            <div class="px-6 py-8 text-center text-[13px] text-slate-400">
                <i class="fa-regular fa-calendar-check text-3xl mb-2 block opacity-40"></i>
                No reservations yet.
            </div>
        @endforelse
    </div>

    {{-- ============ AVAILABLE ROOMS ============ --}}
    <div class="flex justify-between items-center mb-5 flex-wrap gap-3">
        <h3 class="text-lg font-bold">Available Rooms (With Virtual Tour)</h3>
        <div class="text-[12px] text-slate-500">
            {{ $rooms->count() }} room{{ $rooms->count() === 1 ? '' : 's' }} available
        </div>
    </div>

    @if($rooms->isEmpty())
        <div class="bg-white border border-dashed border-slate-200 rounded-2xl p-12 text-center text-slate-400 mb-8">
            <i class="fa-solid fa-camera text-4xl mb-3 block opacity-40"></i>
            <p class="text-sm font-medium text-slate-600">No rooms with virtual tours yet.</p>
            <p class="text-xs mt-1">The admin needs to publish at least one virtual tour.</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-8">
            @foreach($rooms as $room)
                @php
                    $roomJson = json_encode([
                        'id'          => $room['id'],
                        'name'        => $room['name'],
                        'roomNumber'  => 'ROOM ' . $room['num'],
                        'floor'       => $room['floor'],
                        'status'      => $room['status'],
                        'price'       => $room['price'],
                        'img'         => $room['img'],
                        'pax'         => $room['pax'],
                        'beds'        => $room['beds'],
                        'bath'        => $room['bath'],
                        'description' => $room['desc'],
                        'estDate'     => $room['estDate'],
                        'estApprox'   => $room['estApprox'],
                        'tourId'      => $room['tourId'],
                    ]);
                @endphp

                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden flex flex-col shadow-sm">
                    <div class="relative h-52">
                        <img src="{{ $room['img'] }}" alt="Room {{ $room['num'] }}" class="w-full h-full object-cover">

                        <span class="absolute top-3 left-3 text-[11px] font-bold px-3 py-1 rounded-full
                            {{ $room['status'] === 'Occupied' ? 'bg-rose-200 text-rose-700' : '' }}
                            {{ $room['status'] === 'Available' ? 'bg-green-100 text-green-700' : '' }}
                            {{ $room['status'] === 'Reserved' ? 'bg-amber-100 text-amber-700' : '' }}">
                            {{ $room['status'] }}
                        </span>

                        <a href="{{ route('tour.viewer', $room['tourId']) }}" target="_blank"
                           class="absolute top-3 right-3 bg-black/60 hover:bg-black/80 text-white text-[10px] font-semibold px-2.5 py-1 rounded-full backdrop-blur transition">
                            <i class="fa-solid fa-camera mr-1"></i> 360° Tour
                        </a>

                        <span class="absolute bottom-3 left-3 bg-blue-50 text-blue-600 text-[10px] font-extrabold uppercase px-2.5 py-1 rounded-md">
                            ROOM {{ $room['num'] }}
                        </span>
                    </div>

                    <div class="p-4 flex flex-col gap-2.5 flex-1">
                        <div class="flex justify-between items-center">
                            <h4 class="text-[15px] font-bold">{{ $room['name'] }}</h4>
                            <div class="text-sm font-extrabold text-emerald-500">
                                ₱{{ number_format($room['price'], 0) }}<span class="text-[11px] font-normal text-slate-500">/day</span>
                            </div>
                        </div>

                        <div class="flex gap-3.5 text-[11px] text-slate-500">
                            <span class="flex items-center gap-1"><i class="fa-solid fa-users"></i> {{ $room['pax'] }}</span>
                            <span class="flex items-center gap-1"><i class="fa-solid fa-bed"></i> {{ $room['beds'] }}</span>
                            <span class="flex items-center gap-1"><i class="fa-solid fa-bath"></i> {{ $room['bath'] }}</span>
                        </div>

                        <div class="grid grid-cols-2 gap-2.5 mt-1.5">
                            <button
                                type="button"
                                class="open-room-details-btn bg-white border border-slate-200 rounded-lg py-2 text-xs font-semibold inline-flex items-center justify-center gap-1.5 hover:bg-slate-50 transition"
                                data-room='{{ $roomJson }}'
                            >
                                <i class="fa-regular fa-eye"></i> Details
                            </button>

                            @if($room['status'] === 'Available')
                                <button
                                    type="button"
                                    class="open-reserve-modal-btn bg-[#02122c] hover:bg-[#0d2347] text-white rounded-lg py-2 text-xs font-semibold inline-flex items-center justify-center gap-1.5 transition"
                                    data-room='{{ $roomJson }}'
                                >
                                    <i class="fa-regular fa-calendar-check"></i> Reserve
                                </button>
                            @else
                                <button disabled class="bg-slate-100 text-slate-400 rounded-lg py-2 text-xs font-semibold cursor-not-allowed text-center">
                                    Not Available
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection