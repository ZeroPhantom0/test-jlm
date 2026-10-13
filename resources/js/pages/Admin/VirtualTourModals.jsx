// resources/js/pages/Admin/VirtualTourModals.jsx
import React, { useState, useEffect } from 'react';
import Modal from '../../components/Modal';

export default function VirtualTourModals() {
    const [showAdd, setShowAdd] = useState(false);

    useEffect(() => {
        const handler = (e) => {
            if (e.target.closest('#admin-open-add-tour')) setShowAdd(true);
        };
        document.addEventListener('click', handler);
        return () => document.removeEventListener('click', handler);
    }, []);

    return (
        <>
            {showAdd && (
                <Modal onClose={() => setShowAdd(false)} maxWidth="max-w-[820px]">
                    <div className="p-6 pb-4 flex items-center justify-between border-b border-slate-100">
                        <h3 className="text-lg font-bold text-slate-900">Add New Virtual Tour</h3>
                        <button onClick={() => setShowAdd(false)} className="text-slate-400 hover:text-slate-700 text-lg">
                            <i className="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div className="p-6 overflow-y-auto">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div className="flex flex-col gap-4">
                                <div>
                                    <label className="block text-xs font-semibold text-slate-600 mb-1.5">Select Room *</label>
                                    <select className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none bg-white">
                                        <option>— Choose a room —</option>
                                        <option>Solo Room – Standard</option>
                                        <option>Solo Room – Deluxe</option>
                                    </select>
                                    <p className="text-[11px] text-slate-400 mt-1">Choose the room for this virtual tour</p>
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold text-slate-600 mb-1.5">Tour Name *</label>
                                    <input type="text" placeholder="e.g. Standard Solo Room" className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none" />
                                    <div className="flex justify-between text-[11px] text-slate-400 mt-1">
                                        <span>Enter a descriptive title for the virtual tour</span>
                                        <span>29/50</span>
                                    </div>
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold text-slate-600 mb-1.5">Upload 360° Image *</label>
                                    <div className="border-[1.5px] border-dashed border-slate-300 rounded-xl p-6 text-center cursor-pointer hover:border-slate-400 transition">
                                        <i className="fa-solid fa-cloud-arrow-up text-slate-400 text-2xl mb-2"></i>
                                        <p className="text-[13px] font-semibold text-slate-700">Click to upload 360° image</p>
                                        <p className="text-[11px] text-slate-400 mt-1">PNG, JPG or WebP supported</p>
                                    </div>
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold text-slate-600 mb-1.5">Publication Status</label>
                                    <div className="flex bg-slate-50 border border-slate-200 rounded-[10px] p-1 gap-1">
                                        <button type="button" className="flex-1 bg-[#f97316] text-white py-2 rounded-lg text-xs font-semibold inline-flex items-center justify-center gap-1.5">
                                            <i className="fa-regular fa-circle-question"></i> Draft
                                        </button>
                                        <button type="button" className="flex-1 text-slate-500 py-2 rounded-lg text-xs font-semibold inline-flex items-center justify-center gap-1.5 hover:bg-white">
                                            <i className="fa-regular fa-circle-check"></i> Published
                                        </button>
                                    </div>
                                    <p className="text-[11px] text-slate-400 mt-1.5">Draft tours are only visible to administrators.</p>
                                </div>
                            </div>

                            <div className="flex flex-col gap-4">
                                <div className="flex items-center justify-between">
                                    <span className="text-xs font-semibold text-slate-600">
                                        Image Preview <span className="bg-green-100 text-green-600 text-[10px] font-bold px-1.5 py-0.5 rounded ml-1">● Ready</span>
                                    </span>
                                    <button className="text-rose-500 text-xs font-semibold inline-flex items-center gap-1.5">
                                        <i className="fa-regular fa-trash-can"></i> Remove
                                    </button>
                                </div>
                                <div className="rounded-xl overflow-hidden bg-black h-[250px]">
                                    <img src="https://images.unsplash.com/photo-1598928506311-c55ded91a20c?auto=format&fit=crop&q=80&w=600" alt="Room 360 Preview" className="w-full h-full object-cover" />
                                </div>
                                <div className="flex items-center gap-2 text-[11px] text-slate-500">
                                    <i className="fa-regular fa-file-image text-blue-500"></i>
                                    <span>solo_room.jpg • 2.4mb • <span className="text-emerald-500 font-semibold">Uploaded successfully</span></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="p-6 border-t border-slate-100 flex justify-end gap-3">
                        <button onClick={() => setShowAdd(false)} className="text-slate-600 text-[13.5px] font-semibold px-4 py-2">Cancel</button>
                        <button onClick={() => setShowAdd(false)} className="bg-[#0b1930] text-white px-5 py-2.5 rounded-[10px] text-[13.5px] font-semibold">
                            Save Tour
                        </button>
                    </div>
                </Modal>
            )}
        </>
    );
}