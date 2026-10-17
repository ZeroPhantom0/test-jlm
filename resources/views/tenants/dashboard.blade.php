@extends('layouts.tenant', [
    'active'    => 'dashboard',
    'pageTitle' => 'Dashboard',
])

@section('title', 'Dashboard')

@section('content')
<div class="p-4 md:p-8 max-w-[1400px] mx-auto">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

        {{-- ============================================================ --}}
        {{-- LEFT COLUMN --}}
        {{-- ============================================================ --}}
        <div class="lg:col-span-2 space-y-5 min-w-0">

            {{-- Welcome --}}
            <div class="bg-[#111d33] text-white rounded-2xl p-6 flex items-center gap-4">
                <div class="w-12 h-12 rounded-full bg-[#1e2d4a] flex items-center justify-center text-[#16a34a] text-2xl shrink-0">
                    <i class="fa-regular fa-user"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-xs text-slate-400">Welcome back,</p>
                    <h2 class="text-lg font-bold truncate">{{ $user->name }}!</h2>
                </div>
            </div>

            {{-- Balance --}}
            <div class="bg-white rounded-xl p-5 border border-slate-100 flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-pink-100 flex items-center justify-center text-rose-600 text-xl font-bold shrink-0">₱</div>
                    <div>
                        <h3 class="text-2xl font-bold">₱{{ number_format($outstandingBalance, 0) }}</h3>
                        <p class="text-xs text-slate-500">Outstanding Balance</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-[11px] text-slate-400 mb-1.5">
                        {{ $unpaidCount }} unpaid invoice{{ $unpaidCount === 1 ? '' : 's' }}
                    </p>
                    <a href="/tenants/billing"
                       class="inline-flex items-center gap-2 bg-[#16a34a] text-white px-5 py-2.5 rounded-lg text-[13px] font-semibold hover:bg-green-700 transition">
                        <i class="fa-regular fa-credit-card"></i> Pay Now
                    </a>
                </div>
            </div>

            {{-- Room Information --}}
            <div class="bg-white rounded-xl p-5 border border-slate-100">
                <div class="flex items-center gap-2.5 text-base font-bold mb-4">
                    <i class="fa-solid fa-house text-[#16a34a]"></i>
                    <span>Room Information</span>
                </div>

                @if ($room)
                    <div class="bg-slate-50 rounded-xl p-4 flex items-center gap-4 mb-4">
                        <div class="bg-slate-900 text-white px-5 py-3 rounded-lg text-lg font-bold shrink-0">
                            {{ $room->room_number }}
                        </div>
                        <div class="min-w-0">
                            <h4 class="text-[15px] font-bold">Room {{ $room->room_number }}</h4>
                            <p class="text-xs text-slate-500 mt-0.5">
                                {{ $room->room_type }}
                                @if ($room->room_bed_id)
                                    • {{ $room->room_bed_id }}
                                @endif
                                • Floor {{ $room->floor }}
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3">
                        <div class="bg-green-100 text-green-700 p-4 rounded-xl flex-1">
                            <span class="text-[11px] font-semibold">Daily Rent</span>
                            <h3 class="text-2xl font-extrabold leading-tight my-0.5">
                                ₱{{ number_format((float) $room->daily_rent, 0) }}
                            </h3>
                            <small class="text-[10px]">
                                Due on the {{ now()->day }}th
                            </small>
                        </div>
                        <div class="flex gap-3 sm:flex-[2] flex-1">
                            <div class="bg-slate-50 p-4 rounded-xl flex-1 min-w-0">
                                <div class="flex items-center gap-1.5 text-[11px] text-slate-500 mb-1.5">
                                    <i class="fa-regular fa-calendar text-blue-400"></i>
                                    <span>Move-in Date</span>
                                </div>
                                <div class="text-sm font-bold">
                                    {{ $tenant?->move_in_date?->format('M d, Y') ?? '—' }}
                                </div>
                            </div>
                            <div class="bg-slate-50 p-4 rounded-xl flex-1 min-w-0">
                                <div class="flex items-center gap-1.5 text-[11px] text-slate-500 mb-1.5">
                                    <i class="fa-regular fa-calendar text-red-400"></i>
                                    <span>Move-out Date</span>
                                </div>
                                <div class="text-sm font-bold">
                                    {{ $tenant?->move_out_date?->format('M d, Y') ?? '—' }}
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="bg-amber-50 border border-amber-100 rounded-xl p-4 text-[13px] text-amber-700 flex items-center gap-2.5">
                        <i class="fa-regular fa-circle-info"></i>
                        <span>You have not been assigned to a room yet. Please contact the admin.</span>
                    </div>
                @endif
            </div>

            {{-- Service Requests --}}
            <div class="bg-white rounded-xl p-5 border border-slate-100">
                <div class="flex items-center gap-2.5 text-base font-bold mb-4">
                    <i class="fa-solid fa-wrench text-amber-500"></i>
                    <span>Service Requests</span>
                </div>
                <a href="/tenants/services/maintenance"
                   class="flex items-center justify-between bg-yellow-50 border border-yellow-200 rounded-lg px-5 py-3.5 mb-4 hover:bg-yellow-100 transition">
                    <div>
                        <h5 class="text-[13px] font-bold text-orange-900">Submit a Request</h5>
                        <p class="text-[11px] text-orange-700">Report maintenance issues</p>
                    </div>
                    <i class="fa-solid fa-arrow-right text-orange-700"></i>
                </a>

                <div class="text-[11px] font-bold text-slate-500 mb-2 flex justify-between">
                    <span>ACTIVE REQUESTS:</span>
                    <span class="text-[#16a34a]">({{ $activeRequests->count() }})</span>
                </div>

                @forelse ($activeRequests as $req)
                    <div class="bg-slate-50 px-4 py-3 rounded-lg flex items-center justify-between text-xs font-semibold {{ $loop->last ? '' : 'mb-2' }}">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="w-2 h-2 rounded-full shrink-0
                                {{ $req->status === 'Pending' ? 'bg-amber-500' : 'bg-blue-500' }}"></span>
                            <span class="truncate">{{ $req->title }}</span>
                        </div>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold whitespace-nowrap ml-2
                            {{ $req->status === 'Pending' ? 'bg-amber-100 text-amber-700' : 'bg-blue-100 text-blue-700' }}">
                            {{ strtolower($req->status) }}
                        </span>
                    </div>
                @empty
                    <div class="text-center text-slate-400 text-xs py-4">
                        No active service requests.
                    </div>
                @endforelse
            </div>
        </div>

        {{-- ============================================================ --}}
        {{-- RIGHT COLUMN --}}
        {{-- ============================================================ --}}
        <div class="space-y-5 min-w-0">

            {{-- Calendar (Philippines Time) --}}
            <div class="bg-white rounded-xl p-5 border border-slate-100">
                <div class="flex items-center justify-between font-bold text-sm mb-4">
                    <i class="fa-solid fa-chevron-left text-slate-400 cursor-pointer"></i>
                    <span>{{ $monthLabel }}</span>
                    <i class="fa-solid fa-chevron-right text-slate-400 cursor-pointer"></i>
                </div>
                <div class="grid grid-cols-7 gap-1.5 text-xs text-center">
                    @foreach (['SUN','MON','TUE','WED','THU','FRI','SAT'] as $d)
                        <div class="text-[10px] font-bold text-slate-500 mb-2">{{ $d }}</div>
                    @endforeach

                    {{-- Empty slots before first day of the month --}}
                    @for ($i = 0; $i < $startWeekday; $i++)
                        <div class="aspect-square"></div>
                    @endfor

                    {{-- Days of the month --}}
                    @for ($day = 1; $day <= $daysInMonth; $day++)
                        @php $isToday = ($day === $todayDay); @endphp
                        <div class="aspect-square flex items-center justify-center rounded-full cursor-pointer
                                    {{ $isToday ? 'bg-[#e06800] text-white font-bold' : 'hover:bg-slate-100' }}">
                            {{ $day }}
                        </div>
                    @endfor
                </div>
            </div>

            {{-- Announcements --}}
            <div class="bg-white rounded-xl p-5 border border-slate-100">
                <div class="flex items-center gap-2.5 text-base font-bold mb-4">
                    <i class="fa-solid fa-bullhorn text-blue-500"></i>
                    <span>Announcements</span>
                    @if ($announcements->count() > 0)
                        <span class="bg-blue-500 text-white text-[10px] rounded-full w-4 h-4 flex items-center justify-center ml-0.5">
                            {{ $announcements->count() }}
                        </span>
                    @endif
                </div>

                @forelse ($announcements as $ann)
                    @php
                        $isPinned    = $ann->is_pinned;
                        $isImportant = $ann->priority === 'Important' || $ann->priority === 'Urgent';
                        $boxClass    = $isPinned ? 'bg-yellow-50 border border-yellow-200' :
                                       ($isImportant ? 'bg-orange-50 border border-orange-100' :
                                       'bg-slate-50');
                    @endphp
                    <div class="{{ $boxClass }} rounded-lg p-3.5 {{ $loop->last ? '' : 'mb-2.5' }}">
                        @if ($isPinned)
                            <span class="inline-block bg-indigo-100 text-indigo-700 text-[9px] font-bold px-1.5 py-0.5 rounded mb-1.5">
                                <i class="fa-solid fa-thumbtack"></i> PINNED
                            </span>
                        @endif
                        <h5 class="text-xs font-bold mb-1">{{ $ann->title }}</h5>
                        <p class="text-[11px] text-slate-500 leading-snug">
                            {{ \Illuminate\Support\Str::limit($ann->content, 120) }}
                        </p>
                    </div>
                @empty
                    <div class="text-center text-slate-400 text-xs py-4">
                        No announcements yet.
                    </div>
                @endforelse

                <a href="/tenants/announcements" class="block text-right text-[#16a34a] text-[11px] font-bold pt-1.5">
                    View all <i class="fa-solid fa-chevron-right text-[9px]"></i>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection