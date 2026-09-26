@props(['percent', 'tone' => 'slate'])

@php
    $fillClasses = match ($tone) {
        'brand' => 'bg-brand-600',
        'emerald' => 'bg-emerald-500',
        'amber' => 'bg-amber-400',
        'red' => 'bg-red-500',
        default => 'bg-slate-700',
    };
    $clamped = max(0, min(100, (float) $percent));
@endphp

<div {{ $attributes->merge(['class' => 'h-2 w-full overflow-hidden rounded-full bg-slate-100']) }}>
    <div class="h-full rounded-full {{ $fillClasses }} shadow-sm transition-[width] duration-500 ease-smooth" style="width: {{ $clamped }}%"></div>
</div>
