@extends('layouts.principal')
@section('titulo', 'Política de cambios de fecha - TickEvent')
@section('contenido')
<main class="pagina-informativa">
    <header class="informativa-cabecera"><span class="etiqueta">POLÍTICAS TICKEVENT</span><h1>Cambios de fecha</h1><p>Información disponible para eventos publicados.</p></header>
    <section class="informativa-documento">
        <h2>Fecha oficial del evento</h2><p>La fecha y hora vigentes se muestran en la ficha del evento y en las entradas asociadas a la compra.</p>
        <h2>Entradas vinculadas al evento</h2><p>Las entradas se mantienen relacionadas con el evento adquirido. Un cambio de fecha no altera el precio histórico registrado en la reserva confirmada.</p>
        <h2>Seguimiento</h2><p>TickEvent no tiene actualmente un proceso automático para solicitar cambios por fecha. Consulta la ficha publicada del evento para comprobar su información vigente.</p>
        <a href="{{ route('eventos.index') }}" class="boton-secundario">Ver eventos publicados</a>
    </section>
</main>
@endsection
