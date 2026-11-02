{{-- resources/views/layouts/admin.blade.php --}}
@php
    $user = auth()->user();
    $profilePic = $user?->personalInformation?->profile_picture ?? $user?->profile_picture;
    $avatarUrl = $profilePic
        ? (str_starts_with($profilePic, 'http')
            ? $profilePic
            : asset('storage/' . $profilePic))
        : null;

    $headerProps = json_encode([
        'title' => $pageTitle ?? 'Dashboard',
        'userName' => $user?->personalInformation?->full_name ?? ($user?->name ?? 'Administrator'),
        'userRole' => 'Admin',
        'userEmail' => $user?->email ?? '',
        'avatar' => $avatarUrl,
        'showHamburger' => true,
    ]);

    $broadcastConfig = [
        'key' => env('PUSHER_APP_KEY', ''),
        'cluster' => env('PUSHER_APP_CLUSTER', 'ap1'),
    ];

    $navItems = [
        [
            'key' => 'dashboard',
            'label' => 'Dashboard',
            'icon' => 'fa-solid fa-border-all',
            'href' => '/admin',
            'active' => request()->is('admin'),
        ],
        [
            'key' => 'rooms',
            'label' => 'Rooms',
            'icon' => 'fa-solid fa-bed',
            'href' => '/admin/rooms',
            'active' => request()->is('admin/rooms*'),
        ],
        [
            'key' => 'tenants',
            'label' => 'Tenants',
            'icon' => 'fa-solid fa-users',
            'href' => '/admin/tenants',
            'active' => request()->is('admin/tenants*'),
        ],
        [
            'key' => 'services',
            'label' => 'Services',
            'icon' => 'fa-solid fa-wrench',
            'href' => '/admin/services/maintenance',
            'active' => request()->is('admin/services*'),
            'hasSub' => true,
        ],
        [
            'key' => 'billing',
            'label' => 'Billing',
            'icon' => 'fa-solid fa-credit-card',
            'href' => '/admin/billing',
            'active' => request()->is('admin/billing*'),
        ],
        [
            'key' => 'reservations',
            'label' => 'Reservations',
            'icon' => 'fa-regular fa-calendar-check',
            'href' => '/admin/reservations',
            'active' => request()->is('admin/reservations*'),
        ],
        [
            'key' => 'messages',
            'label' => 'Messages',
            'icon' => 'fa-regular fa-comment-dots',
            'href' => '/admin/messages',
            'active' => request()->is('admin/messages*'),
        ],
        [
            'key' => 'announcements',
            'label' => 'Announcements',
            'icon' => 'fa-solid fa-bullhorn',
            'href' => '/admin/announcements',
            'active' => request()->is('admin/announcements*'),
        ],
        [
            'key' => 'virtual-tour',
            'label' => 'Virtual Room Tour',
            'icon' => 'fa-solid fa-camera',
            'href' => '/admin/virtual-tour',
            'active' => request()->is('admin/virtual-tour*'),
        ],
        [
            'key' => 'reports',
            'label' => 'Reports',
            'icon' => 'fa-regular fa-file-lines',
            'href' => '/admin/reports',
            'active' => request()->is('admin/reports*'),
        ],
        [
            'key' => 'history',
            'label' => 'History',
            'icon' => 'fa-solid fa-clock-rotate-left',
            'href' => '/admin/history',
            'active' => request()->is('admin/history*'),
        ],
        [
            'key' => 'audit-logs',
            'label' => 'Audit Logs',
            'icon' => 'fa-solid fa-shield-halved',
            'href' => '/admin/audit-logs',
            'active' => request()->is('admin/audit-logs*'),
        ],
        [
            'key' => 'settings',
            'label' => 'Settings',
            'icon' => 'fa-solid fa-gear',
            'href' => '/admin/settings',
            'active' => request()->is('admin/settings*'),
        ],
    ];

    $servicesSub = [
        [
            'label' => 'Maintenance',
            'icon' => 'fa-solid fa-wrench',
            'href' => '/admin/services/maintenance',
            'active' => request()->is('admin/services/maintenance*'),
        ],
        [
            'label' => 'Room Change',
            'icon' => 'fa-solid fa-house',
            'href' => '/admin/services/room-change',
            'active' => request()->is('admin/services/room-change*'),
        ],
        [
            'label' => 'Stay Extension',
            'icon' => 'fa-regular fa-calendar',
            'href' => '/admin/services/stay-extension',
            'active' => request()->is('admin/services/stay-extension*'),
        ],
    ];
@endphp

<!DOCTYPE html>
<html lang="en" class="h-full w-full overflow-hidden">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Admin Portal') — JLM Cozy Cabin</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script>
        window.__appConfig = {
            userId: {{ $user?->id ?? 'null' }},
            role: @json($user?->role ?? 'guest'),
            isAdmin: {{ $user?->isAdmin() ? 'true' : 'false' }},
            csrf: @json(csrf_token()),
            broadcast: @json($broadcastConfig),
        };
    </script>

    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
    @livewireStyles
    @stack('styles')
</head>

<body class="bg-[#f4f6f9] text-slate-900 antialiased flex h-full w-full overflow-hidden">


    <div id="admin-sidebar-overlay" class="fixed inset-0 bg-slate-900/50 z-[999] hidden md:hidden backdrop-blur-sm">
    </div>

    {{-- SIDEBAR --}}
    <aside id="admin-sidebar-blade"
        class="bg-[#02122b] text-white flex flex-col shrink-0 h-full z-[1000] transition-transform duration-300
               fixed md:sticky md:top-0 top-0 left-0 overflow-x-hidden w-62.5"
        style="box-sizing: border-box; transform: translateX(-100%);">
        {{-- Brand --}}
        <div class="flex items-center justify-between shrink-0 whitespace-nowrap px-[18px] pt-5 pb-3.5">
            <div class="flex items-center gap-3 min-w-0">
                <div class="rounded-full bg-white flex items-center justify-center text-[#f97316] shadow-[0_2px_4px_rgba(0,0,0,0.15)] shrink-0"
                    style="width: 38px; height: 38px; font-size: 18px;">
                    <i class="fa-solid fa-house-key"></i>
                </div>
                <div class="overflow-hidden min-w-0">
                    <h3 class="font-bold leading-tight text-white" style="font-size: 15px;">JLM Cozy Cabin</h3>
                    <p class="text-[#8fa0b5]" style="font-size: 11px;">Admin Portal</p>
                </div>
            </div>

            <button type="button" onclick="document.getElementById('admin-sidebar-blade').classList.toggle('md:w-20')"
                class="text-[#8fa0b5] hover:text-white rounded-md hover:bg-white/10 transition hidden md:block shrink-0"
                style="font-size: 18px; padding: 6px;" title="Toggle Navigation">
                <i class="fa-solid fa-bars"></i>
            </button>

            <button type="button" onclick="closeAdminSidebar()"
                class="md:hidden text-[#8fa0b5] text-lg p-1 hover:text-white shrink-0" aria-label="Close menu">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        {{-- Administrator pill --}}
        <div class="bg-[#f97316] text-white font-bold flex items-center shrink-0 whitespace-nowrap gap-3 rounded-xl shadow-[0_2px_6px_rgba(249,115,22,0.25)]"
            style="margin: 4px 14px 16px 14px; padding: 10px 14px; font-size: 14px; box-sizing: border-box;">
            <i class="fa-solid fa-shield-halved shrink-0" style="font-size: 16px;"></i>
            <span>Administrator</span>
        </div>

        {{-- Nav --}}
        <ul class="flex-1 overflow-y-auto overflow-x-hidden no-scrollbar flex flex-col min-h-0"
            style="padding: 0 10px; gap: 2px;">
            @foreach ($navItems as $item)
                @php
                    $isActive = $item['active'] ?? false;
                    $baseClasses =
                        'flex items-center justify-between w-full rounded-[10px] font-medium whitespace-nowrap transition-all px-3 py-2.5 text-[13.5px]';
                    $activeClasses = $isActive
                        ? 'text-[#ffaa6e] bg-[#092042] font-semibold border-l-[3px] border-[#f97316]'
                        : 'text-slate-400 hover:text-white hover:bg-white/5';

                    // Check if this is the Services menu and is currently active
                    $isServicesActive = $isActive && $item['key'] === 'services';
                @endphp
                <li class="flex flex-col w-full">
                    <div class="flex items-center w-full">
                        <a href="{{ $item['href'] }}" wire:navigate.hover
                            @if (!empty($item['hasSub'])) onclick="openServicesSubmenu()" @endif
                            class="{{ $baseClasses }} {{ $activeClasses }} flex-1" title="{{ $item['label'] }}">
                            <div class="flex items-center gap-3.5">
                                <i class="{{ $item['icon'] }} text-center shrink-0"
                                    style="width: 20px; font-size: 15px;"></i>
                                <span>{{ $item['label'] }}</span>
                            </div>
                        </a>

                        @if (!empty($item['hasSub']))
                            <button type="button" onclick="toggleServicesSubmenu(event, this)"
                                class="text-slate-500 hover:bg-white/10 rounded ml-1" style="padding: 4px;"
                                aria-label="Toggle services submenu">
                                {{-- Set the initial icon based on whether Services is active --}}
                                <i id="services-chevron"
                                    class="fa-solid {{ $isServicesActive ? 'fa-chevron-up' : 'fa-chevron-down' }}"
                                    style="font-size: 11px;"></i>
                            </button>
                        @endif
                    </div>

                    @if (!empty($item['hasSub']))
                        {{-- Set the initial visibility based on whether Services is active --}}
                        <ul id="services-submenu" class="flex-col w-full {{ $isServicesActive ? 'flex' : 'hidden' }}"
                            style="padding-left: 28px; margin-top: 2px; margin-bottom: 6px; gap: 2px;">
                            @foreach ($servicesSub as $sub)
                                @php
                                    $subActive = $sub['active'] ?? false;
                                @endphp
                                <li>
                                    <a href="{{ $sub['href'] }}" wire:navigate.hover
                                        class="flex items-center rounded-lg w-full transition-all px-3 py-2 text-[13px] gap-2.5
                                               {{ $subActive ? 'bg-[#f97316] text-white font-semibold' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">
                                        <i class="{{ $sub['icon'] }} shrink-0" style="font-size: 11px;"></i>
                                        <span>{{ $sub['label'] }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </li>
            @endforeach
        </ul>

        {{-- Footer user card --}}
        <div class="w-full flex items-center justify-between shrink-0 bg-[#02122b] whitespace-nowrap overflow-hidden border-t border-white/10"
            style="padding: 14px;">
            <div class="flex items-center gap-3 min-w-0 flex-1">
                @if ($avatarUrl)
                    <img src="{{ $avatarUrl }}" alt="{{ $user?->name }}"
                        class="rounded-full object-cover border-[1.5px] border-white shrink-0"
                        style="width: 34px; height: 34px;">
                @else
                    <div class="rounded-full bg-white/10 text-white flex items-center justify-center shrink-0 border-[1.5px] border-white"
                        style="width: 34px; height: 34px;">
                        <i class="fa-solid fa-user" style="font-size: 15px;"></i>
                    </div>
                @endif
                <div class="min-w-0 flex-1">
                    <h4 class="font-bold text-white leading-tight truncate" style="font-size: 13px;">
                        {{ $user?->personalInformation?->full_name ?? ($user?->name ?? 'Administrator') }}
                    </h4>
                    <p class="text-[#8fa0b5] truncate" style="font-size: 11px;">administrator</p>
                </div>
            </div>
            <button type="button" onclick="window.dispatchEvent(new Event('open-logout-modal'))"
                class="text-[#8fa0b5] hover:text-white transition cursor-pointer shrink-0" style="font-size: 16px;"
                title="Logout">
                <i class="fa-solid fa-right-from-bracket"></i>
            </button>
        </div>
    </aside>

    {{-- MAIN CONTENT AREA --}}
    <div class="flex-1 flex flex-col min-w-0 h-full overflow-y-auto relative">

        {{-- TOP HEADER (React) --}}
        <div data-react="admin-top-header" data-props='{!! $headerProps !!}' class="sticky top-0 z-[100]"></div>

        <main class="flex-1">
            @yield('content')
        </main>
    </div>

    {{-- Sidebar & Submenu Logic --}}
    <script>
        // Sidebar Mobile Logic
        function openAdminSidebar() {
            const sidebar = document.getElementById('admin-sidebar-blade');
            const overlay = document.getElementById('admin-sidebar-overlay');
            if (sidebar) sidebar.style.transform = 'translateX(0)';
            if (overlay) overlay.classList.remove('hidden');
        }

        function closeAdminSidebar() {
            const sidebar = document.getElementById('admin-sidebar-blade');
            const overlay = document.getElementById('admin-sidebar-overlay');
            if (sidebar && window.innerWidth < 768) sidebar.style.transform = 'translateX(-100%)';
            if (overlay) overlay.classList.add('hidden');
        }

        function resetAdminSidebar() {
            const sidebar = document.getElementById('admin-sidebar-blade');
            const overlay = document.getElementById('admin-sidebar-overlay');
            if (sidebar) {
                sidebar.style.transform = window.innerWidth >= 768 ? 'translateX(0)' : 'translateX(-100%)';
            }
            if (overlay) overlay.classList.add('hidden');
        }

        // Submenu Logic
        function toggleServicesSubmenu(event, btn) {
            event.preventDefault();
            event.stopPropagation();
            const submenu = document.getElementById('services-submenu');
            const icon = document.getElementById('services-chevron');

            if (submenu.classList.contains('hidden')) {
                submenu.classList.remove('hidden');
                submenu.classList.add('flex');
                icon.classList.replace('fa-chevron-down', 'fa-chevron-up');
            } else {
                submenu.classList.add('hidden');
                submenu.classList.remove('flex');
                icon.classList.replace('fa-chevron-up', 'fa-chevron-down');
            }
        }

        function openServicesSubmenu() {
            const submenu = document.getElementById('services-submenu');
            const icon = document.getElementById('services-chevron');
            submenu.classList.remove('hidden');
            submenu.classList.add('flex');
            icon.classList.replace('fa-chevron-down', 'fa-chevron-up');
        }

        // Bind events
        window.addEventListener('open-mobile-sidebar', openAdminSidebar);
        window.addEventListener('resize', resetAdminSidebar);

        document.getElementById('admin-sidebar-overlay')?.addEventListener('click', closeAdminSidebar);

        document.addEventListener('click', (e) => {
            const link = e.target.closest('#admin-sidebar-blade a');
            if (link && window.innerWidth < 768) closeAdminSidebar();
        });

        // Reset state on Livewire navigation & initial load
        document.addEventListener('livewire:navigated', resetAdminSidebar);
        resetAdminSidebar();
    </script>

    @livewireScripts
    @stack('scripts')
</body>

</html>
