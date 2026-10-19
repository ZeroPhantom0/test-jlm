// resources/js/pages/Admin/RoomsModals.jsx
import React, { useState, useEffect, useCallback, useMemo } from 'react';
import Modal from '../../components/Modal';

const csrf = () =>
    document.querySelector('meta[name="csrf-token"]')?.content ||
    document.querySelector('input[name="_token"]')?.value;

const computeTotalBeds = (upper, lower) => {
    const u = parseInt(upper, 10) || 0;
    const l = parseInt(lower, 10) || 0;
    return u + l;
};

const computeRoomBedId = (roomNumber, upper, lower) => {
    const u = parseInt(upper, 10) || 0;
    const l = parseInt(lower, 10) || 0;
    const total = u + l;

    if (!roomNumber || total === 0) return '';

    let suffix = 'M';
    if (u > l) suffix = 'U';
    else if (l > u) suffix = 'L';

    return `R${roomNumber}-B${total}-${suffix}`;
};

const digitsOnly = (val) => String(val).replace(/\D/g, '');

export default function RoomsModals() {
    const [showAdd, setShowAdd] = useState(false);
    const [showView, setShowView] = useState(false);
    const [showEdit, setShowEdit] = useState(false);
    const [room, setRoom] = useState(null);
    const [form, setForm] = useState(emptyForm());
    const [saving, setSaving] = useState(false);

    function emptyForm() {
        return {
            room_number: '',
            floor: 1,
            room_type: 'Solo Room',
            description: '',
            total_beds: 0,
            upper_deck: '',
            lower_deck: '',
            daily_rent: '',
            status: 'Available',
            room_bed_id: '',
            tenant_name: '',
        };
    }

    // ------- Auto-calc -------
    const derivedTotalBeds = useMemo(
        () => computeTotalBeds(form.upper_deck, form.lower_deck),
        [form.upper_deck, form.lower_deck]
    );

    const derivedRoomBedId = useMemo(
        () => computeRoomBedId(form.room_number, form.upper_deck, form.lower_deck),
        [form.room_number, form.upper_deck, form.lower_deck]
    );

    useEffect(() => {
        setForm((f) => {
            if (f.total_beds === derivedTotalBeds && f.room_bed_id === derivedRoomBedId) {
                return f;
            }
            return { ...f, total_beds: derivedTotalBeds, room_bed_id: derivedRoomBedId };
        });
    }, [derivedTotalBeds, derivedRoomBedId]);

    // ------- Search + filters -------
    const applyFilters = useCallback(() => {
        const searchEl = document.getElementById('room-search');
        const statusEl = document.getElementById('room-filter-status');
        const typeEl   = document.getElementById('room-filter-type');
        const tbody    = document.getElementById('rooms-tbody');
        if (!tbody) return;

        const q      = (searchEl?.value || '').trim().toLowerCase();
        const status = statusEl?.value || '';
        const type   = typeEl?.value || '';

        const rows = tbody.querySelectorAll('tr[data-room]');
        let visibleCount = 0;

        rows.forEach((row) => {
            const rowSearch = (row.dataset.search || '').toLowerCase();
            const rowStatus = row.dataset.status || '';
            const rowType   = row.dataset.type || '';

            const matchSearch = !q || rowSearch.includes(q);
            const matchStatus = !status || rowStatus === status;
            const matchType   = !type || rowType === type;

            if (matchSearch && matchStatus && matchType) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        // Empty-state row toggle
        let emptyRow = tbody.querySelector('tr[data-empty-state]');
        if (visibleCount === 0 && rows.length > 0) {
            if (!emptyRow) {
                emptyRow = document.createElement('tr');
                emptyRow.setAttribute('data-empty-state', '1');
                emptyRow.innerHTML = `<td colspan="7" class="py-10 text-center text-slate-400 text-sm">No rooms match your search or filters.</td>`;
                tbody.appendChild(emptyRow);
            }
            emptyRow.style.display = '';
        } else if (emptyRow) {
            emptyRow.style.display = 'none';
        }
    }, []);

    useEffect(() => {
        const searchEl = document.getElementById('room-search');
        const statusEl = document.getElementById('room-filter-status');
        const typeEl   = document.getElementById('room-filter-type');

        searchEl?.addEventListener('input', applyFilters);
        statusEl?.addEventListener('change', applyFilters);
        typeEl?.addEventListener('change', applyFilters);

        return () => {
            searchEl?.removeEventListener('input', applyFilters);
            statusEl?.removeEventListener('change', applyFilters);
            typeEl?.removeEventListener('change', applyFilters);
        };
    }, [applyFilters]);

    // ------- Row data -------
    const getRowData = (id) => {
        const tr = document.querySelector(`.view-room-btn[data-id="${id}"]`)?.closest('tr')
                || document.querySelector(`.edit-room-btn[data-id="${id}"]`)?.closest('tr');
        if (!tr) return null;
        try { return JSON.parse(tr.dataset.room); } catch { return null; }
    };

    const openAdd = useCallback(() => {
        setRoom(null);
        setForm(emptyForm());
        setShowAdd(true);
    }, []);

    const openView = useCallback((id) => {
        const data = getRowData(id);
        setRoom(data);
        setShowView(true);
    }, []);

    const openEdit = useCallback((id) => {
        const data = getRowData(id);
        if (!data) return;
        setRoom(data);
        setForm({ ...emptyForm(), ...data });
        setShowEdit(true);
    }, []);

    useEffect(() => {
        const handleClick = (e) => {
            if (e.target.closest('#open-add-room-btn')) return openAdd();
            const viewBtn = e.target.closest('.view-room-btn');
            if (viewBtn) return openView(viewBtn.dataset.id);
            const editBtn = e.target.closest('.edit-room-btn');
            if (editBtn) return openEdit(editBtn.dataset.id);
            const delBtn = e.target.closest('.delete-room-btn');
            if (delBtn) return deleteRoom(delBtn.dataset.id);
        };
        document.addEventListener('click', handleClick);
        return () => document.removeEventListener('click', handleClick);
    }, [openAdd, openView, openEdit]);

    const updateField = (key, value) => setForm((f) => ({ ...f, [key]: value }));

    const updateDeck = (key, rawValue) => {
        const cleaned = digitsOnly(rawValue);
        updateField(key, cleaned);
    };

    const submitCreate = async () => {
        setSaving(true);
        try {
            const payload = {
                ...form,
                total_beds: derivedTotalBeds,
                room_bed_id: derivedRoomBedId,
                upper_deck: parseInt(form.upper_deck, 10) || 0,
                lower_deck: parseInt(form.lower_deck, 10) || 0,
            };
            const res = await fetch('/admin/rooms', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                },
                body: JSON.stringify(payload),
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Failed to create room.');
            setShowAdd(false);
            window.location.reload();
        } catch (err) {
            alert(err.message);
        } finally {
            setSaving(false);
        }
    };

    const submitEdit = async () => {
        if (!room) return;
        setSaving(true);
        try {
            const payload = {
                ...form,
                total_beds: derivedTotalBeds,
                room_bed_id: derivedRoomBedId,
                upper_deck: parseInt(form.upper_deck, 10) || 0,
                lower_deck: parseInt(form.lower_deck, 10) || 0,
            };
            const res = await fetch(`/admin/rooms/${room.id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                },
                body: JSON.stringify(payload),
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Failed to update room.');
            setShowEdit(false);
            window.location.reload();
        } catch (err) {
            alert(err.message);
        } finally {
            setSaving(false);
        }
    };

    const deleteRoom = async (id) => {
        if (!confirm('Delete this room permanently?')) return;
        try {
            const res = await fetch(`/admin/rooms/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                },
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Failed to delete room.');
            window.location.reload();
        } catch (err) {
            alert(err.message);
        }
    };

    return (
        <>
            {showAdd && (
                <Modal onClose={() => setShowAdd(false)} maxWidth="max-w-[820px]">
                    <div className="p-6 pb-4 flex items-center justify-between border-b border-slate-100">
                        <h3 className="text-lg font-bold text-slate-900">Add New Room</h3>
                        <button onClick={() => setShowAdd(false)} className="text-slate-400 hover:text-slate-700 text-lg">
                            <i className="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div className="p-6 overflow-y-auto">
                        <h4 className="text-sm font-bold text-slate-900 mb-4">Room Information</h4>
                        <div className="grid grid-cols-3 gap-3.5 mb-4">
                            <div>
                                <label className="block text-xs font-semibold text-slate-600 mb-1.5">Room Number</label>
                                <input
                                    type="text"
                                    value={form.room_number}
                                    onChange={(e) => updateField('room_number', digitsOnly(e.target.value))}
                                    placeholder="e.g. 101"
                                    inputMode="numeric"
                                    className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none focus:border-blue-400"
                                />
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-slate-600 mb-1.5">Floor</label>
                                <select
                                    value={form.floor}
                                    onChange={(e) => updateField('floor', parseInt(e.target.value))}
                                    className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none bg-white"
                                >
                                    <option value={1}>Floor 1</option>
                                    <option value={2}>Floor 2</option>
                                    <option value={3}>Floor 3</option>
                                </select>
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-slate-600 mb-1.5">Room Type</label>
                                <select
                                    value={form.room_type}
                                    onChange={(e) => updateField('room_type', e.target.value)}
                                    className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none bg-white"
                                >
                                    <option>Solo Room</option>
                                    <option>Bedspace</option>
                                    <option>Barkada Room</option>
                                </select>
                            </div>
                        </div>
                        <div className="mb-4">
                            <label className="block text-xs font-semibold text-slate-600 mb-1.5">Room Description</label>
                            <textarea
                                value={form.description}
                                onChange={(e) => updateField('description', e.target.value)}
                                placeholder="Describe room here.."
                                className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none resize-y min-h-[80px]"
                            ></textarea>
                        </div>

                        <h4 className="text-sm font-bold text-slate-900 mb-4">Capacity / Bed Configuration</h4>
                        <div className="grid grid-cols-3 gap-3.5 mb-4">
                            <div>
                                <label className="block text-xs font-semibold text-slate-600 mb-1.5">
                                    Total Beds
                                    <span className="ml-1.5 text-[10px] font-normal text-slate-400">(auto)</span>
                                </label>
                                <input
                                    type="text"
                                    value={derivedTotalBeds}
                                    readOnly
                                    tabIndex={-1}
                                    className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-[13px] bg-slate-50 text-slate-500 cursor-not-allowed"
                                />
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-slate-600 mb-1.5">Upper Deck</label>
                                <input
                                    type="text"
                                    inputMode="numeric"
                                    pattern="[0-9]*"
                                    value={form.upper_deck}
                                    onChange={(e) => updateDeck('upper_deck', e.target.value)}
                                    placeholder="0"
                                    className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none focus:border-blue-400"
                                />
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-slate-600 mb-1.5">Lower Deck</label>
                                <input
                                    type="text"
                                    inputMode="numeric"
                                    pattern="[0-9]*"
                                    value={form.lower_deck}
                                    onChange={(e) => updateDeck('lower_deck', e.target.value)}
                                    placeholder="0"
                                    className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none focus:border-blue-400"
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-slate-600 mb-1.5">Daily Rent (₱)</label>
                                <input
                                    type="text"
                                    inputMode="decimal"
                                    value={form.daily_rent}
                                    onChange={(e) => updateField('daily_rent', e.target.value.replace(/[^\d.]/g, ''))}
                                    placeholder="Enter Amount"
                                    className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none focus:border-blue-400"
                                />
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-slate-600 mb-1.5">Room Status</label>
                                <select
                                    value={form.status}
                                    onChange={(e) => updateField('status', e.target.value)}
                                    className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none bg-white"
                                >
                                    <option>Available</option>
                                    <option>Occupied</option>
                                    <option>Reserved</option>
                                </select>
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-slate-600 mb-1.5">
                                    Room / Bed ID
                                    <span className="ml-1.5 text-[10px] font-normal text-slate-400">(auto)</span>
                                </label>
                                <input
                                    type="text"
                                    value={derivedRoomBedId}
                                    readOnly
                                    tabIndex={-1}
                                    placeholder="—"
                                    className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-[13px] bg-slate-50 text-slate-500 cursor-not-allowed"
                                />
                            </div>
                        </div>
                    </div>
                    <div className="p-6 border-t border-slate-100 flex justify-end gap-3">
                        <button onClick={() => setShowAdd(false)} className="text-slate-600 text-[13.5px] font-semibold px-4 py-2">Cancel</button>
                        <button disabled={saving} onClick={submitCreate} className="bg-[#02122c] hover:bg-[#0d2347] disabled:opacity-50 text-white px-5 py-2.5 rounded-lg text-[13.5px] font-semibold transition">
                            {saving ? 'Creating…' : 'Create Room'}
                        </button>
                    </div>
                </Modal>
            )}

            {showView && room && (
                <Modal onClose={() => setShowView(false)} maxWidth="max-w-[820px]">
                    <div className="p-6 pb-4 flex items-center justify-between border-b border-slate-100">
                        <h3 className="text-lg font-bold text-slate-900">Room {room.room_number} – Details</h3>
                        <button onClick={() => setShowView(false)} className="text-slate-400 hover:text-slate-700 text-lg">
                            <i className="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div className="p-6 overflow-y-auto">
                        <div className="flex items-center justify-between mb-3">
                            <div className="flex items-center gap-2">
                                <span className="inline-block px-3 py-1.5 rounded-md text-[10.5px] font-extrabold tracking-wider uppercase bg-blue-100 text-blue-700">{room.room_type}</span>
                                <span className="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-semibold bg-green-100 text-green-600">
                                    <i className="fa-solid fa-check"></i> {room.status}
                                </span>
                            </div>
                            <div>
                                <span className="text-[22px] font-extrabold text-green-600">₱{Number(room.daily_rent).toLocaleString()}</span>
                                <small className="text-[11px] text-slate-500 ml-1">per day</small>
                            </div>
                        </div>
                        <p className="text-[11px] text-slate-400 mb-4">Floor {room.floor} • Room {room.room_number}</p>
                        {room.description && (
                            <div className="bg-slate-50 rounded-lg p-3.5 text-[13px] text-slate-600 leading-snug mb-4">{room.description}</div>
                        )}

                        <h4 className="text-sm font-bold text-slate-900 mb-3">Room Specifications</h4>
                        <div className="grid grid-cols-3 gap-2.5 mb-4">
                            {[
                                { label: 'Room Type', value: room.room_type },
                                { label: 'Beds / Position', value: room.total_beds },
                                { label: 'Room / Bed ID', value: room.room_bed_id || '-' },
                                { label: 'Floor', value: room.floor },
                                { label: 'Available Slots', value: `${room.available_slots ?? 0}/${room.total_beds}` },
                                { label: 'Daily Rate', value: `₱${Number(room.daily_rent).toLocaleString()}` },
                            ].map((spec, i) => (
                                <div key={i} className="bg-slate-50 rounded-lg p-3 text-center">
                                    <div className="text-[9.5px] font-bold text-slate-400 uppercase mb-1">{spec.label}</div>
                                    <div className="text-[12px] font-bold text-slate-900">{spec.value}</div>
                                </div>
                            ))}
                        </div>

                        {room.tenant_name && (
                            <div className="bg-emerald-50 rounded-lg p-3.5 flex items-center gap-3 mb-4">
                                <div className="w-9 h-9 rounded-full bg-emerald-500 text-white flex items-center justify-center font-bold">
                                    {room.tenant_name.charAt(0).toUpperCase()}
                                </div>
                                <div>
                                    <div className="text-[10px] font-extrabold text-emerald-700 tracking-wide uppercase">Current Tenant</div>
                                    <h5 className="text-sm font-bold text-emerald-800">{room.tenant_name}</h5>
                                </div>
                            </div>
                        )}
                    </div>
                    <div className="p-6 border-t border-slate-100 flex justify-end gap-3">
                        <button onClick={() => { setShowView(false); openEdit(room.id); }} className="border border-slate-300 text-slate-700 px-4 py-2 rounded-lg text-[13px] font-medium hover:bg-slate-50">Edit Room</button>
                        <button onClick={() => setShowView(false)} className="bg-[#02122c] text-white px-5 py-2.5 rounded-lg text-[13px] font-semibold">Close</button>
                    </div>
                </Modal>
            )}

            {showEdit && room && (
                <Modal onClose={() => setShowEdit(false)} maxWidth="max-w-[820px]">
                    <div className="p-6 pb-4 flex items-center justify-between border-b border-slate-100">
                        <h3 className="text-lg font-bold text-slate-900">Edit Room {room.room_number}</h3>
                        <button onClick={() => setShowEdit(false)} className="text-slate-400 hover:text-slate-700 text-lg">
                            <i className="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div className="p-6 overflow-y-auto">
                        <div className="grid grid-cols-3 gap-3.5 mb-4">
                            <div>
                                <label className="block text-xs font-semibold text-slate-600 mb-1.5">Room Number</label>
                                <input
                                    type="text"
                                    value={form.room_number ?? ''}
                                    onChange={(e) => updateField('room_number', digitsOnly(e.target.value))}
                                    inputMode="numeric"
                                    className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none"
                                />
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-slate-600 mb-1.5">Floor</label>
                                <select
                                    value={form.floor ?? 1}
                                    onChange={(e) => updateField('floor', parseInt(e.target.value))}
                                    className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none bg-white"
                                >
                                    <option value={1}>Floor 1</option>
                                    <option value={2}>Floor 2</option>
                                    <option value={3}>Floor 3</option>
                                </select>
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-slate-600 mb-1.5">Status</label>
                                <select
                                    value={form.status ?? 'Available'}
                                    onChange={(e) => updateField('status', e.target.value)}
                                    className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none bg-white"
                                >
                                    <option>Available</option>
                                    <option>Occupied</option>
                                    <option>Reserved</option>
                                </select>
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-slate-600 mb-1.5">Upper Deck</label>
                                <input
                                    type="text"
                                    inputMode="numeric"
                                    value={form.upper_deck ?? ''}
                                    onChange={(e) => updateDeck('upper_deck', e.target.value)}
                                    placeholder="0"
                                    className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none"
                                />
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-slate-600 mb-1.5">Lower Deck</label>
                                <input
                                    type="text"
                                    inputMode="numeric"
                                    value={form.lower_deck ?? ''}
                                    onChange={(e) => updateDeck('lower_deck', e.target.value)}
                                    placeholder="0"
                                    className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none"
                                />
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-slate-600 mb-1.5">
                                    Total Beds
                                    <span className="ml-1.5 text-[10px] font-normal text-slate-400">(auto)</span>
                                </label>
                                <input
                                    type="text"
                                    value={derivedTotalBeds}
                                    readOnly
                                    tabIndex={-1}
                                    className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-[13px] bg-slate-50 text-slate-500 cursor-not-allowed"
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-slate-600 mb-1.5">Daily Rate (₱)</label>
                                <input
                                    type="text"
                                    inputMode="decimal"
                                    value={form.daily_rent ?? ''}
                                    onChange={(e) => updateField('daily_rent', e.target.value.replace(/[^\d.]/g, ''))}
                                    className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none"
                                />
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-slate-600 mb-1.5">
                                    Room / Bed ID
                                    <span className="ml-1.5 text-[10px] font-normal text-slate-400">(auto)</span>
                                </label>
                                <input
                                    type="text"
                                    value={derivedRoomBedId}
                                    readOnly
                                    tabIndex={-1}
                                    placeholder="—"
                                    className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-[13px] bg-slate-50 text-slate-500 cursor-not-allowed"
                                />
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-slate-600 mb-1.5">Room Type</label>
                                <select
                                    value={form.room_type ?? 'Solo Room'}
                                    onChange={(e) => updateField('room_type', e.target.value)}
                                    className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none bg-white"
                                >
                                    <option>Solo Room</option>
                                    <option>Bedspace</option>
                                    <option>Barkada Room</option>
                                </select>
                            </div>

                            <div className="col-span-3">
                                <label className="block text-xs font-semibold text-slate-600 mb-1.5">Tenant Name</label>
                                <input
                                    type="text"
                                    value={form.tenant_name ?? ''}
                                    onChange={(e) => updateField('tenant_name', e.target.value)}
                                    className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none"
                                />
                            </div>
                        </div>

                        <div>
                            <label className="block text-xs font-semibold text-slate-600 mb-1.5">Description</label>
                            <textarea
                                value={form.description ?? ''}
                                onChange={(e) => updateField('description', e.target.value)}
                                className="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-[13px] outline-none resize-y min-h-[80px]"
                            ></textarea>
                        </div>
                    </div>
                    <div className="p-6 border-t border-slate-100 flex justify-end gap-3">
                        <button onClick={() => setShowEdit(false)} className="text-slate-600 text-[13.5px] font-semibold px-4 py-2">Cancel</button>
                        <button disabled={saving} onClick={submitEdit} className="bg-[#02122c] disabled:opacity-50 text-white px-5 py-2.5 rounded-lg text-[13.5px] font-semibold">
                            {saving ? 'Saving…' : 'Save Changes'}
                        </button>
                    </div>
                </Modal>
            )}
        </>
    );
}