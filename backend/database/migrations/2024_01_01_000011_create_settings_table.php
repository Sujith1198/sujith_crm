<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create Settings Table
 * Key-value store for system-wide and per-user settings.
 * Supports typed values via the 'type' column.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade')
                  ->comment('NULL = global setting, set = user setting');
            $table->string('key')->index();
            $table->text('value')->nullable();
            $table->enum('type', ['string', 'integer', 'boolean', 'json', 'encrypted'])
                  ->default('string');
            $table->string('group', 50)->nullable()->index()
                  ->comment('Grouping key e.g. general, social, notifications');
            $table->string('label')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
