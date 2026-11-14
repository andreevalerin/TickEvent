<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('titulo', 'TickEvent')</title>
    <link rel="stylesheet" href="{{ asset('css/tickevent.css') }}">
</head>
<body>
    @php
        $rolActual = auth()->user()?->rol;
        $inicioSegunRol = match ($rolActual) {
            'administrador' => route('admin.dashboard'),
            'organizador' => route('organizador.eventos.index'),
            'personal_acceso' => route('entradas.validar'),
            default => route('inicio'),
        };
        $rolEtiqueta = match ($rolActual) {
            'administrador' => 'Administrador',
            'organizador' => 'Organizador',
            'personal_acceso' => 'Personal de acceso',
            default => 'Cliente',
        };
    @endphp

    <header class="encabezado">
        <div class="encabezado-contenido">
            <a class="logo" href="{{ $inicioSegunRol }}">
                <img src="{{ asset('imagenes/logo-tickevent.png') }}" alt="TickEvent" class="logo-imagen">
            </a>

            <button
                type="button"
                class="navegacion-control"
                aria-expanded="false"
                aria-controls="navegacion-principal"
                aria-label="Abrir menú"
            >
                <span></span>
                <span></span>
                <span></span>
            </button>

            <nav class="navegacion" id="navegacion-principal" aria-label="Navegación principal">
                @if(!$rolActual || $rolActual === 'cliente')
                    <a href="{{ route('eventos.index') }}">Eventos</a>
                    <a href="{{ route('conciertos.index') }}">Conciertos</a>
                    <a href="{{ route('partidos.index') }}">Partidos</a>
                    <a href="{{ route('contacto') }}">Contáctanos</a>
                @elseif($rolActual === 'administrador')
                    <a href="{{ route('admin.dashboard') }}">Volver al panel</a>
                @elseif($rolActual === 'organizador')
                    <a href="{{ route('organizador.eventos.index') }}">Volver a mis eventos</a>
                @else
                    <a href="{{ route('entradas.validar') }}">Volver a validación</a>
                @endif

                @guest
                    <a href="{{ route('login') }}">
                        Iniciar sesión
                    </a>

                    <a href="{{ route('registro') }}" class="registro">
                        Registrarse
                    </a>
                @else
                    @if($rolActual === 'cliente')
                        <a href="{{ route('entradas.index') }}">Mis entradas</a>
                    @endif

                    @include('partials.cuenta-menu', ['rolEtiqueta' => $rolEtiqueta])
                @endguest
            </nav>
        </div>
    </header>

    <div class="pagina">
        @yield('contenido')
    </div>

    <footer class="pie-sitio">
        <div class="pie-sitio-contenido">
            <article class="pie-beneficio">
                <span class="pie-beneficio-icono" aria-hidden="true">
                    <svg viewBox="0 0 24 24" focusable="false">
                        <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                        <path d="M3 9h18M7 15h4"></path>
                    </svg>
                </span>
                <div>
                    <strong>Paga a tu manera</strong>
                    <p>Tarjeta habilitada. Yape y Plin: próximamente.</p>
                </div>
            </article>

            <article class="pie-beneficio">
                <span class="pie-beneficio-icono" aria-hidden="true">
                    <svg viewBox="0 0 24 24" focusable="false">
                        <path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM15 14h2v2h-2zM18 14h2v4h-2zM14 18h4v2h-4zM20 20h.01"></path>
                    </svg>
                </span>
                <div>
                    <strong>Tu QR al instante</strong>
                    <p>Código manual activo. QR gráfico: próximamente.</p>
                </div>
            </article>

            <article class="pie-beneficio">
                <span class="pie-beneficio-icono" aria-hidden="true">
                    <svg viewBox="0 0 24 24" focusable="false">
                        <path d="M12 3l7 3v5c0 4.6-2.8 8.2-7 10-4.2-1.8-7-5.4-7-10V6l7-3zM9 12l2 2 4-4"></path>
                    </svg>
                </span>
                <div>
                    <strong>Compra con tranquilidad</strong>
                    <p>La compra se confirma únicamente tras verificar el pago.</p>
                </div>
            </article>
        </div>
    </footer>

    <script>
        const controlNavegacion = document.querySelector('.navegacion-control');
        const navegacionPrincipal = document.getElementById('navegacion-principal');

        controlNavegacion.addEventListener('click', () => {
            const abierto = controlNavegacion.getAttribute('aria-expanded') === 'true';
            controlNavegacion.setAttribute('aria-expanded', String(!abierto));
            controlNavegacion.setAttribute('aria-label', abierto ? 'Abrir menú' : 'Cerrar menú');
            navegacionPrincipal.classList.toggle('abierta', !abierto);
        });
    </script>

</body>
</html>
