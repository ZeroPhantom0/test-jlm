{{-- resources/views/admin/scenes.blade.php --}}
@extends('layouts.admin', ['active' => 'virtual-tour', 'pageTitle' => 'Manage Scenes'])
@section('title', 'Scenes — ' . $virtualTour->title)

@section('content')
<div class="p-4 md:p-8 max-w-[1400px] mx-auto space-y-5">

    {{-- ============ HEADER ============ --}}
    <div class="flex flex-col md:flex-row justify-between md:items-center gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.virtual-tour.builder', $virtualTour->id) }}"
               class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-600 hover:bg-slate-50 transition">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <div>
                <h2 class="text-2xl font-extrabold text-slate-900">
                    Scenes for: {{ $virtualTour->title }}
                </h2>
                <p class="text-sm text-slate-500 mt-0.5">
                    Upload and manage equirectangular 360° images for this virtual tour.
                </p>
            </div>
        </div>
        <a href="{{ route('admin.virtual-tour.builder', $virtualTour->id) }}"
           class="bg-[#0b1930] hover:bg-[#162640] text-white px-5 py-2.5 rounded-[10px] text-sm font-semibold inline-flex items-center gap-2 transition shadow-sm">
            <i class="fa-solid fa-wand-magic-sparkles"></i> Open Builder
        </a>
    </div>

    {{-- ============ FLASH MESSAGES ============ --}}
    @if (session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl px-4 py-3 text-sm">
            <i class="fa-regular fa-circle-check mr-1"></i> {{ session('success') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="bg-rose-50 border border-rose-200 text-rose-800 rounded-xl px-4 py-3 text-sm">
            <i class="fa-solid fa-triangle-exclamation mr-1"></i>
            <ul class="list-disc list-inside mt-1">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">

        {{-- ============ UPLOAD FORM ============ --}}
        <div class="lg:col-span-4">
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <div class="bg-[#0b1930] text-white px-5 py-4 flex items-center gap-2.5">
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                    <span class="text-sm font-bold">Upload 360° Scene</span>
                </div>

                <form method="POST"
                      action="{{ route('admin.virtual-tour.scenes.store', $virtualTour->id) }}"
                      enctype="multipart/form-data"
                      class="p-5 space-y-4">
                    @csrf

                    <div>
                        <label class="block text-[12.5px] font-semibold text-slate-700 mb-1.5">Scene Title *</label>
                        <input type="text" name="title" required
                               placeholder="e.g. Master Bedroom"
                               class="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-[13px] outline-none focus:border-slate-400">
                    </div>

                    <div>
                        <label class="block text-[12.5px] font-semibold text-slate-700 mb-1.5">Description</label>
                        <textarea name="description" rows="2"
                                  placeholder="Short description of the scene..."
                                  class="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-[13px] outline-none focus:border-slate-400 resize-none"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[12.5px] font-semibold text-slate-700 mb-1.5">Default Yaw</label>
                            <input type="number" step="any" name="default_yaw" value="0"
                                   class="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-[13px] outline-none focus:border-slate-400">
                        </div>
                        <div>
                            <label class="block text-[12.5px] font-semibold text-slate-700 mb-1.5">Default Pitch</label>
                            <input type="number" step="any" name="default_pitch" value="0"
                                   class="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-[13px] outline-none focus:border-slate-400">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[12.5px] font-semibold text-slate-700 mb-1.5">Auto-Play Audio Text</label>
                        <textarea name="auto_audio_text" rows="2"
                                  placeholder="Text to speak automatically when scene loads..."
                                  class="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-[13px] outline-none focus:border-slate-400 resize-none"></textarea>
                        <p class="text-[11px] text-slate-400 mt-1">Leave empty for no automatic audio.</p>
                    </div>

                    <div>
                        <label class="block text-[12.5px] font-semibold text-slate-700 mb-1.5">360° Image File *</label>
                        <input type="file" name="scene_image" accept="image/*" required
                               class="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-[13px] file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
                        <p class="text-[11px] text-slate-400 mt-1">PNG, JPG, or WebP — up to 20 MB.</p>
                    </div>

                    <button type="submit"
                            class="w-full bg-[#0b1930] hover:bg-[#162640] text-white px-5 py-2.5 rounded-[10px] text-sm font-semibold inline-flex items-center justify-center gap-2 transition shadow-sm">
                        <i class="fa-solid fa-upload"></i> Upload Scene
                    </button>
                </form>
            </div>
        </div>

        {{-- ============ SCENES LIST ============ --}}
        <div class="lg:col-span-8">
            @if ($virtualTour->scenes->isEmpty())
                <div class="text-center py-20 bg-white rounded-2xl border border-dashed border-slate-200">
                    <i class="fa-solid fa-image text-5xl text-slate-300 mb-4"></i>
                    <h3 class="text-lg font-bold text-slate-700">No scenes yet</h3>
                    <p class="text-slate-500 text-sm mt-1">
                        Use the form on the left to add your first 360° image.
                    </p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach ($virtualTour->scenes as $scene)
                        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden flex flex-col">
                            <div class="relative h-44 bg-black">
                                <img src="{{ $scene->image_url }}"
                                     alt="{{ $scene->title }}"
                                     class="w-full h-full object-cover">

                                @if ($scene->is_starting_scene)
                                    <span class="absolute top-3 left-3 bg-amber-500 text-white text-[10.5px] font-bold px-2.5 py-1 rounded-lg shadow-md inline-flex items-center gap-1">
                                        <i class="fa-solid fa-star"></i> Start Scene
                                    </span>
                                @endif

                                @if ($scene->auto_audio_text)
                                    <span class="absolute top-3 right-3 bg-emerald-500/95 text-white text-[10.5px] font-bold px-2.5 py-1 rounded-lg shadow-md inline-flex items-center gap-1">
                                        <i class="fa-solid fa-music"></i> Audio
                                    </span>
                                @endif
                            </div>

                            <div class="p-4 flex flex-col gap-2 flex-1">
                                <h4 class="text-[15px] font-bold text-slate-900">{{ $scene->title }}</h4>
                                <p class="text-xs text-slate-500 line-clamp-2 flex-1">
                                    {{ $scene->description ?: 'No description' }}
                                </p>

                                <div class="flex flex-wrap gap-1.5 mt-1">
                                    <span class="bg-slate-100 text-slate-600 text-[10.5px] font-semibold px-2.5 py-1 rounded-md inline-flex items-center gap-1">
                                        <i class="fa-solid fa-compass"></i> Yaw: {{ (float) $scene->default_yaw }}
                                    </span>
                                    <span class="bg-slate-100 text-slate-600 text-[10.5px] font-semibold px-2.5 py-1 rounded-md inline-flex items-center gap-1">
                                        <i class="fa-solid fa-arrow-up"></i> Pitch: {{ (float) $scene->default_pitch }}
                                    </span>
                                </div>

                                <div class="flex items-center gap-2 mt-3 pt-3 border-t border-slate-100">
                                    {{-- Using discrete data attributes prevents Blade @json parse errors --}}
                                    <button type="button"
                                            data-id="{{ $scene->id }}"
                                            data-title="{{ $scene->title }}"
                                            data-description="{{ $scene->description }}"
                                            data-yaw="{{ (float) $scene->default_yaw }}"
                                            data-pitch="{{ (float) $scene->default_pitch }}"
                                            data-audio="{{ $scene->auto_audio_text }}"
                                            data-image-url="{{ $scene->image_url }}"
                                            class="flex-1 bg-[#0b1930] hover:bg-[#162640] text-white px-3 py-2 rounded-lg text-xs font-semibold inline-flex items-center justify-center gap-1.5 transition open-edit-scene-btn">
                                        <i class="fa-regular fa-pen-to-square"></i> Edit
                                    </button>

                                    @if (!$scene->is_starting_scene)
                                        <form method="POST"
                                              action="{{ route('admin.virtual-tour.scenes.set-starting', [$virtualTour->id, $scene->id]) }}"
                                              class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                    class="bg-amber-50 border border-amber-200 text-amber-700 px-3 py-2 rounded-lg text-xs font-semibold inline-flex items-center justify-center gap-1.5 hover:bg-amber-100 transition"
                                                    title="Set as starting scene">
                                                <i class="fa-solid fa-star"></i>
                                            </button>
                                        </form>
                                    @endif

                                    <button type="button"
                                            data-scene-id="{{ $scene->id }}"
                                            data-scene-title="{{ $scene->title }}"
                                            class="bg-rose-50 border border-rose-200 text-rose-600 px-3 py-2 rounded-lg text-xs font-semibold inline-flex items-center justify-center gap-1.5 hover:bg-rose-100 transition open-delete-scene-btn"
                                            title="Delete scene">
                                        <i class="fa-regular fa-trash-can"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

{{-- React Modal Mount Point --}}
<div id="admin-scenes-modals" 
     data-tour-id="{{ $virtualTour->id }}"
     data-csrf="{{ csrf_token() }}"
     class="hidden"></div>
@endsection

@push('scripts')
<script>
    // Base URLs for updating and deleting scenes — {id} is appended in React
    window.SCENE_UPDATE_BASE = "{{ url('admin/virtual-tour/' . $virtualTour->id . '/scenes') }}";
    window.SCENE_DESTROY_BASE = "{{ url('admin/virtual-tour/' . $virtualTour->id . '/scenes') }}";
</script>
@endpush