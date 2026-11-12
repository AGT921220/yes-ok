@extends('layouts.app')

@section('title', 'Bloqueos')
@section('page-title', 'Bloqueos')

@section('content')
    @php
        $months = [1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    @endphp
    <div class="agenda-board">
        <div class="card calendar-card">
            <div class="card-body">
                <div class="month-toolbar">
                    <a class="btn btn-sm" href="{{ route('blocks.index', ['month' => $month->copy()->subMonth()->format('Y-m')]) }}">Anterior</a>
                    <strong class="text-capitalize">{{ $months[$month->month] }} {{ $month->year }}</strong>
                    <a class="btn btn-sm" href="{{ route('blocks.index', ['month' => $month->copy()->addMonth()->format('Y-m')]) }}">Siguiente</a>
                </div>
                <p class="text-secondary">Los días en rojo ya no se pueden agendar. Elige uno para bloquearlo.</p>
                @include('partials.month-calendar', ['route' => 'blocks.index'])
            </div>
        </div>
        <div class="card book-panel">
            <div class="card-body">
                <h2>Bloquear {{ $selected }}</h2>
                <form method="POST" action="{{ route('blocks.store') }}">
                    @csrf
                    <input type="hidden" name="start_date" value="{{ $selected }}">
                    <label class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="is_full_day" value="1" checked>
                        <span class="form-check-label">Todo el día</span>
                    </label>
                    <label class="form-label">Hasta</label>
                    <input type="date" name="end_date" value="{{ old('end_date', $selected) }}" class="form-control mb-2">
                    <label class="form-label">De</label>
                    <input type="time" name="start_time" class="form-control mb-2">
                    <label class="form-label">A</label>
                    <input type="time" name="end_time" class="form-control mb-2">
                    <input name="reason" class="form-control mb-2" maxlength="500" placeholder="Motivo">
                    <button class="btn btn-yo w-100">Bloquear</button>
                </form>
                <div class="block-list">
                    @forelse ($blocks as $block)
                        <div>
                            <strong>{{ $block->start_date?->format('d/m') }}–{{ $block->end_date?->format('d/m') }}</strong>
                            <span>{{ $block->is_full_day ? 'Día completo' : substr((string) $block->start_time, 0, 5).'–'.substr((string) $block->end_time, 0, 5) }}</span>
                            <form method="POST" action="{{ route('blocks.destroy', $block) }}">
                                @csrf @method('DELETE')
                                <button>Quitar</button>
                            </form>
                        </div>
                    @empty
                        <p class="text-secondary mb-0">No hay bloqueos.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
