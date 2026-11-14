<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('titulo', 'Organizador - TickEvent')</title>
    <link rel="stylesheet" href="{{ asset('css/tickevent.css') }}">
</head>
<body class="admin-pagina organizador-pagina">
    <header class="admin-encabezado">
        <a class="admin-logo" href="{{ route('organizador.eventos.index') }}" aria-label="TickEvent organizador">
            <img src="{{ asset('imagenes/logo-tickevent.png') }}" alt="TickEvent">
        </a>
        <div class="admin-cuenta">
            <span class="admin-avatar organizador-avatar" aria-hidden="true">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</span>
            <span><strong>{{ auth()->user()->name }}</strong><small>Organizador</small></span>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="admin-salir">Cerrar sesión</button>
            </form>
        </div>
    </header>
    <button type="button" class="admin-menu-control" aria-expanded="false" aria-controls="organizador-sidebar">Menú del organizador</button>
    <div class="admin-estructura">
        @include('partials.organizador-navegacion')
        <main class="admin-principal organizador-principal">@yield('contenido')</main>
    </div>
    <script>
        const panelControl = document.querySelector('.admin-menu-control');
        const panelSidebar = document.getElementById('organizador-sidebar');
        panelControl.addEventListener('click', () => {
            const abierto = panelControl.getAttribute('aria-expanded') === 'true';
            panelControl.setAttribute('aria-expanded', String(!abierto));
            panelSidebar.classList.toggle('abierto', !abierto);
        });
    </script>
</body>
</html>
