@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-6">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Finance</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Expenses</h1>
        <p class="mt-1 text-sm text-slate-600">Every expense recorded across all projects.</p>
    </div>

    <form method="GET" class="mb-4 flex flex-wrap gap-3" data-ajax-form="ajax-results" data-turbo="false">
        <div class="relative">
            <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search project, category, payee" aria-label="Search project, category, payee" class="w-72 rounded-md border-slate-300 py-2 pl-9 pr-3 text-sm shadow-sm transition-all duration-150 focus:border-brand-400 focus:ring-2 focus:ring-brand-100">
        </div>
        <button type="submit" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition-colors duration-150 hover:bg-slate-50">Search</button>
    </form>

    <div id="ajax-results" data-ajax-region>
        @include('budget.partials.expenses-results')
    </div>
</div>
@endsection
