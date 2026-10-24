// resources/js/pages/Admin/ReservationsModals.jsx
import React, { useState, useEffect, useCallback } from 'react';
import Modal from '../../components/Modal';

const csrf = () =>
    document.querySelector('meta[name="csrf-token"]')?.content ||
    document.querySelector('input[name="_token"]')?.value;

export default function ReservationsModals() {
    const [data, setData]                         = useState(null);
    const [adminNotes, setAdminNotes]             = useState('');
    const [saving, setSaving]                     = useState(false);
    const [reservationsData, setReservationsData] = useState({});

    // Confirmation modals state
    const [showApprove, setShowApprove]           = useState(false);
    const [showReject, setShowReject]             = useState(false);
    const [rejectNotes, setRejectNotes]           = useState('');
    const [actionTarget, setActionTarget]         = useState(null);

    useEffect(() => {
        const el = document.getElementById('admin-reservations-modals');
        if (el?.dataset?.reservations) {
            try {
                setReservationsData(JSON.parse(el.dataset.reservations));
            } catch (e) {
                console.error('Invalid reservations JSON', e);
            }
        }
    }, []);

    const sendAction = useCallback(async (id, action, body = {}) => {
        if (id == null || id === '' || id === 'undefined') {
            throw new Error('Missing reservation id.');
        }

        const url = action === 'delete'
            ? `/admin/reservations/${id}`
            : `/admin/reservations/${id}/${action}`;

        const res = await fetch(url, {
            method: action === 'delete' ? 'DELETE' : 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf(),
            },
            body: action === 'delete' ? undefined : JSON.stringify(body),
        });

        const result = await res.json().catch(() => ({}));
        if (!res.ok) throw new Error(result.message || `Failed to ${action}.`);
        return result;
    }, []);

    // ============================================================
    // Event Delegation
    // ============================================================
    useEffect(() => {
        const handler = (e) => {
            // View Details
            const viewBtn = e.target.closest('.icon-view[data-res-key]');
            if (viewBtn) {
                const key   = viewBtn.dataset.resKey;
                const found = reservationsData[key];
                if (found) {
                    setData(found);
                    setAdminNotes(found.adminNotes || '');
                }
                return;
            }

            // Open Approve Modal (Row Action)
            const approveBtn = e.target.closest('.icon-approve');
            if (approveBtn) {
                const id = approveBtn.dataset.resId;
                if (!id) return alert('Missing reservation id.');
                
                const found = Object.values(reservationsData).find(r => r.id == id);
                if (found) {
                    setActionTarget(found);
                    setShowApprove(true);
                }
                return;
            }

            // Open Reject Modal (Row Action)
            const rejectBtn = e.target.closest('.icon-reject');
            if (rejectBtn) {
                const id = rejectBtn.dataset.resId;
                if (!id) return alert('Missing reservation id.');
                
                const found = Object.values(reservationsData).find(r => r.id == id);
                if (found) {
                    setActionTarget(found);
                    setRejectNotes('');
                    setShowReject(true);
                }
                return;
            }
        };

        document.addEventListener('click', handler);
        return () => document.removeEventListener('click', handler);
    }, [reservationsData]);

    const closeModal = () => {
        if (saving) return;
        setData(null);
        setAdminNotes('');
    };

    // ============================================================
    // Actions
    // ============================================================
    const openApproveModal = () => {
        if (!data) return;
        setActionTarget(data);
        setShowApprove(true);
    };

    const openRejectModal = () => {
        if (!data) return;
        setActionTarget(data);
        setRejectNotes('');
        setShowReject(true);
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

    const confirmApprove = async () => {
        if (!actionTarget) return;
        setSaving(true);
        try {
            await sendAction(actionTarget.id, 'approve', { admin_notes: adminNotes });
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
            await sendAction(actionTarget.id, 'reject', { admin_notes: rejectNotes });
            setShowReject(false);
            setActionTarget(null);
            closeModal();
            window.location.reload();
        } catch (err) {
            alert(err.message);
        } finally {
            setSaving(false);
        }
    };

    const handleDelete = async () => {
        if (!data) return;
        if (data.id == null) {
            alert('Cannot delete: reservation id is missing.');
            return;
        }
        if (!confirm('Delete this reservation permanently?')) return;
        setSaving(true);
        try {
            await sendAction(data.id, 'delete');
            closeModal();
            window.location.reload();
        } catch (err) {
            alert(err.message);
        } finally {
            setSaving(false);
        }
    };

    // If data is missing, only render the confirmation modals if they are open
    if (!data) {
        return (
            <>
                {showApprove && actionTarget && (
                    <ApproveConfirmationModal
                        target={actionTarget}
                        saving={saving}
                        onClose={closeApproveModal}
                        onConfirm={confirmApprove}
                    />
                )}
                {showReject && actionTarget && (
                    <RejectConfirmationModal
                        target={actionTarget}
                        saving={saving}
                        notes={rejectNotes}
                        onNotesChange={setRejectNotes}
                        onClose={closeRejectModal}
                        onConfirm={confirmReject}
                    />
                )}
            </>
        );
    }

    const isPending = data.statusText === 'Pending';

    return (
        <>
            <Modal onClose={closeModal} maxWidth="max-w-[520px]">
                <div className="p-5 pb-4 flex items-center justify-between border-b border-slate-100">
                    <h2 className="text-lg font-bold text-slate-900">Reservation Details</h2>
                    <button onClick={closeModal} className="text-slate-400 hover:text-slate-700 text-lg">
                        <i className="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div className="p-6 overflow-y-auto flex flex-col gap-5">
                    {/* Header */}
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-3.5">
                            <div className={`w-12 h-12 rounded-full ${data.bg} ${data.color} flex items-center justify-center text-lg font-bold`}>
                                {data.initial}
                            </div>
                            <div>
                                <h3 className="text-base font-bold text-slate-900">{data.name}</h3>
                                <p className="text-xs text-slate-400 mt-0.5">{data.subCode}</p>
                            </div>
                        </div>
                        <span className={`inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold ${data.statusClass}`}>
                            <i className={data.statusIcon}></i> {data.statusText}
                        </span>
                    </div>

                    <div>
                        <div className="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Reservation Number</div>
                        <div className="text-[13px] font-bold text-teal-600">{data.resNumber}</div>
                    </div>

                    {/* Contact */}
                    <div className="bg-slate-50 rounded-xl p-4 flex flex-col gap-2.5">
                        <div className="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Contact Information</div>
                        <div className="flex items-center gap-2.5 text-[13px] text-slate-700">
                            <i className="fa-regular fa-envelope w-4 text-slate-400"></i> {data.email}
                        </div>
                        <div className="flex items-center gap-2.5 text-[13px] text-slate-700">
                            <i className="fa-solid fa-phone w-4 text-slate-400"></i> {data.phone}
                        </div>
                    </div>

                    {/* Reservation Details */}
                    <div className="bg-slate-50 rounded-xl p-4 flex flex-col gap-2.5">
                        <div className="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Reservation Details</div>
                        <div className="flex items-center gap-2.5 text-[13px] text-slate-700">
                            <i className="fa-solid fa-house w-4 text-slate-400"></i> {data.desiredRoom}
                        </div>
                        <div className="flex items-center gap-2.5 text-[13px] text-slate-700">
                            <i className="fa-solid fa-bed w-4 text-slate-400"></i> {data.bedId}
                        </div>
                        <div className="flex items-center gap-2.5 text-[13px] text-slate-700">
                            <i className="fa-solid fa-right-to-bracket w-4 text-slate-400"></i>
                            Check-in: {data.formattedCheckIn}
                        </div>
                        <div className="flex items-center gap-2.5 text-[13px] text-slate-700">
                            <i className="fa-solid fa-right-from-bracket w-4 text-slate-400"></i>
                            Check-out: {data.formattedCheckOut}
                        </div>
                        <div className="flex items-center gap-2.5 text-[13px] text-slate-700">
                            <i className="fa-regular fa-calendar-plus w-4 text-slate-400"></i> {data.submitted}
                        </div>
                        {data.stayDuration != null && (
                            <div className="flex items-center gap-2.5 text-[13px] text-slate-700">
                                <i className="fa-regular fa-clock w-4 text-slate-400"></i>
                                Stay: {data.stayPeriod} ({data.stayDuration} {data.stayDuration === 1 ? 'day' : 'days'})
                            </div>
                        )}
                    </div>

                    {/* Request Details */}
                    <div className="bg-slate-50 rounded-xl p-4">
                        <div className="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Request Details</div>
                        <p className="text-[13px] text-slate-700 leading-snug">{data.requestDetails || '—'}</p>
                        {data.hasCompanion && (
                            <p className="text-[11px] text-slate-500 mt-1.5">
                                <i className="fa-solid fa-user-group mr-1"></i> With companion (+₱150)
                            </p>
                        )}
                    </div>

                    {/* Charges */}
                    <div className="grid grid-cols-2 gap-3">
                        <div className="bg-[#02122b] text-white rounded-xl p-3.5">
                            <div className="text-[11px] text-slate-400">Total Charges</div>
                            <div className="text-base font-bold mt-1">{data.totalCharges}</div>
                        </div>
                        <div className="bg-emerald-50 rounded-xl p-3.5">
                            <div className="text-[11px] text-emerald-600 font-semibold">Payment Option</div>
                            <div className="text-base font-bold text-emerald-700 mt-1">{data.paymentOption}</div>
                        </div>
                        <div className="bg-slate-50 rounded-xl p-3.5">
                            <div className="text-[11px] text-slate-500">Remaining</div>
                            <div className="text-base font-bold text-slate-900 mt-1">{data.remaining}</div>
                        </div>
                        <div className="bg-slate-50 rounded-xl p-3.5">
                            <div className="text-[11px] text-slate-500">Payment Method</div>
                            <div className="text-base font-bold text-slate-900 mt-1">{data.paymentMethod}</div>
                        </div>
                    </div>

                    {/* Proof of Payment */}
                    <div>
                        <div className="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Uploaded Proof of Payment</div>
                        {data.proofUrl ? (
                            <a
                                href={data.proofUrl}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="block w-full rounded-xl overflow-hidden hover:opacity-90 transition"
                            >
                                <img
                                    src={data.proofUrl}
                                    alt="Proof of payment"
                                    className="w-full h-auto object-cover rounded-xl shadow-sm border border-slate-100"
                                />
                            </a>
                        ) : (
                            <div className="bg-slate-100 rounded-xl p-4 text-center text-[12px] text-slate-500 italic">
                                No proof of payment uploaded.
                            </div>
                        )}
                    </div>

                    {/* Admin Notes */}
                    <div>
                        <div className="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Admin Notes</div>
                        <textarea
                            value={adminNotes}
                            onChange={(e) => setAdminNotes(e.target.value)}
                            placeholder="Add notes..."
                            className="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 text-[13px] outline-none resize-y min-h-[60px] focus:border-slate-400"
                        />
                    </div>
                </div>

                {/* Footer */}
                <div className="p-5 border-t border-slate-100 flex items-center justify-end gap-3">
                    <button
                        onClick={handleDelete}
                        disabled={saving}
                        className="text-rose-600 hover:text-rose-700 disabled:opacity-50 text-[13px] font-semibold mr-auto"
                    >
                        Delete
                    </button>
                    {isPending && (
                        <>
                            <button
                                onClick={openApproveModal}
                                disabled={saving}
                                className="bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white px-4 py-2 rounded-lg text-[13px] font-semibold inline-flex items-center gap-1.5"
                            >
                                <i className="fa-regular fa-circle-check"></i> Approve
                            </button>
                            <button
                                onClick={openRejectModal}
                                disabled={saving}
                                className="bg-rose-50 hover:bg-rose-100 disabled:opacity-50 text-rose-600 px-4 py-2 rounded-lg text-[13px] font-semibold inline-flex items-center gap-1.5"
                            >
                                <i className="fa-regular fa-circle-xmark"></i> Reject
                            </button>
                        </>
                    )}
                    <button
                        onClick={closeModal}
                        className="bg-[#02122c] text-white px-4 py-2 rounded-lg text-[13px] font-semibold"
                    >
                        Close
                    </button>
                </div>
            </Modal>

            {/* Confirmation Modals */}
            {showApprove && actionTarget && (
                <ApproveConfirmationModal
                    target={actionTarget}
                    saving={saving}
                    onClose={closeApproveModal}
                    onConfirm={confirmApprove}
                />
            )}
            {showReject && actionTarget && (
                <RejectConfirmationModal
                    target={actionTarget}
                    saving={saving}
                    notes={rejectNotes}
                    onNotesChange={setRejectNotes}
                    onClose={closeRejectModal}
                    onConfirm={confirmReject}
                />
            )}
        </>
    );
}

// ============================================================
// Approve Confirmation Modal
// ============================================================
function ApproveConfirmationModal({ target, saving, onClose, onConfirm }) {
    return (
        <Modal onClose={onClose} maxWidth="max-w-[460px]">
            <div className="p-5 pb-4 flex items-center justify-between border-b border-slate-100 shrink-0">
                <h3 className="text-lg font-bold text-slate-900">Approve Reservation</h3>
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
                <h4 className="text-lg font-bold text-slate-900 mb-2">Approve this reservation?</h4>
                <p className="text-slate-500 text-[13px] mb-4">
                    This will confirm the reservation, assign the tenant to the room, and generate an invoice for the remaining balance.
                </p>

                <div className="bg-slate-50 rounded-xl p-4 text-left flex flex-col gap-2.5 mb-4">
                    <div className="flex justify-between text-xs">
                        <span className="text-slate-500">Tenant</span>
                        <span className="font-bold text-slate-900">{target.name}</span>
                    </div>
                    <div className="flex justify-between text-xs">
                        <span className="text-slate-500">Room</span>
                        <span className="font-bold text-slate-900">{target.room}</span>
                    </div>
                    <div className="flex justify-between text-xs">
                        <span className="text-slate-500">Reference</span>
                        <span className="font-bold text-slate-900">{target.subCode}</span>
                    </div>
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
                            <i className="fa-solid fa-spinner fa-spin"></i> Approving…
                        </>
                    ) : (
                        <>
                            <i className="fa-solid fa-check"></i> Yes, Approve
                        </>
                    )}
                </button>
            </div>
        </Modal>
    );
}

// ============================================================
// Reject Confirmation Modal
// ============================================================
function RejectConfirmationModal({ target, saving, notes, onNotesChange, onClose, onConfirm }) {
    return (
        <Modal onClose={onClose} maxWidth="max-w-[460px]">
            <div className="p-5 pb-4 flex items-center justify-between border-b border-slate-100 shrink-0">
                <h3 className="text-lg font-bold text-slate-900">Reject Reservation</h3>
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
                    <h4 className="text-lg font-bold text-slate-900 mb-2">Reject this reservation?</h4>
                    <p className="text-slate-500 text-[13px]">
                        This will notify the tenant that their request was denied. They will not be assigned to the room.
                    </p>
                </div>

                <div className="bg-slate-50 rounded-xl p-4 text-left flex flex-col gap-2.5 mb-4">
                    <div className="flex justify-between text-xs">
                        <span className="text-slate-500">Tenant</span>
                        <span className="font-bold text-slate-900">{target.name}</span>
                    </div>
                    <div className="flex justify-between text-xs">
                        <span className="text-slate-500">Room</span>
                        <span className="font-bold text-slate-900">{target.room}</span>
                    </div>
                    <div className="flex justify-between text-xs">
                        <span className="text-slate-500">Reference</span>
                        <span className="font-bold text-slate-900">{target.subCode}</span>
                    </div>
                </div>

                <label className="block text-[13px] font-semibold text-slate-700 mb-1.5">
                    Reason for rejection <span className="text-rose-500">*</span>
                </label>
                <textarea
                    value={notes}
                    onChange={(e) => onNotesChange(e.target.value)}
                    placeholder="Explain why this reservation is being rejected..."
                    className="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 text-[13px] outline-none resize-y min-h-[90px] focus:border-slate-400"
                />
                <p className="text-[11px] text-slate-400 mt-1">
                    The tenant will see this reason on their reservation details.
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
                            <i className="fa-solid fa-spinner fa-spin"></i> Rejecting…
                        </>
                    ) : (
                        <>
                            <i className="fa-solid fa-ban"></i> Yes, Reject
                        </>
                    )}
                </button>
            </div>
        </Modal>
    );
}