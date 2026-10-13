@extends('layouts.tenant', ['active' => 'reservations', 'pageTitle' => 'Reservations'])
@section('title', 'Reservations')

@section('content')
<div class="p-4 md:p-8 max-w-6xl mx-auto">

    <div class="flex items-start gap-4 mb-5">
        <div class="w-10 h-10 rounded-lg bg-emerald-100 text-emerald-500 flex items-center justify-center text-lg">
            <i class="fa-regular fa-calendar-plus"></i>
        </div>
        <div>
            <h2 class="text-xl font-bold">Reserve a Room</h2>
            <p class="text-[13px] text-slate-500">Browse available rooms and submit a reservation request</p>
        </div>
    </div>

    <div class="bg-blue-50 rounded-xl px-5 py-4 flex items-start gap-3 mb-7">
        <i class="fa-solid fa-circle-info text-blue-500 mt-0.5"></i>
        <p class="text-[13px] text-blue-800 leading-relaxed">
            <strong>How Reservations Work</strong><br>
            Browse available rooms below, view the 360° virtual tour, then submit your reservation details.
            <strong>Your reservation will be reviewed by the admin. You'll be notified once approved.</strong>
        </p>
    </div>

    <div class="flex justify-between items-center mb-5 flex-wrap gap-3">
        <h3 class="text-lg font-bold">Available Rooms</h3>
        <div class="flex gap-3 flex-wrap">
            <select class="bg-white border border-slate-200 rounded-lg px-4 py-2 text-xs"><option>All Status</option></select>
            <select class="bg-white border border-slate-200 rounded-lg px-4 py-2 text-xs"><option>Select Capacity</option></select>
            <select class="bg-white border border-slate-200 rounded-lg px-4 py-2 text-xs"><option>Select Room Type</option></select>
        </div>
    </div>

    {{-- Room grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-8">

        @foreach([
            ['num' => 101, 'name' => 'Solo Room - Standard', 'price' => 600, 'status' => 'occupied', 'img' => 'https://images.unsplash.com/photo-1598928506311-c55ded91a20c?auto=format&fit=crop&w=600&q=80', 'tags' => ['Fan','Private CR','TV','+1'], 'pax' => '1-2 pax', 'beds' => '1 bed', 'bath' => '1 bath'],
            ['num' => 102, 'name' => 'Solo Room - Deluxe', 'price' => 950, 'status' => 'available', 'img' => 'https://images.unsplash.com/photo-1566665797739-1674de7a421a?auto=format&fit=crop&w=600&q=80', 'tags' => ['Aircon','Fan','Private CR','+2'], 'pax' => '1-2 pax', 'beds' => '1 bed', 'bath' => '1 bath'],
            ['num' => 103, 'name' => 'Barkada Room - Standard', 'price' => 250, 'status' => 'available', 'img' => 'https://images.unsplash.com/photo-1555854877-bab0e564b8d5?auto=format&fit=crop&w=600&q=80', 'tags' => ['Fan','TV','CR','+1'], 'pax' => '4-6 pax', 'beds' => '4 bed', 'bath' => '1 bath'],
            ['num' => 104, 'name' => 'Barkada Room - Premium', 'price' => 400, 'status' => 'reserved', 'img' => 'https://images.unsplash.com/photo-1595526114035-0d45ed16cfbf?auto=format&fit=crop&w=600&q=80', 'tags' => ['Aircon','Fan','TV','+4'], 'pax' => '4-6 pax', 'beds' => '4 bed', 'bath' => '1 bath'],
        ] as $room)
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden flex flex-col shadow-sm">
            <div class="relative h-52">
                <img src="{{ $room['img'] }}" alt="Room {{ $room['num'] }}" class="w-full h-full object-cover">
                <span class="absolute top-3 left-3 text-[11px] font-bold px-3 py-1 rounded-full
                    {{ $room['status'] === 'occupied' ? 'bg-rose-200 text-rose-700' : '' }}
                    {{ $room['status'] === 'available' ? 'bg-green-100 text-green-700' : '' }}
                    {{ $room['status'] === 'reserved' ? 'bg-amber-100 text-amber-700' : '' }}">
                    {{ ucfirst($room['status']) }}
                </span>
                <span class="absolute top-3 right-3 bg-black/60 text-white text-[10px] font-semibold px-2.5 py-1 rounded-full backdrop-blur">360° Tour</span>
                <span class="absolute bottom-3 left-3 bg-blue-50 text-blue-600 text-[10px] font-extrabold uppercase px-2.5 py-1 rounded-md">ROOM {{ $room['num'] }}</span>
            </div>
            <div class="p-4 flex flex-col gap-2.5 flex-1">
                <div class="flex justify-between items-center">
                    <h4 class="text-[15px] font-bold">{{ $room['name'] }}</h4>
                    <div class="text-sm font-extrabold text-emerald-500">₱{{ $room['price'] }}<span class="text-[11px] font-normal text-slate-500">/day</span></div>
                </div>
                <div class="flex gap-3.5 text-[11px] text-slate-500">
                    <span class="flex items-center gap-1"><i class="fa-solid fa-users"></i> {{ $room['pax'] }}</span>
                    <span class="flex items-center gap-1"><i class="fa-solid fa-bed"></i> {{ $room['beds'] }}</span>
                    <span class="flex items-center gap-1"><i class="fa-solid fa-bath"></i> {{ $room['bath'] }}</span>
                </div>
                <div class="flex flex-wrap gap-1.5">
                    @foreach($room['tags'] as $t)
                        <span class="bg-slate-100 text-slate-500 text-[10px] font-medium px-2 py-0.5 rounded">{{ $t }}</span>
                    @endforeach
                </div>
                <div class="grid grid-cols-2 gap-2.5 mt-1.5">
                    <button class="bg-white border border-slate-200 rounded-lg py-2 text-xs font-semibold inline-flex items-center justify-center gap-1.5">
                        <i class="fa-regular fa-eye"></i> Details
                    </button>
                    @if($room['status'] === 'available')
                        <button class="bg-[#02122c] text-white rounded-lg py-2 text-xs font-semibold inline-flex items-center justify-center gap-1.5">
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