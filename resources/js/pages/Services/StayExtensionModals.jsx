// resources/js/pages/Services/StayExtensionModals.jsx
import React, { useState, useEffect } from 'react';
import Modal from '../../components/Modal';

const csrf = () =>
    document.querySelector('meta[name="csrf-token"]')?.content ||
    document.querySelector('input[name="_token"]')?.value;

export default function StayExtensionModals() {
    const [showSubmit, setShowSubmit]     = useState(false);
    const [showDetails, setShowDetails]   = useState(false);
    const [showDelete, setShowDelete]     = useState(false);
    const [request, setRequest]           = useState(null);
    const [deleteTarget, setDeleteTarget] = useState(null);
    const [saving, setSaving]             = useState(false);
    const [requestsData, setRequestsData] = useState({});
    const [roomNumber, setRoomNumber]     = useState('');
    const [currentLeaseEnd, setCurrentLeaseEnd] = useState('');
    const [minRequestedDate, setMinRequestedDate] = useState('');

    // Form state
    const [form, setForm] = useState({
        current_lease_end:   '',
        requested_lease_end: '',
        reason:              '',
    });

    // ============================================================
    // Read data attributes from mount element
    // ============================================================
    useEffect(() => {
        const el = document.getElementById('services-stay-extension-root');
        if (!el) return;

        setRoomNumber(el.dataset.roomNumber || '');
        setCurrentLeaseEnd(el.dataset.currentLeaseEnd || '');
        setMinRequestedDate(el.dataset.minRequestedDate || '');

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
            if (e.target.closest('#open-new-stay-extension')) {
                setForm({
                    current_lease_end:   currentLeaseEnd,
                    requested_lease_end: '',
                    reason:              '',
                });
                setShowSubmit(true);
                return;
            }

            // Open details modal
            const viewBtn = e.target.closest('.view-stay-extension-details-btn');
            if (viewBtn) {
                const id = viewBtn.dataset.requestId;
                const data = requestsData[id];
                if (data) {
                    setRequest(data);
                    setShowDetails(true);
                }
                return;
            }

            // Open Delete Modal (from row)
            const cancelBtn = e.target.closest('.cancel-stay-extension-btn');
            if (cancelBtn) {
                const id = cancelBtn.dataset.requestId;
                const data = requestsData[id];
                if (data) {
                    setDeleteTarget(data);
                    setShowDelete(true);
                }
                return;
            }
        };

        document.addEventListener('click', handler);
        return () => document.removeEventListener('click', handler);
    }, [requestsData, currentLeaseEnd]);

    const updateField = (key, value) => setForm((f) => ({ ...f, [key]: value }));

    // ============================================================
    // Submit new stay extension request
    // ============================================================
    const handleSubmit = async (e) => {
        e.preventDefault();

        if (!form.requested_lease_end.trim() || !form.reason.trim()) {
            alert('Please fill in all required fields.');
            return;
        }

        // Client-side check against minRequestedDate
        if (minRequestedDate && form.requested_lease_end < minRequestedDate) {
            alert('The requested lease end must be after your current check-out date.');
            return;
        }

        setSaving(true);
        try {
            const res = await fetch('/tenants/services/stay-extension', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                },
                body: JSON.stringify({
                    current_lease_end:   form.current_lease_end,
                    requested_lease_end: form.requested_lease_end,
                    reason:              form.reason,
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
    // Open Delete Modal (from Details footer)
    // ============================================================
    const openDeleteModal = () => {
        if (!request) return;
        setDeleteTarget(request);
        setShowDelete(true);
    };

    const closeDeleteModal = () => {
        if (saving) return;
        setShowDelete(false);
        setDeleteTarget(null);
    };

    // ============================================================
    // Confirm Cancel (Delete)
    // ============================================================
    const confirmCancel = async () => {
        if (!deleteTarget) return;
        setSaving(true);
        try {
            const res = await fetch(`/tenants/services/stay-extension/${deleteTarget.id}/cancel`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                },
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(data.message || 'Failed to cancel request.');

            setShowDelete(false);
            setDeleteTarget(null);
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
            {/* MODAL 1: Submit Stay Extension Request */}
            {/* ============================================================ */}
            {showSubmit && (
                <Modal onClose={() => setShowSubmit(false)} maxWidth="max-w-[480px]">
                    <div className="p-5 pb-4 flex items-center justify-between border-b border-slate-100">
                        <h3 className="text-lg font-bold text-slate-900">Submit Stay Extension Request</h3>
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
                            <div className="bg-emerald-50 rounded-lg p-3 flex gap-2.5 text-[13px] text-emerald-600 leading-snug">
                                <i className="fa-regular fa-circle-info mt-0.5"></i>
                                <span>Request to extend your lease by providing the new end date and reason.</span>
                            </div>

                            {/* Room Number (readonly) */}
                            <div>
                                <label className="block text-[12.5px] font-semibold text-slate-700 mb-1.5">
                                    Room Number
                                </label>
                                <input
                                    type="text"
                                    value={roomNumber}
                                    readOnly
                                    className="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2.5 text-[13px] text-slate-600 cursor-not-allowed"
                                />
                            </div>

                            {/* Lease Dates Row */}
                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="block text-[12.5px] font-semibold text-slate-700 mb-1.5">
                                        Current Lease End
                                    </label>
                                    <input
                                        type="text"
                                        value={currentLeaseEnd}
                                        readOnly
                                        className="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2.5 text-[13px] text-slate-600 cursor-not-allowed"
                                    />
                                </div>
                                <div>
                                    <label className="block text-[12.5px] font-semibold text-slate-700 mb-1.5">
                                        Requested Lease End <span className="text-rose-500">*</span>
                                    </label>
                                    <input
                                        type="date"
                                        value={form.requested_lease_end}
                                        min={minRequestedDate}
                                        onChange={(e) => updateField('requested_lease_end', e.target.value)}
                                        className="w-full bg-white border border-slate-200 rounded-lg px-3 py-2.5 text-[13px] outline-none focus:border-slate-400"
                                    />
                                    {minRequestedDate && (
                                        <p className="text-[11px] text-slate-400 mt-1">
                                            Must be after {currentLeaseEnd || 'your current check-out'}.
                                        </p>
                                    )}
                                </div>
                            </div>

                            {/* Reason */}
                            <div>
                                <label className="block text-[12.5px] font-semibold text-slate-700 mb-1.5">
                                    Reason <span className="text-rose-500">*</span>
                                </label>
                                <textarea
                                    value={form.reason}
                                    onChange={(e) => updateField('reason', e.target.value)}
                                    placeholder="Explain why you need to extend your stay..."
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
            {/* MODAL 2: Stay Extension Details */}
            {/* ============================================================ */}
            {showDetails && request && (
                <Modal onClose={() => setShowDetails(false)} maxWidth="max-w-[480px]">
                    <div className="p-5 pb-4 flex items-center justify-between border-b border-slate-100">
                        <div className="flex items-center gap-3">
                            <h3 className="text-lg font-bold text-slate-900">Stay Extension Request</h3>
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
                                <div className="text-[11px] text-slate-400 mb-1">Tenant</div>
                                <div className="text-[13px] font-semibold text-slate-900">{request.tenant}</div>
                            </div>
                            <div className="bg-slate-50 rounded-lg p-3">
                                <div className="text-[11px] text-slate-400 mb-1">Room / Bed ID</div>
                                <div className="text-[13px] font-semibold text-slate-900">{request.room}</div>
                            </div>
                            <div className="bg-slate-50 rounded-lg p-3">
                                <div className="text-[11px] text-slate-400 mb-1">Current Lease End</div>
                                <div className="text-[13px] font-semibold text-slate-900">{request.current_lease_end}</div>
                            </div>
                            <div className="bg-slate-50 rounded-lg p-3">
                                <div className="text-[11px] text-slate-400 mb-1">Requested Lease End</div>
                                <div className="text-[13px] font-semibold text-slate-900">{request.requested_lease_end}</div>
                            </div>
                        </div>

                        {/* Submitted (half width) */}
                        <div className="bg-slate-50 rounded-lg p-3 w-1/2">
                            <div className="text-[11px] text-slate-400 mb-1">Submitted</div>
                            <div className="text-[13px] font-semibold text-slate-900">{request.submitted}</div>
                        </div>

                        {/* Reason */}
                        <div className="bg-slate-50 rounded-lg p-3">
                            <div className="text-[11px] text-slate-400 mb-1">Reason:</div>
                            <div className="text-[13px] text-slate-700">{request.reason}</div>
                        </div>
                    </div>

                    {/* Footer */}
                    <div className="p-5 border-t border-slate-100 flex justify-end items-center gap-2">
                        {request.status === 'Pending' && (
                            <button
                                onClick={openDeleteModal}
                                disabled={saving}
                                className="border border-red-300 text-red-500 hover:bg-red-50 disabled:opacity-50 rounded-lg px-3.5 py-2 text-[12.5px] font-medium inline-flex items-center gap-1.5"
                            >
                                <i className="fa-regular fa-circle-xmark"></i>
                                Cancel Request
                            </button>
                        )}
                        <button
                            onClick={() => setShowDetails(false)}
                            className="bg-[#001838] hover:bg-[#0d2347] text-white px-4 py-2 rounded-lg text-[12.5px] font-medium"
                        >
                            Close
                        </button>
                    </div>
                </Modal>
            )}

            {/* ============================================================ */}
            {/* MODAL 3: Delete / Cancel Confirmation */}
            {/* ============================================================ */}
            {showDelete && deleteTarget && (
                <Modal onClose={closeDeleteModal} maxWidth="max-w-[460px]">
                    <div className="p-5 pb-4 flex items-center justify-between border-b border-slate-100">
                        <h3 className="text-lg font-bold text-slate-900">Cancel Stay Extension</h3>
                        <button
                            onClick={closeDeleteModal}
                            disabled={saving}
                            className="text-slate-400 hover:text-slate-700 text-lg disabled:opacity-50"
                        >
                            <i className="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div className="p-6 text-center">
                        <div className="w-16 h-16 mx-auto rounded-full bg-rose-50 flex items-center justify-center mb-4">
                            <i className="fa-solid fa-trash-can text-rose-500 text-2xl"></i>
                        </div>
                        <h4 className="text-lg font-bold text-slate-900 mb-2">Are you sure?</h4>
                        <p className="text-slate-500 text-[13px] mb-2">
                            You are about to cancel this stay extension request:
                        </p>
                        <p className="text-[13px] text-slate-600 mb-4">
                            <span className="font-semibold">{deleteTarget.current_lease_end}</span>
                            <i className="fa-solid fa-arrow-right text-slate-400 text-[10px] mx-2"></i>
                            <span className="font-semibold text-purple-700">{deleteTarget.requested_lease_end}</span>
                            <span className="text-slate-400 ml-2">({deleteTarget.ref})</span>
                        </p>
                        <div className="bg-amber-50 border border-amber-200 rounded-xl p-3 text-left">
                            <p className="text-[12px] text-amber-800 leading-snug">
                                <i className="fa-solid fa-circle-info mr-1"></i>
                                This will permanently remove the request. You will need to submit a new one if you change your mind.{' '}
                                <strong>This action cannot be undone.</strong>
                            </p>
                        </div>
                    </div>

                    <div className="p-5 border-t border-slate-100 flex justify-end gap-3">
                        <button
                            onClick={closeDeleteModal}
                            disabled={saving}
                            className="text-slate-600 text-[13.5px] font-semibold px-4 py-2 disabled:opacity-50"
                        >
                            Keep Request
                        </button>
                        <button
                            onClick={confirmCancel}
                            disabled={saving}
                            className="bg-rose-500 hover:bg-rose-600 disabled:opacity-50 text-white px-5 py-2.5 rounded-lg text-[13.5px] font-semibold inline-flex items-center gap-2 transition"
                        >
                            {saving ? (
                                <>
                                    <i className="fa-solid fa-spinner fa-spin"></i>
                                    Cancelling…
                                </>
                            ) : (
                                <>
                                    <i className="fa-solid fa-ban"></i>
                                    Yes, Cancel
                                </>
                            )}
                        </button>
                    </div>
                </Modal>
            )}
        </>
    );
}