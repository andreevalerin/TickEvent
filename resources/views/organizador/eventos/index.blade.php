@extends('layouts.organizador')
@section('titulo', 'Mis eventos - TickEvent')
@section('contenido')
    <div class="org-page-title">
        <div><span class="eyebrow">Panel organizador</span><h1>Mis eventos</h1><p>Administra tu cartelera, zonas y tarifas.</p></div>
        <a href="{{ route('organizador.eventos.create') }}" class="btn btn-primary"><span aria-hidden="true">＋</span> Crear evento</a>
    </div>
    <div class="org-metrics">
        @foreach([['calendar-days', 'Eventos publicados', $metricas['publicados'], 'Visibles en la cartelera'], ['list-filter', 'Borradores', $metricas['borradores'], 'Pendientes de publicar'], ['ticket', 'Eventos registrados', $metricas['total'], 'Toda tu cartelera'], ['map-pin', 'Aforo publicado', $metricas['aforo'], 'Capacidad total configurada']] as [$icono, $etiqueta, $valor, $detalle])
            <article class="org-metric"><span><x-icono :nombre="$icono" :tamano="20" /></span><div><small>{{ $etiqueta }}</small><strong>{{ number_format($valor) }}</strong><em>{{ $detalle }}</em></div></article>
        @endforeach
    </div>
    <section class="org-card" aria-label="Lista de eventos">
        <div class="org-tools">
            <nav class="org-tabs" aria-label="Filtrar eventos">
                @foreach(['todos' => 'Todos', 'proximos' => 'Próximos', 'pasados' => 'Pasados', 'borradores' => 'Borradores', 'retirados' => 'Retirados'] as $clave => $etiqueta)
                    <a href="{{ route('organizador.eventos.index', ['vista' => $clave, 'buscar' => $busqueda, 'orden' => $orden]) }}" @if($vista === $clave) aria-current="page" @endif>{{ $etiqueta }}</a>
                @endforeach
            </nav>
            <form class="org-filters" action="{{ route('organizador.eventos.index') }}" method="GET">
                <input type="hidden" name="vista" value="{{ $vista }}">
                <label class="org-search"><x-icono nombre="search" :tamano="18" /><input type="search" name="buscar" value="{{ $busqueda }}" maxlength="120" placeholder="Buscar evento" aria-label="Buscar por evento o lugar"></label>
                <select name="orden" aria-label="Ordenar eventos"><option value="recientes" @selected($orden === 'recientes')>Fecha más reciente</option><option value="proximos" @selected($orden === 'proximos')>Fecha más próxima</option><option value="titulo" @selected($orden === 'titulo')>Nombre A–Z</option></select>
                <button class="btn btn-secondary" type="submit">Filtrar</button>
            </form>
        </div>
        @forelse($eventos as $evento)
            <article class="org-event">
                <img src="{{ asset($evento->imagen_principal ?: ($evento->categoria === 'partido' ? 'imagenes/prototipo/event-football.jpg' : 'imagenes/prototipo/event-concert.jpg')) }}" alt="" width="118" height="76" loading="lazy">
                <div class="org-event-info"><span class="org-tag {{ $evento->estado }}">{{ ucfirst($evento->estado) }}</span><h2><a href="{{ route('organizador.eventos.edit', $evento) }}">{{ $evento->titulo }}</a></h2><p><time datetime="{{ $evento->fecha_evento->toIso8601String() }}">{{ $evento->fecha_evento->format('d/m/Y · H:i') }}</time> · {{ $evento->lugar }}</p></div>
                <div class="org-event-stat"><small>ZONAS</small><strong>{{ $evento->zonas_count }}</strong></div>
                <div class="org-event-stat"><small>AFORO TOTAL</small><strong>{{ number_format($evento->aforo_total) }}</strong></div>
                <div class="org-event-actions">
                    <a class="btn btn-secondary" href="{{ route('organizador.eventos.edit', $evento) }}" aria-label="Editar {{ $evento->titulo }}">Editar</a>
                    @if($evento->estado === 'publicado')<a class="org-action" href="{{ route('eventos.show', $evento) }}" aria-label="Ver {{ $evento->titulo }} en la cartelera">Ver</a>@endif
                    @if($evento->estado !== 'retirado')
                        <form action="{{ route('organizador.eventos.destroy', $evento) }}" method="POST" data-retirar-evento data-titulo="{{ $evento->titulo }}">@csrf @method('DELETE')<button class="org-action org-danger" type="submit" aria-label="Retirar {{ $evento->titulo }}">Retirar</button></form>
                    @endif
                </div>
            </article>
        @empty
            <div class="org-empty"><x-icono nombre="calendar-days" :tamano="40" /><h2>{{ $metricas['total'] ? 'No encontramos eventos' : 'Tu próxima experiencia empieza aquí' }}</h2><p>{{ $metricas['total'] ? 'Prueba otra búsqueda o revisa los filtros.' : 'Crea tu primer evento y configura sus zonas y tarifas.' }}</p><a class="btn btn-primary" href="{{ $metricas['total'] ? route('organizador.eventos.index') : route('organizador.eventos.create') }}">{{ $metricas['total'] ? 'Limpiar filtros' : 'Crear mi primer evento' }}</a></div>
        @endforelse
        {{ $eventos->links('organizador.eventos.paginacion') }}
    </section>
    <dialog class="org-dialog" data-dialogo-retirar aria-labelledby="retirar-titulo">
        <form method="dialog"><button class="org-dialog-close" aria-label="Cerrar" value="cancelar"><x-icono nombre="x" :tamano="20" /></button><span class="eyebrow">Gestionar cartelera</span><h2 id="retirar-titulo">Retirar evento</h2><p>Se dejará de vender <strong data-evento-retirado></strong>. Sus datos y reservas se conservarán.</p><div class="org-form-actions"><button class="btn btn-secondary" value="cancelar" autofocus>Cancelar</button><button class="btn org-btn-danger" value="retirar">Retirar de la venta</button></div></form>
    </dialog>
@endsection
