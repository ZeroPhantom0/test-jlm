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
            <div class="text-2xl font-extrabold text-slate-900">12</div>
        </div>
        <div class="bg-white rounded-2xl px-5 py-4 border border-slate-100 shadow-sm">
            <p class="text-[12px] text-slate-500 font-medium mb-1.5">Occupied</p>
            <div class="w-8 h-8 rounded-full bg-green-100 text-green-600 inline-flex items-center justify-center text-base font-bold">7</div>
        </div>
        <div class="bg-white rounded-2xl px-5 py-4 border border-slate-100 shadow-sm">
            <p class="text-[12px] text-slate-500 font-medium mb-1.5">Reserved</p>
            <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 inline-flex items-center justify-center text-base font-bold">2</div>
        </div>
        <div class="bg-white rounded-2xl px-5 py-4 border border-slate-100 shadow-sm">
            <p class="text-[12px] text-slate-500 font-medium mb-1.5">Available</p>
            <div class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 inline-flex items-center justify-center text-base font-bold">3</div>
        </div>
    </div>

    {{-- Table Card --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
        <div class="flex flex-col md:flex-row gap-3.5 mb-5">
            <div class="flex-1 bg-white border border-slate-300 rounded-[10px] px-3.5 py-2.5 flex items-center gap-2.5">
                <i class="fa-solid fa-magnifying-glass text-slate-400 text-[13px]"></i>
                <input type="text" placeholder="Search room or tenant..." class="w-full text-[13px] outline-none border-none bg-transparent">
            </div>
            <select class="bg-white border border-slate-300 rounded-[10px] px-3.5 py-2.5 text-[13px] text-slate-700 outline-none cursor-pointer min-w-[160px]">
                <option>All Statuses</option>
                <option>Occupied</option>
                <option>Available</option>
                <option>Reserved</option>
            </select>
            <select class="bg-white border border-slate-300 rounded-[10px] px-3.5 py-2.5 text-[13px] text-slate-700 outline-none cursor-pointer min-w-[160px]">
                <option>All Types</option>
                <option>Solo Room</option>
                <option>Bedspace</option>
                <option>Barkada Room</option>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="text-[12.5px] font-bold text-slate-600 border-b border-slate-100">
                        <th class="py-3 px-4">Room</th>
                        <th class="py-3 px-4">Type</th>
                        <th class="py-3 px-4">Tenant</th>
                        <th class="py-3 px-4">Occupancy</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Rent Rate</th>
                        <th class="py-3 px-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- Row: Room 202 --}}
                    <tr class="border-b border-slate-50 last:border-0">
                        <td class="py-4 px-4">
                            <div class="text-sm font-bold text-slate-900">Room 202</div>
                            <div class="text-[11px] text-slate-400 mt-0.5">Floor 1 • R202</div>
                        </td>
                        <td class="py-4 px-4">
                            <span class="inline-block px-3 py-1.5 rounded-md text-[10.5px] font-extrabold tracking-wider uppercase bg-blue-100 text-blue-700">Solo Room (Deluxe)</span>
                        </td>
                        <td class="py-4 px-4">
                            <div class="flex items-center gap-2.5">
                                <div class="w-7 h-7 rounded-full bg-green-100 text-green-600 flex items-center justify-center text-xs font-bold">N</div>
                                <span class="text-[13px] text-slate-800">Nicole Custorio</span>
                            </div>
                        </td>
                        <td class="py-4 px-4">
                            <div class="inline-flex items-center gap-1.5 bg-slate-100 px-3 py-1.5 rounded-full text-xs font-semibold text-slate-600">
                                <i class="fa-regular fa-user"></i> 1/1
                            </div>
                        </td>
                        <td class="py-4 px-4">
                            <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-semibold bg-green-100 text-green-600">
                                <i class="fa-solid fa-check"></i> Occupied
                            </span>
                        </td>
                        <td class="py-4 px-4 text-[13px] font-bold text-slate-900">₱1,900</td>
                        <td class="py-4 px-4">
                            <div class="flex items-center gap-3.5 text-slate-900">
                                <i class="fa-regular fa-eye cursor-pointer text-[15px] hover:text-orange-500 transition view-room-btn" title="View"></i>
                                <i class="fa-regular fa-pen-to-square cursor-pointer text-[15px] hover:text-orange-500 transition edit-room-btn" title="Edit"></i>
                                <i class="fa-regular fa-trash-can cursor-pointer text-[15px] hover:text-orange-500 transition" title="Delete"></i>
                            </div>
                        </td>
                    </tr>

                    {{-- Row: Room 101 --}}
                    <tr class="border-b border-slate-50 last:border-0">
                        <td class="py-4 px-4">
                            <div class="text-sm font-bold text-slate-900">Room 101</div>
                            <div class="text-[11px] text-slate-400 mt-0.5">Floor 1 • R101-B1-U</div>
                        </td>
                        <td class="py-4 px-4">
                            <span class="inline-block px-3 py-1.5 rounded-md text-[10.5px] font-extrabold tracking-wider uppercase bg-green-100 text-green-700">Bedspace (Premium)</span>
                        </td>
                        <td class="py-4 px-4">
                            <div class="flex items-center gap-2.5">
                                <div class="w-7 h-7 rounded-full bg-green-100 text-green-600 flex items-center justify-center text-xs font-bold">E</div>
                                <span class="text-[13px] text-slate-800">Elle Dela Cruz</span>
                            </div>
                        </td>
                        <td class="py-4 px-4">
                            <div class="inline-flex items-center gap-1.5 bg-slate-100 px-3 py-1.5 rounded-full text-xs font-semibold text-slate-600">
                                <i class="fa-regular fa-user"></i> 2/8
                            </div>
                        </td>
                        <td class="py-4 px-4">
                            <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-semibold bg-green-100 text-green-600">
                                <i class="fa-solid fa-check"></i> Occupied
                            </span>
                        </td>
                        <td class="py-4 px-4 text-[13px] font-bold text-slate-900">₱600</td>
                        <td class="py-4 px-4">
                            <div class="flex items-center gap-3.5 text-slate-900">
                                <i class="fa-regular fa-eye cursor-pointer text-[15px] hover:text-orange-500 transition view-room-btn"></i>
                                <i class="fa-regular fa-pen-to-square cursor-pointer text-[15px] hover:text-orange-500 transition edit-room-btn"></i>
                                <i class="fa-regular fa-trash-can cursor-pointer text-[15px] hover:text-orange-500 transition"></i>
                            </div>
                        </td>
                    </tr>

                    {{-- Row: Room 103 (Available) --}}
                    <tr class="border-b border-slate-50 last:border-0">
                        <td class="py-4 px-4">
                            <div class="text-sm font-bold text-slate-900">Room 103</div>
                            <div class="text-[11px] text-slate-400 mt-0.5">Floor 1 • R103-B1-L</div>
                        </td>
                        <td class="py-4 px-4">
                            <span class="inline-block px-3 py-1.5 rounded-md text-[10.5px] font-extrabold tracking-wider uppercase bg-green-100 text-green-700">Bedspace (Premium)</span>
                        </td>
                        <td class="py-4 px-4">
                            <span class="text-[13px] italic text-slate-400">No tenant</span>
                        </td>
                        <td class="py-4 px-4">
                            <div class="inline-flex items-center gap-1.5 bg-slate-100 px-3 py-1.5 rounded-full text-xs font-semibold text-slate-600">
                                <i class="fa-regular fa-user"></i> 0/8
                            </div>
                        </td>
                        <td class="py-4 px-4">
                            <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                <i class="fa-solid fa-house"></i> Available
                            </span>
                        </td>
                        <td class="py-4 px-4 text-[13px] font-bold text-slate-900">₱300</td>
                        <td class="py-4 px-4">
                            <div class="flex items-center gap-3.5 text-slate-900">
                                <i class="fa-regular fa-eye cursor-pointer text-[15px] hover:text-orange-500 transition view-room-btn"></i>
                                <i class="fa-regular fa-pen-to-square cursor-pointer text-[15px] hover:text-orange-500 transition edit-room-btn"></i>
                                <i class="fa-regular fa-trash-can cursor-pointer text-[15px] hover:text-orange-500 transition"></i>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- React Modal Root --}}
<div id="admin-rooms-modals"></div>
@endsection