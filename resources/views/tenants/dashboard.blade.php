@extends('layouts.tenant', [
    'active' => 'dashboard',
    'pageTitle' => 'Dashboard',
])

@section('title', 'Dashboard')

@section('content')
    {{--
        Tenant dashboard (React): resources/js/pages/Dashboard/TenantDashboard.jsx
        Payload: App\Services\TenantDashboardService::payload()
        Live updates come over the existing Pusher channels inside the component,
        so this page no longer uses the data-live-page HTML refresh.
    --}}
    <div class="p-4 md:p-6 xl:p-8 max-w-[1440px] mx-auto bg-[#F5F7FA] min-h-full">
        <div id="tenant-dashboard"
            data-props="{{ json_encode($dashboard, JSON_HEX_APOS | JSON_HEX_QUOT) }}">
            {{-- Server-rendered skeleton so there's never a blank screen before React mounts --}}
            <div class="grid grid-cols-1 md:grid-cols-6 xl:grid-cols-12 gap-4" aria-hidden="true">
                <div class="rb-skeleton rounded-2xl h-[170px] md:col-span-6 xl:col-span-12"></div>
                <div class="rb-skeleton rounded-2xl h-[320px] md:col-span-6 xl:col-span-8"></div>
                <div class="rb-skeleton rounded-2xl h-[320px] md:col-span-6 xl:col-span-4"></div>
            </div>
        </div>
    </div>

    {{-- Booking modal shared with the Reservations page (handles .open-reserve-modal-btn) --}}
    <div id="tenant-reservations-modals" data-user-name="{{ $user->name }}" data-reservations="{}"></div>

    {{-- Pay Now modal (handles .open-payment-modal; data-invoice kept in sync by the dashboard) --}}
    <div id="payment-modal-root"
        @if (!empty($dashboard['bills']['payNow'])) data-invoice="{{ json_encode($dashboard['bills']['payNow'], JSON_HEX_APOS | JSON_HEX_QUOT) }}" @endif>
    </div>
@endsection
