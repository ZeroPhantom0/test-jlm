@extends('layouts.admin', ['active' => 'virtual-tour', 'pageTitle' => 'Virtual Room Tour'])
@section('title', 'Virtual Room Tour')

@section('content')
<div class="p-4 md:p-8 max-w-[1400px] mx-auto space-y-5" id="virtual-tour-main-view">

    {{-- Page Header --}}
    <div class="flex flex-col md:flex-row justify-between md:items-center gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-camera"></i>
            </div>
            <div>
                <h2 class="text-2xl font-extrabold text-slate-900">Virtual Tour Manager</h2>
                <p class="text-sm text-slate-500 mt-0.5">Manage 360° tour content displayed to tenants</p>
            </div>
        </div>
        <button id="admin-open-add-tour" class="bg-[#0b1930] hover:bg-[#162640] text-white px-5 py-2.5 rounded-[10px] text-sm font-semibold inline-flex items-center gap-2 transition shadow-sm">
            <i class="fa-solid fa-plus"></i> Add Room
        </button>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl px-5 py-4 border border-slate-100 shadow-sm">
            <div class="text-[13px] font-bold text-slate-600">Total Virtual Tours</div>
            <div class="text-[28px] font-extrabold text-slate-900 leading-tight mt-2">8</div>
            <div class="text-[11.5px] text-slate-400 mt-2">All tours created</div>
        </div>
        <div class="bg-white rounded-2xl px-5 py-4 border-[1.5px] border-emerald-200 shadow-sm">
            <div class="text-[13px] font-bold text-emerald-700">Published Tours</div>
            <div class="text-[28px] font-extrabold text-emerald-500 leading-tight mt-2">16</div>
            <div class="text-[11.5px] text-slate-400 mt-2">Visible to tenants</div>
        </div>
        <div class="bg-white rounded-2xl px-5 py-4 border-[1.5px] border-amber-200 shadow-sm">
            <div class="text-[13px] font-bold text-amber-700">Draft Tours</div>
            <div class="text-[28px] font-extrabold text-amber-500 leading-tight mt-2">6</div>
            <div class="text-[11.5px] text-slate-400 mt-2">Not yet published</div>
        </div>
        <div class="bg-white rounded-2xl px-5 py-4 border-[1.5px] border-violet-200 shadow-sm">
            <div class="text-[13px] font-bold text-violet-700">Total Hotspots</div>
            <div class="text-[28px] font-extrabold text-violet-500 leading-tight mt-2">48</div>
            <div class="text-[11.5px] text-slate-400 mt-2">Across all tours</div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="flex flex-col md:flex-row gap-4">
        <input type="text" placeholder="Search virtual tours..." class="flex-1 bg-white border border-slate-200 rounded-[10px] px-4 py-3 text-[13px] outline-none">
        <select class="bg-white border border-slate-200 rounded-[10px] px-4 py-3 text-[13px] text-slate-700 outline-none cursor-pointer md:w-[180px]">
            <option>All Statuses</option>
            <option>Published</option>
            <option>Draft</option>
        </select>
    </div>

    <h3 class="text-base font-bold text-slate-900">All Virtual Room Tours</h3>

    {{-- Tours List --}}
    <div class="flex flex-col gap-3.5">
        @foreach([
            ['num' => 1, 'name' => 'Solo Room – Standard', 'room' => 'RM 101', 'cap' => '1–2', 'price' => '₱600/day', 'status' => 'draft', 'hotspots' => 0, 'date' => 'Aug 29, 2026', 'img' => 'https://images.unsplash.com/photo-1598928506311-c55ded91a20c?auto=format&fit=crop&q=80&w=300'],
            ['num' => 2, 'name' => 'Solo Room – Deluxe', 'room' => 'RM 102', 'cap' => '1–2', 'price' => '₱950/day', 'status' => 'published', 'hotspots' => 2, 'date' => 'Aug 29, 2026', 'img' => 'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?auto=format&fit=crop&q=80&w=300'],
            ['num' => 3, 'name' => 'Barkada Room – Standard', 'room' => 'RM 103', 'cap' => '4–6', 'price' => '₱250/day', 'status' => 'published', 'hotspots' => 3, 'date' => 'Aug 28, 2026', 'img' => 'https://images.unsplash.com/photo-1555854877-bab0e564b8d5?auto=format&fit=crop&q=80&w=300'],
            ['num' => 4, 'name' => 'Barkada Room – Premium', 'room' => 'RM 104', 'cap' => '4–6', 'price' => '₱400/day', 'status' => 'published', 'hotspots' => 4, 'date' => 'Aug 26, 2026', 'img' => 'https://images.unsplash.com/photo-1540518614846-7ede433c5172?auto=format&fit=crop&q=80&w=300'],
            ['num' => 5, 'name' => 'Bedspace – Standard', 'room' => 'RM 201', 'cap' => '8', 'price' => '₱200/day', 'status' => 'draft', 'hotspots' => 1, 'date' => 'Aug 24, 2026', 'img' => 'https://images.unsplash.com/photo-1512918728675-ed5a9ecdebfd?auto=format&fit=crop&q=80&w=300'],
            ['num' => 6, 'name' => 'Bedspace – Premium', 'room' => 'RM 202', 'cap' => '8', 'price' => '₱300/day', 'status' => 'published', 'hotspots' => 1, 'date' => 'Aug 24, 2026', 'img' => 'https://images.unsplash.com/photo-1616486338812-3dadae4b4ace?auto=format&fit=crop&q=80&w=300'],
            ['num' => 7, 'name' => 'Bedspace – All Girls', 'room' => 'RM 203', 'cap' => '12', 'price' => '₱300/day', 'status' => 'draft', 'hotspots' => 0, 'date' => 'Aug 23, 2026', 'img' => 'https://images.unsplash.com/photo-1595526114035-0d45ed16cfbf?auto=format&fit=crop&q=80&w=300'],
            ['num' => 8, 'name' => 'Bedspace – All Boys', 'room' => 'RM 204', 'cap' => '12', 'price' => '₱300/day', 'status' => 'published', 'hotspots' => 3, 'date' => 'Aug 20, 2026', 'img' => 'https://images.unsplash.com/photo-1505691938895-1758d7feb511?auto=format&fit=crop&q=80&w=300'],
        ] as $tour)
        <div class="bg-white border border-slate-200 rounded-2xl px-5 py-3 flex items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="flex flex-col items-center gap-0.5 text-slate-400 text-[11px]">
                    <i class="fa-solid fa-chevron-up cursor-pointer hover:text-slate-700"></i>
                    <span class="text-xs font-bold text-slate-500">{{ $tour['num'] }}</span>
                    <i class="fa-solid fa-chevron-down cursor-pointer hover:text-slate-700"></i>
                </div>
                <img src="{{ $tour['img'] }}" alt="Room" class="w-[110px] h-16 rounded-lg object-cover">
                <div>
                    <h4 class="text-[14.5px] font-bold text-slate-900 leading-tight">{{ $tour['name'] }}</h4>
                    <p class="text-xs text-slate-500 mt-1">{{ $tour['room'] }} &nbsp; Cap: {{ $tour['cap'] }} &nbsp; <span class="text-emerald-500 font-bold">{{ $tour['price'] }}</span></p>
                </div>
            </div>
            <div class="hidden md:flex items-center gap-8">
                @if($tour['status'] === 'published')
                    <span class="inline-flex items-center gap-1.5 bg-emerald-100 text-emerald-600 text-[11.5px] font-bold px-3 py-1 rounded-xl">
                        <i class="fa-solid fa-circle" style="font-size: 6px;"></i> Published
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 bg-yellow-100 text-amber-700 text-[11.5px] font-bold px-3 py-1 rounded-xl">
                        <i class="fa-solid fa-circle" style="font-size: 6px;"></i> Draft
                    </span>
                @endif
                <div class="flex items-center gap-2 text-[12.5px] text-slate-500">
                    <span class="w-5.5 h-5.5 rounded-full bg-violet-100 text-violet-700 font-bold text-[11px] inline-flex items-center justify-center w-6 h-6">{{ $tour['hotspots'] }}</span>
                    hotspots
                </div>
                <div class="text-xs text-slate-400">{{ $tour['date'] }}</div>
            </div>
            <div class="flex items-center gap-4 text-slate-400 text-[15px]">
                <i class="fa-regular fa-eye cursor-pointer hover:text-slate-900 transition"></i>
                <i class="fa-solid fa-signal cursor-pointer hover:text-slate-900 transition"></i>
                <i class="fa-regular fa-pen-to-square cursor-pointer hover:text-slate-900 transition"></i>
                <i class="fa-regular fa-trash-can cursor-pointer hover:text-rose-500 transition"></i>
            </div>
        </div>
        @endforeach
    </div>
</div>

<div id="admin-virtual-tour-modals"></div>
@endsection