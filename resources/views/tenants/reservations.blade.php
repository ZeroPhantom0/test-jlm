@extends('layouts.tenant', ['active' => 'reservations', 'pageTitle' => 'Reservations'])
@section('title', 'Reservations')

@section('content')
    <div class="p-4 md:p-8 max-w-6xl mx-auto">

        {{-- ============ BANNER ============ --}}
        <div class="flex items-start gap-4 mb-5">
            <div class="w-10 h-10 rounded-lg bg-emerald-100 text-emerald-500 flex items-center justify-center text-lg">
                <i class="fa-regular fa-calendar-plus"></i>
            </div>
            <div class="flex-1">
                <h2 class="text-xl font-bold">Reserve a Room</h2>
                <p class="text-[13px] text-slate-500">Browse rooms with virtual tours and submit a reservation request</p>
            </div>
        </div>

        {{-- ============ HOW IT WORKS ============ --}}
        <div class="bg-blue-50 rounded-xl px-5 py-4 flex items-start gap-3 mb-7">
            <i class="fa-solid fa-circle-info text-blue-500 mt-0.5"></i>
            <p class="text-[13px] text-blue-800 leading-relaxed">
                <strong>How Reservations Work</strong><br>
                Only rooms with a <strong>published virtual tour</strong> are shown below. View the tour, then submit your
                reservation with your GCash payment proof.
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

            <div id="my-reservations-list">
                @forelse($myReservations as $res)
                    <div
                        class="px-6 py-4 flex items-center justify-between flex-wrap gap-3 border-b border-slate-200 last:border-b-0">
                        <div class="flex flex-col gap-1.5">
                            <div class="flex items-center gap-3 flex-wrap">
                                <h4 class="text-[14.5px] font-bold text-slate-900">{{ $res['room'] }}</h4>
                                <span
                                    class="text-[11px] font-bold px-2.5 py-[3px] rounded-full inline-flex items-center gap-1 {{ $res['statusClass'] }}">
                                    <i class="{{ $res['statusIcon'] }}"></i> {{ $res['status'] }}
                                </span>
                            </div>
                            <div class="text-[12px] text-slate-500">
                                Move-in: {{ $res['move_in'] }} · Submitted: {{ $res['submitted'] }}
                            </div>
                            @if (!empty($res['note']))
                                <div
                                    class="bg-green-50 text-green-800 text-[11px] px-2.5 py-1 rounded-md inline-block mt-1 font-medium">
                                    {{ $res['note'] }}
                                </div>
                            @endif
                        </div>
                        <div class="text-right flex flex-col items-end gap-0.5">
                            <div class="text-base font-extrabold text-emerald-500">₱{{ $res['deposit'] }}</div>
                            <div class="text-[11px] text-slate-500">deposit</div>
                            @if ($res['status'] === 'Pending')
                                <button type="button"
                                    class="cancel-reservation-btn text-[11px] font-semibold text-rose-500 hover:text-rose-700 mt-1"
                                    data-res-key="{{ $res['key'] }}">
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
        </div>

        {{-- ============ AVAILABLE ROOMS ============ --}}
        <div class="flex justify-between items-center mb-5 flex-wrap gap-3">
            <h3 class="text-lg font-bold">Available Rooms</h3>
            <div class="text-[12px] text-slate-500" id="room-count-text">
                {{ $rooms->count() }} room{{ $rooms->count() === 1 ? '' : 's' }} available
            </div>
        </div>

        @if ($rooms->isEmpty())
            <div id="rooms-empty-state"
                class="bg-white border border-dashed border-slate-200 rounded-2xl p-12 text-center text-slate-400 mb-8">
                <i class="fa-solid fa-camera text-4xl mb-3 block opacity-40"></i>
                <p class="text-sm font-medium text-slate-600">No rooms with virtual tours yet.</p>
                <p class="text-xs mt-1">The admin needs to publish at least one virtual tour.</p>
            </div>
            <div id="available-rooms-grid" class="flex flex-col gap-8 mb-8 hidden"></div>
        @else
            @php
                $fromPath = '/' . ltrim(request()->path(), '/');
            @endphp

            @php
                // Group like the landing page: Solo Rooms → Barkada Rooms → Bedspace
                $roomCategory = function ($type) {
                    $type = (string) $type;
                    return match (true) {
                        str_contains($type, 'Solo')     => 'Solo Rooms',
                        str_contains($type, 'Barkada')  => 'Barkada Rooms',
                        str_contains($type, 'Bedspace') => 'Bedspace',
                        default                         => 'Other Rooms',
                    };
                };
                $categoryOrder = ['Solo Rooms', 'Barkada Rooms', 'Bedspace', 'Other Rooms'];
                $roomGroups = collect($rooms)
                    ->groupBy(fn ($r) => $roomCategory($r['name']))
                    ->sortBy(fn ($group, $key) => array_search($key, $categoryOrder));
            @endphp

            <div class="flex flex-col gap-8 mb-8" id="available-rooms-grid">
                @foreach ($roomGroups as $category => $groupRooms)
                <div class="room-category-group">
                    <div class="flex items-center gap-4 mb-4">
                        <h3 class="text-[15px] font-bold text-slate-900 whitespace-nowrap">{{ $category }}</h3>
                        <div class="h-px flex-1 bg-slate-200"></div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                @foreach ($groupRooms as $room)
                    @php
                        $roomData = [
                            'id' => $room['id'],
                            'name' => $room['name'],
                            'roomNumber' => 'ROOM ' . $room['num'],
                            'floor' => $room['floor'],
                            'status' => $room['status'],
                            'is_bookable' => $room['is_bookable'],
                            'has_reserved' => $room['has_reserved'],
                            'is_current_room' => $room['is_current_room'],
                            'empty_beds' => $room['empty_beds'],
                            'occupied_beds' => $room['occupied_beds'], 
                            'tenants' => $room['tenants'], 
                            'price' => $room['price'],
                            'img' => $room['img'],
                            'pax' => $room['pax'],
                            'beds' => $room['beds'],
                            'total_beds' => $room['total_beds'],
                            'bath' => $room['bath'],
                            'description' => $room['desc'],
                            'estDate' => $room['estDate'],
                            'estApprox' => $room['estApprox'],
                            'tourId' => $room['tourId'],
                            'bed_slots' => $room['bed_slots'],
                            'is_female_only' => $room['is_female_only'],
                            'is_male_only' => $room['is_male_only'],
                        ];

                        $tourUrl = route('tour.viewer', [
                            'virtualTour' => $room['tourId'],
                            'from' => $fromPath,
                        ]);

                        $displayStatus = $room['status'];
                    @endphp

                    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden flex flex-col shadow-sm">
                        <a href="{{ $tourUrl }}" aria-label="Open 360° tour for {{ $room['name'] }}"
                            class="relative h-52 block group cursor-pointer">
                            <img src="{{ $room['img'] }}" alt="Room {{ $room['num'] }}"
                                class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">

                            {{-- Show actual status only (removed "Reserved by You") --}}
                            <span
                                class="absolute top-3 left-3 text-[11px] font-bold px-3 py-1 rounded-full
                            {{ $displayStatus === 'Occupied' ? 'bg-rose-200 text-rose-700' : '' }}
                            {{ $displayStatus === 'Available' ? 'bg-green-100 text-green-700' : '' }}
                            {{ $displayStatus === 'Reserved' ? 'bg-amber-100 text-amber-700' : '' }}">
                                {{ $displayStatus }}
                            </span>

                            <span
                                class="absolute top-3 right-3 bg-black/60 text-white text-[10px] font-semibold px-2.5 py-1 rounded-full backdrop-blur opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none">
                                <i class="fa-solid fa-camera mr-1"></i> View 360° Tour
                            </span>

                            <span
                                class="absolute bottom-3 left-3 bg-blue-50 text-blue-600 text-[10px] font-extrabold uppercase px-2.5 py-1 rounded-md">
                                ROOM {{ $room['num'] }}
                            </span>
                        </a>

                        <div class="p-4 flex flex-col gap-2.5 flex-1">
                            <div class="flex justify-between items-center">
                                <h4 class="text-[15px] font-bold">{{ $room['name'] }}</h4>
                                <div class="text-sm font-extrabold text-emerald-500">
                                    ₱{{ number_format($room['price'], 0) }}<span
                                        class="text-[11px] font-normal text-slate-500">/day</span>
                                </div>
                            </div>

                            <div class="flex gap-3.5 text-[11px] text-slate-500">
                                <span class="flex items-center gap-1"><i class="fa-solid fa-users"></i>
                                    {{ $room['pax'] }}</span>
                                <span class="flex items-center gap-1"><i class="fa-solid fa-bed"></i>
                                    {{ $room['beds'] }}</span>
                            </div>

                            @if ($displayStatus === 'Occupied')
                                <div class="bg-rose-50 border border-rose-100 rounded-lg px-3 py-2 text-[11px] text-rose-700">
                                    <i class="fa-regular fa-clock mr-1"></i> Estimated availability:
                                    <strong>{{ $room['estDate'] ?? 'To be announced' }}</strong>
                                    @if ($room['estApprox'])
                                        <span class="text-rose-500">· {{ $room['estApprox'] }}</span>
                                    @endif
                                </div>
                            @elseif ($displayStatus === 'Reserved' && $room['nextBooking'])
                                <div class="bg-amber-50 border border-amber-100 rounded-lg px-3 py-2 text-[11px] text-amber-700">
                                    <i class="fa-regular fa-calendar mr-1"></i> Booked:
                                    <strong>{{ $room['nextBooking'] }}</strong>
                                    <span class="text-amber-600">· other dates available</span>
                                </div>
                            @endif

                            <div class="grid grid-cols-2 gap-2.5 mt-1.5">
                                <button type="button"
                                    class="open-room-details-btn bg-white border border-slate-200 rounded-lg py-2 text-xs font-semibold inline-flex items-center justify-center gap-1.5 hover:bg-slate-50 transition"
                                    data-room="{{ json_encode($roomData) }}">
                                    <i class="fa-regular fa-eye"></i> Details
                                </button>

                                @if ($room['is_current_room'])
                                    <button disabled
                                        class="bg-blue-100 text-blue-700 border border-blue-200 rounded-lg py-2 text-xs font-semibold cursor-not-allowed text-center inline-flex items-center justify-center gap-1.5">
                                        <i class="fa-solid fa-house-user"></i> Current Room
                                    </button>
                                @elseif ($room['has_reserved'])
                                    <button disabled
                                        class="bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-lg py-2 text-xs font-semibold cursor-not-allowed text-center inline-flex items-center justify-center gap-1.5">
                                        <i class="fa-solid fa-check"></i> Reserved
                                    </button>
                                @elseif ($room['is_bookable'])
                                    <button type="button"
                                        class="open-reserve-modal-btn bg-[#02122c] hover:bg-[#0d2347] text-white rounded-lg py-2 text-xs font-semibold inline-flex items-center justify-center gap-1.5 transition"
                                        data-room="{{ json_encode($roomData) }}">
                                        <i class="fa-regular fa-calendar-check"></i> Reserve
                                    </button>
                                @else
                                    <button disabled
                                        class="bg-slate-100 text-slate-400 rounded-lg py-2 text-xs font-semibold cursor-not-allowed text-center inline-flex items-center justify-center gap-1.5">
                                        <i class="fa-solid fa-ban"></i> {{ $displayStatus }}
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>

    <div id="tenant-reservations-modals" data-user-name="{{ auth()->user()?->name }}" data-reservations='@json($reservations ?? [])'></div>
@endsection

@push('scripts')
    <script>
        (function() {
            // Wait for Echo to be available before trying to subscribe
            function initEcho() {
                if (!window.Echo) {
                    setTimeout(initEcho, 50);
                    return;
                }

                console.log('[Live] Echo found. Subscribing to tenant.reservations...');

                window.Echo.private('tenant.reservations')
                    .listen('.reservation.updated', (e) => {
                        const modalEl = document.getElementById('tenant-reservations-modals');
                        if (modalEl && modalEl.dataset.saving === 'true') return;

                        console.log('[Live] Event received:', e.action);

                        setTimeout(() => {
                            refreshReservations();
                        }, 500);
                    });
            }

            initEcho();

            function refreshReservations() {
                console.log('[Live] Fetching new reservation and room data...');

                // Adding a timestamp prevents the browser from caching the GET request
                fetch(`/tenants/reservations/data?t=${new Date().getTime()}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'Cache-Control': 'no-cache, no-store, must-revalidate'
                        },
                        cache: 'no-store'
                    })
                    .then(r => r.ok ? r.json() : null)
                    .then(data => {
                        if (!data) return;

                        console.log('[Live] Data received:', data);

                        if (data.myReservations) {
                            renderReservationCards(data.myReservations);
                        }

                        // Re-render Rooms in real-time
                        if (data.rooms) {
                            console.log('[Live] Re-rendering rooms...');
                            renderRoomCards(data.rooms);
                        }

                        const el = document.getElementById('tenant-reservations-modals');
                        if (el && data.reservations) {
                            el.dataset.reservations = JSON.stringify(data.reservations);
                            window.dispatchEvent(new CustomEvent('reservation:data-updated', {
                                detail: data.reservations,
                            }));
                        }
                    })
                    .catch(console.error);
            }

            function renderReservationCards(list) {
                const container = document.getElementById('my-reservations-list');
                if (!container) return;

                if (!list || !list.length) {
                    container.innerHTML = `
                <div class="px-6 py-8 text-center text-[13px] text-slate-400">
                    <i class="fa-regular fa-calendar-check text-3xl mb-2 block opacity-40"></i>
                    No reservations yet.
                </div>`;
                    return;
                }

                container.innerHTML = list.map(r => `
            <div class="px-6 py-4 flex items-center justify-between flex-wrap gap-3 border-b border-slate-200 last:border-b-0">
                <div class="flex flex-col gap-1.5">
                    <div class="flex items-center gap-3 flex-wrap">
                        <h4 class="text-[14.5px] font-bold text-slate-900">${r.room}</h4>
                        <span class="text-[11px] font-bold px-2.5 py-[3px] rounded-full inline-flex items-center gap-1 ${r.statusClass}">
                            <i class="${r.statusIcon}"></i> ${r.status}
                        </span>
                    </div>
                    <div class="text-[12px] text-slate-500">
                        Move-in: ${r.move_in} · Submitted: ${r.submitted}
                    </div>
                    ${r.note ? `<div class="bg-green-50 text-green-800 text-[11px] px-2.5 py-1 rounded-md inline-block mt-1 font-medium">${r.note}</div>` : ''}
                </div>
                <div class="text-right flex flex-col items-end gap-0.5">
                    <div class="text-base font-extrabold text-emerald-500">₱${r.deposit}</div>
                    <div class="text-[11px] text-slate-500">deposit</div>
                    ${r.status === 'Pending' ? `
                                <button type="button"
                                    class="cancel-reservation-btn text-[11px] font-semibold text-rose-500 hover:text-rose-700 mt-1"
                                    data-res-key="${r.key}">Cancel</button>` : ''}
                </div>
            </div>
        `).join('');
            }

            // Re-renders the Available Rooms Grid
            function renderRoomCards(rooms) {
                const grid = document.getElementById('available-rooms-grid');
                const emptyState = document.getElementById('rooms-empty-state');
                const countText = document.getElementById('room-count-text');

                if (!grid) return;

                // Update count text
                if (countText) {
                    countText.textContent = `${rooms.length} room${rooms.length === 1 ? '' : 's'} available`;
                }

                if (!rooms || rooms.length === 0) {
                    if (emptyState) emptyState.classList.remove('hidden');
                    grid.classList.add('hidden');
                    grid.innerHTML = '';
                    return;
                }

                if (emptyState) emptyState.classList.add('hidden');
                grid.classList.remove('hidden');

                const currentPath = window.location.pathname;
                const fromPath = currentPath.startsWith('/') ? currentPath : '/' + currentPath;

                const renderCard = (room) => {
                    const roomData = {
                        id: room.id,
                        name: room.name,
                        roomNumber: 'ROOM ' + room.num,
                        floor: room.floor,
                        status: room.status,
                        is_bookable: room.is_bookable,
                        has_reserved: room.has_reserved,
                        is_current_room: room.is_current_room,
                        empty_beds: room.empty_beds,
                        occupied_beds: room.occupied_beds,
                        tenants: room.tenants,
                        price: room.price,
                        img: room.img,
                        pax: room.pax,
                        beds: room.beds,
                        total_beds: room.total_beds,
                        bath: room.bath,
                        description: room.desc,
                        estDate: room.estDate,
                        estApprox: room.estApprox,
                        tourId: room.tourId,
                        bed_slots: room.bed_slots,
                        is_female_only: room.is_female_only,
                        is_male_only: room.is_male_only
                    };

                    const tourUrl = `/tour/${room.tourId}?from=${encodeURIComponent(fromPath)}`;

                    let displayStatus = room.status;

                    // Status badges styling — show actual status only
                    let statusBadge = '';
                    let badgeClass = '';
                    if (displayStatus === 'Occupied') badgeClass = 'bg-rose-200 text-rose-700';
                    if (displayStatus === 'Available') badgeClass = 'bg-green-100 text-green-700';
                    if (displayStatus === 'Reserved') badgeClass = 'bg-amber-100 text-amber-700';
                    statusBadge =
                        `<span class="absolute top-3 left-3 text-[11px] font-bold px-3 py-1 rounded-full ${badgeClass}">${displayStatus}</span>`;

                    // Action button logic
                    let actionBtn = '';
                    if (room.is_current_room) {
                        actionBtn = `
                    <button disabled class="bg-blue-100 text-blue-700 border border-blue-200 rounded-lg py-2 text-xs font-semibold cursor-not-allowed text-center inline-flex items-center justify-center gap-1.5">
                        <i class="fa-solid fa-house-user"></i> Current Room
                    </button>`;
                    } else if (room.has_reserved) {
                        actionBtn = `
                    <button disabled class="bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-lg py-2 text-xs font-semibold cursor-not-allowed text-center inline-flex items-center justify-center gap-1.5">
                        <i class="fa-solid fa-check"></i> Reserved
                    </button>`;
                    } else if (room.is_bookable) {
                        actionBtn = `
                    <button type="button" class="open-reserve-modal-btn bg-[#02122c] hover:bg-[#0d2347] text-white rounded-lg py-2 text-xs font-semibold inline-flex items-center justify-center gap-1.5 transition" data-room='${JSON.stringify(roomData)}'>
                        <i class="fa-regular fa-calendar-check"></i> Reserve
                    </button>`;
                    } else {
                        actionBtn = `
                    <button disabled class="bg-slate-100 text-slate-400 rounded-lg py-2 text-xs font-semibold cursor-not-allowed text-center inline-flex items-center justify-center gap-1.5">
                        <i class="fa-solid fa-ban"></i> ${room.status}
                    </button>`;
                    }

                    return `
                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden flex flex-col shadow-sm">
                    <a href="${tourUrl}" aria-label="Open 360° tour for ${room.name}" class="relative h-52 block group cursor-pointer">
                        <img src="${room.img}" alt="Room ${room.num}" class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">
                        ${statusBadge}
                        <span class="absolute top-3 right-3 bg-black/60 text-white text-[10px] font-semibold px-2.5 py-1 rounded-full backdrop-blur opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none">
                            <i class="fa-solid fa-camera mr-1"></i> View 360° Tour
                        </span>
                        <span class="absolute bottom-3 left-3 bg-blue-50 text-blue-600 text-[10px] font-extrabold uppercase px-2.5 py-1 rounded-md">
                            ROOM ${room.num}
                        </span>
                    </a>
                    <div class="p-4 flex flex-col gap-2.5 flex-1">
                        <div class="flex justify-between items-center">
                            <h4 class="text-[15px] font-bold">${room.name}</h4>
                            <div class="text-sm font-extrabold text-emerald-500">
                                ₱${Number(room.price).toLocaleString()}<span class="text-[11px] font-normal text-slate-500">/day</span>
                            </div>
                        </div>
                        <div class="flex gap-3.5 text-[11px] text-slate-500">
                            <span class="flex items-center gap-1"><i class="fa-solid fa-users"></i> ${room.pax}</span>
                            <span class="flex items-center gap-1"><i class="fa-solid fa-bed"></i> ${room.beds}</span>
                        </div>
                        ${room.status === 'Occupied' ? `
                        <div class="bg-rose-50 border border-rose-100 rounded-lg px-3 py-2 text-[11px] text-rose-700">
                            <i class="fa-regular fa-clock mr-1"></i> Estimated availability:
                            <strong>${room.estDate || 'To be announced'}</strong>
                            ${room.estApprox ? `<span class="text-rose-500">· ${room.estApprox}</span>` : ''}
                        </div>` : (room.status === 'Reserved' && room.nextBooking ? `
                        <div class="bg-amber-50 border border-amber-100 rounded-lg px-3 py-2 text-[11px] text-amber-700">
                            <i class="fa-regular fa-calendar mr-1"></i> Booked:
                            <strong>${room.nextBooking}</strong>
                            <span class="text-amber-600">· other dates available</span>
                        </div>` : '')}
                        <div class="grid grid-cols-2 gap-2.5 mt-1.5">
                            <button type="button" class="open-room-details-btn bg-white border border-slate-200 rounded-lg py-2 text-xs font-semibold inline-flex items-center justify-center gap-1.5 hover:bg-slate-50 transition" data-room='${JSON.stringify(roomData)}'>
                                <i class="fa-regular fa-eye"></i> Details
                            </button>
                            ${actionBtn}
                        </div>
                    </div>
                </div>
            `;
                };

                // Group like the landing page: Solo Rooms → Barkada Rooms → Bedspace
                const categoryOf = (type) => {
                    type = String(type || '');
                    if (type.includes('Solo')) return 'Solo Rooms';
                    if (type.includes('Barkada')) return 'Barkada Rooms';
                    if (type.includes('Bedspace')) return 'Bedspace';
                    return 'Other Rooms';
                };
                const order = ['Solo Rooms', 'Barkada Rooms', 'Bedspace', 'Other Rooms'];
                const groups = {};
                rooms.forEach(room => {
                    (groups[categoryOf(room.name)] ||= []).push(room);
                });

                grid.innerHTML = order
                    .filter(cat => groups[cat]?.length)
                    .map(cat => `
                <div class="room-category-group">
                    <div class="flex items-center gap-4 mb-4">
                        <h3 class="text-[15px] font-bold text-slate-900 whitespace-nowrap">${cat}</h3>
                        <div class="h-px flex-1 bg-slate-200"></div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        ${groups[cat].map(renderCard).join('')}
                    </div>
                </div>
            `).join('');
            }

            window.addEventListener("reservation:refresh", refreshReservations);
        })();
    </script>
@endpush
