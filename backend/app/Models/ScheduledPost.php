<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * ScheduledPost Model
 * Tracks individual publish attempts per platform per post.
 */
class ScheduledPost extends Model
{
    protected $fillable = [
        'post_id',
        'user_id',
        'social_account_id',
        'platform',
        'scheduled_at',
        'status',
        'platform_post_id',
        'error_message',
        'attempts',
        'last_attempted_at',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at'      => 'datetime',
            'last_attempted_at' => 'datetime',
            'published_at'      => 'datetime',
            'attempts'          => 'integer',
        ];
    }

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function socialAccount()
    {
        return $this->belongsTo(SocialAccount::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeDue($query)
    {
        return $query->where('status', 'pending')
                     ->where('scheduled_at', '<=', now());
    }
}
