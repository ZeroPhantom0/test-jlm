{{-- resources/views/layouts/tenant.blade.php --}}
@php
    $user = auth()->user();
    $sidebarProps = json_encode(['active' => $active ?? 'dashboard']);
    $headerProps = json_encode([
        'title'    => $pageTitle ?? 'Dashboard',
        'userName' => $user->name ?? 'Tenant',
        'userRole' => 'Tenant',
        'avatar'   => $user->profile_picture 
                        ? asset('storage/' . $user->profile_picture) 
                        : 'https://i.pravatar.cc/100?img=47',
    ]);
@endphp

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Tenant Portal') — JLM Cozy Cabin</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
</head>

{{-- Hidden logout form (POST + CSRF) --}}
<form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
    @csrf
</form>

<body class="bg-slate-50 flex min-h-screen text-slate-800">

    @php
        $sidebarProps = json_encode(['active' => $active ?? 'dashboard']);
        $headerProps = json_encode([
            'title'    => $pageTitle ?? 'Dashboard',
            'userName' => 'Elle Dela Cruz',
            'userRole' => 'Tenant',
            'avatar'   => 'https://i.pravatar.cc/100?img=47',
        ]);
    @endphp

    {{-- Mobile backdrop --}}
    <div id="mobile-sidebar-overlay"
         class="fixed inset-0 bg-slate-900/50 z-999 hidden md:hidden"></div>

    {{-- SIDEBAR (React component) — persistent across navigation --}}
    <div data-react="sidebar"
         data-props='{!! $sidebarProps !!}'
         id="sidebar-mount"
         wire:persist="sidebar"></div>

    {{-- MAIN WRAPPER --}}
    <div class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto">

        {{-- TOP HEADER (React component) — persistent across navigation --}}
        <div data-react="top-header"
             data-props='{!! $headerProps !!}'
             wire:persist="top-header"></div>

        {{-- PAGE CONTENT — this is the only thing that swaps --}}
        <main class="flex-1">
            @yield('content')
        </main>
    </div>

    @stack('scripts')

    <script>
    // Prefetch linked pages when the user hovers over them
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