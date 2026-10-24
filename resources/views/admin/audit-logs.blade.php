@extends('layouts.admin', ['active' => 'audit-logs', 'pageTitle' => 'Audit Logs'])
@section('title', 'Audit Logs')

@section('content')
<div class="p-4 md:p-8 max-w-[1400px] mx-auto space-y-5">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row justify-between md:items-center gap-4">
        <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <div>
                <h2 class="text-2xl font-extrabold text-slate-900">Audit Logs</h2>
                <p class="text-[13.5px] text-slate-500 mt-0.5">Immutable record of system activities and user actions</p>
            </div>
        </div>
        <div>
            <a id="export-link" href="{{ route('admin.audit-logs.export', request()->query()) }}"
               class="inline-flex items-center gap-2 bg-[#02122b] text-white rounded-lg px-4 py-2.5 text-[13.5px] font-semibold hover:bg-[#092042] transition">
                <i class="fa-solid fa-file-csv"></i> Export CSV
            </a>
        </div>
    </div>

    {{-- Filters (client-driven, no reload) --}}
    <div class="bg-white border border-slate-200 rounded-2xl px-5 py-4 flex flex-col md:flex-row gap-3 items-stretch md:items-center">
        <div class="flex-1 bg-slate-50 border border-slate-200 rounded-[10px] px-4 py-1.5 flex items-center gap-2.5">
            <i class="fa-solid fa-magnifying-glass text-slate-400 text-sm"></i>
            <input id="audit-search" type="text" value="{{ request('q') }}"
                   placeholder="Search by user, action, or description..."
                   class="w-full text-[13px] outline-none bg-transparent">
        </div>

        <input id="audit-from" type="date" value="{{ request('from') }}"
               class="bg-white border border-slate-200 rounded-[10px] px-3 py-1.5 text-[13px] text-slate-700 outline-none cursor-pointer">

        <span class="text-slate-400 text-[13px] text-center md:text-left">to</span>

        <input id="audit-to" type="date" value="{{ request('to') }}"
               class="bg-white border border-slate-200 rounded-[10px] px-3 py-1.5 text-[13px] text-slate-700 outline-none cursor-pointer">

        <div class="relative">
            <select id="audit-order"
                    class="appearance-none bg-white border border-slate-200 rounded-[10px] pl-3 pr-9 py-1.5 text-[13px] text-slate-700 outline-none cursor-pointer">
                <option value="newest" @selected(request('order', 'newest') === 'newest')>Newest first</option>
                <option value="oldest" @selected(request('order') === 'oldest')>Oldest first</option>
            </select>
            <i class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-[10px] text-slate-400 pointer-events-none"></i>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden relative">
        <div class="px-6 py-3.5 flex justify-between items-center border-b border-slate-100">
            <span class="text-[13px] text-slate-500 font-medium" id="audit-count-label">
                {{ $logs->total() }} events
            </span>
            <span class="text-[12px] text-slate-400" id="audit-order-label">
                {{ $order === 'oldest' ? 'Oldest first' : 'Newest first' }}
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="text-[11px] text-slate-500 uppercase tracking-wider font-bold border-b border-slate-100">
                        <th class="py-3.5 px-6">Timestamp</th>
                        <th class="py-3.5 px-6">Action</th>
                        <th class="py-3.5 px-6">Module</th>
                        <th class="py-3.5 px-6">Description</th>
                        <th class="py-3.5 px-6">User</th>
                        <th class="py-3.5 px-6">Status</th>
                    </tr>
                </thead>
                <tbody id="audit-tbody">
                    @forelse($logs as $log)
                    @php
                        $statusStyle = match($log->status) {
                            'success' => ['bg-emerald-50 text-emerald-700 border-emerald-200', 'fa-solid fa-circle-check', 'Success'],
                            'error'   => ['bg-rose-50 text-rose-700 border-rose-200', 'fa-solid fa-circle-xmark', 'Failed'],
                            'warning' => ['bg-amber-50 text-amber-700 border-amber-200', 'fa-solid fa-triangle-exclamation', 'Warning'],
                            default   => ['bg-slate-50 text-slate-700 border-slate-200', 'fa-solid fa-circle-info', ucfirst($log->status ?? 'Unknown')],
                        };
                        $moduleColors = [
                            'slate'  => 'bg-slate-100 text-slate-700',
                            'green'  => 'bg-green-100 text-green-700',
                            'blue'   => 'bg-blue-100 text-blue-700',
                            'teal'   => 'bg-teal-100 text-teal-700',
                            'indigo' => 'bg-indigo-100 text-indigo-700',
                            'pink'   => 'bg-pink-100 text-pink-700',
                            'amber'  => 'bg-amber-100 text-amber-700',
                            'purple' => 'bg-violet-100 text-violet-700',
                            'sky'    => 'bg-sky-100 text-sky-700',
                        ];
                        $avatarColors = [
                            'purple' => 'bg-violet-100 text-violet-600',
                            'teal'   => 'bg-teal-100 text-teal-600',
                            'gray'   => 'bg-slate-200 text-slate-500',
                        ];
                    @endphp
                    <tr class="border-b border-slate-50 last:border-0 hover:bg-slate-50/50">
                        <td class="py-3.5 px-6 whitespace-nowrap">
                            <div class="text-[13px] text-slate-800 font-medium">{{ $log->created_at?->format('M j, Y') }}</div>
                            <div class="text-[11.5px] text-slate-400">{{ $log->created_at?->format('g:i A') }}</div>
                        </td>
                        <td class="py-3.5 px-6">
                            <span class="inline-flex items-center gap-1.5 bg-slate-100 text-slate-700 text-[11px] font-bold px-2.5 py-1 rounded-md tracking-wide">
                                <i class="{{ $log->action_icon }} text-[10px]"></i> {{ $log->action }}
                            </span>
                        </td>
                        <td class="py-3.5 px-6">
                            <span class="inline-block px-2 py-1 rounded-md text-[10.5px] font-bold tracking-wider {{ $moduleColors[$log->module_color] ?? $moduleColors['slate'] }}">
                                {{ $log->module }}
                            </span>
                        </td>
                        <td class="py-3.5 px-6 text-[13px] text-slate-700 max-w-[360px] leading-snug">
                            {{ $log->description }}
                        </td>
                        <td class="py-3.5 px-6">
                            <div class="flex items-center gap-2.5">
                                <div class="w-7 h-7 rounded-full flex items-center justify-center text-[12px] font-bold {{ $avatarColors[$log->avatar_color] ?? $avatarColors['gray'] }}">
                                    {{ $log->initial }}
                                </div>
                                <div>
                                    <div class="text-[13px] font-semibold text-slate-900 leading-tight">{{ $log->user_name }}</div>
                                    <div class="text-[11px] text-slate-500 mt-0.5">{{ ucfirst($log->user_role) }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="py-3.5 px-6">
                            <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold px-2.5 py-1 rounded-full border {{ $statusStyle[0] }}">
                                <i class="{{ $statusStyle[1] }} text-[10px]"></i> {{ $statusStyle[2] }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-16 text-center">
                            <div class="flex flex-col items-center gap-2">
                                <i class="fa-solid fa-inbox text-3xl text-slate-300"></i>
                                <span class="text-[13.5px] text-slate-500">No audit log events found.</span>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Loading overlay --}}
        <div id="audit-loading"
             class="hidden absolute inset-0 bg-white/60 backdrop-blur-[1px] flex items-center justify-center">
            <div class="flex items-center gap-2 text-slate-600 text-[13px] font-semibold">
                <i class="fa-solid fa-circle-notch fa-spin"></i> Loading…
            </div>
        </div>

        {{-- Pagination --}}
        <div id="audit-pagination"
             class="px-6 py-4 border-t border-slate-100 flex flex-col md:flex-row items-center justify-between gap-3">
            <span class="text-[12.5px] text-slate-500" id="audit-page-info">
                Showing {{ $logs->firstItem() ?? 0 }}–{{ $logs->lastItem() ?? 0 }} of {{ $logs->total() }}
            </span>
            <div class="flex items-center gap-1.5" id="audit-page-buttons">
                {{-- Populated by JS --}}
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const DATA_URL   = @json(route('admin.audit-logs.data'));
    const EXPORT_URL = @json(route('admin.audit-logs.export'));

    const tbody       = document.getElementById('audit-tbody');
    const searchInput = document.getElementById('audit-search');
    const fromInput   = document.getElementById('audit-from');
    const toInput     = document.getElementById('audit-to');
    const orderSelect = document.getElementById('audit-order');
    const orderLabel  = document.getElementById('audit-order-label');
    const countLabel  = document.getElementById('audit-count-label');
    const pageInfo    = document.getElementById('audit-page-info');
    const pageButtons = document.getElementById('audit-page-buttons');
    const loadingEl   = document.getElementById('audit-loading');
    const exportLink  = document.getElementById('export-link');

    let state = {
        q:     searchInput.value || '',
        from:  fromInput.value   || '',
        to:    toInput.value     || '',
        order: orderSelect.value || 'newest',
        page:  1,
    };

    let requestSeq = 0; // guards against out-of-order responses

    // ---------------------------------------------------------------
    // Fetch + render
    // ---------------------------------------------------------------
    async function load(page = 1) {
        state.page = page;
        const seq = ++requestSeq;

        loadingEl.classList.remove('hidden');

        const params = new URLSearchParams();
        if (state.q)     params.set('q', state.q);
        if (state.from)  params.set('from', state.from);
        if (state.to)    params.set('to', state.to);
        if (state.order) params.set('order', state.order);
        params.set('page', page);

        // Keep the URL in sync (deep-linkable, back/forward friendly)
        const browserUrl = new URL(window.location.href);
        browserUrl.search = params.toString();
        window.history.replaceState({}, '', browserUrl);

        try {
            const res = await fetch(`${DATA_URL}?${params.toString()}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) throw new Error('Request failed');

            const json = await res.json();
            if (seq !== requestSeq) return; // stale — a newer request won

            renderRows(json.data);
            renderMeta(json.meta);
            renderPagination(json.meta, json.links);
            syncExportLink();
        } catch (e) {
            console.error(e);
            tbody.innerHTML = `
                <tr><td colspan="6" class="py-16 text-center">
                    <div class="flex flex-col items-center gap-2">
                        <i class="fa-solid fa-triangle-exclamation text-3xl text-rose-300"></i>
                        <span class="text-[13.5px] text-slate-500">Failed to load audit logs. Please try again.</span>
                    </div>
                </td></tr>`;
        } finally {
            if (seq === requestSeq) loadingEl.classList.add('hidden');
        }
    }

    function escapeHtml(str) {
        return String(str ?? '').replace(/[&<>"']/g, c => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[c]));
    }

    function renderRows(rows) {
        if (!rows.length) {
            tbody.innerHTML = `
                <tr><td colspan="6" class="py-16 text-center">
                    <div class="flex flex-col items-center gap-2">
                        <i class="fa-solid fa-inbox text-3xl text-slate-300"></i>
                        <span class="text-[13.5px] text-slate-500">No audit log events found.</span>
                    </div>
                </td></tr>`;
            return;
        }

        tbody.innerHTML = rows.map(r => `
            <tr class="border-b border-slate-50 last:border-0 hover:bg-slate-50/50">
                <td class="py-3.5 px-6 whitespace-nowrap">
                    <div class="text-[13px] text-slate-800 font-medium">${escapeHtml(r.date)}</div>
                    <div class="text-[11.5px] text-slate-400">${escapeHtml(r.time)}</div>
                </td>
                <td class="py-3.5 px-6">
                    <span class="inline-flex items-center gap-1.5 bg-slate-100 text-slate-700 text-[11px] font-bold px-2.5 py-1 rounded-md tracking-wide">
                        <i class="${escapeHtml(r.action_icon)} text-[10px]"></i> ${escapeHtml(r.action)}
                    </span>
                </td>
                <td class="py-3.5 px-6">
                    <span class="inline-block px-2 py-1 rounded-md text-[10.5px] font-bold tracking-wider ${escapeHtml(r.module_class)}">
                        ${escapeHtml(r.module)}
                    </span>
                </td>
                <td class="py-3.5 px-6 text-[13px] text-slate-700 max-w-[360px] leading-snug">
                    ${escapeHtml(r.description)}
                </td>
                <td class="py-3.5 px-6">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-full flex items-center justify-center text-[12px] font-bold ${escapeHtml(r.avatar_class)}">
                            ${escapeHtml(r.initial)}
                        </div>
                        <div>
                            <div class="text-[13px] font-semibold text-slate-900 leading-tight">${escapeHtml(r.user_name)}</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">${escapeHtml(r.user_role)}</div>
                        </div>
                    </div>
                </td>
                <td class="py-3.5 px-6">
                    <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold px-2.5 py-1 rounded-full border ${escapeHtml(r.status_class)}">
                        <i class="${escapeHtml(r.status_icon)} text-[10px]"></i> ${escapeHtml(r.status_text)}
                    </span>
                </td>
            </tr>
        `).join('');
    }

    function renderMeta(meta) {
        countLabel.textContent = `${meta.total} event${meta.total === 1 ? '' : 's'}`;
        orderLabel.textContent = state.order === 'oldest' ? 'Oldest first' : 'Newest first';

        if (meta.total === 0) {
            pageInfo.textContent = 'No events';
        } else {
            pageInfo.textContent = `Showing ${meta.from}–${meta.to} of ${meta.total}`;
        }
    }

    function renderPagination(meta, links) {
        const btn = (label, page, opts = {}) => {
            const { disabled = false, active = false } = opts;
            const base = 'min-w-[34px] h-[34px] px-2.5 inline-flex items-center justify-center rounded-lg text-[13px] font-semibold border transition';
            const cls = active
                ? 'bg-[#02122b] text-white border-[#02122b]'
                : disabled
                    ? 'bg-slate-50 text-slate-300 border-slate-200 cursor-not-allowed'
                    : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50';
            return `<button type="button" data-page="${page}" ${disabled ? 'disabled' : ''} class="${base} ${cls}">${label}</button>`;
        };

        let html = '';

        html += btn('<i class="fa-solid fa-chevron-left text-[11px]"></i>', meta.current_page - 1, {
            disabled: meta.current_page <= 1,
        });

        // Window of page numbers around the current page
        const windowSize = 2;
        const start = Math.max(1, meta.current_page - windowSize);
        const end   = Math.min(meta.last_page, meta.current_page + windowSize);

        if (start > 1) {
            html += btn('1', 1);
            if (start > 2) html += `<span class="text-slate-400 px-1">…</span>`;
        }

        for (let p = start; p <= end; p++) {
            html += btn(String(p), p, { active: p === meta.current_page });
        }

        if (end < meta.last_page) {
            if (end < meta.last_page - 1) html += `<span class="text-slate-400 px-1">…</span>`;
            html += btn(String(meta.last_page), meta.last_page);
        }

        html += btn('<i class="fa-solid fa-chevron-right text-[11px]"></i>', meta.current_page + 1, {
            disabled: meta.current_page >= meta.last_page,
        });

        pageButtons.innerHTML = html;

        pageButtons.querySelectorAll('button[data-page]').forEach(b => {
            b.addEventListener('click', () => {
                if (b.disabled) return;
                const p = parseInt(b.dataset.page, 10);
                if (!Number.isFinite(p) || p < 1 || p > meta.last_page) return;
                if (p === meta.current_page) return;
                load(p);
            });
        });
    }

    function syncExportLink() {
        const params = new URLSearchParams();
        if (state.q)     params.set('q', state.q);
        if (state.from)  params.set('from', state.from);
        if (state.to)    params.set('to', state.to);
        if (state.order) params.set('order', state.order);

        exportLink.setAttribute(
            'href',
            EXPORT_URL + (params.toString() ? '?' + params.toString() : '')
        );
    }

    // ---------------------------------------------------------------
    // Events
    // ---------------------------------------------------------------
    let searchTimer;
    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            state.q = searchInput.value.trim();
            load(1);
        }, 300);
    });

    fromInput.addEventListener('change', () => {
        state.from = fromInput.value || '';
        load(1);
    });

    toInput.addEventListener('change', () => {
        state.to = toInput.value || '';
        load(1);
    });

    orderSelect.addEventListener('change', () => {
        state.order = orderSelect.value || 'newest';
        load(1);
    });

    // Build the initial pagination for the server-rendered first page,
    // then leave it to JS on subsequent loads.
    renderPagination(
        {
            current_page: {{ $logs->currentPage() }},
            last_page:    {{ $logs->lastPage() }},
            from:         {{ $logs->firstItem() ?? 0 }},
            to:           {{ $logs->lastItem() ?? 0 }},
            total:        {{ $logs->total() }},
        },
        {}
    );
    syncExportLink();
})();
</script>
@endpush