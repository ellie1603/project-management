@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-6">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Finance</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Budget Requests</h1>
        <p class="mt-1 text-sm text-slate-600">Review and act on budget requests submitted by project personnel.</p>
    </div>

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <nav class="inline-flex gap-1 rounded-xl bg-white p-1 shadow-sm ring-1 ring-slate-900/5">
            @foreach (['Pending', 'Approved', 'Rejected'] as $option)
                <a
                    href="{{ route('budget.requests', ['status' => $option] + request()->except('page')) }}"
                    class="rounded-lg px-3.5 py-1.5 text-sm font-medium transition-colors duration-150 {{ $status === $option ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}"
                >{{ $option }}</a>
            @endforeach
        </nav>

        <form method="GET" class="flex flex-wrap gap-3" data-ajax-form="ajax-results" data-turbo="false">
            <input type="hidden" name="status" value="{{ $status }}">
            <div class="relative">
                <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search project or purpose" aria-label="Search project or purpose" class="w-64 rounded-md border-slate-300 py-2 pl-9 pr-3 text-sm shadow-sm transition-all duration-150 focus:border-brand-400 focus:ring-2 focus:ring-brand-100">
            </div>
            <button type="submit" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition-colors duration-150 hover:bg-slate-50">Search</button>
        </form>
    </div>

    <div id="ajax-results" data-ajax-region>
        @include('budget.partials.requests-results')
    </div>
</div>
@endsection
