{{-- resources/views/layouts/tenant.blade.php --}}
@php
    $user = auth()->user();

    $avatarUrl = $user && $user->profile_picture
        ? (str_starts_with($user->profile_picture, 'http')
            ? $user->profile_picture
            : asset('storage/' . $user->profile_picture))
        : 'https://i.pravatar.cc/100?img=47';

    $sidebarProps = json_encode([
        'active'   => $active ?? 'dashboard',
        'userName' => $user?->name ?? 'Tenant',
        'userRole' => 'Tenant',
        'avatar'   => $avatarUrl,
    ]);

    $headerProps = json_encode([
        'title'     => $pageTitle ?? 'Dashboard',
        'userName'  => $user?->name ?? 'Tenant',
        'userRole'  => 'Tenant',
        'userEmail' => $user?->email ?? '',
        'avatar'    => $avatarUrl,
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