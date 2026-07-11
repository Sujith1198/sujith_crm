<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create Scheduled Posts Table
 * Tracking table for the scheduler job. Decoupled from posts
 * for better queue management and retry tracking.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('social_account_id')->constrained('social_accounts')->onDelete('cascade');
            $table->enum('platform', ['facebook', 'instagram']);
            $table->timestamp('scheduled_at')->index()->comment('Exact publish datetime (UTC)');
            $table->enum('status', [
                'pending',
                'processing',
                'published',
                'failed',
                'cancelled'
            ])->default('pending')->index();
            $table->string('platform_post_id')->nullable()->comment('ID returned by platform after publish');
            $table->text('error_message')->nullable();
            $table->integer('attempts')->default(0);
            $table->timestamp('last_attempted_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'scheduled_at']);
            $table->index(['post_id', 'platform']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_posts');
    }
};
