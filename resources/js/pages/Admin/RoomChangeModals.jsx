// resources/js/pages/Admin/RoomChangeModals.jsx
import React, { useState, useEffect } from "react";
import Modal from "../../components/Modal";

const csrf = () =>
    document.querySelector('meta[name="csrf-token"]')?.content ||
    document.querySelector('input[name="_token"]')?.value;

// Convert array OR object map into a { String(id): row } map
const toKeyedMap = (input) => {
    if (!input) return {};
    if (Array.isArray(input)) {
        const map = {};
        input.forEach((r) => {
            if (r && r.id !== undefined && r.id !== null) map[String(r.id)] = r;
        });
        return map;
    }
    if (typeof input === "object") {
        const map = {};
        Object.entries(input).forEach(([k, v]) => {
            if (v && typeof v === "object") map[String(k)] = v;
        });
        return map;
    }
    return {};
};

export default function RoomChangeModals() {
    const [showDetails, setShowDetails] = useState(false);
    const [showDelete, setShowDelete] = useState(false);
    const [request, setRequest] = useState(null);
    const [deleteTarget, setDeleteTarget] = useState(null);
    const [adminNotes, setAdminNotes] = useState("");
    const [saving, setSaving] = useState(false);
    const [requestsData, setRequestsData] = useState({});

    // ----- Confirmation modal state -----
    const [showApprove, setShowApprove] = useState(false);
    const [showReject, setShowReject] = useState(false);
    const [actionTarget, setActionTarget] = useState(null);
    const [rejectNotes, setRejectNotes] = useState("");

    // ============================================================
    // LIVE DATA — read once, then listen for polling/Echo updates
    // ============================================================
    const readRequestsFromDom = () => {
        const el = document.getElementById("admin-room-change-modals");
        if (!el?.dataset?.requests) return;
        try {
            const parsed = JSON.parse(el.dataset.requests);
            setRequestsData(toKeyedMap(parsed));
        } catch (e) {
            console.error("Invalid requests JSON", e);
        }
    };

    useEffect(() => {
        readRequestsFromDom();

        const handler = (e) => {
            const fresh = e?.detail;
            if (fresh) {
                setRequestsData(toKeyedMap(fresh));
            } else {
                readRequestsFromDom();
            }
        };

        window.addEventListener("room-change:data-updated", handler);
        return () =>
            window.removeEventListener("room-change:data-updated", handler);
    }, []);

    // Sync open request with fresh data
    useEffect(() => {
        if (!request) return;
        const fresh = requestsData[String(request.id)];
        if (!fresh) return;
        if (
            fresh.status !== request.status ||
            fresh.admin_notes !== request.admin_notes ||
            fresh.current_room !== request.current_room ||
            fresh.requested_room !== request.requested_room
        ) {
            setRequest(fresh);
            if (fresh.admin_notes !== undefined && !saving) {
                setAdminNotes(fresh.admin_notes || "");
            }
        }
    }, [requestsData]); // eslint-disable-line react-hooks/exhaustive-deps

    // ============================================================
    // Event delegation for row actions
    // ============================================================
    useEffect(() => {
        const handler = (e) => {
            // View details
            const viewBtn = e.target.closest(".open-details-btn");
            if (viewBtn) {
                const id = String(viewBtn.dataset.requestId);
                const data = requestsData[id];
                if (data) {
                    setRequest(data);
                    setAdminNotes(data.admin_notes || "");
                    setShowDetails(true);
                } else {
                    console.warn(
                        "[RoomChangeModals] No data for id",
                        id,
                        requestsData,
                    );
                }
                return;
            }

            // Approve — from row
            const approveBtn = e.target.closest(".icon-approve");
            if (approveBtn) {
                const id = String(approveBtn.dataset.requestId);
                const data = requestsData[id];
                if (data) {
                    setActionTarget(data);
                    setShowApprove(true);
                } else {
                    console.warn(
                        "[RoomChangeModals] No data for approve id",
                        id,
                    );
                }
                return;
            }

            // Reject — from row
            const rejectBtn = e.target.closest(".icon-reject");
            if (rejectBtn) {
                const id = String(rejectBtn.dataset.requestId);
                const data = requestsData[id];
                if (data) {
                    setActionTarget(data);
                    setRejectNotes("");
                    setShowReject(true);
                } else {
                    console.warn(
                        "[RoomChangeModals] No data for reject id",
                        id,
                    );
                }
                return;
            }
        };

        document.addEventListener("click", handler);
        return () => document.removeEventListener("click", handler);
    }, [requestsData]);

    const closeModal = () => {
        setShowDetails(false);
        setRequest(null);
        setAdminNotes("");
    };

    // ============================================================
    // Actions from within the details modal
    // ============================================================
    const handleApprove = () => {
        if (!request) return;
        setActionTarget({ ...request, admin_notes: adminNotes });
        setShowApprove(true);
    };

    const handleReject = () => {
        if (!request) return;
        setActionTarget({ ...request, admin_notes: adminNotes });
        setRejectNotes("");
        setShowReject(true);
    };

    // ============================================================
    // Actual API calls
    // ============================================================
    const confirmApprove = async () => {
        if (!actionTarget) return;
        setSaving(true);
        try {
            const res = await fetch(
                `/admin/services/room-change/${actionTarget.id}/approve`,
                {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        "X-CSRF-TOKEN": csrf(),
                    },
                    body: JSON.stringify({
                        admin_notes:
                            actionTarget.admin_notes || adminNotes || "",
                    }),
                },
            );
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || "Failed to approve.");

            setShowApprove(false);
            setActionTarget(null);
            closeModal();
            window.dispatchEvent(new CustomEvent("room-change:refresh"));
        } catch (err) {
            alert(err.message);
        } finally {
            setSaving(false);
        }
    };

    const confirmReject = async () => {
        if (!actionTarget) return;
        if (!rejectNotes.trim()) {
            alert("Please provide a reason for rejection.");
            return;
        }
        setSaving(true);
        try {
            const res = await fetch(
                `/admin/services/room-change/${actionTarget.id}/reject`,
                {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        "X-CSRF-TOKEN": csrf(),
                    },
                    body: JSON.stringify({ admin_notes: rejectNotes }),
                },
            );
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || "Failed to reject.");

            setShowReject(false);
            setActionTarget(null);
            setRejectNotes("");
            closeModal();
            window.dispatchEvent(new CustomEvent("room-change:refresh"));
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
        setRejectNotes("");
    };

    // ============================================================
    // Delete
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
            const res = await fetch(
                `/admin/services/room-change/${deleteTarget.id}`,
                {
                    method: "DELETE",
                    headers: {
                        Accept: "application/json",
                        "X-CSRF-TOKEN": csrf(),
                    },
                },
            );
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || "Failed to delete.");
            setShowDelete(false);
            setDeleteTarget(null);
            closeModal();
            window.dispatchEvent(new CustomEvent("room-change:refresh"));
        } catch (err) {
            alert(err.message);
        } finally {
            setSaving(false);
        }
    };

    // ============================================================
    // Render — when details closed, still render confirmation modals
    // ============================================================
    if (!showDetails || !request) {
        return (
            <>
                {showApprove && actionTarget && (
                    <ApproveRoomChangeModal
                        target={actionTarget}
                        saving={saving}
                        onClose={closeApproveModal}
                        onConfirm={confirmApprove}
                    />
                )}
                {showReject && actionTarget && (
                    <RejectRoomChangeModal
                        target={actionTarget}
                        saving={saving}
                        notes={rejectNotes}
                        onNotesChange={setRejectNotes}
                        onClose={closeRejectModal}
                        onConfirm={confirmReject}
                    />
                )}
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

    // ============================================================
    // Status + availability badge config
    // ============================================================
    const statusConfig = {
        Pending: {
            class: "bg-yellow-100 text-amber-700",
            icon: "fa-regular fa-clock",
        },
        Approved: {
            class: "bg-emerald-100 text-emerald-600",
            icon: "fa-regular fa-circle-check",
        },
        Rejected: {
            class: "bg-rose-100 text-rose-600",
            icon: "fa-regular fa-circle-xmark",
        },
    }[request.status] || {
        class: "bg-slate-100 text-slate-500",
        icon: "fa-regular fa-circle",
    };

    const isPending = request.status === "Pending";

    const availabilityConfig = {
        AVAILABLE: {
            class: "bg-emerald-100 text-emerald-600",
            label: "AVAILABLE",
        },
        OCCUPIED: { class: "bg-rose-100 text-rose-600", label: "OCCUPIED" },
        RESERVED: { class: "bg-indigo-100 text-indigo-700", label: "RESERVED" },
    }[request.availability] || {
        class: "bg-slate-100 text-slate-500",
        label: request.availability,
    };

    return (
        <>
            <Modal onClose={closeModal} maxWidth="max-w-[520px]">
                <div className="flex flex-col max-h-[90vh] sm:max-h-[85vh]">
                    {/* ============ HEADER ============ */}
                    <div className="p-4 sm:p-5 pb-4 flex items-center justify-between border-b border-slate-100 shrink-0">
                        <h3 className="text-base sm:text-lg font-bold text-slate-900 pr-4">
                            Room Change Request Details
                        </h3>
                        <button
                            onClick={closeModal}
                            className="text-slate-400 hover:text-slate-700 text-lg shrink-0"
                        >
                            <i className="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    {/* ============ BODY ============ */}
                    <div className="p-4 sm:p-6 overflow-y-auto flex flex-col gap-4 flex-1">
                        {/* Tenant + Status */}
                        <div className="flex items-start justify-between gap-3 flex-wrap">
                            <div className="min-w-0">
                                <div className="text-[16px] sm:text-[17px] font-bold text-slate-900 break-words">
                                    {request.tenant}
                                </div>
                                <div className="text-xs text-slate-400 mt-0.5 break-words">
                                    {request.reference_no}
                                </div>
                            </div>
                            <span
                                className={`inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold shrink-0 ${statusConfig.class}`}
                            >
                                <i className={statusConfig.icon}></i>{" "}
                                {request.status}
                            </span>
                        </div>

                        {/* Room Transfer Box */}
                        <div className="bg-slate-50 rounded-xl p-4 flex flex-col gap-2.5">
                            <div className="flex items-center justify-center gap-2 sm:gap-4">
                                <div className="flex-1 min-w-0 bg-white border border-slate-200 rounded-[10px] py-2.5 px-2 text-center">
                                    <div className="text-[11px] text-slate-400">
                                        Current Room
                                    </div>
                                    <div className="text-lg sm:text-xl font-extrabold text-slate-800 mt-1 break-words">
                                        {request.current_room}
                                    </div>
                                </div>
                                <i className="fa-solid fa-arrow-right text-slate-400 text-sm shrink-0"></i>
                                <div className="flex-1 min-w-0 bg-white border border-slate-200 rounded-[10px] py-2.5 px-2 text-center">
                                    <div className="text-[11px] text-slate-400">
                                        Requested Room
                                    </div>
                                    <div className="text-lg sm:text-xl font-extrabold text-indigo-700 mt-1 break-words">
                                        {request.requested_room}
                                    </div>
                                </div>
                            </div>
                            <span
                                className={`self-end text-[10px] font-bold px-2 py-0.5 rounded tracking-wide ${availabilityConfig.class}`}
                            >
                                {availabilityConfig.label}
                            </span>
                        </div>

                        {/* Reason */}
                        <div>
                            <div className="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">
                                Reason
                            </div>
                            <div className="bg-slate-50 rounded-xl p-4 text-[13.5px] text-slate-700 leading-snug break-words whitespace-pre-wrap">
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
                    <div className="p-4 sm:p-5 border-t border-slate-100 flex items-center gap-2.5 shrink-0 flex-wrap">
                        {isPending && (
                            <>
                                <button
                                    onClick={handleApprove}
                                    disabled={saving}
                                    className="bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white px-3.5 sm:px-4 py-2.5 rounded-lg text-[13px] font-semibold inline-flex items-center gap-1.5"
                                >
                                    <i className="fa-regular fa-circle-check"></i>
                                    Approve
                                </button>
                                <button
                                    onClick={handleReject}
                                    disabled={saving}
                                    className="bg-rose-50 hover:bg-rose-100 disabled:opacity-50 text-rose-600 px-3.5 sm:px-4 py-2.5 rounded-lg text-[13px] font-semibold inline-flex items-center gap-1.5"
                                >
                                    <i className="fa-regular fa-circle-xmark"></i>
                                    Reject
                                </button>
                            </>
                        )}
                        <button
                            onClick={openDeleteModal}
                            disabled={saving}
                            className="text-rose-600 hover:text-rose-700 disabled:opacity-50 text-[13px] font-semibold px-3 py-2 sm:ml-auto"
                        >
                            Delete
                        </button>
                        <button
                            onClick={closeModal}
                            className="bg-[#02122c] text-white px-4 py-2.5 rounded-lg text-[13px] font-semibold ml-auto sm:ml-0"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </Modal>

            {showApprove && actionTarget && (
                <ApproveRoomChangeModal
                    target={actionTarget}
                    saving={saving}
                    onClose={closeApproveModal}
                    onConfirm={confirmApprove}
                />
            )}

            {showReject && actionTarget && (
                <RejectRoomChangeModal
                    target={actionTarget}
                    saving={saving}
                    notes={rejectNotes}
                    onNotesChange={setRejectNotes}
                    onClose={closeRejectModal}
                    onConfirm={confirmReject}
                />
            )}

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

// ============================================================
// APPROVE
// ============================================================
function ApproveRoomChangeModal({ target, saving, onClose, onConfirm }) {
    return (
        <Modal onClose={onClose} maxWidth="max-w-[460px]">
            <div className="flex flex-col max-h-[90vh]">
                <div className="p-4 sm:p-5 pb-4 flex items-center justify-between border-b border-slate-100 shrink-0">
                    <h3 className="text-base sm:text-lg font-bold text-slate-900 pr-4">
                        Approve Room Change
                    </h3>
                    <button
                        onClick={onClose}
                        disabled={saving}
                        className="text-slate-400 hover:text-slate-700 text-lg disabled:opacity-50 shrink-0"
                    >
                        <i className="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div className="p-4 sm:p-6 text-center overflow-y-auto flex-1">
                    <div className="w-16 h-16 mx-auto rounded-full bg-emerald-50 flex items-center justify-center mb-4">
                        <i className="fa-solid fa-circle-check text-emerald-500 text-2xl"></i>
                    </div>
                    <h4 className="text-lg font-bold text-slate-900 mb-2">
                        Approve this room change?
                    </h4>
                    <p className="text-slate-500 text-[13px] mb-4">
                        This will transfer the tenant to the requested room and
                        update both rooms&rsquo; bed assignments.
                    </p>

                    <div className="bg-slate-50 rounded-xl p-4 text-left flex flex-col gap-2.5 mb-4">
                        <div className="flex justify-between text-xs gap-3">
                            <span className="text-slate-500 shrink-0">
                                Tenant
                            </span>
                            <span className="font-bold text-slate-900 text-right break-words">
                                {target.tenant}
                            </span>
                        </div>
                        <div className="flex justify-between text-xs gap-3">
                            <span className="text-slate-500 shrink-0">
                                Reference
                            </span>
                            <span className="font-bold text-slate-900 text-right break-words">
                                {target.reference_no}
                            </span>
                        </div>
                        <div className="flex justify-between text-xs gap-3">
                            <span className="text-slate-500 shrink-0">
                                Room Transfer
                            </span>
                            <span className="font-bold text-slate-900 text-right break-words">
                                {target.current_room}
                                <i className="fa-solid fa-arrow-right text-slate-400 text-[9px] mx-1.5"></i>
                                <span className="text-indigo-700">
                                    {target.requested_room}
                                </span>
                            </span>
                        </div>
                    </div>

                    <div className="bg-emerald-50 border border-emerald-200 rounded-xl p-3 text-left">
                        <p className="text-[12px] text-emerald-800 leading-snug">
                            <i className="fa-solid fa-circle-info mr-1"></i>
                            The tenant will be notified that their room change
                            request has been approved.
                        </p>
                    </div>
                </div>

                <div className="p-4 sm:p-5 border-t border-slate-100 flex justify-end gap-3 shrink-0 flex-wrap">
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
            </div>
        </Modal>
    );
}

// ============================================================
// REJECT
// ============================================================
function RejectRoomChangeModal({
    target,
    saving,
    notes,
    onNotesChange,
    onClose,
    onConfirm,
}) {
    return (
        <Modal onClose={onClose} maxWidth="max-w-[460px]">
            <div className="flex flex-col max-h-[90vh]">
                <div className="p-4 sm:p-5 pb-4 flex items-center justify-between border-b border-slate-100 shrink-0">
                    <h3 className="text-base sm:text-lg font-bold text-slate-900 pr-4">
                        Reject Room Change
                    </h3>
                    <button
                        onClick={onClose}
                        disabled={saving}
                        className="text-slate-400 hover:text-slate-700 text-lg disabled:opacity-50 shrink-0"
                    >
                        <i className="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div className="p-4 sm:p-6 overflow-y-auto flex-1">
                    <div className="text-center mb-4">
                        <div className="w-16 h-16 mx-auto rounded-full bg-rose-50 flex items-center justify-center mb-4">
                            <i className="fa-solid fa-circle-xmark text-rose-500 text-2xl"></i>
                        </div>
                        <h4 className="text-lg font-bold text-slate-900 mb-2">
                            Reject this room change?
                        </h4>
                        <p className="text-slate-500 text-[13px]">
                            The tenant will remain in their current room and
                            will be notified of the rejection.
                        </p>
                    </div>

                    <div className="bg-slate-50 rounded-xl p-4 text-left flex flex-col gap-2.5 mb-4">
                        <div className="flex justify-between text-xs gap-3">
                            <span className="text-slate-500 shrink-0">
                                Tenant
                            </span>
                            <span className="font-bold text-slate-900 text-right break-words">
                                {target.tenant}
                            </span>
                        </div>
                        <div className="flex justify-between text-xs gap-3">
                            <span className="text-slate-500 shrink-0">
                                Reference
                            </span>
                            <span className="font-bold text-slate-900 text-right break-words">
                                {target.reference_no}
                            </span>
                        </div>
                        <div className="flex justify-between text-xs gap-3">
                            <span className="text-slate-500 shrink-0">
                                Room Transfer
                            </span>
                            <span className="font-bold text-slate-900 text-right break-words">
                                {target.current_room}
                                <i className="fa-solid fa-arrow-right text-slate-400 text-[9px] mx-1.5"></i>
                                <span className="text-indigo-700">
                                    {target.requested_room}
                                </span>
                            </span>
                        </div>
                    </div>

                    <label className="block text-[13px] font-semibold text-slate-700 mb-1.5">
                        Reason for rejection{" "}
                        <span className="text-rose-500">*</span>
                    </label>
                    <textarea
                        value={notes}
                        onChange={(e) => onNotesChange(e.target.value)}
                        placeholder="Explain why this room change is being rejected..."
                        className="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 text-[13px] outline-none resize-y min-h-[90px] focus:border-slate-400"
                    />
                    <p className="text-[11px] text-slate-400 mt-1">
                        The tenant will see this reason on their room change
                        request details.
                    </p>
                </div>

                <div className="p-4 sm:p-5 border-t border-slate-100 flex justify-end gap-3 shrink-0 flex-wrap">
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
            </div>
        </Modal>
    );
}

// ============================================================
// DELETE
// ============================================================
function DeleteRoomChangeModal({ target, saving, onClose, onConfirm }) {
    return (
        <Modal onClose={onClose} maxWidth="max-w-[460px]">
            <div className="flex flex-col max-h-[90vh]">
                <div className="p-4 sm:p-5 pb-4 flex items-center justify-between border-b border-slate-100 shrink-0">
                    <h3 className="text-base sm:text-lg font-bold text-slate-900 pr-4">
                        Delete Room Change Request
                    </h3>
                    <button
                        onClick={onClose}
                        disabled={saving}
                        className="text-slate-400 hover:text-slate-700 text-lg disabled:opacity-50 shrink-0"
                    >
                        <i className="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div className="p-4 sm:p-6 text-center overflow-y-auto flex-1">
                    <div className="w-16 h-16 mx-auto rounded-full bg-rose-50 flex items-center justify-center mb-4">
                        <i className="fa-solid fa-trash-can text-rose-500 text-2xl"></i>
                    </div>
                    <h4 className="text-lg font-bold text-slate-900 mb-2">
                        Are you sure?
                    </h4>
                    <p className="text-slate-500 text-[13px] mb-2">
                        You are about to permanently delete:
                    </p>
                    <p className="font-bold text-slate-900 text-[15px] mb-1 break-words">
                        {target.tenant}
                    </p>
                    <p className="text-[13px] text-slate-600 mb-4 break-words">
                        <span className="font-semibold">
                            {target.current_room}
                        </span>
                        <i className="fa-solid fa-arrow-right text-slate-400 text-[10px] mx-2"></i>
                        <span className="font-semibold text-indigo-700">
                            {target.requested_room}
                        </span>
                        <span className="text-slate-400 ml-2">
                            ({target.reference_no})
                        </span>
                    </p>
                    <div className="bg-amber-50 border border-amber-200 rounded-xl p-3 text-left">
                        <p className="text-[12px] text-amber-800 leading-snug">
                            <i className="fa-solid fa-circle-info mr-1"></i>
                            This will permanently remove the room change request
                            and its history.{" "}
                            <strong>This action cannot be undone.</strong>
                        </p>
                    </div>
                </div>

                <div className="p-4 sm:p-5 border-t border-slate-100 flex justify-end gap-3 shrink-0 flex-wrap">
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
            </div>
        </Modal>
    );
}
