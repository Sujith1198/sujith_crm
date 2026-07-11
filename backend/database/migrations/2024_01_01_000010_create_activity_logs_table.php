<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create Activity Logs Table
 * Records all significant user actions for audit trail.
 * Includes before/after JSON snapshots for data changes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('action')->index()->comment('e.g. login, post.created, user.updated');
            $table->string('description');
            $table->string('subject_type')->nullable()->comment('Model class name');
            $table->unsignedBigInteger('subject_id')->nullable()->comment('Model ID');
            $table->json('old_values')->nullable()->comment('Previous state (for updates)');
            $table->json('new_values')->nullable()->comment('New state (for creates/updates)');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('url')->nullable();
            $table->string('method', 10)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'action', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
