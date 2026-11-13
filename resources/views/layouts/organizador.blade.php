<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('titulo', 'Organizador - TickEvent')</title>
    <link rel="icon" href="{{ asset('imagenes/prototipo/favicon.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/tickevent.css') }}">
    <link rel="stylesheet" href="{{ asset('css/organizador.css') }}">
    <script src="{{ asset('js/organizador.js') }}" defer></script>
</head>
<body class="te-public te-organizador">
    <a class="org-skip" href="#contenido">Saltar al contenido</a>
    <div class="org-shell">
        <aside class="org-sidebar">
            <a class="logo" href="{{ route('inicio') }}"><span class="logo-mark"><x-icono nombre="ticket" :tamano="22" /></span><strong>TICKEVENT</strong></a>
            <button class="org-menu" type="button" aria-label="Abrir menú" aria-expanded="false" aria-controls="menu-organizador" data-menu-organizador><x-icono nombre="menu" /></button>
            <div id="menu-organizador" class="org-navigation">
                <small>Organizador</small>
                <nav aria-label="Panel del organizador">
                    <a href="{{ route('organizador.eventos.index') }}" @if(request()->routeIs('organizador.eventos.index')) aria-current="page" @endif><x-icono nombre="calendar-days" :tamano="18" /> Mis eventos</a>
                    <a href="{{ route('organizador.eventos.create') }}" @if(request()->routeIs('organizador.eventos.create')) aria-current="page" @endif><span aria-hidden="true">＋</span> Crear evento</a>
                    <a href="{{ route('eventos.index') }}"><x-icono nombre="ticket" :tamano="18" /> Ver cartelera</a>
                    <a href="{{ route('entradas.validar') }}"><x-icono nombre="check" :tamano="18" /> Validar entradas</a>
                </nav>
                <div class="org-user">
                    <span class="org-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                    <div><strong>{{ auth()->user()->name }}</strong><small>Cuenta de organizador</small></div>
                    <form action="{{ route('logout') }}" method="POST">@csrf<button type="submit" aria-label="Cerrar sesión" title="Cerrar sesión"><x-icono nombre="arrow-left" :tamano="18" /></button></form>
                </div>
            </div>
        </aside>
        <div class="org-main">
            <header class="org-top"><strong>@yield('ubicacion', 'Mis eventos')</strong><a href="{{ route('inicio') }}">Ir a TickEvent <span aria-hidden="true">↗</span></a></header>
            <main id="contenido">
                @if(session('estado'))<div class="org-notice" role="status"><x-icono nombre="check" :tamano="20" />{{ session('estado') }}</div>@endif
                @if($errors->any())
                    <div class="org-errors" role="alert" tabindex="-1" data-errores><strong>Revisa los siguientes datos:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                @endif
                @yield('contenido')
            </main>
        </div>
    </div>
</body>
</html>
