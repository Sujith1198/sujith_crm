<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * ActivityLog Model
 * Immutable audit trail for all user actions.
 */
class ActivityLog extends Model
{
    protected $fillable = [
        'user_id', 'action', 'description',
        'subject_type', 'subject_id',
        'old_values', 'new_values',
        'ip_address', 'user_agent', 'url', 'method',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    // No update/delete — logs are immutable
    public static function boot(): void
    {
        parent::boot();
        static::updating(fn () => false);
        static::deleting(fn () => false);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subject()
    {
        return $this->morphTo();
    }

    public function scopeAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
