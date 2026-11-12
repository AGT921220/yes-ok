<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Exceptions\ConflictError;
use App\Exceptions\NotFoundError;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    private function syncUserServices(User $user, ?array $serviceIds): void
    {
        if ($serviceIds === null) {
            return;
        }
        $normalized = array_values(array_unique(array_map('intval', $serviceIds)));
        $user->services()->sync($normalized);
    }

    public function index(): AnonymousResourceCollection
    {
        // Solo ADMIN puede listar usuarios
        if (auth()->user()->role !== UserRole::ADMIN) {
            return UserResource::collection(collect([]));
        }

        $users = User::with('services')->orderBy('name')->get();
        return UserResource::collection($users);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        // REGLA DE NEGOCIO: Este endpoint solo crea ASISTENTES
        // Los clientes se crean desde ClientController
        if ($request->role === UserRole::CLIENTE) {
            throw new ConflictError(
                'Los clientes no se pueden crear desde el módulo de Usuarios. ' .
                'Use el módulo de Clientes para crear clientes.'
            );
        }

        // Verificar email único
        $existing = User::where('email', $request->email)->first();
        if ($existing) {
            throw new ConflictError('El email ya está registrado');
        }

        $userData = [
            'name' => $request->name,
            'role' => $request->role,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_active' => $request->get('is_active', true),
        ];

        // Agregar phone si está presente (opcional para asistentes)
        if ($request->has('phone') && $request->phone) {
            $userData['phone'] = $request->phone;
        }

        $serviceIds = $request->input('service_ids');
        $user = User::create($userData);
        $this->syncUserServices($user, $serviceIds);

        return (new UserResource($user->load('services')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(User $user): UserResource
    {
        return new UserResource($user->load('services'));
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse|UserResource
    {
        $updateData = $request->validated();
        $serviceIds = $updateData['service_ids'] ?? null;
        unset($updateData['service_ids']);

        // Si viene password, hashearlo
        if (isset($updateData['password'])) {
            $updateData['password'] = Hash::make($updateData['password']);
        }

        // Remover campos null para no sobrescribir con null
        $updateData = array_filter($updateData, fn($value) => $value !== null);

        $user->update($updateData);
        $this->syncUserServices($user, $serviceIds);

        return new UserResource($user->load('services'));
    }

    public function destroy(User $user): JsonResponse
    {
        // No se puede eliminar a uno mismo
        if ($user->id === auth()->id()) {
            throw new ConflictError('No puedes eliminarte a ti mismo');
        }

        $user->delete();

        return response()->json([
            'message' => 'Usuario eliminado',
        ]);
    }
}
