@extends('layouts.principal')

@section('titulo', 'Registrarse - TickEvent')

@section('contenido')

<main class="auth-contenedor">
    <section class="auth-presentacion">
        <span class="etiqueta">TU CUENTA TICKEVENT</span>
        <h2>Compra entradas con claridad y sin complicaciones</h2>
        <p>Elige una zona, registra a los asistentes y revisa el total antes de pagar.</p>

        <div class="auth-beneficios">
            <span>Hasta seis entradas por evento</span>
            <span>Precios y cargos visibles</span>
            <span>Entradas nominativas</span>
        </div>
    </section>

    <div class="auth-tarjeta">
        <span class="etiqueta">ÚNETE A TICKEVENT</span>

        <h1>Crear una cuenta</h1>
        <p>Regístrate para comprar entradas para tus eventos favoritos.</p>

        <form action="{{ route('registro.guardar') }}" method="POST">
            @csrf

            <label for="name">Nombre completo</label>
            <input
                type="text"
                id="name"
                name="name"
                value="{{ old('name') }}"
                autocomplete="name"
                maxlength="255"
                required
            >
            @error('name')
                <small class="auth-error">{{ $message }}</small>
            @enderror

            <label for="email">Correo electrónico</label>
            <input
                type="email"
                id="email"
                name="email"
                value="{{ old('email') }}"
                autocomplete="email"
                maxlength="255"
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
                minlength="8"
                autocomplete="new-password"
                required
            >
            @error('password')
                <small class="auth-error">{{ $message }}</small>
            @enderror

            <label for="password_confirmation">Confirmar contraseña</label>
            <input
                type="password"
                id="password_confirmation"
                name="password_confirmation"
                minlength="8"
                autocomplete="new-password"
                required
            >

            <button type="submit" class="auth-boton">
                Crear cuenta
            </button>
        </form>

        <div class="auth-enlace">
            ¿Ya tienes una cuenta?
            <a href="{{ route('login') }}">Iniciar sesión</a>
        </div>
    </div>
</main>

@endsection
