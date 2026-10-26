{{-- resources/views/admin/virtual-tour-builder.blade.php --}}
@extends('layouts.admin', [
    'active'    => 'virtual-tour',
    'pageTitle' => 'Hotspot Configuration',
])
@section('title', 'Hotspot Configuration — ' . $virtualTour->title)

@section('content')
<div class="builder-wrapper" id="vt-builder-root"
     data-tour-id="{{ $virtualTour->id }}"
     data-scenes="{{ json_encode($virtualTour->scenes->map(fn($s) => [
         'id'    => $s->id,
         'title' => $s->title,
         'image_path' => $s->image_path,
     ])->values(), JSON_HEX_APOS | JSON_HEX_QUOT) }}">

    {{-- ============ TOP TOUR BAR ============ --}}
    <div class="builder-top-bar">
        <div class="builder-top-left">
            <a href="{{ route('admin.virtual-tour.index') }}" class="builder-back-btn">
                <i class="fa-solid fa-arrow-left"></i> Tours
            </a>
            <div>
                <h1 class="builder-title">{{ $virtualTour->title }}</h1>
                <div class="builder-subtitle">
                    Room {{ $virtualTour->room->room_number ?? 'N/A' }} &nbsp;•&nbsp; Hotspot Configuration
                </div>
            </div>
        </div>
        <div class="scene-selector-wrap">
            <label for="scene-selector">Active Scene</label>
            <select id="scene-selector" class="scene-selector-dropdown">
                @forelse($virtualTour->scenes as $s)
                    <option value="{{ $s->id }}">{{ $s->title }}</option>
                @empty
                    <option value="">No scenes yet</option>
                @endforelse
            </select>
        </div>
    </div>

    {{-- ============ CONTENT BODY ============ --}}
    <div class="builder-content-body">

        <div class="hotspot-header-bar">
            <div class="hotspot-title-left">
                <div class="hotspot-title-text">
                    <h3 id="hotspot-header-scene-title">Hotspot Configuration</h3>
                    <p>Manage navigation hotspots for this virtual tour.</p>
                </div>
            </div>
            <div class="tip-banner-box">
                <i class="fa-regular fa-lightbulb"></i>
                <span><strong>Tip:</strong> Click anywhere on the image to place a hotspot.</span>
            </div>
        </div>

        @if($virtualTour->scenes->isEmpty())
            <div class="text-center py-20 bg-white rounded-2xl border border-dashed border-slate-200">
                <i class="fa-solid fa-cubes text-5xl text-slate-300 mb-4"></i>
                <h3 class="text-lg font-bold text-slate-700">No scenes yet</h3>
                <p class="text-slate-500 text-sm mt-1">
                    Upload your first 360° image to start building this tour.
                </p>
                <a href="{{ route('admin.virtual-tour.scenes.index', $virtualTour->id) }}"
                   class="mt-5 inline-flex bg-[#0b1930] hover:bg-[#162640] text-white px-5 py-2.5 rounded-[10px] text-sm font-semibold items-center gap-2 transition shadow-sm">
                    <i class="fa-solid fa-plus"></i> Add First Scene
                </a>
            </div>
        @else
            <div class="hotspot-editor-layout">

                {{-- LEFT: Panorama + thumbnails --}}
                <div class="hotspot-canvas-container">
                    <div class="panorama-viewer-box">
                        <div id="panorama-viewer"></div>

                        <div class="scene-audio-floating">
                            <button id="scene-audio-btn" class="btn" onclick="toggleSceneAudio()">
                                <i class="fa-solid fa-volume-high me-1"></i>
                                <span id="scene-audio-label">Play Scene Audio</span>
                            </button>
                        </div>
                    </div>

                    <div class="thumbnails-row" id="thumbnails-row">
                        @foreach($virtualTour->scenes as $index => $s)
                            <div class="thumb-preview-item {{ $index === 0 ? 'active' : '' }}"
                                 data-scene-id="{{ $s->id }}"
                                 onclick="switchScene({{ $s->id }}, this)">
                                <img src="{{ $s->image_url }}" alt="{{ $s->title }}">
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- RIGHT: Sidebar form --}}
                <div class="hotspot-sidebar-form">

                    <h3 class="sidebar-form-title" id="sidebar-form-title">
                        <span id="sidebar-form-title-text">Add New Hotspot</span>
                        <span class="edit-mode-badge" id="edit-mode-badge">Editing</span>
                    </h3>

                    <div class="edit-mode-banner" id="edit-mode-banner">
                        <span><i class="fa-solid fa-pen-to-square me-1"></i> Editing existing hotspot</span>
                        <button type="button" class="btn-exit-edit" onclick="exitEditMode()">Exit Edit</button>
                    </div>

                    <div class="form-field-group">
                        <label class="field-label">Scene Management</label>
                        <a href="{{ route('admin.virtual-tour.scenes.index', $virtualTour->id) }}" class="manage-scenes-btn">
                            <i class="fa-solid fa-images"></i>
                            <span>Manage Scenes</span>
                            <i class="fa-solid fa-arrow-right arrow-icon"></i>
                        </a>
                    </div>

                    <div class="coords-pill empty" id="coords-pill">
                        <i class="fa-solid fa-location-crosshairs"></i>
                        <span id="coords-text">Click on the panorama to set coordinates</span>
                    </div>

                    <div class="form-field-group">
                        <label class="field-label">Hotspot Name</label>
                        <input
                            type="text"
                            id="hs-name-input"
                            class="input-box"
                            placeholder="e.g. Go to Bedroom or Level 2"
                        />
                        <p class="field-hint" id="name-hint">
                            For <strong>Info</strong> hotspots, this name is displayed above the marker.
                        </p>
                    </div>

                    <div class="form-field-group">
                        <label class="field-label">Hotspot Type</label>
                        <select id="hs-type" class="scene-selector-dropdown" style="width:100%;">
                            <option value="navigation">Scene Navigation</option>
                            <option value="info">Info Tooltip</option>
                        </select>
                    </div>

                    <div class="form-field-group" id="target-scene-group">
                        <label class="field-label">Destination Scene</label>
                        <select id="hs-target-scene" class="scene-selector-dropdown" style="width:100%;">
                            @foreach($virtualTour->scenes as $s)
                                <option value="{{ $s->id }}">{{ $s->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-field-group" id="content-text-group" style="display:none;">
                        <label class="field-label">Info Content</label>
                        <textarea id="hs-content-text" class="textarea-box" rows="3"
                                  placeholder="Text shown when the info hotspot is clicked..."></textarea>
                    </div>

                    <div class="hotspot-form-actions">
                        <button class="btn-cancel-sm" onclick="resetSidebarForm()">Cancel</button>
                        <button class="btn-save-sm" id="hs-save-btn" onclick="saveHotspotFromSidebar()" disabled>Save</button>
                    </div>

                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.css"/>
<style>
:root {
    --navy-blue: #091a34;
    --primary-orange: #f97316;
    --emerald-green: #10b981;
    --border-color: #e2e8f0;
    --text-main: #0f172a;
    --text-muted: #64748b;
}

/* ============ TOP TOUR BAR ============ */
.builder-top-bar {
    background: #ffffff;
    padding: 14px 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid var(--border-color);
    position: sticky; top: 0; z-index: 100;
}
.builder-top-left { display: flex; align-items: center; gap: 14px; }
.builder-back-btn {
    display: inline-flex; align-items: center; gap: 8px;
    background: #ffffff; border: 1px solid var(--border-color);
    border-radius: 8px; padding: 8px 14px;
    font-size: 12.5px; font-weight: 600; color: #334155;
    text-decoration: none; cursor: pointer;
    transition: background 0.15s;
}
.builder-back-btn:hover { background: #f1f5f9; color: #0f172a; }
.builder-title {
    font-size: 16px; font-weight: 700; color: var(--text-main); margin: 0;
}
.builder-subtitle {
    font-size: 12px; color: var(--text-muted); margin-top: 2px;
}
.scene-selector-wrap { display: flex; align-items: center; gap: 10px; }
.scene-selector-wrap label {
    font-size: 12px; font-weight: 700; color: #475569;
    text-transform: uppercase; letter-spacing: .3px;
}
.scene-selector-dropdown {
    background: #f8fafc;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 8px 12px;
    font-size: 13px;
    color: #0f172a;
    outline: none;
    cursor: pointer;
    min-width: 200px;
}

/* ============ CONTENT BODY ============ */
.builder-content-body {
    padding: 24px 28px;
    min-height: calc(100vh - 65px);
}

.hotspot-header-bar {
    display: flex; align-items: center; justify-content: space-between;
    gap: 20px; margin-bottom: 20px;
}
.hotspot-title-left { display: flex; align-items: center; gap: 12px; }
.hotspot-title-text h3 {
    font-size: 20px; font-weight: 700; color: #0f172a; margin: 0;
}
.hotspot-title-text p {
    font-size: 13px; color: #64748b; margin: 2px 0 0 0;
}
.tip-banner-box {
    background-color: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 10px;
    padding: 12px 18px;
    font-size: 13px;
    color: #166534;
    display: flex; align-items: center; gap: 8px;
    font-weight: 500;
}
.tip-banner-box i { color: #10b981; }

.hotspot-editor-layout {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 20px;
    align-items: start;
}

.hotspot-canvas-container {
    background: #ffffff;
    border-radius: 14px;
    border: 1px solid var(--border-color);
    padding: 14px;
    display: flex;
    flex-direction: column;
    gap: 14px;
}
.panorama-viewer-box {
    width: 100%; height: 520px;
    border-radius: 10px;
    position: relative;
    overflow: hidden;
    background: #000;
}
#panorama-viewer { width: 100%; height: 100%; cursor: crosshair !important; }

.scene-audio-floating {
    position: absolute; bottom: 16px; left: 16px; z-index: 100;
}
.scene-audio-floating .btn {
    background: var(--navy-blue); border: none; color: #ffffff;
    font-size: 12px; font-weight: 600; padding: 8px 14px;
    border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.25);
}
.scene-audio-floating .btn:hover { opacity: 0.92; color: #fff; }

.thumbnails-row {
    display: flex; align-items: center; justify-content: center;
    gap: 14px; padding-top: 4px; flex-wrap: wrap;
}
.thumb-preview-item {
    width: 110px; height: 66px; border-radius: 10px;
    overflow: hidden; border: 2px solid transparent;
    cursor: pointer; background: #000;
    transition: border-color .15s, transform .15s; position: relative;
}
.thumb-preview-item:hover { transform: translateY(-2px); }
.thumb-preview-item.active { border-color: var(--primary-orange); }
.thumb-preview-item img { width: 100%; height: 100%; object-fit: cover; display: block; }

.hotspot-sidebar-form {
    background: #ffffff; border-radius: 14px;
    border: 1px solid var(--border-color); padding: 22px;
    display: flex; flex-direction: column; gap: 18px;
    max-height: calc(100vh - 120px); overflow-y: auto;
}
.hotspot-sidebar-form::-webkit-scrollbar { width: 6px; }
.hotspot-sidebar-form::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 10px; }
.hotspot-sidebar-form::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.hotspot-sidebar-form::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

.sidebar-form-title {
    font-size: 16px; font-weight: 700; color: #0f172a; margin: 0;
    display: flex; align-items: center; justify-content: space-between;
    gap: 10px;
}
.sidebar-form-title .edit-mode-badge {
    font-size: 10.5px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .4px;
    padding: 4px 10px; border-radius: 10px;
    background: #fef3c7; color: #b45309;
    border: 1px solid #fde68a;
    display: none;
}
.sidebar-form-title.editing .edit-mode-badge { display: inline-flex; }

.form-field-group { display: flex; flex-direction: column; gap: 8px; }
.field-label { font-size: 13px; font-weight: 600; color: #334155; }
.field-hint { font-size: 11.5px; color: #94a3b8; margin-top: 2px; line-height: 1.4; }

.manage-scenes-btn {
    display: flex; align-items: center; gap: 12px;
    padding: 14px 16px; background: #eff6ff;
    border: 1px solid #bfdbfe; border-radius: 10px;
    text-decoration: none; color: #1e40af;
    font-size: 13.5px; font-weight: 600;
    transition: all 0.15s; cursor: pointer;
}
.manage-scenes-btn:hover {
    background: #dbeafe; border-color: #93c5fd; color: #1e3a8a;
    transform: translateY(-1px); box-shadow: 0 2px 6px rgba(59, 130, 246, 0.15);
}
.manage-scenes-btn i:first-child { font-size: 16px; color: #3b82f6; }
.manage-scenes-btn .arrow-icon {
    margin-left: auto; font-size: 11px; opacity: 0.6; transition: transform 0.15s;
}
.manage-scenes-btn:hover .arrow-icon { transform: translateX(3px); opacity: 1; }

/* Simple text input (replaces formatting-toolbar-box) */
.input-box {
    width: 100%; padding: 12px 14px;
    border: 1px solid var(--border-color); border-radius: 10px;
    font-size: 13.5px; outline: none; color: #0f172a;
    font-family: "Inter", sans-serif;
    background: #ffffff;
}
.input-box:focus { border-color: #94a3b8; }
.input-box::placeholder { color: #94a3b8; }

.textarea-box {
    width: 100%; padding: 12px 14px;
    border: 1px solid var(--border-color); border-radius: 10px;
    font-size: 13.5px; outline: none; color: #0f172a;
    font-family: "Inter", sans-serif; resize: vertical;
    min-height: 90px; background: #ffffff;
}
.textarea-box:focus { border-color: #94a3b8; }

.hotspot-form-actions {
    display: flex; align-items: center; justify-content: flex-end;
    gap: 12px; padding-top: 14px; border-top: 1px solid #f1f5f9;
}
.btn-cancel-sm {
    background: none; border: none;
    font-size: 13.5px; font-weight: 600; color: #64748b;
    cursor: pointer; padding: 10px 16px; border-radius: 8px;
    transition: background .15s;
}
.btn-cancel-sm:hover { background: #f1f5f9; color: #334155; }
.btn-save-sm {
    background: var(--navy-blue); color: #ffffff; border: none;
    border-radius: 10px; padding: 11px 26px;
    font-size: 13.5px; font-weight: 600; cursor: pointer;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1); transition: opacity .15s;
}
.btn-save-sm:hover { opacity: 0.92; }
.btn-save-sm:disabled { opacity: 0.5; cursor: not-allowed; }

.coords-pill {
    background: #eff6ff; border: 1px solid #bfdbfe;
    border-radius: 8px; padding: 8px 12px;
    font-size: 11.5px; color: #1e40af;
    display: flex; align-items: center; gap: 8px; font-weight: 600;
}
.coords-pill i { color: #3b82f6; }
.coords-pill.empty {
    background: #f8fafc; border-color: var(--border-color); color: #94a3b8;
}
.coords-pill.empty i { color: #94a3b8; }

.edit-mode-banner {
    display: none;
    background: #fef3c7;
    border: 1px solid #fde68a;
    border-radius: 10px;
    padding: 10px 14px;
    color: #78350f;
    font-size: 12.5px;
    font-weight: 600;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}
.edit-mode-banner.show { display: flex; }
.edit-mode-banner .btn-exit-edit {
    background: #ffffff;
    border: 1px solid #fcd34d;
    border-radius: 6px;
    font-size: 11.5px;
    font-weight: 700;
    color: #78350f;
    padding: 4px 10px;
    cursor: pointer;
}
.edit-mode-banner .btn-exit-edit:hover { background: #fffbeb; }

/* ============ CUSTOM HOTSPOT MARKERS ============ */
.custom-hotspot { cursor: pointer; }
.custom-hotspot .hotspot-wrapper {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
}

.custom-hotspot .hotspot-icon {
    display: flex; align-items: center; justify-content: center;
    width: 38px; height: 38px;
    color: #ffffff; border-radius: 50%;
    border: 2px solid #ffffff;
    box-shadow: 0 4px 10px rgba(0,0,0,0.35);
    font-size: 15px; transition: transform 0.15s;
}
/* Navigation hotspot = indigo arrow */
.custom-hotspot.type-navigation .hotspot-icon {
    background: rgba(79, 70, 229, 0.95);
}
/* Info hotspot = amber info */
.custom-hotspot.type-info .hotspot-icon {
    background: rgba(245, 158, 11, 0.95);
}

.custom-hotspot:hover .hotspot-icon { transform: scale(1.15); }
.custom-hotspot.is-editing .hotspot-icon {
    background: #f59e0b;
    box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.35), 0 4px 10px rgba(0,0,0,0.35);
}

/* Label badge above the info icon — uses the hotspot's own name (tooltip) */
.custom-hotspot .hotspot-name-label {
    background: #ffffff;
    color: #78350f;
    border: 1px solid #fde68a;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    padding: 2px 9px;
    line-height: 1.4;
    white-space: nowrap;
    max-width: 160px;
    overflow: hidden;
    text-overflow: ellipsis;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
}

.custom-hotspot .delete-hs-btn {
    position: absolute; top: -6px; right: -6px;
    width: 18px; height: 18px;
    background: #dc3545; color: #ffffff;
    border: none; border-radius: 50%;
    font-size: 10px;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.3);
    transition: transform 0.15s; z-index: 5;
}
.custom-hotspot .delete-hs-btn:hover { transform: scale(1.2); }

@media (max-width: 992px) {
    .hotspot-editor-layout { grid-template-columns: 1fr; }
    .panorama-viewer-box { height: 400px; }
}
@media (max-width: 640px) {
    .builder-content-body { padding: 16px; }
    .builder-top-bar { padding: 12px 16px; flex-wrap: wrap; gap: 10px; }
    .hotspot-header-bar { flex-direction: column; align-items: flex-start; }
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.js"></script>
<script>
// ============================================================
// VIRTUAL TOUR BUILDER
// ============================================================

const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';

const ROUTES = {
    getTourData:   '{{ route("admin.virtual-tour.builder.data", $virtualTour->id) }}',
    saveHotspot:   '{{ route("admin.virtual-tour.builder.save-hotspot", $virtualTour->id) }}',
    updateHotspot: '{{ route("admin.virtual-tour.builder.update-hotspot", [$virtualTour->id, 0]) }}',
    deleteHotspot: '{{ route("admin.virtual-tour.builder.delete-hotspot", [$virtualTour->id, 0]) }}',
};

function hotspotUrl(template, hotspotId) {
    return template.replace(/\/0(\/|$)/, '/' + hotspotId + '$1');
}

const sceneTitles = @json($virtualTour->scenes->pluck('title', 'id'));

let viewer = null;
let currentSceneId = {{ $virtualTour->scenes->first()->id ?? 0 }};
let sceneAutoAudioText = '';
let isSceneAudioPlaying = false;

let pendingPitch = null;
let pendingYaw = null;

let editingHotspotId = null;
let currentHotspots = [];

/* ============ VIEWER LOAD ============ */
function loadViewer(sceneId, keepEditState) {
    currentSceneId = sceneId;

    if (!keepEditState) {
        editingHotspotId = null;
    }

    const headerEl = document.getElementById('hotspot-header-scene-title');
    if (headerEl && sceneTitles[sceneId]) {
        headerEl.textContent = 'Hotspot Configuration — ' + sceneTitles[sceneId];
    }

    document.querySelectorAll('.thumb-preview-item').forEach(t => {
        t.classList.toggle('active', parseInt(t.dataset.sceneId) === parseInt(sceneId));
    });

    fetch(ROUTES.getTourData + '?scene_id=' + sceneId, {
        headers: { 'Accept': 'application/json' }
    })
        .then(res => res.json())
        .then(data => {
            if (viewer) viewer.destroy();

            sceneAutoAudioText = data.scene.auto_audio_text || '';
            currentHotspots = data.hotspots || [];

            const config = {
                "type": "equirectangular",
                "panorama": "/storage/scenes/" + data.scene.image_path,
                "autoLoad": true,
                "pitch": parseFloat(data.scene.default_pitch || 0),
                "yaw": parseFloat(data.scene.default_yaw || 0),
                "hfov": parseFloat(data.scene.default_fov || 100),
                "hotSpots": []
            };

            if (data.hotspots) {
                data.hotspots.forEach(hs => {
                    config.hotSpots.push({
                        "id": hs.id,
                        "pitch": parseFloat(hs.pitch),
                        "yaw": parseFloat(hs.yaw),
                        "type": hs.type === 'navigation' ? 'scene' : 'info',
                        "text": hs.tooltip,
                        "createTooltipArgs": hs,
                        "createTooltipFunc": customHotspotElement
                    });
                });
            }

            viewer = pannellum.viewer('panorama-viewer', config);

            viewer.on('load', function() {
                const container = document.getElementById('panorama-viewer');
                container.onclick = function(e) {
                    if (e.target.closest('.custom-hotspot')) return;

                    const coords = viewer.mouseEventToCoords(e);
                    if (coords && coords.length === 2) {
                        pendingPitch = coords[0];
                        pendingYaw = coords[1];
                        updateCoordsPill();
                        document.getElementById('hs-save-btn').disabled = false;
                    }
                };

                if (editingHotspotId) {
                    highlightEditingMarker(editingHotspotId);
                }
            });

            if (!editingHotspotId) {
                resetFormFields();
            }
        })
        .catch(err => {
            console.error('Failed to load scene:', err);
            alert('Failed to load scene. Check the browser console.');
        });
}

function switchScene(sceneId, thumbEl) {
    editingHotspotId = null;
    exitEditModeUI();
    loadViewer(sceneId);
}

/* ============ CUSTOM HOTSPOT MARKER ============ */
function customHotspotElement(hotSpotDiv, args) {
    const isNavigation = args.type === 'navigation';

    hotSpotDiv.classList.add('custom-hotspot');
    hotSpotDiv.classList.add(isNavigation ? 'type-navigation' : 'type-info');
    hotSpotDiv.setAttribute('data-hotspot-id', args.id);

    // Navigation hotspots → arrow icon (scene pointer)
    // Info hotspots → info circle icon
    const icon = isNavigation ? 'fa-arrow-up' : 'fa-circle-info';

    // Info hotspots show their name (from `tooltip`) as a label ABOVE the icon.
    // Navigation hotspots only show the arrow icon.
    const name = String(args.tooltip || '').trim();
    const label = (!isNavigation && name)
        ? `<span class="hotspot-name-label" title="${escapeHtml(name)}">${escapeHtml(name)}</span>`
        : '';

    hotSpotDiv.innerHTML = `
        <div class="hotspot-wrapper">
            ${label}
            <i class="fa-solid ${icon} hotspot-icon" title="${isNavigation ? 'Click to edit' : escapeHtml(name || 'Info hotspot')}"></i>
            <button class="delete-hs-btn" title="Delete Hotspot" onclick="deleteHotspot(event, ${args.id})">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    `;

    const iconEl = hotSpotDiv.querySelector('.hotspot-icon');
    iconEl.onclick = function(e) {
        e.stopPropagation();
        startEditingHotspot(args.id);
    };
}

function escapeHtml(str) {
    return String(str ?? '').replace(/[&<>"']/g, c => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));
}

/* ============ START EDITING ============ */
function startEditingHotspot(hotspotId) {
    const hs = currentHotspots.find(h => parseInt(h.id) === parseInt(hotspotId));
    if (!hs) return;

    editingHotspotId = hs.id;

    document.getElementById('hs-name-input').value   = hs.tooltip || '';
    document.getElementById('hs-type').value          = hs.type || 'navigation';
    document.getElementById('hs-target-scene').value  = hs.target_scene_id || currentSceneId;
    document.getElementById('hs-content-text').value  = hs.content_text || '';

    pendingPitch = parseFloat(hs.pitch);
    pendingYaw = parseFloat(hs.yaw);
    updateCoordsPill();

    toggleDynamicGroups(hs.type);

    document.getElementById('sidebar-form-title-text').textContent = 'Edit Hotspot';
    document.getElementById('sidebar-form-title').classList.add('editing');
    document.getElementById('edit-mode-banner').classList.add('show');
    document.getElementById('hs-save-btn').disabled = false;

    highlightEditingMarker(hs.id);
    document.querySelector('.hotspot-sidebar-form').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function highlightEditingMarker(hotspotId) {
    document.querySelectorAll('.custom-hotspot').forEach(el => {
        const id = parseInt(el.getAttribute('data-hotspot-id'));
        el.classList.toggle('is-editing', id === parseInt(hotspotId));
    });
}

function exitEditMode() {
    editingHotspotId = null;
    exitEditModeUI();
    resetFormFields();
    highlightEditingMarker(null);
}

function exitEditModeUI() {
    document.getElementById('sidebar-form-title-text').textContent = 'Add New Hotspot';
    document.getElementById('sidebar-form-title').classList.remove('editing');
    document.getElementById('edit-mode-banner').classList.remove('show');
}

/* ============ COORDS PILL ============ */
function updateCoordsPill() {
    const pill = document.getElementById('coords-pill');
    const txt  = document.getElementById('coords-text');
    if (pendingPitch !== null && pendingYaw !== null) {
        pill.classList.remove('empty');
        txt.innerHTML = `Pitch: <strong>${pendingPitch.toFixed(2)}</strong> &nbsp;|&nbsp; Yaw: <strong>${pendingYaw.toFixed(2)}</strong>`;
    } else {
        pill.classList.add('empty');
        txt.textContent = 'Click on the panorama to set coordinates';
    }
}

/* ============ RESET FORM FIELDS ============ */
function resetFormFields() {
    pendingPitch = null;
    pendingYaw = null;
    updateCoordsPill();

    document.getElementById('hs-name-input').value  = '';
    document.getElementById('hs-type').value         = 'navigation';
    document.getElementById('hs-target-scene').value = currentSceneId;
    document.getElementById('hs-content-text').value = '';

    toggleDynamicGroups('navigation');
    document.getElementById('hs-save-btn').disabled = true;
}

function resetSidebarForm() {
    editingHotspotId = null;
    exitEditModeUI();
    resetFormFields();
    highlightEditingMarker(null);
}

/* ============ DYNAMIC FIELD VISIBILITY ============ */
function toggleDynamicGroups(type) {
    const isNav = type === 'navigation';

    document.getElementById('target-scene-group').style.display = isNav ? 'flex' : 'none';
    document.getElementById('content-text-group').style.display = isNav ? 'none' : 'flex';

    // Update hint text under the name field
    const hint = document.getElementById('name-hint');
    if (hint) {
        hint.innerHTML = isNav
            ? 'Used as the tooltip when hovering the navigation arrow.'
            : 'For <strong>Info</strong> hotspots, this name is displayed above the marker.';
    }
}

document.getElementById('hs-type').addEventListener('change', function() {
    toggleDynamicGroups(this.value);
});

/* ============ SAVE HOTSPOT (create or update) ============ */
function saveHotspotFromSidebar() {
    if (pendingPitch === null || pendingYaw === null) {
        alert('Please click on the panorama to set the hotspot position first.');
        return;
    }

    const name = document.getElementById('hs-name-input').value.trim();
    if (!name) {
        alert('Please enter a hotspot name.');
        return;
    }

    const type = document.getElementById('hs-type').value;

    const formData = new FormData();
    formData.append('scene_id', currentSceneId);
    formData.append('tooltip', name);
    formData.append('type', type);
    formData.append('pitch', pendingPitch);
    formData.append('yaw', pendingYaw);
    formData.append('_token', CSRF);

    if (type === 'navigation') {
        formData.append('target_scene_id', document.getElementById('hs-target-scene').value);
    } else {
        formData.append('content_text', document.getElementById('hs-content-text').value);
    }

    let endpoint = ROUTES.saveHotspot;
    if (editingHotspotId) {
        formData.append('_method', 'PUT');
        endpoint = hotspotUrl(ROUTES.updateHotspot, editingHotspotId);
    }

    fetch(endpoint, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': CSRF,
            'Accept': 'application/json',
        }
    })
        .then(async res => {
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                console.error('Save failed', res.status, data);
                throw new Error(data.message || `HTTP ${res.status}`);
            }
            return data;
        })
        .then(res => {
            if (res.success) {
                const wasEditing = !!editingHotspotId;
                editingHotspotId = null;
                exitEditModeUI();
                loadViewer(currentSceneId, true);
                if (wasEditing) {
                    setTimeout(() => resetFormFields(), 100);
                }
            } else {
                alert('Error saving hotspot! ' + (res.message || ''));
            }
        })
        .catch(err => {
            alert('Network error saving hotspot: ' + err.message);
        });
}

/* ============ DELETE HOTSPOT ============ */
function deleteHotspot(event, hotspotId) {
    if (event) event.stopPropagation();

    if (!confirm("Are you sure you want to delete this hotspot?")) return;

    if (editingHotspotId && parseInt(editingHotspotId) === parseInt(hotspotId)) {
        editingHotspotId = null;
        exitEditModeUI();
        resetFormFields();
    }

    const formData = new FormData();
    formData.append('_method', 'DELETE');
    formData.append('_token', CSRF);

    fetch(hotspotUrl(ROUTES.deleteHotspot, hotspotId), {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': CSRF,
            'Accept': 'application/json',
        }
    })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                loadViewer(currentSceneId, true);
            } else {
                alert("Failed to delete hotspot.");
            }
        })
        .catch(err => {
            console.error('Delete failed', err);
            alert("Network error deleting hotspot.");
        });
}

/* ============ TEXT-TO-SPEECH ============ */
function playSpeech(text) {
    if (!text || text.trim() === '') return;
    if (!('speechSynthesis' in window)) {
        alert("Text-to-Speech is not supported in your browser.");
        return;
    }
    window.speechSynthesis.cancel();
    const utterance = new SpeechSynthesisUtterance(text);
    utterance.rate = 0.95;
    utterance.pitch = 1;
    utterance.lang = 'en-US';
    utterance.onend = function() { isSceneAudioPlaying = false; updateSceneAudioButton(); };
    utterance.onerror = function() { isSceneAudioPlaying = false; updateSceneAudioButton(); };
    isSceneAudioPlaying = true;
    updateSceneAudioButton();
    window.speechSynthesis.speak(utterance);
}

function toggleSceneAudio() {
    if (isSceneAudioPlaying) {
        window.speechSynthesis.cancel();
        isSceneAudioPlaying = false;
        updateSceneAudioButton();
    } else {
        if (sceneAutoAudioText && sceneAutoAudioText.trim() !== '') {
            playSpeech(sceneAutoAudioText);
        } else {
            alert("No auto-audio text set for this scene.");
        }
    }
}

function updateSceneAudioButton() {
    const btn = document.getElementById('scene-audio-btn');
    if (isSceneAudioPlaying) {
        btn.innerHTML = '<i class="fa-solid fa-stop me-1"></i> <span id="scene-audio-label">Stop Audio</span>';
    } else {
        btn.innerHTML = '<i class="fa-solid fa-volume-high me-1"></i> <span id="scene-audio-label">Play Scene Audio</span>';
    }
}

/* ============ SCENE SELECTOR ============ */
document.getElementById('scene-selector').addEventListener('change', function() {
    editingHotspotId = null;
    exitEditModeUI();
    loadViewer(this.value);
});

/* ============ INIT ============ */
if (currentSceneId > 0) loadViewer(currentSceneId);
</script>
@endpush