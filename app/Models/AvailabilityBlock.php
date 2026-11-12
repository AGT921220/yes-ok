<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AvailabilityBlock extends Model
{
    use HasFactory;

    protected $fillable = [
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'is_full_day',
        'reason',
        'is_recurring',
        'recurrence_pattern',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_full_day' => 'boolean',
        'is_recurring' => 'boolean',
    ];

    public $timestamps = false;

    /**
     * Relación con usuario creador
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
