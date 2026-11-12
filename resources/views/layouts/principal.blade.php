<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('titulo', 'TickEvent')</title>
    <link rel="stylesheet" href="{{ asset('css/tickevent.css') }}">
</head>
<body>

    <header class="encabezado">
        <div class="encabezado-contenido">

            <a class="logo" href="{{ route('inicio') }}">
                <span>▣</span>TICKEVENT
            </a>

            <nav class="navegacion">
                <a href="{{ route('eventos.index') }}">Eventos</a>
                <a href="{{ route('contacto') }}">Contáctanos</a>

                @guest
                    <a href="{{ route('login') }}">
                        Iniciar sesión
                    </a>

                    <a href="{{ route('registro') }}" class="registro">
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
