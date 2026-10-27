// resources/js/components/LogoutModal.jsx
import React, { useState, useEffect } from 'react';
import Modal from './Modal';
import { confirmLogout } from '../utils/logout';

export default function LogoutModal() {
    const [isOpen, setIsOpen] = useState(false);

    // Listen for a custom event to open the modal
    useEffect(() => {
        const handleOpen = () => setIsOpen(true);
        window.addEventListener('open-logout-modal', handleOpen);
        return () => window.removeEventListener('open-logout-modal', handleOpen);
    }, []);

    const handleClose = () => setIsOpen(false);

    const handleConfirm = () => {
        confirmLogout();
    };

    if (!isOpen) return null;

    return (
        <Modal onClose={handleClose} maxWidth="max-w-[420px]">
            <div className="p-5 flex justify-end pb-0">
                <button
                    onClick={handleClose}
                    className="text-slate-400 hover:text-slate-600 transition"
                >
                    <i className="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>

            <div className="px-8 pb-8 pt-2 flex flex-col items-center text-center">
                {/* Red Icon Box */}
                <div className="w-[52px] h-[52px] rounded-[14px] bg-rose-50 flex items-center justify-center text-rose-500 mb-4">
                    <i className="fa-solid fa-arrow-right-from-bracket text-2xl"></i>
                </div>

                {/* Title */}
                <h3 className="text-[20px] font-bold text-slate-900 mb-2">Logout</h3>

                {/* Message */}
                <p className="text-[14px] text-slate-500 font-medium">
                    Are you sure want to logout?
                </p>
            </div>

            {/* Buttons */}
            <div className="px-6 pb-6 flex gap-3">
                <button
                    onClick={handleConfirm}
                    className="flex-1 bg-[#02122b] hover:bg-[#0a1c3a] text-white py-3 rounded-xl text-[14px] font-bold transition"
                >
                    Yes, Logout
                </button>
                <button
                    onClick={handleClose}
                    className="flex-1 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 py-3 rounded-xl text-[14px] font-bold transition"
                >
                    Cancel
                </button>
            </div>
        </Modal>
    );
}