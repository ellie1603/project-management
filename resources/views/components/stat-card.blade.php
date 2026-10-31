@props(['label', 'value', 'icon' => null, 'sub' => null])

{{-- Every stat card uses the same ink chip so a row of cards reads as one set; severity lives in the value and sub text. --}}
<div {{ $attributes->merge(['class' => 'group relative min-w-0 overflow-hidden rounded-2xl glass p-4 transition-all duration-300 ease-elegant hover:shadow-elevated sm:p-5']) }}>
    <div class="relative flex items-start justify-between gap-3">
        <p class="min-w-0 truncate text-[11px] font-medium uppercase tracking-wider text-slate-400 sm:text-[12px]">{{ $label }}</p>
        @if ($icon)
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl chip-ink sm:h-9 sm:w-9">
                <i data-lucide="{{ $icon }}" class="h-4 w-4 sm:h-[17px] sm:w-[17px]"></i>
            </div>
        @endif
    </div>

    <p data-count-up class="relative mt-3 truncate font-display text-[22px] font-semibold leading-none tabular-nums tracking-tight text-slate-900 sm:text-[26px]">{{ $value }}</p>

    @if ($sub)
        <p class="relative mt-2 truncate text-xs text-slate-500">{{ $sub }}</p>
    @endif
</div>
