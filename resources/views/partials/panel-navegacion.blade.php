<nav class="panel-navegacion" aria-label="Navegación del panel">
    <a href="{{ route('inicio') }}">Inicio</a>

    @if(auth()->user()->rol === 'administrador')
        <a href="{{ route('admin.dashboard') }}" @class(['activa' => request()->routeIs('admin.dashboard')])>Resumen</a>
        <a href="{{ route('admin.eventos.index') }}" @class(['activa' => request()->routeIs('admin.eventos.*')])>Eventos</a>
        <a href="{{ route('admin.usuarios.index') }}" @class(['activa' => request()->routeIs('admin.usuarios.*')])>Usuarios y permisos</a>
    @endif

    @if(auth()->user()->rol === 'organizador')
        <a href="{{ route('organizador.eventos.index') }}" @class(['activa' => request()->routeIs('organizador.eventos.index', 'organizador.eventos.edit')])>Mis eventos</a>
        <a href="{{ route('organizador.eventos.create') }}" @class(['activa' => request()->routeIs('organizador.eventos.create')])>Crear evento</a>
        <a href="{{ route('organizador.zonas.index') }}" @class(['activa' => request()->routeIs('organizador.zonas.*')])>Zonas</a>
    @endif

    @if(auth()->user()->rol === 'personal_acceso' && auth()->user()->eventosAsignadosAcceso()->exists())
        <a href="{{ route('entradas.validar') }}" @class(['activa' => request()->routeIs('entradas.validar*')])>Validar entradas</a>
    @endif
</nav>
