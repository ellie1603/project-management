{{-- Trail back to the page's module (App\Services\Breadcrumbs); nothing on pages opened from the sidebar. Full trail on wider screens, a single "back to parent" link on phones. --}}
@php
    $crumbs = app(\App\Services\Breadcrumbs::class)->for(request());
    $parent = collect($crumbs)->slice(0, -1)->whereNotNull('url')->last();
@endphp

@if ($crumbs)
    <nav aria-label="Breadcrumb" {{ $attributes->merge(['class' => 'mb-5']) }}>
        @if ($parent)
            <a href="{{ $parent['url'] }}" class="inline-flex items-center gap-1 text-sm font-medium text-slate-500 transition-colors duration-150 hover:text-slate-900 sm:hidden">
                <i data-lucide="chevron-left" class="h-4 w-4"></i>
                {{ $parent['label'] }}
            </a>
        @endif

        <ol class="hidden flex-wrap items-center gap-1.5 text-sm sm:flex">
            @foreach ($crumbs as $crumb)
                <li class="flex min-w-0 items-center gap-1.5">
                    @unless ($loop->first)
                        <i data-lucide="chevron-right" class="h-3.5 w-3.5 shrink-0 text-slate-300" aria-hidden="true"></i>
                    @endunless

                    @if ($crumb['url'])
                        <a href="{{ $crumb['url'] }}" class="truncate font-medium text-slate-500 transition-colors duration-150 hover:text-slate-900">{{ $crumb['label'] }}</a>
                    @elseif ($loop->last)
                        <span class="truncate font-semibold text-slate-900" aria-current="page">{{ $crumb['label'] }}</span>
                    @else
                        <span class="truncate text-slate-400">{{ $crumb['label'] }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
