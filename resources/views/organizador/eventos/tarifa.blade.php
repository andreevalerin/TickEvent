<div class="org-fare" data-tarifa="{{ $indiceTarifa }}">
    @if(!empty($tarifa['id']))<input type="hidden" name="zonas[{{ $indiceZona }}][tarifas][{{ $indiceTarifa }}][id]" value="{{ $tarifa['id'] }}">@endif
    <label class="org-field"><span>Nombre de tarifa</span><input type="text" name="zonas[{{ $indiceZona }}][tarifas][{{ $indiceTarifa }}][nombre]" value="{{ $tarifa['nombre'] ?? 'Regular' }}" maxlength="60" required></label>
    <label class="org-field"><span>Precio S/</span><input type="number" name="zonas[{{ $indiceZona }}][tarifas][{{ $indiceTarifa }}][precio]" value="{{ $tarifa['precio'] ?? '' }}" min="0.01" max="99999999.99" step="0.01" required></label>
    <label class="org-checkbox"><input type="hidden" name="zonas[{{ $indiceZona }}][tarifas][{{ $indiceTarifa }}][activo]" value="0"><input type="checkbox" name="zonas[{{ $indiceZona }}][tarifas][{{ $indiceTarifa }}][activo]" value="1" @checked((bool) ($tarifa['activo'] ?? true))> Activa</label>
    <button class="org-action org-danger" type="button" data-quitar-tarifa aria-label="Quitar tarifa">Quitar</button>
</div>
