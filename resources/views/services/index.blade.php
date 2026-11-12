@extends('layouts.app')

@section('title', 'Servicios')
@section('page-title', 'Servicios')

@section('page-actions')
    <a href="{{ route('services.create') }}" class="btn btn-primary">Nuevo servicio</a>
@endsection

@section('content')
    <div class="container-xl">
        <div class="card">
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr><th>Nombre</th><th>Duración</th><th>Precio</th><th>Activo</th><th></th></tr>
                    </thead>
                    <tbody>
                        @forelse ($services as $service)
                            <tr>
                                <td>{{ $service->name }}</td>
                                <td>{{ $service->duration_minutes }} min</td>
                                <td>{{ $service->price }}</td>
                                <td>{{ $service->is_active ? 'Sí' : 'No' }}</td>
                                <td>
                                    <a class="btn btn-sm" href="{{ route('services.edit', $service) }}">Editar</a>
                                    <form method="POST" action="{{ route('services.destroy', $service) }}" class="d-inline" onsubmit="return confirm('¿Eliminar servicio?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-danger">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-secondary">No hay servicios.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
