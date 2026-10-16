{{-- resources/views/layouts/admin.blade.php --}}
@php
    $user = auth()->user();
    $sidebarProps = json_encode(['active' => $active ?? 'dashboard']);
    $headerProps = json_encode([
        'title'    => $pageTitle ?? 'Dashboard',
        'userName' => $user->name ?? 'Admin',
        'userRole' => 'Admin',
        'userEmail' => $user->email ?? '',
        'avatar'   => $user->profile_picture 
                        ? asset('storage/' . $user->profile_picture) 
                        : 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&q=80&w=120',
        'showHamburger' => true,
    ]);
@endphp

<!DOCTYPE html>
<html lang="en" class="h-full w-full overflow-hidden">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}"> 
    <title>@yield('title', 'Admin Portal') — JLM Cozy Cabin</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
    @stack('styles')
</head>
{{-- Hidden logout form (POST + CSRF) --}}
<form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
    @csrf
</form>
<body class="bg-[#f4f6f9] text-slate-900 antialiased flex h-full w-full overflow-hidden">

    {{-- Mobile backdrop --}}
    <div id="admin-sidebar-overlay"
         class="fixed inset-0 bg-slate-900/50 z-999 hidden md:hidden backdrop-blur-sm"></div>

    {{-- SIDEBAR (React) --}}
    <div data-react="admin-sidebar"
        data-props='{!! $sidebarProps !!}'
        id="admin-sidebar-mount"
        class="shrink-0 h-full flex flex-col"></div>

    {{-- MAIN WRAPPER --}}
    <div class="flex-1 flex flex-col min-w-0 h-full overflow-y-auto">

        {{-- TOP HEADER (React) --}}
        <div data-react="admin-top-header" data-props='{!! $headerProps !!}'></div>

        {{-- PAGE CONTENT --}}
        <main class="flex-1">
            @yield('content')
        </main>
    </div>

    @stack('scripts')

</body>
</html>