// resources/js/pages/Admin/RoomsModals.jsx
import React, { useState, useEffect } from 'react';
import Modal from '../../components/Modal';

export default function RoomsModals() {
    const [showAdd, setShowAdd] = useState(false);
    const [showView, setShowView] = useState(false);
    const [showEdit, setShowEdit] = useState(false);

    useEffect(() => {
        const handleClick = (e) => {
            if (e.target.closest('#open-add-room-btn')) return setShowAdd(true);
            if (e.target.closest('.view-room-btn')) return setShowView(true);
            if (e.target.closest('.edit-room-btn')) return setShowEdit(true);
        };
        document.addEventListener('click', handleClick);
        return () => document.removeEventListener('click', handleClick);
    }, []);

    return (
        <>
            {showAdd && (
                <Modal onClose={() => setShowAdd(false)} maxWidth="max-w-[820px]">
                    <div className="p-6 pb-4 flex items-center justify-between border-b border-slate-100">
                        <h3 className="text-lg font-bold text-slate-900">Add New Room</h3>
                        <button onClick={() => setShowAdd(false)} className="text-slate-400 hover:text-slate-700 text-lg">
                            <i className="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div className="p-6 overflow-y-auto">
                        <h4 className="text-sm font-bold text-slate-900 mb-4">Room Information</h4>
                        <div className="grid grid-cols-3 gap-3.5 mb-4">
                            <div>
                                <label className="block text-xs font-semibold text-slate-600 mb-1.5">Room Number</label>
                                <input type="text" placeholder="e.g. 101" className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none focus:border-blue-400" />
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-slate-600 mb-1.5">Floor</label>
                                <select className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none bg-white">
                                    <option>Select Floor</option>
                                    <option>Floor 1</option>
                                    <option>Floor 2</option>
                                    <option>Floor 3</option>
                                </select>
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-slate-600 mb-1.5">Room Type</label>
                                <select className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none bg-white">
                                    <option>Select Room</option>
                                    <option>Solo Room</option>
                                    <option>Bedspace</option>
                                    <option>Barkada Room</option>
                                </select>
                            </div>
                        </div>
                        <div className="mb-4">
                            <label className="block text-xs font-semibold text-slate-600 mb-1.5">Room Description</label>
                            <textarea placeholder="Describe room here.." className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none resize-y min-h-[80px]"></textarea>
                        </div>

                        <h4 className="text-sm font-bold text-slate-900 mb-4">Capacity / Bed Configuration</h4>
                        <div className="grid grid-cols-3 gap-3.5 mb-4">
                            <div>
                                <label className="block text-xs font-semibold text-slate-600 mb-1.5">Total Beds</label>
                                <input type="text" value="=" readOnly className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] bg-slate-50 text-slate-500" />
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-slate-600 mb-1.5">Upper Deck</label>
                                <input type="number" defaultValue="0" className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none" />
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-slate-600 mb-1.5">Lower Deck</label>
                                <input type="number" defaultValue="0" className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none" />
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-slate-600 mb-1.5">Daily Rent (₱)</label>
                                <input type="text" placeholder="Enter Amount" className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none" />
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-slate-600 mb-1.5">Room Status</label>
                                <select className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none bg-white">
                                    <option>Select Status</option>
                                    <option>Available</option>
                                    <option>Occupied</option>
                                    <option>Reserved</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div className="p-6 border-t border-slate-100 flex justify-end gap-3">
                        <button onClick={() => setShowAdd(false)} className="text-slate-600 text-[13.5px] font-semibold px-4 py-2">Cancel</button>
                        <button onClick={() => setShowAdd(false)} className="bg-[#02122c] hover:bg-[#0d2347] text-white px-5 py-2.5 rounded-lg text-[13.5px] font-semibold transition">
                            Create Room
                        </button>
                    </div>
                </Modal>
            )}

            {showView && (
                <Modal onClose={() => setShowView(false)} maxWidth="max-w-[820px]">
                    <div className="p-6 pb-4 flex items-center justify-between border-b border-slate-100">
                        <h3 className="text-lg font-bold text-slate-900">Room 202 – Details</h3>
                        <button onClick={() => setShowView(false)} className="text-slate-400 hover:text-slate-700 text-lg">
                            <i className="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div className="p-6 overflow-y-auto">
                        <div className="flex items-center justify-between mb-3">
                            <div className="flex items-center gap-2">
                                <span className="inline-block px-3 py-1.5 rounded-md text-[10.5px] font-extrabold tracking-wider uppercase bg-blue-100 text-blue-700">Solo Room</span>
                                <span className="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-semibold bg-green-100 text-green-600">
                                    <i className="fa-solid fa-check"></i> Occupied
                                </span>
                            </div>
                            <div>
                                <span className="text-[22px] font-extrabold text-green-600">₱950</span>
                                <small className="text-[11px] text-slate-500 ml-1">per day</small>
                            </div>
                        </div>
                        <p className="text-[11px] text-slate-400 mb-4">Floor 1 • Room 101</p>

                        <div className="bg-slate-50 rounded-lg p-3.5 text-[13px] text-slate-600 leading-snug mb-4">
                            Cozy solo room on the ground floor perfect for single occupants. Includes private bathroom.
                        </div>

                        <h4 className="text-sm font-bold text-slate-900 mb-3">Room Specifications</h4>
                        <div className="grid grid-cols-3 gap-2.5 mb-4">
                            {[
                                { label: 'Room Type', value: 'Solo Room - D' },
                                { label: 'Beds / Position', value: '1' },
                                { label: 'Room / Bed ID', value: 'R202' },
                                { label: 'Floor', value: '1' },
                                { label: 'Available Slots', value: '1/1' },
                                { label: 'Daily Rate', value: '₱950' },
                            ].map((spec, i) => (
                                <div key={i} className="bg-slate-50 rounded-lg p-3 text-center">
                                    <div className="text-[9.5px] font-bold text-slate-400 uppercase mb-1">{spec.label}</div>
                                    <div className="text-[12px] font-bold text-slate-900">{spec.value}</div>
                                </div>
                            ))}
                        </div>

                        <div className="bg-emerald-50 rounded-lg p-3.5 flex items-center gap-3 mb-4">
                            <div className="w-9 h-9 rounded-full bg-emerald-500 text-white flex items-center justify-center font-bold">N</div>
                            <div>
                                <div className="text-[10px] font-extrabold text-emerald-700 tracking-wide uppercase">Current Tenant</div>
                                <h5 className="text-sm font-bold text-emerald-800">Nicole Custorio</h5>
                            </div>
                        </div>
                    </div>
                    <div className="p-6 border-t border-slate-100 flex justify-end gap-3">
                        <button onClick={() => { setShowView(false); setShowEdit(true); }} className="border border-slate-300 text-slate-700 px-4 py-2 rounded-lg text-[13px] font-medium hover:bg-slate-50">
                            Edit Room
                        </button>
                        <button onClick={() => setShowView(false)} className="bg-[#02122c] text-white px-5 py-2.5 rounded-lg text-[13px] font-semibold">
                            Close
                        </button>
                    </div>
                </Modal>
            )}

            {showEdit && (
                <Modal onClose={() => setShowEdit(false)} maxWidth="max-w-[820px]">
                    <div className="p-6 pb-4 flex items-center justify-between border-b border-slate-100">
                        <h3 className="text-lg font-bold text-slate-900">Edit Room 103</h3>
                        <button onClick={() => setShowEdit(false)} className="text-slate-400 hover:text-slate-700 text-lg">
                            <i className="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div className="p-6 overflow-y-auto">
                        <div className="grid grid-cols-3 gap-3.5 mb-4">
                            {[
                                { label: 'Room Number', value: '103' },
                                { label: 'Floor', value: '1' },
                                { label: 'Status', value: 'Available' },
                                { label: 'Room / Bed ID', value: 'R103-B1-L' },
                                { label: 'Beds / Position', value: 'Lower Deck' },
                                { label: 'Max Capacity', value: '8' },
                                { label: 'Daily Rate (₱)', value: '300' },
                            ].map((field, i) => (
                                <div key={i}>
                                    <label className="block text-xs font-semibold text-slate-600 mb-1.5">{field.label}</label>
                                    <input type="text" defaultValue={field.value} className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none" />
                                </div>
                            ))}
                            <div>
                                <label className="block text-xs font-semibold text-slate-600 mb-1.5">Room Type</label>
                                <select className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none bg-white">
                                    <option>Bedspace - P</option>
                                    <option>Solo Room</option>
                                </select>
                            </div>
                            <div className="col-span-3">
                                <label className="block text-xs font-semibold text-slate-600 mb-1.5">Tenant Name</label>
                                <input type="text" defaultValue="-" className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none" />
                            </div>
                        </div>

                        <div>
                            <label className="block text-xs font-semibold text-slate-600 mb-1.5">Description</label>
                            <textarea className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none resize-y min-h-[80px]"></textarea>
                        </div>
                    </div>
                    <div className="p-6 border-t border-slate-100 flex justify-end gap-3">
                        <button onClick={() => setShowEdit(false)} className="text-slate-600 text-[13.5px] font-semibold px-4 py-2">Cancel</button>
                        <button onClick={() => setShowEdit(false)} className="bg-[#02122c] text-white px-5 py-2.5 rounded-lg text-[13.5px] font-semibold">
                            Save Changes
                        </button>
                    </div>
                </Modal>
            )}
        </>
    );
}