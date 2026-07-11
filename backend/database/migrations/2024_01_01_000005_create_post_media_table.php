<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create Post Media Table
 * Stores all media files (images, videos) associated with posts.
 * Supports ordering for carousel posts.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['image', 'video'])->default('image');
            $table->string('file_path')->comment('Relative path in storage');
            $table->string('file_name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size')->comment('Size in bytes');
            $table->string('disk', 20)->default('public')->comment('Storage disk');
            $table->integer('width')->nullable()->comment('Image/video width in pixels');
            $table->integer('height')->nullable()->comment('Image/video height in pixels');
            $table->integer('duration')->nullable()->comment('Video duration in seconds');
            $table->string('thumbnail_path')->nullable()->comment('Video thumbnail');
            $table->integer('sort_order')->default(0)->comment('Order for carousel posts');
            $table->string('facebook_media_fbid')->nullable()->comment('FB media ID after upload');
            $table->timestamps();

            $table->index(['post_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_media');
    }
};
