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

    // ============================================================
    // Read reservations data from the DOM (passed by Blade)
    // ============================================================
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

    // ============================================================
    // Shared request helper
    // ============================================================
    const sendAction = useCallback(async (id, action, body = {}) => {
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
    // Event delegation for row actions
    // ============================================================
    useEffect(() => {
        const handler = (e) => {
            // View details — opens modal
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

            // Approve — from row
            const approveBtn = e.target.closest('.icon-approve');
            if (approveBtn) {
                const id = approveBtn.dataset.resId;
                if (!id) return;
                if (!confirm('Approve this reservation?')) return;

                setSaving(true);
                sendAction(id, 'approve', { admin_notes: '' })
                    .then(() => window.location.reload())
                    .catch((err) => alert(err.message))
                    .finally(() => setSaving(false));
                return;
            }

            // Reject — from row
            const rejectBtn = e.target.closest('.icon-reject');
            if (rejectBtn) {
                const id = rejectBtn.dataset.resId;
                if (!id) return;
                if (!confirm('Reject this reservation?')) return;

                setSaving(true);
                sendAction(id, 'reject', { admin_notes: '' })
                    .then(() => window.location.reload())
                    .catch((err) => alert(err.message))
                    .finally(() => setSaving(false));
                return;
            }
        };

        document.addEventListener('click', handler);
        return () => document.removeEventListener('click', handler);
    }, [reservationsData, sendAction]);

    const closeModal = () => {
        setData(null);
        setAdminNotes('');
    };

    // ============================================================
    // Modal actions — use the numeric ID from the payload
    // ============================================================
    const runModalAction = async (action, extra = {}) => {
        if (!data) return;
        setSaving(true);
        try {
            await sendAction(data.id, action, { admin_notes: adminNotes, ...extra });
            closeModal();
            window.location.reload();
        } catch (err) {
            alert(err.message);
        } finally {
            setSaving(false);
        }
    };

    const handleApprove = () => runModalAction('approve');
    const handleReject  = () => runModalAction('reject');

    const handleDelete = async () => {
        if (!data) return;
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

    if (!data) return null;

    const isPending = data.statusText === 'Pending';

    return (
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

                {/* Reservation Details — with Move-in → Move-out pair */}
                <div className="bg-slate-50 rounded-xl p-4 flex flex-col gap-2.5">
                    <div className="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Reservation Details</div>

                    <div className="flex items-center gap-2.5 text-[13px] text-slate-700">
                        <i className="fa-solid fa-house w-4 text-slate-400"></i> {data.desiredRoom}
                    </div>
                    <div className="flex items-center gap-2.5 text-[13px] text-slate-700">
                        <i className="fa-solid fa-bed w-4 text-slate-400"></i> {data.bedId}
                    </div>

                    {/* Move-in */}
                    <div className="flex items-center gap-2.5 text-[13px] text-slate-700">
                        <i className="fa-solid fa-right-to-bracket w-4 text-slate-400"></i> 
                        Move-in: {data.formattedCheckIn}
                    </div>

                    {/* Move-out */}
                    <div className="flex items-center gap-2.5 text-[13px] text-slate-700">
                        <i className="fa-solid fa-right-from-bracket w-4 text-slate-400"></i>
                        Move-out: {data.formattedCheckOut}
                    </div>

                    {/* Submitted */}
                    <div className="flex items-center gap-2.5 text-[13px] text-slate-700">
                        <i className="fa-regular fa-calendar-plus w-4 text-slate-400"></i> {data.submitted}
                    </div>

                    {/* Stay duration */}
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
                            className="bg-lime-200 rounded-xl p-4 flex flex-col items-center justify-center hover:bg-lime-300 transition block"
                        >
                            <img
                                src={data.proofUrl}
                                alt="Proof of payment"
                                className="max-h-48 rounded-md shadow-sm"
                            />
                            <span className="text-[11px] text-slate-600 mt-2">Click to expand</span>
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
                            onClick={handleApprove}
                            disabled={saving}
                            className="bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white px-4 py-2 rounded-lg text-[13px] font-semibold inline-flex items-center gap-1.5"
                        >
                            <i className="fa-regular fa-circle-check"></i>
                            {saving ? 'Saving…' : 'Approve'}
                        </button>
                        <button
                            onClick={handleReject}
                            disabled={saving}
                            className="bg-rose-50 hover:bg-rose-100 disabled:opacity-50 text-rose-600 px-4 py-2 rounded-lg text-[13px] font-semibold inline-flex items-center gap-1.5"
                        >
                            <i className="fa-regular fa-circle-xmark"></i>
                            Reject
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
    );
}