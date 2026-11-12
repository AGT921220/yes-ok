<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
    use HasFactory, SoftDeletes;

    public const CONFIRMATION_WHATSAPP_TEXT_ACTION = 'confirm_appointment';
    public const CANCELLATION_WHATSAPP_TEXT_ACTION = 'cancel_appointment';

    public const ASSIST_ATTENDANCE_STATUS = 'asistio';

    public const COMPLETED_STATUS = 'COMPLETADA';

    protected $fillable = [
        'client_id',
        'staff_user_id',
        'date',
        'start_time',
        'end_time',
        'status',
        'notes',
        'reason',
        'created_by',
        'booked_by_client',
        'forced',
        'attendance_status',
        'payment_status',
        'type',
        'google_calendar_id',
        'cancellation_reason',
    ];

    protected $attributes = [
        'type' => 'presencial',
    ];

    protected $casts = [
        'date' => 'date',
        'status' => AppointmentStatus::class,
        'booked_by_client' => 'boolean',
        'forced' => 'boolean',
    ];

    /**
     * Relación con cliente
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_user_id');
    }

    /**
     * Relación con usuario creador
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relación con pagos
     */
    public function payments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AppointmentPayment::class);
    }

    /**
     * Pago activo (no eliminado) asociado a la cita
     */
    public function activePayment(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(AppointmentPayment::class)->latestOfMany('paid_at');
    }

    /**
     * Servicios asociados a la cita (estética/barbería/salón).
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'appointment_service')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }
}
