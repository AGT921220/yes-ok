@extends('layouts.guest')

@section('title', 'Iniciar sesión')

@section('content')
    <div class="card card-md yo-auth">
        <div class="yo-auth-brand">
            <img src="{{ asset('images/yes-ok.png') }}" alt="yes-ok">
        </div>
        <div class="card-body">
            <h2 class="h2 text-center mb-1">Iniciar sesión</h2>
            <p class="text-secondary text-center mb-4">Agenda de citas</p>
            <form method="POST" action="{{ route('login.store') }}" autocomplete="off">
                @csrf
                <div class="mb-3">
                    <label class="form-label" for="email">Correo</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Contraseña</label>
                    <input id="password" type="password" name="password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember" value="1">
                        <span class="form-check-label">Recordarme</span>
                    </label>
                </div>
                <button type="submit" class="btn btn-yo w-100">Entrar</button>
            </form>
        </div>
    </div>
@endsection
