// resources/js/pages/Admin/VirtualTourModals.jsx
import React, { useState, useEffect, useRef } from "react";
import Modal from "../../components/Modal";

const csrf = () =>
    document.querySelector('meta[name="csrf-token"]')?.content || "";
const routes = () => window.__VT_ROUTES__ || {};

export default function VirtualTourModals() {
    const [modal, setModal] = useState(null);
    const [tour, setTour] = useState(null);
    const [rooms, setRooms] = useState([]);
    const [form, setForm] = useState(emptyForm());
    const [saving, setSaving] = useState(false);
    const [errors, setErrors] = useState({});

    const abortRef = useRef(null);

    function emptyForm() {
        return {
            room_id: "",
            title: "",
            description: "",
            status: "Draft",
            thumbnail: null,
        };
    }

    // Load rooms once on mount
    useEffect(() => {
        const dataEl = document.getElementById("vt-rooms-data");
        if (dataEl) {
            try {
                setRooms(JSON.parse(dataEl.dataset.rooms || "[]"));
            } catch (e) {
                console.error("Invalid rooms JSON", e);
            }
        }
    }, []);

    // Event delegation for triggers
    useEffect(() => {
        const handler = (e) => {
            if (e.target.closest("#admin-open-add-tour")) {
                setTour(null);
                setForm(emptyForm());
                setErrors({});
                setModal("add");
                return;
            }
            const editBtn = e.target.closest(".edit-tour-btn");
            if (editBtn) {
                const data = JSON.parse(editBtn.dataset.tour || "{}");
                setTour(data);
                setForm({
                    room_id: data.room_id,
                    title: data.title || "",
                    description: data.description || "",
                    status: data.status || "Draft",
                    thumbnail: null,
                });
                setErrors({});
                setModal("edit");
                return;
            }
            const viewBtn = e.target.closest(".view-tour-btn");
            if (viewBtn) {
                setTour(JSON.parse(viewBtn.dataset.tour || "{}"));
                setModal("view");
                return;
            }
            const delBtn = e.target.closest(".delete-tour-btn");
            if (delBtn) {
                setTour({
                    id: delBtn.dataset.tourId,
                    roomId: delBtn.dataset.roomId, // <-- add data-room-id in Blade
                    title: delBtn.dataset.tourTitle,
                });
                setModal("delete");
                return;
            }
        };
        document.addEventListener("click", handler);
        return () => document.removeEventListener("click", handler);
    }, []);

    const updateField = (key, value) =>
        setForm((f) => ({ ...f, [key]: value }));

    const onRoomChange = (e) => {
        const value = e.target.value;
        updateField("room_id", value);
        if (!tour && value) {
            const selected = e.target.options[e.target.selectedIndex];
            updateField(
                "title",
                `${selected.dataset.roomType || ""} – ${selected.dataset.roomNumber || ""}`.trim(),
            );
        }
    };

    // ------------------------------------------------------------
    // LOCAL DOM PATCHERS (no full reload)
    // ------------------------------------------------------------
    const patchStats = (stats) => {
        if (!stats) return;
        document
            .querySelectorAll('[data-stat="total"]')
            .forEach((el) => (el.textContent = stats.total));
        document
            .querySelectorAll('[data-stat="published"]')
            .forEach((el) => (el.textContent = stats.published));
        document
            .querySelectorAll('[data-stat="draft"]')
            .forEach((el) => (el.textContent = stats.draft));
        document
            .querySelectorAll('[data-stat="hotspots"]')
            .forEach((el) => (el.textContent = stats.hotspots));
    };

    const removeTourRow = (id) => {
        document.querySelector(`.tour-item[data-tour-id="${id}"]`)?.remove();
        const list = document.getElementById("tours-list-container");
        if (list && list.querySelectorAll(".tour-item").length === 0) {
            list.innerHTML = `
                <div class="text-center py-16 bg-white rounded-2xl border border-dashed border-slate-200">
                    <i class="fa-solid fa-folder-open text-4xl text-slate-300 mb-3"></i>
                    <p class="text-slate-500 font-medium">No virtual tours found.</p>
                    <p class="text-slate-400 text-sm mt-1">Click "Add Room" to create your first virtual tour.</p>
                </div>`;
        }
    };

    // NEW: re-enable room in dropdown after delete or remove on add
    const patchRoomOption = (roomId, hasTour, newTourId = null) => {
        if (!roomId) return;
        // Update React state so reopening add modal reflects it
        setRooms((prev) =>
            prev.map((r) =>
                String(r.id) === String(roomId)
                    ? { ...r, has_tour: hasTour, tour_id: hasTour ? newTourId : null }
                    : r,
            ),
        );
        // Also patch any currently-rendered select (in case the add modal is open in another tab/instance)
        const opt = document.querySelector(
            `#vt-room-select option[value="${roomId}"]`,
        );
        if (opt) {
            opt.disabled = hasTour;
            const base = opt.textContent.replace(" [Has Tour]", "");
            opt.textContent = hasTour ? `${base} [Has Tour]` : base;
        }
    };

    const patchTourRow = (updated) => {
        const row = document.querySelector(
            `.tour-item[data-tour-id="${updated.id}"]`,
        );
        if (!row) return;
        row.dataset.title = (updated.title || "").toLowerCase();
        row.dataset.status = updated.status || "";
        const titleEl = row.querySelector("h4");
        if (titleEl) titleEl.textContent = updated.title || "";
        const statusPill = row.querySelector("[data-status-pill]");
        if (statusPill) {
            const s = updated.status;
            statusPill.className =
                s === "Published"
                    ? "inline-flex items-center gap-1.5 bg-emerald-100 text-emerald-600 text-[11.5px] font-bold px-3 py-1 rounded-xl"
                    : s === "Draft"
                      ? "inline-flex items-center gap-1.5 bg-yellow-100 text-amber-700 text-[11.5px] font-bold px-3 py-1 rounded-xl"
                      : "inline-flex items-center gap-1.5 bg-slate-100 text-slate-600 text-[11.5px] font-bold px-3 py-1 rounded-xl";
            statusPill.innerHTML = `<i class="fa-solid fa-circle" style="font-size:6px;"></i> ${s}`;
        }
        const img = row.querySelector("img");
        if (img && updated.thumbnail_url) img.src = updated.thumbnail_url;
        const editBtn = row.querySelector(".edit-tour-btn");
        if (editBtn) editBtn.dataset.tour = JSON.stringify(updated);
        const viewBtn = row.querySelector(".view-tour-btn");
        if (viewBtn) {
            try {
                const existing = JSON.parse(viewBtn.dataset.tour || "{}");
                viewBtn.dataset.tour = JSON.stringify({ ...existing, ...updated });
            } catch {
                viewBtn.dataset.tour = JSON.stringify(updated);
            }
        }
    };

    // ------------------------------------------------------------
    // SUBMIT (Add / Edit)
    // ------------------------------------------------------------
    const submitForm = async () => {
        if (saving) return;
        setSaving(true);
        setErrors({});

        const fd = new FormData();
        fd.append("title", form.title);
        fd.append("description", form.description || "");
        fd.append("status", form.status);

        if (modal === "add") {
            fd.append("room_id", form.room_id);
        }
        if (form.thumbnail instanceof File) {
            fd.append("thumbnail", form.thumbnail);
        }

        let url,
            method = "POST";
        if (modal === "edit") {
            url = routes().update.replace(":id", tour.id);
            fd.append("_method", "PUT");
        } else {
            url = routes().store;
        }

        const controller = new AbortController();
        abortRef.current = controller;

        try {
            const res = await fetch(url, {
                method,
                headers: {
                    "X-CSRF-TOKEN": csrf(),
                    Accept: "application/json",
                },
                body: fd,
                signal: controller.signal,
            });

            const data = await res.json().catch(() => ({}));

            if (!res.ok) {
                if (res.status === 422) setErrors(data.errors || {});
                return;
            }

            // ---------- PATCH DOM, NO RELOAD ----------
            if (data.stats) patchStats(data.stats);

            if (modal === "edit" && data.tour) {
                patchTourRow(data.tour);
            } else if (modal === "add" && data.tour) {
                // Reload once: list order + index numbers change, and dropdown must mark room as has_tour
                // Mark room in state first so the reload shows it correctly even if backend lags
                patchRoomOption(form.room_id, true, data.tour.id);
                window.location.reload();
                return;
            }

            setModal(null);
        } catch (err) {
            if (err.name === "AbortError") return;
            console.error(err);
        } finally {
            setSaving(false);
            abortRef.current = null;
        }
    };

    // ------------------------------------------------------------
    // DELETE
    // ------------------------------------------------------------
    const confirmDelete = async () => {
        if (saving) return;
        setSaving(true);

        try {
            const res = await fetch(routes().destroy.replace(":id", tour.id), {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": csrf(),
                    Accept: "application/json",
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({ _method: "DELETE" }),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) return;

            // ---------- PATCH DOM, NO RELOAD ----------
            removeTourRow(tour.id);
            if (data.stats) patchStats(data.stats);

            // NEW: Re-enable the room in the dropdown so it can be selected again
            const roomId = tour.roomId || data.room_id;
            if (roomId) patchRoomOption(roomId, false);

            setModal(null);
        } catch (err) {
            console.error(err);
        } finally {
            setSaving(false);
        }
    };

    const onThumbChange = (e) => {
        const file = e.target.files[0];
        if (!file) return;
        if (file.size > 10 * 1024 * 1024) {
            e.target.value = "";
            return;
        }
        updateField("thumbnail", file);
    };

    const openVrTour = (e) => {
        e.preventDefault();
        if (!tour || !tour.id) return;
        const baseUrl = routes().viewer.replace(":id", tour.id);
        const fromPath = routes().fromPath || "";
        window.location.href = fromPath
            ? `${baseUrl}?from=${encodeURIComponent(fromPath)}`
            : baseUrl;
    };

    if (!modal) return null;

    // ============================================================
    // ADD / EDIT MODAL
    // ============================================================
    if (modal === "add" || modal === "edit") {
        const isEdit = modal === "edit";
        return (
            <Modal onClose={() => setModal(null)} maxWidth="max-w-[820px]">
                <div className="p-6 pb-4 flex items-center justify-between border-b border-slate-100 shrink-0">
                    <h3 className="text-lg font-bold text-slate-900">
                        {isEdit ? "Edit Virtual Tour" : "Add New Virtual Tour"}
                    </h3>
                    <button
                        onClick={() => setModal(null)}
                        className="text-slate-400 hover:text-slate-700 text-lg"
                    >
                        <i className="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div className="p-6 overflow-y-auto max-h-[60vh]">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div className="flex flex-col gap-4">
                            <div>
                                <label className="block text-xs font-semibold text-slate-600 mb-1.5">
                                    Select Room *
                                </label>
                                <select
                                    id="vt-room-select"
                                    value={form.room_id}
                                    onChange={onRoomChange}
                                    disabled={isEdit}
                                    className={`w-full border rounded-lg px-3 py-2.5 text-[13px] outline-none bg-white ${
                                        errors.room_id
                                            ? "border-rose-400"
                                            : "border-slate-300"
                                    } ${isEdit ? "bg-slate-50 text-slate-500 cursor-not-allowed" : ""}`}
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
                                            {!isEdit && r.has_tour ? " [Has Tour]" : ""}
                                        </option>
                                    ))}
                                </select>
                                {errors.room_id && (
                                    <p className="text-[11px] text-rose-500 mt-1">
                                        {errors.room_id[0]}
                                    </p>
                                )}
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-slate-600 mb-1.5">
                                    Tour Title *
                                </label>
                                <input
                                    type="text"
                                    value={form.title}
                                    onChange={(e) => updateField("title", e.target.value)}
                                    placeholder="e.g. Standard Solo Room"
                                    className={`w-full border rounded-lg px-3 py-2.5 text-[13px] outline-none ${
                                        errors.title ? "border-rose-400" : "border-slate-300"
                                    }`}
                                />
                                {errors.title && (
                                    <p className="text-[11px] text-rose-500 mt-1">
                                        {errors.title[0]}
                                    </p>
                                )}
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-slate-600 mb-1.5">
                                    Description
                                </label>
                                <textarea
                                    value={form.description}
                                    onChange={(e) => updateField("description", e.target.value)}
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
                                        onClick={() => updateField("status", "Draft")}
                                        className={`flex-1 py-2 rounded-lg text-xs font-semibold inline-flex items-center justify-center gap-1.5 transition ${
                                            form.status === "Draft"
                                                ? "bg-amber-100 text-amber-700"
                                                : "text-slate-500 hover:bg-white"
                                        }`}
                                    >
                                        <i className="fa-regular fa-circle-question"></i> Draft
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => updateField("status", "Published")}
                                        className={`flex-1 py-2 rounded-lg text-xs font-semibold inline-flex items-center justify-center gap-1.5 transition ${
                                            form.status === "Published"
                                                ? "bg-emerald-100 text-emerald-700"
                                                : "text-slate-500 hover:bg-white"
                                        }`}
                                    >
                                        <i className="fa-regular fa-circle-check"></i> Published
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div className="flex flex-col gap-4">
                            <label className="block text-xs font-semibold text-slate-600">
                                Cover Thumbnail{" "}
                                {isEdit && <span className="text-slate-400">(optional)</span>}
                            </label>

                            <label
                                htmlFor="vt-thumb-input"
                                className="border-[1.5px] border-dashed border-slate-300 rounded-xl p-6 text-center cursor-pointer hover:border-slate-400 transition block"
                            >
                                <i className="fa-solid fa-cloud-arrow-up text-slate-400 text-2xl mb-2"></i>
                                <p className="text-[13px] font-semibold text-slate-700">
                                    Click to upload {isEdit ? "new thumbnail" : "360° image"}
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
                                    <p className="text-[11px] text-slate-500 mb-1.5">
                                        Current thumbnail:
                                    </p>
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
                        className="bg-[#0b1930] hover:bg-[#162640] disabled:opacity-50 disabled:cursor-not-allowed text-white px-5 py-2.5 rounded-[10px] text-[13.5px] font-semibold inline-flex items-center gap-2"
                    >
                        {saving ? (
                            <>
                                <i className="fa-solid fa-spinner fa-spin"></i> Saving…
                            </>
                        ) : (
                            <>
                                <i className="fa-solid fa-floppy-disk"></i> Save Tour
                            </>
                        )}
                    </button>
                </div>
            </Modal>
        );
    }

    // ============================================================
    // VIEW MODAL
    // ============================================================
    if (modal === "view" && tour) {
        return (
            <Modal onClose={() => setModal(null)} maxWidth="max-w-[540px]">
                <div
                    onClick={(e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        openVrTour(e);
                    }}
                    className="relative h-[220px] bg-black shrink-0 block cursor-pointer group"
                    title="Click to open full 360° tour"
                >
                    <img
                        src={
                            tour.thumbnail_url ||
                            "https://via.placeholder.com/600x300?text=360+Tour"
                        }
                        alt="Tour"
                        className="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                    />
                    <div className="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                        <span className="bg-white/90 text-slate-900 text-sm font-bold px-4 py-2 rounded-full inline-flex items-center gap-2 shadow-lg">
                            <i className="fa-solid fa-vr-cardboard"></i> Click to view Full VR Tour
                        </span>
                    </div>
                    <span className="absolute top-3 right-3 bg-black/70 text-white text-[11px] font-bold px-3 py-1 rounded-lg backdrop-blur pointer-events-none">
                        360° View
                    </span>
                    <div className="absolute bottom-0 left-0 right-0 p-4 bg-gradient-to-t from-black/80 to-transparent pointer-events-none">
                        <h3 className="text-white text-lg font-bold">{tour.title}</h3>
                        <div className="flex gap-2 mt-2">
                            <span className="bg-indigo-500 text-white text-[11px] font-bold px-2.5 py-0.5 rounded-lg">
                                {tour.room_number}
                            </span>
                            <span
                                className={`text-white text-[11px] font-bold px-2.5 py-0.5 rounded-lg ${
                                    tour.status === "Published"
                                        ? "bg-emerald-500"
                                        : tour.status === "Draft"
                                          ? "bg-amber-500"
                                          : "bg-slate-500"
                                }`}
                            >
                                {tour.status}
                            </span>
                        </div>
                    </div>
                </div>

                <div className="p-5 overflow-y-auto max-h-[60vh]">
                    <p className="text-[13px] text-slate-500 mb-4">
                        {tour.description || "No description provided."}
                    </p>
                    <h4 className="text-[13px] font-bold text-slate-800 mb-3">
                        Tour Specifications
                    </h4>
                    <div className="grid grid-cols-3 gap-3">
                        <div className="bg-slate-50 rounded-xl p-3 text-center">
                            <i className="fa-solid fa-cubes text-slate-400"></i>
                            <p className="text-[10px] font-bold text-slate-400 uppercase mt-1">
                                Scenes
                            </p>
                            <p className="text-sm font-bold text-slate-800">
                                {tour.scenes_count || 0}
                            </p>
                        </div>
                        <div className="bg-slate-50 rounded-xl p-3 text-center">
                            <i className="fa-solid fa-location-dot text-slate-400"></i>
                            <p className="text-[10px] font-bold text-slate-400 uppercase mt-1">
                                Room
                            </p>
                            <p className="text-sm font-bold text-slate-800">
                                {tour.room_number}
                            </p>
                        </div>
                        <div className="bg-slate-50 rounded-xl p-3 text-center">
                            <i className="fa-solid fa-calendar text-slate-400"></i>
                            <p className="text-[10px] font-bold text-slate-400 uppercase mt-1">
                                Created
                            </p>
                            <p className="text-sm font-bold text-slate-800">
                                {tour.created_at_formatted}
                            </p>
                        </div>
                    </div>
                </div>

                <div className="flex items-center justify-between px-5 py-4 border-t border-slate-100 bg-slate-50 shrink-0">
                    <button
                        type="button"
                        onClick={(e) => {
                            e.stopPropagation();
                            openVrTour(e);
                        }}
                        className="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-[13px] font-bold inline-flex items-center gap-2 transition shadow-sm"
                    >
                        <i className="fa-solid fa-vr-cardboard"></i> Open Full VR Tour
                    </button>
                    <div className="flex items-center gap-3">
                        <button
                            type="button"
                            onClick={() => {
                                const editBtn = document.querySelector(
                                    `.edit-tour-btn[data-tour*='"id":${tour.id}']`,
                                );
                                setModal(null);
                                if (editBtn) setTimeout(() => editBtn.click(), 100);
                            }}
                            className="text-[13px] font-semibold text-emerald-600 hover:text-emerald-700"
                        >
                            Edit Tour
                        </button>
                        <button
                            type="button"
                            onClick={() => setModal(null)}
                            className="bg-[#0b1930] hover:bg-[#162640] text-white px-5 py-2 rounded-[10px] text-[13px] font-semibold"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </Modal>
        );
    }

    // ============================================================
    // DELETE MODAL
    // ============================================================
    if (modal === "delete" && tour) {
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
                            This will also delete all scenes, hotspots, and uploaded images.{" "}
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
                                <i className="fa-solid fa-spinner fa-spin"></i> Deleting…
                            </>
                        ) : (
                            <>
                                <i className="fa-solid fa-trash-can"></i> Yes, Delete
                            </>
                        )}
                    </button>
                </div>
            </Modal>
        );
    }

    return null;
}