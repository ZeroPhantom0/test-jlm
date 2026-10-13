// resources/js/components/Modal.jsx
import React, { useEffect } from 'react';
import { createPortal } from 'react-dom';

export default function Modal({ onClose, children, maxWidth = 'max-w-[540px]' }) {
    // Lock body scroll while open
    useEffect(() => {
        const original = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        return () => {
            document.body.style.overflow = original;
        };
    }, []);

    // Close on Escape key
    useEffect(() => {
        const handler = (e) => {
            if (e.key === 'Escape') onClose();
        };
        document.addEventListener('keydown', handler);
        return () => document.removeEventListener('keydown', handler);
    }, [onClose]);

    return createPortal(
        <div
            className="fixed inset-0 bg-slate-900/55 backdrop-blur-sm z-[3000] flex items-center justify-center p-4"
            onClick={(e) => e.target === e.currentTarget && onClose()}
        >
            <div className={`bg-white rounded-2xl w-full ${maxWidth} max-h-[90vh] overflow-hidden shadow-2xl flex flex-col`}>
                {children}
            </div>
        </div>,
        document.body
    );
}