@if ($paginator->hasPages())
<nav role="navigation" aria-label="Pagination Navigation" class="crm-pagination-nav">
    {{-- Previous Page Link --}}
    @if ($paginator->onFirstPage())
        <span class="crm-page-btn disabled" aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
            <i class="bi bi-chevron-left rtl:rotate-180" aria-hidden="true"></i>
            <span>{{ __('crm.previous') }}</span>
        </span>
    @else
        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="crm-page-btn" aria-label="{{ __('pagination.previous') }}">
            <i class="bi bi-chevron-left rtl:rotate-180" aria-hidden="true"></i>
            <span>{{ __('crm.previous') }}</span>
        </a>
    @endif

    {{-- Pagination Elements --}}
    <div class="crm-page-numbers">
        @foreach ($elements as $element)
            {{-- "Three Dots" Separator --}}
            @if (is_string($element))
                <span class="crm-page-dots" aria-disabled="true">{{ $element }}</span>
            @endif

            {{-- Array Of Links --}}
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="crm-page-num active" aria-current="page">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="crm-page-num">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach
    </div>

    {{-- Next Page Link --}}
    @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="crm-page-btn" aria-label="{{ __('pagination.next') }}">
            <span>{{ __('crm.next') }}</span>
            <i class="bi bi-chevron-right rtl:rotate-180" aria-hidden="true"></i>
        </a>
    @else
        <span class="crm-page-btn disabled" aria-disabled="true" aria-label="{{ __('pagination.next') }}">
            <span>{{ __('crm.next') }}</span>
            <i class="bi bi-chevron-right rtl:rotate-180" aria-hidden="true"></i>
        </span>
    @endif
</nav>
@endif
