@extends('layouts.guest')

@section('title', 'Portal del cliente')

@section('content')
    <div class="card card-md yo-auth">
        <div class="yo-auth-brand">
            <img src="{{ asset('images/yes-ok.png') }}" alt="yes-ok">
        </div>
        <div class="card-body">
            <h2 class="h2 text-center mb-4">Portal del cliente</h2>
            <form method="POST" action="{{ route('portal.authenticate') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Correo</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">o teléfono</label>
                    <input name="phone" value="{{ old('phone') }}" class="form-control" placeholder="10 dígitos">
                </div>
                <div class="mb-3">
                    <label class="form-label">Contraseña</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button class="btn btn-yo w-100">Entrar</button>
            </form>
        </div>
    </div>
    <div class="text-center mt-3"><a href="{{ route('login') }}">Volver al panel</a></div>
@endsection
