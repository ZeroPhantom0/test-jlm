// resources/js/pages/Services/TenantMaintenanceModals.jsx
import React, { useState, useEffect } from 'react';
import Modal from '../../components/Modal';

const csrf = () =>
    document.querySelector('meta[name="csrf-token"]')?.content ||
    document.querySelector('input[name="_token"]')?.value;

export default function TenantMaintenanceModals() {
    const [showNew, setShowNew]         = useState(false);
    const [showDetails, setShowDetails] = useState(false);
    const [request, setRequest]         = useState(null);
    const [saving, setSaving]           = useState(false);
    const [requestsData, setRequestsData] = useState({});

    // New-request form state
    const [form, setForm] = useState({
        title:       '',
        category:    'General',
        priority:    'Medium',
        description: '',
    });

    // ============================================================
    // Read requests data passed from Blade
    // ============================================================
    useEffect(() => {
        const el = document.getElementById('tenant-maintenance-modals');
        if (el?.dataset?.requests) {
            try {
                setRequestsData(JSON.parse(el.dataset.requests));
            } catch (e) {
                console.error('Invalid requests JSON', e);
            }
        }
    }, []);

    // ============================================================
    // Event delegation
    // ============================================================
    useEffect(() => {
        const handler = (e) => {
            // New Request button
            if (e.target.closest('#open-new-request')) {
                setForm({
                    title:       '',
                    category:    'General',
                    priority:    'Medium',
                    description: '',
                });
                setShowNew(true);
                return;
            }

            // View Details
            const viewBtn = e.target.closest('.view-details-btn');
            if (viewBtn) {
                const id   = viewBtn.dataset.requestId;
                const data = requestsData[id];
                if (data) {
                    setRequest(data);
                    setShowDetails(true);
                }
                return;
            }

            // Cancel Request (from row)
            const cancelBtn = e.target.closest('.cancel-request-btn');
            if (cancelBtn) {
                const id = cancelBtn.dataset.requestId;
                if (!id) return;
                handleCancel(id);
                return;
            }
        };

        document.addEventListener('click', handler);
        return () => document.removeEventListener('click', handler);
    }, [requestsData]);

    const updateField = (key, value) => setForm((f) => ({ ...f, [key]: value }));

    // ============================================================
    // Cancel a pending request
    // ============================================================
    const handleCancel = async (id) => {
        if (!confirm('Cancel this maintenance request?')) return;
        setSaving(true);
        try {
            const res = await fetch(`/tenants/services/maintenance/${id}/cancel`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                },
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(data.message || 'Failed to cancel request.');
            setShowDetails(false);
            window.location.reload();
        } catch (err) {
            alert(err.message);
        } finally {
            setSaving(false);
        }
    };

    // ============================================================
    // Create new maintenance request
    // ============================================================
    const handleCreate = async () => {
        if (!form.title.trim() || !form.description.trim()) {
            alert('Please fill in both Issue Title and Description.');
            return;
        }
        setSaving(true);
        try {
            const res = await fetch('/tenants/services/maintenance', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                },
                body: JSON.stringify(form),
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
    // Cancel a pending request (from modal footer)
    // ============================================================
    const handleCancelRequest = async () => {
        if (!request) return;
        await handleCancel(request.id);
    };

    return (
        <>
            {/* ============================================================ */}
            {/* MODAL 1: Submit Maintenance Request */}
            {/* ============================================================ */}
            {showNew && (
                <Modal onClose={() => setShowNew(false)} maxWidth="max-w-[500px]">
                    <div className="p-5 pb-4 flex items-center justify-between border-b border-slate-100">
                        <h3 className="text-base font-bold text-slate-900">Submit Maintenance Request</h3>
                        <button onClick={() => setShowNew(false)} className="text-slate-400 hover:text-slate-600 text-lg">
                            <i className="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div className="p-6 overflow-y-auto flex flex-col gap-4">
                        <div className="bg-amber-50 border border-amber-100 rounded-lg p-3 flex gap-2 text-xs text-amber-700">
                            <i className="fa-regular fa-circle-info mt-0.5"></i>
                            <span>Describe the maintenance issue clearly so our team can assist you promptly.</span>
                        </div>

                        <div>
                            <label className="block text-xs font-semibold text-slate-700 mb-1.5">
                                Issue Title <span className="text-rose-500">*</span>
                            </label>
                            <input
                                type="text"
                                value={form.title}
                                onChange={(e) => updateField('title', e.target.value)}
                                placeholder="e.g. Leaky Faucet in Kitchen"
                                className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none focus:border-blue-400"
                            />
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <label className="block text-xs font-semibold text-slate-700 mb-1.5">Category</label>
                                <select
                                    value={form.category}
                                    onChange={(e) => updateField('category', e.target.value)}
                                    className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none bg-white"
                                >
                                    <option>General</option>
                                    <option>Plumbing</option>
                                    <option>Electrical</option>
                                    <option>HVAC</option>
                                </select>
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-slate-700 mb-1.5">Priority</label>
                                <select
                                    value={form.priority}
                                    onChange={(e) => updateField('priority', e.target.value)}
                                    className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none bg-white"
                                >
                                    <option value="Low">Low</option>
                                    <option value="Medium">Medium</option>
                                    <option value="High">High</option>
                                    <option value="Critical">Critical</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label className="block text-xs font-semibold text-slate-700 mb-1.5">
                                Description <span className="text-rose-500">*</span>
                            </label>
                            <textarea
                                value={form.description}
                                onChange={(e) => updateField('description', e.target.value)}
                                placeholder="Describe the problem in detail..."
                                className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none resize-y min-h-[80px]"
                            />
                        </div>
                    </div>

                    <div className="p-5 border-t border-slate-100 flex justify-end gap-3">
                        <button
                            onClick={() => setShowNew(false)}
                            className="text-slate-600 text-[13px] font-semibold px-4 py-2"
                        >
                            Cancel
                        </button>
                        <button
                            onClick={handleCreate}
                            disabled={saving}
                            className="bg-[#02122c] hover:bg-[#0d2347] disabled:opacity-50 text-white px-4 py-2.5 rounded-lg text-[13px] font-semibold"
                        >
                            {saving ? 'Submitting…' : 'Submit Request'}
                        </button>
                    </div>
                </Modal>
            )}

            {/* ============================================================ */}
            {/* MODAL 2: View Details */}
            {/* ============================================================ */}
            {showDetails && request && (
                <Modal onClose={() => setShowDetails(false)} maxWidth="max-w-[500px]">
                    <div className="p-5 pb-4 flex items-center justify-between border-b border-slate-100">
                        <h3 className="text-base font-bold text-slate-900">Maintenance Request Details</h3>
                        <button onClick={() => setShowDetails(false)} className="text-slate-400 hover:text-slate-600 text-lg">
                            <i className="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div className="p-6 overflow-y-auto flex flex-col gap-4">

                        {/* Title + Status */}
                        <div className="flex justify-between items-center">
                            <h4 className="text-[15px] font-bold text-slate-900">{request.title}</h4>
                            <span className={`text-[11px] font-semibold px-2.5 py-1 rounded-full inline-flex items-center gap-1 ${
                                request.status === 'Pending'
                                    ? 'bg-amber-100 text-amber-700'
                                    : request.status === 'In Progress'
                                    ? 'bg-blue-100 text-blue-600'
                                    : request.status === 'Completed'
                                    ? 'bg-emerald-100 text-emerald-600'
                                    : 'bg-slate-100 text-slate-500'
                            }`}>
                                <i className="fa-regular fa-clock"></i> {request.status}
                            </span>
                        </div>
                        <div className="text-[11px] text-slate-400 -mt-2">{request.ref}</div>

                        {/* Description */}
                        <div className="bg-slate-50 rounded-lg p-3 text-[13px] text-slate-700">
                            {request.desc}
                        </div>

                        {/* Details Grid */}
                        <div className="grid grid-cols-2 gap-3">
                            <div className="bg-slate-50 rounded-lg p-3">
                                <div className="text-[10px] text-slate-400 mb-1">Category</div>
                                <div className="text-xs font-semibold text-slate-900">{request.category}</div>
                            </div>
                            <div className="bg-slate-50 rounded-lg p-3">
                                <div className="text-[10px] text-slate-400 mb-1">Priority</div>
                                <span className={`text-[9px] font-bold px-1.5 py-0.5 rounded uppercase ${
                                    request.priority === 'CRITICAL' || request.priority === 'HIGH'
                                        ? 'bg-rose-100 text-rose-700'
                                        : request.priority === 'MEDIUM'
                                        ? 'bg-amber-100 text-amber-700'
                                        : 'bg-blue-100 text-blue-600'
                                }`}>
                                    {request.priority}
                                </span>
                            </div>
                            <div className="bg-slate-50 rounded-lg p-3">
                                <div className="text-[10px] text-slate-400 mb-1">Submitted</div>
                                <div className="text-xs font-semibold text-slate-900">{request.submitted}</div>
                            </div>
                            <div className="bg-slate-50 rounded-lg p-3">
                                <div className="text-[10px] text-slate-400 mb-1">Assigned Staff</div>
                                <div className="text-xs font-semibold text-slate-900">
                                    {request.staff || 'Not Yet Assigned'}
                                </div>
                            </div>
                            <div className="bg-slate-50 rounded-lg p-3">
                                <div className="text-[10px] text-slate-400 mb-1">Scheduled</div>
                                <div className="text-xs font-semibold text-slate-400">
                                    {request.scheduled || '—'}
                                </div>
                            </div>
                            <div className="bg-slate-50 rounded-lg p-3">
                                <div className="text-[10px] text-slate-400 mb-1">Unit</div>
                                <div className="text-xs font-semibold text-slate-900">{request.unit}</div>
                            </div>
                        </div>
                    </div>

                    {/* Footer */}
                    <div className="p-5 border-t border-slate-100 flex items-center justify-end gap-3">
                        {request.status === 'Pending' && (
                            <button
                                onClick={handleCancelRequest}
                                disabled={saving}
                                className="border border-red-300 text-red-500 hover:bg-red-50 disabled:opacity-50 rounded-lg px-4 py-2 text-xs font-semibold inline-flex items-center gap-1.5"
                            >
                                <i className="fa-regular fa-circle-xmark"></i>
                                Cancel Request
                            </button>
                        )}
                        <button
                            onClick={() => setShowDetails(false)}
                            className="bg-[#02122c] hover:bg-[#0d2347] text-white px-4 py-2 rounded-lg text-[13px] font-semibold"
                        >
                            Close
                        </button>
                    </div>
                </Modal>
            )}
        </>
    );
}