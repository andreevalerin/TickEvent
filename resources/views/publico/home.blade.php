
@extends('layouts.principal')

@section('titulo', 'Inicio - TickEvent')

@section('contenido')

    <section>
        <h2>Bienvenido a TickEvent</h2>

        <p>Encuentra entradas para conciertos y partidos de fútbol.</p>

        <a href="{{ route('eventos.index') }}">
            Explorar eventos
        </a>
    </section>

@endsection
