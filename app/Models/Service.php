<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Service extends Model
{
    use HasFactory;

    public const ALLOWED_DURATIONS = [15, 30, 45, 60];

    protected $fillable = [
        'name',
        'duration_minutes',
        'price',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price' => 'decimal:2',
    ];

    /**
     * Citas que incluyen este servicio.
     */
    public function appointments(): BelongsToMany
    {
        return $this->belongsToMany(Appointment::class, 'appointment_service')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    /**
     * Personal (usuarios) que puede atender este servicio.
     */
    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'service_user')
            ->withTimestamps()
            ->orderBy('name');
    }

    /**
     * Suma total de minutos para un set de IDs.
     */
    public static function totalDurationMinutesForIds(array $serviceIds): int
    {
        if (empty($serviceIds)) {
            return 0;
        }

        return (int) self::query()
            ->whereIn('id', $serviceIds)
            ->where('is_active', true)
            ->sum('duration_minutes');
    }
}

