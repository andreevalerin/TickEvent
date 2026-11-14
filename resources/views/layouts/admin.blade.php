<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('titulo', 'Administración - TickEvent')</title>
    <link rel="stylesheet" href="{{ asset('css/tickevent.css') }}">
</head>
<body class="admin-pagina">
    <header class="admin-encabezado">
        <a class="admin-logo" href="{{ route('admin.dashboard') }}" aria-label="TickEvent administración">
            <img src="{{ asset('imagenes/logo-tickevent.png') }}" alt="TickEvent">
        </a>
        <div class="admin-cuenta">
            <span class="admin-avatar" aria-hidden="true">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</span>
            <span><strong>{{ auth()->user()->name }}</strong><small>Administrador</small></span>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="admin-salir">Cerrar sesión</button>
            </form>
        </div>
    </header>
    <button type="button" class="admin-menu-control" aria-expanded="false" aria-controls="admin-sidebar">Menú administrativo</button>
    <div class="admin-estructura">
        @include('partials.admin-navegacion')
        <main class="admin-principal">@yield('contenido')</main>
    </div>
    <script>
        const adminControl = document.querySelector('.admin-menu-control');
        const adminSidebar = document.getElementById('admin-sidebar');
        adminControl.addEventListener('click', () => {
            const abierto = adminControl.getAttribute('aria-expanded') === 'true';
            adminControl.setAttribute('aria-expanded', String(!abierto));
            adminSidebar.classList.toggle('abierto', !abierto);
        });
    </script>
</body>
</html>
