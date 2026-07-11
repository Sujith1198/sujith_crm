<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create Social Accounts Table
 * Stores connected Facebook Pages and Instagram Business Accounts.
 * Includes encrypted token storage, expiry tracking, and auto-refresh flags.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('platform', ['facebook', 'instagram'])->index();
            $table->string('account_name');
            $table->string('page_id')->nullable()->comment('Facebook Page ID or Instagram Account ID');
            $table->string('page_name')->nullable();
            $table->string('account_id')->nullable()->comment('Instagram Business Account ID');
            $table->text('access_token')->comment('Encrypted access token');
            $table->text('refresh_token')->nullable()->comment('Encrypted long-lived token');
            $table->timestamp('token_expires_at')->nullable();
            $table->string('profile_picture_url')->nullable();
            $table->bigInteger('followers_count')->default(0);
            $table->boolean('auto_refresh_token')->default(true);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable()->comment('Extra platform-specific data');
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'platform', 'is_active']);
            $table->unique(['user_id', 'platform', 'page_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
    }
};
