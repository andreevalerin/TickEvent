<aside class="admin-sidebar organizador-sidebar" id="organizador-sidebar">
    <nav aria-label="Secciones del organizador">
        <a href="{{ route('organizador.eventos.index') }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 11 9-8 9 8M5 10v10h14V10M9 20v-6h6v6"/></svg><span>Inicio</span></a>
        <a href="{{ route('organizador.eventos.index') }}" @class(['activo' => request()->routeIs('organizador.eventos.*', 'organizador.zonas.*')])><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3v3m10-3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v14H4V6a1 1 0 0 1 1-1Z"/></svg><span>Mis eventos</span></a>
        <a href="{{ route('organizador.ventas') }}" @class(['activo' => request()->routeIs('organizador.ventas')])><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19V5h16v14H4Zm4-4h8M8 9h8"/></svg><span>Ventas</span></a>
        <a href="{{ route('organizador.perfil') }}" @class(['activo' => request()->routeIs('organizador.perfil')])><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 21a8 8 0 0 0-16 0m8-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/></svg><span>Perfil</span></a>
    </nav>
</aside>
