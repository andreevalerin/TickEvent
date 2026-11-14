@extends('layouts.principal')
@section('titulo', 'Política de reembolsos - TickEvent')
@section('contenido')
<main class="pagina-informativa">
    <header class="informativa-cabecera"><span class="etiqueta">POLÍTICAS TICKEVENT</span><h1>Reembolsos</h1><p>Estado actual del proceso dentro de la plataforma.</p></header>
    <section class="informativa-documento">
        <h2>Sin confirmaciones automáticas</h2><p>TickEvent no dispone actualmente de un flujo automático de reembolsos. Esta página no aprueba solicitudes ni modifica pagos o entradas.</p>
        <h2>Información verificable</h2><p>Los pagos solo se consideran confirmados cuando la pasarela devuelve una respuesta válida. Del mismo modo, ningún reembolso debe mostrarse como realizado sin una verificación equivalente.</p>
        <h2>Consulta del evento</h2><p>Revisa la ficha y los términos del evento correspondiente. Los canales de atención se publicarán cuando existan datos oficiales confirmados.</p>
        <a href="{{ route('eventos.index') }}" class="boton-secundario">Consultar eventos</a>
    </section>
</main>
@endsection
