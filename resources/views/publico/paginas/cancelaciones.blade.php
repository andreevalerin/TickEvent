@extends('layouts.principal')
@section('titulo', 'Política de cancelaciones - TickEvent')
@section('contenido')
<main class="pagina-informativa">
    <header class="informativa-cabecera"><span class="etiqueta">POLÍTICAS TICKEVENT</span><h1>Cancelaciones</h1><p>Reglas aplicadas actualmente por el proceso de reserva.</p></header>
    <section class="informativa-documento">
        <h2>Reservas activas</h2><p>Cada reserva mantiene las entradas seleccionadas durante 15 minutos. Mientras continúe activa, el cliente puede cancelarla desde su resumen.</p>
        <h2>Liberación de disponibilidad</h2><p>Una reserva cancelada o vencida deja de ocupar la disponibilidad de la zona y del evento.</p>
        <h2>Compras confirmadas</h2><p>La cancelación de una reserva no modifica pagos ya aprobados ni entradas emitidas. TickEvent no presenta una compra como cancelada sin una comprobación real del proceso correspondiente.</p>
        <h2>Condiciones del evento</h2><p>Cada entrada corresponde a un asistente y a la zona adquirida. No se asignan asientos numerados y el límite acumulado es de seis entradas por cliente y evento.</p>
    </section>
</main>
@endsection
