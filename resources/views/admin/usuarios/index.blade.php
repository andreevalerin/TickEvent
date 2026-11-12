@extends('layouts.principal')

@section('titulo', 'Usuarios - Administración TickEvent')

@section('contenido')

<main class="compra-contenedor admin-contenedor">
    <div class="organizador-cabecera">
        <div>
            <span class="etiqueta">PANEL ADMINISTRATIVO</span>
            <h1>Usuarios y permisos</h1>
            <p>Asigna organizadores y personal de acceso desde una cuenta administradora.</p>
        </div>

        <a href="{{ route('admin.dashboard') }}" class="boton-secundario">Volver al resumen</a>
    </div>

    @if(session('estado'))
        <div class="compra-exito">{{ session('estado') }}</div>
    @endif

    @if($errors->any())
        <div class="compra-error">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <section class="compra-panel admin-seccion">
        <div class="admin-tabla-contenedor">
            <table class="admin-tabla">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Correo</th>
                        <th>Alta</th>
                        <th>Rol y asignaciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($usuarios as $usuario)
                        <tr>
                            <td><strong>{{ $usuario->name }}</strong></td>
                            <td>{{ $usuario->email }}</td>
                            <td>{{ $usuario->created_at->format('d/m/Y') }}</td>
                            <td>
                                @if($usuario->rol === 'administrador')
                                    <span class="evento-estado publicado">ADMINISTRADOR</span>
                                @else
                                    <form action="{{ route('admin.usuarios.rol', $usuario) }}" method="POST" class="admin-formulario-linea">
                                        @csrf
                                        @method('PATCH')
                                        <select name="rol" aria-label="Rol de {{ $usuario->name }}">
                                            @foreach(['cliente', 'organizador', 'personal_acceso'] as $rol)
                                                <option value="{{ $rol }}" @selected($usuario->rol === $rol)>
                                                    {{ $rol === 'personal_acceso' ? 'Personal de acceso' : ucfirst($rol) }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <button type="submit">Guardar rol</button>
                                    </form>

                                    @if($usuario->rol === 'personal_acceso')
                                        <form action="{{ route('admin.usuarios.eventos-acceso', $usuario) }}" method="POST" class="admin-asignaciones">
                                            @csrf
                                            @method('PUT')
                                            <span>Eventos autorizados</span>
                                            <div>
                                                @forelse($eventos as $evento)
                                                    <label>
                                                        <input
                                                            type="checkbox"
                                                            name="eventos[]"
                                                            value="{{ $evento->id }}"
                                                            @checked($usuario->eventosAsignadosAcceso->contains($evento))
                                                        >
                                                        {{ $evento->titulo }}
                                                    </label>
                                                @empty
                                                    <small>No hay eventos disponibles.</small>
                                                @endforelse
                                            </div>
                                            <button type="submit">Guardar eventos</button>
                                        </form>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</main>

@endsection
