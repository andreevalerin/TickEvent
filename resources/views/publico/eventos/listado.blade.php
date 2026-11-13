<div class="event-grid">
    @forelse($eventos as $evento)
        <article class="event-card">
            <a class="event-image" href="{{ route('eventos.show', $evento) }}" aria-label="Ver {{ $evento->titulo }}">
                <img src="{{ asset($evento->imagen_principal ?: ($evento->categoria === 'partido' ? 'imagenes/prototipo/event-football.jpg' : 'imagenes/prototipo/event-concert.jpg')) }}" alt="{{ $evento->titulo }}" width="600" height="310" loading="lazy">
                <span @class(['category', 'sport' => $evento->categoria === 'partido'])>{{ $evento->categoria === 'partido' ? 'Deportes' : 'Conciertos' }}</span>
            </a>
            <div class="event-body">
                <time class="date" datetime="{{ $evento->fecha_evento->format('Y-m-d\TH:i') }}">
                    <x-icono nombre="calendar-days" :tamano="15" />
                    {{ $evento->fecha_evento->locale('es')->isoFormat('ddd D MMM') }} · {{ $evento->fecha_evento->format('H:i') }}
                </time>
                <h3><a href="{{ route('eventos.show', $evento) }}">{{ $evento->titulo }}</a></h3>
                <p><x-icono nombre="map-pin" :tamano="15" /> {{ $evento->lugar }}, {{ $evento->ciudad }}</p>
                <div class="card-bottom">
                    <span>
                        @if($evento->precio_desde !== null)
                            Desde
                            <strong>S/{{ number_format((float) $evento->precio_desde, fmod((float) $evento->precio_desde, 1) ? 2 : 0) }}</strong>
                        @else
                            <strong class="fare-pending">Tarifas por confirmar</strong>
                        @endif
                    </span>
                    <a class="btn btn-secondary" href="{{ route('eventos.show', $evento) }}">Ver entradas<span class="solo-lectores"> de {{ $evento->titulo }}</span></a>
                </div>
            </div>
        </article>
    @empty
        <div class="empty-state">
            <x-icono nombre="search" :tamano="40" />
            <h3>No encontramos eventos</h3>
            <p>Por el momento no hay eventos disponibles en esta sección.</p>
            <p>Prueba con otro nombre o cambia los filtros.</p>
            <a class="btn btn-secondary" href="{{ route('eventos.index') }}">Limpiar búsqueda</a>
        </div>
    @endforelse
</div>
