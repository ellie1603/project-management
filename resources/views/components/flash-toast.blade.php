@props(['message' => null, 'type' => 'success'])

@php
    $message = $message ?? session('status');
    $tone = match ($type) {
        'error' => ['border-red-200', 'bg-red-50', 'text-red-700', 'alert-circle'],
        default => ['border-emerald-200', 'bg-emerald-50', 'text-emerald-700', 'check-circle-2'],
    };
@endphp

@if ($message)
    <div
        x-data="{ show: true }"
        x-init="setTimeout(() => show = false, 4500)"
        x-show="show"
        x-transition:enter="transition ease-elegant duration-300"
        x-transition:enter-start="opacity-0 -translate-y-2 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 -translate-y-2 scale-95"
        {{ $attributes->merge(['class' => "mb-4 flex items-center gap-2.5 rounded-xl border {$tone[0]} {$tone[1]} {$tone[2]} px-4 py-3 text-sm shadow-soft"]) }}
    >
        <i data-lucide="{{ $tone[3] }}" class="h-4 w-4 shrink-0"></i>
        <span>{{ $message }}</span>
    </div>
@endif
