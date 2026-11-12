<?php
// database/migrations/2026_11_13_200000_add_inactivity_notified_at_to_tenants_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // When the 6-month inactivity email was sent (once per inactive spell;
            // cleared when the tenant becomes active again)
            $table->timestamp('inactivity_notified_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('inactivity_notified_at');
        });
    }
};
