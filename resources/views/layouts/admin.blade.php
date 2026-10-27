{{-- resources/views/layouts/admin.blade.php --}}
@php
    $user = auth()->user();

    $profilePic = $user?->personalInformation?->profile_picture ?? $user?->profile_picture;

    $avatarUrl = $profilePic
        ? (str_starts_with($profilePic, 'http')
            ? $profilePic
            : asset('storage/' . $profilePic))
        : null;

    $sidebarProps = json_encode([
        'active'   => $active ?? 'dashboard',
        'userName' => $user?->personalInformation?->full_name ?? $user?->name ?? 'Administrator',
        'userRole' => 'Administrator',
        'avatar'   => $avatarUrl,
    ]);

    $headerProps = json_encode([
        'title'         => $pageTitle ?? 'Dashboard',
        'userName'      => $user?->personalInformation?->full_name ?? $user?->name ?? 'Administrator',
        'userRole'      => 'Admin',
        'userEmail'     => $user?->email ?? '',
        'avatar'        => $avatarUrl,
        'showHamburger' => true,
    ]);

    // ── Broadcast config (consumed by app.jsx) ──
    $broadcastConfig = [
        'key'     => env('PUSHER_APP_KEY', ''),
        'cluster' => env('PUSHER_APP_CLUSTER', 'ap1'),
    ];
@endphp

<!DOCTYPE html>
<html lang="en" class="h-full w-full overflow-hidden">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Reinforce anti-Back-Button behaviour on the client side --}}
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, max-age=0">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">

    <title>@yield('title', 'Admin Portal') — JLM Cozy Cabin</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    {{--
        ── Echo bootstrap config only ──
        Echo is created in resources/js/app.jsx (single source of truth).
        Do NOT add <script src=".../pusher.min.js"> or new Echo(...) here.
    --}}
    <script>
        window.__appConfig = {
            userId:  {{ $user?->id ?? 'null' }},
            role:    @json($user?->role ?? 'guest'),
            isAdmin: {{ $user?->isAdmin() ? 'true' : 'false' }},
            csrf:    @json(csrf_token()),
            broadcast: @json($broadcastConfig),
        };
    </script>

    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
    @stack('styles')
</head>

<body class="bg-[#f4f6f9] text-slate-900 antialiased flex h-full w-full overflow-hidden">

    {{-- Hidden logout form — submitted by LogoutModal via confirmLogout() --}}
    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
        @csrf
    </form>

    <div id="admin-sidebar-overlay"
         class="fixed inset-0 bg-slate-900/50 z-[999] hidden md:hidden backdrop-blur-sm"></div>

    <div data-react="admin-sidebar"
         data-props='{!! $sidebarProps !!}'
         id="admin-sidebar-mount"
         class="shrink-0 h-full flex flex-col"></div>

    <div class="flex-1 flex flex-col min-w-0 h-full overflow-y-auto">

        <div data-react="admin-top-header" data-props='{!! $headerProps !!}'></div>

        <main class="flex-1">
            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>
</html>