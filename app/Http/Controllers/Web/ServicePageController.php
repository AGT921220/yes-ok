<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Service\StoreServiceRequest;
use App\Http\Requests\Service\UpdateServiceRequest;
use App\Models\Service;

class ServicePageController extends Controller
{
    public function index()
    {
        return view('services.index', [
            'services' => Service::orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('services.form', ['service' => new Service(['duration_minutes' => 30, 'is_active' => true])]);
    }

    public function store(StoreServiceRequest $request)
    {
        Service::create($request->validated() + ['is_active' => $request->boolean('is_active')]);

        return redirect()->route('services.index')->with('success', 'Servicio creado.');
    }

    public function edit(Service $service)
    {
        return view('services.form', compact('service'));
    }

    public function update(UpdateServiceRequest $request, Service $service)
    {
        $service->update($request->validated() + ['is_active' => $request->boolean('is_active')]);

        return redirect()->route('services.index')->with('success', 'Servicio actualizado.');
    }

    public function destroy(Service $service)
    {
        $service->delete();

        return redirect()->route('services.index')->with('success', 'Servicio eliminado.');
    }
}
