@extends('layouts.tenant', ['active' => 'announcements', 'pageTitle' => 'Announcements'])
@section('title', 'Announcements')

@section('content')

{{-- React mount point for the House Rules modal (default static rules) --}}
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

    {{-- Filter --}}
    <div class="bg-white border border-slate-200 rounded-xl p-2.5 flex flex-col sm:flex-row gap-2.5 mb-6">
        <div class="relative flex-1">
            <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[12px]"></i>
            <input
                type="text"
                id="searchInput"
                placeholder="Search announcements..."
                class="w-full h-9 bg-slate-50 border border-slate-200 py-0 pl-8 pr-3 rounded-lg text-[12.5px] text-slate-800 placeholder-slate-400 outline-none transition focus:border-blue-300 focus:bg-white focus:ring-2 focus:ring-blue-100"
            />
        </div>
        <select
            id="prioritySelect"
            class="h-9 bg-white border border-slate-200 rounded-lg px-3 pr-8 text-[12.5px] text-slate-700 outline-none cursor-pointer appearance-none bg-[url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 fill=%22none%22 viewBox=%220 0 24 24%22 stroke=%22%2394a3b8%22 stroke-width=%222%22><path stroke-linecap=%22round%22 stroke-linejoin=%22round%22 d=%22M19 9l-7 7-7-7%22/></svg>')] bg-[length:14px] bg-[right_10px_center] bg-no-repeat transition focus:border-blue-300 focus:ring-2 focus:ring-blue-100 sm:min-w-[140px]"
        >
            <option value="all">All Priorities</option>
            <option value="important">Important</option>
            <option value="urgent">Urgent</option>
            <option value="notice">Notice</option>
        </select>
    </div>

    {{-- ============ OFFICIAL HOUSE RULES CARD (static) ============ --}}
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

    {{-- ============ PINNED ============ --}}
    @if($pinnedAnnouncements->count() > 0)
        <div class="text-[11px] font-bold tracking-wider text-slate-400 uppercase mb-3 flex items-center gap-1.5">
            <i class="fa-solid fa-thumbtack text-[10px]"></i> Pinned Announcements
        </div>

        <div class="space-y-4 mb-8" id="pinnedList">
            @foreach($pinnedAnnouncements as $a)
                @php
                    $priority = strtolower($a->priority ?? 'normal');

                    $borderClass = match ($a->priority) {
                        'Urgent'    => 'border-l-rose-500',
                        'Important' => 'border-l-indigo-500',
                        default     => 'border-l-slate-300',
                    };
                    $badgeBg = match ($a->priority) {
                        'Urgent'    => 'bg-rose-100 text-rose-700',
                        'Important' => 'bg-amber-100 text-amber-700',
                        default     => 'bg-slate-100 text-slate-600',
                    };
                    $badgeIcon = $a->priority === 'Normal'
                        ? 'fa-circle-info'
                        : 'fa-circle-exclamation';
                @endphp

                <div class="announcement-card bg-white border border-slate-200 border-l-[5px] {{ $borderClass }} rounded-xl p-5">
                    <div class="flex flex-wrap justify-between items-center gap-2 mb-3">
                        <div class="flex flex-wrap gap-2 items-center">
                            <span class="bg-indigo-100 text-indigo-700 text-[11px] font-semibold px-2 py-1 rounded-full flex items-center gap-1">
                                <i class="fa-solid fa-thumbtack text-[9px]"></i> Pinned
                            </span>
                            <span class="badge-{{ $priority }} text-[11px] font-semibold px-2 py-1 rounded-full uppercase {{ $badgeBg }} flex items-center gap-1">
                                <i class="fa-solid {{ $badgeIcon }} text-[10px]"></i>
                                {{ $a->priority }}
                            </span>
                        </div>
                        <span class="text-xs text-slate-400">
                            {{ $a->created_at?->format('M d, Y') ?? '—' }}
                        </span>
                    </div>
                    <h3 class="card-title text-base font-bold text-slate-900 mb-2">{{ $a->title }}</h3>
                    <p class="card-desc text-[13px] text-slate-600 leading-relaxed mb-4">{{ $a->content }}</p>
                    <div class="text-xs text-slate-400 flex items-center gap-1.5">
                        <i class="fa-regular fa-globe"></i> Posted by {{ $a->author->name ?? 'Administrator' }}
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- ============ ALL ANNOUNCEMENTS ============ --}}
    <div class="text-[11px] font-bold tracking-wider text-slate-400 uppercase mb-3">
        All Announcements
    </div>

    @if($regularAnnouncements->count() > 0)
        <div class="space-y-4" id="regularList">
            @foreach($regularAnnouncements as $a)
                @php
                    $priority = strtolower($a->priority ?? 'normal');

                    $borderClass = match ($a->priority) {
                        'Urgent'    => 'border-l-rose-500',
                        'Important' => 'border-l-indigo-500',
                        default     => 'border-l-slate-300',
                    };
                    $badgeBg = match ($a->priority) {
                        'Urgent'    => 'bg-rose-100 text-rose-700',
                        'Important' => 'bg-amber-100 text-amber-700',
                        default     => 'bg-slate-100 text-slate-600',
                    };
                    $badgeIcon = $a->priority === 'Normal'
                        ? 'fa-circle-info'
                        : 'fa-circle-exclamation';
                @endphp

                <div class="announcement-card bg-white border border-slate-200 border-l-[5px] {{ $borderClass }} rounded-xl p-5">
                    <div class="flex flex-wrap justify-between items-center gap-2 mb-3">
                        <span class="badge-{{ $priority }} text-[11px] font-semibold px-2 py-1 rounded-full uppercase {{ $badgeBg }} flex items-center gap-1">
                            <i class="fa-solid {{ $badgeIcon }} text-[10px]"></i>
                            {{ $a->priority }}
                        </span>
                        <span class="text-xs text-slate-400">
                            {{ $a->created_at?->format('M d, Y') ?? '—' }}
                        </span>
                    </div>
                    <h3 class="card-title text-base font-bold text-slate-900 mb-2">{{ $a->title }}</h3>
                    <p class="card-desc text-[13px] text-slate-600 leading-relaxed mb-4">{{ $a->content }}</p>
                    <div class="text-xs text-slate-400 flex items-center gap-1.5">
                        <i class="fa-regular fa-globe"></i> {{ $a->author->name ?? 'Administrator' }}
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="bg-white border border-slate-200 rounded-xl px-6 py-12 text-center text-slate-400">
            <i class="fa-regular fa-bell text-3xl mb-2 block"></i>
            <p class="text-sm">No announcements yet.</p>
        </div>
    @endif
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
                const hasUrgent    = card.querySelector('.badge-urgent') !== null;
                const hasNotice    = card.querySelector('.badge-normal') !== null;

                const matchesSearch =
                    !term || title.includes(term) || desc.includes(term);

                let matchesPriority = true;
                if (priority === 'important')   matchesPriority = hasImportant;
                else if (priority === 'urgent') matchesPriority = hasUrgent;
                else if (priority === 'notice') matchesPriority = hasNotice;

                card.style.display = (matchesSearch && matchesPriority) ? '' : 'none';
            });
        }

        searchInput.addEventListener('input', filterAnnouncements);
        prioritySelect.addEventListener('change', filterAnnouncements);
    })();
</script>
@endpush