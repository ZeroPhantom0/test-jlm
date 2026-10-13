// resources/js/pages/Admin/AnnouncementsModals.jsx
import React, { useState, useEffect } from 'react';
import Modal from '../../components/Modal';

export default function AnnouncementsModals() {
    const [showNew, setShowNew] = useState(false);

    useEffect(() => {
        const handler = (e) => {
            if (e.target.closest('#admin-open-new-ann')) setShowNew(true);
        };
        document.addEventListener('click', handler);
        return () => document.removeEventListener('click', handler);
    }, []);

    return (
        <>
            {showNew && (
                <Modal onClose={() => setShowNew(false)} maxWidth="max-w-[520px]">
                    <div className="p-5 pb-4 flex items-center justify-between border-b border-slate-100">
                        <h3 className="text-lg font-bold text-slate-900">New Announcement</h3>
                        <button onClick={() => setShowNew(false)} className="text-slate-400 hover:text-slate-700 text-lg">
                            <i className="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div className="p-6 overflow-y-auto flex flex-col gap-4">
                        <div>
                            <label className="block text-[13.5px] font-semibold text-slate-700 mb-1.5">Title</label>
                            <input type="text" placeholder="Announcement title..." className="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 text-[13.5px] outline-none focus:border-slate-400" />
                        </div>

                        <div>
                            <label className="block text-[13.5px] font-semibold text-slate-700 mb-1.5">Message Content</label>
                            <textarea placeholder="Write your announcement details here..." className="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 text-[13.5px] outline-none resize-y min-h-[110px] focus:border-slate-400"></textarea>
                        </div>

                        <div>
                            <label className="block text-[13.5px] font-semibold text-slate-700 mb-1.5">Priority</label>
                            <select className="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 text-[13.5px] outline-none bg-white">
                                <option>Normal</option>
                                <option>Important</option>
                                <option>Urgent</option>
                            </select>
                        </div>

                        <label className="flex items-center gap-2.5 cursor-pointer select-none mt-1">
                            <input type="checkbox" className="w-5 h-5 accent-[#0b1930]" />
                            <span className="text-[13.5px] font-medium text-slate-700">Pin this announcement to top</span>
                        </label>
                    </div>

                    <div className="p-5 border-t border-slate-100 flex justify-end gap-3">
                        <button onClick={() => setShowNew(false)} className="text-slate-600 text-[13.5px] font-semibold px-4 py-2">Cancel</button>
                        <button onClick={() => setShowNew(false)} className="bg-[#0b1930] text-white px-5 py-2.5 rounded-[10px] text-[13.5px] font-semibold">
                            Post Announcement
                        </button>
                    </div>
                </Modal>
            )}
        </>
    );
}