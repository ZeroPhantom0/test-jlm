// resources/js/pages/Admin/RoomChangeModals.jsx
import React, { useState, useEffect } from 'react';
import Modal from '../../components/Modal';

const csrf = () =>
    document.querySelector('meta[name="csrf-token"]')?.content ||
    document.querySelector('input[name="_token"]')?.value;

export default function RoomChangeModals() {
    const [showDetails, setShowDetails] = useState(false);
    const [showDelete, setShowDelete] = useState(false);
    const [request, setRequest] = useState(null);
    const [deleteTarget, setDeleteTarget] = useState(null);
    const [adminNotes, setAdminNotes] = useState('');
    const [saving, setSaving] = useState(false);
    const [requestsData, setRequestsData] = useState({});

    // ============================================================
    // Read requests data from the DOM (passed by Blade)
    // ============================================================
    useEffect(() => {
        const el = document.getElementById('admin-room-change-modals');
        if (el?.dataset?.requests) {
            try {
                setRequestsData(JSON.parse(el.dataset.requests));
            } catch (e) {
                console.error('Invalid requests JSON', e);
            }
        }
    }, []);

    // ============================================================
    // Event delegation for row actions
    // ============================================================
    useEffect(() => {
        const handler = (e) => {
            // View details — opens modal
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

            // Approve — from row (quick action)
            const approveBtn = e.target.closest('.icon-approve');
            if (approveBtn) {
                const id = approveBtn.dataset.requestId;
                const data = requestsData[id];
                if (!data) return;

                if (!confirm(`Approve room change for ${data.tenant}?`)) return;

                setSaving(true);
                fetch(`/admin/services/room-change/${id}/approve`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf(),
                    },
                    body: JSON.stringify({ admin_notes: '' }),
                })
                    .then(async (res) => {
                        const result = await res.json();
                        if (!res.ok) throw new Error(result.message || 'Failed to approve.');
                        window.location.reload();
                    })
                    .catch((err) => alert(err.message))
                    .finally(() => setSaving(false));
                return;
            }

            // Reject — from row (quick action)
            const rejectBtn = e.target.closest('.icon-reject');
            if (rejectBtn) {
                const id = rejectBtn.dataset.requestId;
                const data = requestsData[id];
                if (!data) return;

                if (!confirm(`Reject room change for ${data.tenant}?`)) return;

                setSaving(true);
                fetch(`/admin/services/room-change/${id}/reject`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf(),
                    },
                    body: JSON.stringify({ admin_notes: '' }),
                })
                    .then(async (res) => {
                        const result = await res.json();
                        if (!res.ok) throw new Error(result.message || 'Failed to reject.');
                        window.location.reload();
                    })
                    .catch((err) => alert(err.message))
                    .finally(() => setSaving(false));
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
    // Actions from within the modal
    // ============================================================
    const handleApprove = async () => {
        if (!request) return;
        setSaving(true);
        try {
            const res = await fetch(`/admin/services/room-change/${request.id}/approve`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                },
                body: JSON.stringify({ admin_notes: adminNotes }),
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Failed to approve.');
            closeModal();
            window.location.reload();
        } catch (err) {
            alert(err.message);
        } finally {
            setSaving(false);
        }
    };

    const handleReject = async () => {
        if (!request) return;
        setSaving(true);
        try {
            const res = await fetch(`/admin/services/room-change/${request.id}/reject`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                },
                body: JSON.stringify({ admin_notes: adminNotes }),
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Failed to reject.');
            closeModal();
            window.location.reload();
        } catch (err) {
            alert(err.message);
        } finally {
            setSaving(false);
        }
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
            current_room: request.current_room,
            requested_room: request.requested_room,
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
            const res = await fetch(`/admin/services/room-change/${deleteTarget.id}`, {
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

    if (!showDetails || !request) {
        // Still render the delete modal even when details closed
        return showDelete && deleteTarget ? (
            <DeleteRoomChangeModal
                target={deleteTarget}
                saving={saving}
                onClose={closeDeleteModal}
                onConfirm={confirmDelete}
            />
        ) : null;
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

    const availabilityConfig = {
        AVAILABLE: { class: 'bg-emerald-100 text-emerald-600', label: 'AVAILABLE' },
        OCCUPIED:  { class: 'bg-rose-100 text-rose-600',       label: 'OCCUPIED' },
        RESERVED:  { class: 'bg-indigo-100 text-indigo-700',   label: 'RESERVED' },
    }[request.availability] || { class: 'bg-slate-100 text-slate-500', label: request.availability };

    return (
        <>
            <Modal onClose={closeModal} maxWidth="max-w-[520px]">
                {/* ============ HEADER ============ */}
                <div className="p-5 pb-4 flex items-center justify-between border-b border-slate-100">
                    <h3 className="text-lg font-bold text-slate-900">Room Change Request Details</h3>
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

                    {/* Room Transfer Box */}
                    <div className="bg-slate-50 rounded-xl p-4 flex flex-col gap-2.5">
                        <div className="flex items-center justify-center gap-4">
                            <div className="flex-1 bg-white border border-slate-200 rounded-[10px] py-2.5 text-center">
                                <div className="text-[11px] text-slate-400">Current Room</div>
                                <div className="text-xl font-extrabold text-slate-800 mt-1">
                                    {request.current_room}
                                </div>
                            </div>
                            <i className="fa-solid fa-arrow-right text-slate-400 text-sm"></i>
                            <div className="flex-1 bg-white border border-slate-200 rounded-[10px] py-2.5 text-center">
                                <div className="text-[11px] text-slate-400">Requested Room</div>
                                <div className="text-xl font-extrabold text-indigo-700 mt-1">
                                    {request.requested_room}
                                </div>
                            </div>
                        </div>
                        <span className={`self-end text-[10px] font-bold px-2 py-0.5 rounded tracking-wide ${availabilityConfig.class}`}>
                            {availabilityConfig.label}
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
                                {saving ? 'Saving…' : 'Approve'}
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
            {/* DELETE CONFIRMATION MODAL */}
            {/* ============================================================ */}
            {showDelete && deleteTarget && (
                <DeleteRoomChangeModal
                    target={deleteTarget}
                    saving={saving}
                    onClose={closeDeleteModal}
                    onConfirm={confirmDelete}
                />
            )}
        </>
    );
}

// ---------- Delete confirmation subcomponent ----------
function DeleteRoomChangeModal({ target, saving, onClose, onConfirm }) {
    return (
        <Modal onClose={onClose} maxWidth="max-w-[460px]">
            <div className="p-5 pb-4 flex items-center justify-between border-b border-slate-100">
                <h3 className="text-lg font-bold text-slate-900">Delete Room Change Request</h3>
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
                    <span className="font-semibold">{target.current_room}</span>
                    <i className="fa-solid fa-arrow-right text-slate-400 text-[10px] mx-2"></i>
                    <span className="font-semibold text-indigo-700">{target.requested_room}</span>
                    <span className="text-slate-400 ml-2">({target.reference_no})</span>
                </p>
                <div className="bg-amber-50 border border-amber-200 rounded-xl p-3 text-left">
                    <p className="text-[12px] text-amber-800 leading-snug">
                        <i className="fa-solid fa-circle-info mr-1"></i>
                        This will permanently remove the room change request and its history.{' '}
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