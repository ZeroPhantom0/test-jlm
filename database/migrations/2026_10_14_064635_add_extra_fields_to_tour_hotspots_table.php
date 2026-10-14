<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tour_hotspots', function (Blueprint $table) {
            if (!Schema::hasColumn('tour_hotspots', 'description')) {
                $table->text('description')->nullable()->after('nav_audio');
            }
            if (!Schema::hasColumn('tour_hotspots', 'icon')) {
                $table->string('icon', 100)->nullable()->after('description');
            }
            if (!Schema::hasColumn('tour_hotspots', 'voice_assistant')) {
                $table->boolean('voice_assistant')->default(false)->after('icon');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tour_hotspots', function (Blueprint $table) {
            $table->dropColumn(['description', 'icon', 'voice_assistant']);
        });
    }
};