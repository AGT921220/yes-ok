@extends('layouts.app')

@section('title', $client->exists ? 'Editar cliente' : 'Nuevo cliente')
@section('page-title', $client->exists ? 'Editar cliente' : 'Nuevo cliente')

@section('content')
    @php
        $digits = preg_replace('/\D/', '', (string) old('whatsapp', $client->whatsapp));
        $phone = strlen($digits) > 10 ? substr($digits, -10) : $digits;
    @endphp
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ $client->exists ? route('clients.update', $client) : route('clients.store') }}">
                        @csrf
                        @if ($client->exists)
                            @method('PUT')
                        @endif
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Nombre</label>
                                <input name="first_name" value="{{ old('first_name', $client->first_name) }}" class="form-control" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Apellido paterno</label>
                                <input name="paternal_surname" value="{{ old('paternal_surname', $client->paternal_surname) }}" class="form-control" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Apellido materno</label>
                                <input name="maternal_surname" value="{{ old('maternal_surname', $client->maternal_surname) }}" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Teléfono</label>
                                <input name="whatsapp" value="{{ $phone }}" class="form-control" placeholder="10 dígitos" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Correo <span class="text-secondary">(opcional)</span></label>
                                <input type="email" name="email" value="{{ old('email', $client->email) }}" class="form-control">
                            </div>
                        </div>
                        <label class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" name="consent_notifications" value="1" @checked(old('consent_notifications', $client->consent_notifications ?? true))>
                            <span class="form-check-label">Recibir recordatorios</span>
                        </label>
                        <button class="btn btn-yo">Guardar</button>
                        <a href="{{ route('clients.index') }}" class="btn">Cancelar</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection