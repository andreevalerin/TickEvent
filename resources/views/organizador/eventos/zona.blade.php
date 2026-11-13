<article class="org-zone" data-zona="{{ $indiceZona }}">
    <div class="org-zone-heading"><h3 data-titulo-zona>{{ ($zona['nombre'] ?? '') ?: 'Nueva zona' }}</h3><button class="org-action org-danger" type="button" data-quitar-zona>Quitar zona</button></div>
    @if(!empty($zona['id']))<input type="hidden" name="zonas[{{ $indiceZona }}][id]" value="{{ $zona['id'] }}">@endif
    <div class="org-zone-fields">
        <label class="org-field"><span>Nombre de zona</span><input type="text" name="zonas[{{ $indiceZona }}][nombre]" value="{{ $zona['nombre'] ?? '' }}" maxlength="60" required data-nombre-zona></label>
        <label class="org-field"><span>Capacidad total</span><input type="number" name="zonas[{{ $indiceZona }}][capacidad]" value="{{ $zona['capacidad'] ?? '' }}" min="1" max="1000000" required data-capacidad></label>
        <label class="org-checkbox"><input type="hidden" name="zonas[{{ $indiceZona }}][activo]" value="0"><input type="checkbox" name="zonas[{{ $indiceZona }}][activo]" value="1" @checked((bool) ($zona['activo'] ?? true))> Zona activa</label>
    </div>
    <div data-tarifas>@foreach(($zona['tarifas'] ?? [['nombre' => 'Regular']]) as $indiceTarifa => $tarifa) @include('organizador.eventos.tarifa') @endforeach</div>
    <button type="button" class="btn btn-secondary" data-agregar-tarifa>＋ Agregar tarifa</button>
</article>
