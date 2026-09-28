@extends('layouts.app')

@section('content')
@php
    $budget = $project->budgetSummary();
    $timeline = $project->timelineSummary();

    $isPersonnel = auth()->user()->isProjectPersonnel();

    $tabs = $isPersonnel
        ? [
            ['id' => 'overview', 'label' => 'Overview', 'icon' => 'file-text'],
            ['id' => 'progress', 'label' => 'Project Progress', 'icon' => 'bar-chart-3'],
            ['id' => 'budget-request', 'label' => 'Budget Request', 'icon' => 'hand-coins'],
            ['id' => 'documents', 'label' => 'Documents', 'icon' => 'file-text'],
        ]
        : [
            ['id' => 'overview', 'label' => 'Overview', 'icon' => 'file-text'],
            ['id' => 'progress', 'label' => 'Project Progress', 'icon' => 'bar-chart-3'],
            ['id' => 'budget', 'label' => 'Budget', 'icon' => 'wallet'],
            ['id' => 'personnel', 'label' => 'Personnel', 'icon' => 'users'],
            ['id' => 'contractors', 'label' => 'Contractor', 'icon' => 'building'],
            ['id' => 'documents', 'label' => 'Documents', 'icon' => 'file-text'],
            ['id' => 'activity', 'label' => 'Activity Log', 'icon' => 'clock'],
        ];
@endphp

<div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8" x-data="{ tab: @js(session('tab', old('_form') ? 'progress' : 'overview')) }">
    <x-flash-toast />
    <x-flash-toast :message="session('error')" type="error" />

    <a href="{{ route('projects.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition-colors duration-150 hover:text-slate-900">
        <i data-lucide="arrow-left" class="h-4 w-4"></i>
        Back to Projects
    </a>

    <x-page-hero
        eyebrow="Project Details"
        :title="$project->title"
        :subtitle="$project->project_code.' · '.($project->location ?? 'Location not provided')"
    >
        @if (auth()->user()->isAdmin())
            <x-slot name="aside">
                <button type="button" @click="$dispatch('open-modal', 'edit-project')" class="inline-flex items-center gap-2 rounded-xl bg-accent-500 px-4 py-2.5 text-sm font-semibold text-white shadow-glow hover:bg-accent-600 transition-transform duration-200 ease-elegant hover:-translate-y-0.5">
                    <i data-lucide="pencil" class="h-4 w-4"></i>
                    Edit Project
                </button>
            </x-slot>
        @endif
    </x-page-hero>

    @php
        // Icon chips share the single ink style; the status itself is carried by the text and badge.
        $chipTone = 'chip-ink';
        $timelineIcon = match ($timeline['timeline_status']) {
            'Delayed' => 'triangle-alert',
            'Approaching Deadline' => 'clock',
            'Completed' => 'circle-check-big',
            default => 'calendar-check',
        };
        $budgetIcon = $budget['budget_status'] === 'Budget Exceeded' ? 'triangle-alert' : 'wallet';
    @endphp

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="group rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-900/5 transition-all duration-300 ease-elegant hover:-translate-y-0.5 hover:shadow-elevated">
            <div class="flex items-start justify-between gap-3">
                <p class="text-[11px] font-medium uppercase tracking-wider text-slate-400">Status</p>
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $chipTone }} text-white shadow-sm transition-transform duration-300 ease-elegant group-hover:scale-110">
                    <i data-lucide="activity" class="h-4 w-4"></i>
                </span>
            </div>
            <p class="mt-2.5"><x-status-badge :status="$project->status" /></p>
        </div>
        <div class="group rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-900/5 transition-all duration-300 ease-elegant hover:-translate-y-0.5 hover:shadow-elevated">
            <div class="flex items-start justify-between gap-3">
                <p class="text-[11px] font-medium uppercase tracking-wider text-slate-400">Timeline</p>
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $chipTone }} text-white shadow-sm transition-transform duration-300 ease-elegant group-hover:scale-110">
                    <i data-lucide="{{ $timelineIcon }}" class="h-4 w-4"></i>
                </span>
            </div>
            <p class="mt-2 font-display text-[15px] font-semibold text-slate-900">{{ $timeline['timeline_status'] }}</p>
            <p class="mt-0.5 text-xs text-slate-500">{{ $timeline['days_remaining'] ?? 'N/A' }} days remaining</p>
        </div>
        <div class="group rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-900/5 transition-all duration-300 ease-elegant hover:-translate-y-0.5 hover:shadow-elevated">
            <div class="flex items-start justify-between gap-3">
                <p class="text-[11px] font-medium uppercase tracking-wider text-slate-400">Budget Status</p>
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $chipTone }} text-white shadow-sm transition-transform duration-300 ease-elegant group-hover:scale-110">
                    <i data-lucide="{{ $budgetIcon }}" class="h-4 w-4"></i>
                </span>
            </div>
            <p class="mt-2 font-display text-[15px] font-semibold text-slate-900">{{ $budget['budget_status'] }}</p>
            <p class="mt-0.5 text-xs text-slate-500">{{ number_format((float) $budget['budget_utilization_percent'], 2) }}% utilized</p>
        </div>
        <div class="group rounded-2xl bg-white p-4 shadow-soft ring-1 ring-slate-900/5 transition-all duration-300 ease-elegant hover:-translate-y-0.5 hover:shadow-elevated">
            <div class="flex items-start justify-between gap-3">
                <p class="text-[11px] font-medium uppercase tracking-wider text-slate-400">Approved Budget</p>
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $chipTone }} text-white shadow-sm transition-transform duration-300 ease-elegant group-hover:scale-110">
                    <i data-lucide="landmark" class="h-4 w-4"></i>
                </span>
            </div>
            <p class="mt-2 font-display text-[15px] font-semibold tabular-nums text-slate-900">₱{{ number_format((float) $project->approved_budget, 2) }}</p>
            <p class="mt-0.5 text-xs text-slate-500">{{ $project->category?->name ?? 'Unassigned category' }}</p>
        </div>
    </div>

    <div class="sticky top-16 z-10 -mx-4 bg-slate-50/95 px-4 py-2 backdrop-blur sm:mx-0 sm:px-0 sm:py-0 sm:bg-transparent sm:backdrop-blur-0">
        <nav class="flex gap-1 overflow-x-auto rounded-2xl bg-white p-1.5 shadow-soft ring-1 ring-slate-900/5 sm:inline-flex">
            @foreach ($tabs as $tabItem)
                <button
                    type="button"
                    @click="tab = '{{ $tabItem['id'] }}'"
                    :class="tab === '{{ $tabItem['id'] }}' ? 'bg-brand-600 text-white shadow-sm' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800'"
                    class="flex shrink-0 items-center gap-2 whitespace-nowrap rounded-xl px-3.5 py-2 text-sm font-medium transition-all duration-200 ease-elegant focus:outline-none"
                >
                    <i data-lucide="{{ $tabItem['icon'] }}" class="h-4 w-4"></i>
                    {{ $tabItem['label'] }}
                </button>
            @endforeach
        </nav>
    </div>

    <div
        x-show="tab === 'overview'"
        x-transition:enter="transition ease-smooth duration-200"
        x-transition:enter-start="opacity-0 translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
    >@include('projects.partials.overview')</div>
    <div x-show="tab === 'progress'" x-transition:enter="transition ease-smooth duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" style="display: none;">@include('projects.partials.progress')</div>
    @unless ($isPersonnel)
        <div x-show="tab === 'budget'" x-transition:enter="transition ease-smooth duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" style="display: none;">@include('projects.partials.budget')</div>
    @endunless
    @if ($isPersonnel)
        <div x-show="tab === 'budget-request'" x-transition:enter="transition ease-smooth duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" style="display: none;">@include('projects.partials.budget-request')</div>
    @endif
    @unless ($isPersonnel)
        <div x-show="tab === 'personnel'" x-transition:enter="transition ease-smooth duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" style="display: none;">@include('projects.partials.personnel')</div>
        <div x-show="tab === 'contractors'" x-transition:enter="transition ease-smooth duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" style="display: none;">@include('projects.partials.contractors')</div>
    @endunless
    <div x-show="tab === 'documents'" x-transition:enter="transition ease-smooth duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" style="display: none;">@include('projects.partials.documents')</div>
    @unless ($isPersonnel)
        <div x-show="tab === 'activity'" x-transition:enter="transition ease-smooth duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" style="display: none;">@include('projects.partials.activity')</div>
    @endunless

    @if (auth()->user()->isAdmin())
        @include('projects.partials.edit-modal')
    @endif

    @include('projects.partials.progress-modals')
</div>
@endsection
