// resources/js/pages/Services/RoomChangeModals.jsx
import React, { useState, useEffect } from 'react';

export default function RoomChangeModals() {
    const [showSubmit, setShowSubmit] = useState(false);
    const [showDetails, setShowDetails] = useState(false);

    useEffect(() => {
        const newBtn = document.getElementById('open-new-room-change');
        const viewBtn = document.getElementById('view-room-change-details');

        const openSubmit = () => setShowSubmit(true);
        const openDetails = () => setShowDetails(true);

        newBtn?.addEventListener('click', openSubmit);
        viewBtn?.addEventListener('click', openDetails);

        return () => {
            newBtn?.removeEventListener('click', openSubmit);
            viewBtn?.removeEventListener('click', openDetails);
        };
    }, []);

    return (
        <>
            {/* MODAL 1: Submit Room Change Request */}
            {showSubmit && (
                <div
                    className="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-[999] flex items-center justify-center p-4"
                    onClick={(e) => e.target === e.currentTarget && setShowSubmit(false)}
                >
                    <div className="bg-white rounded-2xl w-full max-w-[480px] p-6 shadow-2xl relative max-h-[90vh] overflow-y-auto">
                        <div className="flex justify-between items-start mb-4">
                            <h3 className="text-lg font-bold text-slate-900">Submit Room Change Request</h3>
                            <button onClick={() => setShowSubmit(false)} className="text-slate-400 hover:text-slate-600">
                                <i className="fa-solid fa-xmark"></i>
                            </button>
                        </div>

                        <div className="bg-blue-50 rounded-lg p-3 flex gap-2.5 text-[13px] text-blue-600 mb-4 leading-snug">
                            <i className="fa-regular fa-circle-info mt-0.5"></i>
                            <span>Request a room change by providing your desired unit and reason.</span>
                        </div>

                        <form onSubmit={(e) => { e.preventDefault(); setShowSubmit(false); }} className="space-y-3.5">
                            <div>
                                <label className="block text-[12.5px] font-semibold text-slate-700 mb-1.5">Current Room / Bed</label>
                                <input
                                    type="text"
                                    value="R101-B1-U"
                                    readOnly
                                    className="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2.5 text-[13px] text-slate-600 cursor-not-allowed"
                                />
                            </div>

                            <div>
                                <label className="block text-[12.5px] font-semibold text-slate-700 mb-1.5">
                                    Requested Room <span className="text-rose-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    placeholder="e.g. 301"
                                    className="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2.5 text-[13px] outline-none focus:border-slate-400"
                                />
                            </div>

                            <div>
                                <label className="block text-[12.5px] font-semibold text-slate-700 mb-1.5">
                                    Reason <span className="text-rose-500">*</span>
                                </label>
                                <textarea
                                    placeholder="Explain why you need to change rooms..."
                                    className="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2.5 text-[13px] outline-none resize-none h-[90px] focus:border-slate-400"
                                ></textarea>
                            </div>

                            <div className="flex justify-end items-center gap-3 pt-2">
                                <button type="button" onClick={() => setShowSubmit(false)} className="text-slate-600 text-[13px] font-medium px-4 py-2">
                                    Cancel
                                </button>
                                <button type="submit" className="bg-[#001838] hover:bg-[#0d2347] text-white px-4 py-2.5 rounded-lg text-[13px] font-medium">
                                    Submit Request
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* MODAL 2: Room Change Details */}
            {showDetails && (
                <div
                    className="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-[999] flex items-center justify-center p-4"
                    onClick={(e) => e.target === e.currentTarget && setShowDetails(false)}
                >
                    <div className="bg-white rounded-2xl w-full max-w-[480px] p-6 shadow-2xl relative max-h-[90vh] overflow-y-auto">
                        <button onClick={() => setShowDetails(false)} className="absolute top-5 right-5 text-slate-400 hover:text-slate-600">
                            <i className="fa-solid fa-xmark"></i>
                        </button>

                        <div className="flex justify-between items-start mb-1">
                            <h3 className="text-lg font-bold text-slate-900">Room Change Request</h3>
                            <span className="bg-amber-100 text-amber-700 text-[11px] font-semibold px-2.5 py-1 rounded-full inline-flex items-center gap-1">
                                <i className="fa-regular fa-clock"></i> Pending
                            </span>
                        </div>
                        <div className="text-xs text-slate-400 mb-4">rc-1</div>

                        <div className="grid grid-cols-2 gap-3 mb-3">
                            <div className="bg-slate-50 rounded-lg p-3">
                                <div className="text-[11px] text-slate-400 mb-1">Current Room / Bed ID</div>
                                <div className="text-[13px] font-semibold text-slate-900">R101-B1-U</div>
                            </div>
                            <div className="bg-slate-50 rounded-lg p-3">
                                <div className="text-[11px] text-slate-400 mb-1">Requested Room</div>
                                <div className="text-[13px] font-semibold text-slate-900">R301-B1-U</div>
                            </div>
                            <div className="bg-slate-50 rounded-lg p-3">
                                <div className="text-[11px] text-slate-400 mb-1">Submitted</div>
                                <div className="text-[13px] font-semibold text-slate-900">Mar 21, 2026</div>
                            </div>
                            <div className="bg-slate-50 rounded-lg p-3">
                                <div className="text-[11px] text-slate-400 mb-1">Availability</div>
                                <div className="text-[13px] font-semibold text-slate-900">Available</div>
                            </div>
                        </div>

                        <div className="bg-slate-50 rounded-lg p-3 mb-4">
                            <div className="text-[11px] text-slate-400 mb-1">Reason:</div>
                            <div className="text-[13px] text-slate-700">Need more space for family.</div>
                        </div>

                        <div className="flex justify-between items-center pt-2">
                            <button className="border border-red-300 text-red-500 hover:bg-red-50 rounded-lg px-3.5 py-2 text-[12.5px] font-medium inline-flex items-center gap-1.5">
                                <i className="fa-regular fa-circle-xmark"></i>
                                Cancel Request
                            </button>
                            <button
                                onClick={() => setShowDetails(false)}
                                className="bg-[#001838] hover:bg-[#0d2347] text-white px-4 py-2 rounded-lg text-[12.5px] font-medium"
                            >
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </>
    );
}