<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create Facebook Insights Table
 * Raw Facebook Graph API insight data per post.
 * Allows granular querying and historical trending.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facebook_insights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('social_account_id')->constrained('social_accounts')->onDelete('cascade');
            $table->string('facebook_post_id')->index()->comment('FB post_id from Graph API');
            $table->date('date')->index();

            // Post-level metrics
            $table->unsignedBigInteger('impressions')->default(0);
            $table->unsignedBigInteger('impressions_unique')->default(0)->comment('Reach');
            $table->unsignedBigInteger('impressions_paid')->default(0);
            $table->unsignedBigInteger('impressions_organic')->default(0);
            $table->unsignedBigInteger('engaged_users')->default(0);
            $table->unsignedBigInteger('post_clicks')->default(0);
            $table->unsignedBigInteger('post_clicks_unique')->default(0);
            $table->unsignedBigInteger('reactions_like_total')->default(0);
            $table->unsignedBigInteger('reactions_love_total')->default(0);
            $table->unsignedBigInteger('reactions_wow_total')->default(0);
            $table->unsignedBigInteger('reactions_haha_total')->default(0);
            $table->unsignedBigInteger('reactions_sorry_total')->default(0);
            $table->unsignedBigInteger('reactions_anger_total')->default(0);
            $table->unsignedBigInteger('comments_total')->default(0);
            $table->unsignedBigInteger('shares_total')->default(0);
            $table->unsignedBigInteger('video_views')->default(0)->comment('Total video views');
            $table->unsignedBigInteger('video_views_10s')->default(0);
            $table->unsignedBigInteger('video_avg_time_watched')->default(0)->comment('Seconds');

            // Page-level daily metrics
            $table->unsignedBigInteger('page_fans')->default(0)->comment('Total page followers');
            $table->unsignedBigInteger('page_fan_adds')->default(0)->comment('New followers today');
            $table->unsignedBigInteger('page_fan_removes')->default(0)->comment('Unfollows today');
            $table->unsignedBigInteger('page_views_total')->default(0);
            $table->unsignedBigInteger('page_impressions')->default(0);
            $table->unsignedBigInteger('page_reach')->default(0);
            $table->unsignedBigInteger('page_engaged_users')->default(0);

            $table->json('raw_data')->nullable()->comment('Full raw API response');
            $table->timestamps();

            $table->unique(['social_account_id', 'facebook_post_id', 'date'], 'fb_insights_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facebook_insights');
    }
};
