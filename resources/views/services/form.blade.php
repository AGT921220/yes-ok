@extends('layouts.app')

@section('title', $service->exists ? 'Editar servicio' : 'Nuevo servicio')
@section('page-title', $service->exists ? 'Editar servicio' : 'Nuevo servicio')

@section('content')
    <div class="container-xl">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ $service->exists ? route('services.update', $service) : route('services.store') }}">
                    @csrf
                    @if ($service->exists) @method('PUT') @endif
                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input name="name" value="{{ old('name', $service->name) }}" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Duración</label>
                        <select name="duration_minutes" class="form-select" required>
                            @foreach (\App\Models\Service::ALLOWED_DURATIONS as $minutes)
                                <option value="{{ $minutes }}" @selected(old('duration_minutes', $service->duration_minutes) == $minutes)>{{ $minutes }} min</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Precio</label>
                        <input type="number" step="0.01" min="0" name="price" value="{{ old('price', $service->price) }}" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Orden</label>
                        <input type="number" min="0" name="sort_order" value="{{ old('sort_order', $service->sort_order ?? 0) }}" class="form-control">
                    </div>
                    <label class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active', $service->is_active ?? true))>
                        <span class="form-check-label">Activo</span>
                    </label>
                    <button class="btn btn-primary">Guardar</button>
                </form>
            </div>
        </div>
    </div>
@endsection
