// resources/js/pages/Admin/TenantsModals.jsx
import React, { useState, useEffect } from 'react';
import Modal from '../../components/Modal';

// ---------- helpers ----------

// Convert "HH:mm" or "HH:mm:ss" (24hr) to "h:mm AM/PM" (12hr) for display
function formatTime12h(time) {
    if (!time) return '';
    const str = String(time).trim();
    const match = str.match(/^(\d{1,2}):(\d{2})(?::\d{2})?$/);
    if (!match) return str;
    let hours = parseInt(match[1], 10);
    const minutes = match[2];
    const meridiem = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12;
    if (hours === 0) hours = 12;
    return `${hours}:${minutes} ${meridiem}`;
}

// Convert "June 1, 2026" (or ISO date) → "YYYY-MM-DD" for <input type="date">
function toDateInput(str) {
    if (!str || str === 'Open-ended' || str === '—') return '';
    const d = new Date(str);
    if (isNaN(d.getTime())) return '';
    const yyyy = d.getFullYear();
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    return `${yyyy}-${mm}-${dd}`;
}

// Convert any time string → "HH:mm" for <input type="time">
function toTimeInput(str) {
    if (!str) return '';
    const m = String(str).match(/^(\d{1,2}):(\d{2})/);
    if (!m) return '';
    return `${m[1].padStart(2, '0')}:${m[2]}`;
}

// Read the CSRF token from the layout meta tag
function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

// ---------- component ----------

export default function TenantsModals() {
    const [showView, setShowView] = useState(false);
    const [showEdit, setShowEdit] = useState(false);
    const [tenant, setTenant] = useState(null);

    // Edit form state
    const [form, setForm] = useState(null);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState('');

    // -------- open modals via delegated clicks --------
    useEffect(() => {
        const handleClick = (e) => {
            const viewBtn = e.target.closest('.view-tenant-btn');
            const editBtn = e.target.closest('.edit-tenant-btn');
            if (!viewBtn && !editBtn) return;

            const row = e.target.closest('tr[data-tenant]');
            if (!row) return;

            let parsed = null;
            try {
                parsed = JSON.parse(row.dataset.tenant);
            } catch {
                parsed = null;
            }
            setTenant(parsed);
            setError('');
            setForm(null);

            if (viewBtn) setShowView(true);
            if (editBtn) setShowEdit(true);
        };

        document.addEventListener('click', handleClick);
        return () => document.removeEventListener('click', handleClick);
    }, []);

    // -------- seed the edit form when opening --------
    useEffect(() => {
        if (showEdit && tenant) {
            const isOpenEnded = tenant.check_out === 'Open-ended';
            setForm({
                name:           tenant.name  ?? '',
                email:          tenant.email ?? '',
                phone:          tenant.phone ?? '',
                check_in:       toDateInput(tenant.check_in),
                check_in_time:  toTimeInput(tenant.check_in_time),
                check_out:      isOpenEnded ? '' : toDateInput(tenant.check_out),
                check_out_time: isOpenEnded ? '' : toTimeInput(tenant.check_out_time),
                rent_rate:      Number(tenant.rent_rate ?? 0),
                status:         tenant.status ?? 'active',
            });
        }
    }, [showEdit, tenant]);

    // -------- save --------
    const handleSave = async () => {
        if (!tenant || !form) return;
        setSaving(true);
        setError('');

        // Basic client-side check
        if (form.check_out && form.check_in && form.check_out < form.check_in) {
            setError('Check-out date must be on or after check-in date.');
            setSaving(false);
            return;
        }

        try {
            const res = await fetch(`/admin/tenants/${tenant.id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type':  'application/json',
                    'Accept':        'application/json',
                    'X-CSRF-TOKEN':  csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    name:           form.name,
                    email:          form.email,
                    phone:          form.phone,
                    check_in:       form.check_in || null,
                    check_in_time:  form.check_in_time || null,
                    check_out:      form.check_out || null,
                    check_out_time: form.check_out_time || null,
                    rent_rate:      Number(form.rent_rate) || 0,
                    status:         form.status,
                }),
            });

            const json = await res.json().catch(() => ({}));

            if (!res.ok) {
                // Validation errors: { errors: { field: [msg] } }
                if (json.errors) {
                    const first = Object.values(json.errors)[0];
                    throw new Error(
                        Array.isArray(first) ? first[0] : (json.message || 'Validation failed.')
                    );
                }
                throw new Error(json.message || 'Save failed.');
            }

            // Success — close modal and reload so the row reflects the changes
            setShowEdit(false);
            window.location.reload();
        } catch (err) {
            setError(err.message || 'Something went wrong.');
        } finally {
            setSaving(false);
        }
    };

    // -------- derived --------
    const isActive = tenant?.status === 'active';
    const isOpenEnded = tenant?.check_out === 'Open-ended';

    // Generic setter for the form
    const set = (key) => (e) =>
        setForm((f) => ({ ...f, [key]: e.target.value }));

    return (
        <>
            {showView && tenant && (
                <Modal onClose={() => setShowView(false)} maxWidth="max-w-[540px]">
                    <div className="p-5 pb-4 flex items-center justify-between border-b border-slate-100">
                        <h3 className="text-lg font-bold text-slate-900">Tenant Details</h3>
                        <button onClick={() => setShowView(false)} className="text-slate-400 hover:text-slate-700 text-lg">
                            <i className="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div className="p-6 overflow-y-auto flex flex-col gap-4 max-h-[70vh]">
                        {/* Profile card */}
                        <div className="bg-slate-50 rounded-xl p-4 flex items-center justify-between">
                            <div className="flex items-center gap-3.5">
                                <div className={`w-12 h-12 rounded-full flex items-center justify-center text-lg font-bold
                                    ${isActive ? 'bg-green-100 text-green-600' : 'bg-slate-200 text-slate-500'}`}>
                                    {tenant.initial}
                                </div>
                                <div>
                                    <h4 className="text-[15px] font-bold text-slate-900">{tenant.name}</h4>
                                    <p className="text-xs text-slate-500 mt-0.5 flex items-center gap-1.5">
                                        <i className="fa-regular fa-envelope"></i> {tenant.email}
                                    </p>
                                    <p className="text-xs text-slate-500 mt-0.5 flex items-center gap-1.5">
                                        <i className="fa-solid fa-phone"></i> {tenant.phone}
                                    </p>
                                </div>
                            </div>
                            <span className={`inline-block px-2.5 py-1 rounded-xl text-[11px] font-bold
                                ${isActive ? 'bg-green-100 text-green-600' : 'bg-slate-100 text-slate-500'}`}>
                                {isActive ? 'Active' : 'Inactive'}
                            </span>
                        </div>

                        {/* Basic lease info */}
                        <div className="grid grid-cols-2 gap-3">
                            <div className="bg-slate-50 rounded-xl p-3.5">
                                <div className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Room</div>
                                <div className="text-[13px] font-bold text-slate-900 mt-1">{tenant.room}</div>
                            </div>
                            <div className="bg-slate-50 rounded-xl p-3.5">
                                <div className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Rent Rate</div>
                                <div className="text-[13px] font-bold text-slate-900 mt-1">
                                    ₱{Number(tenant.rent_rate || 0).toLocaleString()}
                                </div>
                            </div>
                            <div className="bg-slate-50 rounded-xl p-3.5">
                                <div className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Check In</div>
                                <div className="text-[13px] font-bold text-slate-900 mt-1">
                                    {tenant.check_in}{tenant.check_in_time ? ` · ${formatTime12h(tenant.check_in_time)}` : ''}
                                </div>
                            </div>
                            <div className="bg-slate-50 rounded-xl p-3.5">
                                <div className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Check Out</div>
                                <div className={`text-[13px] font-bold mt-1 ${isOpenEnded ? 'text-slate-400 italic' : 'text-slate-900'}`}>
                                    {tenant.check_out}{!isOpenEnded && tenant.check_out_time ? ` · ${formatTime12h(tenant.check_out_time)}` : ''}
                                </div>
                            </div>
                        </div>

                        {/* Valid ID info */}
                        <div className="grid grid-cols-2 gap-3">
                            <div className="bg-slate-50 rounded-xl p-3.5">
                                <div className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Valid ID Type</div>
                                <div className="text-[13px] font-bold text-slate-900 mt-1">
                                    {tenant.id_type || 'Not uploaded'}
                                </div>
                            </div>
                            <div className="bg-slate-50 rounded-xl p-3.5">
                                <div className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Uploaded Date</div>
                                <div className="text-[13px] font-bold text-slate-900 mt-1">
                                    {tenant.id_uploaded_at || '—'}
                                </div>
                            </div>
                        </div>

                        <div className="text-[13px] font-bold text-slate-900">Valid ID</div>

                        {tenant.id_front_url || tenant.id_back_url ? (
                            <>
                                <div className="grid grid-cols-2 gap-3">
                                    <div className="flex flex-col gap-1.5">
                                        <div className="text-[10px] font-bold text-slate-500 flex items-center gap-1.5">
                                            <i className="fa-regular fa-address-card"></i> FRONT SIDE
                                        </div>
                                        <div className="border border-slate-200 rounded-xl overflow-hidden bg-white h-[120px] flex items-center justify-center">
                                            {tenant.id_front_url ? (
                                                <a href={tenant.id_front_url} target="_blank" rel="noopener noreferrer" className="w-full h-full block">
                                                    <img src={tenant.id_front_url} alt="Valid ID (Front)" className="w-full h-full object-cover hover:scale-105 transition" />
                                                </a>
                                            ) : (
                                                <span className="text-xs text-slate-400">Not uploaded</span>
                                            )}
                                        </div>
                                    </div>
                                    <div className="flex flex-col gap-1.5">
                                        <div className="text-[10px] font-bold text-slate-500 flex items-center gap-1.5">
                                            <i className="fa-regular fa-address-card"></i> BACK SIDE
                                        </div>
                                        <div className="border border-slate-200 rounded-xl overflow-hidden bg-white h-[120px] flex items-center justify-center">
                                            {tenant.id_back_url ? (
                                                <a href={tenant.id_back_url} target="_blank" rel="noopener noreferrer" className="w-full h-full block">
                                                    <img src={tenant.id_back_url} alt="Valid ID (Back)" className="w-full h-full object-cover hover:scale-105 transition" />
                                                </a>
                                            ) : (
                                                <span className="text-xs text-slate-400">Not uploaded</span>
                                            )}
                                        </div>
                                    </div>
                                </div>
                                <p className="text-center text-[11px] text-slate-400">Click an image to view full size</p>
                            </>
                        ) : (
                            <div className="bg-slate-50 border border-dashed border-slate-200 rounded-xl p-6 text-center">
                                <i className="fa-regular fa-id-card text-slate-300 text-2xl"></i>
                                <p className="text-xs text-slate-400 mt-2">No valid ID uploaded by this tenant yet.</p>
                            </div>
                        )}
                    </div>
                </Modal>
            )}

            {showEdit && tenant && form && (
                <Modal onClose={() => !saving && setShowEdit(false)} maxWidth="max-w-[540px]">
                    <div className="p-5 pb-4 flex items-center justify-between border-b border-slate-100">
                        <h3 className="text-lg font-bold text-slate-900">Edit Tenant</h3>
                        <button
                            onClick={() => !saving && setShowEdit(false)}
                            className="text-slate-400 hover:text-slate-700 text-lg"
                        >
                            <i className="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div className="p-6 overflow-y-auto flex flex-col gap-4 max-h-[70vh]">
                        {error && (
                            <div className="bg-rose-50 border border-rose-200 text-rose-700 text-[12px] rounded-lg px-3 py-2">
                                {error}
                            </div>
                        )}

                        <div>
                            <label className="block text-xs font-semibold text-slate-700 mb-1.5">Tenant Full Name</label>
                            <input
                                type="text"
                                value={form.name}
                                onChange={set('name')}
                                className="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-[13px] outline-none"
                            />
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <label className="block text-xs font-semibold text-slate-700 mb-1.5">Email Address</label>
                                <input
                                    type="email"
                                    value={form.email}
                                    onChange={set('email')}
                                    className="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-[13px] outline-none"
                                />
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-slate-700 mb-1.5">Mobile Number</label>
                                <input
                                    type="text"
                                    value={form.phone}
                                    onChange={set('phone')}
                                    className="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-[13px] outline-none"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <label className="block text-xs font-semibold text-slate-700 mb-1.5">Check-In Date</label>
                                <input
                                    type="date"
                                    value={form.check_in}
                                    onChange={set('check_in')}
                                    className="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-[13px] outline-none"
                                />
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-slate-700 mb-1.5">Time-In</label>
                                <input
                                    type="time"
                                    value={form.check_in_time}
                                    onChange={set('check_in_time')}
                                    className="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-[13px] outline-none"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <label className="block text-xs font-semibold text-slate-700 mb-1.5">Check-Out Date</label>
                                <input
                                    type="date"
                                    value={form.check_out}
                                    onChange={set('check_out')}
                                    placeholder="Open-ended"
                                    className="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-[13px] outline-none"
                                />
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-slate-700 mb-1.5">Time-Out</label>
                                <input
                                    type="time"
                                    value={form.check_out_time}
                                    onChange={set('check_out_time')}
                                    className="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-[13px] outline-none"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <label className="block text-xs font-semibold text-slate-700 mb-1.5">Rent Rate (₱)</label>
                                <input
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    value={form.rent_rate}
                                    onChange={set('rent_rate')}
                                    className="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-[13px] outline-none"
                                />
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-slate-700 mb-1.5">Status</label>
                                <select
                                    value={form.status}
                                    onChange={set('status')}
                                    className="w-full border border-slate-300 rounded-lg px-3.5 py-2.5 text-[13px] outline-none bg-white"
                                >
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div className="p-5 border-t border-slate-100 flex justify-end gap-3">
                        <button
                            onClick={() => !saving && setShowEdit(false)}
                            disabled={saving}
                            className="text-slate-600 text-[13px] font-semibold px-4 py-2 disabled:opacity-50"
                        >
                            Cancel
                        </button>
                        <button
                            onClick={handleSave}
                            disabled={saving}
                            className="bg-[#02122c] text-white px-5 py-2.5 rounded-lg text-[13px] font-semibold disabled:opacity-60 inline-flex items-center gap-2"
                        >
                            {saving && <i className="fa-solid fa-spinner fa-spin"></i>}
                            {saving ? 'Saving…' : 'Save Changes'}
                        </button>
                    </div>
                </Modal>
            )}
        </>
    );
}