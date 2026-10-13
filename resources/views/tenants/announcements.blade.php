@extends('layouts.tenant', ['active' => 'announcements', 'pageTitle' => 'Announcements'])
@section('title', 'Announcements')

@section('content')
<div class="p-4 md:p-8 max-w-5xl mx-auto">

    {{-- Banner --}}
    <div class="flex items-center gap-4 mb-6">
        <div class="w-11 h-11 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center text-lg shrink-0">
            <i class="fa-solid fa-bullhorn"></i>
        </div>
        <div class="min-w-0">
            <h2 class="text-xl font-bold text-slate-900">Announcements</h2>
            <p class="text-[13px] text-slate-500 mt-0.5">Official notices from building management</p>
        </div>
    </div>

    {{-- Metrics --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white border border-slate-200 rounded-xl px-5 py-4">
            <div class="text-xs text-slate-500 font-medium">Total</div>
            <div class="text-2xl font-bold text-slate-900 mt-1">3</div>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl px-5 py-4">
            <div class="text-xs text-slate-500 font-medium">Pinned</div>
            <div class="text-2xl font-bold text-indigo-600 mt-1">2</div>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl px-5 py-4">
            <div class="text-xs text-slate-500 font-medium">Urgent</div>
            <div class="text-2xl font-bold text-red-600 mt-1">0</div>
        </div>
    </div>

    {{-- Filter --}}
    <div class="bg-white border border-slate-200 rounded-xl p-3 flex flex-col sm:flex-row gap-3 mb-6">
        <div class="relative flex-1">
            <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
            <input type="text" placeholder="Search announcements..."
                   class="w-full bg-slate-50 border border-slate-100 py-2.5 pl-10 pr-3.5 rounded-lg text-[13px] outline-none focus:border-blue-300" />
        </div>
        <select class="bg-white border border-slate-200 rounded-lg px-4 py-2.5 text-[13px] sm:min-w-[140px] outline-none">
            <option>All Priorities</option>
            <option>Important</option>
            <option>Notice</option>
        </select>
    </div>

    {{-- Pinned --}}
    <div class="text-[11px] font-bold tracking-wider text-slate-400 uppercase mb-3 flex items-center gap-1.5">
        <i class="fa-solid fa-thumbtack text-[10px]"></i> Pinned Announcements
    </div>

    <div class="space-y-4 mb-8">
        @foreach([
            ['title' => 'Water Maintenance Scheduled', 'desc' => 'Water supply will be interrupted on Saturday, March 25 from 8 AM to 12 PM for routine maintenance. Please store water in advance.', 'date' => 'Mar 24, 2026', 'type' => 'important'],
            ['title' => 'Gym Renovation Notice', 'desc' => 'The gym facility will be closed for renovation from March 28 to April 5. We apologize for the inconvenience.', 'date' => 'Mar 18, 2026', 'type' => 'notice'],
        ] as $a)
        <div class="bg-white border border-slate-200 border-l-[5px] {{ $a['type'] === 'important' ? 'border-l-indigo-500' : 'border-l-slate-300' }} rounded-xl p-5">
            <div class="flex flex-wrap justify-between items-center gap-2 mb-3">
                <div class="flex flex-wrap gap-2 items-center">
                    <span class="bg-indigo-100 text-indigo-700 text-[11px] font-semibold px-2 py-1 rounded-full flex items-center gap-1">
                        <i class="fa-solid fa-thumbtack text-[9px]"></i> Pinned
                    </span>
                    <span class="text-[11px] font-semibold px-2 py-1 rounded-full uppercase
                        {{ $a['type'] === 'important' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600' }}">
                        <i class="fa-solid fa-{{ $a['type'] === 'important' ? 'circle-exclamation' : 'circle-info' }} text-[10px]"></i>
                        {{ $a['type'] }}
                    </span>
                </div>
                <span class="text-xs text-slate-400">{{ $a['date'] }}</span>
            </div>
            <h3 class="text-base font-bold text-slate-900 mb-2">{{ $a['title'] }}</h3>
            <p class="text-[13px] text-slate-600 leading-relaxed mb-4">{{ $a['desc'] }}</p>
            <div class="text-xs text-slate-400 flex items-center gap-1.5">
                <i class="fa-regular fa-globe"></i> Posted by Alice Administrator
            </div>
        </div>
        @endforeach
    </div>

    {{-- All --}}
    <div class="text-[11px] font-bold tracking-wider text-slate-400 uppercase mb-3">All Announcements</div>
    <div class="bg-white border border-slate-200 border-l-[5px] border-l-indigo-500 rounded-xl p-5">
        <div class="flex flex-wrap justify-between items-center gap-2 mb-3">
            <span class="bg-slate-100 text-slate-600 text-[11px] font-semibold px-2 py-1 rounded-full uppercase">
                <i class="fa-solid fa-circle-info text-[10px]"></i> Notice
            </span>
            <span class="text-xs text-slate-400">Mar 22, 2026</span>
        </div>
        <h3 class="text-base font-bold text-slate-900 mb-2">Welcome New Residents!</h3>
        <p class="text-[13px] text-slate-600 leading-relaxed mb-4">Please join us in welcoming our newest residents to JLM Cozy Cabin. Community gathering at the lobby on Friday at 6 PM.</p>
        <div class="text-xs text-slate-400 flex items-center gap-1.5">
            <i class="fa-regular fa-globe"></i> Alice Administrator
        </div>
    </div>
</div>
@endsection