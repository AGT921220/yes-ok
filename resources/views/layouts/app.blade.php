<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Panel') | yes-ok</title>
    <link rel="icon" href="{{ asset('images/yes-ok.png') }}">
    @include('partials.assets')
    <link rel="stylesheet" href="{{ asset('css/yes-ok.css') }}">
    @stack('styles')
</head>
<body class="yo-app">
    <div class="yo-shell">
        @include('partials.dashboard.header')
        <div class="yo-main">
            <div class="yo-top">
                <div class="d-flex align-items-center gap-2">
                    <button class="yo-menu" type="button" onclick="document.getElementById('yo-side').classList.toggle('open')">Menú</button>
                    <h1>@yield('page-title', 'Agenda')</h1>
                </div>
                <div class="btn-list">@yield('page-actions')</div>
            </div>
            <div class="yo-body">
                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @yield('content')
            </div>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
