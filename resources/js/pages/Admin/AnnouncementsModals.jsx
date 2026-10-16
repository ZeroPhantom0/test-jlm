// resources/js/pages/Admin/AnnouncementsModals.jsx
import React, { useState, useEffect } from 'react';
import Modal from '../../components/Modal';

const csrf = () =>
    document.querySelector('meta[name="csrf-token"]')?.content ||
    document.querySelector('input[name="_token"]')?.value;

const EMPTY_FORM = {
    id:           null,       // ← ADDED — used to build the correct PUT URL
    title:        '',
    content:      '',
    priority:     'Normal',
    is_pinned:    false,
    is_published: true,
};

export default function AnnouncementsModals() {
    const [showForm, setShowForm] = useState(false);
    const [editMode, setEditMode] = useState(false);
    const [form, setForm]         = useState(EMPTY_FORM);
    const [saving, setSaving]     = useState(false);
    const [announcementsData, setAnnouncementsData] = useState({});

    // ============================================================
    // Read announcements data from the DOM (passed by Blade)
    // ============================================================
    useEffect(() => {
        const el = document.getElementById('admin-announcements-modals');
        if (el?.dataset?.announcements) {
            try {
                setAnnouncementsData(JSON.parse(el.dataset.announcements));
            } catch (e) {
                console.error('Invalid announcements JSON', e);
            }
        }
    }, []);

    // ============================================================
    // Event delegation for opening the modal / row actions
    // ============================================================
    useEffect(() => {
        const handler = (e) => {
            // "New Announcement" button
            if (e.target.closest('#admin-open-new-ann')) {
                setForm(EMPTY_FORM);
                setEditMode(false);
                setShowForm(true);
                return;
            }

            // Toggle pin (row-level)
            const pinBtn = e.target.closest('.toggle-pin-btn');
            if (pinBtn) {
                const id = pinBtn.dataset.annId;
                if (!id) return;
                setSaving(true);
                fetch(`/admin/announcements/${id}/toggle-pin`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf(),
                    },
                })
                    .then(async (res) => {
                        const data = await res.json();
                        if (!res.ok) throw new Error(data.message || 'Failed to toggle pin.');
                        window.location.reload();
                    })
                    .catch((err) => alert(err.message))
                    .finally(() => setSaving(false));
                return;
            }

            // Edit (row-level)
            const editBtn = e.target.closest('.icon-edit');
            if (editBtn) {
                const id = editBtn.dataset.annId;
                const found = announcementsData[id];
                if (found) {
                    setForm({
                        id:           found.id,           // ← CRITICAL: store the ID
                        title:        found.title,
                        content:      found.content,
                        priority:     found.priority,
                        is_pinned:    found.is_pinned,
                        is_published: found.is_published,
                    });
                    setEditMode(true);
                    setShowForm(true);
                }
                return;
            }

            // Delete (row-level)
            const delBtn = e.target.closest('.icon-delete');
            if (delBtn) {
                const id = delBtn.dataset.annId;
                if (!id) return;
                if (!confirm('Delete this announcement permanently?')) return;
                setSaving(true);
                fetch(`/admin/announcements/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf(),
                    },
                })
                    .then(async (res) => {
                        const data = await res.json();
                        if (!res.ok) throw new Error(data.message || 'Failed to delete.');
                        window.location.reload();
                    })
                    .catch((err) => alert(err.message))
                    .finally(() => setSaving(false));
                return;
            }
        };

        document.addEventListener('click', handler);
        return () => document.removeEventListener('click', handler);
    }, [announcementsData]);

    const closeModal = () => {
        setShowForm(false);
        setEditMode(false);
        setForm(EMPTY_FORM);
    };

    const updateField = (key, value) => setForm((f) => ({ ...f, [key]: value }));

    // ============================================================
    // Submit (create or update)
    // ============================================================
    const handleSubmit = async () => {
        if (!form.title.trim() || !form.content.trim()) {
            alert('Please fill in both title and content.');
            return;
        }

        // Build the right URL based on editMode AND form.id
        const isEdit = editMode && form.id;
        const url    = isEdit
            ? `/admin/announcements/${form.id}`
            : '/admin/announcements';
        const method = isEdit ? 'PUT' : 'POST';

        // Only send editable fields (strip id)
        const payload = {
            title:        form.title,
            content:      form.content,
            priority:     form.priority,
            is_pinned:    form.is_pinned,
            is_published: form.is_published,
        };

        setSaving(true);
        try {
            const res = await fetch(url, {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                },
                body: JSON.stringify(payload),
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Failed to save announcement.');
            closeModal();
            window.location.reload();
        } catch (err) {
            alert(err.message);
        } finally {
            setSaving(false);
        }
    };

    return (
        <>
            {showForm && (
                <Modal onClose={closeModal} maxWidth="max-w-[520px]">
                    <div className="p-5 pb-4 flex items-center justify-between border-b border-slate-100">
                        <h3 className="text-lg font-bold text-slate-900">
                            {editMode ? 'Edit Announcement' : 'New Announcement'}
                        </h3>
                        <button onClick={closeModal} className="text-slate-400 hover:text-slate-700 text-lg">
                            <i className="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div className="p-6 overflow-y-auto flex flex-col gap-4">
                        <div>
                            <label className="block text-[13.5px] font-semibold text-slate-700 mb-1.5">Title</label>
                            <input
                                type="text"
                                value={form.title}
                                onChange={(e) => updateField('title', e.target.value)}
                                placeholder="Announcement title..."
                                className="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 text-[13.5px] outline-none focus:border-slate-400"
                            />
                        </div>

                        <div>
                            <label className="block text-[13.5px] font-semibold text-slate-700 mb-1.5">Message Content</label>
                            <textarea
                                value={form.content}
                                onChange={(e) => updateField('content', e.target.value)}
                                placeholder="Write your announcement details here..."
                                className="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 text-[13.5px] outline-none resize-y min-h-[110px] focus:border-slate-400"
                            ></textarea>
                        </div>

                        <div>
                            <label className="block text-[13.5px] font-semibold text-slate-700 mb-1.5">Priority</label>
                            <select
                                value={form.priority}
                                onChange={(e) => updateField('priority', e.target.value)}
                                className="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 text-[13.5px] outline-none bg-white"
                            >
                                <option value="Normal">Normal</option>
                                <option value="Important">Important</option>
                                <option value="Urgent">Urgent</option>
                            </select>
                        </div>

                        <label className="flex items-center gap-2.5 cursor-pointer select-none mt-1">
                            <input
                                type="checkbox"
                                checked={form.is_pinned}
                                onChange={(e) => updateField('is_pinned', e.target.checked)}
                                className="w-5 h-5 accent-[#0b1930]"
                            />
                            <span className="text-[13.5px] font-medium text-slate-700">Pin this announcement to top</span>
                        </label>
                    </div>

                    <div className="p-5 border-t border-slate-100 flex justify-end gap-3">
                        <button
                            onClick={closeModal}
                            className="text-slate-600 text-[13.5px] font-semibold px-4 py-2"
                        >
                            Cancel
                        </button>
                        <button
                            onClick={handleSubmit}
                            disabled={saving}
                            className="bg-[#0b1930] hover:bg-[#162640] disabled:opacity-50 text-white px-5 py-2.5 rounded-[10px] text-[13.5px] font-semibold"
                        >
                            {saving ? 'Saving…' : (editMode ? 'Save Changes' : 'Post Announcement')}
                        </button>
                    </div>
                </Modal>
            )}
        </>
    );
}