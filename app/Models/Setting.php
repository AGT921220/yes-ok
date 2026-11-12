<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $table = 'settings';

    public $timestamps = false;

    protected $fillable = [
        'key',
        'value',
        'updated_by',
    ];

    protected $casts = [
        'updated_at' => 'datetime',
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        $cacheKey = "settings.{$key}";

        return Cache::rememberForever($cacheKey, function () use ($key, $default) {
            $record = static::query()->where('key', $key)->first();

            if (!$record) {
                return $default;
            }

            return $record->value;
        });
    }

    public static function set(string $key, mixed $value): static
    {
        Cache::forget("settings.{$key}");

        $setting = static::firstOrNew(['key' => $key]);
        $setting->value = (string) $value;
        $setting->updated_by = auth()->id();
        $setting->save();

        Cache::forever("settings.{$key}", $setting->value);

        return $setting;
    }

    /**
     * Relación con usuario que actualizó
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
