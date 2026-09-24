@props(['status'])

@php
    $classes = match ($status) {
        'Ongoing', 'On Schedule', 'Within Budget', 'Approved' => 'bg-emerald-50 text-emerald-700',
        'Completed' => 'bg-slate-900 text-white',
        'In Progress' => 'bg-brand-50 text-brand-700',
        'On Hold', 'Approaching Deadline', 'Approaching Budget Limit', 'Pending' => 'bg-amber-50 text-amber-700',
        'Delayed', 'Budget Exceeded', 'Cancelled', 'Rejected' => 'bg-red-50 text-red-700',
        default => 'bg-slate-100 text-slate-600',
    };

    $urgent = in_array($status, ['Delayed', 'Budget Exceeded'], true);
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11.5px] font-semibold tracking-wide transition-colors duration-150 {$classes}"]) }}>
    <span class="relative flex h-1.5 w-1.5 shrink-0">
        @if ($urgent)
            <span class="absolute inline-flex h-full w-full animate-soft-pulse rounded-full bg-current opacity-60"></span>
        @endif
        <span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-current opacity-80"></span>
    </span>
    {{ $status }}
</span>
