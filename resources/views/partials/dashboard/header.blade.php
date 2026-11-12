@php
    $user = auth()->user();
    $items = [
        ['agenda.*', 'agenda.index', 'ti-calendar', 'Agenda'],
        ['clients.*', 'clients.index', 'ti-users', 'Clientes'],
        ['blocks.*', 'blocks.index', 'ti-clock-pause', 'Bloqueos'],
        ['reschedule.*', 'reschedule.index', 'ti-refresh', 'Por reagendar'],
        ['reports.*', 'reports.index', 'ti-chart-bar', 'Reportes'],
    ];
    if ($user->role->value === 'ADMIN') {
        $items[] = ['settings.*', 'settings.index', 'ti-settings', 'Configuración'];
    }
@endphp
<aside class="yo-side" id="yo-side">
    <a class="yo-brand" href="{{ route('agenda.index') }}">
        <img src="{{ asset('images/yes-ok.png') }}" alt="yes-ok">
    </a>
    <nav class="yo-nav">
        @foreach ($items as [$pattern, $route, $icon, $label])
            <a class="{{ request()->routeIs($pattern) ? 'active' : '' }}" href="{{ route($route) }}">
                <i class="ti {{ $icon }}"></i> {{ $label }}
            </a>
        @endforeach
    </nav>
    <div class="yo-side-foot">
        <div class="fw-bold text-white">{{ $user->name }}</div>
        <div class="small">{{ $user->role->value === 'ADMIN' ? 'Administrador' : 'Asistente' }}</div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Cerrar sesión</button>
        </form>
    </div>
</aside>
