@if ($paginator->hasPages())
    <nav class="flex items-center gap-1" aria-label="ໜ້າ">
        @if ($paginator->onFirstPage())
            <span class="icon-btn-sm opacity-40" aria-disabled="true">{{ icon('chevron-left', 'size-4') }}</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="icon-btn-sm" rel="prev" aria-label="ກ່ອນໜ້າ">{{ icon('chevron-left', 'size-4') }}</a>
        @endif
        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="px-2 text-muted">…</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="inline-flex size-9 items-center justify-center rounded-lg bg-primary text-sm font-semibold text-primary-foreground" aria-current="page">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="icon-btn-sm text-sm font-semibold">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="icon-btn-sm" rel="next" aria-label="ຕໍ່ໄປ">{{ icon('chevron-right', 'size-4') }}</a>
        @else
            <span class="icon-btn-sm opacity-40" aria-disabled="true">{{ icon('chevron-right', 'size-4') }}</span>
        @endif
    </nav>
@endif
