@php
    // ---------------------------------------------------------------
    // Resolve the "Back to Rooms" destination.
    // ---------------------------------------------------------------
    $host = request()->getSchemeAndHttpHost();
    $basePath = rtrim(request()->getBaseUrl(), '/');
    $fallback = $host . $basePath . '/#rooms';
    $backUrl = $fallback;

    $from = request()->query('from');

    if ($from) {
        $refHost = parse_url($from, PHP_URL_HOST);
        $refPath = parse_url($from, PHP_URL_PATH) ?? '';
        $isLoop = str_starts_with($refPath, '/tour/') || str_starts_with($refPath, $basePath . '/tour/');

        if (!$isLoop) {
            if ($refHost === null) {
                $path = '/' . ltrim($from, '/');
                $backUrl = $host . $path;
            } elseif ($refHost === request()->getHost()) {
                $backUrl = $from;
            } else {
                $backUrl = $fallback;
            }
        }
    } else {
        $referer = request()->headers->get('referer');
        if ($referer) {
            $refHost = parse_url($referer, PHP_URL_HOST);
            $refPath = parse_url($referer, PHP_URL_PATH) ?? '';
            $isLoop = str_starts_with($refPath, '/tour/');
            $isHome = $refPath === '/' || $refPath === '' || $refPath === $basePath || $refPath === $basePath . '/';

            if ($refHost === request()->getHost() && !$isLoop) {
                $backUrl = $isHome ? $fallback : $referer;
            }
        }
    }
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>{{ $tour->title }} — Virtual Tour | JLM Cozy Cabin</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.css">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html,
        body {
            height: 100%;
            width: 100%;
            overflow: hidden;
            font-family: 'Inter', sans-serif;
            background: #000;
            color: #fff;
        }

        #panorama-viewer {
            width: 100vw;
            height: 100vh;
            position: relative;
            background: #000;
            /* Forces a black background during the 10ms swap */
        }

        /* 🚫 HIDE PANNELLUM LOADING TEXT & SPINNER */
        .pnlm-load-box,
        .pnlm-loading-text,
        .pnlm-loading-indicator {
            display: none !important;
            opacity: 0 !important;
            visibility: hidden !important;
        }

        /* ---------- Top Overlay Bar ---------- */
        .tour-top-bar {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            padding: 16px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 100;
            background: linear-gradient(180deg, rgba(0, 0, 0, 0.85) 0%, rgba(0, 0, 0, 0.4) 60%, transparent 100%);
            pointer-events: none;
        }

        .tour-top-bar>* {
            pointer-events: auto;
        }

        .tour-top-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.25);
            color: #fff;
            padding: 8px 14px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            backdrop-filter: blur(8px);
            transition: background 0.15s;
        }

        .back-btn:hover {
            background: rgba(255, 255, 255, 0.22);
        }

        .tour-title {
            font-size: 18px;
            font-weight: 700;
        }

        .tour-subtitle {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.7);
            margin-top: 2px;
        }

        .tour-top-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .icon-btn {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.25);
            color: #fff;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 15px;
            backdrop-filter: blur(8px);
            transition: background 0.15s;
        }

        .icon-btn:hover {
            background: rgba(255, 255, 255, 0.22);
        }

        /* ---------- Info Popup Modal ---------- */
        .info-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(6px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 200;
            padding: 20px;
        }

        .info-modal-backdrop.active {
            display: flex;
        }

        .info-modal {
            background: #fff;
            color: #0f172a;
            border-radius: 16px;
            max-width: 520px;
            width: 100%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .info-modal-header {
            padding: 18px 22px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .info-modal-header h3 {
            font-size: 16px;
            font-weight: 700;
        }

        .info-modal-close {
            background: none;
            border: none;
            font-size: 20px;
            color: #94a3b8;
            cursor: pointer;
        }

        .info-modal-close:hover {
            color: #0f172a;
        }

        .info-modal-body {
            padding: 22px;
            font-size: 14px;
            line-height: 1.6;
        }

        /* ============================================================
           CUSTOM HOTSPOT MARKERS (Matches Admin Builder Layout)
           ============================================================ */

        .custom-hotspot {
            cursor: pointer;
        }

        .custom-hotspot .hotspot-wrapper {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
        }

        .custom-hotspot .hotspot-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            color: #ffffff;
            border-radius: 50%;
            border: 2px solid #ffffff;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.35);
            font-size: 16px;
            transition: transform 0.15s;
            box-sizing: border-box;
        }

        .custom-hotspot:hover .hotspot-icon {
            transform: scale(1.15);
        }

        .custom-hotspot.type-navigation .hotspot-icon {
            background: rgba(79, 70, 229, 0.95);
        }

        .custom-hotspot.type-info .hotspot-icon {
            background: rgba(245, 158, 11, 0.95);
        }

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
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
            pointer-events: none;
            position: relative;
        }

        @media (max-width: 640px) {
            .tour-title {
                font-size: 14px;
            }

            .tour-subtitle {
                display: none;
            }

            .back-btn span {
                display: none;
            }
        }
    </style>
</head>

<body>

    {{-- Top Overlay Bar --}}
    <div class="tour-top-bar">
        <div class="tour-top-left">
            <a href="{{ $backUrl }}" class="back-btn">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Back to Rooms</span>
            </a>
            <div>
                <div class="tour-title">{{ $tour->title }}</div>
                <div class="tour-subtitle">
                    Room {{ $tour->room->room_number ?? '' }}
                    @if ($tour->room && $tour->room->room_type)
                        • {{ $tour->room->room_type }}
                    @endif
                </div>
            </div>
        </div>

        <div class="tour-top-right">
            <button class="icon-btn" id="audio-toggle-btn" onclick="toggleMute()" title="Toggle Audio">
                <i class="fa-solid fa-volume-high" id="audio-icon"></i>
            </button>
            <button class="icon-btn" onclick="toggleFullscreen()" title="Fullscreen">
                <i class="fa-solid fa-expand"></i>
            </button>
        </div>
    </div>

    {{-- Viewer --}}
    <div id="panorama-viewer"></div>

    {{-- Info Modal --}}
    <div class="info-modal-backdrop" id="info-modal-backdrop" onclick="if(event.target===this) closeInfoModal()">
        <div class="info-modal">
            <div class="info-modal-header">
                <h3 id="info-modal-title">Information</h3>
                <button class="info-modal-close" onclick="closeInfoModal()">&times;</button>
            </div>
            <div class="info-modal-body" id="info-modal-body"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.js"></script>
    <script>
        let viewer = null;
        let currentSceneId = null;
        let sceneAutoAudioText = '';
        let isMuted = false;

        const SCENES_URL = '{{ url('tour-scene-data') }}';

        function escapeHtml(str) {
            return String(str ?? '').replace(/[&<>"']/g, c => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            } [c]));
        }

        // ------------------------------------------------------------
        // Load a scene
        // ------------------------------------------------------------
        function loadScene(sceneId) {
            currentSceneId = sceneId;

            fetch(`${SCENES_URL}/${sceneId}`)
                .then(res => res.json())
                .then(data => {
                    // 1. CLEAN SLATE: Completely destroy the old viewer to prevent overlapping
                    if (viewer) {
                        viewer.destroy();
                        viewer = null;
                    }

                    sceneAutoAudioText = data.scene.auto_audio_text || '';

                    const config = {
                        type: 'equirectangular',
                        panorama: '/storage/scenes/' + data.scene.image_path,
                        autoLoad: true,
                        pitch: parseFloat(data.scene.default_pitch || 0),
                        yaw: parseFloat(data.scene.default_yaw || 0),
                        hfov: parseFloat(data.scene.default_fov || 100),
                        showControls: true,
                        hotSpots: [],
                    };

                    (data.hotspots || []).forEach(hs => {
                        const hsConfig = {
                            id: hs.id,
                            pitch: parseFloat(hs.pitch),
                            yaw: parseFloat(hs.yaw),
                            createTooltipArgs: hs,
                            createTooltipFunc: renderHotspot,
                            clickHandlerArgs: hs,
                        };

                        if (hs.type === 'navigation') {
                            hsConfig.clickHandlerFunc = (e, args) => {
                                window.speechSynthesis.cancel();
                                if (args.target_scene_id) {
                                    loadScene(args.target_scene_id);
                                }
                            };
                        } else {
                            hsConfig.clickHandlerFunc = (e, args) => showInfoModal(args);
                        }

                        config.hotSpots.push(hsConfig);
                    });

                    // 2. CREATE NEW VIEWER
                    viewer = pannellum.viewer('panorama-viewer', config);

                    viewer.on('load', () => {
                        if (sceneAutoAudioText && !isMuted) {
                            setTimeout(() => speak(sceneAutoAudioText), 500);
                        }
                    });
                })
                .catch(err => {
                    console.error('Failed to load scene:', err);
                });
        }

        // ------------------------------------------------------------
        // Hotspot rendering
        // ------------------------------------------------------------
        function renderHotspot(div, args) {
            const isNavigation = args.type === 'navigation';

            div.classList.add('custom-hotspot');
            div.classList.add(isNavigation ? 'type-navigation' : 'type-info');

            const icon = isNavigation ? 'fa-arrow-up' : 'fa-circle-info';
            const name = String(args.tooltip || '').trim();

            const label = (!isNavigation && name) ?
                `<span class="hotspot-name-label" title="${escapeHtml(name)}">${escapeHtml(name)}</span>` :
                '';

            div.innerHTML = `
                <div class="hotspot-wrapper">
                    ${label}
                    <i class="fa-solid ${icon} hotspot-icon"></i>
                </div>
            `;
        }

        function showInfoModal(hs) {
            document.getElementById('info-modal-title').textContent = hs.tooltip || 'Information';

            const content = hs.content_text ?
                `<p>${escapeHtml(hs.content_text).replace(/\n/g, '<br>')}</p>` :
                `<p style="color:#94a3b8;">No details provided.</p>`;

            document.getElementById('info-modal-body').innerHTML = content;
            document.getElementById('info-modal-backdrop').classList.add('active');
        }

        function closeInfoModal() {
            document.getElementById('info-modal-backdrop').classList.remove('active');
        }

        function speak(text, onEnd) {
            if (!text || !('speechSynthesis' in window)) {
                if (onEnd) onEnd();
                return;
            }
            const u = new SpeechSynthesisUtterance(text);
            u.rate = 0.95;
            u.pitch = 1;
            u.lang = 'en-US';
            u.onend = () => onEnd && onEnd();
            u.onerror = () => onEnd && onEnd();
            window.speechSynthesis.speak(u);
        }

        function toggleMute() {
            isMuted = !isMuted;
            const icon = document.getElementById('audio-icon');
            if (isMuted) {
                window.speechSynthesis.cancel();
                icon.className = 'fa-solid fa-volume-xmark';
            } else {
                icon.className = 'fa-solid fa-volume-high';
                if (sceneAutoAudioText) speak(sceneAutoAudioText);
            }
        }

        function toggleFullscreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen();
            } else {
                document.exitFullscreen();
            }
        }

        @if ($startScene)
            loadScene({{ $startScene->id }});
        @endif
    </script>
</body>

</html>
