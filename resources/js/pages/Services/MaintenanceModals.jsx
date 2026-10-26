// resources/js/pages/Services/MaintenanceModals.jsx
import React, { useState, useEffect } from 'react';
import Modal from '../../components/Modal';

export default function MaintenanceModals() {
    const [showNew, setShowNew] = useState(false);
    const [showDetails, setShowDetails] = useState(false);

    // Use event delegation on document — catches clicks on dynamically rendered icons
    useEffect(() => {
        const handleClick = (e) => {
            // "New Request" button
            if (e.target.closest('#open-new-request')) {
                setShowNew(true);
                return;
            }

            // Any "view details" eye icon (matches by class, id, or title)
            const eyeBtn = e.target.closest('.view-details-btn, #view-details-btn, [title="View"]');
            if (eyeBtn) {
                setShowDetails(true);
                return;
            }
        };

        document.addEventListener('click', handleClick);
        return () => document.removeEventListener('click', handleClick);
    }, []);

    return (
        <>
            {/* MODAL 1: Submit Maintenance Request */}
            {showNew && (
                <Modal onClose={() => setShowNew(false)} maxWidth="max-w-[500px]">
                    <div className="p-5 pb-4 flex items-center justify-between border-b border-slate-100">
                        <h3 className="text-base font-bold text-slate-900">Submit Maintenance Request</h3>
                        <button onClick={() => setShowNew(false)} className="text-slate-400 hover:text-slate-600 text-lg">
                            <i className="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div className="p-6 overflow-y-auto flex flex-col gap-4">
                        <div className="bg-amber-50 border border-amber-100 rounded-lg p-3 flex gap-2 text-xs text-amber-700">
                            <i className="fa-regular fa-circle-info mt-0.5"></i>
                            <span>Describe the maintenance issue clearly so our team can assist you promptly.</span>
                        </div>

                        <div>
                            <label className="block text-xs font-semibold text-slate-700 mb-1.5">
                                Issue Title <span className="text-rose-500">*</span>
                            </label>
                            <input
                                type="text"
                                placeholder="e.g. Leaky Faucet in Kitchen"
                                className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none focus:border-blue-400"
                            />
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <label className="block text-xs font-semibold text-slate-700 mb-1.5">Category</label>
                                <select className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none bg-white">
                                    <option>General</option>
                                    <option>Plumbing</option>
                                    <option>Electrical</option>
                                </select>
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-slate-700 mb-1.5">Priority</label>
                                <select className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none bg-white">
                                    <option>Critical</option>
                                    <option>Medium</option>
                                    <option>Low</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label className="block text-xs font-semibold text-slate-700 mb-1.5">
                                Description <span className="text-rose-500">*</span>
                            </label>
                            <textarea
                                placeholder="Describe the problem in detail..."
                                className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none resize-y min-h-[80px]"
                            ></textarea>
                        </div>
                    </div>

                    <div className="p-5 border-t border-slate-100 flex justify-end gap-3">
                        <button
                            onClick={() => setShowNew(false)}
                            className="text-slate-600 text-[13px] font-semibold px-4 py-2"
                        >
                            Cancel
                        </button>
                        <button
                            onClick={() => setShowNew(false)}
                            className="bg-[#02122c] hover:bg-[#0d2347] text-white px-4 py-2.5 rounded-lg text-[13px] font-semibold"
                        >
                            Submit Request
                        </button>
                    </div>
                </Modal>
            )}

            {/* MODAL 2: View Details */}
            {showDetails && (
                <Modal onClose={() => setShowDetails(false)} maxWidth="max-w-[500px]">
                    <div className="p-5 pb-4 flex items-center justify-between border-b border-slate-100">
                        <h3 className="text-base font-bold text-slate-900">Maintenance Request Details</h3>
                        <button onClick={() => setShowDetails(false)} className="text-slate-400 hover:text-slate-600 text-lg">
                            <i className="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div className="p-6 overflow-y-auto flex flex-col gap-4">
                        <div className="flex justify-between items-center">
                            <h4 className="text-[15px] font-bold text-slate-900">Leaky Faucet</h4>
                            <span className="bg-amber-100 text-amber-700 text-[11px] font-semibold px-2.5 py-1 rounded-full inline-flex items-center gap-1">
                                <i className="fa-regular fa-clock"></i> Pending
                            </span>
                        </div>
                        <div className="text-[11px] text-slate-400">sr-1</div>

                        <div className="bg-slate-50 rounded-lg p-3 text-[13px] text-slate-700">
                            Kitchen sink is dripping constantly.
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="bg-slate-50 rounded-lg p-3">
                                <div className="text-[10px] text-slate-400 mb-1">Category</div>
                                <div className="text-xs font-semibold text-slate-900">Plumbing</div>
                            </div>
                            <div className="bg-slate-50 rounded-lg p-3">
                                <div className="text-[10px] text-slate-400 mb-1">Priority</div>
                                <span className="bg-amber-100 text-amber-700 text-[9px] font-bold px-1.5 py-0.5 rounded uppercase">MEDIUM</span>
                            </div>
                            <div className="bg-slate-50 rounded-lg p-3">
                                <div className="text-[10px] text-slate-400 mb-1">Submitted</div>
                                <div className="text-xs font-semibold text-slate-900">Mar 20, 2026</div>
                            </div>
                            <div className="bg-slate-50 rounded-lg p-3">
                                <div className="text-[10px] text-slate-400 mb-1">Assigned Staff</div>
                                <div className="text-xs font-semibold text-slate-900">Not Yet Assigned</div>
                            </div>
                            <div className="bg-slate-50 rounded-lg p-3">
                                <div className="text-[10px] text-slate-400 mb-1">Scheduled</div>
                                <div className="text-xs font-semibold text-slate-400">—</div>
                            </div>
                            <div className="bg-slate-50 rounded-lg p-3">
                                <div className="text-[10px] text-slate-400 mb-1">Unit</div>
                                <div className="text-xs font-semibold text-slate-900">Room 101</div>
                            </div>
                        </div>
                    </div>

                    <div className="p-5 border-t border-slate-100 flex justify-end gap-3">
                        <button className="border border-red-300 text-red-500 hover:bg-red-50 rounded-lg px-4 py-2 text-xs font-semibold inline-flex items-center gap-1.5">
                            <i className="fa-regular fa-circle-xmark"></i>
                            Cancel Request
                        </button>
                        <button
                            onClick={() => setShowDetails(false)}
                            className="bg-[#02122c] hover:bg-[#0d2347] text-white px-4 py-2 rounded-lg text-[13px] font-semibold"
                        >
                            Close
                        </button>
                    </div>
                </Modal>
            )}
        </>
    );
}