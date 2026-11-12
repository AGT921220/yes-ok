<div class="month-weekdays">
    @foreach (['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'] as $weekday)
        <div>{{ $weekday }}</div>
    @endforeach
</div>
<div class="month-grid">
    @foreach ($weeks as $week)
        @foreach ($week as $day)
            @php $marks = $blocked[$day['date']] ?? []; @endphp
            <a
                data-keep
                href="{{ route($route, ['view' => $view ?? 'month', 'month' => $month->format('Y-m'), 'date' => $day['date'], 'duration' => $duration ?? null, 'forced' => ! empty($forced) ? 1 : null]) }}"
                class="month-cell {{ $day['in_month'] ? '' : 'out' }} {{ $day['today'] ? 'today' : '' }} {{ $day['sunday'] ? 'sunday' : '' }} {{ $selected === $day['date'] ? 'selected' : '' }} {{ $marks !== [] ? 'blocked' : '' }}"
            >
                <span class="day-num">{{ $day['day'] }}</span>
                @foreach ($marks as $mark)
                    <span class="cal-block">{{ $mark['label'] }}</span>
                @endforeach
                @foreach ($day['labels'] as $label)
                    <span class="cal-pill">{{ $label }}</span>
                @endforeach
                @if ($day['more'] > 0)
                    <span class="cal-more">+{{ $day['more'] }}</span>
                @endif
            </a>
        @endforeach
    @endforeach
</div>
