// resources/js/pages/Admin/ServicesModals.jsx
import React, { useState, useEffect } from 'react';
import Modal from '../../components/Modal';

const csrf = () =>
    document.querySelector('meta[name="csrf-token"]')?.content ||
    document.querySelector('input[name="_token"]')?.value;

// Priority options
const PRIORITIES = ['Low', 'Medium', 'High', 'Critical'];
const STATUSES = ['Pending', 'In Progress', 'Completed', 'Cancelled'];

export default function ServicesModals() {
    const [showDetails, setShowDetails] = useState(false);
    const [showNew, setShowNew] = useState(false);
    const [showDelete, setShowDelete] = useState(false);
    const [deleteTarget, setDeleteTarget] = useState(null);
    const [request, setRequest] = useState(null);
    const [saving, setSaving] = useState(false);

    // Form state for updating
    const [form, setForm] = useState({
        status: '',
        priority: '',
        assigned_staff: '',
        scheduled_date: '',
    });

    // Form state for new request
    const [newForm, setNewForm] = useState({
        title: '',
        description: '',
        category: '',
        priority: 'Medium',
        preferred_date: '',
        preferred_time: '',
        room_id: '',
    });

    // ============================================================
    // Event delegation for opening modals
    // ============================================================
    useEffect(() => {
        const handler = (e) => {
            // View details
            const viewBtn = e.target.closest('.admin-view-request-btn');
            if (viewBtn) {
                const data = JSON.parse(viewBtn.dataset.request || '{}');
                setRequest(data);
                setForm({
                    status: data.status || 'Pending',
                    priority: data.priority || 'Medium',
                    assigned_staff: data.assigned_staff || '',
                    scheduled_date: data.scheduled_date || '',
                });
                setShowDetails(true);
                return;
            }

            // New request
            if (e.target.closest('#admin-open-new-maintenance')) {
                setNewForm({
                    title: '',
                    description: '',
                    category: '',
                    priority: 'Medium',
                    preferred_date: '',
                    preferred_time: '',
                    room_id: '',
                });
                setShowNew(true);
            }
        };
        document.addEventListener('click', handler);
        return () => document.removeEventListener('click', handler);
    }, []);

    // ============================================================
    // Update request
    // ============================================================
    const handleUpdate = async () => {
        if (!request) return;
        setSaving(true);
        try {
            const res = await fetch(`/admin/services/maintenance/${request.id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                },
                body: JSON.stringify(form),
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Failed to update request.');
            setShowDetails(false);
            window.location.reload();
        } catch (err) {
            alert(err.message);
        } finally {
            setSaving(false);
        }
    };

    // ============================================================
    // Create new request
    // ============================================================
    const handleCreate = async () => {
        setSaving(true);
        try {
            const res = await fetch('/admin/services/maintenance', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                },
                body: JSON.stringify(newForm),
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Failed to create request.');
            setShowNew(false);
            window.location.reload();
        } catch (err) {
            alert(err.message);
        } finally {
            setSaving(false);
        }
    };

    // ============================================================
    // Open delete confirmation
    // ============================================================
    const openDeleteModal = () => {
        if (!request) return;
        setDeleteTarget({
            id: request.id,
            title: request.title,
            ref: request.id,
        });
        setShowDelete(true);
    };

    const closeDeleteModal = () => {
        if (saving) return;
        setShowDelete(false);
        setDeleteTarget(null);
    };

    // ============================================================
    // Confirm delete
    // ============================================================
    const confirmDelete = async () => {
        if (!deleteTarget) return;
        setSaving(true);
        try {
            const res = await fetch(`/admin/services/maintenance/${deleteTarget.id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                },
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Failed to delete request.');
            setShowDelete(false);
            setDeleteTarget(null);
            setShowDetails(false);
            window.location.reload();
        } catch (err) {
            alert(err.message);
        } finally {
            setSaving(false);
        }
    };

    const updateField = (key, value) =>
        setForm((f) => ({ ...f, [key]: value }));

    const updateNewField = (key, value) =>
        setNewForm((f) => ({ ...f, [key]: value }));

    return (
        <>
            {/* ============================================================ */}
            {/* NEW REQUEST MODAL */}
            {/* ============================================================ */}
            {showNew && (
                <Modal onClose={() => setShowNew(false)} maxWidth="max-w-[540px]">
                    <div className="p-5 pb-4 flex items-center justify-between border-b border-slate-100">
                        <h3 className="text-lg font-bold text-slate-900">New Maintenance Request</h3>
                        <button onClick={() => setShowNew(false)} className="text-slate-400 hover:text-slate-700 text-lg">
                            <i className="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div className="p-6 overflow-y-auto flex flex-col gap-4">
                        <div>
                            <label className="block text-[13px] font-semibold text-slate-700 mb-1.5">
                                Issue Title <span className="text-rose-500">*</span>
                            </label>
                            <input
                                type="text"
                                value={newForm.title}
                                onChange={(e) => updateNewField('title', e.target.value)}
                                placeholder="e.g. Leaky Faucet in Kitchen"
                                className="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-[13px] outline-none focus:border-blue-400"
                            />
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <label className="block text-[13px] font-semibold text-slate-700 mb-1.5">
                                    Category <span className="text-rose-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    value={newForm.category}
                                    onChange={(e) => updateNewField('category', e.target.value)}
                                    placeholder="e.g. Plumbing, Electrical, HVAC"
                                    className="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-[13px] outline-none focus:border-blue-400"
                                />
                                <p className="text-[11px] text-slate-400 mt-1">Type any category</p>
                            </div>
                            <div>
                                <label className="block text-[13px] font-semibold text-slate-700 mb-1.5">Priority</label>
                                <select
                                    value={newForm.priority}
                                    onChange={(e) => updateNewField('priority', e.target.value)}
                                    className="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-[13px] outline-none bg-white"
                                >
                                    {PRIORITIES.map((p) => (
                                        <option key={p}>{p}</option>
                                    ))}
                                </select>
                            </div>
                        </div>

                        <div>
                            <label className="block text-[13px] font-semibold text-slate-700 mb-1.5">
                                Description <span className="text-rose-500">*</span>
                            </label>
                            <textarea
                                value={newForm.description}
                                onChange={(e) => updateNewField('description', e.target.value)}
                                placeholder="Describe the problem in detail..."
                                className="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-[13px] outline-none resize-y min-h-[80px] focus:border-blue-400"
                            />
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <label className="block text-[13px] font-semibold text-slate-700 mb-1.5">Preferred Date</label>
                                <input
                                    type="date"
                                    value={newForm.preferred_date}
                                    onChange={(e) => updateNewField('preferred_date', e.target.value)}
                                    className="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-[13px] outline-none"
                                />
                            </div>
                            <div>
                                <label className="block text-[13px] font-semibold text-slate-700 mb-1.5">Preferred Time</label>
                                <input
                                    type="text"
                                    value={newForm.preferred_time}
                                    onChange={(e) => updateNewField('preferred_time', e.target.value)}
                                    placeholder="e.g. 10:00 AM - 12:00 PM"
                                    className="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-[13px] outline-none"
                                />
                            </div>
                        </div>
                    </div>

                    <div className="p-5 border-t border-slate-100 flex justify-end gap-3">
                        <button onClick={() => setShowNew(false)} className="text-slate-600 text-[13px] font-semibold px-4 py-2">
                            Cancel
                        </button>
                        <button
                            disabled={saving}
                            onClick={handleCreate}
                            className="bg-[#02122c] hover:bg-[#0d2347] disabled:opacity-50 text-white px-5 py-2.5 rounded-lg text-[13px] font-semibold"
                        >
                            {saving ? 'Creating…' : 'Create Request'}
                        </button>
                    </div>
                </Modal>
            )}

            {/* ============================================================ */}
            {/* VIEW/EDIT DETAILS MODAL */}
            {/* ============================================================ */}
            {showDetails && request && (
                <Modal onClose={() => setShowDetails(false)} maxWidth="max-w-[540px]">
                    <div className="p-6 pb-4 flex items-center justify-between border-b border-slate-100">
                        <h3 className="text-lg font-bold text-slate-900">Request Details</h3>
                        <button onClick={() => setShowDetails(false)} className="text-slate-400 hover:text-slate-700 text-lg">
                            <i className="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div className="p-6 overflow-y-auto flex flex-col gap-4">
                        <div className="flex items-center justify-between">
                            <h4 className="text-lg font-bold text-slate-900">{request.title}</h4>
                            <div className="flex items-center gap-1.5">
                                <span className={`text-[10px] font-extrabold px-1.5 py-0.5 rounded uppercase ${
                                    request.priority === 'Critical' ? 'bg-rose-100 text-rose-700' :
                                    request.priority === 'High' ? 'bg-rose-100 text-rose-600' :
                                    request.priority === 'Medium' ? 'bg-yellow-100 text-amber-700' :
                                    'bg-blue-100 text-blue-600'
                                }`}>
                                    {request.priority}
                                </span>
                                <span className={`text-[11px] font-semibold px-2.5 py-1 rounded-full ${
                                    request.status === 'Pending' ? 'bg-yellow-100 text-amber-700' :
                                    request.status === 'In Progress' ? 'bg-blue-100 text-blue-600' :
                                    request.status === 'Completed' ? 'bg-emerald-100 text-emerald-600' :
                                    'bg-slate-100 text-slate-500'
                                }`}>
                                    {request.status}
                                </span>
                            </div>
                        </div>
                        <div className="text-xs text-slate-400">#SR-{request.id}</div>

                        <div className="bg-slate-50 rounded-xl p-4 text-[13.5px] text-slate-700 leading-snug">
                            {request.description}
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="flex items-start gap-2.5">
                                <i className="fa-regular fa-user text-slate-400 text-[14px] mt-0.5"></i>
                                <div>
                                    <div className="text-[11px] text-slate-400">Tenant</div>
                                    <p className="text-[13px] font-bold text-slate-900 mt-0.5">
                                        {request.tenant?.user?.name || 'Unknown'}
                                    </p>
                                </div>
                            </div>
                            <div className="flex items-start gap-2.5">
                                <i className="fa-solid fa-wrench text-slate-400 text-[14px] mt-0.5"></i>
                                <div>
                                    <div className="text-[11px] text-slate-400">Room / Category</div>
                                    <p className="text-[13px] font-bold text-slate-900 mt-0.5">
                                        {request.room?.room_number ? `Room ${request.room.room_number}` : 'N/A'} · {request.category}
                                    </p>
                                </div>
                            </div>
                            <div className="flex items-start gap-2.5 col-span-2">
                                <i className="fa-regular fa-calendar text-slate-400 text-[14px] mt-0.5"></i>
                                <div>
                                    <div className="text-[11px] text-slate-400">Created</div>
                                    <p className="text-[13px] font-bold text-slate-900 mt-0.5">
                                        {new Date(request.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}
                                    </p>
                                </div>
                            </div>
                        </div>

                        {/* Status Update */}
                        <div>
                            <div className="text-[13px] font-bold text-slate-900 mb-2.5">Update Status</div>
                            <div className="flex flex-wrap gap-2">
                                {STATUSES.map((s) => (
                                    <button
                                        key={s}
                                        onClick={() => updateField('status', s)}
                                        className={`px-3.5 py-1.5 rounded-[20px] text-xs font-semibold border transition ${
                                            form.status === s
                                                ? 'bg-[#02122b] text-white border-[#02122b]'
                                                : 'bg-white text-slate-600 border-slate-300 hover:border-slate-500'
                                        }`}
                                    >
                                        {s}
                                    </button>
                                ))}
                            </div>
                        </div>

                        {/* Priority Update */}
                        <div>
                            <div className="text-[13px] font-bold text-slate-900 mb-2.5">Priority</div>
                            <div className="flex flex-wrap gap-2">
                                {PRIORITIES.map((p) => (
                                    <button
                                        key={p}
                                        onClick={() => updateField('priority', p)}
                                        className={`px-3.5 py-1.5 rounded-[20px] text-xs font-semibold border transition ${
                                            form.priority === p
                                                ? 'bg-[#02122b] text-white border-[#02122b]'
                                                : 'bg-white text-slate-600 border-slate-300 hover:border-slate-500'
                                        }`}
                                    >
                                        {p}
                                    </button>
                                ))}
                            </div>
                        </div>

                        <div>
                            <label className="block text-[13px] font-bold text-slate-900 mb-2">Assign Staff</label>
                            <input
                                type="text"
                                value={form.assigned_staff}
                                onChange={(e) => updateField('assigned_staff', e.target.value)}
                                placeholder="e.g. Bobby Cruz"
                                className="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-[13px] outline-none"
                            />
                        </div>

                        <div>
                            <label className="block text-[13px] font-bold text-slate-900 mb-2">Schedule Date</label>
                            <input
                                type="date"
                                value={form.scheduled_date}
                                onChange={(e) => updateField('scheduled_date', e.target.value)}
                                className="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-[13px] outline-none"
                            />
                        </div>
                    </div>

                    <div className="p-5 border-t border-slate-100 flex items-center justify-between gap-3">
                        <div className="flex items-center gap-2.5">
                            <button
                                disabled={saving}
                                onClick={handleUpdate}
                                className="bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white px-4 py-2.5 rounded-lg text-[13px] font-semibold inline-flex items-center gap-1.5"
                            >
                                <i className="fa-regular fa-circle-check"></i>
                                {saving ? 'Updating…' : 'Update'}
                            </button>
                            <button
                                onClick={openDeleteModal}
                                className="text-rose-600 hover:text-rose-700 text-[13px] font-semibold px-3 py-2"
                            >
                                Delete
                            </button>
                        </div>
                        <button
                            onClick={() => setShowDetails(false)}
                            className="bg-[#02122c] text-white px-4 py-2.5 rounded-lg text-[13px] font-semibold"
                        >
                            Close
                        </button>
                    </div>
                </Modal>
            )}

            {/* ============================================================ */}
            {/* DELETE CONFIRMATION MODAL */}
            {/* ============================================================ */}
            {showDelete && deleteTarget && (
                <Modal onClose={closeDeleteModal} maxWidth="max-w-[460px]">
                    <div className="p-5 pb-4 flex items-center justify-between border-b border-slate-100">
                        <h3 className="text-lg font-bold text-slate-900">Delete Maintenance Request</h3>
                        <button
                            onClick={closeDeleteModal}
                            disabled={saving}
                            className="text-slate-400 hover:text-slate-700 text-lg disabled:opacity-50"
                        >
                            <i className="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div className="p-6 text-center">
                        <div className="w-16 h-16 mx-auto rounded-full bg-rose-50 flex items-center justify-center mb-4">
                            <i className="fa-solid fa-trash-can text-rose-500 text-2xl"></i>
                        </div>
                        <h4 className="text-lg font-bold text-slate-900 mb-2">Are you sure?</h4>
                        <p className="text-slate-500 text-[13px] mb-2">
                            You are about to permanently delete:
                        </p>
                        <p className="font-bold text-slate-900 text-[15px] mb-4">
                            {deleteTarget.title} <span className="text-slate-400 font-normal">(#SR-{deleteTarget.ref})</span>
                        </p>
                        <div className="bg-amber-50 border border-amber-200 rounded-xl p-3 text-left">
                            <p className="text-[12px] text-amber-800 leading-snug">
                                <i className="fa-solid fa-circle-info mr-1"></i>
                                This will permanently remove the maintenance request, its status, and its history.{' '}
                                <strong>This action cannot be undone.</strong>
                            </p>
                        </div>
                    </div>

                    <div className="p-5 border-t border-slate-100 flex justify-end gap-3">
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