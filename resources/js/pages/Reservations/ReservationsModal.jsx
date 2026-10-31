// resources/js/pages/Reservations/ReservationsModal.jsx
import React, { useState, useEffect, useRef, useMemo } from "react";
import Modal from "../../components/Modal";

const csrf = () =>
    document.querySelector('meta[name="csrf-token"]')?.content ||
    document.querySelector('input[name="_token"]')?.value ||
    "";

const FALLBACK_ROOM = {
    id: null,
    name: "Solo Room",
    roomNumber: "ROOM 101",
    floor: 1,
    status: "Available",
    is_bookable: true,
    has_reserved: false,
    empty_beds: 1,
    price: 600,
    img: "https://images.unsplash.com/photo-1598928506311-c55ded91a20c?auto=format&fit=crop&w=600&q=80",
    pax: "1-2 pax",
    beds: "1 bed",
    total_beds: 1,
    bath: "1 bath",
    description:
        "Cozy room with modern finishes, private CR and city views. Perfect for working professionals and students.",
    estDate: "March 25, 2026",
    estApprox: "Approx. 2 days",
    tourId: null,
    bed_slots: [],
    is_female_only: false,
    is_male_only: false,
};

// ── Due-date tiers (mirrors computeDueDate() on the server) ──
const TIER_1_MAX_DAYS = 1;
const TIER_2_MAX_DAYS = 6;
const TIER_3_MAX_DAYS = 31;
const TIER_4_MAX_DAYS = 100;
const TIER_5_MAX_DAYS = 364;

function computeDueDatePreview(checkInDate, checkInTime, checkOutDate, checkOutTime) {
    if (!checkInDate || !checkOutDate) return null;

    const inD  = new Date(checkInDate  + "T" + (checkInTime  || "00:00"));
    const outD = new Date(checkOutDate + "T" + (checkOutTime || "00:00"));
    if (isNaN(inD) || isNaN(outD) || outD <= inD) return null;

    const msPerDay = 1000 * 60 * 60 * 24;
    const days = Math.max(1, Math.ceil((outD - inD) / msPerDay));

    if (days <= TIER_1_MAX_DAYS) {
        return new Date(outD);
    }

    const due = new Date(inD);
    if (days <= TIER_2_MAX_DAYS) {
        due.setDate(due.getDate() + 3);
    } else if (days <= TIER_3_MAX_DAYS) {
        due.setDate(due.getDate() + 7);
    } else if (days <= TIER_4_MAX_DAYS) {
        due.setMonth(due.getMonth() + 1);
    } else if (days <= TIER_5_MAX_DAYS) {
        due.setMonth(due.getMonth() + 2);
    } else {
        due.setMonth(due.getMonth() + 3);
    }
    return due;
}

function formatDateLong(d) {
    if (!d) return "";
    return d.toLocaleDateString("en-US", {
        year: "numeric", month: "short", day: "numeric",
    });
}

function describeDueRule(days) {
    if (days <= TIER_1_MAX_DAYS) return "on your check-out date";
    if (days <= TIER_2_MAX_DAYS) return "3 days after check-in";
    if (days <= TIER_3_MAX_DAYS) return "7 days after check-in";
    if (days <= TIER_4_MAX_DAYS) return "1 month after check-in";
    if (days <= TIER_5_MAX_DAYS) return "2 months after check-in";
    return "3 months after check-in";
}

// ── sessionStorage helpers for modal state persistence ──
const STORAGE_KEY = "reservation_modal_state";

function saveModalState(state) {
    try {
        // Don't persist File objects (they can't be serialized)
        const { receipt, ...rest } = state.form;
        sessionStorage.setItem(STORAGE_KEY, JSON.stringify({
            showDetails: state.showDetails,
            showReserve: state.showReserve,
            room: state.room,
            form: rest,
            // Track that receipt needs re-upload
            hadReceipt: !!receipt,
        }));
    } catch (e) {
        console.warn("Failed to save modal state", e);
    }
}

function loadModalState() {
    try {
        const raw = sessionStorage.getItem(STORAGE_KEY);
        if (!raw) return null;
        return JSON.parse(raw);
    } catch (e) {
        return null;
    }
}

function clearModalState() {
    try {
        sessionStorage.removeItem(STORAGE_KEY);
    } catch (e) {}
}

export default function ReservationsModal() {
    const [showDetails, setShowDetails] = useState(false);
    const [showReserve, setShowReserve] = useState(false);
    const [showCancel, setShowCancel] = useState(false);
    const [cancelTarget, setCancelTarget] = useState(null);

    // State for the Reservation Policy modal
    const [showPolicyModal, setShowPolicyModal] = useState(false);

    const [room, setRoom] = useState(FALLBACK_ROOM);
    const [saving, setSaving] = useState(false);

    const inFlightRef = useRef(false);
    const cancelInFlightRef = useRef(false);

    const [reservationsData, setReservationsData] = useState({});

    const [form, setForm] = useState({
        check_in_date: "",
        check_in_time: "",
        check_out_date: "",
        check_out_time: "",
        has_companion: false,
        notes: "",
        payment_option: "half",
        reference_no: "",
        receipt: null,
        room_bed_id: "",
        agreed_to_terms: false,
    });

    // Track whether we need to warn about re-uploading receipt
    const [needsReceiptReupload, setNeedsReceiptReupload] = useState(false);

    // 1. Read default payload on mount AND restore any saved modal state
    useEffect(() => {
        const el = document.getElementById("tenant-reservations-modals");
        
        // Initial load
        if (el?.dataset?.reservations) {
            try {
                setReservationsData(JSON.parse(el.dataset.reservations));
            } catch (e) {
                console.error("Invalid reservations JSON", e);
            }
        }

        // ── Restore saved modal state (from before navigating to tour) ──
        const saved = loadModalState();
        if (saved) {
            if (saved.room) setRoom({ ...FALLBACK_ROOM, ...saved.room });
            if (saved.form) {
                setForm((f) => ({ ...f, ...saved.form }));
            }
            if (saved.showDetails) setShowDetails(true);
            if (saved.showReserve) {
                setShowReserve(true);
                if (saved.hadReceipt) {
                    setNeedsReceiptReupload(true);
                }
            }
            // Clear after restoring so a fresh reload doesn't re-open
            // (but we keep it until the user closes the modal or navigates)
            clearModalState();
        }

        // ── Live update listener ──
        const handleLiveUpdate = (e) => {
            if (e.detail) {
                setReservationsData(e.detail);
            } else if (el?.dataset?.reservations) {
                try {
                    setReservationsData(JSON.parse(el.dataset.reservations));
                } catch (err) {}
            }
        };

        window.addEventListener("reservation:data-updated", handleLiveUpdate);

        return () => {
            window.removeEventListener("reservation:data-updated", handleLiveUpdate);
        };
    }, []);

    // 2. Persist modal state whenever it changes (so tour navigation can restore it)
    useEffect(() => {
        if (showDetails || showReserve) {
            saveModalState({ showDetails, showReserve, room, form });
        }
    }, [showDetails, showReserve, room, form]);

    // 3. Global click listener (Open Details, Open Reserve, Open Cancel)
    useEffect(() => {
        const handler = (e) => {
            const detailsBtn = e.target.closest(".open-room-details-btn");
            if (detailsBtn) {
                const data = JSON.parse(detailsBtn.dataset.room || "{}");
                setRoom({ ...FALLBACK_ROOM, ...data });
                setShowDetails(true);
                return;
            }

            const reserveBtn = e.target.closest(".open-reserve-modal-btn");
            if (reserveBtn) {
                inFlightRef.current = false;
                setSaving(false);

                const data = JSON.parse(reserveBtn.dataset.room || "{}");
                setRoom({ ...FALLBACK_ROOM, ...data });
                setForm({
                    check_in_date: "",
                    check_in_time: "",
                    check_out_date: "",
                    check_out_time: "",
                    has_companion: false,
                    notes: "",
                    payment_option: "half",
                    reference_no: "",
                    receipt: null,
                    room_bed_id: "",
                    agreed_to_terms: false,
                });
                setNeedsReceiptReupload(false);
                setShowReserve(true);
                return;
            }

            const cancelBtn = e.target.closest(".cancel-reservation-btn");
            if (cancelBtn) {
                const key = cancelBtn.dataset.resKey;
                const data = reservationsData[key];
                if (data) {
                    cancelInFlightRef.current = false;
                    setCancelTarget(data);
                    setShowCancel(true);
                }
                return;
            }
        };

        document.addEventListener("click", handler);
        return () => document.removeEventListener("click", handler);
    }, [reservationsData]);

    // ── Close helpers — also clear saved state ──
    const closeDetails = () => {
        setShowDetails(false);
        clearModalState();
    };

    const closeReserve = () => {
        if (inFlightRef.current) return;
        setShowReserve(false);
        setNeedsReceiptReupload(false);
        clearModalState();
    };

    const closeCancelModal = () => {
        if (cancelInFlightRef.current) return;
        setShowCancel(false);
        setCancelTarget(null);
    };

    const closePolicyModal = () => {
        setShowPolicyModal(false);
    };

    const updateField = (key, value) =>
        setForm((f) => ({ ...f, [key]: value }));

    // ============================================================
    // Derived charge calculations
    // ============================================================
    const roomRate = Number(room.price) || 0;
    const companionFee = form.has_companion ? 150 : 0;

    const computeDays = () => {
        const inD = new Date(form.check_in_date);
        const outD = new Date(form.check_out_date);
        if (isNaN(inD) || isNaN(outD) || outD <= inD) return 1;
        const diff = Math.ceil((outD - inD) / (1000 * 60 * 60 * 24));
        return diff > 0 ? diff : 1;
    };

    const days = computeDays();
    const roomSubtotal = roomRate * days;
    const totalCharges = roomSubtotal + companionFee;
    const halfPayment = totalCharges / 2;
    const payNow = form.payment_option === "half" ? halfPayment : totalCharges;
    const remaining = totalCharges - payNow;

    // ============================================================
    // 24-hour minimum enforcement (client side)
    // ============================================================
    const minCheckOutDate = useMemo(() => {
        if (!form.check_in_date) return "";
        const inD = new Date(form.check_in_date + "T00:00:00");
        if (isNaN(inD)) return "";
        inD.setDate(inD.getDate() + 1);
        const yyyy = inD.getFullYear();
        const mm = String(inD.getMonth() + 1).padStart(2, "0");
        const dd = String(inD.getDate()).padStart(2, "0");
        return `${yyyy}-${mm}-${dd}`;
    }, [form.check_in_date]);

    const meets24hMinimum = useMemo(() => {
        if (!form.check_in_date || !form.check_out_date) return true;
        const inStr = form.check_in_date + "T" + (form.check_in_time || "00:00");
        const outStr = form.check_out_date + "T" + (form.check_out_time || "00:00");
        const inD = new Date(inStr);
        const outD = new Date(outStr);
        if (isNaN(inD) || isNaN(outD)) return false;
        return (outD - inD) >= 24 * 60 * 60 * 1000;
    }, [form.check_in_date, form.check_in_time, form.check_out_date, form.check_out_time]);

    // ============================================================
    // Due-date preview
    // ============================================================
    const dueDatePreview = useMemo(
        () => computeDueDatePreview(
            form.check_in_date,
            form.check_in_time,
            form.check_out_date,
            form.check_out_time,
        ),
        [form.check_in_date, form.check_in_time, form.check_out_date, form.check_out_time],
    );

    // ============================================================
    // Open 360° tour — same tab, with `from` back-link
    // ============================================================
    const openTour = () => {
        if (!room.tourId) return;
        // Persist current state so the tour's back button can restore it
        saveModalState({ showDetails, showReserve, room, form });
        const from = encodeURIComponent(window.location.pathname);
        window.location.href = `/tour/${room.tourId}?from=${from}`;
    };

    // ============================================================
    // Submit reservation
    // ============================================================
    const handleSubmit = async () => {
        if (inFlightRef.current) return;

        if (!form.check_in_date || !form.check_out_date) {
            alert("Please select both check-in and check-out dates.");
            return;
        }

        if (!meets24hMinimum) {
            alert(
                "The minimum stay is 24 hours (1 full day). Please extend your check-out date or time."
            );
            return;
        }

        if (room.bed_slots && room.bed_slots.length > 0 && !form.room_bed_id) {
            alert("Please select a bed slot.");
            return;
        }

        if (room.bed_slots && room.bed_slots.length > 0) {
            const availableSlots = room.bed_slots.filter(
                (s) => s.status !== "Occupied",
            );
            if (availableSlots.length === 0) {
                alert(
                    "All beds in this room are currently occupied. Please pick another room.",
                );
                return;
            }
        }

        if (!form.reference_no.trim()) {
            alert("Please enter your GCash reference number.");
            return;
        }
        if (!form.receipt) {
            alert("Please upload your payment proof.");
            return;
        }
        if (!form.agreed_to_terms) {
            alert("Please agree to the Reservation Policy.");
            return;
        }

        inFlightRef.current = true;
        setSaving(true);

        try {
            const fd = new FormData();
            fd.append("room_id", room.id);
            fd.append("check_in_date", form.check_in_date);
            fd.append("check_in_time", form.check_in_time);
            fd.append("check_out_date", form.check_out_date);
            fd.append("check_out_time", form.check_out_time);
            fd.append("has_companion", form.has_companion ? "1" : "0");
            fd.append("notes", form.notes);
            fd.append("payment_option", form.payment_option);
            fd.append("reference_no", form.reference_no);
            fd.append("receipt", form.receipt);
            fd.append("room_bed_id", form.room_bed_id);
            fd.append("agreed_to_terms", form.agreed_to_terms ? "1" : "0");

            const res = await fetch("/tenants/reservations", {
                method: "POST",
                headers: {
                    Accept: "application/json",
                    "X-CSRF-TOKEN": csrf(),
                },
                body: fd,
            });

            const data = await res.json().catch(() => ({}));
            if (!res.ok)
                throw new Error(
                    data.message || "Failed to submit reservation.",
                );

            setShowReserve(false);
            clearModalState();
            setNeedsReceiptReupload(false);
            window.dispatchEvent(new Event("reservation:refresh"));
            inFlightRef.current = false;
            setSaving(false);
        } catch (err) {
            alert(err.message);
            inFlightRef.current = false;
            setSaving(false);
        }
    };

    const handleCopyGCash = () => {
        navigator.clipboard?.writeText("0917-123-4567");
        alert("GCash number copied!");
    };

    const confirmCancelReservation = async () => {
        if (!cancelTarget) return;
        if (cancelInFlightRef.current) return;

        cancelInFlightRef.current = true;
        setSaving(true);

        try {
            const res = await fetch(
                `/tenants/reservations/${cancelTarget.id}/cancel`,
                {
                    method: "POST",
                    headers: {
                        Accept: "application/json",
                        "X-CSRF-TOKEN": csrf(),
                    },
                },
            );
            const result = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(result.message || "Failed to cancel.");

            setShowCancel(false);
            setCancelTarget(null);
            window.dispatchEvent(new Event("reservation:refresh"));
        } catch (err) {
            alert(err.message);
            cancelInFlightRef.current = false;
            setSaving(false);
        }
    };

    return (
        <>
            {/* DETAILS MODAL */}
            {showDetails && (
                <Modal onClose={closeDetails} maxWidth="max-w-[540px]">
                    <div className="p-5 pb-4 flex items-center justify-between border-b border-slate-100">
                        <h3 className="text-lg font-bold text-slate-900">
                            {room.name}
                        </h3>
                        <button
                            onClick={closeDetails}
                            className="text-slate-400 hover:text-slate-700 text-lg"
                        >
                            <i className="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div className="p-6 overflow-y-auto flex flex-col gap-4">
                        <div className="relative h-[200px] rounded-xl overflow-hidden">
                            <img
                                src={room.img}
                                alt={room.name}
                                className="w-full h-full object-cover"
                            />

                            <span
                                className={`absolute top-3 left-3 text-[11px] font-bold px-3 py-1 rounded-full ${
                                    room.has_reserved
                                        ? "bg-emerald-100 text-emerald-700 border border-emerald-200"
                                        : room.status === "Occupied"
                                        ? "bg-rose-200 text-rose-700"
                                        : room.status === "Available"
                                          ? "bg-green-100 text-green-700"
                                          : "bg-amber-100 text-amber-700"
                                }`}
                            >
                                {room.has_reserved ? "Reserved by You" : room.status}
                            </span>

                            {room.tourId && (
                                <button
                                    type="button"
                                    onClick={openTour}
                                    className="absolute top-3 right-3 bg-black/60 hover:bg-black/80 text-white text-[10px] font-semibold px-2.5 py-1 rounded-full backdrop-blur transition"
                                >
                                    <i className="fa-solid fa-camera mr-1"></i>{" "}
                                    360° Tour
                                </button>
                            )}

                            <span className="absolute bottom-3 left-3 bg-blue-50 text-blue-600 text-[10px] font-extrabold uppercase px-2.5 py-1 rounded-md">
                                {room.roomNumber}
                            </span>

                            {room.status === "Occupied" && !room.has_reserved && (
                                <div className="absolute bottom-3 right-3 bg-white/95 px-3 py-1.5 rounded-md text-[10px] text-slate-700 text-right shadow">
                                    <div>
                                        Estimated Availability:{" "}
                                        <strong>{room.estDate}</strong>
                                    </div>
                                    <div className="text-slate-500">
                                        {room.estApprox}
                                    </div>
                                </div>
                            )}
                        </div>

                        <p className="text-[13px] text-slate-500 leading-relaxed">
                            {room.description}
                        </p>

                        <div className="text-[13px] font-bold text-slate-900">
                            Room Specifications
                        </div>
                        <div className="grid grid-cols-3 gap-2.5">
                            {[
                                { label: "ROOM TYPE", value: room.name },
                                { label: "MAX CAPACITY", value: room.pax },
                                { label: "BEDS", value: room.beds },
                                {
                                    label: "AVAILABLE SLOTS",
                                    value: room.is_bookable ? (room.empty_beds || "1") : "0",
                                },
                                {
                                    label: "DAILY RENT",
                                    value: `₱${roomRate.toLocaleString()}`,
                                },
                                { label: "FLOOR", value: room.floor },
                            ].map((s, i) => (
                                <div
                                    key={i}
                                    className="bg-slate-50 rounded-lg p-3 text-center"
                                >
                                    <div className="text-[9.5px] font-bold text-slate-400 uppercase mb-1">
                                        {s.label}
                                    </div>
                                    <div className="text-[12px] font-bold text-slate-900">
                                        {s.value}
                                    </div>
                                </div>
                            ))}
                        </div>

                        <div className="grid grid-cols-2 gap-3 mt-2">
                            <button
                                onClick={closeDetails}
                                className="bg-[#02122c] text-white rounded-lg py-2.5 text-[13px] font-semibold"
                            >
                                Close
                            </button>
                            {room.has_reserved ? (
                                <button
                                    disabled
                                    className="bg-emerald-100 text-emerald-700 rounded-lg py-2.5 text-[13px] font-semibold cursor-not-allowed"
                                >
                                    Already Reserved
                                </button>
                            ) : room.is_bookable ? (
                                <button
                                    onClick={() => {
                                        closeDetails();
                                        inFlightRef.current = false;
                                        setSaving(false);
                                        setShowReserve(true);
                                    }}
                                    className="bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg py-2.5 text-[13px] font-semibold"
                                >
                                    Reserve Room
                                </button>
                            ) : (
                                <button
                                    disabled
                                    className="bg-slate-100 text-slate-400 rounded-lg py-2.5 text-[13px] font-semibold cursor-not-allowed"
                                >
                                    Not Available
                                </button>
                            )}
                        </div>
                    </div>
                </Modal>
            )}

            {/* RESERVE MODAL */}
            {showReserve && (
                <Modal onClose={closeReserve} maxWidth="max-w-[580px]">
                    <button
                        onClick={closeReserve}
                        disabled={saving}
                        className="absolute top-5 right-5 text-slate-400 hover:text-slate-700 text-lg z-10 disabled:opacity-50"
                    >
                        <i className="fa-solid fa-xmark"></i>
                    </button>

                    <div className="p-6 overflow-y-auto flex flex-col gap-5">
                        <h3 className="text-lg font-bold text-slate-900">
                            Reserve - {room.name}
                        </h3>

                        {needsReceiptReupload && (
                            <div className="bg-amber-50 border border-amber-200 rounded-lg p-2.5 flex items-start gap-2">
                                <i className="fa-solid fa-triangle-exclamation text-amber-500 mt-0.5"></i>
                                <p className="text-[12px] text-amber-800 leading-snug">
                                    You navigated away from this form. For
                                    security, your uploaded receipt was not
                                    saved — please re-upload your payment proof
                                    before submitting.
                                </p>
                            </div>
                        )}

                        <div className="bg-emerald-50 rounded-xl p-3 flex items-center gap-3">
                            <img
                                src={room.img}
                                alt={room.name}
                                className="w-12 h-12 rounded-lg object-cover"
                            />
                            <div>
                                <h4 className="text-[14px] font-bold text-emerald-800">
                                    {room.name}
                                </h4>
                                <p className="text-[11px] text-emerald-700">
                                    Floor {room.floor} · {room.roomNumber}
                                </p>
                                <h5 className="text-[13px] font-extrabold text-emerald-600 mt-0.5">
                                    ₱{roomRate.toLocaleString()}.00/day
                                </h5>
                            </div>
                        </div>

                        <div>
                            <div className="text-[14px] font-bold text-slate-900 mb-3">
                                Reservation Information
                            </div>

                            <div className="grid grid-cols-2 gap-4 mb-3">
                                <div>
                                    <label className="block text-[12px] font-semibold text-slate-600 mb-1.5">
                                        Check-in Date{" "}
                                        <span className="text-rose-500">*</span>
                                    </label>
                                    <input
                                        type="date"
                                        value={form.check_in_date}
                                        min={new Date().toISOString().split("T")[0]}
                                        onChange={(e) => {
                                            const value = e.target.value;
                                            updateField("check_in_date", value);
                                            if (value) {
                                                const inD = new Date(value + "T00:00:00");
                                                inD.setDate(inD.getDate() + 1);
                                                const yyyy = inD.getFullYear();
                                                const mm = String(inD.getMonth() + 1).padStart(2, "0");
                                                const dd = String(inD.getDate()).padStart(2, "0");
                                                const minOut = `${yyyy}-${mm}-${dd}`;
                                                if (
                                                    form.check_out_date &&
                                                    form.check_out_date < minOut
                                                ) {
                                                    updateField("check_out_date", minOut);
                                                }
                                            }
                                        }}
                                        className="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-[13px] outline-none focus:border-blue-400"
                                    />
                                </div>
                                <div>
                                    <label className="block text-[12px] font-semibold text-slate-600 mb-1.5">
                                        Check-in Time{" "}
                                        <span className="text-rose-500">*</span>
                                    </label>
                                    <input
                                        type="time"
                                        value={form.check_in_time}
                                        onChange={(e) =>
                                            updateField(
                                                "check_in_time",
                                                e.target.value,
                                            )
                                        }
                                        className="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-[13px] outline-none focus:border-blue-400"
                                    />
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-[12px] font-semibold text-slate-600 mb-1.5">
                                        Check-out Date{" "}
                                        <span className="text-rose-500">*</span>
                                    </label>
                                    <input
                                        type="date"
                                        value={form.check_out_date}
                                        min={minCheckOutDate || undefined}
                                        onChange={(e) =>
                                            updateField(
                                                "check_out_date",
                                                e.target.value,
                                            )
                                        }
                                        className="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-[13px] outline-none focus:border-blue-400"
                                    />
                                </div>
                                <div>
                                    <label className="block text-[12px] font-semibold text-slate-600 mb-1.5">
                                        Check-out Time{" "}
                                        <span className="text-rose-500">*</span>
                                    </label>
                                    <input
                                        type="time"
                                        value={form.check_out_time}
                                        onChange={(e) =>
                                            updateField(
                                                "check_out_time",
                                                e.target.value,
                                            )
                                        }
                                        className="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-[13px] outline-none focus:border-blue-400"
                                    />
                                </div>
                            </div>

                            <p className="text-[13px] font-semibold text-slate-600 mt-4 pl-1">
                                Total Stay Duration: {days} night
                                {days > 1 ? "s" : ""}
                            </p>

                            {form.check_in_date && minCheckOutDate && (
                                <p className="text-[11px] text-slate-400 mt-1 pl-1">
                                    <i className="fa-solid fa-circle-info mr-1"></i>
                                    Minimum stay is 24 hours. Earliest check-out:{" "}
                                    <strong className="text-slate-600">
                                        {minCheckOutDate}
                                    </strong>
                                </p>
                            )}

                            {!meets24hMinimum && (
                                <div className="bg-rose-50 border border-rose-200 rounded-lg p-2.5 mt-2 flex items-start gap-2">
                                    <i className="fa-solid fa-circle-exclamation text-rose-500 mt-0.5"></i>
                                    <p className="text-[12px] text-rose-700 leading-snug">
                                        Your selected duration is under 24 hours.
                                        Please extend your check-out date or
                                        time — the minimum stay is 1 full day.
                                    </p>
                                </div>
                            )}

                            {dueDatePreview && (
                                <div className="bg-indigo-50 border border-indigo-100 rounded-lg p-2.5 mt-2 flex items-start gap-2">
                                    <i className="fa-regular fa-calendar-check text-indigo-500 mt-0.5"></i>
                                    <p className="text-[12px] text-indigo-800 leading-snug">
                                        <strong>Payment due date:</strong>{" "}
                                        <span className="font-bold">
                                            {formatDateLong(dueDatePreview)}
                                        </span>
                                        <br />
                                        <span className="text-indigo-600">
                                            For a {days}-day stay, the balance
                                            is due {describeDueRule(days)}.
                                        </span>
                                    </p>
                                </div>
                            )}
                        </div>

                        {room.bed_slots && room.bed_slots.length > 0 && (
                            <div className="bg-teal-50 border border-teal-100 rounded-xl p-4">
                                <div className="flex items-center gap-2 text-[14px] font-bold text-teal-800 mb-2">
                                    <i className="fa-solid fa-bed"></i>
                                    Bedspace Slot Selection
                                </div>

                                {room.is_female_only && (
                                    <div className="bg-teal-100/50 text-teal-800 text-[12px] p-2.5 rounded-lg mb-3">
                                        <strong>Female-Only Dormitory</strong>
                                        <br />
                                        This unit is restricted to female
                                        tenants only.
                                    </div>
                                )}
                                {room.is_male_only && (
                                    <div className="bg-teal-100/50 text-teal-800 text-[12px] p-2.5 rounded-lg mb-3">
                                        <strong>Male-Only Dormitory</strong>
                                        <br />
                                        This unit is restricted to male tenants
                                        only.
                                    </div>
                                )}

                                <label className="block text-[12px] font-semibold text-teal-800 mb-2">
                                    Select Your Bed Slot{" "}
                                    <span className="text-rose-500">*</span>
                                </label>
                                <div className="grid grid-cols-2 gap-2">
                                    {room.bed_slots.map((slot) => {
                                        const isSelected =
                                            form.room_bed_id === slot.id;
                                        const isOccupied =
                                            slot.status === "Occupied";

                                        return (
                                            <button
                                                key={slot.id}
                                                type="button"
                                                disabled={isOccupied}
                                                onClick={() =>
                                                    updateField(
                                                        "room_bed_id",
                                                        slot.id,
                                                    )
                                                }
                                                className={`
                                                    flex flex-col items-start p-3 rounded-lg border text-left transition
                                                    ${
                                                        isOccupied
                                                            ? "bg-slate-100 border-slate-200 opacity-60 cursor-not-allowed"
                                                            : isSelected
                                                              ? "bg-teal-600 border-teal-600 text-white shadow-sm"
                                                              : "bg-white border-slate-200 hover:border-teal-400"
                                                    }
                                                `}
                                            >
                                                <span
                                                    className={`text-[13px] font-bold ${isSelected ? "text-white" : "text-slate-800"}`}
                                                >
                                                    {slot.label}
                                                </span>
                                                <span
                                                    className={`text-[11px] font-semibold mt-0.5 ${isSelected ? "text-teal-100" : isOccupied ? "text-rose-500" : "text-emerald-500"}`}
                                                >
                                                    {slot.status}
                                                </span>
                                            </button>
                                        );
                                    })}
                                </div>
                            </div>
                        )}

                        {room.total_beds === 1 && (
                            <div className="bg-blue-50 rounded-xl p-4">
                                <div className="flex items-center gap-2 text-[13px] font-bold text-blue-800 mb-3">
                                    <i className="fa-regular fa-user"></i>
                                    Room Options
                                </div>
                                <label className="flex items-start gap-2.5 text-[13px] text-blue-800 font-medium cursor-pointer">
                                    <input
                                        type="checkbox"
                                        checked={form.has_companion}
                                        onChange={(e) =>
                                            updateField(
                                                "has_companion",
                                                e.target.checked,
                                            )
                                        }
                                        className="w-[18px] h-[18px] accent-blue-700 mt-0.5 cursor-pointer"
                                    />
                                    <span>
                                        I will have a companion (max 2 persons
                                        total)
                                    </span>
                                </label>
                                <p className="text-[12px] text-blue-500 ml-7 mt-1">
                                    Extra mattress fee: ₱150.00 per companion
                                </p>
                            </div>
                        )}

                        <div>
                            <label className="block text-[12px] font-semibold text-slate-600 mb-1.5">
                                Additional Notes
                            </label>
                            <textarea
                                value={form.notes}
                                onChange={(e) =>
                                    updateField("notes", e.target.value)
                                }
                                placeholder="Any special requirements or questions for the admin..."
                                className="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-[13px] outline-none resize-none h-[70px] focus:border-blue-400"
                            />
                        </div>

                        <div>
                            <label className="block text-[13px] font-bold text-slate-700 mb-2.5">
                                Payment Option{" "}
                                <span className="text-rose-500">*</span>
                            </label>
                            <div className="grid grid-cols-2 gap-4">
                                <button
                                    type="button"
                                    onClick={() =>
                                        updateField("payment_option", "half")
                                    }
                                    className={`rounded-xl p-3.5 text-center transition ${
                                        form.payment_option === "half"
                                            ? "bg-emerald-50 border border-emerald-200"
                                            : "bg-slate-50 border border-slate-200"
                                    }`}
                                >
                                    <div
                                        className={`text-[13px] font-bold flex items-center justify-center gap-1.5 mb-2 ${
                                            form.payment_option === "half"
                                                ? "text-emerald-600"
                                                : "text-slate-500"
                                        }`}
                                    >
                                        <i
                                            className={
                                                form.payment_option === "half"
                                                    ? "fa-solid fa-circle-dot"
                                                    : "fa-regular fa-circle"
                                            }
                                        ></i>
                                        50% Down Payment
                                    </div>
                                    <div className="flex justify-between text-[12px] mb-1.5">
                                        <span className="text-slate-500">
                                            Pay Now:
                                        </span>
                                        <strong className="text-slate-800">
                                            ₱{halfPayment.toLocaleString()}
                                        </strong>
                                    </div>
                                    <div className="flex justify-between text-[12px] border-t border-dashed border-slate-200 pt-1.5">
                                        <span className="text-slate-500">
                                            Remaining:
                                        </span>
                                        <strong className="text-slate-800">
                                            ₱{halfPayment.toLocaleString()}
                                        </strong>
                                    </div>
                                </button>

                                <button
                                    type="button"
                                    onClick={() =>
                                        updateField("payment_option", "full")
                                    }
                                    className={`rounded-xl p-3.5 text-center transition ${
                                        form.payment_option === "full"
                                            ? "bg-emerald-50 border border-emerald-200"
                                            : "bg-slate-50 border border-slate-200"
                                    }`}
                                >
                                    <div
                                        className={`text-[13px] font-bold flex items-center justify-center gap-1.5 mb-2 ${
                                            form.payment_option === "full"
                                                ? "text-emerald-600"
                                                : "text-slate-500"
                                        }`}
                                    >
                                        <i
                                            className={
                                                form.payment_option === "full"
                                                    ? "fa-solid fa-circle-dot"
                                                    : "fa-regular fa-circle"
                                            }
                                        ></i>
                                        Full Payment
                                    </div>
                                    <div className="flex justify-between text-[12px] mb-1.5">
                                        <span className="text-slate-500">
                                            Pay Now:
                                        </span>
                                        <strong className="text-slate-800">
                                            ₱{totalCharges.toLocaleString()}
                                        </strong>
                                    </div>
                                    <div className="flex justify-between text-[12px] border-t border-dashed border-slate-200 pt-1.5">
                                        <span className="text-slate-500">
                                            Remaining:
                                        </span>
                                        <strong className="text-slate-800">
                                            ₱0
                                        </strong>
                                    </div>
                                </button>
                            </div>
                        </div>

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
                                <span>
                                    ₱{roomRate.toLocaleString()}/day x ({days})
                                    day(s)
                                </span>
                                <strong>
                                    ₱{roomSubtotal.toLocaleString()}.00
                                </strong>
                            </div>
                            {form.has_companion && (
                                <div className="flex justify-between text-[12px] text-emerald-700 mb-2">
                                    <span>Companion Fee:</span>
                                    <strong>₱{companionFee}.00</strong>
                                </div>
                            )}
                            <div className="flex justify-between text-[14px] font-extrabold text-emerald-600 border-t border-emerald-200 pt-2.5 mt-2.5">
                                <span>Total Charges:</span>
                                <strong>
                                    ₱{totalCharges.toLocaleString()}.00
                                </strong>
                            </div>
                            <div className="flex justify-between text-[12px] text-emerald-700 mt-2">
                                <span>
                                    {form.payment_option === "half"
                                        ? "50% (Down Payment):"
                                        : "Full Payment:"}
                                </span>
                                <strong>₱{payNow.toLocaleString()}.00</strong>
                            </div>
                            <div className="flex justify-between text-[14px] font-extrabold text-emerald-700 mt-1.5">
                                <span>Remaining Balance:</span>
                                <strong>
                                    ₱{remaining.toLocaleString()}.00
                                </strong>
                            </div>
                        </div>

                        <div className="bg-amber-50 border border-amber-200 rounded-xl p-4">
                            <div className="flex items-center gap-2 text-[14px] font-bold text-amber-800 mb-2.5">
                                <i className="fa-solid fa-mobile-screen-button"></i>
                                Payment Verification Required
                            </div>
                            <p className="text-[12px] text-amber-700 mb-3">
                                Pay via GCash to secure your reservation
                            </p>

                            <div className="flex justify-between text-[12px] mb-1.5">
                                <span className="text-amber-700">
                                    Account Name:
                                </span>
                                <strong className="text-amber-900">
                                    Leah Agujar
                                </strong>
                            </div>
                            <div className="flex justify-between text-[12px] mb-3">
                                <span className="text-amber-700">
                                    GCash Number:
                                </span>
                                <strong className="text-amber-900">
                                    0917-123-4567
                                </strong>
                            </div>

                            <button
                                type="button"
                                onClick={handleCopyGCash}
                                className="w-full bg-orange-500 hover:bg-orange-600 text-white rounded-lg py-2.5 text-[13px] font-bold flex items-center justify-center gap-2 mb-3 transition"
                            >
                                <i className="fa-regular fa-copy"></i>
                                Copy GCash Number
                            </button>

                            <div className="mb-3">
                                <label className="block text-[12px] font-semibold text-amber-800 mb-1.5">
                                    GCash Reference Number{" "}
                                    <span className="text-rose-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    value={form.reference_no}
                                    onChange={(e) =>
                                        updateField(
                                            "reference_no",
                                            e.target.value,
                                        )
                                    }
                                    placeholder="e.g. GC-20260401-12345"
                                    className="w-full bg-white border border-amber-200 rounded-lg px-3 py-2.5 text-[13px] outline-none focus:border-amber-400"
                                />
                            </div>

                            <label className="border border-dashed border-amber-300 rounded-lg p-3 flex items-center gap-3 bg-white cursor-pointer mb-2">
                                <span className="bg-white border border-amber-300 px-3 py-1.5 rounded-md text-[12px] font-bold text-amber-700">
                                    Choose File
                                </span>
                                <span className="text-[12px] text-slate-400">
                                    {form.receipt
                                        ? form.receipt.name
                                        : "No file chosen"}
                                </span>
                                <input
                                    type="file"
                                    accept="image/*"
                                    onChange={(e) => {
                                        updateField(
                                            "receipt",
                                            e.target.files[0] || null,
                                        );
                                        setNeedsReceiptReupload(false);
                                    }}
                                    className="hidden"
                                />
                            </label>
                            <div className="flex items-center gap-1.5 text-[11px] text-amber-700 mb-3">
                                <i className="fa-solid fa-arrow-up-from-bracket"></i>
                                Upload a screenshot of your GCash payment
                                receipt
                            </div>

                            <div className="bg-amber-100 rounded-lg p-3 flex gap-2 text-[12px] text-amber-900 leading-relaxed">
                                <i className="fa-solid fa-circle-info mt-0.5"></i>
                                <span>
                                    <strong>Important:</strong> You must upload
                                    your payment proof before submitting your
                                    reservation. Your payment and reservation
                                    will be reviewed by the admin. You will be
                                    notified once your reservation is approved
                                    or rejected.
                                </span>
                            </div>
                        </div>

                        <div className="bg-slate-50 border border-slate-200 rounded-xl p-4">
                            <label className="flex items-start gap-2.5 cursor-pointer">
                                <input
                                    type="checkbox"
                                    checked={form.agreed_to_terms}
                                    onChange={(e) =>
                                        updateField(
                                            "agreed_to_terms",
                                            e.target.checked,
                                        )
                                    }
                                    className="w-[18px] h-[18px] accent-slate-800 mt-0.5 cursor-pointer rounded border-slate-300"
                                />
                                <span className="text-[12.5px] text-slate-600 leading-relaxed">
                                    I have read, understood, and agree to the{" "}
                                    <button
                                        type="button"
                                        onClick={() => setShowPolicyModal(true)}
                                        className="text-emerald-600 font-bold underline hover:text-emerald-700"
                                    >
                                        Reservation Policy
                                    </button>
                                    . I understand the deposit, cancellation,
                                    and approval terms.{" "}
                                    <span className="text-rose-500">*</span>
                                </span>
                            </label>
                        </div>

                        <div className="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                            <button
                                onClick={closeReserve}
                                disabled={saving}
                                className="text-slate-600 text-[13px] font-semibold px-4 py-2 disabled:opacity-50"
                            >
                                Cancel
                            </button>
                            <button
                                onClick={handleSubmit}
                                disabled={saving || !meets24hMinimum}
                                className="bg-[#02122c] hover:bg-[#0d2347] disabled:opacity-50 disabled:cursor-not-allowed text-white px-6 py-2.5 rounded-lg text-[13px] font-bold"
                            >
                                {saving ? (
                                    <>
                                        <i className="fa-solid fa-spinner fa-spin mr-2"></i>
                                        Submitting…
                                    </>
                                ) : (
                                    "Submit Reservation"
                                )}
                            </button>
                        </div>
                    </div>
                </Modal>
            )}

            {/* CANCEL CONFIRMATION MODAL */}
            {showCancel && cancelTarget && (
                <Modal onClose={closeCancelModal} maxWidth="max-w-[460px]">
                    <div className="p-5 pb-4 flex items-center justify-between border-b border-slate-100 shrink-0">
                        <h3 className="text-lg font-bold text-slate-900">
                            Cancel Reservation
                        </h3>
                        <button
                            onClick={closeCancelModal}
                            disabled={saving}
                            className="text-slate-400 hover:text-slate-700 text-lg disabled:opacity-50"
                        >
                            <i className="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div className="p-6 text-center overflow-y-auto max-h-[60vh]">
                        <div className="w-16 h-16 mx-auto rounded-full bg-rose-50 flex items-center justify-center mb-4">
                            <i className="fa-solid fa-trash-can text-rose-500 text-2xl"></i>
                        </div>
                        <h4 className="text-lg font-bold text-slate-900 mb-2">
                            Are you sure?
                        </h4>
                        <p className="text-slate-500 text-[13px] mb-2">
                            You are about to cancel your reservation for:
                        </p>
                        <p className="font-bold text-slate-900 text-[15px] mb-4">
                            {cancelTarget.room}{" "}
                            <span className="text-slate-400 font-normal">
                                ({cancelTarget.key})
                            </span>
                        </p>
                        <div className="bg-amber-50 border border-amber-200 rounded-xl p-3 text-left">
                            <p className="text-[12px] text-amber-800 leading-snug">
                                <i className="fa-solid fa-circle-info mr-1"></i>
                                This will permanently remove your reservation
                                request and you will need to submit a new one if
                                you change your mind.{" "}
                                <strong>This action cannot be undone.</strong>
                            </p>
                        </div>
                    </div>

                    <div className="p-5 border-t border-slate-100 flex justify-end gap-3 shrink-0">
                        <button
                            onClick={closeCancelModal}
                            disabled={saving}
                            className="text-slate-600 text-[13.5px] font-semibold px-4 py-2 disabled:opacity-50"
                        >
                            Keep Reservation
                        </button>
                        <button
                            onClick={confirmCancelReservation}
                            disabled={saving}
                            className="bg-rose-500 hover:bg-rose-600 disabled:opacity-50 disabled:cursor-not-allowed text-white px-5 py-2.5 rounded-lg text-[13.5px] font-semibold inline-flex items-center gap-2 transition"
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

            {/* RESERVATION POLICY MODAL */}
            {showPolicyModal && (
                <Modal onClose={closePolicyModal} maxWidth="max-w-[540px]">
                    <div className="p-5 pb-4 flex items-center justify-between border-b border-slate-100 shrink-0">
                        <div className="flex items-center gap-3">
                            <i className="fa-regular fa-file-lines text-slate-500 text-xl"></i>
                            <h3 className="text-lg font-bold text-slate-900">
                                Reservation Policy
                            </h3>
                        </div>
                        <button
                            onClick={closePolicyModal}
                            className="text-slate-400 hover:text-slate-700 text-lg"
                        >
                            <i className="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div className="p-6 overflow-y-auto max-h-[70vh]">
                        <div className="text-[13.5px] text-slate-600 leading-relaxed space-y-3">
                            <p>All reservations at JLM Cozy Cabin are subject to room availability and the submission of complete and accurate reservation information.</p>
                            <p>Tenants must provide the required reservation details and select an available payment option before submitting their reservation. The available payment options may include a fifty percent (50%) down payment or full payment, depending on the applicable reservation requirements.</p>
                            <p>After submission, the reservation will be placed under Pending Verification status and will not be considered confirmed until reviewed by the Admin. The Admin will review the reservation details and submitted payment information before approving or rejecting the reservation.</p>
                            <p>An approved reservation will be confirmed by the system and the tenant will be notified of the reservation status. If the reservation does not meet the applicable requirements, the Admin may reject it and provide an appropriate reason.</p>
                            <p>Reservations may expire if the required requirements are not completed within the specified period or if the tenant fails to arrive on the scheduled move-in date, subject to the applicable reservation conditions.</p>
                            <p>Reservation cancellations, changes to reservation details, and room changes are subject to room availability, applicable policies, and Admin approval.</p>
                            <p>Upon move-in, tenants may be required to present a valid identification document and reservation confirmation for verification.</p>
                        </div>
                    </div>

                    <div className="p-5 border-t border-slate-100 flex justify-end shrink-0">
                        <button
                            onClick={closePolicyModal}
                            className="bg-[#02122c] hover:bg-[#0d2347] text-white px-6 py-2.5 rounded-lg text-[13px] font-bold transition"
                        >
                            Close
                        </button>
                    </div>
                </Modal>
            )}
        </>
    );
}