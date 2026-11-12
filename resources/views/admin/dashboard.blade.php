@extends('layouts.principal')

@section('titulo', 'Administración - TickEvent')

@section('contenido')

<main class="compra-contenedor admin-contenedor">
    <div class="organizador-cabecera">
        <div>
            <span class="etiqueta">PANEL ADMINISTRATIVO</span>
            <h1>Resumen de TickEvent</h1>
            <p>Supervisa eventos, ventas, entradas y cuentas desde un solo lugar.</p>
        </div>

        <nav class="admin-atajos">
            <a href="{{ route('admin.eventos.index') }}" class="boton-secundario">Eventos</a>
            <a href="{{ route('admin.usuarios.index') }}" class="boton-comprar-link">Usuarios</a>
        </nav>
    </div>

    <section class="admin-metricas">
        <article><span>Eventos publicados</span><strong>{{ $metricas['eventos_publicados'] }}</strong></article>
        <article><span>Compras confirmadas</span><strong>{{ $metricas['reservas_confirmadas'] }}</strong></article>
        <article><span>Pagos aprobados</span><strong>{{ $metricas['pagos_aprobados'] }}</strong></article>
        <article><span>Entradas emitidas</span><strong>{{ $metricas['entradas_emitidas'] }}</strong></article>
        <article><span>Usuarios</span><strong>{{ $metricas['usuarios'] }}</strong></article>
        <article><span>Organizadores</span><strong>{{ $metricas['organizadores'] }}</strong></article>
    </section>

    <section class="compra-panel admin-seccion">
        <div class="admin-seccion-cabecera">
            <div>
                <span class="etiqueta">ACTIVIDAD</span>
                <h2>Eventos recientes</h2>
            </div>
            <a href="{{ route('admin.eventos.index') }}">Ver todos</a>
        </div>

        @if($eventos->isEmpty())
            <p>No hay eventos registrados.</p>
        @else
            <div class="admin-tabla-contenedor">
                <table class="admin-tabla">
                    <thead>
                        <tr>
                            <th>Evento</th>
                            <th>Organizador</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($eventos as $evento)
                            <tr>
                                <td><strong>{{ $evento->titulo }}</strong><small>{{ $evento->ciudad }}</small></td>
                                <td>{{ $evento->organizador->name }}</td>
                                <td>{{ $evento->fecha_evento->format('d/m/Y H:i') }}</td>
                                <td><span class="evento-estado {{ $evento->estado }}">{{ strtoupper($evento->estado) }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</main>

@endsection
