// resources/js/pages/Admin/BillingModals.jsx
import React, { useState, useEffect } from "react";
import Modal from "../../components/Modal";

const csrf = () =>
    document.querySelector('meta[name="csrf-token"]')?.content ||
    document.querySelector('input[name="_token"]')?.value;

const FALLBACK = {
    tn: "tn-000",
    description: "No description",
    tenant: "Unknown",
    room: "N/A",
    total: "₱0",
    rate: "₱ 0/day",
    paid: "₱0",
    days: "0 days",
    balance: "₱0",
    due: "-",
    status: "Unpaid",
    check_in: "-",
    check_out: "-",
    payment_method: "Not Set",
    receipt_url: null,
    is_remaining: false,
    date_paid: null,
    receipt_no: "-",
};

// Parse "₱1,234" or "₱ 1,234.56" → 1234 (or 1234.56)
const parseMoney = (str) => {
    if (str === null || str === undefined) return 0;
    const n = parseFloat(String(str).replace(/[^\d.]/g, ""));
    return Number.isFinite(n) ? n : 0;
};

// Decide whether verifying this payment leaves the invoice Partial.
const willBePartial = (invoice) => {
    if (!invoice) return false;
    const total = parseMoney(invoice.total);
    const paid = parseMoney(invoice.paid);
    if (total <= 0) return false;
    return paid + 0.005 < total;
};

const projectedRemaining = (invoice) => {
    if (!invoice) return 0;
    const total = parseMoney(invoice.total);
    const paid = parseMoney(invoice.paid);
    return Math.max(0, total - paid);
};

export default function BillingModals() {
    const [showEdit, setShowEdit] = useState(false);
    const [showSummary, setShowSummary] = useState(false);
    const [showReceipt, setShowReceipt] = useState(false);
    const [showVerify, setShowVerify] = useState(false);
    const [showReject, setShowReject] = useState(false);
    const [rejectNotes, setRejectNotes] = useState("");
    const [invoice, setInvoice] = useState(null);
    const [saving, setSaving] = useState(false);
    const [invoicesData, setInvoicesData] = useState({});

    useEffect(() => {
        const el = document.getElementById("admin-billing-modals");
        if (el?.dataset?.invoices) {
            try {
                setInvoicesData(JSON.parse(el.dataset.invoices));
            } catch (e) {
                console.error("Invalid invoices JSON", e);
            }
        }
    }, []);

    useEffect(() => {
        const handleClick = (e) => {
            const btn = e.target.closest(
                ".icon-edit, .icon-view, .icon-payment, .icon-receipt, .icon-remind, .icon-verify, .icon-reject",
            );
            if (!btn) return;

            const row = btn.closest("tr");
            const tn = row?.dataset?.tn;
            const data = invoicesData[tn] || FALLBACK;
            setInvoice(data);

            if (btn.classList.contains("icon-edit")) return setShowEdit(true);
            if (btn.classList.contains("icon-view")) return setShowSummary(true);
            if (btn.classList.contains("icon-payment") || btn.classList.contains("icon-receipt")) return setShowReceipt(true);
            if (btn.classList.contains("icon-remind")) return handleSendReminder(data);
            if (btn.classList.contains("icon-verify")) return setShowVerify(true);
            if (btn.classList.contains("icon-reject")) {
                setRejectNotes("");
                return setShowReject(true);
            }
        };
        document.addEventListener("click", handleClick);
        return () => document.removeEventListener("click", handleClick);
    }, [invoicesData]);

    const closeAll = () => {
        setShowEdit(false);
        setShowSummary(false);
        setShowReceipt(false);
        setShowVerify(false);
        setShowReject(false);
        setRejectNotes("");
        setInvoice(null);
    };

    const handleSendReminder = async (data) => {
        if (!confirm(`Send payment reminder to ${data.tenant}?`)) return;
        try {
            const res = await fetch(`/admin/billing/${data.tn}/remind`, {
                method: "POST",
                headers: { Accept: "application/json", "X-CSRF-TOKEN": csrf() },
            });
            const result = await res.json();
            if (!res.ok) throw new Error(result.message || "Failed to send reminder.");
            alert(result.message || "Reminder sent.");
        } catch (err) {
            alert(err.message);
        }
    };

    const handleSaveEdit = async (formData) => {
        if (!invoice) return;
        setSaving(true);
        try {
            const res = await fetch(`/admin/billing/${invoice.tn}`, {
                method: "PUT",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": csrf(),
                },
                body: JSON.stringify(formData),
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || "Failed to update.");
            closeAll();
            window.location.reload();
        } catch (err) {
            alert(err.message);
        } finally {
            setSaving(false);
        }
    };

    const confirmVerify = async () => {
        if (!invoice) return;
        setSaving(true);
        try {
            const res = await fetch(`/admin/billing/${invoice.tn}/verify-payment`, {
                method: "POST",
                headers: {
                    Accept: "application/json",
                    "X-CSRF-TOKEN": csrf(),
                },
            });
            const result = await res.json();
            
            if (!res.ok) {
                // If the backend blocks it because it's no longer pending,
                // tell the user and reload so they see the true state.
                if (res.status === 422 && result.message.includes("not pending")) {
                    alert(result.message + "\n\nThe page will now refresh to show the latest status.");
                    window.location.reload();
                    return;
                }
                throw new Error(result.message || "Verification failed.");
            }

            closeAll();

            const row = document.querySelector(`tr[data-tn="${invoice.tn}"]`);
            if (!row) {
                window.location.reload();
                return;
            }

            if (result.status === "Paid") {
                row.style.display = "none";
            } else if (result.status === "Partial") {
                const statusBadge = row.querySelector(".px-3\\.5.py-1");
                if (statusBadge) {
                    statusBadge.className =
                        "inline-block px-3.5 py-1 rounded-full text-[11px] font-bold bg-yellow-100 text-yellow-700";
                    statusBadge.innerText = "Partial";
                }

                const actionDiv = row.querySelector(".flex.items-center.gap-3");
                if (actionDiv) {
                    actionDiv.innerHTML = `
                        <i class="fa-solid fa-pen text-slate-700 text-[15px] cursor-pointer hover:text-orange-500 transition icon-edit" title="Edit Invoice"></i>
                        <i class="fa-solid fa-bell text-slate-700 text-[15px] cursor-pointer hover:text-orange-500 transition icon-remind" title="Send Reminder"></i>
                        <i class="fa-solid fa-eye text-slate-700 text-[15px] cursor-pointer hover:text-orange-500 transition icon-view" title="View Summary"></i>
                    `;
                }
            }
        } catch (err) {
            alert(err.message);
        } finally {
            setSaving(false);
        }
    };

    const confirmReject = async () => {
        if (!invoice) return;
        if (!rejectNotes.trim()) {
            alert("Please provide a reason for rejection.");
            return;
        }
        setSaving(true);
        try {
            const res = await fetch(`/admin/billing/${invoice.tn}/reject-payment`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": csrf(),
                },
                body: JSON.stringify({ admin_notes: rejectNotes }),
            });
            const result = await res.json();
            
            if (!res.ok) {
                if (res.status === 422 && result.message.includes("not pending")) {
                    alert(result.message + "\n\nThe page will now refresh to show the latest status.");
                    window.location.reload();
                    return;
                }
                throw new Error(result.message || "Rejection failed.");
            }

            closeAll();
            window.location.reload();
        } catch (err) {
            alert(err.message);
        } finally {
            setSaving(false);
        }
    };

    const data = invoice || FALLBACK;
    const displayDatePaid = data.date_paid && data.date_paid !== "-" ? data.date_paid : "-";

    const statusBadgeClass = {
        Unpaid: "bg-amber-100 text-amber-700",
        Overdue: "bg-rose-100 text-rose-600",
        Partial: "bg-yellow-100 text-yellow-700",
        Pending: "bg-blue-100 text-blue-600",
        Paid: "bg-green-100 text-green-600",
    }[data.status] || "bg-slate-100 text-slate-500";

    const verifyWillBePartial = willBePartial(invoice);
    const verifyRemaining = projectedRemaining(invoice);
    const formattedRemaining =
        "₱" +
        verifyRemaining.toLocaleString(undefined, {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2,
        });

    return (
        <>
            {showEdit && (
                <EditInvoiceModal
                    data={data}
                    onClose={closeAll}
                    onSave={handleSaveEdit}
                    saving={saving}
                />
            )}

            {showSummary && (
                <Modal onClose={closeAll} maxWidth="max-w-[520px]">
                    <div className="p-5 pb-4 flex items-center justify-between border-b border-slate-100">
                        <h3 className="text-lg font-bold text-slate-900">
                            Invoice Summary
                        </h3>
                        <button
                            onClick={closeAll}
                            className="text-slate-400 hover:text-slate-700 text-lg"
                        >
                            <i className="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div className="p-6 overflow-y-auto flex flex-col gap-4">
                        <div className="flex items-center justify-between">
                            <span className="text-xs text-slate-400">
                                {data.tn}
                            </span>
                            <span className={`px-3 py-1 rounded-full text-[11px] font-bold ${statusBadgeClass}`}>
                                {data.status}
                            </span>
                        </div>

                        <div className="bg-orange-50 rounded-xl p-5 text-center">
                            <div className="text-[11px] font-bold text-orange-600">
                                Total Amount
                            </div>
                            <div className="text-[28px] font-extrabold text-orange-600 mt-1">
                                {data.total}
                            </div>
                            <div className="flex justify-center gap-5 text-[11px] text-slate-400 mt-1.5">
                                <span>{data.days}</span>
                                <span>{data.rate.replace("₱ ", "")} per day</span>
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            {[
                                ["Tenant", data.tenant],
                                ["Room Number", data.room],
                                ["Check In", data.check_in],
                                ["Check Out", data.check_out],
                                ["Amount Paid", data.paid],
                                ["Balance", data.balance],
                            ].map(([label, val], i) => (
                                <div key={i} className="bg-slate-50 rounded-xl p-3">
                                    <div className="text-[11px] text-slate-400">{label}</div>
                                    <div className="text-[13px] font-bold text-slate-900 mt-0.5">{val}</div>
                                </div>
                            ))}
                            <div className="bg-slate-50 rounded-xl p-3 col-span-2">
                                <div className="text-[11px] text-slate-400">Due Date</div>
                                <div className="text-[13px] font-bold text-slate-900 mt-0.5">{data.due}</div>
                            </div>
                        </div>

                        {data.status === "Pending" && data.receipt_url && (
                            <div className="bg-slate-50 border border-slate-200 rounded-xl p-4">
                                <div className="text-xs font-semibold text-slate-700 mb-2">Proof of Payment</div>
                                <a
                                    href={data.receipt_url}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="flex items-center gap-2 border border-slate-200 bg-white rounded-lg p-3 text-[13px] text-slate-700 hover:bg-slate-100 transition"
                                >
                                    <i className="fa-solid fa-file text-blue-500"></i>
                                    View Uploaded Proof
                                </a>
                            </div>
                        )}
                    </div>

                    <div className="p-5 border-t border-slate-100 flex justify-between">
                        <button
                            onClick={closeAll}
                            className="bg-[#02122b] text-white px-5 py-2.5 rounded-lg text-[13px] font-semibold hover:bg-slate-800 transition"
                        >
                            Close
                        </button>
                        {data.status === "Pending" && (
                            <div className="flex gap-2">
                                <button
                                    onClick={() => {
                                        setShowSummary(false);
                                        setShowVerify(true);
                                    }}
                                    className="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2.5 rounded-lg text-[13px] font-semibold transition"
                                >
                                    Approve
                                </button>
                                <button
                                    onClick={() => {
                                        setShowSummary(false);
                                        setRejectNotes("");
                                        setShowReject(true);
                                    }}
                                    className="bg-rose-500 hover:bg-rose-600 text-white px-4 py-2.5 rounded-lg text-[13px] font-semibold transition"
                                >
                                    Reject
                                </button>
                            </div>
                        )}
                    </div>
                </Modal>
            )}

            {showReceipt && (
                <Modal onClose={closeAll} maxWidth="max-w-[520px]">
                    <div className="p-5 pb-4 flex items-center justify-between border-b border-slate-100">
                        <h3 className="text-lg font-bold text-slate-900">
                            Payment Receipt
                        </h3>
                        <button
                            onClick={closeAll}
                            className="text-slate-400 hover:text-slate-700 text-lg"
                        >
                            <i className="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div className="p-6 overflow-y-auto">
                        <div className="border-[1.5px] border-dashed border-orange-400 bg-orange-50/30 rounded-2xl p-5 flex flex-col items-center gap-3">
                            <div className="w-11 h-11 rounded-full bg-orange-500 text-white flex items-center justify-center text-xl">
                                <i className="fa-solid fa-check"></i>
                            </div>
                            <div className="text-center">
                                <h4 className="text-base font-extrabold text-slate-900">
                                    Official Receipt
                                </h4>
                                <p className="text-[11px] text-orange-600 font-semibold">
                                    JLM Cozy Cabin
                                </p>
                            </div>
                            <div className="text-sm font-semibold text-slate-600">
                                Transaction No. {data.tn.toUpperCase()}
                            </div>
                            <div className="text-[11px] text-slate-400">
                                Date of Issue: {displayDatePaid}
                            </div>

                            <div className="w-full border-t border-slate-100 pt-3 flex flex-col gap-2">
                                {[
                                    ["Receipt No.", data.tn.toUpperCase()],
                                    ["Tenant", data.tenant],
                                    ["Room", `Room ${data.room}`],
                                    ["Description", data.description],
                                    [
                                        "Rate Per Day",
                                        data.rate.replace("₱ ", "₱"),
                                    ],
                                ].map((row, i) => (
                                    <div
                                        key={i}
                                        className="flex justify-between text-xs"
                                    >
                                        <span className="text-slate-500 font-medium">
                                            {row[0]}
                                        </span>
                                        <span className="text-slate-900 font-bold">
                                            {row[1]}
                                        </span>
                                    </div>
                                ))}
                                <div className="flex justify-between text-sm mt-2 pt-2 border-t border-slate-100">
                                    <span className="text-slate-900 font-bold">
                                        Total Amount
                                    </span>
                                    <span className="text-slate-900 font-extrabold">
                                        {data.total}
                                    </span>
                                </div>
                            </div>

                            <div className="w-full border-t border-dashed border-slate-200 pt-3 flex flex-col gap-2">
                                <div className="text-xs font-bold text-slate-900 mb-1">
                                    Payment Details:
                                </div>
                                <div className="flex justify-between text-xs">
                                    <span className="text-slate-500">
                                        Payment #
                                    </span>
                                    <span className="text-slate-900 font-bold">
                                        {data.receipt_no || "-"}
                                    </span>
                                </div>
                                <div className="flex justify-between text-xs">
                                    <span className="text-slate-500">
                                        Payment Method
                                    </span>
                                    <span className="text-slate-900 font-bold">
                                        {data.payment_method}
                                    </span>
                                </div>
                                <div className="flex justify-between text-xs">
                                    <span className="text-slate-500">
                                        Amount Paid
                                    </span>
                                    <span className="text-slate-900 font-bold">
                                        {data.paid}
                                    </span>
                                </div>
                                <div className="flex justify-between text-xs">
                                    <span className="text-slate-500">
                                        Date Paid
                                    </span>
                                    <span className="text-slate-900 font-bold">
                                        {displayDatePaid}
                                    </span>
                                </div>
                            </div>

                            {data.receipt_url && (
                                <a
                                    href={data.receipt_url}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="w-full border border-orange-300 bg-orange-100 text-orange-700 rounded-lg py-2 text-[12px] font-bold inline-flex items-center justify-center gap-2 hover:bg-orange-200 transition"
                                >
                                    <i className="fa-regular fa-image"></i> View
                                    Uploaded Proof
                                </a>
                            )}

                            <div className="text-center mt-2">
                                <p className="text-[11px] text-slate-500">
                                    Authorized By: <strong>Leah Agujar</strong>
                                </p>
                                <p className="text-xs font-bold text-orange-500 mt-1">
                                    Thank You! :)
                                </p>
                            </div>
                        </div>
                    </div>
                </Modal>
            )}

            {showVerify && invoice && (
                <Modal
                    onClose={() => !saving && setShowVerify(false)}
                    maxWidth="max-w-[460px]"
                >
                    <div className="p-5 pb-4 flex items-center justify-between border-b border-slate-100">
                        <h3 className="text-lg font-bold text-slate-900">
                            Verify Payment
                        </h3>
                        <button
                            onClick={() => !saving && setShowVerify(false)}
                            disabled={saving}
                            className="text-slate-400 hover:text-slate-700 text-lg disabled:opacity-50"
                        >
                            <i className="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div className="p-6 text-center">
                        <div
                            className={`w-16 h-16 mx-auto rounded-full flex items-center justify-center mb-4 ${
                                verifyWillBePartial
                                    ? "bg-amber-50"
                                    : "bg-emerald-50"
                            }`}
                        >
                            <i
                                className={`fa-solid text-2xl ${
                                    verifyWillBePartial
                                        ? "fa-circle-exclamation text-amber-500"
                                        : "fa-circle-check text-emerald-500"
                                }`}
                            ></i>
                        </div>
                        <h4 className="text-lg font-bold text-slate-900 mb-2">
                            Verify this payment?
                        </h4>
                        <p className="text-slate-500 text-[13px] mb-4">
                            {verifyWillBePartial ? (
                                <>
                                    This will mark the invoice as{" "}
                                    <strong className="text-amber-700">
                                        Partial
                                    </strong>{" "}
                                    and record the payment. The tenant will
                                    still have an outstanding balance of{" "}
                                    <strong>{formattedRemaining}</strong>.
                                </>
                            ) : (
                                <>
                                    This will mark the invoice as{" "}
                                    <strong>Paid</strong> and record the
                                    payment.
                                </>
                            )}
                        </p>
                        <div className="bg-slate-50 rounded-xl p-4 text-left flex flex-col gap-2.5 mb-4">
                            <div className="flex justify-between text-xs">
                                <span className="text-slate-500">
                                    Transaction
                                </span>
                                <span className="font-bold text-slate-900">
                                    {invoice.tn}
                                </span>
                            </div>
                            <div className="flex justify-between text-xs">
                                <span className="text-slate-500">Tenant</span>
                                <span className="font-bold text-slate-900">
                                    {invoice.tenant}
                                </span>
                            </div>
                            <div className="flex justify-between text-xs">
                                <span className="text-slate-500">Total</span>
                                <span className="font-bold text-slate-900">
                                    {invoice.total}
                                </span>
                            </div>
                            <div className="flex justify-between text-xs">
                                <span className="text-slate-500">
                                    Amount Paid
                                </span>
                                <span className="font-bold text-emerald-600">
                                    {invoice.paid}
                                </span>
                            </div>
                            {verifyWillBePartial && (
                                <div className="flex justify-between text-xs border-t border-slate-200 pt-2 mt-1">
                                    <span className="text-slate-500">
                                        Remaining After Verification
                                    </span>
                                    <span className="font-bold text-amber-600">
                                        {formattedRemaining}
                                    </span>
                                </div>
                            )}
                            <div className="flex justify-between text-xs">
                                <span className="text-slate-500">Method</span>
                                <span className="font-bold text-slate-900 uppercase">
                                    {invoice.payment_method || "-"}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div className="p-5 border-t border-slate-100 flex justify-end gap-3">
                        <button
                            onClick={() => setShowVerify(false)}
                            disabled={saving}
                            className="text-slate-600 text-[13.5px] font-semibold px-4 py-2 disabled:opacity-50"
                        >
                            Cancel
                        </button>
                        <button
                            onClick={confirmVerify}
                            disabled={saving}
                            className={`disabled:opacity-50 text-white px-5 py-2.5 rounded-lg text-[13.5px] font-semibold inline-flex items-center gap-2 transition ${
                                verifyWillBePartial
                                    ? "bg-amber-500 hover:bg-amber-600"
                                    : "bg-emerald-600 hover:bg-emerald-700"
                            }`}
                        >
                            {saving ? (
                                <>
                                    <i className="fa-solid fa-spinner fa-spin"></i>{" "}
                                    Verifying…
                                </>
                            ) : verifyWillBePartial ? (
                                <>
                                    <i className="fa-solid fa-circle-exclamation"></i>{" "}
                                    Yes, Verify as Partial
                                </>
                            ) : (
                                <>
                                    <i className="fa-solid fa-circle-check"></i>{" "}
                                    Yes, Verify
                                </>
                            )}
                        </button>
                    </div>
                </Modal>
            )}

            {showReject && invoice && (
                <Modal
                    onClose={() => !saving && setShowReject(false)}
                    maxWidth="max-w-[460px]"
                >
                    <div className="p-5 pb-4 flex items-center justify-between border-b border-slate-100 shrink-0">
                        <h3 className="text-lg font-bold text-slate-900">
                            Reject Payment
                        </h3>
                        <button
                            onClick={() => !saving && setShowReject(false)}
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
                            <h4 className="text-lg font-bold text-slate-900 mb-2">
                                Reject this payment?
                            </h4>
                            <p className="text-slate-500 text-[13px]">
                                This will revert the invoice to its previous
                                unpaid state and delete the uploaded proof.
                            </p>
                        </div>
                        <div className="bg-slate-50 rounded-xl p-4 text-left flex flex-col gap-2.5 mb-4">
                            <div className="flex justify-between text-xs">
                                <span className="text-slate-500">
                                    Transaction
                                </span>
                                <span className="font-bold text-slate-900">
                                    {invoice.tn}
                                </span>
                            </div>
                            <div className="flex justify-between text-xs">
                                <span className="text-slate-500">Tenant</span>
                                <span className="font-bold text-slate-900">
                                    {invoice.tenant}
                                </span>
                            </div>
                            <div className="flex justify-between text-xs">
                                <span className="text-slate-500">Amount</span>
                                <span className="font-bold text-slate-900">
                                    {invoice.paid}
                                </span>
                            </div>
                        </div>
                        <label className="block text-[13px] font-semibold text-slate-700 mb-1.5">
                            Reason for rejection{" "}
                            <span className="text-rose-500">*</span>
                        </label>
                        <textarea
                            value={rejectNotes}
                            onChange={(e) => setRejectNotes(e.target.value)}
                            placeholder="Explain why this payment proof is being rejected..."
                            className="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 text-[13px] outline-none resize-y min-h-[90px] focus:border-slate-400"
                        />
                    </div>
                    <div className="p-5 border-t border-slate-100 flex justify-end gap-3 shrink-0">
                        <button
                            onClick={() => setShowReject(false)}
                            disabled={saving}
                            className="text-slate-600 text-[13.5px] font-semibold px-4 py-2 disabled:opacity-50"
                        >
                            Cancel
                        </button>
                        <button
                            onClick={confirmReject}
                            disabled={saving || !rejectNotes.trim()}
                            className="bg-rose-500 hover:bg-rose-600 disabled:opacity-50 text-white px-5 py-2.5 rounded-lg text-[13.5px] font-semibold inline-flex items-center gap-2 transition"
                        >
                            {saving ? (
                                <>
                                    <i className="fa-solid fa-spinner fa-spin"></i>{" "}
                                    Rejecting…
                                </>
                            ) : (
                                <>
                                    <i className="fa-solid fa-circle-xmark"></i>{" "}
                                    Yes, Reject
                                </>
                            )}
                        </button>
                    </div>
                </Modal>
            )}
        </>
    );
}

function EditInvoiceModal({ data, onClose, onSave, saving }) {
    const isPending = data.status === "Pending";

    const toNumber = (str) => {
        if (str === null || str === undefined) return 0;
        const n = parseFloat(String(str).replace(/[^\d.]/g, ""));
        return Number.isFinite(n) ? n : 0;
    };

    const [form, setForm] = useState({
        tenant_name: data.tenant,
        room_number: data.room,
        description: data.description,
        check_in: data.check_in,
        check_out: data.check_out,
        rate_per_day: toNumber(data.rate),
        days: parseInt(String(data.days).replace(/\D/g, ""), 10) || 1,
        amount_paid: toNumber(data.paid),
        due_date: data.due,
        payment_method: data.payment_method || "Not Set",
        status: data.status,
    });

    const updateField = (key, value) =>
        setForm((f) => ({ ...f, [key]: value }));

    const handleSubmit = () => {
        onSave({
            ...form,
            rate_per_day: parseFloat(form.rate_per_day) || 0,
            days: parseInt(form.days, 10) || 1,
            amount_paid: parseFloat(form.amount_paid) || 0,
            due_date: form.due_date === "-" ? null : form.due_date,
            check_in: form.check_in === "-" ? null : form.check_in,
            check_out: form.check_out === "-" ? null : form.check_out,
        });
    };

    return (
        <Modal onClose={onClose} maxWidth="max-w-[520px]">
            <div className="p-5 pb-4 flex items-center justify-between border-b border-slate-100">
                <h3 className="text-lg font-bold text-slate-900">
                    Edit Invoice
                </h3>
                <button
                    onClick={onClose}
                    className="text-slate-400 hover:text-slate-700 text-lg"
                >
                    <i className="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div className="p-6 overflow-y-auto">
                {isPending && (
                    <div className="bg-amber-50 border border-amber-200 rounded-xl p-3 mb-4 text-[12px] text-amber-800 leading-snug">
                        <i className="fa-solid fa-circle-info mr-1"></i>
                        This invoice is awaiting payment verification.{" "}
                        <strong>Use Verify or Reject</strong> to settle it —
                        editing here is for corrections only, and "Amount Paid"
                        is locked to the submitted amount.
                    </div>
                )}

                <div className="grid grid-cols-2 gap-3.5">
                    {[
                        ["tenant_name", "Tenant Name", "text"],
                        ["room_number", "Room Number", "text"],
                        ["check_in", "Check In", "text"],
                        ["check_out", "Check Out", "text"],
                    ].map(([key, label, type]) => (
                        <div key={key}>
                            <label className="block text-xs font-semibold text-slate-600 mb-1.5">
                                {label}
                            </label>
                            <input
                                type={type}
                                value={form[key] ?? ""}
                                onChange={(e) =>
                                    updateField(key, e.target.value)
                                }
                                className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none"
                            />
                        </div>
                    ))}
                    <div className="col-span-2">
                        <label className="block text-xs font-semibold text-slate-600 mb-1.5">
                            Description
                        </label>
                        <input
                            type="text"
                            value={form.description}
                            onChange={(e) =>
                                updateField("description", e.target.value)
                            }
                            className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none"
                        />
                    </div>
                    <div>
                        <label className="block text-xs font-semibold text-slate-600 mb-1.5">
                            Rate Per Day (₱)
                        </label>
                        <input
                            type="text"
                            value={form.rate_per_day}
                            onChange={(e) =>
                                updateField(
                                    "rate_per_day",
                                    e.target.value.replace(/[^\d.]/g, ""),
                                )
                            }
                            className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none"
                        />
                    </div>
                    <div>
                        <label className="block text-xs font-semibold text-slate-600 mb-1.5">
                            Days
                        </label>
                        <input
                            type="text"
                            value={form.days}
                            onChange={(e) =>
                                updateField(
                                    "days",
                                    e.target.value.replace(/\D/g, ""),
                                )
                            }
                            className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none"
                        />
                    </div>
                    <div>
                        <label className="block text-xs font-semibold text-slate-600 mb-1.5">
                            Amount Paid (₱)
                            {isPending && (
                                <span className="ml-1 text-[10px] text-amber-600 font-normal">
                                    (locked)
                                </span>
                            )}
                        </label>
                        <input
                            type="text"
                            value={form.amount_paid}
                            disabled={isPending}
                            onChange={(e) =>
                                updateField(
                                    "amount_paid",
                                    e.target.value.replace(/[^\d.]/g, ""),
                                )
                            }
                            className={`w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none ${
                                isPending
                                    ? "bg-slate-100 text-slate-500 cursor-not-allowed"
                                    : ""
                            }`}
                        />
                    </div>
                    <div>
                        <label className="block text-xs font-semibold text-slate-600 mb-1.5">
                            Due Date
                        </label>
                        <input
                            type="text"
                            value={form.due_date ?? ""}
                            onChange={(e) =>
                                updateField("due_date", e.target.value)
                            }
                            className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none"
                        />
                    </div>
                    <div>
                        <label className="block text-xs font-semibold text-slate-600 mb-1.5">
                            Payment Method
                        </label>
                        <select
                            value={form.payment_method}
                            onChange={(e) =>
                                updateField("payment_method", e.target.value)
                            }
                            className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none bg-white"
                        >
                            <option>Not Set</option>
                            <option>GCash</option>
                            <option>Over The Counter</option>
                            <option>Bank Transfer</option>
                        </select>
                    </div>
                    <div>
                        <label className="block text-xs font-semibold text-slate-600 mb-1.5">
                            Status
                        </label>
                        <select
                            value={form.status}
                            disabled={isPending}
                            onChange={(e) =>
                                updateField("status", e.target.value)
                            }
                            className={`w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none bg-white ${
                                isPending
                                    ? "bg-slate-100 text-slate-500 cursor-not-allowed"
                                    : ""
                            }`}
                        >
                            <option>Unpaid</option>
                            <option>Overdue</option>
                            <option>Partial</option>
                            <option>Pending</option>
                            <option>Paid</option>
                        </select>
                    </div>
                </div>
            </div>
            <div className="p-5 border-t border-slate-100 flex justify-end gap-3">
                <button
                    onClick={onClose}
                    className="text-slate-600 text-[13px] font-semibold px-4 py-2"
                >
                    Cancel
                </button>
                <button
                    onClick={handleSubmit}
                    disabled={saving}
                    className="bg-[#02122b] text-white px-5 py-2.5 rounded-lg text-[13px] font-semibold disabled:opacity-50"
                >
                    {saving ? "Saving…" : "Save Changes"}
                </button>
            </div>
        </Modal>
    );
}