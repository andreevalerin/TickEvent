@if($paginator->total())
    <nav class="org-pagination" aria-label="Paginación de eventos">
        <p>{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} de {{ $paginator->total() }} eventos</p>
        @if($paginator->hasPages())<div>
            @if($paginator->onFirstPage())<span aria-disabled="true">Anterior</span>@else<a rel="prev" href="{{ $paginator->previousPageUrl() }}">Anterior</a>@endif
            <span aria-current="page">{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>
            @if($paginator->hasMorePages())<a rel="next" href="{{ $paginator->nextPageUrl() }}">Siguiente</a>@else<span aria-disabled="true">Siguiente</span>@endif
        </div>@endif
    </nav>
@endif
