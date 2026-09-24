@php
    $statusMeta = [
        'Ongoing' => ['accent' => 'from-brand-500 to-brand-700', 'chip' => 'chip-ink'],
        'Completed' => ['accent' => 'from-slate-700 to-slate-900', 'chip' => 'chip-ink'],
        'On Hold' => ['accent' => 'from-amber-400 to-orange-500', 'chip' => 'chip-ink'],
        'Cancelled' => ['accent' => 'from-red-400 to-rose-500', 'chip' => 'chip-ink'],
        'Registered' => ['accent' => 'from-slate-300 to-slate-400', 'chip' => 'chip-ink'],
    ];
@endphp

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
    @forelse ($projects as $project)
        @php $meta = $statusMeta[$project->status] ?? $statusMeta['Registered']; @endphp
        <a
            href="{{ route('projects.show', $project) }}"
            class="group relative flex animate-rise-in flex-col overflow-hidden rounded-3xl bg-white p-5 shadow-soft ring-1 ring-slate-900/5 transition-all duration-300 ease-elegant hover:-translate-y-0.5 hover:shadow-elevated"
            style="animation-delay: {{ min($loop->index, 8) * 50 }}ms"
        >
            <span class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r {{ $meta['accent'] }}"></span>

            <div class="flex items-start justify-between gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $meta['chip'] }} text-white shadow-sm">
                    <i data-lucide="building-2" class="h-[18px] w-[18px]"></i>
                </span>
                <x-status-badge :status="$project->status" />
            </div>

            <div class="mt-3.5 min-w-0">
                <div class="flex flex-wrap items-center gap-1.5">
                    <span class="text-[11px] font-medium text-slate-400">{{ $project->project_code }}</span>
                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[10.5px] font-medium text-slate-600">{{ $project->category?->name ?? 'Uncategorized' }}</span>
                </div>
                <p class="mt-1.5 line-clamp-2 font-display text-[15px] font-semibold leading-snug text-slate-900 transition-colors duration-150 group-hover:text-brand-700">{{ $project->title }}</p>
                <p class="mt-1 flex items-center gap-1.5 text-xs text-slate-500">
                    <i data-lucide="map-pin" class="h-3.5 w-3.5 shrink-0 text-slate-400"></i>
                    <span class="truncate">{{ $project->location ?? 'Location not provided' }}</span>
                </p>
            </div>

            <div class="mt-4 space-y-3">
                <div>
                    <div class="flex items-center justify-between">
                        <p class="text-[10.5px] font-medium uppercase tracking-wider text-slate-400">Completion</p>
                        <p class="text-xs font-semibold tabular-nums text-slate-900">{{ $project->completionPercentage() }}%</p>
                    </div>
                    <x-progress-bar :percent="$project->completionPercentage()" tone="brand" class="mt-1.5 !h-1.5" />
                </div>

                @if (auth()->user()->isProjectPersonnel())
                    <p class="flex items-center gap-1.5 text-xs font-medium text-slate-700">
                        <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ match ($project->currentPhase()?->status) {
                            'Completed' => 'bg-slate-900',
                            'In Progress' => 'bg-brand-600',
                            default => 'bg-slate-300',
                        } }}"></span>
                        {{ $project->currentPhase()?->name ?? 'Not started' }}
                    </p>
                @else
                    <div>
                        <div class="flex items-center justify-between">
                            <p class="text-[10.5px] font-medium uppercase tracking-wider text-slate-400">Budget Utilization</p>
                            <p class="text-xs font-semibold tabular-nums text-slate-900">{{ number_format((float) $project->budget['budget_utilization_percent'], 1) }}%</p>
                        </div>
                        <x-progress-bar
                            :percent="$project->budget['budget_utilization_percent']"
                            class="mt-1.5 !h-1.5"
                            :tone="match(true) {
                                $project->budget['budget_utilization_percent'] >= 100 => 'red',
                                $project->budget['budget_utilization_percent'] >= 80 => 'amber',
                                default => 'emerald',
                            }"
                        />
                    </div>
                @endif
            </div>

            <div class="mt-4 flex items-center justify-between gap-3 border-t border-slate-100 pt-3.5 text-xs text-slate-500">
                <span class="inline-flex min-w-0 items-center gap-1.5">
                    <i data-lucide="calendar" class="h-3.5 w-3.5 shrink-0 text-slate-400"></i>
                    <span class="truncate">
                        @if ($project->timeline['timeline_status'] === 'Delayed')
                            <span class="font-semibold text-red-600">{{ $project->timeline['delay_days'] }}d overdue</span>
                        @elseif ($project->timeline['days_remaining'] !== null)
                            {{ $project->timeline['days_remaining'] }}d left
                        @else
                            No target date
                        @endif
                    </span>
                </span>

                <span class="inline-flex shrink-0 items-center gap-1 text-xs font-semibold text-slate-700 transition-colors duration-150 group-hover:text-brand-600">
                    View
                    <i data-lucide="arrow-right" class="h-3.5 w-3.5 transition-transform duration-150 group-hover:translate-x-0.5"></i>
                </span>
            </div>
        </a>
    @empty
        <div class="col-span-full rounded-3xl bg-white p-14 text-center shadow-soft ring-1 ring-slate-900/5">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                <i data-lucide="folder-search" class="h-6 w-6"></i>
            </div>
            <p class="mt-4 text-sm font-semibold text-slate-700">No projects match the selected filters.</p>
            <p class="mt-1 text-xs text-slate-500">Try adjusting your search, status, or category filters.</p>
        </div>
    @endforelse
</div>

<div class="ajax-pagination mt-5" data-turbo="false">
    {{ $projects->links() }}
</div>
