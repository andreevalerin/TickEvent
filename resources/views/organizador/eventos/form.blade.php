@extends('layouts.organizador')
@section('titulo', ($evento->exists ? 'Editar' : 'Crear') . ' evento - TickEvent')
@section('ubicacion', $evento->exists ? 'Editar evento' : 'Crear evento')
@section('contenido')
    @php
        $zonasIniciales = old('zonas', $zonasFormulario) ?: [['nombre' => '', 'tarifas' => [['nombre' => 'Regular']]]];
    @endphp
    <a class="org-back" href="{{ route('organizador.eventos.index') }}"><x-icono nombre="arrow-left" :tamano="18" /> Volver a mis eventos</a>
    <div class="org-page-title"><div><span class="eyebrow">Tu próxima experiencia</span><h1>{{ $evento->exists ? 'Editar evento' : 'Crear nuevo evento' }}</h1><p>Prepara tu cartelera y elige cuándo publicarla.</p></div></div>
    <form action="{{ $evento->exists ? route('organizador.eventos.update', $evento) : route('organizador.eventos.store') }}" method="POST" enctype="multipart/form-data" data-editor-evento class="org-card">
        @csrf
        @if($evento->exists) @method('PUT') @endif
        <div class="org-editor-grid">
            <div class="org-images">
                @include('organizador.eventos.imagen', ['campo' => 'imagen_principal', 'etiqueta' => 'Portada del evento'])
                @include('organizador.eventos.imagen', ['campo' => 'imagen_plano', 'etiqueta' => 'Plano de zonas'])
                @if($errors->any())<p class="org-help">Si elegiste imágenes nuevas, vuelve a seleccionarlas antes de guardar.</p>@endif
            </div>
            <section aria-labelledby="datos-evento"><h2 id="datos-evento">Datos del evento</h2><div class="org-fields">
                <label class="org-field org-wide"><span>Nombre del evento</span><input type="text" name="titulo" value="{{ old('titulo', $evento->titulo) }}" maxlength="150" required placeholder="Ej. Robbie Williams – Britpop World Tour"></label>
                <label class="org-field"><span>Categoría</span><select name="categoria" required><option value="concierto" @selected(old('categoria', $evento->categoria) === 'concierto')>Conciertos</option><option value="partido" @selected(old('categoria', $evento->categoria) === 'partido')>Deportes</option></select></label>
                <label class="org-field"><span>Estado de publicación</span><select name="estado" required><option value="borrador" @selected(old('estado', $evento->estado ?: 'borrador') === 'borrador')>Borrador</option><option value="publicado" @selected(old('estado', $evento->estado) === 'publicado')>Publicado</option>@if($evento->estado === 'retirado')<option value="retirado" @selected(old('estado', $evento->estado) === 'retirado')>Retirado</option>@endif</select></label>
                <label class="org-field org-wide"><span>Descripción <small>(opcional)</small></span><textarea name="descripcion" rows="3" maxlength="3000">{{ old('descripcion', $evento->descripcion) }}</textarea></label>
                <label class="org-field"><span>Fecha y hora</span><input type="datetime-local" name="fecha_evento" value="{{ old('fecha_evento', $evento->fecha_evento?->format('Y-m-d\TH:i')) }}" required></label>
                <label class="org-field"><span>Recinto</span><input type="text" name="lugar" value="{{ old('lugar', $evento->lugar) }}" maxlength="150" required placeholder="Ej. Estadio San Marcos"></label>
                <label class="org-field"><span>Dirección <small>(opcional)</small></span><input type="text" name="direccion" value="{{ old('direccion', $evento->direccion) }}" maxlength="180"></label>
                <label class="org-field"><span>Ciudad</span><input type="text" name="ciudad" value="{{ old('ciudad', $evento->ciudad ?: 'Lima') }}" maxlength="80" required></label>
            </div></section>
        </div>
        <section class="org-zones" aria-labelledby="zonas-titulo">
            <div class="org-section-heading"><div><h2 id="zonas-titulo">Zonas y tarifas</h2><p>Todas las tarifas de una zona comparten su capacidad.</p></div><button class="btn btn-secondary" type="button" data-agregar-zona>＋ Agregar zona</button></div>
            <p class="org-capacity" aria-live="polite">Aforo total configurado: <strong data-aforo>{{ number_format(array_sum(array_column($zonasIniciales, 'capacidad'))) }}</strong> entradas</p>
            <div data-zonas>@foreach($zonasIniciales as $indiceZona => $zona) @include('organizador.eventos.zona') @endforeach</div>
            <p class="org-help" data-editor-ayuda role="status">Desactiva una zona o tarifa para detener su venta. Las reservas existentes se conservan.</p>
        </section>
        <div class="org-form-actions"><p>Los borradores no aparecen en la cartelera.</p><a class="btn btn-secondary" href="{{ route('organizador.eventos.index') }}">Cancelar</a><button type="submit" class="btn btn-primary">{{ $evento->exists ? 'Guardar cambios' : 'Crear evento' }}</button></div>
    </form>
    <template id="plantilla-zona">@include('organizador.eventos.zona', ['indiceZona' => '__ZONA__', 'zona' => ['nombre' => '', 'tarifas' => [['nombre' => 'Regular']]]])</template>
    <template id="plantilla-tarifa">@include('organizador.eventos.tarifa', ['indiceZona' => '__ZONA__', 'indiceTarifa' => '__TARIFA__', 'tarifa' => ['nombre' => '']])</template>
@endsection
