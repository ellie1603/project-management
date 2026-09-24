<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn-sheen inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all duration-200 ease-elegant hover:-translate-y-px hover:bg-slate-800 hover:shadow-premium focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 active:translate-y-0 active:scale-[0.98]']) }}>
    {{ $slot }}
</button>
