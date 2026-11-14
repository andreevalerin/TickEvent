@extends('layouts.organizador')

@section('titulo', 'Zonas - TickEvent')

@section('contenido')

<div class="panel-rol-contenido">

    <div class="organizador-cabecera">
        <div>
            <span class="etiqueta">PANEL DEL ORGANIZADOR</span>
            <h1>Zonas de mis eventos</h1>
            <p>Consulta, registra y edita las zonas asociadas a tus eventos.</p>
        </div>

        <a href="{{ route('organizador.zonas.create') }}" class="boton-comprar-link">Registrar zona</a>
    </div>

    @if(session('estado'))
        <div class="compra-exito">{{ session('estado') }}</div>
    @endif

    <section class="compra-panel">
        @if($zonas->isEmpty())
            <div class="entradas-vacio">
                <h2>Aún no registraste zonas</h2>
                <p>Registra una zona para uno de tus eventos.</p>
            </div>
        @else
            <div class="tabla-responsive">
                <table class="tabla-admin">
                    <thead>
                        <tr>
                            <th>Evento</th>
                            <th>Zona</th>
                            <th>Capacidad</th>
                            <th>Tarifas</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($zonas as $zona)
                            <tr>
                                <td>{{ $zona->evento->titulo }}</td>
                                <td>{{ $zona->nombre }}</td>
                                <td>{{ number_format($zona->capacidad) }}</td>
                                <td>
                                    <strong>{{ $zona->tarifas_count }}</strong>
                                    @foreach($zona->tarifas as $tarifa)
                                        <small>{{ $tarifa->nombre }} · S/ {{ number_format($tarifa->precio, 2) }} · {{ $tarifa->activo ? 'Activa' : 'Inactiva' }}</small>
                                    @endforeach
                                </td>
                                <td>{{ $zona->activo ? 'Activa' : 'Inactiva' }}</td>
                                <td>
                                    <div class="organizador-acciones">
                                        <a href="{{ route('organizador.zonas.edit', $zona) }}" class="boton-secundario">Editar</a>
                                        @if($zona->activo)
                                            <form action="{{ route('organizador.zonas.destroy', $zona) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="boton-retirar">Desactivar</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>

@endsection
