@extends('layouts.guest')

@section('title', 'Mis citas')

@section('shell', 'container container-xl')
@section('page-class', '')
@section('body-class', 'yo-app')

@section('content')
    <div class="yo-public-bar">
        <img src="{{ asset('images/yes-ok.png') }}" alt="yes-ok">
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-light">Salir</button></form>
    </div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Hola, {{ $client?->full_name ?? auth()->user()->name }}</h2>
    </div>
    @forelse ($appointments as $appointment)
        <div class="card mb-2">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <strong>{{ $appointment->date->format('Y-m-d') }} {{ substr((string) $appointment->start_time, 0, 5) }}</strong>
                    <div class="text-secondary">{{ $appointment->status->value }}</div>
                </div>
                @if (! in_array($appointment->status->value, ['CANCELADA', 'COMPLETADA'], true))
                    <form method="POST" action="{{ route('portal.cancel', $appointment) }}">
                        @csrf
                        <input name="cancellation_reason" class="form-control form-control-sm mb-1" placeholder="Motivo" required>
                        <button class="btn btn-sm btn-danger">Cancelar</button>
                    </form>
                @endif
            </div>
        </div>
    @empty
        <p class="text-secondary">No tienes citas.</p>
    @endforelse
@endsection
