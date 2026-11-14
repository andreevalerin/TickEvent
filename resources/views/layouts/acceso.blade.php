<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('titulo', 'Control de acceso - TickEvent')</title>
    <link rel="stylesheet" href="{{ asset('css/tickevent.css') }}">
</head>
<body class="admin-pagina acceso-pagina">
    <header class="admin-encabezado acceso-encabezado">
        <a class="admin-logo" href="{{ route('entradas.validar') }}" aria-label="TickEvent control de acceso">
            <img src="{{ asset('imagenes/logo-tickevent.png') }}" alt="TickEvent">
        </a>
        <div class="acceso-cabecera-derecha">
            <span class="acceso-modulo">Validación de entradas</span>
            @include('partials.cuenta-menu', ['rolEtiqueta' => 'Personal de acceso'])
        </div>
    </header>
    <main class="acceso-principal">@yield('contenido')</main>
</body>
</html>
