@extends('layouts.principal')
@section('titulo', 'Contacto - TickEvent')
@section('contenido')
<main class="pagina-informativa">
    <header class="informativa-cabecera"><span class="etiqueta">AYUDA TICKEVENT</span><h1>Contáctanos</h1><p>Consulta primero la información registrada en tu cuenta y en la ficha oficial del evento.</p></header>
    <div class="informativa-grid">
        <section class="informativa-tarjeta"><h2>Compras y reservas</h2><p>Las reservas activas, pagos confirmados y entradas emitidas se consultan desde la cuenta con la que realizaste la compra.</p>@auth<a href="{{ route('entradas.index') }}" class="boton-comprar-link">Ver mis entradas</a>@else<a href="{{ route('login') }}" class="boton-comprar-link">Iniciar sesión</a>@endauth</section>
        <section class="informativa-tarjeta"><h2>Información del evento</h2><p>La fecha, el recinto, las zonas disponibles y los términos se muestran en la ficha publicada de cada evento.</p><a href="{{ route('eventos.index') }}" class="boton-secundario">Consultar eventos</a></section>
        <section class="informativa-tarjeta informativa-aviso"><h2>Canales de atención</h2><p>TickEvent todavía no tiene un correo, teléfono o dirección de atención publicados en este proyecto. No mostramos datos que no hayan sido confirmados.</p></section>
    </div>
</main>
@endsection
