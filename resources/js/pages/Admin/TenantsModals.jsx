// resources/js/pages/Admin/TenantsModals.jsx
import React, { useState, useEffect } from 'react';
import Modal from '../../components/Modal';

export default function TenantsModals() {
    const [showView, setShowView] = useState(false);
    const [showEdit, setShowEdit] = useState(false);

    useEffect(() => {
        const handleClick = (e) => {
            if (e.target.closest('.view-tenant-btn')) return setShowView(true);
            if (e.target.closest('.edit-tenant-btn')) return setShowEdit(true);
        };
        document.addEventListener('click', handleClick);
        return () => document.removeEventListener('click', handleClick);
    }, []);

    return (
        <>
            {showView && (
                <Modal onClose={() => setShowView(false)} maxWidth="max-w-[540px]">
                    <div className="p-5 pb-4 flex items-center justify-between border-b border-slate-100">
                        <h3 className="text-lg font-bold text-slate-900">Tenant Details</h3>
                        <button onClick={() => setShowView(false)} className="text-slate-400 hover:text-slate-700 text-lg">
                            <i className="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div className="p-6 overflow-y-auto flex flex-col gap-4">
                        <div className="bg-slate-50 rounded-xl p-4 flex items-center justify-between">
                            <div className="flex items-center gap-3.5">
                                <div className="w-12 h-12 rounded-full bg-green-100 text-green-600 flex items-center justify-center text-lg font-bold">N</div>
                                <div>
                                    <h4 className="text-[15px] font-bold text-slate-900">Nicole Custorio</h4>
                                    <p className="text-xs text-slate-500 mt-0.5 flex items-center gap-1.5">
                                        <i className="fa-regular fa-envelope"></i> nicole@gmail.com
                                    </p>
                                    <p className="text-xs text-slate-500 mt-0.5 flex items-center gap-1.5">
                                        <i className="fa-solid fa-phone"></i> 09171234567
                                    </p>
                                </div>
                            </div>
                            <span className="inline-block px-2.5 py-1 rounded-xl text-[11px] font-bold bg-green-100 text-green-600">
                                Active
                            </span>
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="bg-slate-50 rounded-xl p-3.5">
                                <div className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Valid ID Type</div>
                                <div className="text-[13px] font-bold text-slate-900 mt-1">PhilSys / National ID</div>
                            </div>
                            <div className="bg-slate-50 rounded-xl p-3.5">
                                <div className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Uploaded Date</div>
                                <div className="text-[13px] font-bold text-slate-900 mt-1">Mar 19, 2026</div>
                            </div>
                        </div>

                        <div className="text-[13px] font-bold text-slate-900">Valid ID</div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="flex flex-col gap-1.5">
                                <div className="text-[10px] font-bold text-slate-500 flex items-center gap-1.5">
                                    <i className="fa-regular fa-address-card"></i> FRONT SIDE
                                </div>
                                <div className="border border-slate-200 rounded-xl overflow-hidden bg-white h-[120px]">
                                    <img src="https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?auto=format&fit=crop&q=80&w=300" alt="Front" className="w-full h-full object-cover" />
                                </div>
                            </div>
                            <div className="flex flex-col gap-1.5">
                                <div className="text-[10px] font-bold text-slate-500 flex items-center gap-1.5">
                                    <i className="fa-regular fa-address-card"></i> BACK SIDE
                                </div>
                                <div className="border border-slate-200 rounded-xl overflow-hidden bg-white h-[120px]">
                                    <img src="https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?auto=format&fit=crop&q=80&w=300" alt="Back" className="w-full h-full object-cover" />
                                </div>
                            </div>
                        </div>

                        <p className="text-center text-[11px] text-slate-400">Click an image to view full size</p>
                    </div>
                </Modal>
            )}

            {showEdit && (
                <Modal onClose={() => setShowEdit(false)} maxWidth="max-w-[540px]">
                    <div className="p-5 pb-4 flex items-center justify-between border-b border-slate-100">
                        <h3 className="text-lg font-bold text-slate-900">Edit Tenant</h3>
                        <button onClick={() => setShowEdit(false)} className="text-slate-400 hover:text-slate-700 text-lg">
                            <i className="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div className="p-6 overflow-y-auto flex flex-col gap-4">
                        <div>
                            <label className="block text-xs font-semibold text-slate-700 mb-1.5">Tenant Full Name</label>
                            <input type="text" defaultValue="Nicole Custorio" className="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-[13px] outline-none" />
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <label className="block text-xs font-semibold text-slate-700 mb-1.5">Email Address</label>
                                <input type="email" defaultValue="nicole@example.com" className="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-[13px] outline-none" />
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-slate-700 mb-1.5">Mobile Number</label>
                                <input type="text" defaultValue="09211234567" className="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-[13px] outline-none" />
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <label className="block text-xs font-semibold text-slate-700 mb-1.5">Move-In Date</label>
                                <input type="text" defaultValue="03/20/2026" className="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-[13px] outline-none" />
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-slate-700 mb-1.5">Time-In</label>
                                <input type="text" defaultValue="7:00 AM" className="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-[13px] outline-none" />
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <label className="block text-xs font-semibold text-slate-700 mb-1.5">Move-Out Date</label>
                                <input type="text" defaultValue="03/22/2026" className="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-[13px] outline-none" />
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-slate-700 mb-1.5">Time-Out</label>
                                <input type="text" defaultValue="7:00 AM" className="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-[13px] outline-none" />
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <label className="block text-xs font-semibold text-slate-700 mb-1.5">Rent Rate (₱)</label>
                                <input type="text" defaultValue="1,900" className="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-[13px] outline-none" />
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-slate-700 mb-1.5">Status</label>
                                <select className="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-[13px] outline-none bg-white">
                                    <option>Active</option>
                                    <option>Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div className="p-5 border-t border-slate-100 flex justify-end gap-3">
                        <button onClick={() => setShowEdit(false)} className="text-slate-600 text-[13px] font-semibold px-4 py-2">Cancel</button>
                        <button onClick={() => setShowEdit(false)} className="bg-[#02122c] text-white px-5 py-2.5 rounded-lg text-[13px] font-semibold">
                            Save Changes
                        </button>
                    </div>
                </Modal>
            )}
        </>
    );
}