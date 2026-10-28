{{-- resources/views/admin/rooms.blade.php --}}
@extends('layouts.admin', ['active' => 'rooms', 'pageTitle' => 'Rooms'])
@section('title', 'Rooms')

@section('content')
<div class="p-4 md:p-8 max-w-[1400px] mx-auto space-y-5">

    {{-- Page Title & Add Button --}}
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div class="flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-yellow-100 text-yellow-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-city"></i>
            </div>
            <div>
                <h2 class="text-[22px] font-extrabold text-slate-900 leading-tight">Manage Rooms</h2>
                <p class="text-[13px] text-slate-500">Manage building rooms and occupancy</p>
            </div>
        </div>
        <button id="open-add-room-btn" class="bg-[#02122c] hover:bg-[#0d2347] text-white px-5 py-2.5 rounded-[10px] text-[13.5px] font-semibold inline-flex items-center gap-2 transition">
            <i class="fa-solid fa-plus"></i> Add New Room
        </button>
    </div>

    {{-- Metrics Grid --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl px-5 py-4 border border-slate-100 shadow-sm">
            <p class="text-[12px] text-slate-500 font-medium mb-1.5">Total Rooms</p>
            <div class="text-2xl font-extrabold text-slate-900">{{ $metrics['total'] }}</div>
        </div>
        <div class="bg-white rounded-2xl px-5 py-4 border border-slate-100 shadow-sm">
            <p class="text-[12px] text-slate-500 font-medium mb-1.5">Occupied</p>
            <div class="w-8 h-8 rounded-full bg-green-100 text-green-600 inline-flex items-center justify-center text-base font-bold">{{ $metrics['occupied'] }}</div>
        </div>
        <div class="bg-white rounded-2xl px-5 py-4 border border-slate-100 shadow-sm">
            <p class="text-[12px] text-slate-500 font-medium mb-1.5">Reserved</p>
            <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 inline-flex items-center justify-center text-base font-bold">{{ $metrics['reserved'] }}</div>
        </div>
        <div class="bg-white rounded-2xl px-5 py-4 border border-slate-100 shadow-sm">
            <p class="text-[12px] text-slate-500 font-medium mb-1.5">Available</p>
            <div class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 inline-flex items-center justify-center text-base font-bold">{{ $metrics['available'] }}</div>
        </div>
    </div>

    {{-- Table Card --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
        <div class="flex flex-col md:flex-row gap-3.5 mb-5">
            <div class="flex-1 bg-white border border-slate-300 rounded-[10px] px-3.5 py-1.5 flex items-center gap-2.5">
                <i class="fa-solid fa-magnifying-glass text-slate-400 text-[13px]"></i>
                <input type="text" id="room-search" placeholder="Search room..." class="w-full text-[13px] outline-none border-none bg-transparent">
            </div>
            <select id="room-filter-status" class="bg-white border border-slate-300 rounded-[10px] px-3.5 py-1.5 text-[13px] text-slate-700 outline-none cursor-pointer min-w-[160px]">
                <option value="">All Statuses</option>
                <option>Occupied</option>
                <option>Available</option>
                <option>Reserved</option>
            </select>
            <select id="room-filter-type" class="bg-white border border-slate-300 rounded-[10px] px-3.5 py-1.5 text-[13px] text-slate-700 outline-none cursor-pointer min-w-[160px]">
                <option value="">All Types</option>
                <option>Solo Room</option>
                <option>Bedspace</option>
                <option>Barkada Room</option>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-[11px] uppercase tracking-wider font-bold text-slate-400 border-b border-slate-200">
                        <th class="py-3 px-4">Room</th>
                        <th class="py-3 px-4">Type</th>
                        <th class="py-3 px-4">Occupancy</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Rent Rate</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="rooms-tbody">
                    @forelse ($rooms as $room)
                        <tr class="border-b border-slate-100 last:border-0 hover:bg-slate-50/50 transition-colors"
                            data-room='@json($room->toRowArray())'
                            data-search="{{ strtolower($room->room_number . ' ' . $room->room_type) }}"
                            data-status="{{ $room->status }}"
                            data-type="{{ $room->room_type }}">
                            <td class="py-3.5 px-4">
                                <div class="text-[14px] font-semibold text-slate-900 tracking-tight">Room {{ $room->room_number }}</div>
                                <div class="text-[11.5px] text-slate-500 mt-0.5 font-medium">Floor {{ $room->floor }} &middot; {{ $room->room_bed_id ?? 'R'.$room->room_number }}</div>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="inline-block px-2.5 py-1 rounded-md text-[10.5px] font-bold tracking-wide uppercase {{ $room->type_color }}">
                                    {{ $room->room_type }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="inline-flex items-center gap-1.5 bg-slate-100 px-2.5 py-1 rounded-full text-[11.5px] font-semibold text-slate-700 tracking-tight">
                                    <i class="fa-regular fa-user"></i> {{ $room->occupancy_label }}
                                </div>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11.5px] font-bold {{ $room->status_color }}">
                                    <i class="fa-solid {{ $room->status_icon }}"></i> {{ $room->status }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-[13.5px] font-bold text-slate-900 tracking-tight">
                                ₱{{ number_format($room->daily_rent, 0) }}
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="flex items-center justify-end gap-3.5 text-slate-400">
                                    <i class="fa-regular fa-eye cursor-pointer text-[14px] hover:text-slate-900 transition-colors view-room-btn" data-id="{{ $room->id }}" title="View"></i>
                                    <i class="fa-regular fa-pen-to-square cursor-pointer text-[14px] hover:text-slate-900 transition-colors edit-room-btn" data-id="{{ $room->id }}" title="Edit"></i>
                                    <i class="fa-regular fa-trash-can cursor-pointer text-[14px] hover:text-rose-600 transition-colors delete-room-btn" data-id="{{ $room->id }}" title="Delete"></i>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-10 text-center text-slate-400 text-sm">No rooms found. Click "Add New Room" to get started.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- React Modal Root --}}
<div id="admin-rooms-modals"></div>
@endsection