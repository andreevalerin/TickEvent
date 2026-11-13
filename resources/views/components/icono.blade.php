@props(['nombre', 'tamano' => 24])
<svg {{ $attributes->class(['icono']) }} width="{{ $tamano }}" height="{{ $tamano }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    <use href="{{ asset('imagenes/iconos.svg') }}#{{ $nombre }}"></use>
</svg>
