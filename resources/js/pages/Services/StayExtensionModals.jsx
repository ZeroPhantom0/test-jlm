// resources/js/pages/Services/StayExtensionModals.jsx
import React, { useState, useEffect } from "react";
import Modal from "../../components/Modal";

const csrf = () =>
    document.querySelector('meta[name="csrf-token"]')?.content ||
    document.querySelector('input[name="_token"]')?.value;

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

const formatMoney = (n) =>
    '₱' + Number(n).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

export default function StayExtensionModals() {
    const [showSubmit, setShowSubmit] = useState(false);
    const [showDetails, setShowDetails] = useState(false);
    const [showDelete, setShowDelete] = useState(false);
    const [request, setRequest] = useState(null);
    const [deleteTarget, setDeleteTarget] = useState(null);
    const [saving, setSaving] = useState(false);
    const [requestsData, setRequestsData] = useState({});
    const [roomNumber, setRoomNumber] = useState("");
    const [currentLeaseEnd, setCurrentLeaseEnd] = useState("");
    const [minRequestedDate, setMinRequestedDate] = useState("");
    const [dailyRent, setDailyRent] = useState(0);

    // Form state
    const [form, setForm] = useState({
        current_lease_end: "",
        requested_lease_end: "",
        reason: "",
    });

    // ============================================================
    // LIVE DATA
    // ============================================================
    const readFromDom = () => {
        const el = document.getElementById("services-stay-extension-root");
        if (!el) return;

        setRoomNumber(el.dataset.roomNumber || "");
        setCurrentLeaseEnd(el.dataset.currentLeaseEnd || "");
        setMinRequestedDate(el.dataset.minRequestedDate || "");
        setDailyRent(Number(el.dataset.dailyRent) || 0);

        if (el.dataset.requests) {
            try {
                const parsed = JSON.parse(el.dataset.requests);
                setRequestsData(toKeyedMap(parsed));
            } catch (e) {
                console.error("Invalid requests JSON", e);
            }
        }
    };

    useEffect(() => {
        readFromDom();

        const handler = (e) => {
            const fresh = e?.detail;
            if (fresh) {
                setRequestsData(toKeyedMap(fresh));
            } else {
                readFromDom();
            }
        };

        window.addEventListener("stay-extension:data-updated", handler);
        return () =>
            window.removeEventListener("stay-extension:data-updated", handler);
    }, []);

    // Sync open request with fresh data
    useEffect(() => {
        if (!request) return;
        const fresh = requestsData[String(request.id)];
        if (!fresh) return;

        if (
            fresh.status !== request.status ||
            fresh.reason !== request.reason ||
            fresh.current_lease_end !== request.current_lease_end ||
            fresh.requested_lease_end !== request.requested_lease_end
        ) {
            setRequest(fresh);
        }
    }, [requestsData]); // eslint-disable-line react-hooks/exhaustive-deps

    // ============================================================
    // Event delegation
    // ============================================================
    useEffect(() => {
        const handler = (e) => {
            if (e.target.closest("#open-new-stay-extension")) {
                setForm({
                    current_lease_end: currentLeaseEnd,
                    requested_lease_end: "",
                    reason: "",
                });
                setShowSubmit(true);
                return;
            }

            const viewBtn = e.target.closest(
                ".view-stay-extension-details-btn",
            );
            if (viewBtn) {
                const id = String(viewBtn.dataset.requestId);
                const data = requestsData[id];
                if (data) {
                    setRequest(data);
                    setShowDetails(true);
                }
                return;
            }

            const cancelBtn = e.target.closest(".cancel-stay-extension-btn");
            if (cancelBtn) {
                const id = String(cancelBtn.dataset.requestId);
                const data = requestsData[id];
                if (data) {
                    setDeleteTarget(data);
                    setShowDelete(true);
                }
                return;
            }
        };

        document.addEventListener("click", handler);
        return () => document.removeEventListener("click", handler);
    }, [requestsData, currentLeaseEnd]);

    const updateField = (key, value) =>
        setForm((f) => ({ ...f, [key]: value }));

    // ============================================================
    // Submit
    // ============================================================
    const handleSubmit = async (e) => {
        e.preventDefault();

        if (!form.requested_lease_end.trim() || !form.reason.trim()) {
            alert("Please fill in all required fields.");
            return;
        }

        if (minRequestedDate && form.requested_lease_end < minRequestedDate) {
            alert(
                "The requested lease end must be after your current check-out date.",
            );
            return;
        }

        setSaving(true);
        try {
            const res = await fetch("/tenants/services/stay-extension", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": csrf(),
                },
                body: JSON.stringify({
                    current_lease_end: form.current_lease_end,
                    requested_lease_end: form.requested_lease_end,
                    reason: form.reason,
                }),
            });
            const data = await res.json();
            if (!res.ok)
                throw new Error(data.message || "Failed to submit request.");
            setShowSubmit(false);
            window.dispatchEvent(new CustomEvent("stay-extension:refresh"));
        } catch (err) {
            alert(err.message);
        } finally {
            setSaving(false);
        }
    };

    // ============================================================
    // Delete / Cancel
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
            const res = await fetch(
                `/tenants/services/stay-extension/${deleteTarget.id}/cancel`,
                {
                    method: "POST",
                    headers: {
                        Accept: "application/json",
                        "X-CSRF-TOKEN": csrf(),
                    },
                },
            );
            const data = await res.json().catch(() => ({}));
            if (!res.ok)
                throw new Error(data.message || "Failed to cancel request.");

            setShowDelete(false);
            setDeleteTarget(null);
            setShowDetails(false);
            window.dispatchEvent(new CustomEvent("stay-extension:refresh"));
        } catch (err) {
            alert(err.message);
        } finally {
            setSaving(false);
        }
    };

    // ── Billing Estimate Logic ──
    const computeEstimate = () => {
        if (!dailyRent || !form.requested_lease_end) return null;

        const today = new Date();
        today.setHours(0, 0, 0, 0);

        const requestedDate = new Date(form.requested_lease_end);
        requestedDate.setHours(0, 0, 0, 0);

        const msPerDay = 1000 * 60 * 60 * 24;
        // Math from TODAY until the newly requested date
        const days = Math.max(0, Math.round((requestedDate - today) / msPerDay));

        if (days <= 0) return null;

        return {
            days,
            rate: dailyRent,
            total: days * dailyRent,
            requestedDateStr: form.requested_lease_end,
        };
    };

    const estimate = computeEstimate();

    return (
        <>
            {/* ============================================================ */}
            {/* MODAL 1: Submit Stay Extension Request */}
            {/* ============================================================ */}
            {showSubmit && (
                <Modal
                    onClose={() => setShowSubmit(false)}
                    maxWidth="max-w-[480px]"
                >
                    <div className="flex flex-col max-h-[90vh]">
                        <div className="p-4 sm:p-5 pb-4 flex items-center justify-between border-b border-slate-100 shrink-0">
                            <h3 className="text-base sm:text-lg font-bold text-slate-900 pr-4">
                                Submit Stay Extension Request
                            </h3>
                            <button
                                onClick={() => setShowSubmit(false)}
                                className="text-slate-400 hover:text-slate-700 text-lg shrink-0"
                            >
                                <i className="fa-solid fa-xmark"></i>
                            </button>
                        </div>

                        <form
                            onSubmit={handleSubmit}
                            className="flex flex-col flex-1 min-h-0"
                        >
                            <div className="p-4 sm:p-6 overflow-y-auto flex flex-col gap-4 flex-1">
                                <div className="bg-emerald-50 rounded-lg p-3 flex gap-2.5 text-[13px] text-emerald-600 leading-snug">
                                    <i className="fa-regular fa-circle-info mt-0.5"></i>
                                    <span>
                                        Request to extend your lease by
                                        providing the new end date and reason.
                                    </span>
                                </div>

                                <div>
                                    <label className="block text-[12.5px] font-semibold text-slate-700 mb-1.5">
                                        Room Number
                                    </label>
                                    <input
                                        type="text"
                                        value={roomNumber}
                                        readOnly
                                        className="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2.5 text-[13px] text-slate-600 cursor-not-allowed"
                                    />
                                </div>

                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label className="block text-[12.5px] font-semibold text-slate-700 mb-1.5">
                                            Current Lease End
                                        </label>
                                        <input
                                            type="text"
                                            value={currentLeaseEnd}
                                            readOnly
                                            className="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2.5 text-[13px] text-slate-600 cursor-not-allowed"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-[12.5px] font-semibold text-slate-700 mb-1.5">
                                            Requested Lease End{" "}
                                            <span className="text-rose-500">
                                                *
                                            </span>
                                        </label>
                                        <input
                                            type="date"
                                            value={form.requested_lease_end}
                                            min={minRequestedDate}
                                            onChange={(e) =>
                                                updateField(
                                                    "requested_lease_end",
                                                    e.target.value,
                                                )
                                            }
                                            className="w-full bg-white border border-slate-200 rounded-lg px-3 py-2.5 text-[13px] outline-none focus:border-slate-400"
                                        />
                                        {minRequestedDate && (
                                            <p className="text-[11px] text-slate-400 mt-1">
                                                Must be after{" "}
                                                {currentLeaseEnd ||
                                                    "your current check-out"}
                                                .
                                            </p>
                                        )}
                                    </div>
                                </div>

                                {/* ── BILLING ESTIMATE (below Date, above Reason) ── */}
                                {estimate && (
                                    <div className="bg-amber-50 border border-amber-200 rounded-xl p-4">
                                        <div className="flex items-center gap-2 text-[13px] font-bold text-amber-800 mb-2.5">
                                            <i className="fa-solid fa-receipt"></i>
                                            Estimated Extension Bill
                                        </div>

                                        <div className="flex flex-col gap-1.5 text-[12.5px] text-slate-700">
                                            <div className="flex justify-between">
                                                <span className="text-slate-500">Daily rent</span>
                                                <span className="font-semibold">{formatMoney(estimate.rate)} / day</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span className="text-slate-500">
                                                    Days (today → {new Date(estimate.requestedDateStr).toLocaleDateString('en-PH', {
                                                        month: 'short', day: 'numeric', year: 'numeric',
                                                    })})
                                                </span>
                                                <span className="font-semibold">
                                                    {estimate.days} day{estimate.days === 1 ? '' : 's'}
                                                </span>
                                            </div>
                                            <div className="flex justify-between border-t border-amber-200 pt-1.5 mt-1">
                                                <span className="font-bold text-slate-900">Total estimate</span>
                                                <span className="font-bold text-amber-800">{formatMoney(estimate.total)}</span>
                                            </div>
                                        </div>

                                        <p className="text-[11px] text-amber-700 mt-2 leading-snug">
                                            <i className="fa-solid fa-circle-info mr-1"></i>
                                            This is an estimate based on today's date until your requested end date. Final billing is confirmed by the admin upon approval.
                                        </p>
                                    </div>
                                )}

                                <div>
                                    <label className="block text-[12.5px] font-semibold text-slate-700 mb-1.5">
                                        Reason{" "}
                                        <span className="text-rose-500">*</span>
                                    </label>
                                    <textarea
                                        value={form.reason}
                                        onChange={(e) =>
                                            updateField(
                                                "reason",
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Explain why you need to extend your stay..."
                                        className="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2.5 text-[13px] outline-none resize-none h-[90px] focus:border-slate-400"
                                    ></textarea>
                                </div>
                            </div>

                            <div className="p-4 sm:p-5 border-t border-slate-100 flex justify-end items-center gap-3 shrink-0 flex-wrap">
                                <button
                                    type="button"
                                    onClick={() => setShowSubmit(false)}
                                    className="text-slate-600 text-[13px] font-medium px-4 py-2"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    disabled={saving}
                                    className="bg-[#001838] hover:bg-[#0d2347] disabled:opacity-50 text-white px-4 py-2.5 rounded-lg text-[13px] font-medium"
                                >
                                    {saving ? "Submitting…" : "Submit Request"}
                                </button>
                            </div>
                        </form>
                    </div>
                </Modal>
            )}

            {/* ============================================================ */}
            {/* MODAL 2: Details */}
            {/* ============================================================ */}
            {showDetails && request && (
                <Modal
                    onClose={() => setShowDetails(false)}
                    maxWidth="max-w-[480px]"
                >
                    <div className="flex flex-col max-h-[90vh]">
                        <div className="p-4 sm:p-5 pb-4 flex items-center justify-between border-b border-slate-100 shrink-0">
                            <div className="flex items-center gap-3 min-w-0 flex-wrap">
                                <h3 className="text-base sm:text-lg font-bold text-slate-900 truncate">
                                    Stay Extension Request
                                </h3>
                                <span
                                    className={`text-[11px] font-semibold px-2.5 py-0.5 rounded-full inline-flex items-center gap-1 shrink-0 ${
                                        request.status === "Pending"
                                            ? "bg-amber-100 text-amber-700"
                                            : request.status === "Approved"
                                              ? "bg-emerald-100 text-emerald-600"
                                              : "bg-rose-100 text-rose-600"
                                    }`}
                                >
                                    <i className="fa-regular fa-clock"></i>{" "}
                                    {request.status}
                                </span>
                            </div>
                            <button
                                onClick={() => setShowDetails(false)}
                                className="text-slate-400 hover:text-slate-700 text-lg shrink-0"
                            >
                                <i className="fa-solid fa-xmark"></i>
                            </button>
                        </div>

                        <div className="p-4 sm:p-6 overflow-y-auto flex flex-col gap-4 flex-1">
                            <div className="text-xs text-slate-400 -mt-1 break-words">
                                {request.ref}
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div className="bg-slate-50 rounded-lg p-3">
                                    <div className="text-[11px] text-slate-400 mb-1">
                                        Tenant
                                    </div>
                                    <div className="text-[13px] font-semibold text-slate-900 break-words">
                                        {request.tenant}
                                    </div>
                                </div>
                                <div className="bg-slate-50 rounded-lg p-3">
                                    <div className="text-[11px] text-slate-400 mb-1">
                                        Room / Bed ID
                                    </div>
                                    <div className="text-[13px] font-semibold text-slate-900 break-words">
                                        {request.room}
                                    </div>
                                </div>
                                <div className="bg-slate-50 rounded-lg p-3">
                                    <div className="text-[11px] text-slate-400 mb-1">
                                        Current Lease End
                                    </div>
                                    <div className="text-[13px] font-semibold text-slate-900 break-words">
                                        {request.current_lease_end}
                                    </div>
                                </div>
                                <div className="bg-slate-50 rounded-lg p-3">
                                    <div className="text-[11px] text-slate-400 mb-1">
                                        Requested Lease End
                                    </div>
                                    <div className="text-[13px] font-semibold text-slate-900 break-words">
                                        {request.requested_lease_end}
                                    </div>
                                </div>
                            </div>

                            <div className="bg-slate-50 rounded-lg p-3 sm:w-1/2">
                                <div className="text-[11px] text-slate-400 mb-1">
                                    Submitted
                                </div>
                                <div className="text-[13px] font-semibold text-slate-900">
                                    {request.submitted}
                                </div>
                            </div>

                            <div className="bg-slate-50 rounded-lg p-3">
                                <div className="text-[11px] text-slate-400 mb-1">
                                    Reason:
                                </div>
                                <div className="text-[13px] text-slate-700 break-words whitespace-pre-wrap">
                                    {request.reason}
                                </div>
                            </div>
                        </div>

                        <div className="p-4 sm:p-5 border-t border-slate-100 flex justify-end items-center gap-2 shrink-0 flex-wrap">
                            {request.status === "Pending" && (
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
                                className="bg-[#001838] hover:bg-[#0d2347] text-white px-4 py-2 rounded-lg text-[12.5px] font-medium"
                            >
                                Close
                            </button>
                        </div>
                    </div>
                </Modal>
            )}

            {/* ============================================================ */}
            {/* MODAL 3: Delete / Cancel Confirmation */}
            {/* ============================================================ */}
            {showDelete && deleteTarget && (
                <Modal onClose={closeDeleteModal} maxWidth="max-w-[460px]">
                    <div className="flex flex-col max-h-[90vh]">
                        <div className="p-4 sm:p-5 pb-4 flex items-center justify-between border-b border-slate-100 shrink-0">
                            <h3 className="text-base sm:text-lg font-bold text-slate-900 pr-4">
                                Cancel Stay Extension
                            </h3>
                            <button
                                onClick={closeDeleteModal}
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
                                You are about to cancel this stay extension
                                request:
                            </p>
                            <p className="text-[13px] text-slate-600 mb-4 break-words">
                                <span className="font-semibold">
                                    {deleteTarget.current_lease_end}
                                </span>
                                <i className="fa-solid fa-arrow-right text-slate-400 text-[10px] mx-2"></i>
                                <span className="font-semibold text-purple-700">
                                    {deleteTarget.requested_lease_end}
                                </span>
                                <span className="text-slate-400 ml-2">
                                    ({deleteTarget.ref})
                                </span>
                            </p>
                            <div className="bg-amber-50 border border-amber-200 rounded-xl p-3 text-left">
                                <p className="text-[12px] text-amber-800 leading-snug">
                                    <i className="fa-solid fa-circle-info mr-1"></i>
                                    This will permanently remove the request.
                                    You will need to submit a new one if you
                                    change your mind.{" "}
                                    <strong>
                                        This action cannot be undone.
                                    </strong>
                                </p>
                            </div>
                        </div>

                        <div className="p-4 sm:p-5 border-t border-slate-100 flex justify-end gap-3 shrink-0 flex-wrap">
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
                    </div>
                </Modal>
            )}
        </>
    );
}