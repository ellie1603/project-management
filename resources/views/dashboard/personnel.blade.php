@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <x-page-hero
        eyebrow="Project Personnel"
        title="My Projects"
        subtitle="Projects assigned to you — keep progress and issues up to date."
        class="animate-rise-in"
    />

    <div class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat-card label="Assigned" :value="$stats['assigned']" icon="folder" sub="Total projects" class="animate-rise-in" style="animation-delay: 60ms" />
        <x-stat-card label="Ongoing" :value="$stats['ongoing']" icon="loader" :sub="$stats['completed'].' completed'" class="animate-rise-in" style="animation-delay: 110ms" />
        <x-stat-card label="Delayed" :value="$stats['delayed']" icon="triangle-alert" sub="Needs an update" class="animate-rise-in" style="animation-delay: 160ms" />
        <x-stat-card label="Upcoming Deadlines" :value="$stats['upcoming_deadlines']" icon="calendar-clock" sub="Approaching target date" class="animate-rise-in" style="animation-delay: 210ms" />
    </div>

    <div class="mt-6 animate-rise-in" style="animation-delay: 240ms">
        @include('dashboard.partials.timeline', ['timelineTasks' => $timelineTasks])
    </div>

    <div class="mt-6 animate-rise-in rounded-2xl border border-slate-200/80 bg-white shadow-soft" style="animation-delay: 280ms">
        <div class="border-b border-slate-100 px-5 py-4">
            <h3 class="font-display text-lg font-semibold text-slate-900">Assigned Projects</h3>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse ($projectRows as $row)
                <div class="flex flex-col gap-3 px-5 py-4 transition-colors duration-150 hover:bg-slate-50/80 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-slate-900">{{ $row['project']->title }}</p>
                        <p class="mt-1 flex flex-wrap items-center gap-1.5 text-xs text-slate-500">
                            {{ $row['project']->project_code }} · Completion {{ $row['completion_percentage'] }}%
                            <x-status-badge :status="$row['timeline_status']" />
                            <x-status-badge :status="$row['budget_status']" />
                        </p>
                        @if ($row['latest_update'])
                            <p class="mt-1 text-xs text-slate-500">Latest update: {{ $row['latest_update']->progress_date->format('M d, Y') }} — {{ Str::limit($row['latest_update']->accomplishments, 80) }}</p>
                        @endif
                        @if ($row['required_action'])
                            <p class="mt-1 text-xs font-medium text-amber-600">Action needed: {{ $row['required_action'] }}</p>
                        @endif
                    </div>
                    <a href="{{ route('projects.show', $row['project']) }}" class="inline-flex shrink-0 items-center gap-1 text-sm font-medium text-slate-700 transition-colors duration-150 hover:text-brand-600">
                        Open
                        <i data-lucide="arrow-right" class="h-3.5 w-3.5"></i>
                    </a>
                </div>
            @empty
                <div class="px-5 py-12 text-center text-sm text-slate-500">You have no assigned projects yet.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
