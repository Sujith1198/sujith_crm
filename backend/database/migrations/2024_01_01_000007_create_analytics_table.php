<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create Analytics Table
 * Aggregated analytics data per post per day.
 * Used for dashboard charts and reports.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('social_account_id')->constrained('social_accounts')->onDelete('cascade');
            $table->enum('platform', ['facebook', 'instagram'])->index();
            $table->date('date')->index();

            // Engagement metrics
            $table->unsignedBigInteger('views')->default(0);
            $table->unsignedBigInteger('reach')->default(0);
            $table->unsignedBigInteger('impressions')->default(0);
            $table->unsignedBigInteger('likes')->default(0);
            $table->unsignedBigInteger('comments')->default(0);
            $table->unsignedBigInteger('shares')->default(0);
            $table->unsignedBigInteger('clicks')->default(0);
            $table->unsignedBigInteger('saves')->default(0);
            $table->decimal('engagement_rate', 8, 4)->default(0);
            $table->decimal('ctr', 8, 4)->default(0)->comment('Click-through rate');

            // Audience
            $table->unsignedBigInteger('followers_count')->default(0);
            $table->unsignedBigInteger('profile_visits')->default(0);
            $table->unsignedBigInteger('website_clicks')->default(0);

            // Video specific
            $table->unsignedBigInteger('video_views')->default(0);

            $table->timestamps();

            $table->unique(['social_account_id', 'post_id', 'platform', 'date'], 'analytics_unique');
            $table->index(['social_account_id', 'platform', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics');
    }
};
