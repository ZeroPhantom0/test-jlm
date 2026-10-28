// resources/js/pages/Admin/VirtualTourModals.jsx
import React, { useState, useEffect } from 'react';
import Modal from '../../components/Modal';

const csrf = () =>
    document.querySelector('meta[name="csrf-token"]')?.content || '';

// Get routes from the global object that Blade passes in
const routes = () => window.__VT_ROUTES__ || {};

export default function VirtualTourModals() {
    // Modals: 'add' | 'edit' | 'view' | 'delete' | null
    const [modal, setModal] = useState(null);
    const [tour, setTour]   = useState(null);        // For edit/view/delete
    const [rooms, setRooms] = useState([]);          // Populated from Blade

    // Form state
    const [form, setForm] = useState(emptyForm());
    const [saving, setSaving] = useState(false);
    const [errors, setErrors] = useState({});

    function emptyForm() {
        return {
            room_id: '',
            title: '',
            description: '',
            status: 'Draft',
            thumbnail: null,
        };
    }

    // -----------------------------------------------------------
    // Read rooms from the DOM (passed by Blade)
    // -----------------------------------------------------------
    useEffect(() => {
        const dataEl = document.getElementById('vt-rooms-data');
        if (dataEl) {
            try {
                setRooms(JSON.parse(dataEl.dataset.rooms || '[]'));
            } catch (e) {
                console.error('Invalid rooms JSON', e);
            }
        }
    }, []);

    // -----------------------------------------------------------
    // Global event delegation for trigger buttons
    // -----------------------------------------------------------
    useEffect(() => {
        const handler = (e) => {
            // Add
            if (e.target.closest('#admin-open-add-tour')) {
                setTour(null);
                setForm(emptyForm());
                setErrors({});
                setModal('add');
                return;
            }

            // Edit
            const editBtn = e.target.closest('.edit-tour-btn');
            if (editBtn) {
                const data = JSON.parse(editBtn.dataset.tour || '{}');
                setTour(data);
                setForm({
                    room_id: data.room_id,
                    title: data.title || '',
                    description: data.description || '',
                    status: data.status || 'Draft',
                    thumbnail: null,
                });
                setErrors({});
                setModal('edit');
                return;
            }

            // View
            const viewBtn = e.target.closest('.view-tour-btn');
            if (viewBtn) {
                const data = JSON.parse(viewBtn.dataset.tour || '{}');
                setTour(data);
                setModal('view');
                return;
            }

            // Delete
            const delBtn = e.target.closest('.delete-tour-btn');
            if (delBtn) {
                setTour({
                    id: delBtn.dataset.tourId,
                    title: delBtn.dataset.tourTitle,
                });
                setModal('delete');
                return;
            }
        };

        document.addEventListener('click', handler);
        return () => document.removeEventListener('click', handler);
    }, []);

    // -----------------------------------------------------------
    // Form field helpers
    // -----------------------------------------------------------
    const updateField = (key, value) =>
        setForm((f) => ({ ...f, [key]: value }));

    // Auto-populate title from room selection
    const onRoomChange = (e) => {
        const value = e.target.value;
        updateField('room_id', value);

        if (!tour && value) {
            const selected = e.target.options[e.target.selectedIndex];
            const roomType = selected.dataset.roomType || '';
            const roomNumber = selected.dataset.roomNumber || '';
            updateField('title', `${roomType} – ${roomNumber}`);
        }
    };

    // -----------------------------------------------------------
    // Submit Add / Edit
    // -----------------------------------------------------------
    const submitForm = async () => {
        setSaving(true);
        setErrors({});

        const fd = new FormData();
        fd.append('room_id',     form.room_id);
        fd.append('title',       form.title);
        fd.append('description', form.description || '');
        fd.append('status',      form.status);
        if (form.thumbnail) fd.append('thumbnail', form.thumbnail);

        // Determine URL & method
        let url, method;
        if (modal === 'edit') {
            url = routes().update.replace(':id', tour.id);
            method = 'PUT';
        } else {
            url = routes().store;
            method = 'POST';
        }

        // Laravel needs _method spoofing for PUT with FormData
        if (method === 'PUT') fd.append('_method', 'PUT');

        try {
            const res = await fetch(url, {
                method: 'POST', // Always POST; _method handles PUT
                headers: {
                    'X-CSRF-TOKEN': csrf(),
                    'Accept': 'application/json',
                },
                body: fd,
            });

            const data = await res.json().catch(() => ({}));

            if (!res.ok) {
                if (res.status === 422) {
                    setErrors(data.errors || {});
                } else {
                    alert(data.message || 'Failed to save tour.');
                }
                return;
            }

            // Success — reload to show new data
            window.location.reload();
        } catch (err) {
            console.error(err);
            alert('Network error. Please try again.');
        } finally {
            setSaving(false);
        }
    };

    // -----------------------------------------------------------
    // Delete tour
    // -----------------------------------------------------------
    const confirmDelete = async () => {
        setSaving(true);
        try {
            const res = await fetch(routes().destroy.replace(':id', tour.id), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf(),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ _method: 'DELETE' }),
            });

            if (!res.ok) {
                alert('Failed to delete tour.');
                return;
            }
            window.location.reload();
        } catch (err) {
            console.error(err);
            alert('Network error.');
        } finally {
            setSaving(false);
        }
    };

    // -----------------------------------------------------------
    // Thumbnail upload handler
    // -----------------------------------------------------------
    const onThumbChange = (e) => {
        const file = e.target.files[0];
        if (file) updateField('thumbnail', file);
    };

    // -----------------------------------------------------------
    // Open VR Tour in same tab
    // -----------------------------------------------------------
    const openVrTour = (e) => {
        e.preventDefault();
        if (!tour || !tour.id) return;
        
        // Build the viewer URL and append the `from` parameter
        // so the tour's back button returns to the admin page
        const baseUrl = routes().viewer.replace(':id', tour.id);
        const fromPath = routes().fromPath || '';
        const finalUrl = fromPath ? `${baseUrl}?from=${encodeURIComponent(fromPath)}` : baseUrl;
        
        window.location.href = finalUrl;
    };

    if (!modal) return null;

    // ============================================================
    // ADD / EDIT MODAL
    // ============================================================
    if (modal === 'add' || modal === 'edit') {
        const isEdit = modal === 'edit';

        return (
            <Modal onClose={() => setModal(null)} maxWidth="max-w-[820px]">
                {/* Fixed Header */}
                <div className="p-6 pb-4 flex items-center justify-between border-b border-slate-100 shrink-0">
                    <h3 className="text-lg font-bold text-slate-900">
                        {isEdit ? 'Edit Virtual Tour' : 'Add New Virtual Tour'}
                    </h3>
                    <button
                        onClick={() => setModal(null)}
                        className="text-slate-400 hover:text-slate-700 text-lg"
                    >
                        <i className="fa-solid fa-xmark"></i>
                    </button>
                </div>

                {/* Scrollable Body */}
                <div className="p-6 overflow-y-auto max-h-[60vh]">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {/* LEFT */}
                        <div className="flex flex-col gap-4">
                            <div>
                                <label className="block text-xs font-semibold text-slate-600 mb-1.5">
                                    Select Room *
                                </label>
                                <select
                                    value={form.room_id}
                                    onChange={onRoomChange}
                                    disabled={isEdit}
                                    className={`w-full border rounded-lg px-3 py-2.5 text-[13px] outline-none bg-white ${
                                        errors.room_id ? 'border-rose-400' : 'border-slate-300'
                                    } ${isEdit ? 'bg-slate-50 text-slate-500 cursor-not-allowed' : ''}`}
                                >
                                    <option value="">— Choose a room —</option>
                                    {rooms.map((r) => (
                                        <option
                                            key={r.id}
                                            value={r.id}
                                            data-room-number={r.room_number}
                                            data-room-type={r.room_type}
                                            disabled={!isEdit && r.has_tour}
                                        >
                                            {r.room_number} - {r.room_type} ({r.status})
                                            {!isEdit && r.has_tour ? ' [Has Tour]' : ''}
                                        </option>
                                    ))}
                                </select>
                                {errors.room_id && (
                                    <p className="text-[11px] text-rose-500 mt-1">{errors.room_id[0]}</p>
                                )}
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-slate-600 mb-1.5">
                                    Tour Title *
                                </label>
                                <input
                                    type="text"
                                    value={form.title}
                                    onChange={(e) => updateField('title', e.target.value)}
                                    placeholder="e.g. Standard Solo Room"
                                    className={`w-full border rounded-lg px-3 py-2.5 text-[13px] outline-none ${
                                        errors.title ? 'border-rose-400' : 'border-slate-300'
                                    }`}
                                />
                                {errors.title && (
                                    <p className="text-[11px] text-rose-500 mt-1">{errors.title[0]}</p>
                                )}
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-slate-600 mb-1.5">
                                    Description
                                </label>
                                <textarea
                                    value={form.description}
                                    onChange={(e) => updateField('description', e.target.value)}
                                    rows={3}
                                    placeholder="Brief details about this virtual tour..."
                                    className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none resize-none"
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-slate-600 mb-1.5">
                                    Publication Status
                                </label>
                                <div className="flex bg-slate-50 border border-slate-200 rounded-[10px] p-1 gap-1">
                                    <button
                                        type="button"
                                        onClick={() => updateField('status', 'Draft')}
                                        className={`flex-1 py-2 rounded-lg text-xs font-semibold inline-flex items-center justify-center gap-1.5 transition ${
                                            form.status === 'Draft'
                                                ? 'bg-amber-100 text-amber-700'
                                                : 'text-slate-500 hover:bg-white'
                                        }`}
                                    >
                                        <i className="fa-regular fa-circle-question"></i> Draft
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => updateField('status', 'Published')}
                                        className={`flex-1 py-2 rounded-lg text-xs font-semibold inline-flex items-center justify-center gap-1.5 transition ${
                                            form.status === 'Published'
                                                ? 'bg-emerald-100 text-emerald-700'
                                                : 'text-slate-500 hover:bg-white'
                                        }`}
                                    >
                                        <i className="fa-regular fa-circle-check"></i> Published
                                    </button>
                                </div>
                            </div>
                        </div>

                        {/* RIGHT: Thumbnail */}
                        <div className="flex flex-col gap-4">
                            <label className="block text-xs font-semibold text-slate-600">
                                Cover Thumbnail {isEdit && <span className="text-slate-400">(optional)</span>}
                            </label>

                            <label
                                htmlFor="vt-thumb-input"
                                className="border-[1.5px] border-dashed border-slate-300 rounded-xl p-6 text-center cursor-pointer hover:border-slate-400 transition block"
                            >
                                <i className="fa-solid fa-cloud-arrow-up text-slate-400 text-2xl mb-2"></i>
                                <p className="text-[13px] font-semibold text-slate-700">
                                    Click to upload {isEdit ? 'new thumbnail' : '360° image'}
                                </p>
                                <p className="text-[11px] text-slate-400 mt-1">
                                    PNG, JPG or WebP supported
                                </p>
                                <input
                                    id="vt-thumb-input"
                                    type="file"
                                    accept="image/*"
                                    onChange={onThumbChange}
                                    className="hidden"
                                />
                            </label>

                            {form.thumbnail && (
                                <div className="rounded-xl overflow-hidden bg-black h-[200px]">
                                    <img
                                        src={URL.createObjectURL(form.thumbnail)}
                                        alt="Preview"
                                        className="w-full h-full object-cover"
                                    />
                                </div>
                            )}

                            {isEdit && tour?.thumbnail_url && !form.thumbnail && (
                                <div>
                                    <p className="text-[11px] text-slate-500 mb-1.5">Current thumbnail:</p>
                                    <div className="rounded-xl overflow-hidden bg-black h-[140px]">
                                        <img
                                            src={tour.thumbnail_url}
                                            alt="Current"
                                            className="w-full h-full object-cover"
                                        />
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </div>

                {/* Fixed Footer */}
                <div className="p-6 border-t border-slate-100 flex justify-end gap-3 shrink-0">
                    <button
                        onClick={() => setModal(null)}
                        className="text-slate-600 text-[13.5px] font-semibold px-4 py-2"
                    >
                        Cancel
                    </button>
                    <button
                        disabled={saving}
                        onClick={submitForm}
                        className="bg-[#0b1930] hover:bg-[#162640] disabled:opacity-50 text-white px-5 py-2.5 rounded-[10px] text-[13.5px] font-semibold inline-flex items-center gap-2"
                    >
                        <i className="fa-solid fa-floppy-disk"></i>
                        {saving ? 'Saving…' : 'Save Tour'}
                    </button>
                </div>
            </Modal>
        );
    }

    // ============================================================
    // VIEW MODAL — Opens VR Tour in same tab
    // ============================================================
    if (modal === 'view' && tour) {
        return (
            <Modal onClose={() => setModal(null)} maxWidth="max-w-[540px]">
                {/* 
                  Wrap the image in an anchor tag. 
                  Clicking it will navigate to the VR tour in the same tab,
                  instead of opening a new window/tab.
                */}
                <a 
                    href="#" 
                    onClick={openVrTour} 
                    className="relative h-[200px] bg-black shrink-0 block cursor-pointer group"
                    title="Click to open full 360° tour"
                >
                    <img
                        src={tour.thumbnail_url || 'https://via.placeholder.com/600x300?text=360+Tour'}
                        alt="Tour"
                        className="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                    />
                    
                    {/* Hover overlay hint */}
                    <div className="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                        <span className="bg-white/90 text-slate-900 text-sm font-bold px-4 py-2 rounded-full inline-flex items-center gap-2">
                            <i className="fa-solid fa-vr-cardboard"></i> Click to view Full VR Tour
                        </span>
                    </div>

                    <span className="absolute top-3 right-3 bg-black/70 text-white text-[11px] font-bold px-3 py-1 rounded-lg backdrop-blur">
                        360° View
                    </span>
                    <div className="absolute bottom-0 left-0 right-0 p-4 bg-gradient-to-t from-black/80 to-transparent">
                        <h3 className="text-white text-lg font-bold">{tour.title}</h3>
                        <div className="flex gap-2 mt-2">
                            <span className="bg-indigo-500 text-white text-[11px] font-bold px-2.5 py-0.5 rounded-lg">
                                {tour.room_number}
                            </span>
                            <span className={`text-white text-[11px] font-bold px-2.5 py-0.5 rounded-lg ${
                                tour.status === 'Published' ? 'bg-emerald-500' :
                                tour.status === 'Draft' ? 'bg-amber-500' : 'bg-slate-500'
                            }`}>
                                {tour.status}
                            </span>
                        </div>
                    </div>
                </a>

                <div className="p-5 overflow-y-auto max-h-[60vh]">
                    <p className="text-[13px] text-slate-500 mb-4">
                        {tour.description || 'No description provided.'}
                    </p>

                    <h4 className="text-[13px] font-bold text-slate-800 mb-3">Tour Specifications</h4>
                    <div className="grid grid-cols-3 gap-3">
                        <div className="bg-slate-50 rounded-xl p-3 text-center">
                            <i className="fa-solid fa-cubes text-slate-400"></i>
                            <p className="text-[10px] font-bold text-slate-400 uppercase mt-1">Scenes</p>
                            <p className="text-sm font-bold text-slate-800">{tour.scenes_count || 0}</p>
                        </div>
                        <div className="bg-slate-50 rounded-xl p-3 text-center">
                            <i className="fa-solid fa-location-dot text-slate-400"></i>
                            <p className="text-[10px] font-bold text-slate-400 uppercase mt-1">Room</p>
                            <p className="text-sm font-bold text-slate-800">{tour.room_number}</p>
                        </div>
                        <div className="bg-slate-50 rounded-xl p-3 text-center">
                            <i className="fa-solid fa-calendar text-slate-400"></i>
                            <p className="text-[10px] font-bold text-slate-400 uppercase mt-1">Created</p>
                            <p className="text-sm font-bold text-slate-800">{tour.created_at_formatted}</p>
                        </div>
                    </div>
                </div>

                <div className="flex items-center justify-end gap-3 px-5 py-4 border-t border-slate-100 bg-slate-50 shrink-0">
                    <button
                        onClick={() => {
                            // Switch to edit with existing data
                            const editBtn = document.querySelector(
                                `.edit-tour-btn[data-tour*='"id":${tour.id}']`
                            );
                            setModal(null);
                            if (editBtn) setTimeout(() => editBtn.click(), 100);
                        }}
                        className="text-[13px] font-semibold text-emerald-600 hover:text-emerald-700"
                    >
                        Edit Tour
                    </button>
                    <button
                        onClick={() => setModal(null)}
                        className="bg-[#0b1930] hover:bg-[#162640] text-white px-5 py-2 rounded-[10px] text-[13px] font-semibold"
                    >
                        Close
                    </button>
                </div>
            </Modal>
        );
    }

    // ============================================================
    // DELETE MODAL
    // ============================================================
    if (modal === 'delete' && tour) {
        return (
            <Modal onClose={() => setModal(null)} maxWidth="max-w-[460px]">
                <div className="p-5 pb-4 flex items-center justify-between border-b border-slate-100 shrink-0">
                    <h3 className="text-lg font-bold text-slate-900">Delete Virtual Tour</h3>
                    <button
                        onClick={() => setModal(null)}
                        disabled={saving}
                        className="text-slate-400 hover:text-slate-700 text-lg disabled:opacity-50"
                    >
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
                    <p className="font-bold text-slate-900 text-[15px] mb-4">{tour.title}</p>
                    <div className="bg-amber-50 border border-amber-200 rounded-xl p-3 text-left">
                        <p className="text-[12px] text-amber-800 leading-snug">
                            <i className="fa-solid fa-circle-info mr-1"></i>
                            This will also delete all scenes, hotspots, and uploaded images.{' '}
                            <strong>This action cannot be undone.</strong>
                        </p>
                    </div>
                </div>

                <div className="p-5 border-t border-slate-100 flex justify-end gap-3 shrink-0">
                    <button
                        onClick={() => setModal(null)}
                        disabled={saving}
                        className="text-slate-600 text-[13.5px] font-semibold px-4 py-2 disabled:opacity-50"
                    >
                        Cancel
                    </button>
                    <button
                        disabled={saving}
                        onClick={confirmDelete}
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