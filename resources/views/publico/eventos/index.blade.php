@extends('layouts.principal')
@section('titulo', $titulo . ' - TickEvent')
@section('contenido')
    <section class="shell catalog-page">
        <div class="page-title"><div>
            <span class="eyebrow">Encuentra tu próxima experiencia</span>
            <h1>{{ $titulo }}</h1>
            <p>Conciertos y partidos para vivirlos en primera fila.</p>
        </div></div>
        @include('publico.eventos.busqueda')
        @if($errors->any())
            <div class="public-error" role="alert">
                @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            </div>
        @endif
        <div class="section-heading catalog-results">
            <p role="status">{{ $eventos->count() }} {{ $eventos->count() === 1 ? 'evento encontrado' : 'eventos encontrados' }}@if($busqueda !== '') para “{{ $busqueda }}”@endif</p>
            @include('publico.eventos.categorias')
        </div>
        @include('publico.eventos.listado', ['eventos' => $eventos])
    </section>
@endsection
