// resources/js/pages/Services/RoomChangeModals.jsx
import React, { useState, useEffect, useRef } from 'react';
import Modal from '../../components/Modal';

const csrf = () =>
    document.querySelector('meta[name="csrf-token"]')?.content ||
    document.querySelector('input[name="_token"]')?.value;

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
                    requested_room: form.requested_room,
                    room_bed_id:    form.room_bed_id,
                    reason:         form.reason,
                }),
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
            document.dispatchEvent(new CustomEvent('room-change:refresh:refresh')); 
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

                    {/* SCROLLABLE BODY */}
                    <div className="p-6 overflow-y-auto max-h-[65vh] flex flex-col gap-4">
                        <div className="text-xs text-slate-400 -mt-1">{request.ref}</div>

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

                        <div className="bg-slate-50 rounded-lg p-3">
                            <div className="text-[11px] text-slate-400 mb-1">Reason:</div>
                            <div className="text-[13px] text-slate-700">{request.reason}</div>
                        </div>
                    </div>

                    {/* FIXED FOOTER */}
                    <div className="p-5 border-t border-slate-100 flex justify-between items-center shrink-0">
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
                            className="bg-[#001838] hover:bg-[#0d2347] text-white px-4 py-2 rounded-lg text-[12.5px] font-medium ml-auto"
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