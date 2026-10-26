@extends('layouts.app')

@section('content')
@php
    $budget = $project->budgetSummary();
    $timeline = $project->timelineSummary();

    $viewer = auth()->user();
    $isPersonnel = $viewer->isProjectPersonnel();
    $canEditProject = $viewer->can('update', $project);
    $canDeleteProject = $viewer->can('delete', $project);

    // Tabs follow the viewer's permissions (Roles & Permissions page).
    $tabs = array_values(array_filter([
        ['id' => 'overview', 'label' => 'Overview', 'icon' => 'file-text'],
        ['id' => 'progress', 'label' => 'Project Progress', 'icon' => 'bar-chart-3'],
        $viewer->can('viewBudget', $project) ? ['id' => 'budget', 'label' => 'Budget', 'icon' => 'wallet'] : null,
        $isPersonnel && $viewer->can('createBudgetRequest', $project) ? ['id' => 'budget-request', 'label' => 'Budget Request', 'icon' => 'hand-coins'] : null,
        $isPersonnel ? null : ['id' => 'personnel', 'label' => 'Personnel', 'icon' => 'users'],
        $isPersonnel ? null : ['id' => 'contractors', 'label' => 'Contractor', 'icon' => 'building'],
        ['id' => 'documents', 'label' => 'Documents', 'icon' => 'file-text'],
        $isPersonnel ? null : ['id' => 'activity', 'label' => 'Activity Log', 'icon' => 'clock'],
    ]));
    $tabIds = array_column($tabs, 'id');
@endphp

<div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8" x-data="tabSlide(@js(session('tab', old('_form') ? 'progress' : 'overview')), @js(array_column($tabs, 'id')))">
    <x-flash-toast />
    <x-flash-toast type="error" />

    <x-breadcrumbs />

    <x-page-hero
        eyebrow="Project Details"
        :title="$project->title"
        :subtitle="$project->project_code.' · '.($project->location ?? 'Location not provided')"
    >
        @if ($canEditProject || $canDeleteProject)
            <x-slot name="aside">
                <div class="flex flex-wrap items-center gap-3">
                    @if ($canEditProject)
                    <button type="button" @click="$dispatch('open-modal', 'edit-project')" class="inline-flex items-center gap-2 rounded-xl bg-accent-500 px-4 py-2.5 text-sm font-semibold text-white shadow-glow hover:bg-accent-600 transition-transform duration-200 ease-elegant hover:-translate-y-0.5">
                        <i data-lucide="pencil" class="h-4 w-4"></i>
                        Edit Project
                    </button>
                    @endif
                    @if ($canDeleteProject)
                    <button type="button" @click="$dispatch('open-modal', 'delete-project')" class="inline-flex items-center gap-2 rounded-xl border border-red-400/40 bg-red-500/10 px-4 py-2.5 text-sm font-semibold text-red-300 transition-colors duration-150 hover:bg-red-600 hover:text-white">
                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                        Delete Project
                    </button>
                    @endif
                </div>
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
        <nav x-ref="tabNav" class="relative flex max-w-full gap-1 overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden rounded-2xl bg-white p-1.5 shadow-soft ring-1 ring-slate-900/5 sm:inline-flex">
            <span x-ref="tabPill" aria-hidden="true" class="pointer-events-none absolute bottom-1.5 top-1.5 rounded-xl bg-brand-600 opacity-0 shadow-sm" style="left: 0; width: 0;"></span>
            @foreach ($tabs as $tabItem)
                <button
                    type="button"
                    data-tab="{{ $tabItem['id'] }}"
                    @click="switchTab('{{ $tabItem['id'] }}')"
                    :class="tab === '{{ $tabItem['id'] }}' ? 'text-white delay-200' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800'"
                    class="relative z-10 flex shrink-0 items-center gap-2 whitespace-nowrap rounded-xl px-3.5 py-2 text-sm font-medium transition-colors duration-300 ease-elegant focus:outline-none"
                >
                    <i data-lucide="{{ $tabItem['icon'] }}" class="h-4 w-4"></i>
                    {{ $tabItem['label'] }}
                </button>
            @endforeach
        </nav>
    </div>

    @php
        $paneTransition = 'x-transition:enter="tab-pane-enter" x-transition:enter-start="tab-pane-enter-start" x-transition:enter-end="tab-pane-enter-end"';
    @endphp
    <div class="tab-stage" :data-dir="direction">
        <div x-show="tab === 'overview'" {!! $paneTransition !!}>@include('projects.partials.overview')</div>
        <div x-show="tab === 'progress'" {!! $paneTransition !!} style="display: none;">@include('projects.partials.progress')</div>
        @if (in_array('budget', $tabIds, true))
            <div x-show="tab === 'budget'" {!! $paneTransition !!} style="display: none;">@include('projects.partials.budget')</div>
        @endif
        @if (in_array('budget-request', $tabIds, true))
            <div x-show="tab === 'budget-request'" {!! $paneTransition !!} style="display: none;">@include('projects.partials.budget-request')</div>
        @endif
        @if (in_array('personnel', $tabIds, true))
            <div x-show="tab === 'personnel'" {!! $paneTransition !!} style="display: none;">@include('projects.partials.personnel')</div>
            <div x-show="tab === 'contractors'" {!! $paneTransition !!} style="display: none;">@include('projects.partials.contractors')</div>
        @endif
        <div x-show="tab === 'documents'" {!! $paneTransition !!} style="display: none;">@include('projects.partials.documents')</div>
        @if (in_array('activity', $tabIds, true))
            <div x-show="tab === 'activity'" {!! $paneTransition !!} style="display: none;">@include('projects.partials.activity')</div>
        @endif
    </div>

    @if ($canEditProject)
        @include('projects.partials.edit-modal')
    @endif
    @if ($canDeleteProject)
        @include('projects.partials.delete-modal')
    @endif

    @include('projects.partials.progress-modals')
</div>
@endsection
