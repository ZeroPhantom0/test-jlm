<?php
// routes/web.php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\RoomController;

// ============ PUBLIC LANDING PAGE ============
Route::get('/', fn () => Inertia::render('Landing/Index'))->name('landing');

// ============ AUTHENTICATION ============
Route::middleware('guest')->group(function () {
    Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/signup',  [AuthController::class, 'showSignup'])->name('signup');
    Route::post('/signup', [AuthController::class, 'signup']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ============ ONBOARDING (auth only) ============
Route::middleware('auth')->group(function () {
    Route::get('/personal-information',   [AuthController::class, 'showPersonalInformation'])->name('personal-information');
    Route::post('/personal-information',  [AuthController::class, 'storePersonalInformation']);

    Route::get('/identity-verification',  [AuthController::class, 'showIdentityVerification'])->name('identity-verification');
    Route::post('/identity-verification', [AuthController::class, 'storeIdentityVerification']);
});

// ============ TENANT PORTAL ============
Route::middleware('auth')->prefix('tenants')->name('tenants.')->group(function () {
    Route::view('/', 'tenants.dashboard')->name('dashboard');
    Route::view('/billing', 'tenants.billing')->name('billing');
    Route::view('/reservations', 'tenants.reservations')->name('reservations');
    Route::view('/messages', 'tenants.messages')->name('messages');
    Route::view('/announcements', 'tenants.announcements')->name('announcements');
    Route::view('/history', 'tenants.history')->name('history');
    Route::view('/settings', 'tenants.settings')->name('settings');

    Route::prefix('services')->name('services.')->group(function () {
        Route::view('/maintenance', 'tenants.services.maintenance')->name('maintenance');
        Route::view('/room-change', 'tenants.services.room-change')->name('room-change');
        Route::view('/stay-extension', 'tenants.services.stay-extension')->name('stay-extension');
    });
});

// ============ ADMIN PORTAL ============
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::view('/', 'admin.dashboard')->name('dashboard');

    // Rooms CRUD
    Route::get('/rooms',           [RoomController::class, 'index'])->name('rooms');
    Route::post('/rooms',          [RoomController::class, 'store'])->name('rooms.store');
    Route::get('/rooms/{room}',    [RoomController::class, 'show'])->name('rooms.show');
    Route::put('/rooms/{room}',    [RoomController::class, 'update'])->name('rooms.update');
    Route::delete('/rooms/{room}', [RoomController::class, 'destroy'])->name('rooms.destroy');

    Route::view('/tenants', 'admin.tenants')->name('tenants');
    Route::view('/billing', 'admin.billing')->name('billing');
    Route::view('/reservations', 'admin.reservations')->name('reservations');
    Route::view('/messages', 'admin.messages')->name('messages');
    Route::view('/announcements', 'admin.announcements')->name('announcements');
    Route::view('/virtual-tour', 'admin.virtual-tour')->name('virtual-tour');
    Route::view('/reports', 'admin.reports')->name('reports');
    Route::view('/history', 'admin.history')->name('history');
    Route::view('/audit-logs', 'admin.audit-logs')->name('audit-logs');
    Route::view('/settings', 'admin.settings')->name('settings');

    Route::prefix('services')->name('services.')->group(function () {
        Route::view('/maintenance', 'admin.services.maintenance')->name('maintenance');
        Route::view('/room-change', 'admin.services.room-change')->name('room-change');
        Route::view('/stay-extension', 'admin.services.stay-extension')->name('stay-extension');
    });
});