@extends('layouts.admin')

@section('titulo', 'Eventos - Administración TickEvent')

@section('contenido')

<div class="admin-contenido-seccion">
    <div class="admin-titulo">
        <div>
            <span class="admin-etiqueta">PANEL ADMINISTRATIVO</span>
            <h1>Eventos</h1>
            <p>Revisa el catálogo y controla su disponibilidad pública sin eliminar información.</p>
        </div>

    </div>

    @if(session('estado'))
        <div class="compra-exito">{{ session('estado') }}</div>
    @endif

    <section class="admin-panel admin-seccion">
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
                            <td><div class="admin-evento-tabla"><span>@if($evento->imagen_principal)<img src="{{ asset($evento->imagen_principal) }}" alt="">@endif</span><div><strong>{{ $evento->titulo }}</strong><small>{{ $evento->lugar }}, {{ $evento->ciudad }}</small></div></div></td>
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
</div>

@endsection
