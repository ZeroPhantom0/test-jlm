// resources/js/pages/Services/RoomChangeModals.jsx
import React, { useState, useEffect, useRef } from 'react';
import Modal from '../../components/Modal';

const csrf = () =>
    document.querySelector('meta[name="csrf-token"]')?.content ||
    document.querySelector('input[name="_token"]')?.value;

// ── Billing helpers ──
const formatMoney = (n) =>
    '₱' + Number(n).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const computeEstimate = (room) => {
    if (!room || !room.daily_rent || !room.tenant_check_out) return null;

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const checkOut = new Date(room.tenant_check_out);
    checkOut.setHours(0, 0, 0, 0);

    const msPerDay = 1000 * 60 * 60 * 24;
    const days = Math.max(0, Math.round((checkOut - today) / msPerDay));

    if (days <= 0) return null;

    return {
        days,
        rate: room.daily_rent,
        total: days * room.daily_rent,
        checkOut: room.tenant_check_out,
    };
};

export default function RoomChangeModals() {
    const [showSubmit, setShowSubmit]     = useState(false);
    const [showDetails, setShowDetails]   = useState(false);
    const [showDelete, setShowDelete]     = useState(false);
    const [request, setRequest]           = useState(null);
    const [deleteTarget, setDeleteTarget] = useState(null);
    const [saving, setSaving]             = useState(false);
    const [requestsData, setRequestsData] = useState({});
    const [currentRoom, setCurrentRoom]   = useState('');
    const [availableRooms, setAvailableRooms] = useState([]);

    // State for tracking the selected room's bed slots
    const [selectedRoomData, setSelectedRoomData] = useState(null);

    // OCR & Upload State
    const [ocrLoading, setOcrLoading] = useState(false);
    const [ocrError, setOcrError] = useState("");
    const [receipt, setReceipt] = useState(null);
    const [referenceNo, setReferenceNo] = useState("");

    // Tracks whether the admin is actively typing in the form
    const savingRef = useRef(false);
    useEffect(() => { savingRef.current = saving; }, [saving]);

    // Form state for submit
    const [form, setForm] = useState({
        requested_room: '',
        room_bed_id:    '',
        reason:         '',
    });

    // ============================================================
    // LIVE DATA — read once, then listen for polling/Echo updates
    // ============================================================
    const readFromDom = () => {
        const el = document.getElementById('services-room-change-root');
        if (!el) return;

        if (el.dataset.currentRoom !== undefined) {
            setCurrentRoom(el.dataset.currentRoom || '');
        }

        if (el.dataset.availableRooms) {
            try {
                setAvailableRooms(JSON.parse(el.dataset.availableRooms));
            } catch (e) {
                console.error('Invalid available rooms JSON', e);
            }
        }

        if (el.dataset.requests) {
            try {
                const parsed = JSON.parse(el.dataset.requests);
                if (parsed && typeof parsed === 'object') setRequestsData(parsed);
            } catch (e) {
                console.error('Invalid requests JSON', e);
            }
        }
    };

    useEffect(() => {
        readFromDom();

        const handler = (e) => {
            const fresh = e?.detail;
            if (fresh && typeof fresh === 'object') {
                setRequestsData(fresh);
            } else {
                readFromDom();
            }
        };

        window.addEventListener('room-change:data-updated', handler);
        return () => window.removeEventListener('room-change:data-updated', handler);
    }, []);

    // Sync open request with fresh data from the server
    useEffect(() => {
        if (!request) return;
        const fresh = requestsData[request.id];
        if (!fresh) return;

        if (
            fresh.status !== request.status ||
            fresh.reason !== request.reason ||
            fresh.current_room !== request.current_room ||
            fresh.requested_room !== request.requested_room
        ) {
            setRequest(fresh);
        }
    }, [requestsData]); // eslint-disable-line react-hooks/exhaustive-deps

    // ============================================================
    // Event delegation
    // ============================================================
    useEffect(() => {
        const handler = (e) => {
            if (e.target.closest('#open-new-room-change')) {
                setForm({ requested_room: '', room_bed_id: '', reason: '' });
                setSelectedRoomData(null);
                setReceipt(null);
                setReferenceNo('');
                setOcrError('');
                setShowSubmit(true);
                return;
            }

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

            const cancelBtn = e.target.closest('.cancel-room-change-btn');
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
    }, [requestsData]);

    const updateField = (key, value) => setForm((f) => ({ ...f, [key]: value }));

    // ============================================================
    // Handle room selection (to show bed slots)
    // ============================================================
    const handleRoomSelect = (e) => {
        const roomId = e.target.value;
        updateField('requested_room', roomId);

        // Reset bed selection
        updateField('room_bed_id', '');

        // Find the room data to get bed slots
        const room = availableRooms.find(r => r.id.toString() === roomId.toString());
        setSelectedRoomData(room || null);
    };

    // ============================================================
    // OCR Receipt Upload
    // ============================================================
    const handleReceiptUpload = async (file) => {
        if (!file) return;

        setReceipt(file);
        setOcrError("");
        setOcrLoading(true);

        const fd = new FormData();
        fd.append("receipt", file);

        try {
            const res = await fetch("/tenants/billing/parse-receipt", {
                method: "POST",
                headers: {
                    Accept: "application/json",
                    "X-CSRF-TOKEN": csrf(),
                },
                body: fd,
            });

            const data = await res.json().catch(() => ({}));

            if (res.ok && data.success && data.reference_no) {
                setReferenceNo(data.reference_no);
            } else if (res.ok && data.success && !data.reference_no) {
                setOcrError("Reference number not detected. Please type it manually.");
            } else {
                setOcrError("Could not scan receipt. Please type reference manually.");
            }
        } catch (err) {
            console.error("OCR failed", err);
            setOcrError("OCR service unavailable. Please type manually.");
        } finally {
            setOcrLoading(false);
        }
    };

    const handleCopyGCash = () => {
        navigator.clipboard?.writeText("0917-123-4567");
        alert("GCash number copied!");
    };

    // ============================================================
    // Submit new room change request
    // ============================================================
    const handleSubmit = async (e) => {
        e.preventDefault();

        if (!form.requested_room) {
            alert('Please select an available room.');
            return;
        }

        // Check if bed selection is required
        if (selectedRoomData?.bed_slots?.length > 0 && !form.room_bed_id) {
            alert('Please select a bed slot for this room.');
            return;
        }

        if (!form.reason.trim()) {
            alert('Please provide a reason.');
            return;
        }

        if (!referenceNo.trim()) {
            alert('Please enter the GCash reference number.');
            return;
        }
        if (!receipt) {
            alert('Please upload your payment proof.');
            return;
        }

        setSaving(true);
        try {
            const formData = new FormData();
            formData.append('requested_room', form.requested_room);
            if (form.room_bed_id) formData.append('room_bed_id', form.room_bed_id);
            formData.append('reason', form.reason);
            formData.append('reference_no', referenceNo);
            if (receipt) formData.append('receipt', receipt);

            const res = await fetch('/tenants/services/room-change', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                },
                body: formData,
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Failed to submit request.');
            
            setShowSubmit(false);
            document.dispatchEvent(new CustomEvent('room-change:refresh'));
        } catch (err) {
            alert(err.message);
        } finally {
            setSaving(false);
        }
    };

    // ============================================================
    // Delete Modal Handlers
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

    const confirmCancel = async () => {
        if (!deleteTarget) return;
        setSaving(true);
        try {
            const res = await fetch(`/tenants/services/room-change/${deleteTarget.id}/cancel`, {
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
            document.dispatchEvent(new CustomEvent('room-change:refresh'));
        } catch (err) {
            alert(err.message);
        } finally {
            setSaving(false);
        }
    };

    // ── Live billing estimate for the selected room ──
    const estimate = computeEstimate(selectedRoomData);

    return (
        <>
            {/* ============================================================ */}
            {/* MODAL 1: Submit Room Change Request */}
            {/* ============================================================ */}
            {showSubmit && (
                <Modal onClose={() => setShowSubmit(false)} maxWidth="max-w-[480px]">
                    {/* FIXED HEADER */}
                    <div className="p-5 pb-4 flex items-center justify-between border-b border-slate-100 shrink-0">
                        <h3 className="text-lg font-bold text-slate-900">Submit Room Change Request</h3>
                        <button
                            onClick={() => setShowSubmit(false)}
                            className="text-slate-400 hover:text-slate-700 text-lg"
                        >
                            <i className="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <form onSubmit={handleSubmit} className="flex flex-col min-h-0">
                        {/* SCROLLABLE BODY */}
                        <div className="p-6 overflow-y-auto max-h-[65vh] flex flex-col gap-4">

                            <div className="bg-blue-50 rounded-lg p-3 flex gap-2.5 text-[13px] text-blue-600 leading-snug">
                                <i className="fa-regular fa-circle-info mt-0.5"></i>
                                <span>Request a room change by selecting from the currently available units.</span>
                            </div>

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

                            <div>
                                <label className="block text-[12.5px] font-semibold text-slate-700 mb-1.5">
                                    Requested Room <span className="text-rose-500">*</span>
                                </label>
                                <select
                                    value={form.requested_room}
                                    onChange={handleRoomSelect}
                                    className="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2.5 text-[13px] outline-none focus:border-slate-400 cursor-pointer"
                                >
                                    <option value="" disabled>-- Select an available room --</option>
                                    {availableRooms.map((room) => (
                                        <option key={room.id} value={room.id}>
                                            {room.label}
                                        </option>
                                    ))}
                                </select>
                                {availableRooms.length === 0 && (
                                    <p className="text-[11px] text-rose-500 mt-1">No other rooms are currently available.</p>
                                )}
                            </div>

                            {/* BED SLOT SELECTION */}
                            {selectedRoomData?.bed_slots?.length > 0 && (
                                <div className="bg-teal-50 border border-teal-100 rounded-xl p-4 mt-1">
                                    <div className="flex items-center gap-2 text-[13px] font-bold text-teal-800 mb-3">
                                        <i className="fa-solid fa-bed"></i>
                                        Select Your Bed Slot <span className="text-rose-500">*</span>
                                    </div>

                                    <div className="grid grid-cols-2 gap-2">
                                        {selectedRoomData.bed_slots.map((slot) => {
                                            const isSelected = form.room_bed_id === slot.id;
                                            const isOccupied = slot.status === 'Occupied';

                                            return (
                                                <button
                                                    key={slot.id}
                                                    type="button"
                                                    disabled={isOccupied}
                                                    onClick={() => updateField('room_bed_id', slot.id)}
                                                    className={`
                                                        flex flex-col items-start p-2.5 rounded-lg border text-left transition
                                                        ${
                                                            isOccupied
                                                                ? "bg-slate-100 border-slate-200 opacity-60 cursor-not-allowed"
                                                                : isSelected
                                                                ? "bg-teal-600 border-teal-600 text-white shadow-sm"
                                                                : "bg-white border-slate-200 hover:border-teal-400"
                                                        }
                                                    `}
                                                >
                                                    <span className={`text-[12px] font-bold ${isSelected ? "text-white" : "text-slate-800"}`}>
                                                        {slot.label}
                                                    </span>
                                                    <span className={`text-[10px] font-semibold mt-0.5 ${isSelected ? "text-teal-100" : isOccupied ? "text-rose-500" : "text-emerald-500"}`}>
                                                        {slot.status}
                                                    </span>
                                                </button>
                                            );
                                        })}
                                    </div>
                                </div>
                            )}

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

                            {/* ── SUMMARY OF CHARGES ── */}
                            {estimate && (
                                <div className="bg-[#f4f7fc] border border-blue-100 rounded-xl p-4 mt-2">
                                    <div className="flex items-center gap-2 text-[14px] font-bold text-slate-800 mb-3">
                                        <i className="fa-solid fa-peso-sign"></i>
                                        Summary of Charges
                                    </div>

                                    <div className="flex justify-between text-[12px] text-slate-700 mb-2">
                                        <span>Room Rate</span>
                                        <strong>{formatMoney(estimate.rate)} / day</strong>
                                    </div>
                                    
                                    <div className="flex justify-between text-[12px] text-slate-700 mb-2">
                                        <span>
                                            {formatMoney(estimate.rate)} × {estimate.days} day{estimate.days === 1 ? '' : 's'}
                                        </span>
                                        <strong>{formatMoney(estimate.total)}</strong>
                                    </div>

                                    <div className="flex justify-between text-[14px] font-extrabold text-slate-900 border-t border-slate-300 pt-2.5 mt-2.5">
                                        <span>Total Charges:</span>
                                        <strong>{formatMoney(estimate.total)}</strong>
                                    </div>
                                    
                                    <div className="flex justify-between text-[12px] text-slate-700 mt-2">
                                        <span>Full Payment:</span>
                                        <strong>{formatMoney(estimate.total)}</strong>
                                    </div>
                                    
                                    <div className="flex justify-between text-[14px] font-extrabold text-slate-900 mt-1.5">
                                        <span>Remaining Balance:</span>
                                        <strong>₱0.00</strong>
                                    </div>
                                </div>
                            )}

                            {/* ── PAYMENT VERIFICATION REQUIRED ── */}
                            {estimate && (
                                <div className="bg-amber-50 border border-amber-200 rounded-xl p-4 mt-2">
                                    <div className="flex items-center gap-2 text-[14px] font-bold text-amber-800 mb-2.5">
                                        <i className="fa-solid fa-mobile-screen-button"></i>
                                        Payment Verification Required
                                    </div>
                                    <p className="text-[12px] text-amber-700 mb-3">
                                        Pay via GCash to secure your room change
                                    </p>

                                    <div className="flex justify-between text-[12px] mb-1.5">
                                        <span className="text-amber-700">Account Name:</span>
                                        <strong className="text-amber-900">Leah Agujar</strong>
                                    </div>
                                    <div className="flex justify-between text-[12px] mb-3">
                                        <span className="text-amber-700">GCash Number:</span>
                                        <strong className="text-amber-900">0917-123-4567</strong>
                                    </div>

                                    <button
                                        type="button"
                                        onClick={handleCopyGCash}
                                        className="w-full bg-orange-500 hover:bg-orange-600 text-white rounded-lg py-2.5 text-[13px] font-bold flex items-center justify-center gap-2 mb-3 transition"
                                    >
                                        <i className="fa-regular fa-copy"></i>
                                        Copy GCash Number
                                    </button>

                                    <label className="border border-dashed border-amber-300 rounded-lg p-3 flex items-center gap-3 bg-white cursor-pointer mb-2">
                                        <span className="bg-white border border-amber-300 px-3 py-1.5 rounded-md text-[12px] font-bold text-amber-700">
                                            Choose File
                                        </span>
                                        <span className="text-[12px] text-slate-400">
                                            {receipt ? receipt.name : "No file chosen"}
                                        </span>
                                        <input
                                            type="file"
                                            accept="image/*"
                                            onChange={(e) => {
                                                const file = e.target.files[0] || null;
                                                if (file) handleReceiptUpload(file);
                                                else setReceipt(null);
                                            }}
                                            className="hidden"
                                        />
                                    </label>

                                    <div className="flex items-center gap-1.5 text-[11px] text-amber-700 mb-3">
                                        <i className="fa-solid fa-arrow-up-from-bracket"></i>
                                        Upload a screenshot of your GCash payment receipt
                                    </div>

                                    <div className="mb-3">
                                        <label className="block text-[12px] font-semibold text-amber-800 mb-1.5">
                                            GCash Reference Number <span className="text-rose-500">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            value={referenceNo}
                                            onChange={(e) => setReferenceNo(e.target.value)}
                                            placeholder="e.g. GC-20260401-12345"
                                            className="w-full bg-white border border-amber-200 rounded-lg px-3 py-2.5 text-[13px] outline-none focus:border-amber-400"
                                        />
                                        {ocrLoading && (
                                            <div className="flex items-center gap-1.5 text-[11px] text-blue-600 mt-1">
                                                <i className="fa-solid fa-spinner fa-spin"></i>
                                                Scanning receipt for reference number...
                                            </div>
                                        )}
                                        {ocrError && (
                                            <div className="flex items-center gap-1.5 text-[11px] text-amber-600 mt-1">
                                                <i className="fa-solid fa-circle-exclamation"></i>
                                                {ocrError}
                                            </div>
                                        )}
                                    </div>
                                    <div className="bg-amber-100 rounded-lg p-3 flex gap-2 text-[12px] text-amber-900 leading-relaxed">
                                        <i className="fa-solid fa-circle-info mt-0.5"></i>
                                        <span>
                                            <strong>Important:</strong> You must upload your payment proof before submitting your room change request. Your payment and request will be reviewed by the admin.
                                        </span>
                                    </div>
                                </div>
                            )}

                        </div>

                        {/* FIXED FOOTER */}
                        <div className="p-5 border-t border-slate-100 flex justify-end items-center gap-3 shrink-0">
                            <button
                                type="button"
                                onClick={() => setShowSubmit(false)}
                                className="text-slate-600 text-[13px] font-medium px-4 py-2"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                disabled={saving || availableRooms.length === 0}
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
                    {/* FIXED HEADER */}
                    <div className="p-5 pb-4 flex items-center justify-between border-b border-slate-100 shrink-0">
                        <div>
                            <h3 className="text-lg font-bold text-slate-900">Request Details</h3>
                            <div className="text-[11px] text-slate-400 mt-0.5">{request.ref}</div>
                        </div>
                        <span className={`text-[11px] font-bold px-3 py-1.5 rounded-full inline-flex items-center gap-1.5 ${
                            request.status === 'Pending'
                                ? 'bg-amber-100 text-amber-700'
                                : request.status === 'Approved'
                                ? 'bg-emerald-100 text-emerald-700'
                                : 'bg-rose-100 text-rose-700'
                        }`}>
                            <i className={request.status === 'Pending' ? 'fa-regular fa-clock' : request.status === 'Approved' ? 'fa-regular fa-circle-check' : 'fa-regular fa-circle-xmark'}></i> 
                            {request.status}
                        </span>
                    </div>

                    {/* SCROLLABLE BODY */}
                    <div className="p-6 overflow-y-auto max-h-[65vh] flex flex-col gap-5">
                        
                        {/* Room Transfer Visual */}
                        <div className="flex items-center justify-between gap-3">
                            <div className="flex-1 bg-slate-50 border border-slate-200 rounded-xl p-3 text-center">
                                <div className="text-[10px] text-slate-400 font-bold uppercase mb-1">From</div>
                                <div className="text-[14px] font-bold text-slate-800 truncate">{request.current_room}</div>
                            </div>
                            <div className="w-8 h-8 rounded-full bg-white border border-slate-200 flex items-center justify-center shrink-0 shadow-sm z-10 -mx-2">
                                <i className="fa-solid fa-arrow-right text-slate-400 text-xs"></i>
                            </div>
                            <div className="flex-1 bg-emerald-50 border border-emerald-200 rounded-xl p-3 text-center">
                                <div className="text-[10px] text-emerald-600 font-bold uppercase mb-1">To</div>
                                <div className="text-[14px] font-bold text-emerald-700 truncate">{request.requested_room}</div>
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="bg-slate-50 rounded-xl p-3.5 border border-slate-100">
                                <div className="text-[10px] font-bold text-slate-400 uppercase mb-1">Date Submitted</div>
                                <div className="text-[13px] font-semibold text-slate-800">{request.submitted}</div>
                            </div>
                            <div className="bg-slate-50 rounded-xl p-3.5 border border-slate-100">
                                <div className="text-[10px] font-bold text-slate-400 uppercase mb-1">Target Status</div>
                                <div className="text-[13px] font-semibold text-slate-800">{request.availability}</div>
                            </div>
                        </div>

                        {/* Your Reason */}
                        <div>
                            <div className="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">
                                Your Reason
                            </div>
                            <div className="bg-slate-50 border border-slate-100 rounded-xl p-4 text-[13px] text-slate-700 leading-relaxed whitespace-pre-wrap">
                                {request.reason}
                            </div>
                        </div>

                        {/* Payment info if pending/approved */}
                        {request.status !== 'Rejected' && (
                            <div className="bg-amber-50 border border-amber-200 rounded-xl p-4">
                                <div className="flex items-center gap-2 text-[13px] font-bold text-amber-800 mb-2">
                                    <i className="fa-solid fa-receipt"></i>
                                    Payment Status
                                </div>
                                <p className="text-[12px] text-amber-700 leading-relaxed">
                                    {request.status === 'Pending' 
                                        ? "Your payment proof is currently being verified by the admin."
                                        : "Your room change request and payment have been approved."}
                                </p>
                            </div>
                        )}

                        {/* Rejection Reason */}
                        {request.status === 'Rejected' && request.admin_notes && (
                            <div className="bg-rose-50 border border-rose-200 rounded-xl p-4">
                                <div className="flex items-center gap-2 text-[13px] font-bold text-rose-800 mb-2">
                                    <i className="fa-regular fa-circle-xmark"></i>
                                    Rejection Reason
                                </div>
                                <p className="text-[12px] text-rose-700 leading-relaxed whitespace-pre-wrap">
                                    {request.admin_notes}
                                </p>
                            </div>
                        )}
                    </div>

                    {/* FIXED FOOTER */}
                    <div className="p-5 border-t border-slate-100 flex justify-between items-center shrink-0 bg-slate-50/50 rounded-b-2xl">
                        {request.status === 'Pending' ? (
                            <button
                                onClick={openDeleteModal}
                                disabled={saving}
                                className="text-rose-600 hover:text-rose-700 disabled:opacity-50 text-[13px] font-semibold px-2 py-2"
                            >
                                Cancel Request
                            </button>
                        ) : (
                            <div /> // Spacer
                        )}
                        <button
                            onClick={() => setShowDetails(false)}
                            className="bg-[#001838] hover:bg-[#0d2347] text-white px-5 py-2.5 rounded-xl text-[13px] font-bold transition ml-auto"
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
                    <div className="p-5 pb-4 flex items-center justify-between border-b border-slate-100 shrink-0">
                        <h3 className="text-lg font-bold text-slate-900">Cancel Room Change</h3>
                        <button
                            onClick={closeDeleteModal}
                            disabled={saving}
                            className="text-slate-400 hover:text-slate-700 text-lg disabled:opacity-50"
                        >
                            <i className="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div className="p-6 text-center overflow-y-auto max-h-[65vh]">
                        <div className="w-16 h-16 mx-auto rounded-full bg-rose-50 flex items-center justify-center mb-4">
                            <i className="fa-solid fa-trash-can text-rose-500 text-2xl"></i>
                        </div>
                        <h4 className="text-lg font-bold text-slate-900 mb-2">Are you sure?</h4>
                        <p className="text-slate-500 text-[13px] mb-2">
                            You are about to cancel this room change request:
                        </p>
                        <p className="text-[13px] text-slate-600 mb-4">
                            <span className="font-semibold">{deleteTarget.current_room}</span>
                            <i className="fa-solid fa-arrow-right text-slate-400 text-[10px] mx-2"></i>
                            <span className="font-semibold text-emerald-600">{deleteTarget.requested_room}</span>
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

                    <div className="p-5 border-t border-slate-100 flex justify-end gap-3 shrink-0">
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