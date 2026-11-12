@extends('layouts.principal')

@section('titulo', 'Eventos - Administración TickEvent')

@section('contenido')

<main class="compra-contenedor admin-contenedor">
    <div class="organizador-cabecera">
        <div>
            <span class="etiqueta">PANEL ADMINISTRATIVO</span>
            <h1>Eventos</h1>
            <p>Revisa el catálogo y controla su disponibilidad pública sin eliminar información.</p>
        </div>

        <a href="{{ route('admin.dashboard') }}" class="boton-secundario">Volver al resumen</a>
    </div>

    @if(session('estado'))
        <div class="compra-exito">{{ session('estado') }}</div>
    @endif

    <section class="compra-panel admin-seccion">
        <div class="admin-tabla-contenedor">
            <table class="admin-tabla">
                <thead>
                    <tr>
                        <th>Evento</th>
                        <th>Organizador</th>
                        <th>Fecha</th>
                        <th>Aforo</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($eventos as $evento)
                        <tr>
                            <td><strong>{{ $evento->titulo }}</strong><small>{{ $evento->lugar }}, {{ $evento->ciudad }}</small></td>
                            <td>{{ $evento->organizador->name }}</td>
                            <td>{{ $evento->fecha_evento->format('d/m/Y H:i') }}</td>
                            <td>{{ number_format($evento->aforo_total) }}</td>
                            <td>
                                <form action="{{ route('admin.eventos.estado', $evento) }}" method="POST" class="admin-formulario-linea">
                                    @csrf
                                    @method('PATCH')
                                    <select name="estado" aria-label="Estado de {{ $evento->titulo }}">
                                        @foreach(['borrador', 'publicado', 'retirado'] as $estado)
                                            <option value="{{ $estado }}" @selected($evento->estado === $estado)>
                                                {{ ucfirst($estado) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="submit">Guardar</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</main>

@endsection
