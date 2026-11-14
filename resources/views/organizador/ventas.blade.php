@extends('layouts.organizador')
@section('titulo', 'Ventas - TickEvent')
@section('contenido')
<div class="panel-rol-contenido">
    <header class="admin-titulo"><div><span class="admin-etiqueta">Panel del organizador</span><h1>Ventas</h1><p>Ventas aprobadas exclusivamente de tus eventos.</p></div></header>
    <section class="admin-resumen-corto"><article><strong>{{ number_format($pagos->total()) }}</strong><span>Compras aprobadas</span></article><article><strong>{{ number_format($entradasVendidas) }}</strong><span>Entradas vendidas</span></article><article><strong>S/ {{ number_format($ingresosCentimos / 100, 2) }}</strong><span>Ingresos totales</span></article></section>
    <section class="admin-panel admin-seccion"><div class="admin-tabla-contenedor"><table class="admin-tabla"><thead><tr><th>Fecha</th><th>Evento</th><th>Cliente</th><th>Entradas</th><th>Importe</th></tr></thead><tbody>
        @forelse($pagos as $pago)<tr><td>{{ ($pago->procesado_en ?? $pago->updated_at)->format('d/m/Y H:i') }}</td><td><strong>{{ $pago->reserva->evento->titulo }}</strong></td><td>{{ $pago->reserva->usuario->name }}</td><td>{{ $pago->reserva->entradas->count() }}</td><td><strong>{{ $pago->moneda }} {{ number_format($pago->monto_centimos / 100, 2) }}</strong></td></tr>@empty<tr><td colspan="5" class="admin-vacio">No existen ventas aprobadas en tus eventos.</td></tr>@endforelse
    </tbody></table></div>{{ $pagos->links() }}</section>
</div>
@endsection
