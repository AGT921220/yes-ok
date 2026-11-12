<?php

namespace Database\Seeders;

use App\Models\PaymentType;
use Illuminate\Database\Seeder;

class PaymentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            'Efectivo',
            'Tarjeta',
            'Transferencia',
        ];

        foreach ($types as $name) {
            PaymentType::firstOrCreate(['name' => $name]);
        }
    }
}

