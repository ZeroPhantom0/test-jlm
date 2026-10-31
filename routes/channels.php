<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('admin.reservations',  fn ($user) => $user && $user->isAdmin());
Broadcast::channel('admin.billing',       fn ($user) => $user && $user->isAdmin());
Broadcast::channel('admin.services',      fn ($user) => $user && $user->isAdmin());
Broadcast::channel('admin.announcements', fn ($user) => $user && $user->isAdmin());
Broadcast::channel('admin.messages',      fn ($user) => $user && $user->isAdmin());

Broadcast::channel('tenant.reservations',  fn ($user) => $user && $user->isTenant());
Broadcast::channel('tenant.billing',       fn ($user) => $user && $user->isTenant());
Broadcast::channel('tenant.services',      fn ($user) => $user && $user->isTenant());
Broadcast::channel('tenant.announcements', fn ($user) => $user && $user->isTenant());
Broadcast::channel('tenant.{tenantId}.messages', function ($user, $tenantId) {
    if (!$user || !$user->isTenant()) {
        return false;
    }
    
    // Check if the authenticated user belongs to this tenant ID
    $tenant = \App\Models\Tenant::where('user_id', $user->id)->first();
    return $tenant && $tenant->id == $tenantId;
});