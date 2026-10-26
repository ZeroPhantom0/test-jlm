@extends('layouts.admin', ['active' => 'announcements', 'pageTitle' => 'Announcements'])
@section('title', 'Announcements')

@section('content')
<div class="p-4 md:p-8 max-w-[1400px] mx-auto space-y-5">

    {{-- Banner --}}
    <div class="flex flex-col md:flex-row justify-between md:items-center gap-4">
        <div class="flex items-center gap-4">
            <div class="w-13 h-13 rounded-xl bg-yellow-100 text-amber-600 flex items-center justify-center text-[22px]">
                <i class="fa-solid fa-bullhorn"></i>
            </div>
            <div>
                <h2 class="text-2xl font-extrabold text-slate-900">Post Announcements</h2>
                <p class="text-sm text-slate-500 mt-0.5">Broadcast messages to tenants</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <button id="admin-open-new-ann" class="bg-[#0b1930] hover:bg-[#162640] text-white px-5 py-2.5 rounded-[10px] text-sm font-semibold inline-flex items-center gap-2 transition shadow-sm">
                <i class="fa-solid fa-plus"></i> New Announcement
            </button>
        </div>
    </div>

    {{-- Metrics --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl px-5 py-4 border border-slate-100 shadow-sm">
            <div class="text-[13.5px] font-semibold text-slate-500">Total</div>
            <div id="metric-total" class="text-2xl font-extrabold text-slate-900 mt-1.5">{{ $metrics['total'] }}</div>
        </div>
        <div class="bg-white rounded-2xl px-5 py-4 border border-slate-100 shadow-sm">
            <div class="text-[13.5px] font-semibold text-slate-500">Pinned</div>
            <span id="metric-pinned" class="inline-flex items-center justify-center bg-amber-100 text-amber-700 text-sm font-bold rounded-full px-2 h-7 min-w-7 mt-1.5">{{ $metrics['pinned'] }}</span>
        </div>
        <div class="bg-white rounded-2xl px-5 py-4 border border-slate-100 shadow-sm">
            <div class="text-[13.5px] font-semibold text-slate-500">Important</div>
            <span id="metric-important" class="inline-flex items-center justify-center bg-amber-100 text-amber-700 text-sm font-bold rounded-full px-2 h-7 min-w-7 mt-1.5">{{ $metrics['important'] }}</span>
        </div>
        <div class="bg-white rounded-2xl px-5 py-4 border border-slate-100 shadow-sm">
            <div class="text-[13.5px] font-semibold text-slate-500">Urgent</div>
            <span id="metric-urgent" class="inline-flex items-center justify-center bg-rose-100 text-rose-600 text-sm font-bold rounded-full px-2 h-7 min-w-7 mt-1.5">{{ $metrics['urgent'] }}</span>
        </div>
    </div>

    {{-- List --}}
    <div id="admin-announcements-list" class="flex flex-col gap-4">
        {{-- Rendered by JS for live updates --}}
    </div>
</div>

{{-- React Modal Mount Point with data --}}
<div id="admin-announcements-modals" data-announcements='@json($announcementsForReact)'></div>
@endsection

@push('scripts')
<script>
(function () {
    const DATA_URL = "{{ route('admin.announcements.data') }}";
    const listEl   = document.getElementById('admin-announcements-list');
    const modalsEl = document.getElementById('admin-announcements-modals');

    // Seed with initial server render
    let allRows = @json($rows);

    function escapeHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, (c) => (
            { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
        ));
    }

    function rowTemplate(a) {
        const pinnedBadge = a.is_pinned ? `
            <span class="inline-flex items-center gap-1.5 bg-yellow-100 text-amber-700 text-[11px] font-bold px-2 py-0.5 rounded-xl uppercase">
                <i class="fa-solid fa-thumbtack text-[10px]"></i> Pinned
            </span>` : '';

        return `
            <div class="bg-white ${escapeHtml(a.box_class)} rounded-2xl p-5 flex gap-4">
                <div class="w-11 h-11 rounded-xl ${escapeHtml(a.icon_bg)} flex items-center justify-center text-lg font-bold shrink-0">
                    <i class="${escapeHtml(a.priority_icon)}"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap justify-between items-center gap-3 mb-2.5">
                        <div class="flex items-center gap-2.5 flex-wrap">
                            <span class="text-lg font-bold text-slate-900">${escapeHtml(a.title)}</span>
                            ${pinnedBadge}
                        </div>
                        <div class="flex items-center gap-3.5 text-slate-400">
                            <button type="button" class="hover:text-slate-600 transition toggle-pin-btn" data-ann-id="${a.id}" title="${a.is_pinned ? 'Unpin' : 'Pin'}">
                                <i class="fa-solid fa-map-pin text-[15px]"></i>
                            </button>
                            <button type="button" class="hover:text-slate-600 transition icon-edit" data-ann-id="${a.id}" title="Edit">
                                <i class="fa-regular fa-pen-to-square text-[15px]"></i>
                            </button>
                            <button type="button" class="hover:text-rose-500 transition icon-delete" data-ann-id="${a.id}" title="Delete">
                                <i class="fa-regular fa-trash-can text-[15px]"></i>
                            </button>
                        </div>
                    </div>
                    <div class="text-sm text-slate-600 leading-relaxed mb-2.5">
                        ${escapeHtml(a.content)}
                    </div>
                    <div class="flex items-center gap-4 text-[13px]">
                        <span class="inline-flex items-center gap-1.5 ${escapeHtml(a.priority_class)} text-xs font-semibold px-3 py-1 rounded-[14px]">
                            <i class="fa-solid fa-circle-info text-[10px]"></i> ${escapeHtml(a.priority)}
                        </span>
                        <span class="text-slate-400 font-medium">By ${escapeHtml(a.author)}</span>
                        <span class="text-slate-400">${escapeHtml(a.created_at)}</span>
                    </div>
                </div>
            </div>
        `;
    }

    function renderRows() {
        if (!allRows.length) {
            listEl.innerHTML = `
                <div class="bg-white border border-slate-200 rounded-2xl p-12 text-center text-slate-400">
                    <i class="fa-solid fa-bullhorn text-4xl mb-3"></i>
                    <p class="text-sm">No announcements found.</p>
                </div>`;
            return;
        }
        listEl.innerHTML = allRows.map(rowTemplate).join('');
    }

    function setMetrics(m) {
        document.getElementById('metric-total').textContent     = m.total;
        document.getElementById('metric-pinned').textContent    = m.pinned;
        document.getElementById('metric-important').textContent = m.important;
        document.getElementById('metric-urgent').textContent    = m.urgent;
    }

    async function refresh() {
        try {
            const res = await fetch(DATA_URL, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (!res.ok) return;
            const data = await res.json();

            allRows = data.rows || [];
            setMetrics(data.metrics || {});
            renderRows();

            if (modalsEl) {
                modalsEl.dataset.announcements = JSON.stringify(data.announcementsForReact || {});
                window.dispatchEvent(new CustomEvent('announcements:data-updated', {
                    detail: data.announcementsForReact || {}
                }));
            }
        } catch (e) { /* silent */ }
    }

    // Initial paint
    renderRows();

    // Polling fallback
    let pollTimer = setInterval(refresh, 3000);
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) clearInterval(pollTimer);
        else { refresh(); pollTimer = setInterval(refresh, 3000); }
    });

    // Echo real-time
    if (window.Echo) {
        try {
            window.Echo.channel('admin.announcements')
                .listen('.announcement.updated', () => refresh());
        } catch (e) { /* Echo unavailable → polling covers it */ }
    }

    // After any admin action (create/edit/delete/toggle), refresh immediately
    window.addEventListener('announcements:data-updated', () => refresh());
})();
</script>
@endpush