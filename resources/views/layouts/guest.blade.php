<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'yes-ok')</title>
    <link rel="icon" href="{{ asset('images/yes-ok.png') }}">
    @include('partials.assets')
    <link rel="stylesheet" href="{{ asset('css/yes-ok.css') }}">
</head>
<body class="@yield('body-class', 'd-flex flex-column')">
    <div class="@yield('page-class', 'page page-center')">
        <div class="@yield('shell', 'container container-tight') py-4">
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
</body>
</html>
