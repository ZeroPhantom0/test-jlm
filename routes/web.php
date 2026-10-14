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
use App\Models\TourScene;
use App\Models\VirtualTour;

// ============================================================
// PUBLIC ROUTES
// ============================================================

// Landing page (rooms + virtual tour availability)
Route::get('/', [LandingController::class, 'index'])->name('landing');

// Full-page public virtual tour viewer
Route::get('/tour/{virtualTour}', [PublicTourViewerController::class, 'show'])
    ->name('tour.viewer');

// AJAX: scene + hotspots (used by the public viewer)
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

    // ---------- Static views ----------
    Route::view('/billing',       'admin.billing')->name('billing');
    Route::view('/reservations',  'admin.reservations')->name('reservations');
    Route::view('/messages',      'admin.messages')->name('messages');
    Route::view('/announcements', 'admin.announcements')->name('announcements');
    Route::view('/reports',       'admin.reports')->name('reports');
    Route::view('/history',       'admin.history')->name('history');
    Route::view('/audit-logs',    'admin.audit-logs')->name('audit-logs');
    Route::view('/settings',      'admin.settings')->name('settings');

    // ---------- Services ----------
    Route::prefix('services')->name('services.')->group(function () {
        Route::view('/maintenance',    'admin.services.maintenance')->name('maintenance');
        Route::view('/room-change',    'admin.services.room-change')->name('room-change');
        Route::view('/stay-extension', 'admin.services.stay-extension')->name('stay-extension');
    });

    // ============================================================
    // VIRTUAL TOUR
    // ============================================================
    Route::prefix('virtual-tour')->name('virtual-tour.')->group(function () {

        // ---- CRUD ----
        Route::get('/',                       [VirtualTourController::class, 'index'])->name('index');
        Route::post('/',                      [VirtualTourController::class, 'store'])->name('store');
        Route::put('/{virtualTour}',          [VirtualTourController::class, 'update'])->name('update');
        Route::delete('/{virtualTour}',       [VirtualTourController::class, 'destroy'])->name('destroy');
        Route::patch('/{virtualTour}/status', [VirtualTourController::class, 'toggleStatus'])->name('status');

        // ---- Builder (scene + hotspot editor) ----
        Route::get('/{virtualTour}/builder', function (VirtualTour $virtualTour) {
            return view('admin.virtual-tour-builder', compact('virtualTour'));
        })->name('builder');

        // ---- Builder AJAX endpoints ----
        Route::prefix('{virtualTour}/builder')->name('builder.')->group(function () {
            // Fetch scene + hotspots for the viewer
            Route::get('/data', [TourHotspotController::class, 'getTourData'])
                ->name('data');

            // Create hotspot
            Route::post('/hotspots', [TourHotspotController::class, 'store'])
                ->name('save-hotspot');

            // Update hotspot
            Route::post('/hotspots/{hotspot}', [TourHotspotController::class, 'update'])
                ->name('update-hotspot');

            // Delete hotspot (POST + DELETE, so _method=DELETE spoofing works)
            Route::match(['POST', 'DELETE'], '/hotspots/{hotspot}', [TourHotspotController::class, 'destroy'])
                ->name('delete-hotspot');
        });

        // ---- Scenes manager ----
        Route::prefix('{virtualTour}/scenes')->name('scenes.')->group(function () {
            Route::get('/',                       [TourSceneController::class, 'index'])->name('index');
            Route::post('/',                      [TourSceneController::class, 'store'])->name('store');
            Route::put('/{scene}',                [TourSceneController::class, 'update'])->name('update');
            Route::delete('/{scene}',             [TourSceneController::class, 'destroy'])->name('destroy');
            Route::patch('/{scene}/set-starting', [TourSceneController::class, 'setStarting'])->name('set-starting');
        });
    });
});