@extends('layouts.app')

@section('title', 'Personal')
@section('page-title', 'Personal')

@section('page-actions')
    <a href="{{ route('users.create') }}" class="btn btn-primary">Nuevo usuario</a>
@endsection

@section('content')
    <div class="container-xl">
        <div class="card">
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->role->value }}</td>
                                <td>
                                    <a class="btn btn-sm" href="{{ route('users.edit', $user) }}">Editar</a>
                                    <form method="POST" action="{{ route('users.destroy', $user) }}" class="d-inline" onsubmit="return confirm('¿Eliminar usuario?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-danger">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
