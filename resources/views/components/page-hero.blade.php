@props(['eyebrow' => null, 'title', 'subtitle' => null])

<div {{ $attributes->merge(['class' => 'palette-fixed hero-brand relative overflow-hidden rounded-3xl bg-brand-600 ring-1 ring-white/10 px-6 py-7 shadow-premium sm:px-8 sm:py-9']) }}>
    <div class="pointer-events-none absolute inset-0">
    </div>

    <div class="relative flex flex-col gap-7 lg:flex-row lg:items-end lg:justify-between lg:gap-10">
        <div class="min-w-0">
            @if ($eyebrow)
                <p class="text-[11px] font-semibold uppercase tracking-[0.3em] text-highlight-400">{{ $eyebrow }}</p>
            @endif
            <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight text-white sm:text-[2rem]">{{ $title }}</h1>
            @if ($subtitle)
                <p class="mt-2 max-w-xl text-sm leading-relaxed text-slate-400">{{ $subtitle }}</p>
            @endif
        </div>

        @isset($aside)
            <div class="shrink-0">{{ $aside }}</div>
        @endisset
    </div>

    @isset($footer)
        <div class="relative mt-7 border-t border-white/10 pt-6">{{ $footer }}</div>
    @endisset
</div>
