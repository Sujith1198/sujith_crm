<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create Instagram Insights Table
 * Stores Instagram Graph API media and profile insight data.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instagram_insights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('social_account_id')->constrained('social_accounts')->onDelete('cascade');
            $table->string('instagram_media_id')->index()->comment('IG media_id from Graph API');
            $table->date('date')->index();

            // Media-level metrics
            $table->unsignedBigInteger('impressions')->default(0);
            $table->unsignedBigInteger('reach')->default(0);
            $table->unsignedBigInteger('engagement')->default(0);
            $table->unsignedBigInteger('likes')->default(0);
            $table->unsignedBigInteger('comments')->default(0);
            $table->unsignedBigInteger('shares')->default(0);
            $table->unsignedBigInteger('saved')->default(0);
            $table->unsignedBigInteger('plays')->default(0)->comment('Reel plays');
            $table->unsignedBigInteger('video_views')->default(0);
            $table->decimal('engagement_rate', 8, 4)->default(0);

            // Story-specific metrics
            $table->unsignedBigInteger('exits')->default(0);
            $table->unsignedBigInteger('replies')->default(0);
            $table->unsignedBigInteger('taps_forward')->default(0);
            $table->unsignedBigInteger('taps_back')->default(0);

            // Profile-level daily metrics
            $table->unsignedBigInteger('profile_views')->default(0);
            $table->unsignedBigInteger('website_clicks')->default(0);
            $table->unsignedBigInteger('email_contacts')->default(0);
            $table->unsignedBigInteger('follower_count')->default(0);
            $table->unsignedBigInteger('follower_count_change')->default(0);

            $table->json('raw_data')->nullable()->comment('Full raw API response');
            $table->timestamps();

            $table->unique(['social_account_id', 'instagram_media_id', 'date'], 'ig_insights_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instagram_insights');
    }
};
