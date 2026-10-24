// resources/js/pages/Admin/MessagesModals.jsx
import React, { useState, useEffect } from 'react';
import Modal from '../../components/Modal';

const csrf = () =>
    document.querySelector('meta[name="csrf-token"]')?.content ||
    document.querySelector('input[name="_token"]')?.value || '';

export default function MessagesModals() {
    const [showDeleteConv, setShowDeleteConv] = useState(false);
    const [showDeleteMsg, setShowDeleteMsg]   = useState(false);
    
    const [convTarget, setConvTarget] = useState(null);
    const [msgTarget, setMsgTarget]   = useState(null);
    const [saving, setSaving]         = useState(false);

    // Listen for custom events triggered from the Blade script
    useEffect(() => {
        const handleOpenConv = (e) => {
            setConvTarget(e.detail);
            setShowDeleteConv(true);
        };
        
        const handleOpenMsg = (e) => {
            setMsgTarget(e.detail);
            setShowDeleteMsg(true);
        };

        document.addEventListener('open-delete-conv-modal', handleOpenConv);
        document.addEventListener('open-delete-msg-modal', handleOpenMsg);

        return () => {
            document.removeEventListener('open-delete-conv-modal', handleOpenConv);
            document.removeEventListener('open-delete-msg-modal', handleOpenMsg);
        };
    }, []);

    const closeConvModal = () => {
        if (saving) return;
        setShowDeleteConv(false);
        setConvTarget(null);
    };

    const closeMsgModal = () => {
        if (saving) return;
        setShowDeleteMsg(false);
        setMsgTarget(null);
    };

    const confirmDeleteConv = async () => {
        if (!convTarget) return;
        setSaving(true);
        try {
            const res = await fetch(`/admin/messages/conversation/${convTarget.id}`, {
                method: 'DELETE',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Failed to delete conversation.');

            // Notify the Blade script to remove the item from the UI
            document.dispatchEvent(new CustomEvent('conv-deleted', { detail: convTarget }));
            
            setShowDeleteConv(false);
            setConvTarget(null);
        } catch (err) {
            alert(err.message);
        } finally {
            setSaving(false);
        }
    };

    const confirmDeleteMsg = async () => {
        if (!msgTarget) return;
        setSaving(true);
        try {
            const res = await fetch(`/admin/messages/${msgTarget.id}`, {
                method: 'DELETE',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Failed to delete message.');

            // Notify the Blade script to re-render the messages
            document.dispatchEvent(new CustomEvent('msg-deleted', { detail: msgTarget }));
            
            setShowDeleteMsg(false);
            setMsgTarget(null);
        } catch (err) {
            alert(err.message);
        } finally {
            setSaving(false);
        }
    };

    return (
        <>
            {/* Delete Conversation Modal */}
            {showDeleteConv && convTarget && (
                <Modal onClose={closeConvModal} maxWidth="max-w-[460px]">
                    <div className="p-5 pb-4 flex items-center justify-between border-b border-slate-100 shrink-0">
                        <h3 className="text-lg font-bold text-slate-900">Delete Conversation</h3>
                        <button onClick={closeConvModal} disabled={saving} className="text-slate-400 hover:text-slate-700 text-lg disabled:opacity-50">
                            <i className="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div className="p-6 text-center overflow-y-auto max-h-[60vh]">
                        <div className="w-16 h-16 mx-auto rounded-full bg-rose-50 flex items-center justify-center mb-4">
                            <i className="fa-solid fa-trash-can text-rose-500 text-2xl"></i>
                        </div>
                        <h4 className="text-lg font-bold text-slate-900 mb-2">Are you sure?</h4>
                        <p className="text-slate-500 text-[13px] mb-4">
                            You are about to permanently delete this entire conversation with <strong>{convTarget.tenant}</strong>.
                        </p>
                        <div className="bg-amber-50 border border-amber-200 rounded-xl p-3 text-left">
                            <p className="text-[12px] text-amber-800 leading-snug">
                                <i className="fa-solid fa-circle-info mr-1"></i>
                                This will remove the conversation from your inbox. The tenant will still see it unless they also delete it.{' '}
                                <strong>This action cannot be undone.</strong>
                            </p>
                        </div>
                    </div>

                    <div className="p-5 border-t border-slate-100 flex justify-end gap-3 shrink-0">
                        <button onClick={closeConvModal} disabled={saving} className="text-slate-600 text-[13.5px] font-semibold px-4 py-2 disabled:opacity-50">
                            Keep Conversation
                        </button>
                        <button onClick={confirmDeleteConv} disabled={saving} className="bg-rose-500 hover:bg-rose-600 disabled:opacity-50 text-white px-5 py-2.5 rounded-lg text-[13.5px] font-semibold inline-flex items-center gap-2 transition">
                            {saving ? <><i className="fa-solid fa-spinner fa-spin"></i> Deleting…</> : <><i className="fa-solid fa-trash-can"></i> Yes, Delete</>}
                        </button>
                    </div>
                </Modal>
            )}

            {/* Delete Message Modal */}
            {showDeleteMsg && msgTarget && (
                <Modal onClose={closeMsgModal} maxWidth="max-w-[460px]">
                    <div className="p-5 pb-4 flex items-center justify-between border-b border-slate-100 shrink-0">
                        <h3 className="text-lg font-bold text-slate-900">Delete Message</h3>
                        <button onClick={closeMsgModal} disabled={saving} className="text-slate-400 hover:text-slate-700 text-lg disabled:opacity-50">
                            <i className="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div className="p-6 text-center overflow-y-auto max-h-[60vh]">
                        <div className="w-16 h-16 mx-auto rounded-full bg-rose-50 flex items-center justify-center mb-4">
                            <i className="fa-solid fa-trash-can text-rose-500 text-2xl"></i>
                        </div>
                        <h4 className="text-lg font-bold text-slate-900 mb-2">Delete this message?</h4>
                        <p className="text-slate-500 text-[13px] mb-4">
                            This will remove the message from your view only.
                        </p>
                        <div className="bg-amber-50 border border-amber-200 rounded-xl p-3 text-left">
                            <p className="text-[12px] text-amber-800 leading-snug">
                                <i className="fa-solid fa-circle-info mr-1"></i>
                                The tenant will still see it unless they also delete it.{' '}
                                <strong>This action cannot be undone.</strong>
                            </p>
                        </div>
                    </div>

                    <div className="p-5 border-t border-slate-100 flex justify-end gap-3 shrink-0">
                        <button onClick={closeMsgModal} disabled={saving} className="text-slate-600 text-[13.5px] font-semibold px-4 py-2 disabled:opacity-50">
                            Cancel
                        </button>
                        <button onClick={confirmDeleteMsg} disabled={saving} className="bg-rose-500 hover:bg-rose-600 disabled:opacity-50 text-white px-5 py-2.5 rounded-lg text-[13.5px] font-semibold inline-flex items-center gap-2 transition">
                            {saving ? <><i className="fa-solid fa-spinner fa-spin"></i> Deleting…</> : <><i className="fa-solid fa-trash-can"></i> Yes, Delete</>}
                        </button>
                    </div>
                </Modal>
            )}
        </>
    );
}