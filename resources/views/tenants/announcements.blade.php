@extends('layouts.tenant', ['active' => 'announcements', 'pageTitle' => 'Announcements'])
@section('title', 'Announcements')

@section('content')

{{-- React mount point for the House Rules modal --}}
<div id="tenant-announcements-modals"></div>

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
            <input
                type="text"
                id="searchInput"
                placeholder="Search announcements..."
                class="w-full bg-slate-50 border border-slate-100 py-2.5 pl-10 pr-3.5 rounded-lg text-[13px] outline-none focus:border-blue-300"
            />
        </div>
        <select
            id="prioritySelect"
            class="bg-white border border-slate-200 rounded-lg px-4 py-2.5 text-[13px] sm:min-w-[140px] outline-none"
        >
            <option value="all">All Priorities</option>
            <option value="important">Important</option>
            <option value="notice">Notice</option>
        </select>
    </div>

    {{-- ============ OFFICIAL HOUSE RULES CARD ============ --}}
    <div class="bg-amber-50 border border-amber-300 rounded-xl mb-8 overflow-hidden shadow-sm">
        {{-- Header --}}
        <div class="bg-amber-500 text-white px-5 py-3 flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2.5 text-[13px] font-bold uppercase tracking-wider">
                <i class="fa-solid fa-file-contract text-base"></i>
                Official House Rules &amp; Contract
            </div>
            <div class="text-[13px] opacity-90">Sep 28, 2026</div>
        </div>

        {{-- Body --}}
        <div class="p-5">
            <h3 class="text-lg font-extrabold text-slate-900 mb-3">
                JLM Cozy Cabin - House Rules &amp; Boarding Contract
            </h3>
            <p class="text-[13px] text-slate-600 leading-relaxed mb-4">
                HOUSE RULES &amp; BOARDING CONTRACT JLM Cozy Cabin — Effective: January 2026 1. QUIET HOURS Quiet hours are strictly observed from 10:00 PM to 7:00 AM daily. Loud music, parties, and excessive noise are prohibited during these hours. Violations are subject to warnings and eventual eviction. 2. VISITOR POLICY Visitors are allowed only between 8:00 AM and
            </p>

            {{-- Footer --}}
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-center gap-1.5 text-xs text-slate-500">
                    <i class="fa-regular fa-globe text-sm"></i>
                    <span>Leah Administrator</span>
                </div>
                <button
                    type="button"
                    id="viewRulesBtn"
                    class="bg-amber-500 hover:bg-amber-600 text-white px-5 py-2.5 rounded-lg text-[13px] font-bold inline-flex items-center justify-center gap-2 transition w-full sm:w-auto"
                >
                    <i class="fa-regular fa-file-lines"></i>
                    View Complete Rules
                </button>
            </div>
        </div>
    </div>

    {{-- Pinned --}}
    <div class="text-[11px] font-bold tracking-wider text-slate-400 uppercase mb-3 flex items-center gap-1.5">
        <i class="fa-solid fa-thumbtack text-[10px]"></i> Pinned Announcements
    </div>

    <div class="space-y-4 mb-8" id="pinnedList">
        @foreach([
            ['title' => 'Water Maintenance Scheduled', 'desc' => 'Water supply will be interrupted on Saturday, March 25 from 8 AM to 12 PM for routine maintenance. Please store water in advance.', 'date' => 'Mar 24, 2026', 'type' => 'important'],
            ['title' => 'Gym Renovation Notice', 'desc' => 'The gym facility will be closed for renovation from March 28 to April 5. We apologize for the inconvenience.', 'date' => 'Mar 18, 2026', 'type' => 'notice'],
        ] as $a)
        <div class="announcement-card bg-white border border-slate-200 border-l-[5px] {{ $a['type'] === 'important' ? 'border-l-indigo-500' : 'border-l-slate-300' }} rounded-xl p-5">
            <div class="flex flex-wrap justify-between items-center gap-2 mb-3">
                <div class="flex flex-wrap gap-2 items-center">
                    <span class="bg-indigo-100 text-indigo-700 text-[11px] font-semibold px-2 py-1 rounded-full flex items-center gap-1">
                        <i class="fa-solid fa-thumbtack text-[9px]"></i> Pinned
                    </span>
                    <span class="badge-{{ $a['type'] }} text-[11px] font-semibold px-2 py-1 rounded-full uppercase
                        {{ $a['type'] === 'important' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600' }}">
                        <i class="fa-solid fa-{{ $a['type'] === 'important' ? 'circle-exclamation' : 'circle-info' }} text-[10px]"></i>
                        {{ $a['type'] }}
                    </span>
                </div>
                <span class="text-xs text-slate-400">{{ $a['date'] }}</span>
            </div>
            <h3 class="card-title text-base font-bold text-slate-900 mb-2">{{ $a['title'] }}</h3>
            <p class="card-desc text-[13px] text-slate-600 leading-relaxed mb-4">{{ $a['desc'] }}</p>
            <div class="text-xs text-slate-400 flex items-center gap-1.5">
                <i class="fa-regular fa-globe"></i> Posted by Alice Administrator
            </div>
        </div>
        @endforeach
    </div>

    {{-- All --}}
    <div class="text-[11px] font-bold tracking-wider text-slate-400 uppercase mb-3">All Announcements</div>
    <div class="announcement-card bg-white border border-slate-200 border-l-[5px] border-l-indigo-500 rounded-xl p-5">
        <div class="flex flex-wrap justify-between items-center gap-2 mb-3">
            <span class="badge-notice bg-slate-100 text-slate-600 text-[11px] font-semibold px-2 py-1 rounded-full uppercase">
                <i class="fa-solid fa-circle-info text-[10px]"></i> Notice
            </span>
            <span class="text-xs text-slate-400">Mar 22, 2026</span>
        </div>
        <h3 class="card-title text-base font-bold text-slate-900 mb-2">Welcome New Residents!</h3>
        <p class="card-desc text-[13px] text-slate-600 leading-relaxed mb-4">Please join us in welcoming our newest residents to JLM Cozy Cabin. Community gathering at the lobby on Friday at 6 PM.</p>
        <div class="text-xs text-slate-400 flex items-center gap-1.5">
            <i class="fa-regular fa-globe"></i> Alice Administrator
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // ============================================================
    // Search & Priority Filter
    // ============================================================
    (function () {
        const searchInput    = document.getElementById('searchInput');
        const prioritySelect = document.getElementById('prioritySelect');
        const cards          = document.querySelectorAll('.announcement-card');

        if (!searchInput || !prioritySelect) return;

        function filterAnnouncements() {
            const term     = searchInput.value.toLowerCase().trim();
            const priority = prioritySelect.value.toLowerCase();

            cards.forEach((card) => {
                const title = card.querySelector('.card-title')?.textContent.toLowerCase() || '';
                const desc  = card.querySelector('.card-desc')?.textContent.toLowerCase() || '';

                const hasImportant = card.querySelector('.badge-important') !== null;
                const hasNotice    = card.querySelector('.badge-notice') !== null;

                const matchesSearch =
                    !term || title.includes(term) || desc.includes(term);

                let matchesPriority = true;
                if (priority === 'important') matchesPriority = hasImportant;
                else if (priority === 'notice') matchesPriority = hasNotice;

                card.style.display = (matchesSearch && matchesPriority) ? '' : 'none';
            });
        }

        searchInput.addEventListener('input', filterAnnouncements);
        prioritySelect.addEventListener('change', filterAnnouncements);
    })();
</script>
@endpush