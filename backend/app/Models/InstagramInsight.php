<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** InstagramInsight Model — raw Instagram Graph API media insights */
class InstagramInsight extends Model
{
    protected $fillable = [
        'post_id', 'social_account_id', 'instagram_media_id', 'date',
        'impressions', 'reach', 'engagement', 'likes', 'comments',
        'shares', 'saved', 'plays', 'video_views', 'engagement_rate',
        'exits', 'replies', 'taps_forward', 'taps_back',
        'profile_views', 'website_clicks', 'email_contacts',
        'follower_count', 'follower_count_change',
        'raw_data',
    ];

    protected function casts(): array
    {
        return [
            'date'            => 'date',
            'engagement_rate' => 'float',
            'raw_data'        => 'array',
        ];
    }

    public function post() { return $this->belongsTo(Post::class); }
    public function socialAccount() { return $this->belongsTo(SocialAccount::class); }
}
