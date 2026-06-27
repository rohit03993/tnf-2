@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="tnf-pagination">
        <div class="tnf-pagination-mobile">
            @if ($paginator->onFirstPage())
                <span class="tnf-pagination-btn tnf-pagination-btn--disabled">Previous</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="tnf-pagination-btn" rel="prev">Previous</a>
            @endif

            <span class="tnf-pagination-status">
                Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}
            </span>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="tnf-pagination-btn" rel="next">Next</a>
            @else
                <span class="tnf-pagination-btn tnf-pagination-btn--disabled">Next</span>
            @endif
        </div>

        <div class="tnf-pagination-desktop">
            @if ($paginator->onFirstPage())
                <span class="tnf-pagination-btn tnf-pagination-btn--disabled" aria-hidden="true">&lsaquo;</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="tnf-pagination-btn" rel="prev" aria-label="Previous page">&lsaquo;</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="tnf-pagination-ellipsis">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="tnf-pagination-btn tnf-pagination-btn--active" aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="tnf-pagination-btn">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="tnf-pagination-btn" rel="next" aria-label="Next page">&rsaquo;</a>
            @else
                <span class="tnf-pagination-btn tnf-pagination-btn--disabled" aria-hidden="true">&rsaquo;</span>
            @endif
        </div>
    </nav>
@endif
