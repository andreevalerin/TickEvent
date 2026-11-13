@extends('layouts.principal')
@section('titulo', $evento->titulo . ' - TickEvent')
@section('contenido')
    <section class="detail-hero">
        <img class="detail-background" src="{{ asset($evento->imagen_principal ?: ($evento->categoria === 'partido' ? 'imagenes/prototipo/event-football.jpg' : 'imagenes/prototipo/event-concert.jpg')) }}" alt="" fetchpriority="high">
        <div class="shell">
            <a class="back-link" href="{{ route('eventos.index') }}"><x-icono nombre="arrow-left" :tamano="18" /> Volver a eventos</a>
            <span class="badge-light">{{ $evento->categoria === 'partido' ? 'Deportes' : 'Conciertos' }}</span>
            <h1>{{ $evento->titulo }}</h1>
            @if($evento->descripcion)<p class="event-subtitle">{{ $evento->descripcion }}</p>@endif
            <div class="event-meta">
                <span><x-icono nombre="calendar-days" :tamano="18" /><time datetime="{{ $evento->fecha_evento->format('Y-m-d') }}">{{ $evento->fecha_evento->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}</time></span>
                <span><x-icono nombre="clock-3" :tamano="18" />{{ $evento->fecha_evento->format('H:i') }}</span>
                <span><x-icono nombre="map-pin" :tamano="18" />{{ $evento->lugar }}, {{ $evento->ciudad }}</span>
            </div>
        </div>
    </section>
    <section class="shell detail-content" data-evento>
        <div class="tabs" data-pestanas aria-label="Información del evento">
            <button type="button" class="active" data-panel="tarifario" id="pestana-tarifario">Tarifario</button>
            <button type="button" data-panel="acerca" id="pestana-acerca">Acerca del evento</button>
            <button type="button" data-panel="terminos" id="pestana-terminos">Términos y condiciones</button>
        </div>
        <section id="tarifario" aria-labelledby="pestana-tarifario">
            <form action="{{ route('eventos.comprar', $evento) }}" method="GET" id="seleccion-entradas" class="ticket-layout">
                <div class="venue-card venue-image-card">
                    @if($evento->imagen_plano)
                        <img src="{{ asset($evento->imagen_plano) }}" alt="Plano de zonas de {{ $evento->titulo }}">
                    @else
                        <div class="venue-placeholder"><x-icono nombre="map-pin" :tamano="40" /><h2>{{ $evento->lugar }}</h2><p>Plano de zonas pendiente de publicación.</p></div>
                    @endif
                </div>
                <section class="pricing-card" aria-labelledby="sectores-titulo">
                    <div class="pricing-head">
                        <div><span class="eyebrow">Elige tu ubicación</span><h2 id="sectores-titulo">Seleccionar sector</h2><p>Elige una zona para ver su tarifa y disponibilidad.</p></div>
                        @if($sectores->where('disponibles', '>', 0)->isNotEmpty())<span class="status"><i aria-hidden="true"></i> Disponible</span>@endif
                    </div>
                    <div class="sector-list" role="group" aria-labelledby="sectores-titulo">
                        @forelse($sectores as $sector)
                            <label class="sector-row">
                                <input class="sector-radio" type="radio" name="tarifa_id" value="{{ $sector['id'] }}" data-zona="{{ $sector['zona'] }}" data-precio="{{ $sector['precio'] }}" @disabled($sector['disponibles'] < 1) required>
                                <i class="sector-color" aria-hidden="true"></i>
                                <span><strong>{{ $sector['zona'] }}</strong><small>{{ $sector['disponibles'] > 0 ? number_format($sector['disponibles']).' disponibles' : 'Sin disponibilidad' }}@if($sector['tarifa'] !== 'Regular') · {{ $sector['tarifa'] }}@endif</small></span>
                                <b>S/{{ number_format((float) $sector['precio'], fmod((float) $sector['precio'], 1) ? 2 : 0) }}</b>
                                <x-icono nombre="check" :tamano="18" />
                            </label>
                        @empty
                            <p class="pricing-empty">Las tarifas de este evento todavía no están publicadas.</p>
                        @endforelse
                    </div>
                </section>
            </form>
            <p class="fare-note">Cargo de servicio adicional: S/{{ number_format($evento->cargoServicio(), 2) }} por entrada. La disponibilidad se confirma al reservar.</p>
        </section>
        <section id="acerca" class="info-copy" aria-labelledby="pestana-acerca">
            <h2>{{ $evento->titulo }}</h2>
            @if($evento->descripcion)<p>{{ $evento->descripcion }}</p>@endif
            <p>{{ $evento->lugar }}, {{ $evento->ciudad }}@if($evento->direccion) · {{ $evento->direccion }}@endif.</p>
            <p>Las entradas se venden por zonas. No se asignan asientos numerados.</p>
        </section>
        <section id="terminos" class="info-copy" aria-labelledby="pestana-terminos">
            <h2>Condiciones del evento</h2>
            <p>Cada entrada permite el acceso a la zona adquirida y corresponde a un asistente.</p>
            <p>El límite es de seis entradas por cliente y evento, incluyendo compras anteriores.</p>
            <p>Las reservas activas duran 15 minutos. La disponibilidad se confirma durante el proceso de reserva.</p>
            <a class="text-link" href="{{ route('politicas.cancelaciones') }}">Consultar política de cancelaciones</a>
        </section>
    </section>
    <div class="purchase-bar">
        <div class="shell purchase-inner">
            <div><small>Venta general</small><strong data-resumen-sector aria-live="polite">Selecciona un sector</strong></div>
            <span class="available"><i aria-hidden="true"></i> {{ $sectores->where('disponibles', '>', 0)->isNotEmpty() ? 'Disponible' : 'Sin disponibilidad' }}</span>
            <button class="btn btn-primary" type="submit" form="seleccion-entradas" data-comprar @disabled($sectores->where('disponibles', '>', 0)->isEmpty())>Comprar ahora <x-icono nombre="arrow-left" class="arrow-right" :tamano="17" /></button>
        </div>
    </div>
@endsection
