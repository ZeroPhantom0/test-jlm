{{-- resources/views/layouts/tenant.blade.php --}}
@php
    $user = auth()->user();
    $profilePic = $user?->personalInformation?->profile_picture ?? $user?->profile_picture;
    $avatarUrl = $profilePic ? (str_starts_with($profilePic, 'http') ? $profilePic : asset('storage/' . $profilePic)) : null;
    $isTenant = \App\Models\Tenant::isApproved();

    $headerProps = json_encode([
        'title'     => $pageTitle ?? 'Dashboard',
        'userName'  => $user?->personalInformation?->full_name ?? $user?->name ?? 'Tenant',
        'userRole'  => $isTenant ? 'Tenant' : 'New Tenant',
        'userEmail' => $user?->email ?? '',
        'avatar'    => $avatarUrl,
        'isTenant'  => $isTenant,
    ]);

    $broadcastConfig = [
        'key'     => env('PUSHER_APP_KEY', ''),
        'cluster' => env('PUSHER_APP_CLUSTER', 'ap1'),
    ];

    $navItems = [
        ['key' => 'dashboard',     'label' => 'Dashboard',     'icon' => 'fa-solid fa-border-all',        'href' => '/tenants',                    'active' => request()->is('tenants'),                      'requiresTenant' => false],
        ['key' => 'services',      'label' => 'Services',      'icon' => 'fa-solid fa-wrench',            'href' => '/tenants/services/maintenance', 'active' => request()->is('tenants/services*'),             'requiresTenant' => true, 'hasSub' => true],
        ['key' => 'billing',       'label' => 'Billing',       'icon' => 'fa-regular fa-credit-card',     'href' => '/tenants/billing',            'active' => request()->is('tenants/billing*'),             'requiresTenant' => true],
        ['key' => 'reservations',  'label' => 'Reservations',  'icon' => 'fa-regular fa-calendar-check',  'href' => '/tenants/reservations',       'active' => request()->is('tenants/reservations*'),        'requiresTenant' => false],
        ['key' => 'messages',      'label' => 'Messages',      'icon' => 'fa-regular fa-comment-dots',    'href' => '/tenants/messages',           'active' => request()->is('tenants/messages*'),            'requiresTenant' => false],
        ['key' => 'announcements', 'label' => 'Announcements', 'icon' => 'fa-solid fa-bullhorn',          'href' => '/tenants/announcements',      'active' => request()->is('tenants/announcements*'),       'requiresTenant' => false],
        ['key' => 'settings',      'label' => 'Settings',      'icon' => 'fa-solid fa-gear',              'href' => '/tenants/settings',           'active' => request()->is('tenants/settings*'),            'requiresTenant' => false],
    ];

    $servicesSub = [
        ['label' => 'Maintenance',    'icon' => 'fa-solid fa-wrench',     'href' => '/tenants/services/maintenance',    'active' => request()->is('tenants/services/maintenance*')],
        ['label' => 'Room Change',    'icon' => 'fa-solid fa-house',      'href' => '/tenants/services/room-change',    'active' => request()->is('tenants/services/room-change*')],
        ['label' => 'Stay Extension', 'icon' => 'fa-regular fa-calendar', 'href' => '/tenants/services/stay-extension', 'active' => request()->is('tenants/services/stay-extension*')],
    ];
@endphp

<!DOCTYPE html>
<html lang="en" class="h-full w-full overflow-hidden">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Tenant Portal') — JLM Cozy Cabin</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script>
        window.__appConfig = {
            userId:  {{ $user?->id ?? 'null' }},
            role:    @json($user?->role ?? 'guest'),
            isTenant: {{ $user?->isTenant() ? 'true' : 'false' }},
            csrf:    @json(csrf_token()),
            broadcast: @json($broadcastConfig),
        };
    </script>

    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
    @livewireStyles
    @stack('styles')
</head>

<body class="bg-slate-50 flex h-full w-full overflow-hidden text-slate-800">

    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
        @csrf
    </form>

    <div id="mobile-sidebar-overlay"
         class="fixed inset-0 bg-slate-900/50 z-[999] hidden md:hidden"></div>

    {{-- SIDEBAR --}}
    <aside
        id="tenant-sidebar-blade"
        class="bg-navy-dark text-white flex flex-col shrink-0 z-[1000] transition-transform duration-300
               fixed md:sticky md:top-0 top-0 left-0 h-screen w-62.5"
        style="background-color: #02122b; transform: translateX(-100%);"
    >
        {{-- Brand & Toggle --}}
        <div class="px-4.5 pt-5 pb-3.75 flex items-center justify-between whitespace-nowrap shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-9.5 h-9.5 rounded-full bg-white flex items-center justify-center text-[#e06800] text-lg shrink-0">
                    <i class="fa-solid fa-house-chimney"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold leading-tight text-white">JLM Cozy Cabin</h3>
                    <p class="text-[11px] text-[#8fa0b5]">Tenant Portal</p>
                </div>
            </div>

            <button
                type="button"
                onclick="document.getElementById('tenant-sidebar-blade').classList.toggle('md:w-20')"
                class="hidden md:block text-[#8fa0b5] hover:text-white rounded-md hover:bg-white/10 transition shrink-0"
                style="font-size: 18px; padding: 6px;"
                title="Toggle Navigation"
            >
                <i class="fa-solid fa-bars"></i>
            </button>

            <button
                type="button"
                onclick="closeTenantSidebar()"
                class="md:hidden text-[#8fa0b5] text-lg p-1 hover:text-white shrink-0"
                aria-label="Close menu"
            >
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        {{-- User pill --}}
        <div class="bg-[#e06800] text-white font-semibold text-[13px] flex items-center gap-2.5 mx-3.5 mb-3.75 shrink-0 whitespace-nowrap rounded-[20px] px-3.5 py-2">
            @if ($avatarUrl)
                <img src="{{ $avatarUrl }}" alt="{{ $user?->name }}"
                     class="rounded-full object-cover shrink-0 border border-white/30"
                     style="width: 22px; height: 22px;">
            @else
                <i class="fa-solid fa-user-circle shrink-0" style="font-size: 22px;"></i>
            @endif
            <span class="truncate">{{ $user?->personalInformation?->full_name ?? $user?->name ?? 'Tenant' }}</span>
        </div>

        {{-- Nav --}}
        <ul class="flex-1 overflow-y-auto px-3 space-y-0.75 scrollbar-thin scrollbar-thumb-white/20">
            @foreach ($navItems as $item)
                @php
                    if (!empty($item['requiresTenant']) && !$isTenant) {
                        continue;
                    }

                    $isActive = $item['active'] ?? false;
                    $activeClasses = $isActive
                        ? 'text-[#e06800] bg-[#e06800]/10'
                        : 'hover:text-white';

                    // Check if this is the Services menu and is currently active
                    $isServicesActive = $isActive && $item['key'] === 'services';
                @endphp
                <li class="flex flex-col text-[#8fa0b5] rounded-lg text-[13.5px] font-medium">
                    <div class="flex items-center justify-between w-full py-2.5 px-3 rounded-lg transition-colors {{ $activeClasses }}">
                        <a
                            href="{{ $item['href'] }}"
                            wire:navigate.hover
                            @if(!empty($item['hasSub'])) onclick="openTenantServicesSubmenu()" @endif
                            class="flex items-center gap-3 flex-1"
                        >
                            <i class="{{ $item['icon'] }} w-[18px] text-center text-[15px] shrink-0"></i>
                            <span class="nav-text">{{ $item['label'] }}</span>
                        </a>

                        @if (!empty($item['hasSub']))
                            <button
                                type="button"
                                onclick="toggleTenantServicesSubmenu(event, this)"
                                aria-label="Toggle services submenu"
                                class="p-1 hover:text-white"
                            >
                                {{-- Set the initial icon based on whether Services is active --}}
                                <i id="tenant-services-chevron" class="fa-solid {{ $isServicesActive ? 'fa-chevron-up' : 'fa-chevron-down' }} text-[10px] transition-transform"></i>
                            </button>
                        @endif
                    </div>

                    @if (!empty($item['hasSub']))
                        {{-- Set the initial visibility based on whether Services is active --}}
                        <ul id="tenant-services-submenu" class="{{ $isServicesActive ? 'block' : 'hidden' }} pl-5 space-y-0.75 mt-0.5 mb-1">
                            @foreach ($servicesSub as $sub)
                                @php $subActive = $sub['active'] ?? false; @endphp
                                <li>
                                    <a
                                        href="{{ $sub['href'] }}"
                                        wire:navigate.hover
                                        class="flex items-center gap-2.5 py-2 px-3 rounded-lg text-[13px] transition-colors
                                               {{ $subActive ? 'bg-[#e06800] text-white font-semibold' : 'text-[#8fa0b5] hover:text-white' }}"
                                    >
                                        <i class="{{ $sub['icon'] }} text-[12px] w-[18px] text-center"></i>
                                        <span>{{ $sub['label'] }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </li>
            @endforeach
        </ul>

        {{-- Footer (Logout only) --}}
        <div class="p-3 border-t border-white/5 flex flex-col gap-0.5 shrink-0">
            <button
                type="button"
                onclick="document.getElementById('logout-form').requestSubmit()"
                class="flex items-center py-2.5 px-3 rounded-lg text-[13.5px] font-medium text-[#8fa0b5] hover:text-white transition-colors w-full"
            >
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-right-from-bracket w-[18px] text-center text-[15px] shrink-0"></i>
                    <span>Logout</span>
                </div>
            </button>
        </div>
    </aside>

    {{-- MAIN CONTENT AREA --}}
    <div class="flex-1 flex flex-col min-w-0 h-full overflow-y-auto relative">

        {{-- TOP HEADER (React) --}}
        <div data-react="top-header"
             data-props='{!! $headerProps !!}'
             class="sticky top-0 z-[100]"></div>

        <main class="flex-1">
            @yield('content')
        </main>
    </div>

    {{-- Sidebar & Submenu Logic --}}
    <script>
        // Sidebar Mobile Logic
        function openTenantSidebar() {
            const sidebar = document.getElementById('tenant-sidebar-blade');
            const overlay = document.getElementById('mobile-sidebar-overlay');
            if (sidebar) sidebar.style.transform = 'translateX(0)';
            if (overlay) overlay.classList.remove('hidden');
        }

        function closeTenantSidebar() {
            const sidebar = document.getElementById('tenant-sidebar-blade');
            const overlay = document.getElementById('mobile-sidebar-overlay');
            if (sidebar && window.innerWidth < 768) sidebar.style.transform = 'translateX(-100%)';
            if (overlay) overlay.classList.add('hidden');
        }

        function resetTenantSidebar() {
            const sidebar = document.getElementById('tenant-sidebar-blade');
            const overlay = document.getElementById('mobile-sidebar-overlay');
            if (sidebar) {
                sidebar.style.transform = window.innerWidth >= 768 ? 'translateX(0)' : 'translateX(-100%)';
            }
            if (overlay) overlay.classList.add('hidden');
        }

        // Submenu Logic
        function toggleTenantServicesSubmenu(event, btn) {
            event.preventDefault();
            event.stopPropagation();
            const submenu = document.getElementById('tenant-services-submenu');
            const icon = document.getElementById('tenant-services-chevron');
            
            if (submenu.classList.contains('hidden')) {
                submenu.classList.remove('hidden');
                submenu.classList.add('block');
                icon.classList.replace('fa-chevron-down', 'fa-chevron-up');
            } else {
                submenu.classList.add('hidden');
                submenu.classList.remove('block');
                icon.classList.replace('fa-chevron-up', 'fa-chevron-down');
            }
        }

        function openTenantServicesSubmenu() {
            const submenu = document.getElementById('tenant-services-submenu');
            const icon = document.getElementById('tenant-services-chevron');
            submenu.classList.remove('hidden');
            submenu.classList.add('block');
            icon.classList.replace('fa-chevron-down', 'fa-chevron-up');
        }

        // Bind events
        window.addEventListener('open-mobile-sidebar', openTenantSidebar);
        window.addEventListener('resize', resetTenantSidebar);
        
        document.getElementById('mobile-sidebar-overlay')?.addEventListener('click', closeTenantSidebar);

        document.addEventListener('click', (e) => {
            const link = e.target.closest('#tenant-sidebar-blade a');
            if (link && window.innerWidth < 768) closeTenantSidebar();
        });

        // Reset state on Livewire navigation & initial load
        document.addEventListener('livewire:navigated', resetTenantSidebar);
        resetTenantSidebar();
    </script>

    @livewireScripts
    @stack('scripts')
</body>
</html>