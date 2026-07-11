<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create Posts Table
 * Central post entity supporting Facebook & Instagram,
 * multiple statuses, scheduling, timezone awareness.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('caption')->nullable();
            $table->text('description')->nullable();
            $table->text('hashtags')->nullable();
            $table->string('thumbnail')->nullable();

            // Status tracking
            $table->enum('status', [
                'draft',
                'scheduled',
                'publishing',
                'published',
                'failed',
                'cancelled'
            ])->default('draft')->index();

            // Scheduling
            $table->timestamp('publish_at')->nullable()->comment('When to publish (UTC stored)');
            $table->string('timezone', 50)->default('UTC');

            // Platform flags
            $table->boolean('post_to_facebook')->default(false);
            $table->boolean('post_to_instagram')->default(false);

            // Platform-specific post IDs after publishing
            $table->string('facebook_post_id')->nullable();
            $table->string('instagram_media_id')->nullable();
            $table->string('instagram_container_id')->nullable();

            // Post type
            $table->enum('post_type', ['text', 'image', 'video', 'carousel', 'reel'])->default('text');

            // Error tracking
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('last_retry_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'publish_at']);
            $table->index('publish_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
