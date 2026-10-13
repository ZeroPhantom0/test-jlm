// resources/js/pages/Billing/PaymentModal.jsx
import React, { useState } from 'react';

export default function PaymentModal({ onClose }) {
    const [method, setMethod] = useState('gcash');

    return (
        <div className="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-1000 flex items-center justify-center p-4">
            <div className="bg-white rounded-2xl w-full max-w-120 p-6 shadow-2xl relative max-h-[90vh] overflow-y-auto">
                <button onClick={onClose} className="absolute top-5 right-5 text-slate-400 text-lg">
                    <i className="fa-solid fa-xmark"></i>
                </button>

                <h3 className="text-lg font-bold mb-4">Submit Payment</h3>

                <div className="bg-slate-50 rounded-xl p-4 text-center mb-5">
                    <span className="text-[11px] text-slate-500">Rent - March 2026</span>
                    <h2 className="text-[26px] font-extrabold text-slate-800 my-0.5">₱600</h2>
                    <p className="text-[11px] text-slate-400">Due: Mar 30, 2026</p>
                </div>

                <div className="text-xs font-semibold text-slate-800 mb-2.5">Payment Method</div>

                <div className="grid grid-cols-3 gap-2.5 mb-4">
                    {[
                        { key: 'gcash',   label: 'GCash',         icon: 'fa-solid fa-mobile-screen-button' },
                        { key: 'bank',    label: 'Bank Transfer', icon: 'fa-solid fa-building-columns' },
                        { key: 'counter', label: 'Counter',       icon: 'fa-solid fa-circle-info' },
                    ].map(m => (
                        <button key={m.key}
                            onClick={() => setMethod(m.key)}
                            className={`flex flex-col items-center gap-1.5 border rounded-lg py-3 text-[11px] font-semibold transition
                                ${method === m.key
                                    ? 'border-blue-400 text-blue-600 bg-blue-50'
                                    : 'border-slate-200 text-slate-500 bg-white'}`}>
                            <i className={`${m.icon} text-lg`}></i>
                            <span>{m.label}</span>
                        </button>
                    ))}
                </div>

                {method === 'gcash' && (
                    <div className="bg-blue-50 rounded-lg p-3.5 mb-4">
                        <div className="text-[11px] text-blue-600 font-semibold mb-1">GCash Number:</div>
                        <div className="text-lg font-extrabold text-blue-700 flex items-center gap-2">
                            0917-123-4567
                            <i className="fa-regular fa-copy text-sm cursor-pointer"></i>
                        </div>
                        <div className="text-[11px] text-blue-500 mt-1.5">
                            Send ₱600 then enter the reference number below.
                        </div>
                    </div>
                )}

                {method === 'counter' && (
                    <div className="bg-green-50 rounded-lg p-3 text-[11px] text-green-800 flex gap-2.5 mb-4">
                        <i className="fa-solid fa-circle-info mt-0.5"></i>
                        <span>Over-the-counter payments will be marked as paid by the admin when confirmed.</span>
                    </div>
                )}

                {method !== 'counter' && (
                    <>
                        <div className="mb-4">
                            <label className="block text-xs font-semibold text-slate-800 mb-1.5">
                                Reference / Transaction Number <span className="text-rose-500">*</span>
                            </label>
                            <input type="text"
                                placeholder={method === 'gcash' ? 'e.g. GC-20260323-12345' : 'e.g. BT-20260323-78901'}
                                className="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-[13px] outline-none focus:border-blue-400" />
                        </div>

                        <div className="mb-4">
                            <label className="block text-xs font-semibold text-slate-800 mb-1.5">
                                Upload Receipt Screenshot <span className="text-slate-400 font-normal">(optional)</span>
                            </label>
                            <div className="border border-dashed border-slate-200 rounded-lg py-3.5 text-center text-slate-400 text-xs cursor-pointer">
                                <i className="fa-solid fa-arrow-up-from-bracket mr-1.5"></i> Click to upload screenshot
                            </div>
                        </div>
                    </>
                )}

                <div className="bg-blue-50 rounded-lg p-3 flex gap-2.5 text-[11px] text-blue-600 leading-snug mb-5">
                    <i className="fa-solid fa-circle-info mt-0.5"></i>
                    <span>Your payment will be reviewed by the admin. You'll be notified once verified.</span>
                </div>

                <div className="flex justify-end items-center gap-3">
                    <button onClick={onClose} className="text-slate-500 text-[13px] font-semibold px-4 py-2.5">
                        Cancel
                    </button>
                    <button onClick={onClose}
                        className="bg-navy-dark text-white px-5 py-2.5 rounded-lg text-[13px] font-semibold inline-flex items-center gap-2">
                        <i className="fa-solid fa-check"></i> Submit Payment
                    </button>
                </div>
            </div>
        </div>
    );
}