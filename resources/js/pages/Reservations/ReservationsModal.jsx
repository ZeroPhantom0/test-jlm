// resources/js/pages/Reservations/ReservationsModal.jsx
import React, { useState, useEffect } from 'react';
import Modal from '../../components/Modal';

const csrf = () =>
    document.querySelector('meta[name="csrf-token"]')?.content ||
    document.querySelector('input[name="_token"]')?.value;

const FALLBACK_ROOM = {
    id:          null,
    name:        'Solo Room',
    roomNumber:  'ROOM 101',
    floor:       1,
    status:      'Available',
    price:       600,
    img:         'https://images.unsplash.com/photo-1598928506311-c55ded91a20c?auto=format&fit=crop&w=600&q=80',
    pax:         '1-2 pax',
    beds:        '1 bed',
    bath:        '1 bath',
    description: 'Cozy room with modern finishes, private CR and city views. Perfect for working professionals and students.',
    estDate:     'March 25, 2026',
    estApprox:   'Approx. 2 days',
};

export default function ReservationsModal() {
    // Modal state
    const [showDetails, setShowDetails] = useState(false);
    const [showReserve, setShowReserve] = useState(false);
    const [room, setRoom]               = useState(FALLBACK_ROOM);
    const [saving, setSaving]           = useState(false);

    // Reservations data passed from Blade
    const [reservationsData, setReservationsData] = useState({});

    // ---- Reserve form state ----
    const [form, setForm] = useState({
        check_in_date:  '',
        check_in_time:  '',
        check_out_date: '',
        check_out_time: '',
        has_companion:  false,
        notes:          '',
        payment_option: 'half', // 'half' | 'full'
        reference_no:   '',
        receipt:        null,
    });

    // ============================================================
    // Read data from mount element
    // ============================================================
    useEffect(() => {
        const el = document.getElementById('tenant-reservations-modals');
        if (!el) return;

        if (el.dataset.reservations) {
            try {
                setReservationsData(JSON.parse(el.dataset.reservations));
            } catch (e) {
                console.error('Invalid reservations JSON', e);
            }
        }
    }, []);

    // ============================================================
    // Event delegation
    // ============================================================
    useEffect(() => {
        const handler = (e) => {
            // Open details modal
            const detailsBtn = e.target.closest('.open-room-details-btn');
            if (detailsBtn) {
                const data = JSON.parse(detailsBtn.dataset.room || '{}');
                setRoom({ ...FALLBACK_ROOM, ...data });
                setShowDetails(true);
                return;
            }

            // Open reserve modal
            const reserveBtn = e.target.closest('.open-reserve-modal-btn');
            if (reserveBtn) {
                const data = JSON.parse(reserveBtn.dataset.room || '{}');
                setRoom({ ...FALLBACK_ROOM, ...data });
                setForm({
                    check_in_date:  '',
                    check_in_time:  '',
                    check_out_date: '',
                    check_out_time: '',
                    has_companion:  false,
                    notes:          '',
                    payment_option: 'half',
                    reference_no:   '',
                    receipt:        null,
                });
                setShowReserve(true);
                return;
            }
        };

        document.addEventListener('click', handler);
        return () => document.removeEventListener('click', handler);
    }, []);

    const closeDetails = () => setShowDetails(false);
    const closeReserve = () => setShowReserve(false);

    const updateField = (key, value) =>
        setForm((f) => ({ ...f, [key]: value }));

    // ============================================================
    // Derived charge calculations
    // ============================================================
    const roomRate     = Number(room.price) || 0;
    const companionFee = form.has_companion ? 150 : 0;

    // Simple day diff (fallback to 1 if invalid)
    const computeDays = () => {
        const inD  = new Date(form.check_in_date);
        const outD = new Date(form.check_out_date);
        if (isNaN(inD) || isNaN(outD) || outD <= inD) return 1;
        const diff = Math.ceil((outD - inD) / (1000 * 60 * 60 * 24));
        return diff > 0 ? diff : 1;
    };
    const days         = computeDays();
    const roomSubtotal = roomRate * days;
    const totalCharges = roomSubtotal + companionFee;
    const halfPayment  = totalCharges / 2;
    const payNow       = form.payment_option === 'half' ? halfPayment : totalCharges;
    const remaining    = totalCharges - payNow;

    // ============================================================
    // Submit reservation
    // ============================================================
    const handleSubmit = async () => {
        if (!form.check_in_date || !form.check_out_date) {
            alert('Please select both check-in and check-out dates.');
            return;
        }
        if (!form.reference_no.trim()) {
            alert('Please enter your GCash reference number.');
            return;
        }
        if (!form.receipt) {
            alert('Please upload your payment proof.');
            return;
        }

        setSaving(true);
        try {
            const fd = new FormData();
            fd.append('room_id',         room.id);
            fd.append('check_in_date',   form.check_in_date);
            fd.append('check_in_time',   form.check_in_time);
            fd.append('check_out_date',  form.check_out_date);
            fd.append('check_out_time',  form.check_out_time);
            fd.append('has_companion',   form.has_companion ? '1' : '0');
            fd.append('notes',           form.notes);
            fd.append('payment_option',  form.payment_option);
            fd.append('reference_no',    form.reference_no);
            fd.append('receipt',         form.receipt);

            const res = await fetch('/tenants/reservations', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                },
                body: fd,
            });

            const data = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(data.message || 'Failed to submit reservation.');

            closeReserve();
            window.location.reload();
        } catch (err) {
            alert(err.message);
        } finally {
            setSaving(false);
        }
    };

    // Copy GCash number
    const handleCopyGCash = () => {
        navigator.clipboard?.writeText('0917-123-4567');
        alert('GCash number copied!');
    };

    // ============================================================
    // Cancel a pending reservation
    // ============================================================
    const handleCancelReservation = async (key) => {
        const data = reservationsData[key];
        if (!data) return;
        if (!confirm('Cancel this reservation?')) return;

        setSaving(true);
        try {
            const res = await fetch(`/tenants/reservations/${data.id}/cancel`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                },
            });
            const result = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(result.message || 'Failed to cancel.');
            window.location.reload();
        } catch (err) {
            alert(err.message);
        } finally {
            setSaving(false);
        }
    };

    // Expose cancel via data attribute click
    useEffect(() => {
        const handler = (e) => {
            const btn = e.target.closest('.cancel-reservation-btn');
            if (btn) handleCancelReservation(btn.dataset.resKey);
        };
        document.addEventListener('click', handler);
        return () => document.removeEventListener('click', handler);
        // eslint-disable-next-line
    }, [reservationsData]);

    return (
        <>
            {/* ============================================================ */}
            {/* DETAILS MODAL */}
            {/* ============================================================ */}
            {showDetails && (
                <Modal onClose={closeDetails} maxWidth="max-w-[540px]">
                    <div className="p-5 pb-4 flex items-center justify-between border-b border-slate-100">
                        <h3 className="text-lg font-bold text-slate-900">{room.name}</h3>
                        <button onClick={closeDetails} className="text-slate-400 hover:text-slate-700 text-lg">
                            <i className="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div className="p-6 overflow-y-auto flex flex-col gap-4">
                        {/* Image + badges */}
                        <div className="relative h-[200px] rounded-xl overflow-hidden">
                            <img src={room.img} alt={room.name} className="w-full h-full object-cover" />

                            <span className={`absolute top-3 left-3 text-[11px] font-bold px-3 py-1 rounded-full ${
                                room.status === 'Occupied' ? 'bg-rose-200 text-rose-700' :
                                room.status === 'Available' ? 'bg-green-100 text-green-700' :
                                'bg-amber-100 text-amber-700'
                            }`}>
                                {room.status}
                            </span>

                            <span className="absolute top-3 right-3 bg-black/60 text-white text-[10px] font-semibold px-2.5 py-1 rounded-full backdrop-blur">
                                360° Tour
                            </span>

                            <span className="absolute bottom-3 left-3 bg-blue-50 text-blue-600 text-[10px] font-extrabold uppercase px-2.5 py-1 rounded-md">
                                {room.roomNumber}
                            </span>

                            {room.status === 'Occupied' && (
                                <div className="absolute bottom-3 right-3 bg-white/95 px-3 py-1.5 rounded-md text-[10px] text-slate-700 text-right shadow">
                                    <div>Estimated Availability: <strong>{room.estDate}</strong></div>
                                    <div className="text-slate-500">{room.estApprox}</div>
                                </div>
                            )}
                        </div>

                        {/* Description */}
                        <p className="text-[13px] text-slate-500 leading-relaxed">{room.description}</p>

                        {/* Specs */}
                        <div className="text-[13px] font-bold text-slate-900">Room Specifications</div>
                        <div className="grid grid-cols-3 gap-2.5">
                            {[
                                { label: 'ROOM TYPE', value: room.name },
                                { label: 'MAX CAPACITY', value: room.pax },
                                { label: 'BEDS', value: room.beds },
                                { label: 'AVAILABLE SLOTS', value: room.status === 'Available' ? '1' : '0' },
                                { label: 'DAILY RENT', value: `₱${roomRate.toLocaleString()}` },
                                { label: 'FLOOR', value: room.floor },
                            ].map((s, i) => (
                                <div key={i} className="bg-slate-50 rounded-lg p-3 text-center">
                                    <div className="text-[9.5px] font-bold text-slate-400 uppercase mb-1">{s.label}</div>
                                    <div className="text-[12px] font-bold text-slate-900">{s.value}</div>
                                </div>
                            ))}
                        </div>

                        {/* Actions */}
                        <div className="grid grid-cols-2 gap-3 mt-2">
                            <button
                                onClick={closeDetails}
                                className="bg-[#02122c] text-white rounded-lg py-2.5 text-[13px] font-semibold"
                            >
                                Close
                            </button>
                            {room.status === 'Available' ? (
                                <button
                                    onClick={() => {
                                        closeDetails();
                                        setForm((f) => ({ ...f }));
                                        setShowReserve(true);
                                    }}
                                    className="bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg py-2.5 text-[13px] font-semibold"
                                >
                                    Reserve Room
                                </button>
                            ) : (
                                <button disabled className="bg-slate-100 text-slate-400 rounded-lg py-2.5 text-[13px] font-semibold cursor-not-allowed">
                                    Not Available
                                </button>
                            )}
                        </div>
                    </div>
                </Modal>
            )}

            {/* ============================================================ */}
            {/* RESERVE MODAL */}
            {/* ============================================================ */}
            {showReserve && (
                <Modal onClose={closeReserve} maxWidth="max-w-[580px]">
                    <button
                        onClick={closeReserve}
                        className="absolute top-5 right-5 text-slate-400 hover:text-slate-700 text-lg z-10"
                    >
                        <i className="fa-solid fa-xmark"></i>
                    </button>

                    <div className="p-6 overflow-y-auto flex flex-col gap-5">
                        <h3 className="text-lg font-bold text-slate-900">
                            Reserve - {room.name}
                        </h3>

                        {/* Room header card */}
                        <div className="bg-emerald-50 rounded-xl p-3 flex items-center gap-3">
                            <img src={room.img} alt={room.name} className="w-12 h-12 rounded-lg object-cover" />
                            <div>
                                <h4 className="text-[14px] font-bold text-emerald-800">{room.name}</h4>
                                <p className="text-[11px] text-emerald-700">Floor {room.floor} · {room.roomNumber}</p>
                                <h5 className="text-[13px] font-extrabold text-emerald-600 mt-0.5">
                                    ₱{roomRate.toLocaleString()}.00/day
                                </h5>
                            </div>
                        </div>

                        {/* Reservation Information */}
                        <div>
                            <div className="text-[14px] font-bold text-slate-900 mb-3">Reservation Information</div>

                            <div className="grid grid-cols-2 gap-4 mb-3">
                                <div>
                                    <label className="block text-[12px] font-semibold text-slate-600 mb-1.5">
                                        Check-in Date <span className="text-rose-500">*</span>
                                    </label>
                                    <input
                                        type="date"
                                        value={form.check_in_date}
                                        onChange={(e) => updateField('check_in_date', e.target.value)}
                                        className="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-[13px] outline-none focus:border-blue-400"
                                    />
                                </div>
                                <div>
                                    <label className="block text-[12px] font-semibold text-slate-600 mb-1.5">
                                        Check-in Time <span className="text-rose-500">*</span>
                                    </label>
                                    <input
                                        type="time"
                                        value={form.check_in_time}
                                        onChange={(e) => updateField('check_in_time', e.target.value)}
                                        className="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-[13px] outline-none focus:border-blue-400"
                                    />
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-[12px] font-semibold text-slate-600 mb-1.5">
                                        Check-out Date <span className="text-rose-500">*</span>
                                    </label>
                                    <input
                                        type="date"
                                        value={form.check_out_date}
                                        onChange={(e) => updateField('check_out_date', e.target.value)}
                                        className="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-[13px] outline-none focus:border-blue-400"
                                    />
                                </div>
                                <div>
                                    <label className="block text-[12px] font-semibold text-slate-600 mb-1.5">
                                        Check-out Time <span className="text-rose-500">*</span>
                                    </label>
                                    <input
                                        type="time"
                                        value={form.check_out_time}
                                        onChange={(e) => updateField('check_out_time', e.target.value)}
                                        className="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-[13px] outline-none focus:border-blue-400"
                                    />
                                </div>
                            </div>

                            <p className="text-[13px] font-semibold text-slate-600 mt-4 pl-1">
                                Total Stay Duration: {days} night{days > 1 ? 's' : ''}
                            </p>
                        </div>

                        {/* Solo Room Options */}
                        <div className="bg-blue-50 rounded-xl p-4">
                            <div className="flex items-center gap-2 text-[13px] font-bold text-blue-800 mb-3">
                                <i className="fa-regular fa-user"></i>
                                Solo Room Options
                            </div>
                            <label className="flex items-start gap-2.5 text-[13px] text-blue-800 font-medium cursor-pointer">
                                <input
                                    type="checkbox"
                                    checked={form.has_companion}
                                    onChange={(e) => updateField('has_companion', e.target.checked)}
                                    className="w-[18px] h-[18px] accent-blue-700 mt-0.5 cursor-pointer"
                                />
                                <span>I will have a companion (max 2 persons total)</span>
                            </label>
                            <p className="text-[12px] text-blue-500 ml-7 mt-1">
                                Extra mattress fee: ₱150.00 per companion
                            </p>
                        </div>

                        {/* Additional Notes */}
                        <div>
                            <label className="block text-[12px] font-semibold text-slate-600 mb-1.5">
                                Additional Notes
                            </label>
                            <textarea
                                value={form.notes}
                                onChange={(e) => updateField('notes', e.target.value)}
                                placeholder="Any special requirements or questions for the admin..."
                                className="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-[13px] outline-none resize-none h-[70px] focus:border-blue-400"
                            />
                        </div>

                        {/* Payment Option */}
                        <div>
                            <label className="block text-[13px] font-bold text-slate-700 mb-2.5">
                                Payment Option <span className="text-rose-500">*</span>
                            </label>
                            <div className="grid grid-cols-2 gap-4">
                                <button
                                    type="button"
                                    onClick={() => updateField('payment_option', 'half')}
                                    className={`rounded-xl p-3.5 text-center transition ${
                                        form.payment_option === 'half'
                                            ? 'bg-emerald-50 border border-emerald-200'
                                            : 'bg-slate-50 border border-slate-200'
                                    }`}
                                >
                                    <div className={`text-[13px] font-bold flex items-center justify-center gap-1.5 mb-2 ${
                                        form.payment_option === 'half' ? 'text-emerald-600' : 'text-slate-500'
                                    }`}>
                                        <i className={form.payment_option === 'half' ? 'fa-solid fa-circle-dot' : 'fa-regular fa-circle'}></i>
                                        50% Down Payment
                                    </div>
                                    <div className="flex justify-between text-[12px] mb-1.5">
                                        <span className="text-slate-500">Pay Now:</span>
                                        <strong className="text-slate-800">₱{halfPayment.toLocaleString()}</strong>
                                    </div>
                                    <div className="flex justify-between text-[12px] border-t border-dashed border-slate-200 pt-1.5">
                                        <span className="text-slate-500">Remaining:</span>
                                        <strong className="text-slate-800">₱{halfPayment.toLocaleString()}</strong>
                                    </div>
                                </button>

                                <button
                                    type="button"
                                    onClick={() => updateField('payment_option', 'full')}
                                    className={`rounded-xl p-3.5 text-center transition ${
                                        form.payment_option === 'full'
                                            ? 'bg-emerald-50 border border-emerald-200'
                                            : 'bg-slate-50 border border-slate-200'
                                    }`}
                                >
                                    <div className={`text-[13px] font-bold flex items-center justify-center gap-1.5 mb-2 ${
                                        form.payment_option === 'full' ? 'text-emerald-600' : 'text-slate-500'
                                    }`}>
                                        <i className={form.payment_option === 'full' ? 'fa-solid fa-circle-dot' : 'fa-regular fa-circle'}></i>
                                        Full Payment
                                    </div>
                                    <div className="flex justify-between text-[12px] mb-1.5">
                                        <span className="text-slate-500">Pay Now:</span>
                                        <strong className="text-slate-800">₱{totalCharges.toLocaleString()}</strong>
                                    </div>
                                    <div className="flex justify-between text-[12px] border-t border-dashed border-slate-200 pt-1.5">
                                        <span className="text-slate-500">Remaining:</span>
                                        <strong className="text-slate-800">₱0</strong>
                                    </div>
                                </button>
                            </div>
                        </div>

                        {/* Summary of Charges */}
                        <div className="bg-emerald-50 rounded-xl p-4">
                            <div className="flex items-center gap-2 text-[14px] font-bold text-emerald-800 mb-3">
                                <i className="fa-solid fa-peso-sign"></i>
                                Summary of Charges
                            </div>
                            <div className="flex justify-between text-[12px] text-emerald-700 mb-2">
                                <span>Room Rate</span>
                                <strong>₱{roomRate.toLocaleString()}.00</strong>
                            </div>
                            <div className="flex justify-between text-[12px] text-emerald-700 mb-2">
                                <span>₱{roomRate.toLocaleString()}/day x ({days}) day(s)</span>
                                <strong>₱{roomSubtotal.toLocaleString()}.00</strong>
                            </div>
                            {form.has_companion && (
                                <div className="flex justify-between text-[12px] text-emerald-700 mb-2">
                                    <span>Companion Fee:</span>
                                    <strong>₱{companionFee}.00</strong>
                                </div>
                            )}
                            <div className="flex justify-between text-[14px] font-extrabold text-emerald-600 border-t border-emerald-200 pt-2.5 mt-2.5">
                                <span>Total Charges:</span>
                                <strong>₱{totalCharges.toLocaleString()}.00</strong>
                            </div>
                            <div className="flex justify-between text-[12px] text-emerald-700 mt-2">
                                <span>{form.payment_option === 'half' ? '50% (Down Payment):' : 'Full Payment:'}</span>
                                <strong>₱{payNow.toLocaleString()}.00</strong>
                            </div>
                            <div className="flex justify-between text-[14px] font-extrabold text-emerald-700 mt-1.5">
                                <span>Remaining Balance:</span>
                                <strong>₱{remaining.toLocaleString()}.00</strong>
                            </div>
                        </div>

                        {/* Payment Verification */}
                        <div className="bg-amber-50 border border-amber-200 rounded-xl p-4">
                            <div className="flex items-center gap-2 text-[14px] font-bold text-amber-800 mb-2.5">
                                <i className="fa-solid fa-mobile-screen-button"></i>
                                Payment Verification Required
                            </div>
                            <p className="text-[12px] text-amber-700 mb-3">
                                Pay via GCash to secure your reservation
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

                            {/* Reference Number */}
                            <div className="mb-3">
                                <label className="block text-[12px] font-semibold text-amber-800 mb-1.5">
                                    GCash Reference Number <span className="text-rose-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    value={form.reference_no}
                                    onChange={(e) => updateField('reference_no', e.target.value)}
                                    placeholder="e.g. GC-20260401-12345"
                                    className="w-full bg-white border border-amber-200 rounded-lg px-3 py-2.5 text-[13px] outline-none focus:border-amber-400"
                                />
                            </div>

                            {/* Upload */}
                            <label className="border border-dashed border-amber-300 rounded-lg p-3 flex items-center gap-3 bg-white cursor-pointer mb-2">
                                <span className="bg-white border border-amber-300 px-3 py-1.5 rounded-md text-[12px] font-bold text-amber-700">
                                    Choose File
                                </span>
                                <span className="text-[12px] text-slate-400">
                                    {form.receipt ? form.receipt.name : 'No file chosen'}
                                </span>
                                <input
                                    type="file"
                                    accept="image/*"
                                    onChange={(e) => updateField('receipt', e.target.files[0] || null)}
                                    className="hidden"
                                />
                            </label>
                            <div className="flex items-center gap-1.5 text-[11px] text-amber-700 mb-3">
                                <i className="fa-solid fa-arrow-up-from-bracket"></i>
                                Upload a screenshot of your GCash payment receipt
                            </div>

                            <div className="bg-amber-100 rounded-lg p-3 flex gap-2 text-[12px] text-amber-900 leading-relaxed">
                                <i className="fa-solid fa-circle-info mt-0.5"></i>
                                <span>
                                    <strong>Important:</strong> You must upload your payment proof before submitting your reservation. Your payment and reservation will be reviewed by the admin. You will be notified once your reservation is approved or rejected.
                                </span>
                            </div>
                        </div>

                        {/* Footer */}
                        <div className="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                            <button
                                onClick={closeReserve}
                                className="text-slate-600 text-[13px] font-semibold px-4 py-2"
                            >
                                Cancel
                            </button>
                            <button
                                onClick={handleSubmit}
                                disabled={saving}
                                className="bg-[#02122c] hover:bg-[#0d2347] disabled:opacity-50 text-white px-6 py-2.5 rounded-lg text-[13px] font-bold"
                            >
                                {saving ? 'Submitting…' : 'Submit Reservation'}
                            </button>
                        </div>
                    </div>
                </Modal>
            )}
        </>
    );
}