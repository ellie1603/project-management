@props(['eyebrow' => null, 'title', 'subtitle' => null])

<div {{ $attributes->merge(['class' => 'relative overflow-hidden rounded-3xl glass px-6 py-7 sm:px-8 sm:py-8']) }}>
    <div class="relative flex flex-col gap-7 lg:flex-row lg:items-end lg:justify-between lg:gap-10">
        <div class="min-w-0">
            @if ($eyebrow)
                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-brand-600">{{ $eyebrow }}</p>
            @endif
            <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight text-slate-900 sm:text-[2rem]">{{ $title }}</h1>
            @if ($subtitle)
                <p class="mt-2 max-w-xl text-sm leading-relaxed text-slate-500">{{ $subtitle }}</p>
            @endif
        </div>

        @isset($aside)
            <div class="shrink-0">{{ $aside }}</div>
        @endisset
    </div>

    @isset($footer)
        <div class="relative mt-7 border-t border-slate-100 pt-6">{{ $footer }}</div>
    @endisset
</div>
