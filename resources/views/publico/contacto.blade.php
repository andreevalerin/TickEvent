@extends('layouts.principal')
@section('titulo', 'Contáctanos - TickEvent')
@section('contenido')
    <section class="shell contact-page">
        <a class="back-link dark" href="{{ route('inicio') }}"><x-icono nombre="arrow-left" :tamano="18" /> Volver al inicio</a>
        <span class="eyebrow">Estamos para ayudarte</span>
        <h1>Contáctanos</h1>
        <p class="contact-intro">Encuentra información para disfrutar tu próxima experiencia.</p>
        <div class="contact-grid">
            <section class="content-card contact-card">
                <span class="contact-icon"><x-icono nombre="mail" :tamano="28" /></span>
                <h2>Atención al cliente</h2>
                <p>Los canales de atención de TickEvent todavía no están publicados.</p>
                <p>Puedes consultar las zonas y tarifas en la página de cada evento y revisar tus entradas desde tu cuenta.</p>
                <a class="btn btn-primary" href="{{ route('entradas.index') }}">Consultar mis entradas</a>
            </section>
            <section class="contact-faq" aria-labelledby="preguntas-titulo">
                <h2 id="preguntas-titulo">Antes de elegir tus entradas</h2>
                <details open><summary>¿Cuántas entradas puedo comprar?</summary><p>Hasta seis entradas acumuladas por cliente y evento, incluyendo compras anteriores.</p></details>
                <details><summary>¿Cuánto tiempo dura una reserva?</summary><p>Las reservas activas duran 15 minutos. Al vencer, las entradas vuelven a estar disponibles.</p></details>
                <details><summary>¿Las entradas tienen asientos numerados?</summary><p>Las entradas permiten el acceso a la zona seleccionada. No se asignan asientos numerados.</p></details>
                <a class="text-link" href="{{ route('eventos.index') }}">Explorar eventos <x-icono nombre="arrow-left" class="arrow-right" :tamano="17" /></a>
            </section>
        </div>
    </section>
@endsection
