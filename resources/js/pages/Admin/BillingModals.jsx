// resources/js/pages/Admin/BillingModals.jsx
import React, { useState, useEffect } from 'react';
import Modal from '../../components/Modal';

export default function BillingModals() {
    const [showEdit, setShowEdit] = useState(false);
    const [showSummary, setShowSummary] = useState(false);
    const [showReceipt, setShowReceipt] = useState(false);

    useEffect(() => {
        const handleClick = (e) => {
            if (e.target.closest('.icon-edit')) return setShowEdit(true);
            if (e.target.closest('.icon-view')) return setShowSummary(true);
            if (e.target.closest('.icon-payment')) return setShowReceipt(true);
        };
        document.addEventListener('click', handleClick);
        return () => document.removeEventListener('click', handleClick);
    }, []);

    return (
        <>
            {showEdit && (
                <Modal onClose={() => setShowEdit(false)} maxWidth="max-w-[520px]">
                    <div className="p-5 pb-4 flex items-center justify-between border-b border-slate-100">
                        <h3 className="text-lg font-bold text-slate-900">Edit Invoice</h3>
                        <button onClick={() => setShowEdit(false)} className="text-slate-400 hover:text-slate-700 text-lg">
                            <i className="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div className="p-6 overflow-y-auto">
                        <div className="grid grid-cols-2 gap-3.5">
                            <div>
                                <label className="block text-xs font-semibold text-slate-600 mb-1.5">Tenant Name</label>
                                <input type="text" defaultValue="Nicole Custorio" className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none" />
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-slate-600 mb-1.5">Room Number</label>
                                <input type="text" defaultValue="R202" className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none" />
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-slate-600 mb-1.5">Check In</label>
                                <input type="text" defaultValue="03-20-2026 07:00 AM" className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none" />
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-slate-600 mb-1.5">Check Out</label>
                                <input type="text" defaultValue="03-22-2026 07:00 AM" className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none" />
                            </div>
                            <div className="col-span-2">
                                <label className="block text-xs font-semibold text-slate-600 mb-1.5">Description</label>
                                <input type="text" defaultValue="Solo - March 20-22, 2026" className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none" />
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-slate-600 mb-1.5">Total Amount (₱)</label>
                                <input type="text" defaultValue="1,900" className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none" />
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-slate-600 mb-1.5">Amount Paid</label>
                                <input type="text" defaultValue="0" className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none" />
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-slate-600 mb-1.5">Balance</label>
                                <input type="text" defaultValue="1,900" className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none" />
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-slate-600 mb-1.5">Due Date</label>
                                <input type="text" defaultValue="03-22-2026" className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none" />
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-slate-600 mb-1.5">Payment Method</label>
                                <select className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none bg-white">
                                    <option>Not Set</option>
                                    <option>GCash</option>
                                    <option>Over The Counter</option>
                                </select>
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-slate-600 mb-1.5">Status</label>
                                <select className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none bg-white">
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
                        <button onClick={() => setShowEdit(false)} className="text-slate-600 text-[13px] font-semibold px-4 py-2">Cancel</button>
                        <button onClick={() => setShowEdit(false)} className="bg-[#02122b] text-white px-5 py-2.5 rounded-lg text-[13px] font-semibold">
                            Save Changes
                        </button>
                    </div>
                </Modal>
            )}

            {showSummary && (
                <Modal onClose={() => setShowSummary(false)} maxWidth="max-w-[520px]">
                    <div className="p-5 pb-4 flex items-center justify-between border-b border-slate-100">
                        <h3 className="text-lg font-bold text-slate-900">Invoice Summary</h3>
                        <button onClick={() => setShowSummary(false)} className="text-slate-400 hover:text-slate-700 text-lg">
                            <i className="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div className="p-6 overflow-y-auto flex flex-col gap-4">
                        <div className="flex items-center justify-between">
                            <span className="text-xs text-slate-400">inv-101</span>
                            <span className="px-3 py-1 rounded-full text-[11px] font-bold bg-amber-100 text-amber-700">Unpaid</span>
                        </div>

                        <div className="bg-orange-50 rounded-xl p-5 text-center">
                            <div className="text-[11px] font-bold text-orange-600">Total Amount</div>
                            <div className="text-[28px] font-extrabold text-orange-600 mt-1">₱1,900</div>
                            <div className="flex justify-center gap-5 text-[11px] text-slate-400 mt-1.5">
                                <span>2 days</span>
                                <span>950 per day</span>
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="bg-slate-50 rounded-xl p-3"><div className="text-[11px] text-slate-400">Tenant</div><div className="text-[13px] font-bold text-slate-900 mt-0.5">Nicole Custorio</div></div>
                            <div className="bg-slate-50 rounded-xl p-3"><div className="text-[11px] text-slate-400">Room Number</div><div className="text-[13px] font-bold text-slate-900 mt-0.5">R202</div></div>
                            <div className="bg-slate-50 rounded-xl p-3"><div className="text-[11px] text-slate-400">Check In</div><div className="text-[13px] font-bold text-slate-900 mt-0.5">03-20-2026 07:00 AM</div></div>
                            <div className="bg-slate-50 rounded-xl p-3"><div className="text-[11px] text-slate-400">Check Out</div><div className="text-[13px] font-bold text-slate-900 mt-0.5">03-22-2026 07:00 AM</div></div>
                            <div className="bg-slate-50 rounded-xl p-3"><div className="text-[11px] text-slate-400">Amount Paid</div><div className="text-[13px] font-bold text-slate-900 mt-0.5">₱0</div></div>
                            <div className="bg-slate-50 rounded-xl p-3"><div className="text-[11px] text-slate-400">Balance</div><div className="text-[13px] font-bold text-slate-900 mt-0.5">₱1,900</div></div>
                            <div className="bg-slate-50 rounded-xl p-3 col-span-2"><div className="text-[11px] text-slate-400">Due Date</div><div className="text-[13px] font-bold text-slate-900 mt-0.5">03-22-2026</div></div>
                        </div>

                        <div className="text-[13px] font-bold text-slate-900 mt-1">Payments:</div>

                        <div className="bg-[#02122b] text-white rounded-xl p-4 flex flex-col gap-2.5">
                            <div className="flex justify-between text-xs"><span className="text-slate-400">Payment #</span><span className="font-semibold">-</span></div>
                            <div className="flex justify-between text-xs"><span className="text-slate-400">Amount</span><span className="font-semibold">₱0</span></div>
                            <div className="flex justify-between text-xs"><span className="text-slate-400">Payment Method</span><span className="font-semibold">-</span></div>
                            <div className="flex justify-between text-xs"><span className="text-slate-400">Date Paid</span><span className="font-semibold">-</span></div>
                        </div>
                    </div>
                    <div className="p-5 border-t border-slate-100 flex justify-start">
                        <button onClick={() => setShowSummary(false)} className="bg-[#02122b] text-white px-5 py-2.5 rounded-lg text-[13px] font-semibold">Close</button>
                    </div>
                </Modal>
            )}

            {showReceipt && (
                <Modal onClose={() => setShowReceipt(false)} maxWidth="max-w-[520px]">
                    <div className="p-5 pb-4 flex items-center justify-between border-b border-slate-100">
                        <h3 className="text-lg font-bold text-slate-900">Payment Receipt</h3>
                        <button onClick={() => setShowReceipt(false)} className="text-slate-400 hover:text-slate-700 text-lg">
                            <i className="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div className="p-6 overflow-y-auto">
                        <div className="border-[1.5px] border-dashed border-orange-400 bg-orange-50/30 rounded-2xl p-5 flex flex-col items-center gap-3">
                            <div className="w-11 h-11 rounded-full bg-orange-500 text-white flex items-center justify-center text-xl">
                                <i className="fa-solid fa-check"></i>
                            </div>
                            <div className="text-center">
                                <h4 className="text-base font-extrabold text-slate-900">Official Receipt</h4>
                                <p className="text-[11px] text-orange-600 font-semibold">JLM Cozy Cabin</p>
                            </div>
                            <div className="text-sm font-semibold text-slate-600">Transaction No. OTC-20260215-0018</div>
                            <div className="text-[11px] text-slate-400">Date of Issue: March 22, 2026</div>

                            <div className="w-full border-t border-slate-100 pt-3 flex flex-col gap-2">
                                {[
                                    ['Receipt No.', 'Inv-105'],
                                    ['Tenant', 'Francis Sabiniano'],
                                    ['Room', 'Room 203'],
                                    ['Description', 'Solo - January 15-22, 2026'],
                                    ['Check-in', '01-15-2026 10:00AM'],
                                    ['Check-out', '01-22-2026 10:00AM'],
                                    ['Rate Per Day', '₱600'],
                                ].map((row, i) => (
                                    <div key={i} className="flex justify-between text-xs">
                                        <span className="text-slate-500 font-medium">{row[0]}</span>
                                        <span className="text-slate-900 font-bold">{row[1]}</span>
                                    </div>
                                ))}
                                <div className="flex justify-between text-sm mt-2 pt-2 border-t border-slate-100">
                                    <span className="text-slate-900 font-bold">Total Amount</span>
                                    <span className="text-slate-900 font-extrabold">₱4,200</span>
                                </div>
                            </div>

                            <div className="w-full border-t border-dashed border-slate-200 pt-3 flex flex-col gap-2">
                                <div className="text-xs font-bold text-slate-900 mb-1">Payment Details:</div>
                                <div className="flex justify-between text-xs"><span className="text-slate-500">Payment #</span><span className="text-slate-900 font-bold">REF-20260215-0018</span></div>
                                <div className="flex justify-between text-xs"><span className="text-slate-500">Payment Method</span><span className="text-slate-900 font-bold">Over The Counter</span></div>
                                <div className="flex justify-between text-xs"><span className="text-slate-500">Amount Paid</span><span className="text-slate-900 font-bold">₱2,200</span></div>
                                <div className="flex justify-between text-xs"><span className="text-slate-500">Date Paid</span><span className="text-slate-900 font-bold">Jan 15, 2026</span></div>
                            </div>

                            <div className="text-center mt-2">
                                <p className="text-[11px] text-slate-500">Authorized By: <strong>Leah Agujar</strong></p>
                                <p className="text-xs font-bold text-orange-500 mt-1">Thank You! :)</p>
                            </div>
                        </div>

                        <button className="w-full mt-3 border border-slate-300 bg-white rounded-lg py-2.5 text-[13px] font-bold text-slate-900 inline-flex items-center justify-center gap-2 hover:bg-slate-50 transition">
                            <i className="fa-solid fa-print"></i> Print Receipt
                        </button>
                    </div>
                </Modal>
            )}
        </>
    );
}