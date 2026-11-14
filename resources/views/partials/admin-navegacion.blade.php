<aside class="admin-sidebar" id="admin-sidebar">
    <nav aria-label="Secciones administrativas">
        <a href="{{ route('admin.dashboard') }}" @class(['activo' => request()->routeIs('admin.dashboard')])><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 13h6V4H4v9Zm0 7h6v-5H4v5Zm10 0h6v-9h-6v9Zm0-16v5h6V4h-6Z"/></svg><span>Dashboard</span></a>
        <a href="{{ route('admin.eventos.index') }}" @class(['activo' => request()->routeIs('admin.eventos.*')])><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3v3m10-3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v14H4V6a1 1 0 0 1 1-1Z"/></svg><span>Eventos</span></a>
        <a href="{{ route('admin.entradas.index') }}" @class(['activo' => request()->routeIs('admin.entradas.*')])><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m4 14 10-10 6 6-10 10-6-6Zm9-7 4 4M7 15l2 2"/></svg><span>Entradas</span></a>
        <a href="{{ route('admin.ventas.index') }}" @class(['activo' => request()->routeIs('admin.ventas.*')])><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 20v-7m5 7V8m5 12V4m5 16V10"/></svg><span>Ventas</span></a>
        <a href="{{ route('admin.usuarios.index') }}" @class(['activo' => request()->routeIs('admin.usuarios.*')])><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 20v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2m6.5-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm7.5 1a3 3 0 1 0 0-6m4 15v-2a4 4 0 0 0-3-3.9"/></svg><span>Usuarios</span></a>
    </nav>
</aside>
