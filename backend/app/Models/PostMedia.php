<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * PostMedia Model
 * Stores individual media files for a post.
 * Ordered by sort_order for carousel rendering.
 */
class PostMedia extends Model
{
    protected $fillable = [
        'post_id',
        'type',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'disk',
        'width',
        'height',
        'duration',
        'thumbnail_path',
        'sort_order',
        'facebook_media_fbid',
    ];

    protected function casts(): array
    {
        return [
            'file_size'  => 'integer',
            'width'      => 'integer',
            'height'     => 'integer',
            'duration'   => 'integer',
            'sort_order' => 'integer',
        ];
    }

    // ──────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    // ──────────────────────────────────────────────
    // Accessors
    // ──────────────────────────────────────────────

    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->file_path);
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->thumbnail_path ? asset('storage/' . $this->thumbnail_path) : null;
    }

    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size;
        if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576)    return number_format($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024)       return number_format($bytes / 1024, 2) . ' KB';
        return $bytes . ' B';
    }

    public function isImage(): bool
    {
        return $this->type === 'image';
    }

    public function isVideo(): bool
    {
        return $this->type === 'video';
    }
}
