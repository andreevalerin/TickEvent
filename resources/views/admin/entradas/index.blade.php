@extends('layouts.admin')
@section('titulo', 'Entradas - Administración TickEvent')
@section('contenido')
<div class="admin-contenido-seccion">
    <div class="admin-titulo"><div><span class="admin-etiqueta">Panel administrativo</span><h1>Entradas</h1><p>Consulta las entradas emitidas y su estado. La validación operativa corresponde al personal de acceso.</p></div></div>
    <section class="admin-resumen-corto">
        <article><strong>{{ number_format($metricas['total']) }}</strong><span>Total emitidas</span></article>
        <article><strong>{{ number_format($metricas['emitidas']) }}</strong><span>Disponibles</span></article>
        <article><strong>{{ number_format($metricas['usadas']) }}</strong><span>Utilizadas</span></article>
    </section>
    <section class="admin-panel admin-seccion">
        <div class="admin-tabla-contenedor"><table class="admin-tabla"><thead><tr><th>Código</th><th>Evento</th><th>Zona y tarifa</th><th>Comprador</th><th>Estado</th><th>Validación</th></tr></thead><tbody>
            @forelse($entradas as $entrada)
                <tr><td><strong>{{ $entrada->codigo }}</strong><small>DNI {{ $entrada->dni }}</small></td><td><strong>{{ $entrada->reserva->evento->titulo }}</strong><small>{{ $entrada->reserva->evento->fecha_evento->format('d/m/Y H:i') }}</small></td><td>{{ $entrada->tarifa->zona->nombre }}<small>{{ $entrada->tarifa->nombre }}</small></td><td>{{ $entrada->reserva->usuario->name }}<small>{{ $entrada->reserva->usuario->email }}</small></td><td><span class="evento-estado {{ $entrada->estado === 'usada' ? 'retirado' : 'publicado' }}">{{ strtoupper($entrada->estado) }}</span></td><td>@if($entrada->usada_en){{ $entrada->usada_en->format('d/m/Y H:i') }}<small>{{ $entrada->validador?->name }}</small>@else<span class="admin-texto-suave">Sin uso</span>@endif</td></tr>
            @empty
                <tr><td colspan="6" class="admin-vacio">No existen entradas emitidas.</td></tr>
            @endforelse
        </tbody></table></div>
        {{ $entradas->links() }}
    </section>
</div>
@endsection
