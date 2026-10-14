{{-- resources/views/admin/virtual-tour.blade.php --}}
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
        <button id="admin-open-add-tour"
                class="bg-[#0b1930] hover:bg-[#162640] text-white px-5 py-2.5 rounded-[10px] text-sm font-semibold inline-flex items-center gap-2 transition shadow-sm">
            <i class="fa-solid fa-plus"></i> Add Room
        </button>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl px-5 py-4 border border-slate-100 shadow-sm">
            <div class="text-[13px] font-bold text-slate-600">Total Virtual Tours</div>
            <div class="text-[28px] font-extrabold text-slate-900 leading-tight mt-2">{{ $stats['total'] }}</div>
            <div class="text-[11.5px] text-slate-400 mt-2">All tours created</div>
        </div>
        <div class="bg-white rounded-2xl px-5 py-4 border-[1.5px] border-emerald-200 shadow-sm">
            <div class="text-[13px] font-bold text-emerald-700">Published Tours</div>
            <div class="text-[28px] font-extrabold text-emerald-500 leading-tight mt-2">{{ $stats['published'] }}</div>
            <div class="text-[11.5px] text-slate-400 mt-2">Visible to tenants</div>
        </div>
        <div class="bg-white rounded-2xl px-5 py-4 border-[1.5px] border-amber-200 shadow-sm">
            <div class="text-[13px] font-bold text-amber-700">Draft Tours</div>
            <div class="text-[28px] font-extrabold text-amber-500 leading-tight mt-2">{{ $stats['draft'] }}</div>
            <div class="text-[11.5px] text-slate-400 mt-2">Not yet published</div>
        </div>
        <div class="bg-white rounded-2xl px-5 py-4 border-[1.5px] border-violet-200 shadow-sm">
            <div class="text-[13px] font-bold text-violet-700">Total Hotspots</div>
            <div class="text-[28px] font-extrabold text-violet-500 leading-tight mt-2">{{ $stats['hotspots'] }}</div>
            <div class="text-[11.5px] text-slate-400 mt-2">Across all tours</div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="flex flex-col md:flex-row gap-4">
        <input type="text" id="tour-search" placeholder="Search virtual tours..."
               class="flex-1 bg-white border border-slate-200 rounded-[10px] px-4 py-3 text-[13px] outline-none"
               value="{{ request('search') }}">
        <select id="tour-status-filter"
                class="bg-white border border-slate-200 rounded-[10px] px-4 py-3 text-[13px] text-slate-700 outline-none cursor-pointer md:w-[180px]">
            <option value="">All Statuses</option>
            <option value="Published" {{ request('status') == 'Published' ? 'selected' : '' }}>Published</option>
            <option value="Draft" {{ request('status') == 'Draft' ? 'selected' : '' }}>Draft</option>
            <option value="Archived" {{ request('status') == 'Archived' ? 'selected' : '' }}>Archived</option>
        </select>
    </div>

    <h3 class="text-base font-bold text-slate-900">All Virtual Room Tours</h3>

    {{-- Tours List --}}
    <div class="flex flex-col gap-3.5" id="tours-list-container">
        @forelse($tours as $index => $tour)
        <div class="bg-white border border-slate-200 rounded-2xl px-5 py-3 flex items-center justify-between gap-4 tour-item"
             data-tour-id="{{ $tour->id }}"
             data-status="{{ $tour->status }}"
             data-title="{{ strtolower($tour->title) }}">
            <div class="flex items-center gap-4">
                <div class="flex flex-col items-center gap-0.5 text-slate-400 text-[11px]">
                    <i class="fa-solid fa-chevron-up cursor-pointer hover:text-slate-700"></i>
                    <span class="text-xs font-bold text-slate-500">{{ $index + 1 }}</span>
                    <i class="fa-solid fa-chevron-down cursor-pointer hover:text-slate-700"></i>
                </div>
                <img src="{{ $tour->thumbnail_url }}" alt="Room"
                     class="w-[110px] h-16 rounded-lg object-cover"
                     onerror="this.src='https://via.placeholder.com/220x128?text=360+Tour'">
                <div>
                    <h4 class="text-[14.5px] font-bold text-slate-900 leading-tight">{{ $tour->title }}</h4>
                    <p class="text-xs text-slate-500 mt-1">
                        {{ $tour->room->room_number ?? 'N/A' }}
                        &nbsp; Cap: {{ $tour->room->total_beds ?? 'N/A' }}
                        &nbsp; <span class="text-emerald-500 font-bold">₱{{ number_format($tour->room->daily_rent ?? 0, 0) }}/day</span>
                    </p>
                </div>
            </div>
            <div class="hidden md:flex items-center gap-8">
                @if($tour->status === 'Published')
                    <span class="inline-flex items-center gap-1.5 bg-emerald-100 text-emerald-600 text-[11.5px] font-bold px-3 py-1 rounded-xl">
                        <i class="fa-solid fa-circle" style="font-size: 6px;"></i> Published
                    </span>
                @elseif($tour->status === 'Draft')
                    <span class="inline-flex items-center gap-1.5 bg-yellow-100 text-amber-700 text-[11.5px] font-bold px-3 py-1 rounded-xl">
                        <i class="fa-solid fa-circle" style="font-size: 6px;"></i> Draft
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 bg-slate-100 text-slate-600 text-[11.5px] font-bold px-3 py-1 rounded-xl">
                        <i class="fa-solid fa-circle" style="font-size: 6px;"></i> Archived
                    </span>
                @endif
                <div class="flex items-center gap-2 text-[12.5px] text-slate-500">
                    <span class="rounded-full bg-violet-100 text-violet-700 font-bold text-[11px] inline-flex items-center justify-center w-6 h-6">
                        {{ $tour->hotspots_count }}
                    </span>
                    hotspots
                </div>
                <div class="text-xs text-slate-400">{{ $tour->created_at->format('M d, Y') }}</div>
            </div>
            <div class="flex items-center gap-4 text-slate-400 text-[15px]">
                <i class="fa-regular fa-eye cursor-pointer hover:text-slate-900 transition view-tour-btn"
                   data-tour='@json($tour->toViewerArray())' title="Preview Tour"></i>
                <a href="{{ route('admin.virtual-tour.builder', $tour->id) }}"
                   title="Hotspot Configuration"
                   class="text-slate-400 hover:text-slate-900 transition">
                    <i class="fa-solid fa-signal"></i>
                </a>
                <i class="fa-regular fa-pen-to-square cursor-pointer hover:text-slate-900 transition edit-tour-btn"
                   data-tour='@json($tour->toEditArray())' title="Edit Tour"></i>
                <i class="fa-regular fa-trash-can cursor-pointer hover:text-rose-500 transition delete-tour-btn"
                   data-tour-id="{{ $tour->id }}"
                   data-tour-title="{{ $tour->title }}" title="Delete Tour"></i>
            </div>
        </div>
        @empty
        <div class="text-center py-16 bg-white rounded-2xl border border-dashed border-slate-200">
            <i class="fa-solid fa-folder-open text-4xl text-slate-300 mb-3"></i>
            <p class="text-slate-500 font-medium">No virtual tours found.</p>
            <p class="text-slate-400 text-sm mt-1">Click "Add Room" to create your first virtual tour.</p>
        </div>
        @endforelse
    </div>

    {{-- Hidden data for React modal --}}
    <<div id="vt-rooms-data"
     data-rooms="{{ json_encode($roomsForReact, JSON_HEX_APOS | JSON_HEX_QUOT) }}"
     class="hidden"></div>

{{-- React modal mount point --}}
<div id="admin-virtual-tour-modals"></div>

{{-- Pass route URLs to React --}}
<script>
    window.__VT_ROUTES__ = {
        store:   '{{ route("admin.virtual-tour.store") }}',
        update:  '{{ route("admin.virtual-tour.update", ":id") }}',
        destroy: '{{ route("admin.virtual-tour.destroy", ":id") }}',
    };
</script>
@endsection

@push('scripts')
<script>
// Client-side search & filter (unchanged)
(function () {
    const searchInput = document.getElementById('tour-search');
    const statusFilter = document.getElementById('tour-status-filter');
    if (!searchInput || !statusFilter) return;

    function filterTours() {
        const search = searchInput.value.toLowerCase();
        const status = statusFilter.value;

        document.querySelectorAll('.tour-item').forEach(item => {
            const title  = item.dataset.title;
            const itemStatus = item.dataset.status;
            const matchesSearch = title.includes(search);
            const matchesStatus = !status || itemStatus === status;
            item.style.display = (matchesSearch && matchesStatus) ? '' : 'none';
        });
    }

    searchInput.addEventListener('input', filterTours);
    statusFilter.addEventListener('change', filterTours);
})();
</script>
@endpush