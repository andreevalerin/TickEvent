<div class="org-upload" data-imagen>
    <label for="{{ $campo }}">{{ $etiqueta }}</label>
    <div class="org-preview {{ $campo === 'imagen_plano' ? 'org-preview-map' : '' }}">
        <img src="{{ $evento->$campo ? asset($evento->$campo) : asset('imagenes/prototipo/event-concert.jpg') }}" alt="Vista previa de {{ mb_strtolower($etiqueta) }}" data-preview data-original="{{ $evento->$campo ? asset($evento->$campo) : '' }}" @if(!$evento->$campo) hidden @endif>
        <span data-sin-imagen @if($evento->$campo) hidden @endif><x-icono nombre="{{ $campo === 'imagen_plano' ? 'map-pin' : 'ticket' }}" :tamano="32" />{{ $campo === 'imagen_plano' ? 'Agrega el plano de zonas' : 'Agrega la imagen de tu evento' }}</span>
    </div>
    <input type="file" id="{{ $campo }}" name="{{ $campo }}" accept="image/png,image/jpeg,image/webp" aria-describedby="ayuda-{{ $campo }}">
    <small id="ayuda-{{ $campo }}">JPG, PNG o WebP. Máximo 5 MB.</small>
    <button class="org-action" type="button" data-restaurar-imagen hidden>Cancelar nueva imagen</button>
</div>
