@php
    $usuarioCuenta = auth()->user();
    $inicialesCuenta = collect(preg_split('/\s+/', trim($usuarioCuenta->name)))
        ->filter()
        ->take(2)
        ->map(fn ($parte) => mb_strtoupper(mb_substr($parte, 0, 1)))
        ->implode('');
@endphp

<details class="cuenta-menu">
    <summary>
        <span class="cuenta-avatar" aria-hidden="true">{{ $inicialesCuenta }}</span>
        <span class="cuenta-identidad">
            <strong>{{ $usuarioCuenta->name }}</strong>
            <small>{{ $rolEtiqueta }}</small>
        </span>
        <span class="cuenta-flecha" aria-hidden="true">⌄</span>
    </summary>
    <div class="cuenta-menu-panel">
        @isset($perfilUrl)
            <a href="{{ $perfilUrl }}">Mi perfil</a>
        @endisset
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit">Cerrar sesión</button>
        </form>
    </div>
</details>
