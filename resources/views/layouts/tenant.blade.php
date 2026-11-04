{{-- resources/views/layouts/tenant.blade.php --}}
@php
    $user = auth()->user();
    $profilePic = $user?->personalInformation?->profile_picture ?? $user?->profile_picture;
    $avatarUrl = $profilePic
        ? (str_starts_with($profilePic, 'http')
            ? $profilePic
            : asset('storage/' . $profilePic))
        : null;
    $isTenant = \App\Models\Tenant::isApproved();

    $headerProps = json_encode([
        'title' => $pageTitle ?? 'Dashboard',
        'userName' => $user?->personalInformation?->full_name ?? ($user?->name ?? 'Tenant'),
        'userRole' => $isTenant ? 'Tenant' : 'New Tenant',
        'userEmail' => $user?->email ?? '',
        'avatar' => $avatarUrl,
        'isTenant' => $isTenant,
    ]);

    $broadcastConfig = [
        'key' => env('PUSHER_APP_KEY', ''),
        'cluster' => env('PUSHER_APP_CLUSTER', 'ap1'),
    ];

    $sidebarProps = json_encode([
        'active'   => $active ?? 'dashboard',
        'userName' => $user?->personalInformation?->full_name ?? ($user?->name ?? 'Tenant'),
        'userRole' => $isTenant ? 'Tenant' : 'New Tenant',
        'avatar'   => $avatarUrl,
        'isTenant' => $isTenant,
    ]);
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
            userId: {{ $user?->id ?? 'null' }},
            role: @json($user?->role ?? 'guest'),
            isTenant: {{ $user?->isTenant() ? 'true' : 'false' }},
            csrf: @json(csrf_token()),
            broadcast: @json($broadcastConfig),
            tenantId: {{ $user?->tenant?->id ?? 'null' }},
        };
    </script>

    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
    @stack('styles')
</head>

<body class="bg-slate-50 flex h-full w-full overflow-hidden text-slate-800">

    <div id="mobile-sidebar-overlay" class="fixed inset-0 bg-slate-900/50 z-[999] hidden md:hidden"></div>

    {{-- SIDEBAR (React) --}}
    <div 
        id="tenant-sidebar-root" 
        data-props="{!! htmlspecialchars($sidebarProps, ENT_QUOTES, 'UTF-8') !!}"
    ></div>

    {{-- MAIN CONTENT AREA --}}
    <div class="flex-1 flex flex-col min-w-0 h-full overflow-y-auto relative">

        {{-- TOP HEADER (React) --}}
        <div data-react="top-header" data-props='{!! $headerProps !!}' class="sticky top-0 z-[100]"></div>

        <main class="flex-1">
            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>

</html>