@extends('layouts.app')

@section('title', 'Configuración')
@section('page-title', 'Configuración')

@section('content')
    <div class="container-xl">
        <div class="card mb-3">
            <div class="card-body">
                <p class="mb-1">Horario de agenda: {{ $scheduleStart }} – {{ $scheduleEnd }}</p>
                <p class="mb-0 text-secondary">Paso entre citas: {{ $slotStep }} minutos. Se cambia en el .env (`BOOKING_SCHEDULE_START`, `BOOKING_SCHEDULE_END`, `BOOKING_SLOT_STEP_MINUTES`).</p>
            </div>
        </div>
        <div class="card">
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead><tr><th>Clave</th><th>Valor</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($settings as $setting)
                            <tr>
                                <td>{{ $setting->key }}</td>
                                <td colspan="2">
                                    <form method="POST" action="{{ route('settings.update', $setting->key) }}" class="d-flex gap-2">
                                        @csrf @method('PUT')
                                        <input name="value" value="{{ $setting->value }}" class="form-control">
                                        <button class="btn btn-primary">Guardar</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-secondary">No hay ajustes guardados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
