<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StoreClientRequest;
use App\Http\Requests\Client\UpdateClientRequest;
use App\Models\Client;
use Illuminate\Http\Request;

class ClientPageController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $clients = Client::query()
            ->when($q !== '', function ($query) use ($q) {
                $digits = preg_replace('/\D/', '', $q);
                $query->where(function ($inner) use ($q, $digits) {
                    $inner->where('first_name', 'like', "%{$q}%")
                        ->orWhere('paternal_surname', 'like', "%{$q}%")
                        ->orWhere('maternal_surname', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('whatsapp', 'like', "%{$q}%");
                    if ($digits !== '') {
                        $inner->orWhere('whatsapp', 'like', "%{$digits}%");
                    }
                });
            })
            ->orderBy('first_name')
            ->orderBy('paternal_surname')
            ->get();

        return view('clients.index', compact('clients', 'q'));
    }

    public function create()
    {
        return view('clients.form', ['client' => new Client()]);
    }

    public function store(StoreClientRequest $request)
    {
        Client::create($request->validated() + [
            'consent_notifications' => $request->boolean('consent_notifications'),
        ]);

        return redirect()->route('clients.index')->with('success', 'Cliente registrado.');
    }

    public function edit(Client $client)
    {
        return view('clients.form', compact('client'));
    }

    public function update(UpdateClientRequest $request, Client $client)
    {
        $client->update($request->validated() + [
            'consent_notifications' => $request->boolean('consent_notifications'),
        ]);

        return redirect()->route('clients.index')->with('success', 'Cliente actualizado.');
    }

    public function destroy(Client $client)
    {
        if ($client->appointments()->exists()) {
            return back()->with('error', 'Este cliente tiene citas. No se puede eliminar.');
        }

        $client->delete();

        return redirect()->route('clients.index')->with('success', 'Cliente eliminado.');
    }
}
