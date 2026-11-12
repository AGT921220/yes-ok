@extends('layouts.app')

@section('title', $userModel->exists ? 'Editar usuario' : 'Nuevo usuario')
@section('page-title', $userModel->exists ? 'Editar usuario' : 'Nuevo usuario')

@section('content')
    <div class="container-xl">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ $userModel->exists ? route('users.update', $userModel) : route('users.store') }}">
                    @csrf
                    @if ($userModel->exists) @method('PUT') @endif
                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input name="name" value="{{ old('name', $userModel->name) }}" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Correo</label>
                        <input type="email" name="email" value="{{ old('email', $userModel->email) }}" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Teléfono</label>
                        <input name="phone" value="{{ old('phone', $userModel->phone) }}" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contraseña {{ $userModel->exists ? '(vacío = no cambiar)' : '' }}</label>
                        <input type="password" name="password" class="form-control" {{ $userModel->exists ? '' : 'required' }}>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Rol</label>
                        <select name="role" class="form-select">
                            @foreach (['ADMIN' => 'Administrador', 'ASISTENTE' => 'Personal'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('role', $userModel->role?->value ?? 'ASISTENTE') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <label class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active', $userModel->is_active ?? true))>
                        <span class="form-check-label">Activo</span>
                    </label>
                    <button class="btn btn-primary">Guardar</button>
                </form>
            </div>
        </div>
    </div>
@endsection
