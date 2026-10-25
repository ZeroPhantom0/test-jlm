<?php
// routes/web.php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\LandingController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PublicTourViewerController;
use App\Http\Controllers\AdminNotificationController;
use App\Http\Controllers\TenantNotificationController;

// Admin Controllers
use App\Http\Controllers\Admin\DashboardController;
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
use App\Http\Controllers\Admin\ReportsController;
use App\Http\Controllers\Admin\MessageController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\SettingsController;

// Tenant Controllers
use App\Http\Controllers\Tenant\TenantDashboardController;
use App\Http\Controllers\Tenant\TenantBillingController;
use App\Http\Controllers\Tenant\TenantMaintenanceController;
use App\Http\Controllers\Tenant\TenantRoomChangeController;
use App\Http\Controllers\Tenant\TenantStayExtensionController;
use App\Http\Controllers\Tenant\TenantAnnouncementController;
use App\Http\Controllers\Tenant\TenantHistoryController;
use App\Http\Controllers\Tenant\TenantMessageController;
use App\Http\Controllers\Tenant\TenantReservationController;
use App\Http\Controllers\Tenant\TenantSettingsController;

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

    Route::get('/auth/google',          [AuthController::class, 'redirectToGoogle'])->name('auth.google');
    Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');

    Route::get('/login/verify',   [AuthController::class, 'showVerify'])->name('login.verify');
    Route::post('/login/verify',  [AuthController::class, 'verify'])->name('login.verify.submit');
    Route::post('/login/resend',  [AuthController::class, 'resendCode'])->name('login.resend');
    Route::post('/login/cancel',  [AuthController::class, 'cancelVerification'])->name('login.cancel');

    // ---------- PASSWORD RESET ----------
    Route::get('/forgot-password',  [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendPasswordResetLink'])->name('password.email');
    Route::get('/password/reset/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/password/reset',        [AuthController::class, 'resetPassword'])->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');


// ============================================================
// ONBOARDING (auth only)
// ============================================================
Route::middleware('auth')->group(function () {
    Route::get('/personal-information', [AuthController::class, 'showPersonalInformation'])
        ->middleware('onboarding.step:personal')
        ->name('personal-information');

    Route::post('/personal-information', [AuthController::class, 'storePersonalInformation'])
        ->middleware('onboarding.step:personal');

    Route::get('/identity-verification', [AuthController::class, 'showIdentityVerification'])
        ->middleware('onboarding.step:identity')
        ->name('identity-verification');

    Route::post('/identity-verification', [AuthController::class, 'storeIdentityVerification'])
        ->middleware('onboarding.step:identity');
});


// ============================================================
// TENANT PORTAL
// ============================================================
Route::middleware(['auth', 'onboarding.complete'])
    ->prefix('tenants')
    ->name('tenants.')
    ->group(function () {

        Route::get('/', [TenantDashboardController::class, 'index'])->name('dashboard');

        Route::get('/notifications/feed', [TenantNotificationController::class, 'index'])
            ->name('notifications.feed');

        Route::get('/billing',         [TenantBillingController::class, 'index'])->name('billing');
        Route::post('/billing/pay',    [TenantBillingController::class, 'pay'])->name('billing.pay');
        Route::post('/billing/cancel', [TenantBillingController::class, 'cancelPayment'])->name('billing.cancel');

        Route::get('/reservations',                       [TenantReservationController::class, 'index'])->name('reservations');
        Route::post('/reservations',                      [TenantReservationController::class, 'store'])->name('reservations.store');
        Route::post('/reservations/{reservation}/cancel', [TenantReservationController::class, 'cancel'])->name('reservations.cancel');

        Route::get('/messages',                       [TenantMessageController::class, 'index'])->name('messages');
        Route::post('/messages',                      [TenantMessageController::class, 'store'])->name('messages.store');
        Route::get('/messages/{conversation}',        [TenantMessageController::class, 'show'])->name('messages.show');
        Route::post('/messages/{conversation}/reply', [TenantMessageController::class, 'reply'])->name('messages.reply');

        Route::delete('/messages/{message}',           [TenantMessageController::class, 'deleteMessage'])->name('messages.delete');
        Route::post('/messages/{message}/react',       [TenantMessageController::class, 'reactToMessage'])->name('messages.react');
        Route::delete('/messages/conversation/{conversation}', [TenantMessageController::class, 'deleteConversation'])->name('messages.conversation.delete');

        Route::get('/announcements', [TenantAnnouncementController::class, 'index'])->name('announcements');
        Route::get('/history',       [TenantHistoryController::class, 'index'])->name('history');

        // --- TENANT SETTINGS ROUTES ---
        Route::get('/settings',           [TenantSettingsController::class, 'index'])->name('settings');
        Route::put('/settings/profile',   [TenantSettingsController::class, 'updateProfile'])->name('settings.update');
        Route::put('/settings/password',  [TenantSettingsController::class, 'updatePassword'])->name('settings.password');

        Route::prefix('services')->name('services.')->group(function () {
            Route::get('/maintenance',                               [TenantMaintenanceController::class, 'index'])->name('maintenance');
            Route::post('/maintenance',                              [TenantMaintenanceController::class, 'store'])->name('maintenance.store');
            Route::post('/maintenance/{maintenanceRequest}/cancel',  [TenantMaintenanceController::class, 'cancel'])->name('maintenance.cancel');

            Route::get('/room-change',                               [TenantRoomChangeController::class, 'index'])->name('room-change');
            Route::post('/room-change',                              [TenantRoomChangeController::class, 'store'])->name('room-change.store');
            Route::post('/room-change/{roomChangeRequest}/cancel',   [TenantRoomChangeController::class, 'cancel'])->name('room-change.cancel');

            Route::get('/stay-extension',                                [TenantStayExtensionController::class, 'index'])->name('stay-extension');
            Route::post('/stay-extension',                               [TenantStayExtensionController::class, 'store'])->name('stay-extension.store');
            Route::post('/stay-extension/{stayExtensionRequest}/cancel', [TenantStayExtensionController::class, 'cancel'])->name('stay-extension.cancel');
        });
    });


// ============================================================
// ADMIN PORTAL
// ============================================================
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/notifications/feed', [AdminNotificationController::class, 'index'])
        ->name('notifications.feed');

    Route::post('/notifications/message-read', [AdminNotificationController::class, 'markMessageRead'])
        ->name('notifications.message-read');

    // ---------- Rooms ----------
    Route::get('/rooms',           [RoomController::class, 'index'])->name('rooms');
    Route::post('/rooms',          [RoomController::class, 'store'])->name('rooms.store');
    Route::get('/rooms/{room}',    [RoomController::class, 'show'])->name('rooms.show');
    Route::put('/rooms/{room}',    [RoomController::class, 'update'])->name('rooms.update');
    Route::delete('/rooms/{room}', [RoomController::class, 'destroy'])->name('rooms.destroy');

    // ---------- Tenants ----------
    Route::get('/tenants',          [TenantController::class, 'index'])->name('tenants');
    Route::put('/tenants/{tenant}', [TenantController::class, 'update'])->name('tenants.update');

    // ---------- Billing ----------
    Route::get('/billing',                                          [BillingController::class, 'index'])->name('billing');
    Route::post('/billing',                                         [BillingController::class, 'store'])->name('billing.store');
    Route::get('/billing/{invoice:transaction_no}',                 [BillingController::class, 'show'])->name('billing.show');
    Route::put('/billing/{invoice:transaction_no}',                 [BillingController::class, 'update'])->name('billing.update');
    Route::delete('/billing/{invoice:transaction_no}',              [BillingController::class, 'destroy'])->name('billing.destroy');
    Route::post('/billing/{invoice:transaction_no}/mark-paid',      [BillingController::class, 'markPaid'])->name('billing.mark-paid');
    Route::post('/billing/{invoice:transaction_no}/remind',         [BillingController::class, 'sendReminder'])->name('billing.remind');
    Route::post('/billing/{invoice:transaction_no}/verify-payment', [BillingController::class, 'verifyPayment'])->name('billing.verify-payment');
    Route::post('/billing/{invoice:transaction_no}/reject-payment', [BillingController::class, 'rejectPayment'])->name('billing.reject-payment');
    Route::post('/billing/{invoice:transaction_no}/send-overdue-notice', [BillingController::class, 'sendOverdueNotice'])->name('billing.send-overdue-notice');

    // ---------- Reservations ----------
    Route::get('/reservations',                        [ReservationController::class, 'index'])->name('reservations');
    Route::post('/reservations',                       [ReservationController::class, 'store'])->name('reservations.store');
    Route::get('/reservations/{reservation}',          [ReservationController::class, 'show'])->name('reservations.show');
    Route::put('/reservations/{reservation}',          [ReservationController::class, 'update'])->name('reservations.update');
    Route::delete('/reservations/{reservation}',       [ReservationController::class, 'destroy'])->name('reservations.destroy');
    Route::post('/reservations/{reservation}/approve', [ReservationController::class, 'approve'])->name('reservations.approve');
    Route::post('/reservations/{reservation}/reject',  [ReservationController::class, 'reject'])->name('reservations.reject');

    // ---------- Announcements ----------
    Route::get('/announcements',                            [AnnouncementController::class, 'index'])->name('announcements');
    Route::post('/announcements',                           [AnnouncementController::class, 'store'])->name('announcements.store');
    Route::get('/announcements/{announcement}',             [AnnouncementController::class, 'show'])->name('announcements.show');
    Route::put('/announcements/{announcement}',             [AnnouncementController::class, 'update'])->name('announcements.update');
    Route::delete('/announcements/{announcement}',          [AnnouncementController::class, 'destroy'])->name('announcements.destroy');
    Route::post('/announcements/{announcement}/toggle-pin', [AnnouncementController::class, 'togglePin'])->name('announcements.toggle-pin');

    // ---------- Messages ----------
    Route::get('/messages',                       [MessageController::class, 'index'])->name('messages');
    Route::post('/messages',                      [MessageController::class, 'store'])->name('messages.store');
    Route::get('/messages/{conversation}',        [MessageController::class, 'show'])->name('messages.show');
    Route::post('/messages/{conversation}/reply', [MessageController::class, 'reply'])->name('messages.reply');
    Route::delete('/messages/{message}',           [MessageController::class, 'deleteMessage'])->name('messages.delete');
    Route::post('/messages/{message}/react',       [MessageController::class, 'reactToMessage'])->name('messages.react');
    Route::delete('/messages/conversation/{conversation}', [MessageController::class, 'deleteConversation'])->name('messages.conversation.delete');

    // ---------- History / Reports ----------
    Route::get('/history', [HistoryController::class, 'index'])->name('history');
    Route::get('/reports', [ReportsController::class, 'index'])->name('reports');
    Route::get('/reports/export', [ReportsController::class, 'export'])->name('reports.export');

    // ---------- Audit Logs ----------
    Route::get('/audit-logs',        [AuditLogController::class, 'index'])->name('audit-logs');
    Route::get('/audit-logs/data',   [AuditLogController::class, 'data'])->name('audit-logs.data');
    Route::get('/audit-logs/export', [AuditLogController::class, 'export'])->name('audit-logs.export');

    // ---------- Admin Settings ----------
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::put('/settings/general', [SettingsController::class, 'updateGeneral'])->name('settings.general');
    Route::put('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile');
    Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password');

    // ---------- Data Management ----------
    Route::post('/settings/backup', [SettingsController::class, 'backupDatabase'])->name('settings.backup');
    Route::get('/settings/backup/{filename}', [SettingsController::class, 'downloadBackup'])->name('settings.download-backup');
    Route::get('/settings/export', [SettingsController::class, 'exportData'])->name('settings.export');
    Route::post('/settings/archive', [SettingsController::class, 'archiveRecords'])->name('settings.archive');

    // ============================================================
    // SERVICES
    // ============================================================
    Route::prefix('services')->name('services.')->group(function () {
        Route::get('/', function () {
            return redirect()->route('admin.services.maintenance');
        })->name('index');

        Route::get('/maintenance',                      [MaintenanceController::class, 'index'])->name('maintenance');
        Route::post('/maintenance',                     [MaintenanceController::class, 'store'])->name('maintenance.store');
        Route::get('/maintenance/{maintenanceRequest}', [MaintenanceController::class, 'show'])->name('maintenance.show');
        Route::put('/maintenance/{maintenanceRequest}', [MaintenanceController::class, 'update'])->name('maintenance.update');
        Route::delete('/maintenance/{maintenanceRequest}', [MaintenanceController::class, 'destroy'])->name('maintenance.destroy');

        Route::get('/room-change',                          [RoomChangeController::class, 'index'])->name('room-change');
        Route::post('/room-change',                         [RoomChangeController::class, 'store'])->name('room-change.store');
        Route::get('/room-change/{roomChangeRequest}',      [RoomChangeController::class, 'show'])->name('room-change.show');
        Route::put('/room-change/{roomChangeRequest}',      [RoomChangeController::class, 'update'])->name('room-change.update');
        Route::delete('/room-change/{roomChangeRequest}',   [RoomChangeController::class, 'destroy'])->name('room-change.destroy');
        Route::post('/room-change/{roomChangeRequest}/approve', [RoomChangeController::class, 'approve'])->name('room-change.approve');
        Route::post('/room-change/{roomChangeRequest}/reject',  [RoomChangeController::class, 'reject'])->name('room-change.reject');

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