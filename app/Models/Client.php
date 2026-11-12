<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'paternal_surname',
        'maternal_surname',
        'whatsapp',
        'email',
        'user_id',
        'consent_notifications',
        'otp_verified',
        'access_token',
        'access_token_expires',
    ];

    protected $casts = [
        'consent_notifications' => 'boolean',
        'otp_verified' => 'boolean',
        'access_token_expires' => 'datetime',
    ];

    protected $hidden = [
        'access_token',
    ];

    /**
     * Obtener el nombre completo del cliente
     */
    public function getFullNameAttribute(): string
    {
        $parts = [$this->first_name, $this->paternal_surname];
        if ($this->maternal_surname) {
            $parts[] = $this->maternal_surname;
        }
        return implode(' ', $parts);
    }

    /**
     * Relación con citas
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Relación con usuario (1:1)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

}
