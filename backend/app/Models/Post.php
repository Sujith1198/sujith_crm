<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Post Model
 * Central entity for all social media content.
 *
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string|null $caption
 * @property string|null $description
 * @property string|null $hashtags
 * @property string|null $thumbnail
 * @property string $status draft|scheduled|publishing|published|failed|cancelled
 * @property \Carbon\Carbon|null $publish_at
 * @property string $timezone
 * @property bool $post_to_facebook
 * @property bool $post_to_instagram
 * @property string $post_type text|image|video|carousel|reel
 */
class Post extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'caption',
        'description',
        'hashtags',
        'thumbnail',
        'status',
        'publish_at',
        'timezone',
        'post_to_facebook',
        'post_to_instagram',
        'facebook_post_id',
        'instagram_media_id',
        'instagram_container_id',
        'post_type',
        'error_message',
        'retry_count',
        'last_retry_at',
    ];

    protected function casts(): array
    {
        return [
            'publish_at'       => 'datetime',
            'last_retry_at'    => 'datetime',
            'post_to_facebook' => 'boolean',
            'post_to_instagram'=> 'boolean',
            'retry_count'      => 'integer',
        ];
    }

    // ──────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function media()
    {
        return $this->hasMany(PostMedia::class)->orderBy('sort_order');
    }

    public function scheduledPosts()
    {
        return $this->hasMany(ScheduledPost::class);
    }

    public function analytics()
    {
        return $this->hasMany(Analytics::class);
    }

    public function facebookInsights()
    {
        return $this->hasMany(FacebookInsight::class);
    }

    public function instagramInsights()
    {
        return $this->hasMany(InstagramInsight::class);
    }

    // ──────────────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────────────

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeDueForPublishing($query)
    {
        return $query->where('status', 'scheduled')
                     ->where('publish_at', '<=', now());
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
              ->orWhere('caption', 'like', "%{$term}%")
              ->orWhere('hashtags', 'like', "%{$term}%");
        });
    }

    // ──────────────────────────────────────────────
    // Accessors / Helpers
    // ──────────────────────────────────────────────

    public function isPending(): bool
    {
        return in_array($this->status, ['draft', 'scheduled']);
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function getHashtagsArrayAttribute(): array
    {
        if (! $this->hashtags) return [];
        return array_filter(array_map('trim', explode(' ', $this->hashtags)));
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        if (! $this->thumbnail) return null;
        return asset('storage/' . $this->thumbnail);
    }

    public function getPlatformsAttribute(): array
    {
        $platforms = [];
        if ($this->post_to_facebook) $platforms[] = 'facebook';
        if ($this->post_to_instagram) $platforms[] = 'instagram';
        return $platforms;
    }
}
