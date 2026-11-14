@extends('layouts.principal')

@section('titulo', 'Iniciar sesión - TickEvent')

@section('contenido')

<main class="auth-contenedor">
    <section class="auth-presentacion">
        <span class="etiqueta">TU CUENTA TICKEVENT</span>
        <h2>Todo listo para tu próximo evento</h2>
        <p>Consulta tus reservas y entradas desde un solo lugar.</p>

        <div class="auth-beneficios">
            <span>Reservas por 15 minutos</span>
            <span>Entradas asociadas a cada asistente</span>
            <span>Historial disponible en tu cuenta</span>
        </div>
    </section>

    <div class="auth-tarjeta">
        <span class="etiqueta">BIENVENIDO A TICKEVENT</span>

        <h1>Iniciar sesión</h1>
        <p>Ingresa a tu cuenta para continuar con tus compras.</p>

        <form action="{{ route('login.ingresar') }}" method="POST">
            @csrf

            <label for="email">Correo electrónico</label>
            <input
                type="email"
                id="email"
                name="email"
                value="{{ old('email') }}"
                autocomplete="email"
                required
            >
            @error('email')
                <small class="auth-error">{{ $message }}</small>
            @enderror

            <label for="password">Contraseña</label>
            <input
                type="password"
                id="password"
                name="password"
                autocomplete="current-password"
                required
            >
            @error('password')
                <small class="auth-error">{{ $message }}</small>
            @enderror

            <button type="submit" class="auth-boton">
                Iniciar sesión
            </button>
        </form>

        <div class="auth-enlace">
            ¿No tienes una cuenta?
            <a href="{{ route('registro') }}">Regístrate</a>
        </div>
    </div>
</main>

@endsection
