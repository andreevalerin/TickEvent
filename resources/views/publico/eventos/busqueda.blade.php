@php($categoriaActual = $categoria ?? '')
<form class="shell search-panel" action="{{ route('eventos.index') }}" method="GET" role="search">
    <label class="search-field">
        <x-icono nombre="search" />
        <span><span class="search-label">¿Qué estás buscando?</span>
            <input type="search" name="buscar" maxlength="120" value="{{ $busqueda ?? '' }}" placeholder="Artista, equipo o evento">
        </span>
    </label>
    <label class="search-field category-select">
        <x-icono nombre="list-filter" />
        <span><span class="search-label">Categoría</span>
            <select name="categoria">
                <option value="">Todas las categorías</option>
                <option value="concierto" @selected($categoriaActual === 'concierto')>Conciertos</option>
                <option value="partido" @selected($categoriaActual === 'partido')>Deportes</option>
            </select>
        </span>
    </label>
    <button type="submit" class="btn btn-primary"><x-icono nombre="search" :tamano="18" /> Buscar</button>
</form>
