<?php
// routes/web.php

use Illuminate\Support\Facades\Route;

// Tenant Portal Routes
Route::prefix('tenants')->name('tenants.')->group(function () {
    Route::view('/', 'tenants.dashboard')->name('dashboard');
    Route::view('/billing', 'tenants.billing')->name('billing');
    Route::view('/reservations', 'tenants.reservations')->name('reservations');
    Route::view('/messages', 'tenants.messages')->name('messages');
    Route::view('/announcements', 'tenants.announcements')->name('announcements');
    Route::view('/history', 'tenants.history')->name('history');
    Route::view('/settings', 'tenants.settings')->name('settings');

    // Services
    Route::prefix('services')->name('services.')->group(function () {
        Route::view('/maintenance', 'tenants.services.maintenance')->name('maintenance');
        Route::view('/room-change', 'tenants.services.room-change')->name('room-change');
        Route::view('/stay-extension', 'tenants.services.stay-extension')->name('stay-extension');
    });
});

// Redirect root to tenants dashboard
Route::redirect('/', '/tenants');

// ============ ADMIN PORTAL ============
Route::prefix('admin')->name('admin.')->group(function () {
    Route::view('/', 'admin.dashboard')->name('dashboard');
    Route::view('/rooms', 'admin.rooms')->name('rooms');
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

    // Services
    Route::prefix('services')->name('services.')->group(function () {
        Route::view('/maintenance', 'admin.services.maintenance')->name('maintenance');
        Route::view('/room-change', 'admin.services.room-change')->name('room-change');
        Route::view('/stay-extension', 'admin.services.stay-extension')->name('stay-extension');
    });
});

// Redirect root to admin dashboard
Route::redirect('/', '/admin');