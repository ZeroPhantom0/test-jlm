// resources/js/pages/Admin/AnnouncementsModals.jsx
import React, { useState, useEffect, useRef } from 'react';
import Modal from '../../components/Modal';

const csrf = () => {
    const meta = document.querySelector('meta[name="csrf-token"]')?.content;
    if (meta) return meta;

    const input = document.querySelector('input[name="_token"]')?.value;
    if (input) return input;

    // Last resort — from the app config written by the layout
    return window.__appConfig?.csrf || '';
};

const EMPTY_FORM = {
    id:           null,
    title:        '',
    content:      '',
    priority:     'Normal',
    is_pinned:    false,
    is_published: true,
};

/**
 * Safe JSON reader — never throws on HTML error responses.
 * Returns { ok, status, data } so callers can branch cleanly.
 */
async function readJsonSafe(res) {
    const text = await res.text();
    let data = null;
    try { data = text ? JSON.parse(text) : {}; }
    catch { data = { message: `Server returned non-JSON (HTTP ${res.status}).` }; }
    return { ok: res.ok, status: res.status, data };
}

export default function AnnouncementsModals() {
    const [showForm, setShowForm]       = useState(false);
    const [showDelete, setShowDelete]   = useState(false);
    const [editMode, setEditMode]       = useState(false);
    const [form, setForm]               = useState(EMPTY_FORM);
    const [deleteTarget, setDeleteTarget] = useState(null);
    const [saving, setSaving]           = useState(false);
    const [errorBanner, setErrorBanner] = useState('');
    const [announcementsData, setAnnouncementsData] = useState({});

    const savingRef = useRef(false);
    useEffect(() => { savingRef.current = saving; }, [saving]);

    // ============================================================
    // LIVE DATA — read once, then listen for polling/Echo updates
    // ============================================================
    const readAnnouncementsFromDom = () => {
        const el = document.getElementById('admin-announcements-modals');
        if (!el?.dataset?.announcements) return;
        try {
            const parsed = JSON.parse(el.dataset.announcements);
            if (parsed && typeof parsed === 'object') setAnnouncementsData(parsed);
        } catch (e) {
            console.error('Invalid announcements JSON', e);
        }
    };

    useEffect(() => {
        readAnnouncementsFromDom();

        const handler = (e) => {
            const fresh = e?.detail;
            if (fresh && typeof fresh === 'object') {
                setAnnouncementsData(fresh);
            } else {
                readAnnouncementsFromDom();
            }
        };

        window.addEventListener('announcements:data-updated', handler);
        return () => window.removeEventListener('announcements:data-updated', handler);
    }, []);

    // Sync open delete-modal target with fresh data
    useEffect(() => {
        if (!deleteTarget) return;
        const fresh = announcementsData[deleteTarget.id];
        if (!fresh) {
            setShowDelete(false);
            setDeleteTarget(null);
            return;
        }
        if (fresh.title !== deleteTarget.title) {
            setDeleteTarget(fresh);
        }
    }, [announcementsData]); // eslint-disable-line react-hooks/exhaustive-deps

    // ============================================================
    // Event delegation for opening the modal / row actions
    // ============================================================
    useEffect(() => {
        const handler = async (e) => {
            // "New Announcement" button
            if (e.target.closest('#admin-open-new-ann')) {
                setErrorBanner('');
                setForm(EMPTY_FORM);
                setEditMode(false);
                setShowForm(true);
                return;
            }

            // Toggle pin (row-level)
            const pinBtn = e.target.closest('.toggle-pin-btn');
            if (pinBtn) {
                const id = pinBtn.dataset.annId;
                if (!id || savingRef.current) return;

                setSaving(true);
                setErrorBanner('');
                try {
                    const res = await fetch(`/admin/announcements/${id}/toggle-pin`, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf(),
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    });
                    const { ok, data } = await readJsonSafe(res);
                    if (!ok) throw new Error(data.message || `Pin failed (HTTP ${res.status}).`);

                    window.dispatchEvent(new CustomEvent('announcements:data-updated'));
                } catch (err) {
                    setErrorBanner(err.message);
                    alert(err.message);
                } finally {
                    setSaving(false);
                }
                return;
            }

            // Edit (row-level)
            const editBtn = e.target.closest('.icon-edit');
            if (editBtn) {
                const id = editBtn.dataset.annId;
                const found = announcementsData[id];
                if (found) {
                    setErrorBanner('');
                    setForm({
                        id:           found.id,
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

            // Open Delete Modal (row-level)
            const delBtn = e.target.closest('.icon-delete');
            if (delBtn) {
                const id = delBtn.dataset.annId;
                const found = announcementsData[id];
                if (found) {
                    setErrorBanner('');
                    setDeleteTarget(found);
                    setShowDelete(true);
                }
                return;
            }
        };

        document.addEventListener('click', handler);
        return () => document.removeEventListener('click', handler);
    }, [announcementsData]);

    const closeModal = () => {
        if (saving) return;
        setShowForm(false);
        setEditMode(false);
        setForm(EMPTY_FORM);
        setErrorBanner('');
    };

    const closeDeleteModal = () => {
        if (saving) return;
        setShowDelete(false);
        setDeleteTarget(null);
        setErrorBanner('');
    };

    const updateField = (key, value) => setForm((f) => ({ ...f, [key]: value }));

    // ============================================================
    // Submit (create or update)
    // ============================================================
    const handleSubmit = async () => {
        if (saving) return;

        if (!form.title.trim() || !form.content.trim()) {
            setErrorBanner('Please fill in both title and content.');
            return;
        }

        const isEdit = editMode && form.id;
        const url    = isEdit
            ? `/admin/announcements/${form.id}`
            : '/admin/announcements';
        const method = isEdit ? 'PUT' : 'POST';

        const payload = {
            title:        form.title,
            content:      form.content,
            priority:     form.priority,
            is_pinned:    form.is_pinned,
            is_published: form.is_published,
        };

        setSaving(true);
        setErrorBanner('');

        try {
            const res = await fetch(url, {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify(payload),
            });

            const { ok, data } = await readJsonSafe(res);
            if (!ok) throw new Error(data.message || `Save failed (HTTP ${res.status}).`);

            window.dispatchEvent(new CustomEvent('announcements:data-updated'));
            setShowForm(false);
            setEditMode(false);
            setForm(EMPTY_FORM);
        } catch (err) {
            setErrorBanner(err.message);
            alert(err.message);
        } finally {
            setSaving(false);
        }
    };

    // ============================================================
    // Confirm Delete
    // ============================================================
    const confirmDelete = async () => {
        if (!deleteTarget || saving) return;

        setSaving(true);
        setErrorBanner('');

        try {
            const res = await fetch(`/admin/announcements/${deleteTarget.id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            const { ok, data } = await readJsonSafe(res);
            if (!ok) throw new Error(data.message || `Delete failed (HTTP ${res.status}).`);

            window.dispatchEvent(new CustomEvent('announcements:data-updated'));

            setShowDelete(false);
            setDeleteTarget(null);
        } catch (err) {
            setErrorBanner(err.message);
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
                        <button
                            onClick={closeModal}
                            disabled={saving}
                            className="text-slate-400 hover:text-slate-700 text-lg disabled:opacity-50"
                        >
                            <i className="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div className="p-6 overflow-y-auto flex flex-col gap-4">
                        {errorBanner && (
                            <div className="bg-rose-50 border border-rose-200 text-rose-700 text-[12.5px] rounded-lg px-3 py-2 flex items-center gap-2">
                                <i className="fa-solid fa-circle-exclamation"></i>
                                {errorBanner}
                            </div>
                        )}

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
                            disabled={saving}
                            className="text-slate-600 text-[13.5px] font-semibold px-4 py-2 disabled:opacity-50"
                        >
                            Cancel
                        </button>
                        <button
                            onClick={handleSubmit}
                            disabled={saving}
                            className="bg-[#0b1930] hover:bg-[#162640] disabled:opacity-50 text-white px-5 py-2.5 rounded-[10px] text-[13.5px] font-semibold inline-flex items-center gap-2"
                        >
                            {saving ? (
                                <>
                                    <i className="fa-solid fa-spinner fa-spin"></i>
                                    Saving…
                                </>
                            ) : (
                                editMode ? 'Save Changes' : 'Post Announcement'
                            )}
                        </button>
                    </div>
                </Modal>
            )}

            {showDelete && deleteTarget && (
                <Modal onClose={closeDeleteModal} maxWidth="max-w-[460px]">
                    <div className="p-5 pb-4 flex items-center justify-between border-b border-slate-100 shrink-0">
                        <h3 className="text-lg font-bold text-slate-900">Delete Announcement</h3>
                        <button
                            onClick={closeDeleteModal}
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
                        <p className="font-bold text-slate-900 text-[15px] mb-4">
                            {deleteTarget.title}
                        </p>
                        <div className="bg-amber-50 border border-amber-200 rounded-xl p-3 text-left">
                            <p className="text-[12px] text-amber-800 leading-snug">
                                <i className="fa-solid fa-circle-info mr-1"></i>
                                This will permanently remove the announcement from all tenants' announcements. <strong>This action cannot be undone.</strong>
                            </p>
                        </div>
                    </div>

                    <div className="p-5 border-t border-slate-100 flex justify-end gap-3 shrink-0">
                        <button
                            onClick={closeDeleteModal}
                            disabled={saving}
                            className="text-slate-600 text-[13.5px] font-semibold px-4 py-2 disabled:opacity-50"
                        >
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
            )}
        </>
    );
}