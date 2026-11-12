<div class="slot-grid">
    @foreach ($rows as $row)
        @if ($row['appointment'] && empty($public))
            <a class="slot taken" href="{{ route('agenda.show', $row['appointment']) }}">
                <strong>{{ $row['time'] }}</strong>
                <small>{{ $row['appointment']->client?->full_name }}</small>
            </a>
        @elseif ($row['busy'])
            <span class="slot busy">
                <strong>{{ $row['time'] }}</strong>
                <small>Ocupado</small>
            </span>
        @else
            <a class="slot free {{ ($start ?? null) === $row['time'] ? 'active' : '' }}" href="{{ route($route, ['month' => $month->format('Y-m'), 'date' => $selected, 'start' => $row['time'], 'forced' => ! empty($forced) ? 1 : null]) }}">
                <strong>{{ $row['time'] }}</strong>
                <small>Libre</small>
            </a>
        @endif
    @endforeach
</div>
