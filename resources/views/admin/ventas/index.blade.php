@extends('layouts.admin')
@section('titulo', 'Ventas - Administración TickEvent')
@section('contenido')
<div class="admin-contenido-seccion">
    <div class="admin-titulo"><div><span class="admin-etiqueta">Panel administrativo</span><h1>Ventas</h1><p>Reporte construido exclusivamente con pagos aprobados registrados en TickEvent.</p></div></div>
    <section class="admin-indicadores">
        <article class="admin-indicador azul"><div><strong>{{ number_format($metricas['ventas']) }}</strong><small>Ventas aprobadas</small></div></article>
        <article class="admin-indicador turquesa"><div><strong>{{ number_format($metricas['entradas']) }}</strong><small>Entradas vendidas</small></div></article>
        <article class="admin-indicador violeta"><div><strong>S/ {{ number_format($metricas['ingresos_centimos'] / 100, 2) }}</strong><small>Ingresos totales</small></div></article>
        <article class="admin-indicador arena"><div><strong>S/ {{ number_format($metricas['promedio_centimos'] / 100, 2) }}</strong><small>Venta promedio</small></div></article>
    </section>
    <section class="admin-panel admin-seccion">
        <div class="admin-panel-cabecera"><div><h2>Detalle de ventas</h2><p>No se incluyen pagos pendientes, rechazados o simulados.</p></div></div>
        <div class="admin-tabla-contenedor"><table class="admin-tabla"><thead><tr><th>Fecha</th><th>Evento</th><th>Cliente</th><th>Entradas</th><th>Proveedor</th><th>Importe</th></tr></thead><tbody>
            @forelse($pagos as $pago)
                <tr><td>{{ ($pago->procesado_en ?? $pago->updated_at)->format('d/m/Y H:i') }}</td><td><strong>{{ $pago->reserva->evento->titulo }}</strong></td><td>{{ $pago->reserva->usuario->name }}<small>{{ $pago->reserva->usuario->email }}</small></td><td>{{ $pago->reserva->entradas->count() }}</td><td>{{ ucfirst($pago->proveedor) }}</td><td><strong>{{ $pago->moneda }} {{ number_format($pago->monto_centimos / 100, 2) }}</strong></td></tr>
            @empty
                <tr><td colspan="6" class="admin-vacio">No existen ventas aprobadas.</td></tr>
            @endforelse
        </tbody></table></div>
        {{ $pagos->links() }}
    </section>
</div>
@endsection
