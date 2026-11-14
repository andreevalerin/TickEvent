@extends('layouts.admin')
@section('titulo', 'Dashboard - TickEvent')
@section('contenido')
<header class="admin-titulo"><div><span class="admin-etiqueta">Panel administrativo</span><h1>Dashboard</h1><p>Resumen de la actividad registrada en TickEvent.</p></div><span class="admin-periodo">Datos actuales de MySQL</span></header>
<section class="admin-indicadores">
    <article class="admin-indicador azul"><span class="admin-indicador-icono">▣</span><div><strong>{{ number_format($metricas['eventos_publicados']) }}</strong><small>Eventos publicados</small></div></article>
    <article class="admin-indicador turquesa"><span class="admin-indicador-icono">◇</span><div><strong>{{ number_format($metricas['entradas_vendidas']) }}</strong><small>Entradas vendidas</small></div></article>
    <article class="admin-indicador violeta"><span class="admin-indicador-icono">▥</span><div><strong>S/ {{ number_format($metricas['ingresos_centimos'] / 100, 2) }}</strong><small>Ingresos totales</small></div></article>
    <article class="admin-indicador arena"><span class="admin-indicador-icono">♙</span><div><strong>{{ number_format($metricas['organizadores']) }}</strong><small>Organizadores registrados</small></div></article>
</section>
@php
    $maxVenta = max(1, (int) ($ventas->max('total') ?? 0));
    $puntos = $ventas->map(function ($venta, $indice) use ($ventas, $maxVenta) {
        $x = $ventas->count() > 1 ? 30 + ($indice * (440 / ($ventas->count() - 1))) : 250;
        $y = 180 - (((int) $venta->total / $maxVenta) * 130);
        return $x.','.$y;
    })->implode(' ');
    $totalCategorias = (int) $categorias->sum('total');
    $colores = ['#18c9c5', '#168eea', '#8a66e8', '#f3a83b', '#e85f83'];
    $inicio = 0;
    $segmentos = [];
    foreach ($categorias as $indice => $categoria) {
        $fin = $totalCategorias > 0 ? $inicio + ((int) $categoria->total / $totalCategorias * 100) : $inicio;
        $segmentos[] = $colores[$indice % count($colores)].' '.$inicio.'% '.$fin.'%';
        $inicio = $fin;
    }
    $degradado = $segmentos ? implode(', ', $segmentos) : '#e7eef0 0 100%';
@endphp
<section class="admin-graficos">
    <article class="admin-panel admin-grafico-ventas">
        <div class="admin-panel-cabecera"><div><h2>Ventas de entradas</h2><p>Entradas asociadas a pagos aprobados, por fecha.</p></div></div>
        @if($ventas->isEmpty())
            <div class="admin-vacio">Todavía no existen ventas aprobadas para graficar.</div>
        @else
            <svg class="admin-linea" viewBox="0 0 500 220" role="img" aria-label="Ventas de entradas por fecha">
                <path d="M30 50H470M30 93H470M30 136H470M30 180H470" class="admin-rejilla"/>
                <polyline points="{{ $puntos }}" class="admin-linea-area"/>
                <polyline points="{{ $puntos }}" class="admin-linea-trazo"/>
                @foreach($ventas as $indice => $venta)
                    @php
                        $x = $ventas->count() > 1 ? 30 + ($indice * (440 / ($ventas->count() - 1))) : 250;
                        $y = 180 - (((int) $venta->total / $maxVenta) * 130);
                    @endphp
                    <circle cx="{{ $x }}" cy="{{ $y }}" r="4"/><text x="{{ $x }}" y="205" text-anchor="middle">{{ \Carbon\Carbon::parse($venta->fecha)->format('d/m') }}</text>
                @endforeach
            </svg>
        @endif
    </article>
    <article class="admin-panel admin-grafico-categorias">
        <div class="admin-panel-cabecera"><div><h2>Entradas por categoría</h2><p>Distribución de entradas vendidas.</p></div></div>
        <div class="admin-dona-contenido">
            <div class="admin-dona" style="background: conic-gradient({{ $degradado }})"><span><strong>{{ number_format($totalCategorias) }}</strong><small>entradas</small></span></div>
            <ul class="admin-leyenda">
                @forelse($categorias as $indice => $categoria)
                    <li><i style="background: {{ $colores[$indice % count($colores)] }}"></i><span>{{ ucfirst($categoria->categoria) }}</span><strong>{{ number_format($categoria->total) }}</strong></li>
                @empty
                    <li><i></i><span>Sin ventas registradas</span><strong>0</strong></li>
                @endforelse
            </ul>
        </div>
    </article>
</section>
<section class="admin-proximos">
    <div class="admin-seccion-titulo"><h2>Próximos eventos</h2><a href="{{ route('admin.eventos.index') }}">Ver todos</a></div>
    @if($eventos->isEmpty())
        <div class="admin-panel admin-vacio">No hay próximos eventos publicados.</div>
    @else
        <div class="admin-eventos-grid">
            @foreach($eventos as $evento)
                @php $vendidas = (int) ($vendidasPorEvento[$evento->id] ?? 0); @endphp
                <article class="admin-evento-card">
                    <div class="admin-evento-imagen">@if($evento->imagen_principal)<img src="{{ asset($evento->imagen_principal) }}" alt="{{ $evento->titulo }}">@else<span>{{ ucfirst($evento->categoria) }}</span>@endif</div>
                    <div class="admin-evento-detalle"><div class="admin-evento-fecha"><strong>{{ $evento->fecha_evento->format('d') }}</strong><small>{{ strtoupper($evento->fecha_evento->translatedFormat('M')) }}</small></div><div><h3>{{ $evento->titulo }}</h3><p>{{ $evento->lugar }}, {{ $evento->ciudad }}</p></div></div>
                    <div class="admin-evento-progreso"><span style="width: {{ $evento->aforo_total > 0 ? min(100, $vendidas / $evento->aforo_total * 100) : 0 }}%"></span></div>
                    <p class="admin-evento-cifra"><strong>{{ number_format($vendidas) }}</strong> / {{ number_format($evento->aforo_total) }} vendidas</p>
                    <a href="{{ route('eventos.show', $evento) }}" class="admin-boton">Ver evento</a>
                </article>
            @endforeach
        </div>
    @endif
</section>
@endsection
