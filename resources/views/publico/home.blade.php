@extends('layouts.principal')
@section('titulo', 'TickEvent — Entradas para conciertos y fútbol')
@section('descripcion', 'Encuentra conciertos y partidos en Perú. Explora las zonas y tarifas de tu próximo evento.')
@section('contenido')
    <section class="hero">
        <div class="shell hero-content">
            <span class="eyebrow light">La emoción empieza aquí</span>
            <h1>Tu próxima entrada<br>está <em>aquí</em></h1>
            <p>Conciertos y partidos en Perú. Elige tu próximo gran momento.</p>
        </div>
    </section>
    @include('publico.eventos.busqueda')
    <section class="shell home-main" aria-labelledby="eventos-titulo">
        <div class="section-heading">
            <div>
                <span class="eyebrow">Agenda destacada</span>
                <h2 id="eventos-titulo">Eventos para ti</h2>
                <p>Vive experiencias que se quedan contigo.</p>
            </div>
            @include('publico.eventos.categorias')
        </div>
        @include('publico.eventos.listado', ['eventos' => $eventos])
        @if($eventos->isNotEmpty())
            <div class="catalog-link"><a class="btn btn-secondary" href="{{ route('eventos.index') }}">Ver todos los eventos <x-icono nombre="arrow-left" class="arrow-right" :tamano="17" /></a></div>
        @endif
    </section>
    <section class="trust-strip" aria-label="Tu experiencia en TickEvent">
        <div class="shell trust-grid">
            <div class="trust-item"><span><x-icono nombre="calendar-days" /></span><div><strong>Encuentra tu evento</strong><small>Conciertos y fútbol en Perú</small></div></div>
            <div class="trust-item"><span><x-icono nombre="map-pin" /></span><div><strong>Elige tu ubicación</strong><small>Zonas y tarifas a tu alcance</small></div></div>
            <div class="trust-item"><span><x-icono nombre="ticket" /></span><div><strong>Tus entradas, en tu cuenta</strong><small>Consulta tus próximos eventos</small></div></div>
        </div>
    </section>
@endsection
