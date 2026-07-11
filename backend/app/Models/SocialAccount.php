<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

/**
 * SocialAccount Model
 * Represents a connected Facebook Page or Instagram Business Account.
 * Tokens are encrypted at rest using Laravel's Crypt facade.
 *
 * @property int $id
 * @property int $user_id
 * @property string $platform facebook|instagram
 * @property string $account_name
 * @property string|null $page_id
 * @property string|null $page_name
 * @property string|null $account_id
 * @property string $access_token (encrypted)
 * @property string|null $refresh_token (encrypted)
 * @property \Carbon\Carbon|null $token_expires_at
 * @property int $followers_count
 * @property bool $auto_refresh_token
 * @property bool $is_active
 */
class SocialAccount extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'platform',
        'account_name',
        'page_id',
        'page_name',
        'account_id',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'profile_picture_url',
        'followers_count',
        'auto_refresh_token',
        'is_active',
        'metadata',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'token_expires_at'   => 'datetime',
            'last_synced_at'     => 'datetime',
            'auto_refresh_token' => 'boolean',
            'is_active'          => 'boolean',
            'metadata'           => 'array',
            'followers_count'    => 'integer',
        ];
    }

    // ──────────────────────────────────────────────
    // Token Encryption / Decryption
    // ──────────────────────────────────────────────

    public function setAccessTokenAttribute(string $value): void
    {
        $this->attributes['access_token'] = Crypt::encryptString($value);
    }

    public function getAccessTokenAttribute(string $value): string
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Exception) {
            return $value; // Already decrypted or plain (migration path)
        }
    }

    public function setRefreshTokenAttribute(?string $value): void
    {
        $this->attributes['refresh_token'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getRefreshTokenAttribute(?string $value): ?string
    {
        if (! $value) return null;
        try {
            return Crypt::decryptString($value);
        } catch (\Exception) {
            return $value;
        }
    }

    // ──────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function posts()
    {
        return $this->hasMany(Post::class, 'user_id', 'user_id');
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

    public function scheduledPosts()
    {
        return $this->hasMany(ScheduledPost::class);
    }

    // ──────────────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    public function scopeExpiringTokens($query, int $daysAhead = 7)
    {
        return $query->where('token_expires_at', '<=', now()->addDays($daysAhead))
                     ->where('auto_refresh_token', true);
    }

    // ──────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────

    public function isTokenExpired(): bool
    {
        return $this->token_expires_at && $this->token_expires_at->isPast();
    }

    public function isTokenExpiringSoon(int $days = 7): bool
    {
        return $this->token_expires_at && $this->token_expires_at->isBefore(now()->addDays($days));
    }

    public function isFacebook(): bool
    {
        return $this->platform === 'facebook';
    }

    public function isInstagram(): bool
    {
        return $this->platform === 'instagram';
    }
}
