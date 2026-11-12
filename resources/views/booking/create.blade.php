@extends('layouts.guest')

@section('title', 'Reservar')
@section('body-class', 'yo-app')
@section('page-class', '')
@section('shell', 'container-xl')

@section('content')
    @php
        $months = [1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        $weekdays = [1 => 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo'];
        $day = \Carbon\Carbon::parse($selected);
        $dayLabel = $weekdays[$day->dayOfWeekIso].' '.$day->day.' de '.$months[$day->month];
    @endphp
    <div class="yo-public-bar">
        <img src="{{ asset('images/yes-ok.png') }}" alt="yes-ok">
        <a class="btn btn-light" href="{{ route('login') }}">Entrar al panel</a>
    </div>
    <div class="row g-3">
        <div class="col-xl-7">
            <div class="card">
                <div class="card-body">
                    <h2 class="h2 mb-3">Elige un día</h2>
                    @include('partials.month-calendar', ['route' => 'booking.create'])
                </div>
            </div>
        </div>
        <div class="col-xl-5">
            <div class="card">
                <div class="card-body">
                    <div class="text-secondary small text-uppercase">Apartar cita</div>
                    <h2 class="h2 text-capitalize">{{ $dayLabel }}</h2>
                    <p class="text-secondary">Cada cita dura {{ $duration }} minutos.</p>
                    @if ($closed)
                        <p class="text-secondary">Este domingo está cerrado.</p>
                    @else
                        @include('partials.slot-menu', ['route' => 'booking.create', 'public' => true])
                    @endif
                    @if ($start)
                        <form method="POST" action="{{ route('booking.store') }}" class="mt-3 border-top pt-3">
                            @csrf
                            <input type="hidden" name="date" value="{{ $selected }}">
                            <input type="hidden" name="start_time" value="{{ $start }}">
                            <h3 class="h3">Tus datos · {{ $start }}</h3>
                            <div class="row">
                                <div class="col-md-6 mb-2"><input name="first_name" value="{{ old('first_name') }}" class="form-control" placeholder="Nombre" required></div>
                                <div class="col-md-6 mb-2"><input name="paternal_surname" value="{{ old('paternal_surname') }}" class="form-control" placeholder="Apellido paterno" required></div>
                                <div class="col-md-6 mb-2"><input name="maternal_surname" value="{{ old('maternal_surname') }}" class="form-control" placeholder="Apellido materno"></div>
                                <div class="col-md-6 mb-2"><input name="phone" value="{{ old('phone') }}" class="form-control" placeholder="Teléfono 10 dígitos" required></div>
                                <div class="col-12 mb-2"><input type="email" name="email" value="{{ old('email') }}" class="form-control" placeholder="Correo (opcional)"></div>
                                <div class="col-12 mb-3"><input name="reason" value="{{ old('reason') }}" class="form-control" placeholder="Motivo"></div>
                            </div>
                            <button class="btn btn-yo w-100">Confirmar cita</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
