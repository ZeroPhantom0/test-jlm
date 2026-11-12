<?php
// database/migrations/2026_11_13_300000_add_removed_at_to_tenants_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Set when an admin removes the tenant from the Tenants list. The row
            // is kept on purpose: room changes, stay extensions and messages
            // reference it with ON DELETE CASCADE, so a hard delete would wipe
            // that history. Cleared automatically if they become Active again.
            $table->timestamp('removed_at')->nullable()->after('inactivity_notified_at');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('removed_at');
        });
    }
};
