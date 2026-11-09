@if ($paginator->hasPages())
<nav class="paginacao" aria-label="Paginação">
    @if ($paginator->onFirstPage())<span class="desativado">‹ Anterior</span>@else<a href="{{ $paginator->previousPageUrl() }}" rel="prev">‹ Anterior</a>@endif
    <span>Página {{ $paginator->currentPage() }} de {{ $paginator->lastPage() }}</span>
    @if ($paginator->hasMorePages())<a href="{{ $paginator->nextPageUrl() }}" rel="next">Próxima ›</a>@else<span class="desativado">Próxima ›</span>@endif
</nav>
@endif
