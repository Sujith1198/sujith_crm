<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** FacebookInsight Model — raw Facebook Graph API post-level insights */
class FacebookInsight extends Model
{
    protected $fillable = [
        'post_id', 'social_account_id', 'facebook_post_id', 'date',
        'impressions', 'impressions_unique', 'impressions_paid', 'impressions_organic',
        'engaged_users', 'post_clicks', 'post_clicks_unique',
        'reactions_like_total', 'reactions_love_total', 'reactions_wow_total',
        'reactions_haha_total', 'reactions_sorry_total', 'reactions_anger_total',
        'comments_total', 'shares_total',
        'video_views', 'video_views_10s', 'video_avg_time_watched',
        'page_fans', 'page_fan_adds', 'page_fan_removes',
        'page_views_total', 'page_impressions', 'page_reach', 'page_engaged_users',
        'raw_data',
    ];

    protected function casts(): array
    {
        return ['date' => 'date', 'raw_data' => 'array'];
    }

    public function post() { return $this->belongsTo(Post::class); }
    public function socialAccount() { return $this->belongsTo(SocialAccount::class); }

    public function getTotalReactionsAttribute(): int
    {
        return $this->reactions_like_total + $this->reactions_love_total +
               $this->reactions_wow_total + $this->reactions_haha_total +
               $this->reactions_sorry_total + $this->reactions_anger_total;
    }
}
