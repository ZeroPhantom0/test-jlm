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
            <p class="text-[13px] text-slate-500">Browse available rooms and submit a reservation request</p>
        </div>
    </div>

    {{-- ============ HOW IT WORKS ============ --}}
    <div class="bg-blue-50 rounded-xl px-5 py-4 flex items-start gap-3 mb-7">
        <i class="fa-solid fa-circle-info text-blue-500 mt-0.5"></i>
        <p class="text-[13px] text-blue-800 leading-relaxed">
            <strong>How Reservations Work</strong><br>
            Browse available rooms below, view the 360° virtual tour, then submit your reservation details.
            <strong>Your reservation will be reviewed by the admin. You'll be notified once approved.</strong>
        </p>
    </div>

    {{-- ============ MY RESERVATIONS ============ --}}
    @php
        $myReservations = $myReservations ?? [
            [
                'id' => 1, 'key' => 'res-1',
                'room' => 'Solo Room - Room 102',
                'status' => 'Pending', 'statusClass' => 'bg-amber-100 text-amber-700',
                'statusIcon' => 'fa-regular fa-clock',
                'move_in' => 'Apr 8, 2026', 'submitted' => 'Apr 1, 2026',
                'deposit' => '500.00', 'note' => null,
            ],
            [
                'id' => 2, 'key' => 'res-2',
                'room' => 'Solo Room - Unit 101',
                'status' => 'Approved', 'statusClass' => 'bg-emerald-100 text-emerald-600',
                'statusIcon' => 'fa-regular fa-circle-check',
                'move_in' => 'Sep 13, 2025', 'submitted' => 'Aug 29, 2025',
                'deposit' => '4,500.00',
                'note' => 'Approved. Successfully moved in on Jan 15, 2023.',
            ],
            [
                'id' => 3, 'key' => 'res-3',
                'room' => 'Solo Room - Unit 201',
                'status' => 'Rejected', 'statusClass' => 'bg-rose-100 text-rose-600',
                'statusIcon' => 'fa-regular fa-circle-xmark',
                'move_in' => 'Aug 24, 2025', 'submitted' => 'Aug 9, 2025',
                'deposit' => '4,800.00', 'note' => null,
            ],
        ];
    @endphp

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm mb-8 overflow-hidden">
        <div class="px-[24px] py-[18px] flex items-center gap-2.5 border-b border-slate-200">
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-regular fa-calendar-check text-emerald-500"></i>
                My Reservations
            </h3>
        </div>

        @forelse($myReservations as $res)
            <div class="px-[24px] py-[16px] flex items-center justify-between flex-wrap gap-3 border-b border-slate-200 last:border-b-0">
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
                No reservations yet.
            </div>
        @endforelse
    </div>

    {{-- ============ FILTERS ============ --}}
    <div class="flex justify-between items-center mb-5 flex-wrap gap-3">
        <h3 class="text-lg font-bold">Available Rooms</h3>
        <div class="flex gap-3 flex-wrap">
            <select class="bg-white border border-slate-200 rounded-lg px-4 py-2 text-xs">
                <option>All Status</option>
            </select>
            <select class="bg-white border border-slate-200 rounded-lg px-4 py-2 text-xs">
                <option>Select Capacity</option>
            </select>
            <select class="bg-white border border-slate-200 rounded-lg px-4 py-2 text-xs">
                <option>Select Room Type</option>
            </select>
        </div>
    </div>

    {{-- ============ ROOM GRID ============ --}}
    @php
        $rooms = $rooms ?? [
            ['id'=>1,'num'=>101,'name'=>'Solo Room','price'=>600,'status'=>'Occupied','img'=>'https://images.unsplash.com/photo-1598928506311-c55ded91a20c?auto=format&fit=crop&w=600&q=80','pax'=>'1-2 pax','beds'=>'1 bed','bath'=>'1 bath','floor'=>1,'desc'=>'Cozy solo room with modern finishes, private CR and city views.','estDate'=>'March 25, 2026','estApprox'=>'Approx. 2 days'],
            ['id'=>2,'num'=>102,'name'=>'Solo Room','price'=>950,'status'=>'Available','img'=>'https://images.unsplash.com/photo-1566665797739-1674de7a421a?auto=format&fit=crop&w=600&q=80','pax'=>'1-2 pax','beds'=>'1 bed','bath'=>'1 bath','floor'=>2,'desc'=>'Spacious solo room with aircon and private CR.','estDate'=>null,'estApprox'=>null],
            ['id'=>3,'num'=>103,'name'=>'Barkada Room','price'=>250,'status'=>'Available','img'=>'https://images.unsplash.com/photo-1555854877-bab0e564b8d5?auto=format&fit=crop&w=600&q=80','pax'=>'4-6 pax','beds'=>'4 bed','bath'=>'1 bath','floor'=>1,'desc'=>'Great for friends and groups.','estDate'=>null,'estApprox'=>null],
            ['id'=>4,'num'=>104,'name'=>'Barkada Room','price'=>400,'status'=>'Reserved','img'=>'https://images.unsplash.com/photo-1595526114035-0d45ed16cfbf?auto=format&fit=crop&w=600&q=80','pax'=>'4-6 pax','beds'=>'4 bed','bath'=>'1 bath','floor'=>1,'desc'=>'Premium group room with aircon.','estDate'=>null,'estApprox'=>null],
            ['id'=>5,'num'=>105,'name'=>'Bedspace','price'=>200,'status'=>'Available','img'=>'https://images.unsplash.com/photo-1522771739844-6a9f6d5f14af?auto=format&fit=crop&w=600&q=80','pax'=>'8 pax','beds'=>'1 bed','bath'=>'Shared CR','floor'=>1,'desc'=>'Affordable bedspace with locker.','estDate'=>null,'estApprox'=>null],
            ['id'=>6,'num'=>106,'name'=>'Bedspace','price'=>300,'status'=>'Occupied','img'=>'https://images.unsplash.com/photo-1616486338812-3dadae4b4ace?auto=format&fit=crop&w=600&q=80','pax'=>'8 pax','beds'=>'1 bed','bath'=>'Shared CR','floor'=>1,'desc'=>'Premium bedspace with locker.','estDate'=>'April 5, 2026','estApprox'=>'Approx. 5 days'],
            ['id'=>7,'num'=>107,'name'=>'Bedspace - All Girls','price'=>300,'status'=>'Available','img'=>'https://images.unsplash.com/photo-1540518614846-7ede433c5163?auto=format&fit=crop&w=600&q=80','pax'=>'10 pax','beds'=>'1 bed','bath'=>'Shared CR','floor'=>2,'desc'=>'Exclusive dorm for female tenants.','estDate'=>null,'estApprox'=>null],
            ['id'=>8,'num'=>108,'name'=>'Bedspace - All Boys','price'=>300,'status'=>'Available','img'=>'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=600&q=80','pax'=>'8 pax','beds'=>'1 bed','bath'=>'Shared CR','floor'=>2,'desc'=>'Exclusive dorm for male tenants.','estDate'=>null,'estApprox'=>null],
        ];
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-8">
        @foreach($rooms as $room)
            @php
                // Encode full room data for the modal
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

                    <span class="absolute top-3 right-3 bg-black/60 text-white text-[10px] font-semibold px-2.5 py-1 rounded-full backdrop-blur">
                        360° Tour
                    </span>

                    <span class="absolute bottom-3 left-3 bg-blue-50 text-blue-600 text-[10px] font-extrabold uppercase px-2.5 py-1 rounded-md">
                        ROOM {{ $room['num'] }}
                    </span>
                </div>

                <div class="p-4 flex flex-col gap-2.5 flex-1">
                    <div class="flex justify-between items-center">
                        <h4 class="text-[15px] font-bold">{{ $room['name'] }}</h4>
                        <div class="text-sm font-extrabold text-emerald-500">
                            ₱{{ $room['price'] }}<span class="text-[11px] font-normal text-slate-500">/day</span>
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
                            class="open-room-details-btn bg-white border border-slate-200 rounded-lg py-2 text-xs font-semibold inline-flex items-center justify-center gap-1.5"
                            data-room='{{ $roomJson }}'
                        >
                            <i class="fa-regular fa-eye"></i> Details
                        </button>

                        @if($room['status'] === 'Available')
                            <button
                                type="button"
                                class="open-reserve-modal-btn bg-[#02122c] text-white rounded-lg py-2 text-xs font-semibold inline-flex items-center justify-center gap-1.5"
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
</div>
@endsection