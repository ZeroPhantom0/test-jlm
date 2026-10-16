// resources/js/pages/History/HistoryModals.jsx
import React, { useState, useEffect } from 'react';
import Modal from '../../components/Modal';

/**
 * Renders:
 *  1. Expandable inline detail panel (drives .history-item-details inside each row)
 *  2. View-Receipt modal               (opened from payment rows)
 *  3. Filter pills + search + sort     (replaces the old @push('scripts') block)
 *
 * Contract with Blade:
 *   - Row:        <div class="history-item" data-item-key="KEY" data-category="CAT">
 *   - Header:     <div class="history-item-header">
 *   - Panel:      <div class="history-item-details" id="details-KEY">
 *   - Chevron:    <i class="expand-chevron">
 *   - Receipt:    <button class="view-receipt-btn" data-item-key="KEY">
 *   - Filters:    <div class="history-pill" data-category="CAT">
 *   - Search:     <input id="history-search">
 *   - Sort:       <select id="history-sort">
 *   - Counter:    <div id="history-records-count">
 *   - List:       <div id="history-list">
 *
 * The component toggles classes AND sets inline styles so it works even
 * if `@push('styles')` is not rendered by the layout.
 */
export default function HistoryModals() {
    const [openKey, setOpenKey]   = useState(null);
    const [receipt, setReceipt]   = useState(null);
    const [items, setItems]       = useState({});

    // ============================================================
    // Read items map from mount element (passed by Blade)
    // ============================================================
    useEffect(() => {
        const el = document.getElementById('tenant-history-modals');
        if (!el) return;
        if (el.dataset.items) {
            try {
                setItems(JSON.parse(el.dataset.items));
            } catch (e) {
                console.error('Invalid history JSON', e);
            }
        }
    }, []);

    // ============================================================
    // Toggle expand / open receipt / export  (document-level delegation)
    // ============================================================
    useEffect(() => {
        const handler = (e) => {
            // --- Receipt button: handle FIRST so it never falls into toggle ---
            const receiptBtn = e.target.closest('.view-receipt-btn');
            if (receiptBtn) {
                e.preventDefault();
                e.stopPropagation();
                const key  = receiptBtn.dataset.itemKey;
                const data = items[key]?.receipt || items[key];
                if (data) setReceipt(data);
                return;
            }

            // --- Row header: toggle details panel ---
            const header = e.target.closest('.history-item-header');
            if (header) {
                const item = header.closest('.history-item');
                const key  = item?.dataset?.itemKey;
                if (key) {
                    setOpenKey((prev) => (prev === key ? null : key));
                }
                return;
            }

            // --- Export history ---
            if (e.target.closest('#export-history-btn')) {
                e.preventDefault();
                alert('Export will be available once wired to the backend.');
            }
        };

        document.addEventListener('click', handler);
        return () => document.removeEventListener('click', handler);
    }, [items]);

    // ============================================================
    // Sync openKey → toggle classes AND inline styles on the DOM
    // (works even without `@push('styles')`)
    // ============================================================
    useEffect(() => {
        const panels   = document.querySelectorAll('.history-item-details');
        const chevrons = document.querySelectorAll('.expand-chevron');

        // Collapse all first
        panels.forEach((p) => {
            p.classList.remove('expanded');
            p.style.maxHeight = '0px';
            p.style.overflow  = 'hidden';
            p.style.padding   = '0 20px';
            p.style.borderTop = '1px solid transparent';
            p.style.background = '#f8fafc';
            p.style.transition = 'max-height 0.35s ease, padding 0.35s ease';
        });
        chevrons.forEach((c) => {
            c.classList.remove('rotated');
            c.style.transition = 'transform 0.3s ease';
            c.style.transform  = 'rotate(0deg)';
        });

        if (!openKey) return;

        const panel   = document.getElementById(`details-${openKey}`);
        const chevron = document.querySelector(
            `.history-item[data-item-key="${openKey}"] .expand-chevron`
        );

        if (panel) {
            panel.classList.add('expanded');
            panel.style.maxHeight = '1200px';
            panel.style.overflow  = 'hidden';
            panel.style.padding   = '20px';
            panel.style.borderTop = '1px solid #f1f5f9';
            panel.style.background = '#f8fafc';
            panel.style.transition = 'max-height 0.35s ease, padding 0.35s ease';
        }
        if (chevron) {
            chevron.classList.add('rotated');
            chevron.style.transform = 'rotate(180deg)';
        }
    }, [openKey]);

    // ============================================================
    // Filter pills + search + sort (replaces the old @push('scripts'))
    // ============================================================
    useEffect(() => {
        const pills       = document.querySelectorAll('.history-pill');
        const listEl      = document.getElementById('history-list');
        const searchInput = document.getElementById('history-search');
        const sortSelect  = document.getElementById('history-sort');
        const counter     = document.getElementById('history-records-count');

        if (!listEl || !pills.length) return;

        let currentCategory = 'all';
        const initialOrder  = Array.from(listEl.querySelectorAll('.history-item'));

        const filterRecords = () => {
            const allItems = Array.from(listEl.querySelectorAll('.history-item'));
            const q = (searchInput?.value || '').toLowerCase().trim();
            let visible = 0;

            allItems.forEach((item) => {
                const cat  = item.dataset.category;
                const text = item.innerText.toLowerCase();
                const show =
                    (currentCategory === 'all' || cat === currentCategory) &&
                    text.includes(q);
                item.style.display = show ? '' : 'none';
                if (show) visible++;
            });

            if (counter) {
                counter.textContent =
                    `Showing ${visible} of ${allItems.length} total records`;
            }
        };

        const pillHandlers = [];
        pills.forEach((p) => {
            const h = () => {
                pills.forEach((x) => {
                    x.classList.remove('active', 'bg-[#02122c]', 'text-white');
                    x.classList.add('bg-slate-100', 'text-slate-600');
                });
                p.classList.add('active', 'bg-[#02122c]', 'text-white');
                p.classList.remove('bg-slate-100', 'text-slate-600');
                currentCategory = p.dataset.category;
                filterRecords();
            };
            p.addEventListener('click', h);
            pillHandlers.push([p, h]);
        });

        const searchHandler = () => filterRecords();
        searchInput?.addEventListener('input', searchHandler);

        const sortHandler = () => {
            const order = sortSelect?.value || 'newest';
            const nodes = order === 'oldest'
                ? [...initialOrder].reverse()
                : [...initialOrder];
            nodes.forEach((n) => listEl.appendChild(n));
            filterRecords();
        };
        sortSelect?.addEventListener('change', sortHandler);

        return () => {
            pillHandlers.forEach(([el, h]) => el.removeEventListener('click', h));
            searchInput?.removeEventListener('input', searchHandler);
            sortSelect?.removeEventListener('change', sortHandler);
        };
    }, []);

    const closeReceipt = () => setReceipt(null);

    return (
        <>
            {/* ============================================================ */}
            {/* RECEIPT MODAL (payment items only) */}
            {/* ============================================================ */}
            {receipt && (
                <Modal onClose={closeReceipt} maxWidth="max-w-[520px]">
                    <div className="p-5 pb-4 flex items-center justify-between border-b border-slate-100">
                        <h3 className="text-lg font-bold text-slate-900">Payment Receipt</h3>
                        <button
                            onClick={closeReceipt}
                            className="text-slate-400 hover:text-slate-700 text-lg"
                        >
                            <i className="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div className="p-6 overflow-y-auto">
                        <div className="border-[1.5px] border-dashed border-orange-400 bg-orange-50/30 rounded-2xl p-5 flex flex-col items-center gap-3">
                            <div className="w-11 h-11 rounded-full bg-orange-500 text-white flex items-center justify-center text-xl">
                                <i className="fa-solid fa-check"></i>
                            </div>
                            <div className="text-center">
                                <h4 className="text-base font-extrabold text-slate-900">
                                    Official Receipt
                                </h4>
                                <p className="text-[11px] text-orange-600 font-semibold">
                                    JLM Cozy Cabin
                                </p>
                            </div>
                            <div className="text-sm font-semibold text-slate-600">
                                Transaction No. {(receipt.reference || '').toUpperCase() || '-'}
                            </div>
                            <div className="text-[11px] text-slate-400">
                                Date of Issue: {receipt.paid_date || '-'}
                            </div>

                            <div className="w-full border-t border-slate-100 pt-3 flex flex-col gap-2">
                                {[
                                    ['Receipt No.',    (receipt.reference || '').toUpperCase() || '-'],
                                    ['Tenant',         receipt.tenant || 'Elle Dela Cruz'],
                                    ['Unit',           receipt.unit || '-'],
                                    ['Description',    receipt.description || '-'],
                                    ['Reference No.',  receipt.reference || '-'],
                                    ['Payment Method', (receipt.method || '-').toUpperCase()],
                                ].map((row, i) => (
                                    <div key={i} className="flex justify-between text-xs">
                                        <span className="text-slate-500 font-medium">{row[0]}</span>
                                        <span className="text-slate-900 font-bold">{row[1]}</span>
                                    </div>
                                ))}
                                <div className="flex justify-between text-sm mt-2 pt-2 border-t border-slate-100">
                                    <span className="text-slate-900 font-bold">Total Amount</span>
                                    <span className="text-slate-900 font-extrabold">
                                        {receipt.amount || '-'}
                                    </span>
                                </div>
                            </div>

                            <div className="text-center mt-2">
                                <p className="text-[11px] text-slate-500">
                                    Authorized By: <strong>Leah Agujar</strong>
                                </p>
                                <p className="text-xs font-bold text-orange-500 mt-1">
                                    Thank You! :)
                                </p>
                            </div>
                        </div>

                        <div className="flex gap-2.5 mt-4">
                            <button
                                className="flex-1 border border-slate-300 bg-white rounded-lg py-2.5 text-[13px] font-bold text-slate-900 inline-flex items-center justify-center gap-2 hover:bg-slate-50 transition"
                                onClick={() => window.print()}
                            >
                                <i className="fa-solid fa-print"></i> Print
                            </button>
                            <button
                                className="flex-1 bg-[#3b82f6] hover:bg-[#2563eb] text-white rounded-lg py-2.5 text-[13px] font-bold inline-flex items-center justify-center gap-2 transition"
                                onClick={() => alert('PDF download will be wired to the backend.')}
                            >
                                <i className="fa-solid fa-download"></i> Download PDF
                            </button>
                        </div>
                    </div>
                </Modal>
            )}
        </>
    );
}