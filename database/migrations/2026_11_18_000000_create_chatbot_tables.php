<?php
// database/migrations/2026_11_18_000000_create_chatbot_tables.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // FAQ categories shown as quick-reply chips in the landing chatbot
        Schema::create('chatbot_categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 60)->unique();
            $table->string('name', 100);
            $table->string('icon', 60)->nullable();          // Font Awesome class, e.g. "fa-bed"
            $table->text('welcome_message');
            $table->string('button_label', 60)->nullable();  // Suggested action button
            $table->string('button_url', 255)->nullable();
            $table->text('keywords')->nullable();            // Comma-separated, matches the whole category
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Individual question / answer pairs with matching keywords
        Schema::create('chatbot_faqs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chatbot_category_id')->constrained()->cascadeOnDelete();
            $table->string('question', 255);
            $table->text('answer');                          // May contain {{live_tokens}}
            $table->text('keywords');                        // Comma-separated, phrases allowed
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('hits')->default(0);     // How often it was answered
            $table->timestamps();
        });

        // Fixed bot lines (greeting, fallback, back option, closing)
        Schema::create('chatbot_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 60)->unique();
            $table->text('value');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_faqs');
        Schema::dropIfExists('chatbot_categories');
        Schema::dropIfExists('chatbot_settings');
    }
};
