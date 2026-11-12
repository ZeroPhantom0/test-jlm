// resources/js/utils/tablePager.js
//
// Client-side pagination for admin tables / lists.
//
// Mark any <tbody> (or list container) with `data-paginate` and it gets a
// pager rendered below it, showing `data-per-page` items (default 10).
//
// It cooperates with each page's existing filter scripts instead of
// replacing them: a filter hides an item (style.display = 'none', the
// `hidden` class, or the `hidden` attribute) and the pager only pages through
// the items that are left. Paging itself uses a separate `data-page-hidden`
// attribute, so the two never fight over the same flag.
//
// A MutationObserver re-paginates whenever rows are re-rendered (live
// refresh via innerHTML) or a filter shows/hides rows, so no page needs to
// call into the pager manually.
//
// Options (data attributes on the container):
//   data-per-page="10"             items per page
//   data-item-selector=".row"      which children count as items
//                                  (default: direct <tr> children for a tbody,
//                                  all direct children otherwise)
//   data-pager-reset="#a, #b"      controls whose input/change/click jumps
//                                  back to page 1 (i.e. the filters)
//   data-pager-after="#el"         render the pager after this element
//                                  (default: after the table's scroll wrapper)
//
// Items with `data-no-paginate` (totals rows, empty-state rows) are left
// alone, as are single-cell colspan rows such as "No records found".

const STYLE_ID = 'table-pager-style';
const DEFAULT_PER_PAGE = 10;

function injectStyle() {
    if (document.getElementById(STYLE_ID)) return;
    const style = document.createElement('style');
    style.id = STYLE_ID;
    style.textContent = '[data-page-hidden]{display:none !important;}';
    document.head.appendChild(style);
}

function isPlaceholderRow(el) {
    if (el.tagName !== 'TR') return false;
    const cells = el.children;
    return cells.length === 1 && Number(cells[0].getAttribute('colspan') || 1) > 1;
}

function isFilteredOut(el) {
    return el.style.display === 'none' || el.classList.contains('hidden') || el.hidden;
}

function buttonHtml(label, page, { disabled = false, active = false } = {}) {
    const base =
        'min-w-[34px] h-[34px] px-2.5 inline-flex items-center justify-center rounded-lg text-[13px] font-semibold border transition';
    const cls = active
        ? 'bg-[#02122b] text-white border-[#02122b]'
        : disabled
            ? 'bg-slate-50 text-slate-300 border-slate-200 cursor-not-allowed'
            : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50';
    return `<button type="button" data-page="${page}" ${disabled ? 'disabled' : ''} class="${base} ${cls}">${label}</button>`;
}

class TablePager {
    constructor(container) {
        this.container = container;
        this.perPage = parseInt(container.dataset.perPage, 10) || DEFAULT_PER_PAGE;
        this.page = 1;
        this.scheduled = false;

        this.buildUi();
        this.bindResetControls();
        this.observe();
        this.render();
    }

    // ------------------------------------------------------------------
    // Items
    // ------------------------------------------------------------------
    items() {
        const selector = this.container.dataset.itemSelector;
        const children = Array.from(this.container.children);
        const candidates = selector
            ? children.filter((el) => el.matches(selector))
            : this.container.tagName === 'TBODY'
                ? children.filter((el) => el.tagName === 'TR')
                : children;

        return candidates.filter((el) => !el.hasAttribute('data-no-paginate') && !isPlaceholderRow(el));
    }

    // ------------------------------------------------------------------
    // UI
    // ------------------------------------------------------------------
    buildUi() {
        this.el = document.createElement('div');
        this.el.className =
            'table-pager px-4 py-4 border-t border-slate-100 flex flex-col md:flex-row items-center justify-between gap-3';
        this.el.innerHTML = `
            <span class="table-pager-info text-[12.5px] text-slate-500"></span>
            <div class="table-pager-buttons flex items-center gap-1.5 flex-wrap justify-center"></div>
        `;
        this.info = this.el.querySelector('.table-pager-info');
        this.buttons = this.el.querySelector('.table-pager-buttons');

        this.buttons.addEventListener('click', (e) => {
            const btn = e.target.closest('button[data-page]');
            if (!btn || btn.disabled) return;
            const p = parseInt(btn.dataset.page, 10);
            if (!Number.isFinite(p) || p === this.page) return;
            this.page = p;
            this.render();
        });

        const afterSel = this.container.dataset.pagerAfter;
        let anchor = afterSel ? document.querySelector(afterSel) : null;
        if (!anchor) {
            const table = this.container.closest('table');
            anchor = table ? table.closest('.overflow-x-auto') || table : this.container;
        }
        anchor.insertAdjacentElement('afterend', this.el);
    }

    bindResetControls() {
        const sel = this.container.dataset.pagerReset;
        if (!sel) return;
        document.querySelectorAll(sel).forEach((ctrl) => {
            const reset = () => {
                this.page = 1;
                this.schedule();
            };
            ctrl.addEventListener('input', reset);
            ctrl.addEventListener('change', reset);
            ctrl.addEventListener('click', reset);
        });
    }

    observe() {
        this.observer = new MutationObserver(() => this.schedule());
        this.observer.observe(this.container, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['style', 'class', 'hidden'],
        });
    }

    // Batch several DOM mutations (e.g. a filter touching every row) into one render.
    schedule() {
        if (this.scheduled) return;
        this.scheduled = true;
        requestAnimationFrame(() => {
            this.scheduled = false;
            this.render();
        });
    }

    render() {
        // Stop observing our own attribute writes.
        this.observer?.disconnect();

        const all = this.items();
        const visible = all.filter((el) => !isFilteredOut(el));
        const total = visible.length;
        const lastPage = Math.max(1, Math.ceil(total / this.perPage));
        this.page = Math.min(Math.max(1, this.page), lastPage);

        const start = (this.page - 1) * this.perPage;
        const end = start + this.perPage;
        const onPage = new Set(visible.slice(start, end));

        all.forEach((el) => {
            if (onPage.has(el) || isFilteredOut(el)) el.removeAttribute('data-page-hidden');
            else el.setAttribute('data-page-hidden', '');
        });

        this.renderControls(total, lastPage, start);

        this.observer?.observe(this.container, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['style', 'class', 'hidden'],
        });
    }

    renderControls(total, lastPage, start) {
        this.el.hidden = total === 0;
        if (total === 0) return;

        const from = start + 1;
        const to = Math.min(start + this.perPage, total);
        this.info.textContent = `Showing ${from}–${to} of ${total}`;

        const cur = this.page;
        let html = buttonHtml('<i class="fa-solid fa-chevron-left text-[11px]"></i>', cur - 1, { disabled: cur <= 1 });

        const windowSize = 2;
        const s = Math.max(1, cur - windowSize);
        const e = Math.min(lastPage, cur + windowSize);

        if (s > 1) {
            html += buttonHtml('1', 1);
            if (s > 2) html += '<span class="text-slate-400 px-1">…</span>';
        }
        for (let p = s; p <= e; p++) html += buttonHtml(String(p), p, { active: p === cur });
        if (e < lastPage) {
            if (e < lastPage - 1) html += '<span class="text-slate-400 px-1">…</span>';
            html += buttonHtml(String(lastPage), lastPage);
        }

        html += buttonHtml('<i class="fa-solid fa-chevron-right text-[11px]"></i>', cur + 1, { disabled: cur >= lastPage });
        this.buttons.innerHTML = html;
    }
}

export function initTablePagers(root = document) {
    injectStyle();
    root.querySelectorAll('[data-paginate]').forEach((el) => {
        if (el.__pager) return;
        el.__pager = new TablePager(el);
    });
}

export default initTablePagers;
