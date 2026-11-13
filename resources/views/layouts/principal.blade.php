<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('titulo', 'TickEvent')</title>
    <meta name="description" content="@yield('descripcion', 'Conciertos y partidos en Perú. Encuentra tu próximo evento en TickEvent.')">
    <link rel="icon" href="{{ asset('imagenes/prototipo/favicon.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/tickevent.css') }}">
    <script src="{{ asset('js/tickevent.js') }}" defer></script>
</head>
@php($esPublico = request()->routeIs('inicio', 'eventos.index', 'conciertos.index', 'partidos.index', 'eventos.show', 'contacto'))
<body @class(['te-public' => $esPublico])>

    <header class="{{ $esPublico ? 'header' : 'encabezado' }}">
        <div class="{{ $esPublico ? 'shell header-inner' : 'encabezado-contenido' }}">

            <a class="logo" href="{{ route('inicio') }}">
                @if($esPublico)
                    <span class="logo-mark"><x-icono nombre="ticket" :tamano="22" /></span><strong>TICKEVENT</strong>
                @else
                    <span>▣</span>TICKEVENT
                @endif
            </a>

            @if($esPublico)
                <button class="mobile-menu" type="button" data-menu aria-controls="navegacion-principal" aria-expanded="false" aria-label="Abrir menú"><x-icono nombre="menu" /></button>
            @endif
            <nav class="{{ $esPublico ? 'nav' : 'navegacion' }}" id="navegacion-principal" aria-label="Navegación principal">
                <a href="{{ route('eventos.index') }}">Eventos</a>
                <a href="{{ route('contacto') }}">Contáctanos</a>

                @guest
                    <a href="{{ route('login') }}">
                        Iniciar sesión
                    </a>

                    <a href="{{ route('registro') }}" class="{{ $esPublico ? 'btn btn-primary' : 'registro' }}">
                        Registrarse
                    </a>
                @else
                    <a href="{{ route('entradas.index') }}">Mis entradas</a>

                    @if(auth()->user()->rol === 'organizador')
                        <a href="{{ route('organizador.eventos.index') }}">Mis eventos</a>
                    @endif

                    @if(auth()->user()->rol === 'administrador')
                        <a href="{{ route('admin.dashboard') }}">Administración</a>
                    @endif

                    @if(
                        auth()->user()->rol === 'administrador'
                        || (auth()->user()->rol === 'organizador' && auth()->user()->eventosOrganizados()->exists())
                        || (auth()->user()->rol === 'personal_acceso' && auth()->user()->eventosAsignadosAcceso()->exists())
                    )
                        <a href="{{ route('entradas.validar') }}">Validar entrada</a>
                    @endif

                    <span>{{ auth()->user()->name }}</span>

                    <form action="{{ route('logout') }}" method="POST" class="form-salir">
                        @csrf

                        <button type="submit" class="boton-salir">
                            Cerrar sesión
                        </button>
                    </form>
                @endguest
            </nav>

        </div>
    </header>

    <main>
        @yield('contenido')
    </main>

</body>
</html>
