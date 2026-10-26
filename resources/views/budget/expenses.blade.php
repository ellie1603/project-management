@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <x-flash-toast />
    <x-flash-toast type="error" />

    <x-breadcrumbs />

    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Finance</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Expenses</h1>
            <p class="mt-1 text-sm text-slate-600">Every expense recorded across all projects.</p>
        </div>

        @if (auth()->user()->can('create', App\Models\ProjectExpense::class))
            <button type="button" @click="$dispatch('open-modal', 'add-expenses')" class="inline-flex items-center gap-2 rounded-xl bg-accent-500 px-4 py-2.5 text-sm font-semibold text-white shadow-glow transition-transform duration-200 ease-elegant hover:-translate-y-0.5 hover:bg-accent-600">
                <i data-lucide="plus" class="h-4 w-4"></i>
                Add Expenses
            </button>
        @endif
    </div>

    <form method="GET" class="mb-4 flex flex-wrap gap-3" data-ajax-form="ajax-results" data-turbo="false">
        <div class="relative">
            <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search project or reference" aria-label="Search project or reference" class="w-72 rounded-md border-slate-300 py-2 pl-9 pr-3 text-sm shadow-sm transition-all duration-150 focus:border-brand-400 focus:ring-2 focus:ring-brand-100">
        </div>
        <x-filter-reset class="!rounded-md !py-2" />
    </form>

    <div id="ajax-results" data-ajax-region>
        @include('budget.partials.expenses-results')
    </div>
</div>

{{-- ?add=1 (the old Add Expenses address) opens the dialog straight away. --}}
<x-add-expenses-modal :open="request()->boolean('add')" />

@endsection
