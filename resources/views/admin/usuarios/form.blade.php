@extends('layouts.admin')

@section('titulo', ($usuario->exists ? 'Editar' : 'Crear') . ' usuario - TickEvent')

@section('contenido')

<div class="admin-contenido-seccion">

    <a href="{{ route('admin.usuarios.index') }}" class="volver-evento">
        ← Volver a usuarios
    </a>

    <div class="admin-titulo">
        <div>
        <span class="admin-etiqueta">PANEL ADMINISTRATIVO</span>
        <h1>{{ $usuario->exists ? 'Editar usuario' : 'Crear usuario' }}</h1>
        <p>La asignación de organizadores y personal de acceso solo está disponible para administración.</p>
        </div>
    </div>

    @if($errors->any())
        <div class="compra-error">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form
        action="{{ $usuario->exists ? route('admin.usuarios.update', $usuario) : route('admin.usuarios.store') }}"
        method="POST"
        class="organizador-formulario"
    >
        @csrf
        @if($usuario->exists)
            @method('PUT')
        @endif

        <section class="admin-panel admin-formulario-panel">
            <h2>Datos de la cuenta</h2>

            <div class="formulario-grid">
                <label class="campo">
                    <span>Nombre</span>
                    <input type="text" name="name" value="{{ old('name', $usuario->name) }}" maxlength="255" required>
                </label>

                <label class="campo">
                    <span>Correo electrónico</span>
                    <input type="email" name="email" value="{{ old('email', $usuario->email) }}" maxlength="255" required>
                </label>

                <label class="campo">
                    <span>Rol</span>
                    <select name="rol" required>
                        @foreach(['cliente', 'organizador', 'personal_acceso'] as $rol)
                            <option value="{{ $rol }}" @selected(old('rol', $usuario->rol ?: 'cliente') === $rol)>
                                {{ $rol === 'personal_acceso' ? 'Personal de acceso' : ucfirst($rol) }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="campo">
                    <span>{{ $usuario->exists ? 'Nueva contraseña' : 'Contraseña' }}</span>
                    <input type="password" name="password" minlength="8" @required(!$usuario->exists)>
                    @if($usuario->exists)
                        <small>Déjala vacía para conservar la contraseña actual.</small>
                    @endif
                </label>

                <label class="campo">
                    <span>Confirmar contraseña</span>
                    <input type="password" name="password_confirmation" minlength="8" @required(!$usuario->exists)>
                </label>
            </div>

            <div class="organizador-formulario-acciones">
                <a href="{{ route('admin.usuarios.index') }}" class="boton-secundario">Cancelar</a>
                <button type="submit" class="boton-comprar-link">
                    {{ $usuario->exists ? 'Guardar cambios' : 'Crear usuario' }}
                </button>
            </div>
        </section>
    </form>
</div>

@endsection
