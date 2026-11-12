<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;

class SeedController extends Controller
{
    public function seed(): JsonResponse
    {
        // Solo en desarrollo
        if (app()->environment('production')) {
            return response()->json(['message' => 'Not allowed in production'], 403);
        }

        try {
            Artisan::call('db:seed');
            return response()->json(['message' => 'Database seeded successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error seeding database: ' . $e->getMessage()], 500);
        }
    }
}
