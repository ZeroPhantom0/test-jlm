// resources/js/pages/Dashboard/components/Toast.jsx
// Tiny toast bus: call toast("text") from anywhere; <ToastHost/> renders them.
import React, { useEffect, useState } from "react";
import { createPortal } from "react-dom";

const EVENT = "dash:toast";

export function toast(text, { icon = "fa-solid fa-bolt text-[#E8710A]", tone = "default", duration = 5000 } = {}) {
    window.dispatchEvent(new CustomEvent(EVENT, { detail: { text, icon, tone, duration } }));
}

function ToastHostInner() {
    const [toasts, setToasts] = useState([]);

    useEffect(() => {
        const timers = new Set();
        const onToast = (e) => {
            const id = Math.random().toString(36).slice(2);
            setToasts((t) => [...t.slice(-2), { id, ...e.detail }]);
            const timer = setTimeout(() => setToasts((t) => t.filter((x) => x.id !== id)), e.detail.duration);
            timers.add(timer);
        };
        window.addEventListener(EVENT, onToast);
        return () => {
            window.removeEventListener(EVENT, onToast);
            timers.forEach(clearTimeout);
        };
    }, []);

    const dismiss = (id) => setToasts((t) => t.filter((x) => x.id !== id));

    return (
        <div className="fixed top-20 right-4 z-[1500] flex flex-col gap-2 w-[min(360px,calc(100vw-2rem))] pointer-events-none" aria-live="polite" role="status">
            {toasts.map((t) => (
                <div key={t.id}
                    className={`rb-toast pointer-events-auto border shadow-lg rounded-2xl px-4 py-3 flex items-start gap-3 ${
                        t.tone === "success" ? "bg-emerald-50 border-emerald-200" : "bg-white border-slate-200"}`}>
                    <i className={`${t.icon} mt-0.5`} aria-hidden="true" />
                    <p className="flex-1 text-[13px] text-slate-800 font-medium">{t.text}</p>
                    <button type="button" onClick={() => dismiss(t.id)} aria-label="Dismiss notification"
                        className="w-8 h-8 -m-1 rounded-full text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-300">
                        <i className="fa-solid fa-xmark" aria-hidden="true" />
                    </button>
                </div>
            ))}
        </div>
    );
}

/** Rendered into <body> so toasts sit above everything (see RoomDetailDrawer). */
export default function ToastHost() {
    return createPortal(<ToastHostInner />, document.body);
}
