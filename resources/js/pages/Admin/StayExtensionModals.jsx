// resources/js/pages/Admin/StayExtensionModals.jsx
import React, { useState, useEffect } from 'react';
import Modal from '../../components/Modal';

const csrf = () =>
    document.querySelector('meta[name="csrf-token"]')?.content ||
    document.querySelector('input[name="_token"]')?.value;

export default function StayExtensionModals() {
    const [showDetails, setShowDetails] = useState(false);
    const [showDelete, setShowDelete] = useState(false);
    const [request, setRequest] = useState(null);
    const [deleteTarget, setDeleteTarget] = useState(null);
    const [adminNotes, setAdminNotes] = useState('');
    const [saving, setSaving] = useState(false);
    const [requestsData, setRequestsData] = useState({});

    // ----- Confirmation modal state -----
    const [showApprove, setShowApprove] = useState(false);
    const [showReject, setShowReject] = useState(false);
    const [actionTarget, setActionTarget] = useState(null);
    const [rejectNotes, setRejectNotes] = useState('');

    // ============================================================
    // Read requests data from the DOM (passed by Blade)
    // ============================================================
    useEffect(() => {
        const el = document.getElementById('admin-stay-extension-modals');
        if (el?.dataset?.requests) {
            try {
                setRequestsData(JSON.parse(el.dataset.requests));
            } catch (e) {
                console.error('Invalid requests JSON', e);
            }
        }
    }, []);

    // ============================================================
    // Event delegation for opening modals
    // ============================================================
    useEffect(() => {
        const handler = (e) => {
            // View details
            const viewBtn = e.target.closest('.open-details-btn');
            if (viewBtn) {
                const id = viewBtn.dataset.requestId;
                const data = requestsData[id];
                if (data) {
                    setRequest(data);
                    setAdminNotes(data.admin_notes || '');
                    setShowDetails(true);
                }
                return;
            }

            // Approve — from row (opens confirmation modal)
            const approveBtn = e.target.closest('.row-approve-btn');
            if (approveBtn) {
                const id = approveBtn.dataset.requestId;
                const data = requestsData[id];
                if (data) {
                    setActionTarget(data);
                    setShowApprove(true);
                }
                return;
            }

            // Reject — from row (opens confirmation modal)
            const rejectBtn = e.target.closest('.row-reject-btn');
            if (rejectBtn) {
                const id = rejectBtn.dataset.requestId;
                const data = requestsData[id];
                if (data) {
                    setActionTarget(data);
                    setRejectNotes('');
                    setShowReject(true);
                }
                return;
            }
        };

        document.addEventListener('click', handler);
        return () => document.removeEventListener('click', handler);
    }, [requestsData]);

    const closeModal = () => {
        setShowDetails(false);
        setRequest(null);
        setAdminNotes('');
    };

    // ============================================================
    // Actions from within the details modal — open confirmation
    // ============================================================
    const handleApprove = () => {
        if (!request) return;
        setActionTarget({ ...request, admin_notes: adminNotes });
        setShowApprove(true);
    };

    const handleReject = () => {
        if (!request) return;
        setActionTarget({ ...request, admin_notes: adminNotes });
        setRejectNotes('');
        setShowReject(true);
    };

    // ============================================================
    // Actual API calls (fired from confirmation modals)
    // ============================================================
    const confirmApprove = async () => {
        if (!actionTarget) return;
        setSaving(true);
        try {
            const res = await fetch(`/admin/services/stay-extension/${actionTarget.id}/approve`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                },
                body: JSON.stringify({ admin_notes: actionTarget.admin_notes || adminNotes || '' }),
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Failed to approve.');
            setShowApprove(false);
            setActionTarget(null);
            closeModal();
            window.location.reload();
        } catch (err) {
            alert(err.message);
        } finally {
            setSaving(false);
        }
    };

    const confirmReject = async () => {
        if (!actionTarget) return;
        if (!rejectNotes.trim()) {
            alert('Please provide a reason for rejection.');
            return;
        }
        setSaving(true);
        try {
            const res = await fetch(`/admin/services/stay-extension/${actionTarget.id}/reject`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                },
                body: JSON.stringify({ admin_notes: rejectNotes }),
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Failed to reject.');
            setShowReject(false);
            setActionTarget(null);
            setRejectNotes('');
            closeModal();
            window.location.reload();
        } catch (err) {
            alert(err.message);
        } finally {
            setSaving(false);
        }
    };

    const closeApproveModal = () => {
        if (saving) return;
        setShowApprove(false);
        setActionTarget(null);
    };

    const closeRejectModal = () => {
        if (saving) return;
        setShowReject(false);
        setActionTarget(null);
        setRejectNotes('');
    };

    // ============================================================
    // Open delete confirmation
    // ============================================================
    const openDeleteModal = () => {
        if (!request) return;
        setDeleteTarget({
            id: request.id,
            tenant: request.tenant,
            reference_no: request.reference_no,
            current_end: request.current_end,
            requested_end: request.requested_end,
            extension_days: request.extension_days,
        });
        setShowDelete(true);
    };

    const closeDeleteModal = () => {
        if (saving) return;
        setShowDelete(false);
        setDeleteTarget(null);
    };

    const confirmDelete = async () => {
        if (!deleteTarget) return;
        setSaving(true);
        try {
            const res = await fetch(`/admin/services/stay-extension/${deleteTarget.id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                },
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Failed to delete.');
            setShowDelete(false);
            setDeleteTarget(null);
            closeModal();
            window.location.reload();
        } catch (err) {
            alert(err.message);
        } finally {
            setSaving(false);
        }
    };

    // ============================================================
    // Render — when details closed, still render all modals
    // ============================================================
    if (!showDetails || !request) {
        return (
            <>
                {showApprove && actionTarget && (
                    <ApproveStayExtensionModal
                        target={actionTarget}
                        saving={saving}
                        onClose={closeApproveModal}
                        onConfirm={confirmApprove}
                    />
                )}
                {showReject && actionTarget && (
                    <RejectStayExtensionModal
                        target={actionTarget}
                        saving={saving}
                        notes={rejectNotes}
                        onNotesChange={setRejectNotes}
                        onClose={closeRejectModal}
                        onConfirm={confirmReject}
                    />
                )}
                {showDelete && deleteTarget && (
                    <DeleteStayExtensionModal
                        target={deleteTarget}
                        saving={saving}
                        onClose={closeDeleteModal}
                        onConfirm={confirmDelete}
                    />
                )}
            </>
        );
    }

    // ============================================================
    // Status badge config
    // ============================================================
    const statusConfig = {
        Pending:  { class: 'bg-yellow-100 text-amber-700',    icon: 'fa-regular fa-clock' },
        Approved: { class: 'bg-emerald-100 text-emerald-600', icon: 'fa-regular fa-circle-check' },
        Rejected: { class: 'bg-rose-100 text-rose-600',       icon: 'fa-regular fa-circle-xmark' },
    }[request.status] || { class: 'bg-slate-100 text-slate-500', icon: 'fa-regular fa-circle' };

    const isPending = request.status === 'Pending';

    return (
        <>
            <Modal onClose={closeModal} maxWidth="max-w-[500px]">
                {/* ============ HEADER ============ */}
                <div className="p-5 pb-4 flex items-center justify-between border-b border-slate-100">
                    <h3 className="text-lg font-bold text-slate-900">Extension Request Details</h3>
                    <button
                        onClick={closeModal}
                        className="text-slate-400 hover:text-slate-700 text-lg"
                    >
                        <i className="fa-solid fa-xmark"></i>
                    </button>
                </div>

                {/* ============ BODY ============ */}
                <div className="p-6 overflow-y-auto flex flex-col gap-4">

                    {/* Tenant + Status */}
                    <div className="flex items-center justify-between">
                        <div>
                            <div className="text-[17px] font-bold text-slate-900">
                                {request.tenant}
                            </div>
                            <div className="text-xs text-slate-400 mt-0.5">
                                {request.reference_no}
                            </div>
                        </div>
                        <span className={`inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold ${statusConfig.class}`}>
                            <i className={statusConfig.icon}></i> {request.status}
                        </span>
                    </div>

                    {/* Lease Extension Card */}
                    <div className="bg-purple-50 border border-purple-100 rounded-2xl p-5 flex flex-col items-center gap-3">
                        <div className="flex justify-around w-full">
                            <div className="text-center">
                                <div className="text-[11px] text-purple-500 font-medium mb-1">
                                    Current Lease Ends
                                </div>
                                <div className="text-base font-extrabold text-slate-900">
                                    {request.current_end}
                                </div>
                            </div>
                            <div className="text-center">
                                <div className="text-[11px] text-purple-500 font-medium mb-1">
                                    Requested New End
                                </div>
                                <div className="text-base font-extrabold text-purple-700">
                                    {request.requested_end}
                                </div>
                            </div>
                        </div>
                        <span className="bg-purple-100 text-purple-700 text-[11px] font-bold px-3.5 py-1 rounded-xl">
                            {request.extension_days}
                        </span>
                    </div>

                    {/* Reason */}
                    <div>
                        <div className="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Reason</div>
                        <div className="bg-slate-50 rounded-xl p-4 text-[13.5px] text-slate-700 leading-snug">
                            {request.reason}
                        </div>
                    </div>

                    {/* Admin Notes */}
                    <div>
                        <div className="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">
                            Admin Notes
                        </div>
                        <textarea
                            value={adminNotes}
                            onChange={(e) => setAdminNotes(e.target.value)}
                            placeholder="Add internal notes here..."
                            className="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 text-[13px] outline-none resize-y min-h-[70px] focus:border-slate-400"
                        />
                    </div>
                </div>

                {/* ============ FOOTER ACTIONS ============ */}
                <div className="p-5 border-t border-slate-100 flex items-center gap-2.5">
                    {isPending && (
                        <>
                            <button
                                onClick={handleApprove}
                                disabled={saving}
                                className="bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white px-4 py-2.5 rounded-lg text-[13px] font-semibold inline-flex items-center gap-1.5"
                            >
                                <i className="fa-regular fa-circle-check"></i>
                                Approve
                            </button>
                            <button
                                onClick={handleReject}
                                disabled={saving}
                                className="bg-rose-50 hover:bg-rose-100 disabled:opacity-50 text-rose-600 px-4 py-2.5 rounded-lg text-[13px] font-semibold inline-flex items-center gap-1.5"
                            >
                                <i className="fa-regular fa-circle-xmark"></i>
                                Reject
                            </button>
                        </>
                    )}
                    <button
                        onClick={openDeleteModal}
                        disabled={saving}
                        className="text-rose-600 hover:text-rose-700 disabled:opacity-50 text-[13px] font-semibold px-3 py-2 ml-auto"
                    >
                        Delete
                    </button>
                    <button
                        onClick={closeModal}
                        className="bg-[#02122c] text-white px-4 py-2.5 rounded-lg text-[13px] font-semibold"
                    >
                        Close
                    </button>
                </div>
            </Modal>

            {/* ============================================================ */}
            {/* APPROVE CONFIRMATION MODAL */}
            {/* ============================================================ */}
            {showApprove && actionTarget && (
                <ApproveStayExtensionModal
                    target={actionTarget}
                    saving={saving}
                    onClose={closeApproveModal}
                    onConfirm={confirmApprove}
                />
            )}

            {/* ============================================================ */}
            {/* REJECT CONFIRMATION MODAL */}
            {/* ============================================================ */}
            {showReject && actionTarget && (
                <RejectStayExtensionModal
                    target={actionTarget}
                    saving={saving}
                    notes={rejectNotes}
                    onNotesChange={setRejectNotes}
                    onClose={closeRejectModal}
                    onConfirm={confirmReject}
                />
            )}

            {/* ============================================================ */}
            {/* DELETE CONFIRMATION MODAL */}
            {/* ============================================================ */}
            {showDelete && deleteTarget && (
                <DeleteStayExtensionModal
                    target={deleteTarget}
                    saving={saving}
                    onClose={closeDeleteModal}
                    onConfirm={confirmDelete}
                />
            )}
        </>
    );
}

// ============================================================
// APPROVE CONFIRMATION SUBCOMPONENT
// ============================================================
function ApproveStayExtensionModal({ target, saving, onClose, onConfirm }) {
    return (
        <Modal onClose={onClose} maxWidth="max-w-[460px]">
            <div className="p-5 pb-4 flex items-center justify-between border-b border-slate-100 shrink-0">
                <h3 className="text-lg font-bold text-slate-900">Approve Extension</h3>
                <button
                    onClick={onClose}
                    disabled={saving}
                    className="text-slate-400 hover:text-slate-700 text-lg disabled:opacity-50"
                >
                    <i className="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div className="p-6 text-center overflow-y-auto max-h-[60vh]">
                <div className="w-16 h-16 mx-auto rounded-full bg-emerald-50 flex items-center justify-center mb-4">
                    <i className="fa-solid fa-circle-check text-emerald-500 text-2xl"></i>
                </div>
                <h4 className="text-lg font-bold text-slate-900 mb-2">
                    Approve this stay extension?
                </h4>
                <p className="text-slate-500 text-[13px] mb-4">
                    This will extend the tenant&rsquo;s lease to the requested end date and update their billing cycle.
                </p>

                <div className="bg-slate-50 rounded-xl p-4 text-left flex flex-col gap-2.5 mb-4">
                    <div className="flex justify-between text-xs">
                        <span className="text-slate-500">Tenant</span>
                        <span className="font-bold text-slate-900">{target.tenant}</span>
                    </div>
                    <div className="flex justify-between text-xs">
                        <span className="text-slate-500">Reference</span>
                        <span className="font-bold text-slate-900">{target.reference_no}</span>
                    </div>
                    <div className="flex justify-between text-xs">
                        <span className="text-slate-500">Extension</span>
                        <span className="font-bold text-purple-700">{target.extension_days}</span>
                    </div>
                    <div className="flex justify-between text-xs border-t border-slate-200 pt-2 mt-1">
                        <span className="text-slate-500">New Lease Ends</span>
                        <span className="font-bold text-emerald-600">{target.requested_end}</span>
                    </div>
                </div>

                <div className="bg-emerald-50 border border-emerald-200 rounded-xl p-3 text-left">
                    <p className="text-[12px] text-emerald-800 leading-snug">
                        <i className="fa-solid fa-circle-info mr-1"></i>
                        The tenant will be notified that their stay extension has been approved.
                    </p>
                </div>
            </div>

            <div className="p-5 border-t border-slate-100 flex justify-end gap-3 shrink-0">
                <button
                    onClick={onClose}
                    disabled={saving}
                    className="text-slate-600 text-[13.5px] font-semibold px-4 py-2 disabled:opacity-50"
                >
                    Cancel
                </button>
                <button
                    onClick={onConfirm}
                    disabled={saving}
                    className="bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white px-5 py-2.5 rounded-lg text-[13.5px] font-semibold inline-flex items-center gap-2 transition"
                >
                    {saving ? (
                        <>
                            <i className="fa-solid fa-spinner fa-spin"></i>
                            Approving…
                        </>
                    ) : (
                        <>
                            <i className="fa-solid fa-check"></i>
                            Yes, Approve
                        </>
                    )}
                </button>
            </div>
        </Modal>
    );
}

// ============================================================
// REJECT CONFIRMATION SUBCOMPONENT
// ============================================================
function RejectStayExtensionModal({ target, saving, notes, onNotesChange, onClose, onConfirm }) {
    return (
        <Modal onClose={onClose} maxWidth="max-w-[460px]">
            <div className="p-5 pb-4 flex items-center justify-between border-b border-slate-100 shrink-0">
                <h3 className="text-lg font-bold text-slate-900">Reject Extension</h3>
                <button
                    onClick={onClose}
                    disabled={saving}
                    className="text-slate-400 hover:text-slate-700 text-lg disabled:opacity-50"
                >
                    <i className="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div className="p-6 overflow-y-auto max-h-[60vh]">
                <div className="text-center mb-4">
                    <div className="w-16 h-16 mx-auto rounded-full bg-rose-50 flex items-center justify-center mb-4">
                        <i className="fa-solid fa-circle-xmark text-rose-500 text-2xl"></i>
                    </div>
                    <h4 className="text-lg font-bold text-slate-900 mb-2">
                        Reject this stay extension?
                    </h4>
                    <p className="text-slate-500 text-[13px]">
                        The tenant&rsquo;s lease will remain unchanged and they will be notified of the rejection.
                    </p>
                </div>

                <div className="bg-slate-50 rounded-xl p-4 text-left flex flex-col gap-2.5 mb-4">
                    <div className="flex justify-between text-xs">
                        <span className="text-slate-500">Tenant</span>
                        <span className="font-bold text-slate-900">{target.tenant}</span>
                    </div>
                    <div className="flex justify-between text-xs">
                        <span className="text-slate-500">Reference</span>
                        <span className="font-bold text-slate-900">{target.reference_no}</span>
                    </div>
                    <div className="flex justify-between text-xs">
                        <span className="text-slate-500">Requested End</span>
                        <span className="font-bold text-slate-900">{target.requested_end}</span>
                    </div>
                </div>

                <label className="block text-[13px] font-semibold text-slate-700 mb-1.5">
                    Reason for rejection <span className="text-rose-500">*</span>
                </label>
                <textarea
                    value={notes}
                    onChange={(e) => onNotesChange(e.target.value)}
                    placeholder="Explain why this stay extension is being rejected..."
                    className="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 text-[13px] outline-none resize-y min-h-[90px] focus:border-slate-400"
                />
                <p className="text-[11px] text-slate-400 mt-1">
                    The tenant will see this reason on their extension request details.
                </p>
            </div>

            <div className="p-5 border-t border-slate-100 flex justify-end gap-3 shrink-0">
                <button
                    onClick={onClose}
                    disabled={saving}
                    className="text-slate-600 text-[13.5px] font-semibold px-4 py-2 disabled:opacity-50"
                >
                    Cancel
                </button>
                <button
                    onClick={onConfirm}
                    disabled={saving || !notes.trim()}
                    className="bg-rose-500 hover:bg-rose-600 disabled:opacity-50 text-white px-5 py-2.5 rounded-lg text-[13.5px] font-semibold inline-flex items-center gap-2 transition"
                >
                    {saving ? (
                        <>
                            <i className="fa-solid fa-spinner fa-spin"></i>
                            Rejecting…
                        </>
                    ) : (
                        <>
                            <i className="fa-solid fa-ban"></i>
                            Yes, Reject
                        </>
                    )}
                </button>
            </div>
        </Modal>
    );
}

// ---------- Delete confirmation subcomponent ----------
function DeleteStayExtensionModal({ target, saving, onClose, onConfirm }) {
    return (
        <Modal onClose={onClose} maxWidth="max-w-[460px]">
            <div className="p-5 pb-4 flex items-center justify-between border-b border-slate-100">
                <h3 className="text-lg font-bold text-slate-900">Delete Stay Extension Request</h3>
                <button
                    onClick={onClose}
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
                    You are about to permanently delete:
                </p>
                <p className="font-bold text-slate-900 text-[15px] mb-1">
                    {target.tenant}
                </p>
                <p className="text-[13px] text-slate-600 mb-4">
                    <span className="font-semibold">{target.current_end}</span>
                    <i className="fa-solid fa-arrow-right text-slate-400 text-[10px] mx-2"></i>
                    <span className="font-semibold text-purple-700">{target.requested_end}</span>
                    <span className="text-slate-400 ml-2">({target.reference_no})</span>
                </p>
                <div className="bg-amber-50 border border-amber-200 rounded-xl p-3 text-left">
                    <p className="text-[12px] text-amber-800 leading-snug">
                        <i className="fa-solid fa-circle-info mr-1"></i>
                        This will permanently remove the stay extension request and its history. The tenant's lease will not be affected.{' '}
                        <strong>This action cannot be undone.</strong>
                    </p>
                </div>
            </div>

            <div className="p-5 border-t border-slate-100 flex justify-end gap-3">
                <button
                    onClick={onClose}
                    disabled={saving}
                    className="text-slate-600 text-[13.5px] font-semibold px-4 py-2 disabled:opacity-50"
                >
                    Cancel
                </button>
                <button
                    onClick={onConfirm}
                    disabled={saving}
                    className="bg-rose-500 hover:bg-rose-600 disabled:opacity-50 text-white px-5 py-2.5 rounded-lg text-[13.5px] font-semibold inline-flex items-center gap-2 transition"
                >
                    {saving ? (
                        <>
                            <i className="fa-solid fa-spinner fa-spin"></i>
                            Deleting…
                        </>
                    ) : (
                        <>
                            <i className="fa-solid fa-trash-can"></i>
                            Yes, Delete
                        </>
                    )}
                </button>
            </div>
        </Modal>
    );
}