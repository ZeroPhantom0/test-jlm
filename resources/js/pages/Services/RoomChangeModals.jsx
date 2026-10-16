// resources/js/pages/Services/RoomChangeModals.jsx
import React, { useState, useEffect } from 'react';
import Modal from '../../components/Modal';

const csrf = () =>
    document.querySelector('meta[name="csrf-token"]')?.content ||
    document.querySelector('input[name="_token"]')?.value;

export default function RoomChangeModals() {
    const [showSubmit, setShowSubmit]     = useState(false);
    const [showDetails, setShowDetails]   = useState(false);
    const [request, setRequest]           = useState(null);
    const [saving, setSaving]             = useState(false);
    const [requestsData, setRequestsData] = useState({});
    const [currentRoom, setCurrentRoom]   = useState('');

    // Form state for submit
    const [form, setForm] = useState({
        requested_room: '',
        reason:         '',
    });

    // ============================================================
    // Read data attributes from the mount element
    // ============================================================
    useEffect(() => {
        const el = document.getElementById('services-room-change-root');
        if (!el) return;

        // Current room from the row
        setCurrentRoom(el.dataset.currentRoom || '');

        // Requests map
        if (el.dataset.requests) {
            try {
                setRequestsData(JSON.parse(el.dataset.requests));
            } catch (e) {
                console.error('Invalid requests JSON', e);
            }
        }
    }, []);

    // ============================================================
    // Event delegation
    // ============================================================
    useEffect(() => {
        const handler = (e) => {
            // Open submit modal
            if (e.target.closest('#open-new-room-change')) {
                setForm({ requested_room: '', reason: '' });
                setShowSubmit(true);
                return;
            }

            // Open details modal
            const viewBtn = e.target.closest('.view-room-change-details-btn');
            if (viewBtn) {
                const id = viewBtn.dataset.requestId;
                const data = requestsData[id];
                if (data) {
                    setRequest(data);
                    setShowDetails(true);
                }
                return;
            }
        };

        document.addEventListener('click', handler);
        return () => document.removeEventListener('click', handler);
    }, [requestsData]);

    const updateField = (key, value) => setForm((f) => ({ ...f, [key]: value }));

    // ============================================================
    // Submit new room change request
    // ============================================================
    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!form.requested_room.trim() || !form.reason.trim()) {
            alert('Please fill in both Requested Room and Reason.');
            return;
        }

        setSaving(true);
        try {
            const res = await fetch('/tenants/services/room-change', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                },
                body: JSON.stringify({
                    current_room:   currentRoom,
                    requested_room: form.requested_room,
                    reason:         form.reason,
                }),
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Failed to submit request.');
            setShowSubmit(false);
            window.location.reload();
        } catch (err) {
            alert(err.message);
        } finally {
            setSaving(false);
        }
    };

    // ============================================================
    // Cancel a pending request
    // ============================================================
    const handleCancelRequest = async () => {
        if (!request) return;
        if (!confirm('Cancel this room change request?')) return;

        setSaving(true);
        try {
            const res = await fetch(`/tenants/services/room-change/${request.id}/cancel`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                },
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(data.message || 'Failed to cancel request.');
            setShowDetails(false);
            window.location.reload();
        } catch (err) {
            alert(err.message);
        } finally {
            setSaving(false);
        }
    };

    return (
        <>
            {/* ============================================================ */}
            {/* MODAL 1: Submit Room Change Request */}
            {/* ============================================================ */}
            {showSubmit && (
                <Modal onClose={() => setShowSubmit(false)} maxWidth="max-w-[480px]">
                    <div className="p-5 pb-4 flex items-center justify-between border-b border-slate-100">
                        <h3 className="text-lg font-bold text-slate-900">Submit Room Change Request</h3>
                        <button
                            onClick={() => setShowSubmit(false)}
                            className="text-slate-400 hover:text-slate-700 text-lg"
                        >
                            <i className="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <form onSubmit={handleSubmit}>
                        <div className="p-6 overflow-y-auto flex flex-col gap-4">

                            {/* Info banner */}
                            <div className="bg-blue-50 rounded-lg p-3 flex gap-2.5 text-[13px] text-blue-600 leading-snug">
                                <i className="fa-regular fa-circle-info mt-0.5"></i>
                                <span>Request a room change by providing your desired unit and reason.</span>
                            </div>

                            {/* Current Room */}
                            <div>
                                <label className="block text-[12.5px] font-semibold text-slate-700 mb-1.5">
                                    Current Room / Bed
                                </label>
                                <input
                                    type="text"
                                    value={currentRoom}
                                    readOnly
                                    className="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2.5 text-[13px] text-slate-600 cursor-not-allowed"
                                />
                            </div>

                            {/* Requested Room */}
                            <div>
                                <label className="block text-[12.5px] font-semibold text-slate-700 mb-1.5">
                                    Requested Room <span className="text-rose-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    value={form.requested_room}
                                    onChange={(e) => updateField('requested_room', e.target.value)}
                                    placeholder="e.g. 301"
                                    className="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2.5 text-[13px] outline-none focus:border-slate-400"
                                />
                            </div>

                            {/* Reason */}
                            <div>
                                <label className="block text-[12.5px] font-semibold text-slate-700 mb-1.5">
                                    Reason <span className="text-rose-500">*</span>
                                </label>
                                <textarea
                                    value={form.reason}
                                    onChange={(e) => updateField('reason', e.target.value)}
                                    placeholder="Explain why you need to change rooms..."
                                    className="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2.5 text-[13px] outline-none resize-none h-[90px] focus:border-slate-400"
                                ></textarea>
                            </div>
                        </div>

                        <div className="p-5 border-t border-slate-100 flex justify-end items-center gap-3">
                            <button
                                type="button"
                                onClick={() => setShowSubmit(false)}
                                className="text-slate-600 text-[13px] font-medium px-4 py-2"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                disabled={saving}
                                className="bg-[#001838] hover:bg-[#0d2347] disabled:opacity-50 text-white px-4 py-2.5 rounded-lg text-[13px] font-medium"
                            >
                                {saving ? 'Submitting…' : 'Submit Request'}
                            </button>
                        </div>
                    </form>
                </Modal>
            )}

            {/* ============================================================ */}
            {/* MODAL 2: Room Change Details */}
            {/* ============================================================ */}
            {showDetails && request && (
                <Modal onClose={() => setShowDetails(false)} maxWidth="max-w-[480px]">
                    <div className="p-5 pb-4 flex items-center justify-between border-b border-slate-100">
                        <div className="flex items-center gap-3">
                            <h3 className="text-lg font-bold text-slate-900">Room Change Request</h3>
                            <span className={`text-[11px] font-semibold px-2.5 py-0.5 rounded-full inline-flex items-center gap-1 ${
                                request.status === 'Pending'
                                    ? 'bg-amber-100 text-amber-700'
                                    : request.status === 'Approved'
                                    ? 'bg-emerald-100 text-emerald-600'
                                    : 'bg-rose-100 text-rose-600'
                            }`}>
                                <i className="fa-regular fa-clock"></i> {request.status}
                            </span>
                        </div>
                        <button
                            onClick={() => setShowDetails(false)}
                            className="text-slate-400 hover:text-slate-700 text-lg"
                        >
                            <i className="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div className="p-6 overflow-y-auto flex flex-col gap-4">

                        {/* Reference */}
                        <div className="text-xs text-slate-400 -mt-1">{request.ref}</div>

                        {/* Details grid */}
                        <div className="grid grid-cols-2 gap-3">
                            <div className="bg-slate-50 rounded-lg p-3">
                                <div className="text-[11px] text-slate-400 mb-1">Current Room / Bed ID</div>
                                <div className="text-[13px] font-semibold text-slate-900">{request.current_room}</div>
                            </div>
                            <div className="bg-slate-50 rounded-lg p-3">
                                <div className="text-[11px] text-slate-400 mb-1">Requested Room</div>
                                <div className="text-[13px] font-semibold text-slate-900">{request.requested_room}</div>
                            </div>
                            <div className="bg-slate-50 rounded-lg p-3">
                                <div className="text-[11px] text-slate-400 mb-1">Submitted</div>
                                <div className="text-[13px] font-semibold text-slate-900">{request.submitted}</div>
                            </div>
                            <div className="bg-slate-50 rounded-lg p-3">
                                <div className="text-[11px] text-slate-400 mb-1">Availability</div>
                                <div className="text-[13px] font-semibold text-slate-900">{request.availability}</div>
                            </div>
                        </div>

                        {/* Reason */}
                        <div className="bg-slate-50 rounded-lg p-3">
                            <div className="text-[11px] text-slate-400 mb-1">Reason:</div>
                            <div className="text-[13px] text-slate-700">{request.reason}</div>
                        </div>
                    </div>

                    {/* Footer */}
                    <div className="p-5 border-t border-slate-100 flex justify-between items-center">
                        {request.status === 'Pending' && (
                            <button
                                onClick={handleCancelRequest}
                                disabled={saving}
                                className="border border-red-300 text-red-500 hover:bg-red-50 disabled:opacity-50 rounded-lg px-3.5 py-2 text-[12.5px] font-medium inline-flex items-center gap-1.5"
                            >
                                <i className="fa-regular fa-circle-xmark"></i>
                                Cancel Request
                            </button>
                        )}
                        <button
                            onClick={() => setShowDetails(false)}
                            className="bg-[#001838] hover:bg-[#0d2347] text-white px-4 py-2 rounded-lg text-[12.5px] font-medium ml-auto"
                        >
                            Close
                        </button>
                    </div>
                </Modal>
            )}
        </>
    );
}