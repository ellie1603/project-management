@extends('layouts.app')

@section('content')
@php
    $formatBytes = function (int $bytes): string {
        if ($bytes <= 0) {
            return '0 KB';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $power = min(count($units) - 1, (int) floor(log($bytes, 1024)));

        return number_format($bytes / (1024 ** $power), $power === 0 ? 0 : 1).' '.$units[$power];
    };
@endphp

<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <x-page-hero
        eyebrow="Administration"
        title="Project Documents"
        subtitle="Approved designs, contracts, reports, and photos uploaded across every project — organized in one searchable archive."
        class="animate-rise-in"
    >
        <x-slot name="footer">
            <div class="grid grid-cols-2 gap-6 sm:grid-cols-4">
                @foreach ([
                    ['label' => 'Total Documents', 'value' => number_format($summaryStats['total']), 'tone' => 'text-white'],
                    ['label' => 'Current Approved Designs', 'value' => number_format($summaryStats['current_designs']), 'tone' => 'text-brand-300'],
                    ['label' => 'Storage Used', 'value' => $formatBytes($summaryStats['storage_bytes']), 'tone' => 'text-emerald-300'],
                    ['label' => 'Uploaded This Week', 'value' => number_format($summaryStats['uploaded_this_week']), 'tone' => 'text-amber-300'],
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
                placeholder="Search document or project"
                aria-label="Search document or project"
                class="w-full rounded-xl border-slate-200 bg-slate-50/60 py-2.5 pl-10 pr-3 text-sm transition-all duration-200 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100"
            >
        </div>

        <select name="document_type" aria-label="Filter by document type" class="rounded-xl border-slate-200 bg-slate-50/60 py-2.5 pl-3.5 pr-9 text-sm transition-all duration-200 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100">
            <option value="">All Types</option>
            @foreach ($documentTypes as $type)
                <option value="{{ $type }}" @selected(request('document_type') === $type)>{{ $type }}</option>
            @endforeach
        </select>

        <x-filter-reset />
    </form>

    <div id="ajax-results" data-ajax-region class="mt-5">
        @include('documents.partials.results')
    </div>
</div>
@endsection
