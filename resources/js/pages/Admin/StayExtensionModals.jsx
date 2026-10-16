// resources/js/pages/Admin/StayExtensionModals.jsx
import React, { useState, useEffect } from 'react';
import Modal from '../../components/Modal';

const csrf = () =>
    document.querySelector('meta[name="csrf-token"]')?.content ||
    document.querySelector('input[name="_token"]')?.value;

export default function StayExtensionModals() {
    const [showDetails, setShowDetails] = useState(false);
    const [request, setRequest] = useState(null);
    const [adminNotes, setAdminNotes] = useState('');
    const [saving, setSaving] = useState(false);
    const [requestsData, setRequestsData] = useState({});

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
    // Event delegation for opening the modal
    // ============================================================
    useEffect(() => {
        const handler = (e) => {
            const btn = e.target.closest('.open-details-btn');
            if (btn) {
                const id = btn.dataset.requestId;
                const data = requestsData[id];
                if (data) {
                    setRequest(data);
                    setAdminNotes(data.admin_notes || '');
                    setShowDetails(true);
                }
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
    // Actions — wired to backend
    // ============================================================
    const handleApprove = async () => {
        if (!request) return;
        setSaving(true);
        try {
            const res = await fetch(`/admin/services/stay-extension/${request.id}/approve`, {
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
            const res = await fetch(`/admin/services/stay-extension/${request.id}/reject`, {
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

    const handleDelete = async () => {
        if (!request) return;
        if (!confirm('Delete this stay extension request permanently?')) return;
        setSaving(true);
        try {
            const res = await fetch(`/admin/services/stay-extension/${request.id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                },
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Failed to delete.');
            closeModal();
            window.location.reload();
        } catch (err) {
            alert(err.message);
        } finally {
            setSaving(false);
        }
    };

    if (!showDetails || !request) return null;

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
                    onClick={handleDelete}
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
    );
}