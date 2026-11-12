<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AvailabilityBlock\StoreAvailabilityBlockRequest;
use App\Http\Resources\AvailabilityBlockResource;
use App\Models\AvailabilityBlock;
use App\Services\AvailabilityBlockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AvailabilityBlockController extends Controller
{
    public function __construct(
        private AvailabilityBlockService $availabilityBlockService
    ) {
    }

    /**
     * Listar bloqueos con filtros de fechas
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = AvailabilityBlock::query();

        if ($request->has('start_date')) {
            $query->where('end_date', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->where('start_date', '<=', $request->end_date);
        }

        $blocks = $query->orderBy('start_date')->get();

        return AvailabilityBlockResource::collection($blocks);
    }

    /**
     * Crear bloqueo de disponibilidad
     */
    public function store(StoreAvailabilityBlockRequest $request): JsonResponse
    {
        $block = $this->availabilityBlockService->create(
            $request->validated(),
            auth()->user()
        );

        return (new AvailabilityBlockResource($block))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Eliminar bloqueo
     */
    public function destroy(AvailabilityBlock $availabilityBlock): JsonResponse
    {
        $availabilityBlock->delete();

        return response()->json([
            'message' => 'Bloqueo eliminado'
        ]);
    }
}
