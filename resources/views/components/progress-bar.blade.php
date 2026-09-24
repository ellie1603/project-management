@props(['percent', 'tone' => 'slate'])

@php
    $fillClasses = match ($tone) {
        'brand' => 'bg-gradient-to-r from-brand-500 to-brand-700',
        'emerald' => 'bg-gradient-to-r from-emerald-500 to-teal-500',
        'amber' => 'bg-gradient-to-r from-amber-400 to-orange-500',
        'red' => 'bg-gradient-to-r from-red-500 to-rose-500',
        default => 'bg-gradient-to-r from-slate-700 to-slate-900',
    };
    $clamped = max(0, min(100, (float) $percent));
@endphp

<div {{ $attributes->merge(['class' => 'h-2 w-full overflow-hidden rounded-full bg-slate-100']) }}>
    <div class="h-full rounded-full {{ $fillClasses }} shadow-sm transition-[width] duration-500 ease-smooth" style="width: {{ $clamped }}%"></div>
</div>
