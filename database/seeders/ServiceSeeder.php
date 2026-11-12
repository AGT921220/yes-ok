<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            ['name' => 'Corte', 'duration_minutes' => 30, 'sort_order' => 1],
            ['name' => 'Barba', 'duration_minutes' => 15, 'sort_order' => 2],
            ['name' => 'Corte + Barba', 'duration_minutes' => 45, 'sort_order' => 3],
            ['name' => 'Uñas', 'duration_minutes' => 45, 'sort_order' => 4],
            ['name' => 'Peinado', 'duration_minutes' => 60, 'sort_order' => 5],
            ['name' => 'Tinte', 'duration_minutes' => 60, 'sort_order' => 6],
        ];

        foreach ($services as $s) {
            Service::firstOrCreate(
                ['name' => $s['name']],
                [
                    'duration_minutes' => $s['duration_minutes'],
                    'is_active' => true,
                    'sort_order' => $s['sort_order'],
                ]
            );
        }
    }
}

