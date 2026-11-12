@extends('layouts.app')

@section('title', 'Reportes')
@section('page-title', 'Reportes')

@section('content')
    <div class="container-xl">
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-3"><input type="date" name="start_date" value="{{ request('start_date') }}" class="form-control"></div>
            <div class="col-md-3"><input type="date" name="end_date" value="{{ request('end_date') }}" class="form-control"></div>
            <div class="col-md-2"><button class="btn btn-primary">Filtrar</button></div>
        </form>
        <div class="row g-3">
            <div class="col-md-4"><div class="card"><div class="card-body"><div class="text-secondary">Citas</div><div class="h1">{{ $total }}</div></div></div></div>
            <div class="col-md-4"><div class="card"><div class="card-body"><div class="text-secondary">Cancelación</div><div class="h1">{{ $cancellationRate }}%</div></div></div></div>
            <div class="col-md-4"><div class="card"><div class="card-body"><div class="text-secondary">Inasistencia</div><div class="h1">{{ $noShowRate }}%</div></div></div></div>
        </div>
        <div class="card mt-3">
            <div class="card-body">
                @forelse ($byStatus as $status => $count)
                    <div class="d-flex justify-content-between border-bottom py-2"><span>{{ $status }}</span><strong>{{ $count }}</strong></div>
                @empty
                    <p class="text-secondary mb-0">Sin citas en el rango.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
