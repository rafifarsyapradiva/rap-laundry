@props([
    'paginator',
])

@if ($paginator && $paginator->hasPages())
    <nav class="pagination-wrap no-print" role="navigation" aria-label="Navigasi Halaman">
        <div class="pagination-info">
            Menampilkan <strong>{{ $paginator->firstItem() }}</strong> &ndash; <strong>{{ $paginator->lastItem() }}</strong> dari <strong>{{ $paginator->total() }}</strong> data
        </div>

        <ul class="pagination-list">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <li class="pagination-item">
                    <span class="pagination-btn" disabled aria-disabled="true" aria-label="Sebelumnya">
                        <x-icon name="arrow-left" size="14" />
                    </span>
                </li>
            @else
                <li class="pagination-item">
                    <a href="{{ $paginator->previousPageUrl() }}" class="pagination-btn" rel="prev" aria-label="Sebelumnya">
                        <x-icon name="arrow-left" size="14" />
                    </a>
                </li>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($paginator->getUrlRange(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $page => $url)
                @if ($page == $paginator->currentPage())
                    <li class="pagination-item">
                        <span class="pagination-btn active" aria-current="page">{{ $page }}</span>
                    </li>
                @else
                    <li class="pagination-item">
                        <a href="{{ $url }}" class="pagination-btn">{{ $page }}</a>
                    </li>
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li class="pagination-item">
                    <a href="{{ $paginator->nextPageUrl() }}" class="pagination-btn" rel="next" aria-label="Berikutnya">
                        <x-icon name="arrow-right" size="14" />
                    </a>
                </li>
            @else
                <li class="pagination-item">
                    <span class="pagination-btn" disabled aria-disabled="true" aria-label="Berikutnya">
                        <x-icon name="arrow-right" size="14" />
                    </span>
                </li>
            @endif
        </ul>
    </nav>
@endif
