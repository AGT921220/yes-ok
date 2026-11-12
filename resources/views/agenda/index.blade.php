@extends('layouts.app')

@section('title', 'Agenda')
@section('page-title', 'Agenda')

@section('content')
    @php
        $months = [1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        $weekdays = [1 => 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo'];
        $day = \Carbon\Carbon::parse($selected);
        $dayLabel = $weekdays[$day->dayOfWeekIso].' '.$day->day.' de '.$months[$day->month];
        $query = ['view' => $view, 'duration' => $duration, 'forced' => $forced ? 1 : null];
        $prev = match ($view) {
            'week' => $weekStart->copy()->subWeek()->toDateString(),
            'day' => $day->copy()->subDay()->toDateString(),
            default => null,
        };
        $next = match ($view) {
            'week' => $weekStart->copy()->addWeek()->toDateString(),
            'day' => $day->copy()->addDay()->toDateString(),
            default => null,
        };
        $title = match ($view) {
            'week' => $weekStart->day.'–'.$weekStart->copy()->endOfWeek(\Carbon\Carbon::SUNDAY)->day.' '.$months[$weekStart->month],
            'day' => $dayLabel,
            default => $months[$month->month].' '.$month->year,
        };
    @endphp
    <div class="agenda-board">
        <div class="card calendar-card">
            <div class="card-body">
                <div class="month-toolbar">
                    @if ($view === 'month')
                        <a data-keep class="btn btn-sm" href="{{ route('agenda.index', ['view' => 'month', 'month' => $month->copy()->subMonth()->format('Y-m'), 'duration' => $duration]) }}">Anterior</a>
                    @else
                        <a data-keep class="btn btn-sm" href="{{ route('agenda.index', $query + ['date' => $prev, 'month' => substr($prev, 0, 7)]) }}">Anterior</a>
                    @endif
                    <strong class="text-capitalize">{{ $title }}</strong>
                    @if ($view === 'month')
                        <a data-keep class="btn btn-sm" href="{{ route('agenda.index', ['view' => 'month', 'month' => $month->copy()->addMonth()->format('Y-m'), 'duration' => $duration]) }}">Siguiente</a>
                    @else
                        <a data-keep class="btn btn-sm" href="{{ route('agenda.index', $query + ['date' => $next, 'month' => substr($next, 0, 7)]) }}">Siguiente</a>
                    @endif
                </div>
                <div class="view-switch">
                    @foreach (['month' => 'Mes', 'week' => 'Semana', 'day' => 'Día'] as $key => $label)
                        <a data-keep class="{{ $view === $key ? 'on' : '' }}" href="{{ route('agenda.index', ['view' => $key, 'month' => $month->format('Y-m'), 'date' => $selected, 'duration' => $duration]) }}">{{ $label }}</a>
                    @endforeach
                </div>
                @if ($view === 'month')
                    @include('partials.month-calendar', ['route' => 'agenda.index'])
                @elseif ($view === 'week')
                    <div class="week-grid">
                        @for ($i = 0; $i < 7; $i++)
                            @php
                                $cursor = $weekStart->copy()->addDays($i);
                                $key = $cursor->toDateString();
                                $marks = $blocked[$key] ?? [];
                                $items = $weekAppointments->filter(fn ($appointment) => $appointment->date->toDateString() === $key);
                            @endphp
                            <a data-keep href="{{ route('agenda.index', ['view' => 'day', 'date' => $key, 'month' => $cursor->format('Y-m'), 'duration' => $duration]) }}" class="week-col {{ $key === $selected ? 'selected' : '' }} {{ $marks !== [] ? 'blocked' : '' }}">
                                <strong>{{ ['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'][$i] }} {{ $cursor->day }}</strong>
                                @foreach ($marks as $mark)
                                    <span class="cal-block">{{ $mark['label'] }}</span>
                                @endforeach
                                @foreach ($items as $appointment)
                                    <span class="cal-pill">{{ substr((string) $appointment->start_time, 0, 5) }} {{ $appointment->client?->first_name }}</span>
                                @endforeach
                            </a>
                        @endfor
                    </div>
                @else
                    <div class="day-board {{ ($blocked[$selected] ?? []) !== [] ? 'blocked' : '' }}">
                        @foreach ($blocked[$selected] ?? [] as $mark)
                            <div class="cal-block mb-2">{{ $mark['label'] }} {{ $mark['reason'] }}</div>
                        @endforeach
                        @forelse ($dayAppointments as $appointment)
                            <a class="day-apt" href="{{ route('agenda.show', $appointment) }}">
                                <strong>{{ substr((string) $appointment->start_time, 0, 5) }}–{{ substr((string) $appointment->end_time, 0, 5) }}</strong>
                                <span>{{ $appointment->client?->full_name }}</span>
                            </a>
                        @empty
                            <p class="text-secondary mb-0">Sin citas este día.</p>
                        @endforelse
                    </div>
                @endif
            </div>
        </div>
        <div class="card book-panel">
            <div class="card-body">
                <h2>Nueva cita</h2>
                <p class="text-capitalize">{{ $dayLabel }}</p>
                <form id="book-form" method="POST" action="{{ route('agenda.store') }}">
                    @csrf
                    <input type="hidden" name="date" value="{{ $selected }}">
                    <input type="hidden" name="view" value="{{ $view }}">
                    @if ($forced)
                        <input type="hidden" name="forced" value="1">
                    @endif
                    <label class="form-label">Duración</label>
                    <div class="duration-picks">
                        @foreach ($durations as $minutes)
                            <button type="button" data-duration="{{ $minutes }}" class="{{ (int) $duration === $minutes ? 'on' : '' }}">{{ $minutes }}</button>
                        @endforeach
                    </div>
                    <input id="duration-input" type="number" name="duration" min="15" max="720" step="5" value="{{ old('duration', $draft['duration'] ?? $duration) }}" class="form-control mb-2" required>
                    <label class="form-label">Hora</label>
                    <select name="start_time" class="form-select mb-2" required>
                        @forelse ($starts as $time)
                            <option value="{{ $time }}" @selected(old('start_time', $draft['start_time'] ?? null) === $time)>{{ $time }}</option>
                        @empty
                            <option value="">Sin horario libre</option>
                        @endforelse
                    </select>
                    <label class="form-label">Cliente</label>
                    <select name="client_id" class="form-select mb-2">
                        <option value="">Nuevo</option>
                        @foreach ($clients as $client)
                            <option value="{{ $client->id }}" @selected(old('client_id', $draft['client_id'] ?? null) == $client->id)>{{ $client->full_name }}</option>
                        @endforeach
                    </select>
                    <input name="first_name" value="{{ old('first_name', $draft['first_name'] ?? '') }}" class="form-control mb-1" placeholder="Nombre">
                    <input name="paternal_surname" value="{{ old('paternal_surname', $draft['paternal_surname'] ?? '') }}" class="form-control mb-1" placeholder="Apellido">
                    <input name="whatsapp" value="{{ old('whatsapp', $draft['whatsapp'] ?? '') }}" class="form-control mb-1" placeholder="Teléfono" inputmode="numeric">
                    <input type="email" name="email" value="{{ old('email', $draft['email'] ?? '') }}" class="form-control mb-2" placeholder="Correo, opcional">
                    <input name="reason" value="{{ old('reason', $draft['reason'] ?? '') }}" class="form-control mb-2" placeholder="Motivo">
                    @if (auth()->user()->role->value === 'ADMIN')
                        <label class="form-check mb-2">
                            <input id="forced-check" class="form-check-input" type="checkbox" @checked($forced)>
                            <span class="form-check-label">Horario completo</span>
                        </label>
                    @endif
                    <button class="btn btn-yo w-100" @disabled($closed || $starts === [])>Agendar</button>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    const form = document.getElementById('book-form');
    const names = ['client_id', 'first_name', 'paternal_surname', 'whatsapp', 'email', 'reason', 'start_time', 'duration'];

    function draftQuery(url) {
        const next = new URL(url, window.location.origin);
        names.forEach((name) => {
            const field = form.elements[name];
            if (field && field.value !== '') next.searchParams.set(name, field.value);
        });
        const forced = document.getElementById('forced-check');
        if (forced && forced.checked) next.searchParams.set('forced', '1');
        if (forced && ! forced.checked) next.searchParams.delete('forced');
        return next;
    }

    document.querySelectorAll('[data-keep]').forEach((link) => {
        link.addEventListener('click', (event) => {
            event.preventDefault();
            window.location = draftQuery(link.href);
        });
    });

    document.querySelectorAll('[data-duration]').forEach((button) => {
        button.addEventListener('click', () => {
            form.elements.duration.value = button.dataset.duration;
            const next = draftQuery(window.location.href);
            next.searchParams.set('duration', button.dataset.duration);
            window.location = next;
        });
    });

    document.getElementById('duration-input').addEventListener('change', (event) => {
        const minutes = Math.max(15, Number(event.target.value || 15));
        event.target.value = minutes;
        const next = draftQuery(window.location.href);
        next.searchParams.set('duration', String(minutes));
        window.location = next;
    });

    const forced = document.getElementById('forced-check');
    if (forced) {
        forced.addEventListener('change', () => {
            window.location = draftQuery(window.location.href);
        });
    }
</script>
@endpush
