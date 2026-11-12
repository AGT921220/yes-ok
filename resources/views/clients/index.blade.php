@extends('layouts.app')

@section('title', 'Clientes')
@section('page-title', 'Clientes')

@section('page-actions')
    <a href="{{ route('clients.create') }}" class="btn btn-yo">Nuevo cliente</a>
@endsection

@section('content')
    <div class="card clients-card">
        <div class="card-body">
            <form method="GET" class="clients-search">
                <input name="q" value="{{ $q }}" class="form-control" placeholder="Buscar por nombre, correo o teléfono">
                <button class="btn btn-yo">Buscar</button>
            </form>
            <div class="client-grid">
                @forelse ($clients as $client)
                    @php
                        $digits = preg_replace('/\D/', '', (string) $client->whatsapp);
                        $phone = strlen($digits) > 10 ? substr($digits, -10) : $digits;
                    @endphp
                    <article class="client-tile">
                        <div class="client-avatar">{{ strtoupper(substr($client->first_name, 0, 1)) }}</div>
                        <div>
                            <h3>{{ $client->full_name }}</h3>
                            <p>{{ $phone }}</p>
                            <p class="text-secondary">{{ $client->email ?: 'Sin correo' }}</p>
                        </div>
                        <div class="client-actions">
                            <a href="{{ route('clients.edit', $client) }}" class="btn btn-sm">Editar</a>
                            <form method="POST" action="{{ route('clients.destroy', $client) }}" onsubmit="return confirm('¿Eliminar este cliente?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Eliminar</button>
                            </form>
                        </div>
                    </article>
                @empty
                    <div class="client-empty">
                        <h3>Todavía no hay clientes</h3>
                        <p>Registra el primero o créalo al agendar una cita.</p>
                        <a href="{{ route('clients.create') }}" class="btn btn-yo">Nuevo cliente</a>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
