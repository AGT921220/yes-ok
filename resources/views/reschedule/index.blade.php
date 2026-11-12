@extends('layouts.app')

@section('title', 'Por reagendar')
@section('page-title', 'Por reagendar')

@section('content')
    <div class="container-xl">
        <div class="card">
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead><tr><th>Fecha</th><th>Cliente</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($appointments as $appointment)
                            <tr>
                                <td>{{ $appointment->date->format('Y-m-d') }} {{ substr((string) $appointment->start_time, 0, 5) }}</td>
                                <td>{{ $appointment->client?->full_name }}</td>
                                <td><a class="btn btn-sm" href="{{ route('agenda.show', $appointment) }}">Abrir</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-secondary">No hay citas por reagendar.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
