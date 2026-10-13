// resources/js/pages/Admin/ServicesModals.jsx
import React, { useState, useEffect } from 'react';
import Modal from '../../components/Modal';

export default function ServicesModals() {
    const [showDetails, setShowDetails] = useState(false);

    useEffect(() => {
        const handler = (e) => {
            if (e.target.closest('.admin-view-request-btn')) setShowDetails(true);
        };
        document.addEventListener('click', handler);
        return () => document.removeEventListener('click', handler);
    }, []);

    return (
        <>
            {showDetails && (
                <Modal onClose={() => setShowDetails(false)} maxWidth="max-w-[520px]">
                    <div className="p-6 pb-4 flex items-center justify-between border-b border-slate-100">
                        <h3 className="text-lg font-bold text-slate-900">Request Details</h3>
                        <button onClick={() => setShowDetails(false)} className="text-slate-400 hover:text-slate-700 text-lg">
                            <i className="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div className="p-6 overflow-y-auto flex flex-col gap-4">
                        <div className="flex items-center justify-between">
                            <h4 className="text-lg font-bold text-slate-900">Leaky Faucet</h4>
                            <div className="flex items-center gap-1.5">
                                <span className="text-[10px] font-extrabold bg-yellow-100 text-amber-700 px-1.5 py-0.5 rounded uppercase">MEDIUM</span>
                                <span className="bg-yellow-100 text-amber-700 text-[11px] font-semibold px-2.5 py-1 rounded-full">Pending</span>
                            </div>
                        </div>
                        <div className="text-xs text-slate-400">#SR-1</div>

                        <div className="bg-slate-50 rounded-xl p-4 text-[13.5px] text-slate-700 leading-snug">
                            Kitchen sink is dripping constantly.
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="flex items-start gap-2.5">
                                <i className="fa-regular fa-user text-slate-400 text-[14px] mt-0.5"></i>
                                <div>
                                    <div className="text-[11px] text-slate-400">Tenant</div>
                                    <p className="text-[13px] font-bold text-slate-900 mt-0.5">Nicole Custorio</p>
                                </div>
                            </div>
                            <div className="flex items-start gap-2.5">
                                <i className="fa-solid fa-wrench text-slate-400 text-[14px] mt-0.5"></i>
                                <div>
                                    <div className="text-[11px] text-slate-400">Room / Category</div>
                                    <p className="text-[13px] font-bold text-slate-900 mt-0.5">Room 101 · Plumbing</p>
                                </div>
                            </div>
                            <div className="flex items-start gap-2.5 col-span-2">
                                <i className="fa-regular fa-calendar text-slate-400 text-[14px] mt-0.5"></i>
                                <div>
                                    <div className="text-[11px] text-slate-400">Created</div>
                                    <p className="text-[13px] font-bold text-slate-900 mt-0.5">Mar 20, 2026</p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <div className="text-[13px] font-bold text-slate-900 mb-2.5">Update Status</div>
                            <div className="flex flex-wrap gap-2">
                                {['Pending','In Progress','Completed','Cancelled'].map((s, i) => (
                                    <button key={s} className={`px-3.5 py-1.5 rounded-[20px] text-xs font-semibold border transition
                                        ${i === 0
                                            ? 'bg-[#02122b] text-white border-[#02122b]'
                                            : 'bg-white text-slate-600 border-slate-300 hover:border-slate-500'}`}>
                                        {s}
                                    </button>
                                ))}
                            </div>
                        </div>

                        <div>
                            <label className="block text-[13px] font-bold text-slate-900 mb-2">Assign Staff</label>
                            <input type="text" placeholder="e.g. Bobby Cruz" className="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-[13px] outline-none" />
                        </div>

                        <div>
                            <label className="block text-[13px] font-bold text-slate-900 mb-2">Schedule Date</label>
                            <input type="text" placeholder="mm/dd/yyyy" className="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-[13px] outline-none" />
                        </div>
                    </div>

                    <div className="p-5 border-t border-slate-100 flex items-center justify-between gap-3">
                        <div className="flex items-center gap-2.5">
                            <button className="bg-emerald-600 text-white px-4 py-2.5 rounded-lg text-[13px] font-semibold inline-flex items-center gap-1.5">
                                <i className="fa-regular fa-circle-check"></i> Update
                            </button>
                            <button className="text-rose-600 text-[13px] font-semibold px-3 py-2">Delete</button>
                        </div>
                        <button onClick={() => setShowDetails(false)} className="bg-[#02122c] text-white px-4 py-2.5 rounded-lg text-[13px] font-semibold">
                            Close
                        </button>
                    </div>
                </Modal>
            )}
        </>
    );
}