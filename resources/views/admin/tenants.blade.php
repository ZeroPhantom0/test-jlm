@extends('layouts.admin', ['active' => 'tenants', 'pageTitle' => 'Tenants'])
@section('title', 'Tenants')

@section('content')
<div class="p-4 md:p-8 max-w-[1400px] mx-auto space-y-5">

    {{-- Page Header --}}
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div class="flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-green-100 text-green-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-users"></i>
            </div>
            <div>
                <h2 class="text-[22px] font-extrabold text-slate-900 leading-tight">Manage Tenants</h2>
                <p class="text-[13px] text-slate-500">Directory of current and past tenants</p>
            </div>
        </div>
        <button class="bg-white border border-slate-300 text-slate-700 px-4 py-2 rounded-lg text-[13px] font-semibold inline-flex items-center gap-2 hover:bg-slate-50 transition">
            <i class="fa-regular fa-file-lines"></i> Export
        </button>
    </div>

    {{-- Stats Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div class="bg-white rounded-2xl px-5 py-4 border border-slate-200">
            <div class="text-xs text-slate-500 font-medium">Total</div>
            <div class="text-[26px] font-extrabold text-slate-900 mt-1">5</div>
        </div>
        <div class="bg-white rounded-2xl px-5 py-4 border border-slate-200">
            <div class="text-xs text-slate-500 font-medium">Active</div>
            <div class="text-[26px] font-extrabold text-green-600 mt-1">4</div>
        </div>
        <div class="bg-white rounded-2xl px-5 py-4 border border-slate-200">
            <div class="text-xs text-slate-500 font-medium">Inactive</div>
            <div class="text-[26px] font-extrabold text-slate-500 mt-1">1</div>
        </div>
    </div>

    {{-- Table Card --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-5">
        <div class="flex items-center gap-4 mb-5 flex-wrap">
            <div class="flex-1 bg-slate-50 border border-slate-200 rounded-[10px] px-3.5 py-2 flex items-center gap-2.5">
                <i class="fa-solid fa-magnifying-glass text-slate-400 text-sm"></i>
                <input type="text" placeholder="Search name or email..." class="w-full text-[13px] outline-none bg-transparent">
            </div>
            <select class="bg-white border border-slate-300 rounded-[10px] px-4 py-2 text-[13px] font-medium text-slate-700 outline-none cursor-pointer min-w-[140px]">
                <option>All Statuses</option>
                <option>Active</option>
                <option>Inactive</option>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="text-xs font-bold text-slate-500 border-b border-slate-100">
                        <th class="py-3 px-3.5">Name</th>
                        <th class="py-3 px-3.5">Contact</th>
                        <th class="py-3 px-3.5">Room</th>
                        <th class="py-3 px-3.5">Move In</th>
                        <th class="py-3 px-3.5">Move Out</th>
                        <th class="py-3 px-3.5">Rent Rate</th>
                        <th class="py-3 px-3.5">Status</th>
                        <th class="py-3 px-3.5">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach([
                        ['initial' => 'N', 'name' => 'Nicole Custorio', 'email' => 'nicole@gmail.com', 'phone' => '09171234567', 'room' => 'Room 202', 'rate' => '₱1,900', 'status' => 'active'],
                        ['initial' => 'E', 'name' => 'Elle Dela Cruz', 'email' => 'elle@gmail.com', 'phone' => '09181234567', 'room' => 'Room 101', 'rate' => '₱600', 'status' => 'active'],
                        ['initial' => 'M', 'name' => 'Mau Santos', 'email' => 'mau@gmail.com', 'phone' => '09191234567', 'room' => 'Room 201', 'rate' => '₱600', 'status' => 'active'],
                        ['initial' => 'G', 'name' => 'Gabriel Maningo', 'email' => 'gabriel@gmail.com', 'phone' => '09201234567', 'room' => 'Room 101', 'rate' => '₱1,000', 'status' => 'active'],
                        ['initial' => 'F', 'name' => 'Francis Sabiniano', 'email' => 'francis@gmail.com', 'phone' => '09211234567', 'room' => 'Room 203', 'rate' => '₱4,200', 'status' => 'inactive'],
                    ] as $t)
                    <tr class="border-b border-slate-50 last:border-0">
                        <td class="py-4 px-3.5">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full {{ $t['status'] === 'active' ? 'bg-green-100 text-green-600' : 'bg-slate-100 text-slate-500' }} flex items-center justify-center font-bold text-[13px]">
                                    {{ $t['initial'] }}
                                </div>
                                <div class="font-bold text-slate-900">{{ $t['name'] }}</div>
                            </div>
                        </td>
                        <td class="py-4 px-3.5">
                            <div class="flex flex-col gap-0.5 text-xs text-slate-500">
                                <div class="flex items-center gap-1.5"><i class="fa-regular fa-envelope text-[11px] w-3"></i> {{ $t['email'] }}</div>
                                <div class="flex items-center gap-1.5"><i class="fa-solid fa-phone text-[11px] w-3"></i> {{ $t['phone'] }}</div>
                            </div>
                        </td>
                        <td class="py-4 px-3.5 text-[13px] font-medium text-slate-900">{{ $t['room'] }}</td>
                        <td class="py-4 px-3.5 text-xs text-slate-700">
                            <div><i class="fa-regular fa-calendar text-[10px] text-slate-400 mr-1"></i> Mar 20, 2026</div>
                            <div class="text-slate-500">6:00 PM</div>
                        </td>
                        <td class="py-4 px-3.5 text-xs text-slate-700">
                            <div><i class="fa-regular fa-calendar text-[10px] text-slate-400 mr-1"></i> Mar 22, 2026</div>
                            <div class="text-slate-500">6:00 PM</div>
                        </td>
                        <td class="py-4 px-3.5 text-[13px] font-semibold text-slate-900">{{ $t['rate'] }}</td>
                        <td class="py-4 px-3.5">
                            <span class="inline-block px-2.5 py-1 rounded-xl text-[11px] font-bold
                                {{ $t['status'] === 'active' ? 'bg-green-100 text-green-600' : 'bg-slate-100 text-slate-500' }}">
                                {{ ucfirst($t['status']) }}
                            </span>
                        </td>
                        <td class="py-4 px-3.5">
                            <div class="flex items-center gap-3">
                                <i class="fa-regular fa-eye text-green-500 text-sm cursor-pointer hover:scale-110 transition view-tenant-btn"></i>
                                <i class="fa-solid fa-pencil text-slate-700 text-sm cursor-pointer hover:scale-110 transition edit-tenant-btn"></i>
                                <i class="fa-regular fa-trash-can text-red-500 text-sm cursor-pointer hover:scale-110 transition"></i>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="admin-tenants-modals"></div>
@endsection