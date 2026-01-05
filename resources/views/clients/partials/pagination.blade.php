@if ($paginator->hasPages())
    <nav class="blog-pagination" role="navigation" aria-label="Pagination Navigation">
        <ul class="blog-pagination-list">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <li class="blog-pagination-item blog-pagination-item-disabled">
                    <span class="blog-pagination-link">‹ Trước</span>
                </li>
            @else
                <li class="blog-pagination-item">
                    <a class="blog-pagination-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">‹ Trước</a>
                </li>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <li class="blog-pagination-item blog-pagination-item-disabled">
                        <span class="blog-pagination-link">{{ $element }}</span>
                    </li>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="blog-pagination-item blog-pagination-item-active">
                                <span class="blog-pagination-link">{{ $page }}</span>
                            </li>
                        @else
                            <li class="blog-pagination-item">
                                <a class="blog-pagination-link" href="{{ $url }}">{{ $page }}</a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li class="blog-pagination-item">
                    <a class="blog-pagination-link" href="{{ $paginator->nextPageUrl() }}" rel="next">Sau ›</a>
                </li>
            @else
                <li class="blog-pagination-item blog-pagination-item-disabled">
                    <span class="blog-pagination-link">Sau ›</span>
                </li>
            @endif
        </ul>
    </nav>
@endif

