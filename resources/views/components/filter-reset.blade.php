{{-- Filters apply automatically; this clears every visible field in the surrounding filter form. --}}
<button
    type="button"
    data-filter-reset
    {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 shadow-sm transition-colors duration-150 hover:bg-slate-50 hover:text-slate-900']) }}
>
    <i data-lucide="rotate-ccw" class="h-4 w-4"></i>
    Reset
</button>
