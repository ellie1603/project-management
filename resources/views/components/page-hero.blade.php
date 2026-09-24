@props(['eyebrow' => null, 'title', 'subtitle' => null])

<div {{ $attributes->merge(['class' => 'palette-fixed relative overflow-hidden rounded-3xl bg-slate-950 ring-1 ring-white/10 px-6 py-7 shadow-premium sm:px-8 sm:py-9']) }}>
    <div class="pointer-events-none absolute inset-0">
        <div class="absolute -left-20 -top-28 h-80 w-80 animate-drift rounded-full bg-white/10 blur-3xl"></div>
        <div class="absolute -bottom-32 right-10 h-72 w-72 animate-drift-slow rounded-full bg-white/5 blur-3xl"></div>
        <div class="absolute inset-0 [background-image:linear-gradient(to_right,theme(colors.white/0.05)_1px,transparent_1px),linear-gradient(to_bottom,theme(colors.white/0.05)_1px,transparent_1px)] [background-size:56px_56px] [mask-image:radial-gradient(ellipse_75%_70%_at_20%_10%,black_10%,transparent_75%)]"></div>
    </div>

    <div class="relative flex flex-col gap-7 lg:flex-row lg:items-end lg:justify-between lg:gap-10">
        <div class="min-w-0">
            @if ($eyebrow)
                <p class="text-[11px] font-semibold uppercase tracking-[0.3em] text-brand-300">{{ $eyebrow }}</p>
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
