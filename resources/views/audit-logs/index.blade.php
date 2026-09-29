@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
    <x-page-hero
        eyebrow="Administration"
        title="Audit Logs"
        subtitle="A complete, unmodifiable trail of important actions taken across the system."
        class="animate-rise-in"
    />

    <form method="GET" class="mt-6 flex flex-wrap items-center gap-3 rounded-2xl bg-white p-3 shadow-soft ring-1 ring-slate-900/5" data-ajax-form="ajax-results" data-turbo="false">
        <div class="relative min-w-[16rem] flex-1">
            <i data-lucide="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Search user, action, module, or description"
                aria-label="Search audit logs"
                class="w-full rounded-xl border-slate-200 bg-slate-50/60 py-2.5 pl-10 pr-3 text-sm transition-all duration-200 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100"
            >
        </div>

        <select name="module" aria-label="Filter by module" class="rounded-xl border-slate-200 bg-slate-50/60 py-2.5 pl-3.5 pr-9 text-sm transition-all duration-200 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100">
            <option value="">All Modules</option>
            @foreach ($modules as $module)
                <option value="{{ $module }}" @selected(request('module') === $module)>{{ ucwords(str_replace('_', ' ', $module)) }}</option>
            @endforeach
        </select>

        <x-filter-reset />
    </form>

    <div id="ajax-results" data-ajax-region class="mt-5">
        @include('audit-logs.partials.results')
    </div>
</div>
@endsection
