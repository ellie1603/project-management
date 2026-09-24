@props(['disabled' => false, 'icon' => 'lock'])

<div x-data="{ show: false }" class="relative">
    @if ($icon)
        <i data-lucide="{{ $icon }}" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
    @endif
    <input
        :type="show ? 'text' : 'password'"
        @disabled($disabled)
        {{ $attributes->merge(['class' => 'w-full rounded-lg border-slate-300 shadow-sm transition-all duration-150 ease-smooth focus:border-brand-500 focus:ring-2 focus:ring-brand-100 '.($icon ? 'pl-10 pr-10' : 'pr-10')]) }}
    >
    <button
        type="button"
        @click="show = ! show"
        tabindex="-1"
        class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 transition-colors duration-150 hover:text-slate-600"
        :aria-label="show ? 'Hide password' : 'Show password'"
    >
        <i data-lucide="eye" x-show="! show" class="h-4 w-4"></i>
        <i data-lucide="eye-off" x-show="show" style="display: none;" class="h-4 w-4"></i>
    </button>
</div>
