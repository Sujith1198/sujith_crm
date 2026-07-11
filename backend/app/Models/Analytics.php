<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Analytics Model — aggregated daily metrics per post per platform */
class Analytics extends Model
{
    protected $fillable = [
        'post_id', 'social_account_id', 'platform', 'date',
        'views', 'reach', 'impressions', 'likes', 'comments', 'shares',
        'clicks', 'saves', 'engagement_rate', 'ctr',
        'followers_count', 'profile_visits', 'website_clicks', 'video_views',
    ];

    protected function casts(): array
    {
        return [
            'date'            => 'date',
            'engagement_rate' => 'float',
            'ctr'             => 'float',
        ];
    }

    public function post() { return $this->belongsTo(Post::class); }
    public function socialAccount() { return $this->belongsTo(SocialAccount::class); }

    public function scopePlatform($query, string $platform) {
        return $query->where('platform', $platform);
    }
    public function scopeDateRange($query, string $from, string $to) {
        return $query->whereBetween('date', [$from, $to]);
    }
}
