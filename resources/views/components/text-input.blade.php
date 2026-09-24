@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-lg border-slate-300 shadow-sm transition-all duration-150 ease-smooth focus:border-brand-500 focus:ring-2 focus:ring-brand-100']) }}>
