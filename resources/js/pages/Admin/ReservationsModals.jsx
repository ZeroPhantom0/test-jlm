// resources/js/pages/Admin/ReservationsModals.jsx
import React, { useState, useEffect } from 'react';
import Modal from '../../components/Modal';

const DATA = {
    maria: {
        avatar: 'M', bg: 'bg-teal-100', color: 'text-teal-600',
        name: 'Maria Santos', subCode: '#RSV-1',
        resNumber: 'RSV-2026-00005',
        email: 'maria@email.com', phone: '09171112222',
        desiredRoom: 'Desired Room: Room 101 — Bedspace',
        bedId: 'Room/Bed ID: R101-B1-U',
        moveIn: 'Move-in: October 10, 2026',
        submitted: 'Submitted: September 29, 2026',
        requestDetails: 'Looking for a quiet unit near the stairs. Prefers bedspace for 7 days.',
        totalCharges: '₱1,000.00', paymentOption: 'Down Payment',
        remaining: '₱500.00', paymentMethod: 'GCash',
        statusClass: 'bg-yellow-100 text-amber-700', statusText: 'Pending', statusIcon: 'fa-regular fa-clock',
    },
    ramon: {
        avatar: 'R', bg: 'bg-sky-100', color: 'text-sky-600',
        name: 'Ramon Bautista', subCode: '#RSV-2',
        resNumber: 'RSV-2026-00005',
        email: 'ramon@email.com', phone: '09182223333',
        desiredRoom: 'Desired Room: Room 104 — Barkada Room',
        bedId: 'Room/Bed ID: R104-B1-L',
        moveIn: 'Move-in: April 8, 2026',
        submitted: 'Submitted: March 9, 2026',
        requestDetails: 'Group of 4 friends starting April. Need barkada room for 1 week.',
        totalCharges: '₱9,000.00', paymentOption: 'Full Payment',
        remaining: '₱0.00', paymentMethod: 'Bank Transfer',
        statusClass: 'bg-green-100 text-green-600', statusText: 'Approved', statusIcon: 'fa-regular fa-circle-check',
    },
    jenny: {
        avatar: 'J', bg: 'bg-sky-100', color: 'text-sky-600',
        name: 'Jenny Villanueva', subCode: '#RSV-3',
        resNumber: 'RSV-2026-00003',
        email: 'jenny@email.com', phone: '09193334444',
        desiredRoom: 'Desired Room: Room 103 — Bedspace',
        bedId: 'Room/Bed ID: R103-B2-U',
        moveIn: 'Move-in: March 28, 2026',
        submitted: 'Submitted: March 15, 2026',
        requestDetails: 'Needs bedspace near window. Student with early class schedule.',
        totalCharges: '₱1,000.00', paymentOption: 'Down Payment',
        remaining: '₱500.00', paymentMethod: 'GCash',
        statusClass: 'bg-rose-100 text-rose-600', statusText: 'Rejected', statusIcon: 'fa-regular fa-circle-xmark',
    },
    lito: {
        avatar: 'L', bg: 'bg-green-100', color: 'text-green-600',
        name: 'Lito Cruz', subCode: '#RSV-4',
        resNumber: 'RSV-2026-00002',
        email: 'lito@email.com', phone: '09204445555',
        desiredRoom: 'Desired Room: Room 202 — Solo Room',
        bedId: 'Room/Bed ID: R202-S1',
        moveIn: 'Move-in: March 22, 2026',
        submitted: 'Submitted: March 10, 2026',
        requestDetails: 'Requires solo room for work setup.',
        totalCharges: '₱5,000.00', paymentOption: 'Full Payment',
        remaining: '₱0.00', paymentMethod: 'Bank Transfer',
        statusClass: 'bg-slate-100 text-slate-500', statusText: 'Expired', statusIcon: 'fa-regular fa-clock',
    },
    sofia: {
        avatar: 'S', bg: 'bg-teal-100', color: 'text-teal-600',
        name: 'Sofia Reyes', subCode: '#RSV-5',
        resNumber: 'RSV-2026-00001',
        email: 'sofia@email.com', phone: '09155556666',
        desiredRoom: 'Desired Room: Room 303 — Barkada Room',
        bedId: 'Room/Bed ID: R303-B1-L',
        moveIn: 'Move-in: April 4, 2026',
        submitted: 'Submitted: March 24, 2026',
        requestDetails: 'Visiting for short term stay.',
        totalCharges: '₱1,000.00', paymentOption: 'Down Payment',
        remaining: '₱500.00', paymentMethod: 'GCash',
        statusClass: 'bg-yellow-100 text-amber-700', statusText: 'Pending', statusIcon: 'fa-regular fa-clock',
    },
};

export default function ReservationsModals() {
    const [data, setData] = useState(null);

    useEffect(() => {
        const handler = (e) => {
            const btn = e.target.closest('[data-res-key]');
            if (btn && DATA[btn.dataset.resKey]) {
                setData(DATA[btn.dataset.resKey]);
            }
        };
        document.addEventListener('click', handler);
        return () => document.removeEventListener('click', handler);
    }, []);

    if (!data) return null;

    return (
        <Modal onClose={() => setData(null)} maxWidth="max-w-[520px]">
            <div className="p-5 pb-4 flex items-center justify-between border-b border-slate-100">
                <h2 className="text-lg font-bold text-slate-900">Reservation Details</h2>
                <button onClick={() => setData(null)} className="text-slate-400 hover:text-slate-700 text-lg">
                    <i className="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div className="p-6 overflow-y-auto flex flex-col gap-5">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3.5">
                        <div className={`w-12 h-12 rounded-full ${data.bg} ${data.color} flex items-center justify-center text-lg font-bold`}>
                            {data.avatar}
                        </div>
                        <div>
                            <h3 className="text-base font-bold text-slate-900">{data.name}</h3>
                            <p className="text-xs text-slate-400 mt-0.5">{data.subCode}</p>
                        </div>
                    </div>
                    <span className={`inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold ${data.statusClass}`}>
                        <i className={data.statusIcon}></i> {data.statusText}
                    </span>
                </div>

                <div>
                    <div className="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Reservation Number</div>
                    <div className="text-[13px] font-bold text-teal-600">{data.resNumber}</div>
                </div>

                <div className="bg-slate-50 rounded-xl p-4 flex flex-col gap-2.5">
                    <div className="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Contact Information</div>
                    <div className="flex items-center gap-2.5 text-[13px] text-slate-700">
                        <i className="fa-regular fa-envelope w-4 text-slate-400"></i> {data.email}
                    </div>
                    <div className="flex items-center gap-2.5 text-[13px] text-slate-700">
                        <i className="fa-solid fa-phone w-4 text-slate-400"></i> {data.phone}
                    </div>
                </div>

                <div className="bg-slate-50 rounded-xl p-4 flex flex-col gap-2.5">
                    <div className="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Reservation Details</div>
                    <div className="flex items-center gap-2.5 text-[13px] text-slate-700">
                        <i className="fa-solid fa-house w-4 text-slate-400"></i> {data.desiredRoom}
                    </div>
                    <div className="flex items-center gap-2.5 text-[13px] text-slate-700">
                        <i className="fa-solid fa-bed w-4 text-slate-400"></i> {data.bedId}
                    </div>
                    <div className="flex items-center gap-2.5 text-[13px] text-slate-700">
                        <i className="fa-regular fa-calendar w-4 text-slate-400"></i> {data.moveIn}
                    </div>
                    <div className="flex items-center gap-2.5 text-[13px] text-slate-700">
                        <i className="fa-regular fa-calendar-check w-4 text-slate-400"></i> {data.submitted}
                    </div>
                </div>

                <div className="bg-slate-50 rounded-xl p-4">
                    <div className="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Request Details</div>
                    <p className="text-[13px] text-slate-700 leading-snug">{data.requestDetails}</p>
                </div>

                <div className="grid grid-cols-2 gap-3">
                    <div className="bg-[#02122b] text-white rounded-xl p-3.5">
                        <div className="text-[11px] text-slate-400">Total Charges</div>
                        <div className="text-base font-bold mt-1">{data.totalCharges}</div>
                    </div>
                    <div className="bg-emerald-50 rounded-xl p-3.5">
                        <div className="text-[11px] text-emerald-600 font-semibold">Payment Option</div>
                        <div className="text-base font-bold text-emerald-700 mt-1">{data.paymentOption}</div>
                    </div>
                    <div className="bg-slate-50 rounded-xl p-3.5">
                        <div className="text-[11px] text-slate-500">Remaining</div>
                        <div className="text-base font-bold text-slate-900 mt-1">{data.remaining}</div>
                    </div>
                    <div className="bg-slate-50 rounded-xl p-3.5">
                        <div className="text-[11px] text-slate-500">Payment Method</div>
                        <div className="text-base font-bold text-slate-900 mt-1">{data.paymentMethod}</div>
                    </div>
                </div>

                <div>
                    <div className="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Uploaded Proof of Payment</div>
                    <div className="bg-lime-200 rounded-xl p-4 flex flex-col items-center justify-center">
                        <div className="bg-white p-2.5 rounded-md shadow-sm w-[120px] text-[8px] text-slate-600">
                            <div className="text-[9px] font-extrabold text-center mb-1.5">CASH RECEIPT</div>
                            <p>Receipt No: ______</p>
                            <p>Amount: ₱500.00</p>
                            <p>Paid By: {data.name}</p>
                        </div>
                        <span className="text-[11px] text-slate-600 mt-2">Click to expand</span>
                    </div>
                </div>

                <div>
                    <div className="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Admin Notes</div>
                    <textarea placeholder="Add notes..." className="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 text-[13px] outline-none resize-y min-h-[60px]"></textarea>
                </div>
            </div>

            <div className="p-5 border-t border-slate-100 flex items-center justify-end gap-3">
                <button className="text-rose-600 text-[13px] font-semibold mr-auto">Delete</button>
                <button className="bg-emerald-600 text-white px-4 py-2 rounded-lg text-[13px] font-semibold inline-flex items-center gap-1.5">
                    <i className="fa-regular fa-circle-check"></i> Approve
                </button>
                <button className="bg-rose-50 text-rose-600 px-4 py-2 rounded-lg text-[13px] font-semibold inline-flex items-center gap-1.5">
                    <i className="fa-regular fa-circle-xmark"></i> Reject
                </button>
                <button onClick={() => setData(null)} className="bg-[#02122c] text-white px-4 py-2 rounded-lg text-[13px] font-semibold">
                    Close
                </button>
            </div>
        </Modal>
    );
}