@extends('layouts.tenant', [
    'active' => 'dashboard',
    'pageTitle' => 'Dashboard',
])

@section('title', 'Dashboard')

@section('content')
    {{--
        Tenant dashboard (React): resources/js/pages/Dashboard/TenantDashboard.jsx
        Payload: App\Services\TenantDashboardService::payload()
        Full-width hero search → room categories → room browser. Room
        availability updates live over Pusher inside the component.
    --}}
    <div class="bg-[#F5F7FA] min-h-full">
        <div id="tenant-dashboard"
            data-props="{{ json_encode($dashboard, JSON_HEX_APOS | JSON_HEX_QUOT) }}">
            {{-- Server-rendered skeleton so there's never a blank screen before React mounts --}}
            <div aria-hidden="true">
                <div class="rb-skeleton h-[calc(100dvh-88px)] min-h-[560px]"></div>
                <div class="p-4 md:p-6 xl:p-8 max-w-[1440px] mx-auto grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    <div class="rb-skeleton rounded-2xl h-[400px]"></div>
                    <div class="rb-skeleton rounded-2xl h-[400px]"></div>
                    <div class="rb-skeleton rounded-2xl h-[400px]"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Booking modal shared with the Reservations page (handles .open-reserve-modal-btn) --}}
    <div id="tenant-reservations-modals" data-user-name="{{ $user->name }}" data-reservations="{}"></div>
@endsection
