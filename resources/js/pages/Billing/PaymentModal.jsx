// resources/js/pages/Billing/PaymentModal.jsx
import React, { useState, useEffect } from 'react';
import Modal from '../../components/Modal';

const csrf = () =>
    document.querySelector('meta[name="csrf-token"]')?.content ||
    document.querySelector('input[name="_token"]')?.value;

const METHOD_MAP = {
    gcash:   'GCash',
    bank:    'Bank Transfer',
    counter: 'Over The Counter',
};

export default function PaymentModal() {
    const [open, setOpen]               = useState(false);
    const [saving, setSaving]           = useState(false);
    const [invoice, setInvoice]         = useState(null);
    const [error, setError]             = useState('');

    const [showCancel, setShowCancel]   = useState(false);
    const [cancelTarget, setCancelTarget] = useState(null);
    const [cancelSaving, setCancelSaving] = useState(false);

    const [method, setMethod]                   = useState('gcash');
    const [referenceNumber, setReferenceNumber] = useState('');
    const [receipt, setReceipt]                 = useState(null);

    // 1. Read default payload on mount (optional)
    useEffect(() => {
        const el = document.getElementById('payment-modal-root');
        if (!el?.dataset?.invoice) return;
        try {
            setInvoice(JSON.parse(el.dataset.invoice));
        } catch (e) {
            console.error('Invalid default invoice JSON', e);
        }
    }, []);

    // 2. Global click listener
    useEffect(() => {
        const handler = (e) => {
            // Open Payment Modal
            const payBtn = e.target.closest('.open-payment-modal');
            if (payBtn) {
                e.preventDefault();
                const raw = payBtn.getAttribute('data-invoice');
                if (raw) {
                    try { setInvoice(JSON.parse(raw)); }
                    catch (err) { console.error('Invalid button invoice JSON', err); }
                }
                setMethod('gcash');
                setReferenceNumber('');
                setReceipt(null);
                setError('');
                setOpen(true);
                return;
            }

            // Open Cancel Modal
            const cancelBtn = e.target.closest('.cancel-payment-btn');
            if (cancelBtn) {
                e.preventDefault();
                const ref = cancelBtn.dataset.invoiceRef;
                if (ref) {
                    setCancelTarget({ ref });
                    setShowCancel(true);
                }
                return;
            }
        };

        document.addEventListener('click', handler);
        return () => document.removeEventListener('click', handler);
    }, []);

    const close = () => { if (!saving) setOpen(false); };
    const closeCancelModal = () => {
        if (cancelSaving) return;
        setShowCancel(false);
        setCancelTarget(null);
    };

    const safeJson = async (res) => {
        const text = await res.text();
        try { return JSON.parse(text); }
        catch { return { message: text.slice(0, 300) || `HTTP ${res.status}` }; }
    };

    const handleSubmit = async () => {
        setError('');
        if (method !== 'counter' && !referenceNumber.trim()) {
            setError('Please enter a reference / transaction number.'); return;
        }
        if ((method === 'gcash' || method === 'bank') && !receipt) {
            setError('Please upload your receipt screenshot.'); return;
        }
        setSaving(true);
        try {
            const fd = new FormData();
            fd.append('invoice_ref',      invoice.reference);
            fd.append('payment_method',   METHOD_MAP[method]);
            fd.append('reference_number', referenceNumber || '');
            if (receipt) fd.append('receipt', receipt);

            const res = await fetch('/tenants/billing/pay', {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
                body: fd,
            });
            const data = await safeJson(res);
            if (!res.ok) {
                if (data.errors) {
                    const first = Object.values(data.errors)[0]?.[0];
                    throw new Error(first || data.message || `Validation failed (HTTP ${res.status}).`);
                }
                throw new Error(data.message || `Request failed (HTTP ${res.status}).`);
            }
            close();
            window.location.reload();
        } catch (err) {
            setError(err.message);
        } finally {
            setSaving(false);
        }
    };

    const confirmCancelPayment = async () => {
        if (!cancelTarget) return;
        setCancelSaving(true);
        try {
            const res = await fetch('/tenants/billing/cancel', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                },
                body: JSON.stringify({ invoice_ref: cancelTarget.ref }),
            });
            const result = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(result.message || 'Failed to cancel payment.');
            setShowCancel(false);
            setCancelTarget(null);
            window.location.reload();
        } catch (err) {
            alert(err.message);
        } finally {
            setCancelSaving(false);
        }
    };

    return (
        <>
            {/* Payment Modal */}
            {open && invoice && (
                <Modal onClose={close} maxWidth="max-w-[480px]">
                    <div className="p-5 pb-4 flex items-center justify-between border-b border-slate-100">
                        <h3 className="text-lg font-bold text-slate-900">Submit Payment</h3>
                        <button onClick={close} disabled={saving} className="text-slate-400 hover:text-slate-700 text-lg disabled:opacity-50">
                            <i className="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div className="p-6 overflow-y-auto flex flex-col gap-4">
                        <div className="bg-slate-50 rounded-xl p-4 text-center">
                            <span className="text-[11px] text-slate-500">{invoice.title}</span>
                            <h2 className="text-[26px] font-extrabold text-slate-800 my-0.5">
                                ₱{Number(invoice.amount).toLocaleString()}
                            </h2>
                            <p className="text-[11px] text-slate-400">Due: {invoice.due_date}</p>
                            <p className="text-[10px] text-slate-400 mt-1">Ref: {invoice.reference}</p>
                        </div>

                        {error && (
                            <div className="bg-rose-50 border border-rose-200 text-rose-700 rounded-lg px-3 py-2 text-[12px]">
                                <i className="fa-solid fa-circle-exclamation mr-1.5"></i>{error}
                            </div>
                        )}

                        <div className="text-xs font-semibold text-slate-800">Payment Method</div>
                        <div className="grid grid-cols-3 gap-2.5">
                            {[
                                { key: 'gcash',   label: 'GCash',         icon: 'fa-solid fa-mobile-screen-button' },
                                { key: 'bank',    label: 'Bank Transfer', icon: 'fa-solid fa-building-columns' },
                                { key: 'counter', label: 'Counter',       icon: 'fa-solid fa-circle-info' },
                            ].map((m) => (
                                <button key={m.key} type="button" onClick={() => setMethod(m.key)}
                                    className={`flex flex-col items-center gap-1.5 border rounded-lg py-3 text-[11px] font-semibold transition ${
                                        method === m.key
                                            ? 'border-blue-400 text-blue-600 bg-blue-50'
                                            : 'border-slate-200 text-slate-500 bg-white'
                                    }`}>
                                    <i className={`${m.icon} text-lg`}></i>
                                    <span>{m.label}</span>
                                </button>
                            ))}
                        </div>

                        {method === 'gcash' && (
                            <div className="bg-blue-50 rounded-lg p-3.5">
                                <div className="text-[11px] text-blue-600 font-semibold mb-1">GCash Number:</div>
                                <div className="text-lg font-extrabold text-blue-700 flex items-center gap-2">
                                    {invoice.gcash_number}
                                    <i className="fa-regular fa-copy text-sm cursor-pointer"
                                       onClick={() => navigator.clipboard?.writeText((invoice.gcash_number || '').replace(/\D/g, ''))}></i>
                                </div>
                                <div className="text-[11px] text-blue-500 mt-1.5">
                                    Send ₱{Number(invoice.amount).toLocaleString()} then enter the reference number below.
                                </div>
                            </div>
                        )}

                        {method === 'counter' && (
                            <div className="bg-green-50 rounded-lg p-3 text-[11px] text-green-800 flex gap-2.5">
                                <i className="fa-solid fa-circle-info mt-0.5"></i>
                                <span>Over-the-counter payments will be marked as paid by the admin when confirmed.</span>
                            </div>
                        )}

                        {method !== 'counter' && (
                            <>
                                <div>
                                    <label className="block text-xs font-semibold text-slate-800 mb-1.5">
                                        Reference / Transaction Number <span className="text-rose-500">*</span>
                                    </label>
                                    <input type="text" value={referenceNumber}
                                        onChange={(e) => setReferenceNumber(e.target.value)}
                                        placeholder={method === 'gcash' ? 'e.g. GC-20260323-12345' : 'e.g. BT-20260323-78901'}
                                        className="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-[13px] outline-none focus:border-blue-400"/>
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold text-slate-800 mb-1.5">
                                        Upload Receipt Screenshot <span className="text-rose-500">*</span>
                                    </label>
                                    <label htmlFor="receipt-upload"
                                        className="border border-dashed border-slate-200 rounded-lg py-3.5 text-center text-slate-400 text-xs cursor-pointer block hover:bg-slate-50 transition">
                                        <i className="fa-solid fa-arrow-up-from-bracket mr-1.5"></i>
                                        {receipt ? receipt.name : 'Click to upload screenshot (JPG/PNG/WEBP, ≤10 MB)'}
                                    </label>
                                    <input id="receipt-upload" type="file" accept="image/*"
                                        onChange={(e) => setReceipt(e.target.files[0] || null)} className="hidden"/>
                                </div>
                            </>
                        )}

                        <div className="bg-blue-50 rounded-lg p-3 flex gap-2.5 text-[11px] text-blue-600 leading-snug">
                            <i className="fa-solid fa-circle-info mt-0.5"></i>
                            <span>Your payment will be reviewed by the admin. You'll be notified once verified.</span>
                        </div>
                    </div>

                    <div className="p-5 border-t border-slate-100 flex justify-end items-center gap-3">
                        <button onClick={close} disabled={saving}
                            className="text-slate-500 text-[13px] font-semibold px-4 py-2.5 disabled:opacity-50">
                            Cancel
                        </button>
                        <button onClick={handleSubmit} disabled={saving}
                            className="bg-[#02122c] hover:bg-[#0d2347] disabled:opacity-50 text-white px-5 py-2.5 rounded-lg text-[13px] font-semibold inline-flex items-center gap-2">
                            <i className={`fa-solid ${saving ? 'fa-spinner fa-spin' : 'fa-check'}`}></i>
                            {saving ? 'Submitting…' : 'Submit Payment'}
                        </button>
                    </div>
                </Modal>
            )}

            {/* Cancel Confirmation Modal */}
            {showCancel && cancelTarget && (
                <Modal onClose={closeCancelModal} maxWidth="max-w-[460px]">
                    <div className="p-5 pb-4 flex items-center justify-between border-b border-slate-100 shrink-0">
                        <h3 className="text-lg font-bold text-slate-900">Cancel Payment</h3>
                        <button
                            onClick={closeCancelModal}
                            disabled={cancelSaving}
                            className="text-slate-400 hover:text-slate-700 text-lg disabled:opacity-50"
                        >
                            <i className="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div className="p-6 text-center overflow-y-auto max-h-[60vh]">
                        <div className="w-16 h-16 mx-auto rounded-full bg-rose-50 flex items-center justify-center mb-4">
                            <i className="fa-solid fa-trash-can text-rose-500 text-2xl"></i>
                        </div>
                        <h4 className="text-lg font-bold text-slate-900 mb-2">Are you sure?</h4>
                        <p className="text-slate-500 text-[13px] mb-2">
                            You are about to cancel the payment for:
                        </p>
                        <p className="font-bold text-slate-900 text-[15px] mb-4">
                            {cancelTarget.ref}
                        </p>
                        <div className="bg-amber-50 border border-amber-200 rounded-xl p-3 text-left">
                            <p className="text-[12px] text-amber-800 leading-snug">
                                <i className="fa-solid fa-circle-info mr-1"></i>
                                This will remove the uploaded proof and you will need to submit a new payment request.{' '}
                                <strong>This action cannot be undone.</strong>
                            </p>
                        </div>
                    </div>

                    <div className="p-5 border-t border-slate-100 flex justify-end gap-3 shrink-0">
                        <button
                            onClick={closeCancelModal}
                            disabled={cancelSaving}
                            className="text-slate-600 text-[13.5px] font-semibold px-4 py-2 disabled:opacity-50"
                        >
                            Keep Payment
                        </button>
                        <button
                            onClick={confirmCancelPayment}
                            disabled={cancelSaving}
                            className="bg-rose-500 hover:bg-rose-600 disabled:opacity-50 text-white px-5 py-2.5 rounded-lg text-[13.5px] font-semibold inline-flex items-center gap-2 transition"
                        >
                            {cancelSaving ? (
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