{{-- Replaces Laravel's default: its dark: classes double-flip this app's theme palette and turn the links invisible. --}}
@if ($paginator->hasPages())
    @php
        $base = 'inline-flex h-9 min-w-[2.25rem] items-center justify-center rounded-lg px-3 text-sm font-medium transition-colors duration-150';
        $link = $base.' text-slate-600 ring-1 ring-slate-200 bg-white hover:bg-slate-100 hover:text-slate-900';
        $disabled = $base.' cursor-not-allowed text-slate-300 ring-1 ring-slate-200 bg-white';
        $current = $base.' bg-brand-600 text-white shadow-sm';
    @endphp

    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex flex-col items-center justify-between gap-3 sm:flex-row">
        <p class="text-sm text-slate-500">
            Showing
            <span class="font-semibold text-slate-800">{{ $paginator->firstItem() }}</span>
            to
            <span class="font-semibold text-slate-800">{{ $paginator->lastItem() }}</span>
            of
            <span class="font-semibold text-slate-800">{{ $paginator->total() }}</span>
            results
        </p>

        <div class="flex flex-wrap items-center gap-1.5">
            @if ($paginator->onFirstPage())
                <span class="{{ $disabled }}" aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                    <i data-lucide="chevron-left" class="h-4 w-4"></i>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $link }}" aria-label="{{ __('pagination.previous') }}">
                    <i data-lucide="chevron-left" class="h-4 w-4"></i>
                </a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="{{ $base }} text-slate-400" aria-disabled="true">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="{{ $current }}" aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="{{ $link }} hidden sm:inline-flex" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $link }}" aria-label="{{ __('pagination.next') }}">
                    <i data-lucide="chevron-right" class="h-4 w-4"></i>
                </a>
            @else
                <span class="{{ $disabled }}" aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                    <i data-lucide="chevron-right" class="h-4 w-4"></i>
                </span>
            @endif
        </div>
    </nav>
@endif
