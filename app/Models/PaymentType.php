<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentType extends Model
{
    use HasFactory;

    protected $table = 'payment_types';
    public const PAYMENT_TYPE_CASH_ID = 1;
    public const PAYMENT_TYPE_TRANSFER_ID = 2;
    public const PAYMENT_TYPE_OTHER_ID = 3;

    protected $fillable = [
        'name',
    ];

    public function payments(): HasMany
    {
        return $this->hasMany(AppointmentPayment::class, 'payment_type_id');
    }
}

