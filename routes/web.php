<?php
// routes/web.php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\LandingController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PublicTourViewerController;
use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\VirtualTourController;
use App\Http\Controllers\Admin\TourHotspotController;
use App\Http\Controllers\Admin\TourSceneController;
use App\Http\Controllers\Admin\MaintenanceController;
use App\Http\Controllers\Admin\RoomChangeController;
use App\Http\Controllers\Admin\StayExtensionController;
use App\Http\Controllers\Admin\BillingController;
use App\Http\Controllers\Admin\ReservationController;
use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\HistoryController;
use App\Http\Controllers\Admin\ReportsController; // ← ADDED
use App\Models\TourScene;
use App\Models\VirtualTour;

// ============================================================
// PUBLIC ROUTES
// ============================================================

Route::get('/', [LandingController::class, 'index'])->name('landing');

Route::get('/tour/{virtualTour}', [PublicTourViewerController::class, 'show'])
    ->name('tour.viewer');

Route::get('/tour-scene-data/{scene}', function (TourScene $scene) {
    return response()->json([
        'scene'    => $scene,
        'hotspots' => $scene->hotspots,
    ]);
})->name('tour.scene-data');


// ============================================================
// AUTHENTICATION
// ============================================================
Route::middleware('guest')->group(function () {
    Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/signup',  [AuthController::class, 'showSignup'])->name('signup');
    Route::post('/signup', [AuthController::class, 'signup']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');


// ============================================================
// ONBOARDING (auth only)
// ============================================================
Route::middleware('auth')->group(function () {
    Route::get('/personal-information',   [AuthController::class, 'showPersonalInformation'])->name('personal-information');
    Route::post('/personal-information',  [AuthController::class, 'storePersonalInformation']);

    Route::get('/identity-verification',  [AuthController::class, 'showIdentityVerification'])->name('identity-verification');
    Route::post('/identity-verification', [AuthController::class, 'storeIdentityVerification']);
});


// ============================================================
// TENANT PORTAL
// ============================================================
Route::middleware('auth')->prefix('tenants')->name('tenants.')->group(function () {
    Route::view('/',               'tenants.dashboard')->name('dashboard');
    Route::view('/billing',        'tenants.billing')->name('billing');
    Route::view('/reservations',   'tenants.reservations')->name('reservations');
    Route::view('/messages',       'tenants.messages')->name('messages');
    Route::view('/announcements',  'tenants.announcements')->name('announcements');
    Route::view('/history',        'tenants.history')->name('history');
    Route::view('/settings',       'tenants.settings')->name('settings');

    Route::prefix('services')->name('services.')->group(function () {
        Route::view('/maintenance',    'tenants.services.maintenance')->name('maintenance');
        Route::view('/room-change',    'tenants.services.room-change')->name('room-change');
        Route::view('/stay-extension', 'tenants.services.stay-extension')->name('stay-extension');
    });
});


// ============================================================
// ADMIN PORTAL
// ============================================================
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {

    // ---------- Dashboard ----------
    Route::view('/', 'admin.dashboard')->name('dashboard');

    // ---------- Rooms CRUD ----------
    Route::get('/rooms',           [RoomController::class, 'index'])->name('rooms');
    Route::post('/rooms',          [RoomController::class, 'store'])->name('rooms.store');
    Route::get('/rooms/{room}',    [RoomController::class, 'show'])->name('rooms.show');
    Route::put('/rooms/{room}',    [RoomController::class, 'update'])->name('rooms.update');
    Route::delete('/rooms/{room}', [RoomController::class, 'destroy'])->name('rooms.destroy');

    // ---------- Tenants ----------
    Route::get('/tenants', [TenantController::class, 'index'])->name('tenants');

    // ---------- Billing & Payments CRUD ----------
    Route::get('/billing',                      [BillingController::class, 'index'])->name('billing');
    Route::post('/billing',                     [BillingController::class, 'store'])->name('billing.store');
    Route::get('/billing/{invoice}',            [BillingController::class, 'show'])->name('billing.show');
    Route::put('/billing/{invoice}',            [BillingController::class, 'update'])->name('billing.update');
    Route::delete('/billing/{invoice}',         [BillingController::class, 'destroy'])->name('billing.destroy');
    Route::post('/billing/{invoice}/mark-paid', [BillingController::class, 'markPaid'])->name('billing.mark-paid');
    Route::post('/billing/{invoice}/remind',    [BillingController::class, 'sendReminder'])->name('billing.remind');

    // ---------- Reservations CRUD ----------
    Route::get('/reservations',                        [ReservationController::class, 'index'])->name('reservations');
    Route::post('/reservations',                       [ReservationController::class, 'store'])->name('reservations.store');
    Route::get('/reservations/{reservation}',          [ReservationController::class, 'show'])->name('reservations.show');
    Route::put('/reservations/{reservation}',          [ReservationController::class, 'update'])->name('reservations.update');
    Route::delete('/reservations/{reservation}',       [ReservationController::class, 'destroy'])->name('reservations.destroy');

    Route::post('/reservations/{reservation}/approve', [ReservationController::class, 'approve'])->name('reservations.approve');
    Route::post('/reservations/{reservation}/reject',  [ReservationController::class, 'reject'])->name('reservations.reject');

    // ---------- Announcements CRUD ----------
    Route::get('/announcements',                            [AnnouncementController::class, 'index'])->name('announcements');
    Route::post('/announcements',                           [AnnouncementController::class, 'store'])->name('announcements.store');
    Route::get('/announcements/{announcement}',             [AnnouncementController::class, 'show'])->name('announcements.show');
    Route::put('/announcements/{announcement}',             [AnnouncementController::class, 'update'])->name('announcements.update');
    Route::delete('/announcements/{announcement}',          [AnnouncementController::class, 'destroy'])->name('announcements.destroy');

    Route::post('/announcements/{announcement}/toggle-pin', [AnnouncementController::class, 'togglePin'])->name('announcements.toggle-pin');

    // ---------- History (aggregate view) ----------
    Route::get('/history', [HistoryController::class, 'index'])->name('history');

    // ---------- Reports (aggregate view) ----------
    Route::get('/reports', [ReportsController::class, 'index'])->name('reports');

    // ---------- Static views ----------
    Route::view('/messages',      'admin.messages')->name('messages');
    Route::view('/audit-logs',    'admin.audit-logs')->name('audit-logs');
    Route::view('/settings',      'admin.settings')->name('settings');

    // ============================================================
    // SERVICES
    // ============================================================
    Route::prefix('services')->name('services.')->group(function () {

        // ---------- Maintenance Requests CRUD ----------
        Route::get('/maintenance',                      [MaintenanceController::class, 'index'])->name('maintenance');
        Route::post('/maintenance',                     [MaintenanceController::class, 'store'])->name('maintenance.store');
        Route::get('/maintenance/{maintenanceRequest}', [MaintenanceController::class, 'show'])->name('maintenance.show');
        Route::put('/maintenance/{maintenanceRequest}', [MaintenanceController::class, 'update'])->name('maintenance.update');
        Route::delete('/maintenance/{maintenanceRequest}', [MaintenanceController::class, 'destroy'])->name('maintenance.destroy');

        // ---------- Room Change Requests CRUD ----------
        Route::get('/room-change',                          [RoomChangeController::class, 'index'])->name('room-change');
        Route::post('/room-change',                         [RoomChangeController::class, 'store'])->name('room-change.store');
        Route::get('/room-change/{roomChangeRequest}',      [RoomChangeController::class, 'show'])->name('room-change.show');
        Route::put('/room-change/{roomChangeRequest}',      [RoomChangeController::class, 'update'])->name('room-change.update');
        Route::delete('/room-change/{roomChangeRequest}',   [RoomChangeController::class, 'destroy'])->name('room-change.destroy');

        Route::post('/room-change/{roomChangeRequest}/approve', [RoomChangeController::class, 'approve'])->name('room-change.approve');
        Route::post('/room-change/{roomChangeRequest}/reject',  [RoomChangeController::class, 'reject'])->name('room-change.reject');

        // ---------- Stay Extension Requests CRUD ----------
        Route::get('/stay-extension',                            [StayExtensionController::class, 'index'])->name('stay-extension');
        Route::post('/stay-extension',                           [StayExtensionController::class, 'store'])->name('stay-extension.store');
        Route::get('/stay-extension/{stayExtensionRequest}',     [StayExtensionController::class, 'show'])->name('stay-extension.show');
        Route::put('/stay-extension/{stayExtensionRequest}',     [StayExtensionController::class, 'update'])->name('stay-extension.update');
        Route::delete('/stay-extension/{stayExtensionRequest}',  [StayExtensionController::class, 'destroy'])->name('stay-extension.destroy');

        Route::post('/stay-extension/{stayExtensionRequest}/approve', [StayExtensionController::class, 'approve'])->name('stay-extension.approve');
        Route::post('/stay-extension/{stayExtensionRequest}/reject',  [StayExtensionController::class, 'reject'])->name('stay-extension.reject');
    });

    // ============================================================
    // VIRTUAL TOUR
    // ============================================================
    Route::prefix('virtual-tour')->name('virtual-tour.')->group(function () {

        Route::get('/',                       [VirtualTourController::class, 'index'])->name('index');
        Route::post('/',                      [VirtualTourController::class, 'store'])->name('store');
        Route::put('/{virtualTour}',          [VirtualTourController::class, 'update'])->name('update');
        Route::delete('/{virtualTour}',       [VirtualTourController::class, 'destroy'])->name('destroy');
        Route::patch('/{virtualTour}/status', [VirtualTourController::class, 'toggleStatus'])->name('status');

        Route::get('/{virtualTour}/builder', function (VirtualTour $virtualTour) {
            return view('admin.virtual-tour-builder', compact('virtualTour'));
        })->name('builder');

        Route::prefix('{virtualTour}/builder')->name('builder.')->group(function () {
            Route::get('/data', [TourHotspotController::class, 'getTourData'])->name('data');
            Route::post('/hotspots', [TourHotspotController::class, 'store'])->name('save-hotspot');
            Route::post('/hotspots/{hotspot}', [TourHotspotController::class, 'update'])->name('update-hotspot');
            Route::match(['POST', 'DELETE'], '/hotspots/{hotspot}', [TourHotspotController::class, 'destroy'])->name('delete-hotspot');
        });

        Route::prefix('{virtualTour}/scenes')->name('scenes.')->group(function () {
            Route::get('/',                       [TourSceneController::class, 'index'])->name('index');
            Route::post('/',                      [TourSceneController::class, 'store'])->name('store');
            Route::put('/{scene}',                [TourSceneController::class, 'update'])->name('update');
            Route::delete('/{scene}',             [TourSceneController::class, 'destroy'])->name('destroy');
            Route::patch('/{scene}/set-starting', [TourSceneController::class, 'setStarting'])->name('set-starting');
        });
    });
});