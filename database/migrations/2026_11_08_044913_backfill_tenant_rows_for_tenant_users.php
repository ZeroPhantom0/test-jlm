<?php
// database/migrations/2026_11_08_044913_backfill_tenant_rows_for_tenant_users.php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        User::where('role', 'tenant')
            ->whereDoesntHave('tenant')
            ->select('id')
            ->chunkById(200, function ($users) {
                $rows = $users->map(fn ($u) => [
                    'user_id'                 => $u->id,
                    'room_id'                 => null,
                    'approved_reservation_id' => null,
                    'rent_rate'               => 0,
                    'status'                  => 'active',   // ✅ valid enum value
                    'created_at'              => now(),
                    'updated_at'              => now(),
                ])->all();

                Tenant::insert($rows);
            });
    }

    public function down(): void
    {
        // No-op — never delete tenant rows on rollback.
    }
};