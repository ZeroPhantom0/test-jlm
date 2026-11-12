<?php
// database/migrations/2026_11_13_000000_add_preferences_and_session_timeout_to_users_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Notification preferences — enabled by default for everyone
            $table->boolean('email_notifications')->default(true)->after('role');
            $table->boolean('sms_notifications')->default(true)->after('email_notifications');
            // Admin idle-session timeout in minutes (30 / 60 / 120)
            $table->unsignedSmallInteger('session_timeout')->default(120)->after('sms_notifications');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['email_notifications', 'sms_notifications', 'session_timeout']);
        });
    }
};
