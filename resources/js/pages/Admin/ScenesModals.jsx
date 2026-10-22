import React, { useState, useEffect } from 'react';
import Modal from '../../components/Modal';

const csrf = () => 
    document.querySelector('#admin-scenes-modals')?.dataset?.csrf || 
    document.querySelector('meta[name="csrf-token"]')?.content || '';

export default function ScenesModals() {
    const [modal, setModal] = useState(null); // 'edit' | 'delete' | null
    const [scene, setScene] = useState(null);
    const [saving, setSaving] = useState(false);

    // Edit form state
    const [form, setForm] = useState({
        title: '',
        description: '',
        default_yaw: 0,
        default_pitch: 0,
        auto_audio_text: '',
        scene_image: null,
    });

    useEffect(() => {
        const handleClick = (e) => {
            // Open Edit
            const editBtn = e.target.closest('.open-edit-scene-btn');
            if (editBtn) {
                // Read from discrete data attributes (bypasses JSON parse issues)
                const data = {
                    id: editBtn.dataset.id,
                    title: editBtn.dataset.title || '',
                    description: editBtn.dataset.description || '',
                    default_yaw: parseFloat(editBtn.dataset.yaw) || 0,
                    default_pitch: parseFloat(editBtn.dataset.pitch) || 0,
                    auto_audio_text: editBtn.dataset.audio || '',
                    image_url: editBtn.dataset.imageUrl || '',
                };

                setScene(data);
                setForm({
                    title: data.title,
                    description: data.description,
                    default_yaw: data.default_yaw,
                    default_pitch: data.default_pitch,
                    auto_audio_text: data.auto_audio_text,
                    scene_image: null,
                });
                setModal('edit');
                return;
            }

            // Open Delete
            const delBtn = e.target.closest('.open-delete-scene-btn');
            if (delBtn) {
                setScene({
                    id: delBtn.dataset.sceneId,
                    title: delBtn.dataset.sceneTitle,
                });
                setModal('delete');
                return;
            }
        };

        document.addEventListener('click', handleClick);
        return () => document.removeEventListener('click', handleClick);
    }, []);

    // ============================================================
    // Edit / Save
    // ============================================================
    const submitEdit = async () => {
        setSaving(true);
        const fd = new FormData();
        fd.append('_method', 'PUT');
        fd.append('_token', csrf());
        fd.append('title', form.title);
        fd.append('description', form.description || '');
        fd.append('default_yaw', form.default_yaw);
        fd.append('default_pitch', form.default_pitch);
        fd.append('auto_audio_text', form.auto_audio_text || '');
        if (form.scene_image) {
            fd.append('scene_image', form.scene_image);
        }

        try {
            const res = await fetch(`${window.SCENE_UPDATE_BASE}/${scene.id}`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                },
                body: fd,
            });

            if (!res.ok) {
                const data = await res.json().catch(() => ({}));
                throw new Error(data.message || 'Failed to update scene.');
            }
            window.location.reload();
        } catch (err) {
            alert(err.message);
            setSaving(false);
        }
    };

    // ============================================================
    // Delete
    // ============================================================
    const confirmDelete = async () => {
        setSaving(true);
        const fd = new FormData();
        fd.append('_method', 'DELETE');
        fd.append('_token', csrf());

        try {
            const res = await fetch(`${window.SCENE_DESTROY_BASE}/${scene.id}`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                },
                body: fd,
            });

            if (!res.ok) {
                const data = await res.json().catch(() => ({}));
                throw new Error(data.message || 'Failed to delete scene.');
            }
            window.location.reload();
        } catch (err) {
            alert(err.message);
            setSaving(false);
        }
    };

    const closeModal = () => {
        if (saving) return;
        setModal(null);
        setScene(null);
    };

    if (!modal) return null;

    // ============================================================
    // EDIT MODAL
    // ============================================================
    if (modal === 'edit') {
        return (
            <Modal onClose={closeModal} maxWidth="max-w-[720px]">
                {/* Fixed Header */}
                <div className="p-5 pb-4 flex items-center justify-between border-b border-slate-100 shrink-0">
                    <h3 className="text-lg font-bold text-slate-900">Edit Scene</h3>
                    <button onClick={closeModal} className="text-slate-400 hover:text-slate-700 text-lg">
                        <i className="fa-solid fa-xmark"></i>
                    </button>
                </div>

                {/* Scrollable Body */}
                <div className="p-6 overflow-y-auto max-h-[65vh]">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {/* Left: Image */}
                        <div className="space-y-4">
                            <div>
                                <label className="block text-[12.5px] font-semibold text-slate-700 mb-1.5">Current Image</label>
                                <div className="rounded-xl overflow-hidden bg-black h-[160px]">
                                    <img src={scene.image_url} alt="Scene" className="w-full h-full object-cover" />
                                </div>
                            </div>
                            <div>
                                <label className="block text-[12.5px] font-semibold text-slate-700 mb-1.5">Replace Image (Optional)</label>
                                <input 
                                    type="file" 
                                    accept="image/*"
                                    onChange={(e) => setForm(f => ({...f, scene_image: e.target.files[0]}))}
                                    className="w-full border border-slate-200 rounded-lg px-3 py-2 text-[13px] file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200" 
                                />
                                <p className="text-[11px] text-slate-400 mt-1">Leave empty to keep the current image.</p>
                            </div>
                        </div>

                        {/* Right: Fields */}
                        <div className="space-y-4">
                            <div>
                                <label className="block text-[12.5px] font-semibold text-slate-700 mb-1.5">Scene Title *</label>
                                <input
                                    type="text"
                                    value={form.title}
                                    onChange={(e) => setForm(f => ({...f, title: e.target.value}))}
                                    className="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-[13px] outline-none focus:border-slate-400"
                                />
                            </div>

                            <div>
                                <label className="block text-[12.5px] font-semibold text-slate-700 mb-1.5">Description</label>
                                <textarea
                                    value={form.description}
                                    onChange={(e) => setForm(f => ({...f, description: e.target.value}))}
                                    rows={2}
                                    className="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-[13px] outline-none focus:border-slate-400 resize-none"
                                />
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="block text-[12.5px] font-semibold text-slate-700 mb-1.5">Default Yaw</label>
                                    <input
                                        type="number"
                                        step="any"
                                        value={form.default_yaw}
                                        onChange={(e) => setForm(f => ({...f, default_yaw: e.target.value}))}
                                        className="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-[13px] outline-none focus:border-slate-400"
                                    />
                                </div>
                                <div>
                                    <label className="block text-[12.5px] font-semibold text-slate-700 mb-1.5">Default Pitch</label>
                                    <input
                                        type="number"
                                        step="any"
                                        value={form.default_pitch}
                                        onChange={(e) => setForm(f => ({...f, default_pitch: e.target.value}))}
                                        className="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-[13px] outline-none focus:border-slate-400"
                                    />
                                </div>
                            </div>

                            <div>
                                <label className="block text-[12.5px] font-semibold text-slate-700 mb-1.5">Auto-Play Audio Text</label>
                                <textarea
                                    value={form.auto_audio_text}
                                    onChange={(e) => setForm(f => ({...f, auto_audio_text: e.target.value}))}
                                    rows={3}
                                    className="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-[13px] outline-none focus:border-slate-400 resize-none"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                {/* Fixed Footer */}
                <div className="p-5 border-t border-slate-100 flex justify-end gap-3 shrink-0 bg-slate-50 rounded-b-2xl">
                    <button onClick={closeModal} disabled={saving} className="text-slate-600 text-[13.5px] font-semibold px-4 py-2 disabled:opacity-50">
                        Cancel
                    </button>
                    <button
                        onClick={submitEdit}
                        disabled={saving}
                        className="bg-[#0b1930] hover:bg-[#162640] disabled:opacity-50 text-white px-5 py-2.5 rounded-[10px] text-[13.5px] font-semibold inline-flex items-center gap-2 transition shadow-sm"
                    >
                        {saving ? <i className="fa-solid fa-spinner fa-spin"></i> : <i className="fa-solid fa-floppy-disk"></i>}
                        {saving ? 'Saving…' : 'Save Changes'}
                    </button>
                </div>
            </Modal>
        );
    }

    // ============================================================
    // DELETE MODAL
    // ============================================================
    if (modal === 'delete') {
        return (
            <Modal onClose={closeModal} maxWidth="max-w-[460px]">
                <div className="p-5 pb-4 flex items-center justify-between border-b border-slate-100 shrink-0">
                    <h3 className="text-lg font-bold text-slate-900">Delete Scene</h3>
                    <button onClick={closeModal} disabled={saving} className="text-slate-400 hover:text-slate-700 text-lg disabled:opacity-50">
                        <i className="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div className="p-6 text-center overflow-y-auto max-h-[60vh]">
                    <div className="w-16 h-16 mx-auto rounded-full bg-rose-50 flex items-center justify-center mb-4">
                        <i className="fa-solid fa-trash-can text-rose-500 text-2xl"></i>
                    </div>
                    <h4 className="text-lg font-bold text-slate-900 mb-2">Are you sure?</h4>
                    <p className="text-slate-500 text-[13px] mb-2">
                        You are about to permanently delete:
                    </p>
                    <p className="font-bold text-slate-900 text-[15px] mb-4">
                        {scene.title}
                    </p>
                    <div className="bg-amber-50 border border-amber-200 rounded-xl p-3 text-left">
                        <p className="text-[12px] text-amber-800 leading-snug">
                            <i className="fa-solid fa-circle-info mr-1"></i>
                            This will also delete all hotspots attached to this scene and remove the uploaded 360° image. <strong>This action cannot be undone.</strong>
                        </p>
                    </div>
                </div>

                <div className="p-5 border-t border-slate-100 flex justify-end gap-3 shrink-0 bg-slate-50 rounded-b-2xl">
                    <button onClick={closeModal} disabled={saving} className="text-slate-600 text-[13.5px] font-semibold px-4 py-2 disabled:opacity-50">
                        Cancel
                    </button>
                    <button
                        onClick={confirmDelete}
                        disabled={saving}
                        className="bg-rose-500 hover:bg-rose-600 disabled:opacity-50 text-white px-5 py-2.5 rounded-lg text-[13.5px] font-semibold inline-flex items-center gap-2 transition"
                    >
                        {saving ? (
                            <>
                                <i className="fa-solid fa-spinner fa-spin"></i>
                                Deleting…
                            </>
                        ) : (
                            <>
                                <i className="fa-solid fa-trash-can"></i>
                                Yes, Delete
                            </>
                        )}
                    </button>
                </div>
            </Modal>
        );
    }

    return null;
}