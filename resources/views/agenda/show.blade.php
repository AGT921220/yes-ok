@extends('layouts.app')

@section('title', 'Cita')
@section('page-title', 'Cita')

@section('page-actions')
    <a href="{{ route('agenda.index', ['date' => $appointment->date->format('Y-m-d'), 'view' => 'day']) }}" class="btn">Volver</a>
@endsection

@section('content')
    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-body">
                    <h2 class="mb-1">{{ $appointment->client?->full_name }}</h2>
                    <p class="text-secondary mb-3">{{ $appointment->date->format('Y-m-d') }} · {{ substr((string) $appointment->start_time, 0, 5) }}–{{ substr((string) $appointment->end_time, 0, 5) }}</p>
                    @if ($appointment->reason)
                        <p class="mb-0">{{ $appointment->reason }}</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card mb-3">
                <div class="card-body">
                    <div class="form-label">Asistencia</div>
                    <div class="status-row">
                        @foreach (['pendiente' => 'Pendiente', 'asistio' => 'Asistió', 'no_asistio' => 'No asistió'] as $value => $label)
                            <form method="POST" action="{{ route('agenda.attendance', $appointment) }}">
                                @csrf
                                <button name="status" value="{{ $value }}" class="status-btn {{ $appointment->attendance_status === $value ? 'on' : '' }}">{{ $label }}</button>
                            </form>
                        @endforeach
                    </div>
                    <div class="form-label mt-3">Cita</div>
                    <div class="status-row">
                        @if ($appointment->status->value !== 'CANCELADA')
                            <form method="POST" action="{{ route('agenda.confirm', $appointment) }}">
                                @csrf
                                <button class="status-btn {{ $appointment->status->value === 'CONFIRMADA' ? 'on' : '' }}">Confirmada</button>
                            </form>
                            <form method="POST" action="{{ route('agenda.cancel', $appointment) }}" onsubmit="return confirm('¿Cancelar esta cita?')">
                                @csrf
                                <input type="hidden" name="cancellation_reason" value="Cancelada desde la agenda">
                                <button class="status-btn danger">Cancelada</button>
                            </form>
                        @else
                            <span class="status-btn danger on">Cancelada</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
