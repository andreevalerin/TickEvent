@extends('layouts.organizador')
@section('titulo', 'Mis eventos - TickEvent')
@section('contenido')
<div class="panel-rol-contenido">
    <header class="admin-titulo organizador-titulo">
        <div><span class="admin-etiqueta">Panel del organizador</span><h1>Mis eventos</h1><p>Administra tus eventos, crea nuevos y revisa su estado.</p></div>
        <a href="{{ route('organizador.eventos.create') }}" class="admin-boton organizador-crear">＋ Crear evento</a>
    </header>

    @if(session('estado'))<div class="compra-exito">{{ session('estado') }}</div>@endif

    <section class="admin-indicadores organizador-indicadores">
        <article class="admin-indicador azul"><span class="admin-indicador-icono">▣</span><div><strong>{{ number_format($metricas['eventos']) }}</strong><small>Eventos totales</small></div></article>
        <article class="admin-indicador turquesa"><span class="admin-indicador-icono">✓</span><div><strong>{{ number_format($metricas['proximos']) }}</strong><small>Próximos eventos</small></div></article>
        <article class="admin-indicador violeta"><span class="admin-indicador-icono">◇</span><div><strong>{{ number_format($metricas['entradas']) }}</strong><small>Entradas vendidas</small></div></article>
        <article class="admin-indicador arena"><span class="admin-indicador-icono">▥</span><div><strong>S/ {{ number_format($metricas['ingresos_centimos'] / 100, 2) }}</strong><small>Ingresos totales</small></div></article>
    </section>

    <div class="organizador-herramientas">
        <nav><span class="activo">Todos ({{ $eventos->count() }})</span><span>Próximos ({{ $eventos->where('estado', 'publicado')->where('fecha_evento', '>=', now())->count() }})</span><span>Pasados ({{ $eventos->where('fecha_evento', '<', now())->count() }})</span><span>Borradores ({{ $eventos->where('estado', 'borrador')->count() }})</span></nav>
        <a href="{{ route('organizador.zonas.index') }}" class="boton-secundario">Administrar zonas</a>
    </div>

    @if($eventos->isEmpty())
        <section class="admin-panel admin-vacio"><div><h2>Aún no registraste eventos</h2><p>Crea tu primer evento y configura sus zonas y tarifas.</p></div></section>
    @else
        <section class="organizador-lista">
            @foreach($eventos as $evento)
                <article class="organizador-fila">
                    <div class="organizador-fila-imagen">@if($evento->imagen_principal)<img src="{{ asset($evento->imagen_principal) }}" alt="{{ $evento->titulo }}">@else<span>{{ ucfirst($evento->categoria) }}</span>@endif</div>
                    <div class="organizador-fila-info"><h2>{{ $evento->titulo }}</h2><p>▣ {{ $evento->fecha_evento->translatedFormat('D. d M Y, H:i') }}</p><p>⌖ {{ $evento->lugar }}, {{ $evento->ciudad }}</p><span>{{ ucfirst($evento->categoria) }}</span></div>
                    <div class="organizador-fila-estado"><span class="evento-estado {{ $evento->estado }}">{{ ucfirst($evento->estado) }}</span></div>
                    <div class="organizador-fila-cifra"><strong>{{ number_format($evento->entradas_vendidas) }} / {{ number_format($evento->aforo_total) }}</strong><small>Entradas vendidas</small></div>
                    <div class="organizador-fila-cifra"><strong>S/ {{ number_format($evento->ingresos_centimos / 100, 2) }}</strong><small>Ingresos</small></div>
                    <div class="organizador-fila-acciones">
                        @if($evento->estado === 'publicado')<a href="{{ route('eventos.show', $evento) }}" class="boton-secundario">Ver</a>@endif
                        @if($evento->estado !== 'retirado')<a href="{{ route('organizador.eventos.edit', $evento) }}" class="boton-secundario">Editar</a>@endif
                        @if($evento->estado !== 'retirado')<form action="{{ route('organizador.eventos.destroy', $evento) }}" method="POST">@csrf @method('DELETE')<button type="submit" class="boton-retirar">Retirar</button></form>@endif
                    </div>
                </article>
            @endforeach
        </section>
    @endif
</div>
@endsection
