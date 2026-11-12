<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PaymentType;
use Illuminate\Http\JsonResponse;

class PaymentTypeController extends Controller
{
    public function index(): JsonResponse
    {
        $types = PaymentType::query()->orderBy('name')->get(['id', 'name']);

        return response()->json([
            'data' => $types,
        ]);
    }
}

