<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * Setting Model
 * Typed key-value settings supporting global and per-user scopes.
 */
class Setting extends Model
{
    protected $fillable = [
        'user_id', 'key', 'value', 'type', 'group', 'label', 'description',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ──────────────────────────────────────────────
    // Typed Value Accessor
    // ──────────────────────────────────────────────

    public function getTypedValueAttribute(): mixed
    {
        return match ($this->type) {
            'integer'   => (int) $this->value,
            'boolean'   => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'json'      => json_decode($this->value, true),
            'encrypted' => $this->decryptValue(),
            default     => $this->value,
        };
    }

    private function decryptValue(): ?string
    {
        try {
            return Crypt::decryptString($this->value);
        } catch (\Exception) {
            return $this->value;
        }
    }

    // ──────────────────────────────────────────────
    // Static Helpers
    // ──────────────────────────────────────────────

    public static function get(string $key, mixed $default = null, ?int $userId = null): mixed
    {
        $setting = static::where('key', $key)
            ->where('user_id', $userId)
            ->first();

        return $setting ? $setting->typed_value : $default;
    }

    public static function set(string $key, mixed $value, string $type = 'string', ?int $userId = null): self
    {
        if ($type === 'json') $value = json_encode($value);
        if ($type === 'encrypted') $value = Crypt::encryptString((string) $value);

        return static::updateOrCreate(
            ['key' => $key, 'user_id' => $userId],
            ['value' => $value, 'type' => $type]
        );
    }

    public function scopeGlobal($query)
    {
        return $query->whereNull('user_id');
    }

    public function scopeGroup($query, string $group)
    {
        return $query->where('group', $group);
    }
}
