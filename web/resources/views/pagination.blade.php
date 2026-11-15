@php
    $elements = [];
    if ($paginator->lastPage() > 1) {
        $window = range(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2));
        if (min($window) > 1) {
            $elements[] = [1 => $paginator->url(1)];
            if (min($window) > 2) {
                $elements[] = '…';
            }
        }
        $elements[] = array_combine($window, array_map(fn ($p) => $paginator->url($p), $window));
        if (max($window) < $paginator->lastPage()) {
            if (max($window) < $paginator->lastPage() - 1) {
                $elements[] = '…';
            }
            $elements[] = [$paginator->lastPage() => $paginator->url($paginator->lastPage())];
        }
    }
@endphp
@if ($paginator->hasPages())
    <nav class="pagination">
        @if ($paginator->onFirstPage())
            <span>‹</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}">‹</a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span>{{ $element }}</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="active">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}">›</a>
        @else
            <span>›</span>
        @endif
    </nav>
@endif
