// resources/js/pages/Admin/BillingModals.jsx
import React, { useState, useEffect } from 'react';
import Modal from '../../components/Modal';

const csrf = () =>
    document.querySelector('meta[name="csrf-token"]')?.content ||
    document.querySelector('input[name="_token"]')?.value;

const FALLBACK = {
    tn: 'tn-000',
    description: 'No description',
    tenant: 'Unknown',
    room: 'N/A',
    total: '₱0',
    rate: '₱ 0/day',
    paid: '₱0',
    days: '0 days',
    balance: '₱0',
    due: '-',
    status: 'Unpaid',
    check_in: '-',
    check_out: '-',
    payment_method: 'Not Set',
    receipt_url: null,
    is_remaining: false,
    date_paid: null, // Default to null
    receipt_no: '-',
};

export default function BillingModals() {
    const [showEdit, setShowEdit]       = useState(false);
    const [showSummary, setShowSummary] = useState(false);
    const [showReceipt, setShowReceipt] = useState(false);
    const [invoice, setInvoice]         = useState(null);
    const [saving, setSaving]           = useState(false);
    const [invoicesData, setInvoicesData] = useState({});

    useEffect(() => {
        const el = document.getElementById('admin-billing-modals');
        if (el?.dataset?.invoices) {
            try {
                setInvoicesData(JSON.parse(el.dataset.invoices));
            } catch (e) {
                console.error('Invalid invoices JSON', e);
            }
        }
    }, []);

    useEffect(() => {
        const handleClick = (e) => {
            const editBtn    = e.target.closest('.icon-edit');
            const viewBtn    = e.target.closest('.icon-view');
            const payBtn     = e.target.closest('.icon-payment');
            const receiptBtn = e.target.closest('.icon-receipt');
            const remindBtn  = e.target.closest('.icon-remind');

            const btn = editBtn || viewBtn || payBtn || receiptBtn || remindBtn;
            if (!btn) return;

            const row  = btn.closest('tr');
            const tn   = row?.dataset?.tn;
            const data = invoicesData[tn] || FALLBACK;
            setInvoice(data);

            if (editBtn)    return setShowEdit(true);
            if (viewBtn)    return setShowSummary(true);
            if (payBtn)     return setShowReceipt(true);
            if (receiptBtn) return setShowReceipt(true);
            if (remindBtn)  return handleSendReminder(data);
        };
        document.addEventListener('click', handleClick);
        return () => document.removeEventListener('click', handleClick);
    }, [invoicesData]);

    const closeAll = () => {
        setShowEdit(false);
        setShowSummary(false);
        setShowReceipt(false);
        setInvoice(null);
    };

    const handleSendReminder = async (data) => {
        if (!confirm(`Send payment reminder to ${data.tenant}?`)) return;
        try {
            const res = await fetch(`/admin/billing/${data.tn}/remind`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                },
            });
            const result = await res.json();
            if (!res.ok) throw new Error(result.message || 'Failed to send reminder.');
            alert(result.message || 'Reminder sent.');
        } catch (err) {
            alert(err.message);
        }
    };

    const handleSaveEdit = async (formData) => {
        if (!invoice) return;
        setSaving(true);
        try {
            const res = await fetch(`/admin/billing/${invoice.tn}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                },
                body: JSON.stringify(formData),
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Failed to update.');
            closeAll();
            window.location.reload();
        } catch (err) {
            alert(err.message);
        } finally {
            setSaving(false);
        }
    };

    const handleVerify = async () => {
        if (!invoice) return;
        if (!confirm(`Verify payment for ${invoice.tn}?`)) return;

        try {
            const res = await fetch(`/admin/billing/${invoice.tn}/verify-payment`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                },
            });
            const result = await res.json();
            if (!res.ok) throw new Error(result.message || 'Verification failed.');
            closeAll();
            window.location.reload();
        } catch (err) {
            alert(err.message);
        }
    };

    const handleReject = async () => {
        if (!invoice) return;
        const notes = prompt('Reason for rejection (required):');
        if (!notes) return;

        try {
            const res = await fetch(`/admin/billing/${invoice.tn}/reject-payment`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                },
                body: JSON.stringify({ admin_notes: notes }),
            });
            const result = await res.json();
            if (!res.ok) throw new Error(result.message || 'Rejection failed.');
            closeAll();
            window.location.reload();
        } catch (err) {
            alert(err.message);
        }
    };

    const data = invoice || FALLBACK;

    // If date_paid is missing, show '-' instead of falling back to due date
    const displayDatePaid = data.date_paid && data.date_paid !== '-' ? data.date_paid : '-';

    const statusBadgeClass = {
        Unpaid:  'bg-amber-100 text-amber-700',
        Overdue: 'bg-rose-100 text-rose-600',
        Partial: 'bg-yellow-100 text-yellow-700',
        Pending: 'bg-blue-100 text-blue-600',
        Paid:    'bg-green-100 text-green-600',
    }[data.status] || 'bg-slate-100 text-slate-500';

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
                        <h3 className="text-lg font-bold text-slate-900">Invoice Summary</h3>
                        <button onClick={closeAll} className="text-slate-400 hover:text-slate-700 text-lg">
                            <i className="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div className="p-6 overflow-y-auto flex flex-col gap-4">
                        <div className="flex items-center justify-between">
                            <span className="text-xs text-slate-400">{data.tn}</span>
                            <span className={`px-3 py-1 rounded-full text-[11px] font-bold ${statusBadgeClass}`}>
                                {data.status}
                            </span>
                        </div>

                        <div className="bg-orange-50 rounded-xl p-5 text-center">
                            <div className="text-[11px] font-bold text-orange-600">Total Amount</div>
                            <div className="text-[28px] font-extrabold text-orange-600 mt-1">{data.total}</div>
                            <div className="flex justify-center gap-5 text-[11px] text-slate-400 mt-1.5">
                                <span>{data.days}</span>
                                <span>{data.rate.replace('₱ ', '')} per day</span>
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="bg-slate-50 rounded-xl p-3"><div className="text-[11px] text-slate-400">Tenant</div><div className="text-[13px] font-bold text-slate-900 mt-0.5">{data.tenant}</div></div>
                            <div className="bg-slate-50 rounded-xl p-3"><div className="text-[11px] text-slate-400">Room Number</div><div className="text-[13px] font-bold text-slate-900 mt-0.5">{data.room}</div></div>
                            <div className="bg-slate-50 rounded-xl p-3"><div className="text-[11px] text-slate-400">Check In</div><div className="text-[13px] font-bold text-slate-900 mt-0.5">{data.check_in}</div></div>
                            <div className="bg-slate-50 rounded-xl p-3"><div className="text-[11px] text-slate-400">Check Out</div><div className="text-[13px] font-bold text-slate-900 mt-0.5">{data.check_out}</div></div>
                            <div className="bg-slate-50 rounded-xl p-3"><div className="text-[11px] text-slate-400">Amount Paid</div><div className="text-[13px] font-bold text-slate-900 mt-0.5">{data.paid}</div></div>
                            <div className="bg-slate-50 rounded-xl p-3"><div className="text-[11px] text-slate-400">Balance</div><div className="text-[13px] font-bold text-slate-900 mt-0.5">{data.balance}</div></div>
                            <div className="bg-slate-50 rounded-xl p-3 col-span-2"><div className="text-[11px] text-slate-400">Due Date</div><div className="text-[13px] font-bold text-slate-900 mt-0.5">{data.due}</div></div>
                            
                            {/* Always show for Pending, Partial, Overdue, and Paid */}
                            {['Pending', 'Partial', 'Overdue', 'Paid'].includes(data.status) && (
                                <>
                                    <div className="bg-slate-50 rounded-xl p-3 col-span-1"><div className="text-[11px] text-slate-400">Payment Method</div><div className="text-[13px] font-bold text-blue-700 mt-0.5 uppercase">{data.payment_method || '-'}</div></div>
                                    <div className="bg-slate-50 rounded-xl p-3 col-span-1"><div className="text-[11px] text-slate-400">Date Paid</div><div className="text-[13px] font-bold text-slate-900 mt-0.5">{displayDatePaid}</div></div>
                                </>
                            )}
                        </div>

                        {data.status === 'Pending' && (
                            <>
                                <div className="text-[13px] font-bold text-slate-900 mt-1">Proof of Payment:</div>
                                <div className="bg-slate-50 border border-slate-200 rounded-xl p-4">
                                    <div className="text-xs font-semibold text-slate-700 mb-2">1 Attachment</div>
                                    {data.receipt_url ? (
                                        <a href={data.receipt_url} target="_blank" rel="noopener noreferrer" className="flex items-center gap-2 border border-slate-200 bg-white rounded-lg p-3 text-[13px] text-slate-700 hover:bg-slate-100 transition">
                                            <i className="fa-solid fa-file text-blue-500"></i>
                                            Proof of Payment - {data.tenant.split(' ')[0].toUpperCase()}.png
                                        </a>
                                    ) : (
                                        <div className="text-xs text-slate-400 italic">No image uploaded</div>
                                    )}
                                </div>
                                <div className="text-[13px] font-bold text-slate-900 mt-2">Tenant Note:</div>
                                <div className="text-[13px] text-slate-600 mt-1">{data.tenant_note || 'Transferred via ' + data.payment_method + '. Please verify.'}</div>
                            </>
                        )}

                        {data.status === 'Partial' && data.receipt_url && (
                            <>
                                <div className="text-[13px] font-bold text-slate-900 mt-1">Proof of Payment:</div>
                                <div className="bg-slate-50 border border-slate-200 rounded-xl p-4">
                                    <div className="text-xs font-semibold text-slate-700 mb-2">Verified Attachment</div>
                                    <a href={data.receipt_url} target="_blank" rel="noopener noreferrer" className="flex items-center gap-2 border border-slate-200 bg-white rounded-lg p-3 text-[13px] text-slate-700 hover:bg-slate-100 transition">
                                        <i className="fa-solid fa-file text-blue-500"></i>
                                        Proof of Payment - {data.tenant.split(' ')[0].toUpperCase()}.png
                                    </a>
                                </div>
                            </>
                        )}

                        {['Pending', 'Partial', 'Unpaid', 'Paid', 'Overdue'].includes(data.status) && (
                            <>
                                <div className="text-[13px] font-bold text-slate-900 mt-1">Payments:</div>
                                <div className="bg-[#02122b] text-white rounded-xl p-4 flex flex-col gap-3">
                                    <div className="flex justify-between text-xs">
                                        <span className="text-slate-400">Payment #</span>
                                        <span className="font-semibold">{data.receipt_no || '-'}</span>
                                    </div>
                                    <div className="flex justify-between text-xs">
                                        <span className="text-slate-400">Amount</span>
                                        <span className="font-semibold">{data.status === 'Unpaid' ? '₱0' : data.paid}</span>
                                    </div>
                                    <div className="flex justify-between text-xs">
                                        <span className="text-slate-400">Payment Method</span>
                                        <span className="font-semibold">{data.status === 'Unpaid' ? '-' : data.payment_method}</span>
                                    </div>
                                    <div className="flex justify-between text-xs">
                                        <span className="text-slate-400">Date Paid</span>
                                        <span className="font-semibold">{displayDatePaid}</span>
                                    </div>
                                </div>
                            </>
                        )}
                    </div>
                    
                    <div className="p-5 border-t border-slate-100 flex justify-between">
                        <button onClick={closeAll} className="bg-[#02122b] text-white px-5 py-2.5 rounded-lg text-[13px] font-semibold hover:bg-slate-800 transition">
                            Close
                        </button>

                        {data.status === 'Pending' && (
                            <div className="flex gap-2">
                                <button
                                    onClick={handleVerify}
                                    className="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2.5 rounded-lg text-[13px] font-semibold transition"
                                >
                                    Approve
                                </button>
                                <button
                                    onClick={handleReject}
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
                        <h3 className="text-lg font-bold text-slate-900">Payment Receipt</h3>
                        <button onClick={closeAll} className="text-slate-400 hover:text-slate-700 text-lg">
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
                            <div className="text-sm font-semibold text-slate-600">Transaction No. {data.tn.toUpperCase()}</div>
                            <div className="text-[11px] text-slate-400">Date of Issue: {displayDatePaid}</div>

                            <div className="w-full border-t border-slate-100 pt-3 flex flex-col gap-2">
                                {[
                                    ['Receipt No.',       data.tn.toUpperCase()],
                                    ['Tenant',            data.tenant],
                                    ['Room',              `Room ${data.room}`],
                                    ['Description',       data.description],
                                    ['Check-in',          data.check_in],
                                    ['Check-out',         data.check_out],
                                    ['Rate Per Day',      data.rate.replace('₱ ', '₱')],
                                ].map((row, i) => (
                                    <div key={i} className="flex justify-between text-xs">
                                        <span className="text-slate-500 font-medium">{row[0]}</span>
                                        <span className="text-slate-900 font-bold">{row[1]}</span>
                                    </div>
                                ))}
                                <div className="flex justify-between text-sm mt-2 pt-2 border-t border-slate-100">
                                    <span className="text-slate-900 font-bold">Total Amount</span>
                                    <span className="text-slate-900 font-extrabold">{data.total}</span>
                                </div>
                            </div>

                            <div className="w-full border-t border-dashed border-slate-200 pt-3 flex flex-col gap-2">
                                <div className="text-xs font-bold text-slate-900 mb-1">Payment Details:</div>
                                <div className="flex justify-between text-xs"><span className="text-slate-500">Payment #</span><span className="text-slate-900 font-bold">{data.receipt_no || '-'}</span></div>
                                <div className="flex justify-between text-xs"><span className="text-slate-500">Payment Method</span><span className="text-slate-900 font-bold">{data.payment_method}</span></div>
                                <div className="flex justify-between text-xs"><span className="text-slate-500">Amount Paid</span><span className="text-slate-900 font-bold">{data.paid}</span></div>
                                <div className="flex justify-between text-xs"><span className="text-slate-500">Date Paid</span><span className="text-slate-900 font-bold">{displayDatePaid}</span></div>
                            </div>

                            {data.receipt_url && (
                                <a
                                    href={data.receipt_url}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="w-full border border-orange-300 bg-orange-100 text-orange-700 rounded-lg py-2 text-[12px] font-bold inline-flex items-center justify-center gap-2 hover:bg-orange-200 transition"
                                >
                                    <i className="fa-regular fa-image"></i> View Uploaded Proof
                                </a>
                            )}

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

function EditInvoiceModal({ data, onClose, onSave, saving }) {
    const [form, setForm] = useState({
        tenant_name:    data.tenant,
        room_number:    data.room,
        description:    data.description,
        check_in:       data.check_in,
        check_out:      data.check_out,
        rate_per_day:   data.rate.replace(/[₱\s,]/g, '').replace('/day', ''),
        days:           data.days.replace(/\D/g, ''),
        amount_paid:    data.paid.replace(/[₱\s,]/g, ''),
        due_date:       data.due,
        payment_method: data.payment_method,
        status:         data.status,
    });

    const updateField = (key, value) => setForm((f) => ({ ...f, [key]: value }));

    const handleSubmit = () => {
        onSave({
            ...form,
            rate_per_day: parseFloat(form.rate_per_day) || 0,
            days:         parseInt(form.days, 10) || 1,
            amount_paid:  parseFloat(form.amount_paid) || 0,
        });
    };

    return (
        <Modal onClose={onClose} maxWidth="max-w-[520px]">
            <div className="p-5 pb-4 flex items-center justify-between border-b border-slate-100">
                <h3 className="text-lg font-bold text-slate-900">Edit Invoice</h3>
                <button onClick={onClose} className="text-slate-400 hover:text-slate-700 text-lg">
                    <i className="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div className="p-6 overflow-y-auto">
                <div className="grid grid-cols-2 gap-3.5">
                    <div>
                        <label className="block text-xs font-semibold text-slate-600 mb-1.5">Tenant Name</label>
                        <input type="text" value={form.tenant_name} onChange={(e) => updateField('tenant_name', e.target.value)} className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none" />
                    </div>
                    <div>
                        <label className="block text-xs font-semibold text-slate-600 mb-1.5">Room Number</label>
                        <input type="text" value={form.room_number} onChange={(e) => updateField('room_number', e.target.value)} className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none" />
                    </div>
                    <div>
                        <label className="block text-xs font-semibold text-slate-600 mb-1.5">Check In</label>
                        <input type="text" value={form.check_in} onChange={(e) => updateField('check_in', e.target.value)} className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none" />
                    </div>
                    <div>
                        <label className="block text-xs font-semibold text-slate-600 mb-1.5">Check Out</label>
                        <input type="text" value={form.check_out} onChange={(e) => updateField('check_out', e.target.value)} className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none" />
                    </div>
                    <div className="col-span-2">
                        <label className="block text-xs font-semibold text-slate-600 mb-1.5">Description</label>
                        <input type="text" value={form.description} onChange={(e) => updateField('description', e.target.value)} className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none" />
                    </div>
                    <div>
                        <label className="block text-xs font-semibold text-slate-600 mb-1.5">Rate Per Day (₱)</label>
                        <input type="text" value={form.rate_per_day} onChange={(e) => updateField('rate_per_day', e.target.value.replace(/[^\d.]/g, ''))} className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none" />
                    </div>
                    <div>
                        <label className="block text-xs font-semibold text-slate-600 mb-1.5">Days</label>
                        <input type="text" value={form.days} onChange={(e) => updateField('days', e.target.value.replace(/\D/g, ''))} className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none" />
                    </div>
                    <div>
                        <label className="block text-xs font-semibold text-slate-600 mb-1.5">Amount Paid (₱)</label>
                        <input type="text" value={form.amount_paid} onChange={(e) => updateField('amount_paid', e.target.value.replace(/[^\d.]/g, ''))} className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none" />
                    </div>
                    <div>
                        <label className="block text-xs font-semibold text-slate-600 mb-1.5">Due Date</label>
                        <input type="text" value={form.due_date} onChange={(e) => updateField('due_date', e.target.value)} className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none" />
                    </div>
                    <div>
                        <label className="block text-xs font-semibold text-slate-600 mb-1.5">Payment Method</label>
                        <select value={form.payment_method} onChange={(e) => updateField('payment_method', e.target.value)} className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none bg-white">
                            <option>Not Set</option>
                            <option>GCash</option>
                            <option>Over The Counter</option>
                            <option>Bank Transfer</option>
                        </select>
                    </div>
                    <div>
                        <label className="block text-xs font-semibold text-slate-600 mb-1.5">Status</label>
                        <select value={form.status} onChange={(e) => updateField('status', e.target.value)} className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none bg-white">
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
                <button onClick={onClose} className="text-slate-600 text-[13px] font-semibold px-4 py-2">Cancel</button>
                <button
                    onClick={handleSubmit}
                    disabled={saving}
                    className="bg-[#02122b] text-white px-5 py-2.5 rounded-lg text-[13px] font-semibold disabled:opacity-50"
                >
                    {saving ? 'Saving…' : 'Save Changes'}
                </button>
            </div>
        </Modal>
    );
}