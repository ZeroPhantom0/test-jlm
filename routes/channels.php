<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('admin.reservations',  fn ($user) => $user && $user->isAdmin());
Broadcast::channel('admin.billing',       fn ($user) => $user && $user->isAdmin());
Broadcast::channel('admin.services',      fn ($user) => $user && $user->isAdmin());
Broadcast::channel('admin.announcements', fn ($user) => $user && $user->isAdmin());   // NEW

Broadcast::channel('tenant.reservations',  fn ($user) => $user && $user->isTenant());
Broadcast::channel('tenant.billing',       fn ($user) => $user && $user->isTenant());
Broadcast::channel('tenant.services',      fn ($user) => $user && $user->isTenant());
Broadcast::channel('tenant.announcements', fn ($user) => $user && $user->isTenant()); // NEW