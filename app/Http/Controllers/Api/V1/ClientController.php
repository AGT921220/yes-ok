<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\SetClientPasswordRequest;
use App\Http\Requests\Client\StoreClientRequest;
use App\Http\Requests\Client\UpdateClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ClientController extends Controller
{
    /**
     * Listar clientes con búsqueda opcional
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Client::query();
        
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('paternal_surname', 'like', "%{$search}%")
                  ->orWhere('maternal_surname', 'like', "%{$search}%")
                  ->orWhere('whatsapp', 'like', "%{$search}%");
            });
        }
        
        $clients = $query->orderBy('paternal_surname')
                         ->orderBy('first_name')
                         ->get();
        
        return ClientResource::collection($clients);
    }

    /**
     * Crear nuevo cliente (sin usuario; el usuario se puede agregar después con "Agregar contraseña").
     * Obligatorio: correo o teléfono (solo uno), único en clients y users.
     */
    public function store(StoreClientRequest $request): JsonResponse
    {
        $action = new \App\Actions\CreateClientOnlyAction();

        try {
            $client = $action->execute($request->validated());

            return response()->json([
                'data' => new ClientResource($client),
                'message' => 'Cliente creado exitosamente.',
            ], 201);
        } catch (\App\Exceptions\ConflictError $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
                'error' => [
                    'code' => 'CONFLICT_ERROR',
                    'message' => $e->getMessage(),
                ],
            ], 409);
        }
    }

    /**
     * Obtener cliente por ID
     */
    public function show(Client $client): ClientResource
    {
        return new ClientResource($client);
    }

    /**
     * Actualizar cliente
     */
    public function update(UpdateClientRequest $request, Client $client): JsonResponse|ClientResource
    {
        // Verificar unicidad de WhatsApp si se está actualizando
        if ($request->has('whatsapp') && $request->whatsapp !== $client->whatsapp) {
            $existing = Client::where('whatsapp', $request->whatsapp)
                              ->where('id', '!=', $client->id)
                              ->first();
            
            if ($existing) {
                return response()->json([
                    'message' => "Ya existe un cliente con este WhatsApp: {$existing->full_name}"
                ], 400);
            }
        }
        
        $client->update($request->validated());
        
        return new ClientResource($client);
    }

    /**
     * Eliminar cliente
     */
    public function destroy(Client $client): JsonResponse
    {
        $client->delete();
        
        return response()->json([
            'message' => 'Cliente eliminado'
        ]);
    }

    /**
     * Asignar o actualizar la contraseña del usuario asociado a un cliente.
     * Si el cliente no tiene usuario, se crea uno (con su correo o teléfono) y se asigna la contraseña.
     */
    public function setPassword(SetClientPasswordRequest $request, Client $client): JsonResponse
    {
        $user = $client->user;

        if (!$user) {
            $user = $this->createUserForPatient($client);
            $client->update(['user_id' => $user->id]);
        }

        $user->password = \Illuminate\Support\Facades\Hash::make($request->input('password'));
        $user->save();

        return response()->json([
            'message' => 'Contraseña actualizada correctamente para el cliente.',
        ]);
    }

    private function createUserForPatient(Client $client): \App\Models\User
    {
        $fullName = trim(
            $client->first_name . ' ' .
            $client->paternal_surname .
            ($client->maternal_surname ? ' ' . $client->maternal_surname : '')
        );

        $email = $client->email;
        if ($email === null || $email === '') {
            $email = 'patient-' . $client->id . '@yes-ok.local';
        }

        $phone = null;
        if ($client->whatsapp !== null && $client->whatsapp !== '') {
            $phone = \App\Helpers\PhoneNormalizer::normalizeMexicanPhone($client->whatsapp);
        }

        return \App\Models\User::create([
            'name' => $fullName,
            'email' => $email,
            'phone' => $phone,
            'password' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(32)),
            'role' => \App\Enums\UserRole::CLIENTE,
            'is_active' => true,
        ]);
    }
}
