{{-- resources/views/layouts/tenant.blade.php --}}
@php
    $user = auth()->user();

    // Prioritize the uploaded profile picture from personal_information
    $profilePic = $user?->personalInformation?->profile_picture ?? $user?->profile_picture;

    $avatarUrl = $profilePic
        ? (str_starts_with($profilePic, 'http')
            ? $profilePic
            : asset('storage/' . $profilePic))
        : null;

    // True only if an admin has approved this user's reservation
    $isTenant = \App\Models\Tenant::isApproved();

    // History lives under Dashboard (no sidebar entry) — keep Dashboard
    // highlighted while the user is on the History page.
    $sidebarActive = ($active ?? 'dashboard') === 'history'
        ? 'dashboard'
        : ($active ?? 'dashboard');

    $sidebarProps = json_encode([
        'active'   => $sidebarActive,
        'userName' => $user?->personalInformation?->full_name ?? $user?->name ?? 'Tenant',
        'userRole' => $isTenant ? 'Tenant' : 'New Tenant',
        'avatar'   => $avatarUrl,
        'isTenant' => $isTenant,
    ]);

    $headerProps = json_encode([
        'title'     => $pageTitle ?? 'Dashboard',
        'userName'  => $user?->personalInformation?->full_name ?? $user?->name ?? 'Tenant',
        'userRole'  => $isTenant ? 'Tenant' : 'New Tenant',
        'userEmail' => $user?->email ?? '',
        'avatar'    => $avatarUrl,
        'isTenant'  => $isTenant,
    ]);
@endphp

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Tenant Portal') — JLM Cozy Cabin</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
    @stack('styles')
</head>

<form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
    @csrf
</form>

<body class="bg-slate-50 flex min-h-screen text-slate-800">

    <div id="mobile-sidebar-overlay"
         class="fixed inset-0 bg-slate-900/50 z-[999] hidden md:hidden"></div>

    {{-- Tenant SIDEBAR (React) --}}
    <div data-react="sidebar"
         data-props='{!! $sidebarProps !!}'
         id="sidebar-mount"></div>

    <div class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto">

        {{-- Tenant TOP HEADER (React) --}}
        <div data-react="top-header"
             data-props='{!! $headerProps !!}'></div>

        <main class="flex-1">
            @yield('content')
        </main>
    </div>

    @stack('scripts')

    <script>
    document.addEventListener('mouseover', (e) => {
        const link = e.target.closest('a[href^="/tenants"]');
        if (!link || link.dataset.prefetched) return;
        link.dataset.prefetched = 'true';
        const prefetch = document.createElement('link');
        prefetch.rel = 'prefetch';
        prefetch.href = link.href;
        document.head.appendChild(prefetch);
    });
    </script>

</body>
</html>