@if ($paginator->hasPages())
@once
<style>
    .crm-pagination-nav{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:18px;font-family:var(--font-primary,inherit)}
    .crm-page-btn,.crm-page-num{min-width:40px;min-height:40px;display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:8px 11px;border:1px solid var(--line,#e2e8f0);border-radius:10px;background:var(--card,#fff);color:var(--dark,#1e293b);font-size:12px;font-weight:800;text-decoration:none}
    .crm-page-btn:hover,.crm-page-num:hover{border-color:#dc2637;color:#dc2637}.crm-page-btn.disabled{opacity:.45;cursor:not-allowed}.crm-page-numbers{display:flex;align-items:center;justify-content:center;gap:5px;flex-wrap:wrap}.crm-page-num.active{border-color:#dc2637;background:#dc2637;color:#fff}.crm-page-dots{padding:0 4px;color:var(--muted,#64748b)}
    @media(max-width:560px){.crm-pagination-nav{align-items:stretch;flex-wrap:wrap}.crm-page-numbers{order:3;width:100%}.crm-page-btn{flex:1}.crm-page-num{min-width:38px;min-height:38px}}
</style>
@endonce
<nav role="navigation" aria-label="{{ __('pagination.navigation') }}" class="crm-pagination-nav">
    {{-- Previous Page Link --}}
    @if ($paginator->onFirstPage())
        <span class="crm-page-btn disabled" aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
            <i class="bi bi-chevron-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}" aria-hidden="true"></i>
            <span>{{ __('crm.previous') }}</span>
        </span>
    @else
        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="crm-page-btn" aria-label="{{ __('pagination.previous') }}">
            <i class="bi bi-chevron-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}" aria-hidden="true"></i>
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
            <i class="bi bi-chevron-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}" aria-hidden="true"></i>
        </a>
    @else
        <span class="crm-page-btn disabled" aria-disabled="true" aria-label="{{ __('pagination.next') }}">
            <span>{{ __('crm.next') }}</span>
            <i class="bi bi-chevron-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}" aria-hidden="true"></i>
        </span>
    @endif
</nav>
@endif
