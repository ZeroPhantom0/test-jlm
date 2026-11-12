<?php
// database/migrations/2026_11_12_000000_add_balance_reminder_fields_to_reservations_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            // Set when an approved reservation still owes a balance; only these
            // reservations are reminded / cancelled under the payment policy.
            $table->timestamp('balance_tracked_at')->nullable()->after('expires_at');
            // 3-hour (or immediate) remaining-balance SMS reminder
            $table->timestamp('balance_reminder_sent_at')->nullable()->after('balance_tracked_at');
            // Payment-policy cancellation + the follow-up SMS sent 5 minutes later
            $table->timestamp('cancelled_at')->nullable()->after('balance_reminder_sent_at');
            $table->timestamp('cancellation_sms_sent_at')->nullable()->after('cancelled_at');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn([
                'balance_tracked_at',
                'balance_reminder_sent_at',
                'cancelled_at',
                'cancellation_sms_sent_at',
            ]);
        });
    }
};
