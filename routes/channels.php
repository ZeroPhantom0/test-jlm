<?php
// routes/channels.php

use App\Models\Tenant;
use Illuminate\Support\Facades\Broadcast;

// ── Admin channels ──
Broadcast::channel('admin.reservations',  fn ($user) => $user && $user->isAdmin());
Broadcast::channel('admin.billing',       fn ($user) => $user && $user->isAdmin());
Broadcast::channel('admin.services',      fn ($user) => $user && $user->isAdmin());
Broadcast::channel('admin.announcements', fn ($user) => $user && $user->isAdmin());
Broadcast::channel('admin.messages',      fn ($user) => $user && $user->isAdmin());

// ── Tenant-wide channels ──
Broadcast::channel('tenant.reservations',  fn ($user) => $user && $user->isTenant());
Broadcast::channel('tenant.billing',       fn ($user) => $user && $user->isTenant());
Broadcast::channel('tenant.services',      fn ($user) => $user && $user->isTenant());
Broadcast::channel('tenant.announcements', fn ($user) => $user && $user->isTenant());

// ── Tenant-scoped messages (primary) ──
Broadcast::channel('tenant.{tenantId}.messages', function ($user, int $tenantId) {
    if (! $user || ! $user->isTenant()) {
        return false;
    }

    // Ensure the user actually owns this tenant row.
    $tenant = $user->tenant;
    if (! $tenant) {
        // Safety net — auto-create the shell row so auth never fails
        $tenant = Tenant::ensureForUser($user);
    }

    return (int) $tenant->id === $tenantId;
});

// ── Per-user fallback channel (NEW) ──
// Used by MessageSent so messages still reach brand-new tenants whose
// Tenant row was just created (or legacy users missing the row entirely).
Broadcast::channel('user.{userId}.messages', function ($user, int $userId) {
    return $user && (int) $user->id === $userId;
});