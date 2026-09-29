@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <x-page-hero
        eyebrow="Projects"
        title="All Projects"
        subtitle="Every project you have access to — search, filter, and open any one for full budget, timeline, and progress detail."
        class="animate-rise-in"
    >
        @if (auth()->user()->isAdmin())
            <x-slot name="aside">
                <a href="{{ route('projects.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-accent-500 px-4 py-2.5 text-sm font-semibold text-white shadow-glow hover:bg-accent-600 transition-transform duration-200 ease-elegant hover:-translate-y-0.5">
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    Register Project
                </a>
            </x-slot>
        @endif

        <x-slot name="footer">
            <div class="grid grid-cols-2 gap-6 sm:grid-cols-4">
                @foreach ([
                    ['label' => 'Total Projects', 'value' => $summaryStats['total'], 'tone' => 'text-white'],
                    ['label' => 'Ongoing', 'value' => $summaryStats['ongoing'], 'tone' => 'text-brand-300'],
                    ['label' => 'Completed', 'value' => $summaryStats['completed'], 'tone' => 'text-emerald-300'],
                    ['label' => 'On Hold', 'value' => $summaryStats['on_hold'], 'tone' => 'text-amber-300'],
                ] as $i => $figure)
                    <div class="animate-rise-in" style="animation-delay: {{ 120 + $i * 60 }}ms">
                        <p class="text-[11px] font-medium uppercase tracking-wider text-slate-500">{{ $figure['label'] }}</p>
                        <p class="mt-1.5 font-display text-xl font-semibold tabular-nums tracking-tight {{ $figure['tone'] }}">{{ $figure['value'] }}</p>
                    </div>
                @endforeach
            </div>
        </x-slot>
    </x-page-hero>

    <form method="GET" class="mt-6 flex flex-wrap items-center gap-3 rounded-2xl bg-white p-3 shadow-soft ring-1 ring-slate-900/5" data-ajax-form="ajax-results" data-turbo="false">
        <div class="relative min-w-[16rem] flex-1">
            <i data-lucide="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Search code, title, or location"
                aria-label="Search code, title, or location"
                class="w-full rounded-xl border-slate-200 bg-slate-50/60 py-2.5 pl-10 pr-3 text-sm transition-all duration-200 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100"
            >
        </div>

        <div class="relative">
            <select name="status" aria-label="Filter by status" class="rounded-xl border-slate-200 bg-slate-50/60 py-2.5 pl-3.5 pr-9 text-sm transition-all duration-200 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100">
                <option value="">Any Status</option>
                @foreach (\App\Models\Project::STATUSES as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                @endforeach
            </select>
        </div>

        <div class="relative">
            <select name="category_id" aria-label="Filter by category" class="rounded-xl border-slate-200 bg-slate-50/60 py-2.5 pl-3.5 pr-9 text-sm transition-all duration-200 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100">
                <option value="">Any Category</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>

        <x-filter-reset />
    </form>

    <div id="ajax-results" data-ajax-region class="mt-5">
        @include('projects.partials.results')
    </div>
</div>
@endsection
