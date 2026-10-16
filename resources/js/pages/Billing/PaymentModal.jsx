// resources/js/pages/Billing/PaymentModal.jsx
import React, { useState, useEffect } from 'react';
import Modal from '../../components/Modal';

const csrf = () =>
    document.querySelector('meta[name="csrf-token"]')?.content ||
    document.querySelector('input[name="_token"]')?.value;

const FALLBACK = {
    reference:    'inv-101',
    title:        'Rent - March 2026',
    amount:       600,
    due_date:     'Mar 30, 2026',
    gcash_number: '0917-123-4567',
};

export default function PaymentModal() {
    const [open, setOpen]       = useState(false);
    const [saving, setSaving]   = useState(false);
    const [invoice, setInvoice] = useState(FALLBACK);

    // Form state
    const [method, setMethod]                 = useState('gcash');
    const [referenceNumber, setReferenceNumber] = useState('');
    const [receipt, setReceipt]               = useState(null);

    // ============================================================
    // Read invoice data from mount element
    // ============================================================
    useEffect(() => {
        const el = document.getElementById('payment-modal-root');
        if (!el) return;

        if (el.dataset.invoice) {
            try {
                setInvoice(JSON.parse(el.dataset.invoice));
            } catch (e) {
                console.error('Invalid invoice JSON', e);
            }
        }
    }, []);

    // ============================================================
    // Open modal on Pay Now click
    // ============================================================
    useEffect(() => {
        const handler = (e) => {
            if (e.target.closest('#open-payment-modal')) {
                // Reset form on open
                setMethod('gcash');
                setReferenceNumber('');
                setReceipt(null);
                setOpen(true);
            }
        };
        document.addEventListener('click', handler);
        return () => document.removeEventListener('click', handler);
    }, []);

    const close = () => setOpen(false);

    // ============================================================
    // Submit payment
    // ============================================================
    const handleSubmit = async () => {
        // Validate
        if (method !== 'counter') {
            if (!referenceNumber.trim()) {
                alert('Please enter a reference number.');
                return;
            }
        }

        setSaving(true);
        try {
            const fd = new FormData();
            fd.append('invoice_ref',     invoice.reference);
            fd.append('payment_method',  method);
            fd.append('reference_number', referenceNumber || '');
            if (receipt) fd.append('receipt', receipt);

            const res = await fetch('/tenants/billing/pay', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                },
                body: fd,
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(data.message || 'Failed to submit payment.');
            close();
            window.location.reload();
        } catch (err) {
            alert(err.message);
        } finally {
            setSaving(false);
        }
    };

    if (!open) return null;

    return (
        <Modal onClose={close} maxWidth="max-w-[480px]">
            <div className="p-5 pb-4 flex items-center justify-between border-b border-slate-100">
                <h3 className="text-lg font-bold text-slate-900">Submit Payment</h3>
                <button
                    onClick={close}
                    className="text-slate-400 hover:text-slate-700 text-lg"
                >
                    <i className="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div className="p-6 overflow-y-auto flex flex-col gap-4">

                {/* Amount box */}
                <div className="bg-slate-50 rounded-xl p-4 text-center">
                    <span className="text-[11px] text-slate-500">{invoice.title}</span>
                    <h2 className="text-[26px] font-extrabold text-slate-800 my-0.5">
                        ₱{Number(invoice.amount).toLocaleString()}
                    </h2>
                    <p className="text-[11px] text-slate-400">Due: {invoice.due_date}</p>
                </div>

                {/* Method selector */}
                <div className="text-xs font-semibold text-slate-800">Payment Method</div>
                <div className="grid grid-cols-3 gap-2.5">
                    {[
                        { key: 'gcash',   label: 'GCash',         icon: 'fa-solid fa-mobile-screen-button' },
                        { key: 'bank',    label: 'Bank Transfer', icon: 'fa-solid fa-building-columns' },
                        { key: 'counter', label: 'Counter',       icon: 'fa-solid fa-circle-info' },
                    ].map((m) => (
                        <button
                            key={m.key}
                            type="button"
                            onClick={() => setMethod(m.key)}
                            className={`flex flex-col items-center gap-1.5 border rounded-lg py-3 text-[11px] font-semibold transition ${
                                method === m.key
                                    ? 'border-blue-400 text-blue-600 bg-blue-50'
                                    : 'border-slate-200 text-slate-500 bg-white'
                            }`}
                        >
                            <i className={`${m.icon} text-lg`}></i>
                            <span>{m.label}</span>
                        </button>
                    ))}
                </div>

                {/* GCash box */}
                {method === 'gcash' && (
                    <div className="bg-blue-50 rounded-lg p-3.5">
                        <div className="text-[11px] text-blue-600 font-semibold mb-1">GCash Number:</div>
                        <div className="text-lg font-extrabold text-blue-700 flex items-center gap-2">
                            {invoice.gcash_number}
                            <i className="fa-regular fa-copy text-sm cursor-pointer"></i>
                        </div>
                        <div className="text-[11px] text-blue-500 mt-1.5">
                            Send ₱{Number(invoice.amount).toLocaleString()} then enter the reference number below.
                        </div>
                    </div>
                )}

                {/* Counter notice */}
                {method === 'counter' && (
                    <div className="bg-green-50 rounded-lg p-3 text-[11px] text-green-800 flex gap-2.5">
                        <i className="fa-solid fa-circle-info mt-0.5"></i>
                        <span>Over-the-counter payments will be marked as paid by the admin when confirmed.</span>
                    </div>
                )}

                {/* Reference + Upload (hidden for counter) */}
                {method !== 'counter' && (
                    <>
                        <div>
                            <label className="block text-xs font-semibold text-slate-800 mb-1.5">
                                Reference / Transaction Number <span className="text-rose-500">*</span>
                            </label>
                            <input
                                type="text"
                                value={referenceNumber}
                                onChange={(e) => setReferenceNumber(e.target.value)}
                                placeholder={method === 'gcash'
                                    ? 'e.g. GC-20260323-12345'
                                    : 'e.g. BT-20260323-78901'}
                                className="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-[13px] outline-none focus:border-blue-400"
                            />
                        </div>

                        <div>
                            <label className="block text-xs font-semibold text-slate-800 mb-1.5">
                                Upload Receipt Screenshot <span className="text-slate-400 font-normal">(optional)</span>
                            </label>
                            <label
                                htmlFor="receipt-upload"
                                className="border border-dashed border-slate-200 rounded-lg py-3.5 text-center text-slate-400 text-xs cursor-pointer block hover:bg-slate-50 transition"
                            >
                                <i className="fa-solid fa-arrow-up-from-bracket mr-1.5"></i>
                                {receipt ? receipt.name : 'Click to upload screenshot'}
                            </label>
                            <input
                                id="receipt-upload"
                                type="file"
                                accept="image/*"
                                onChange={(e) => setReceipt(e.target.files[0] || null)}
                                className="hidden"
                            />
                        </div>
                    </>
                )}

                {/* Info notice */}
                <div className="bg-blue-50 rounded-lg p-3 flex gap-2.5 text-[11px] text-blue-600 leading-snug">
                    <i className="fa-solid fa-circle-info mt-0.5"></i>
                    <span>Your payment will be reviewed by the admin. You'll be notified once verified.</span>
                </div>
            </div>

            <div className="p-5 border-t border-slate-100 flex justify-end items-center gap-3">
                <button
                    onClick={close}
                    className="text-slate-500 text-[13px] font-semibold px-4 py-2.5"
                >
                    Cancel
                </button>
                <button
                    onClick={handleSubmit}
                    disabled={saving}
                    className="bg-[#02122c] hover:bg-[#0d2347] disabled:opacity-50 text-white px-5 py-2.5 rounded-lg text-[13px] font-semibold inline-flex items-center gap-2"
                >
                    <i className="fa-solid fa-check"></i>
                    {saving ? 'Submitting…' : 'Submit Payment'}
                </button>
            </div>
        </Modal>
    );
}