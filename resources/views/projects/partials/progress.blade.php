@php
    use Illuminate\Support\Carbon;

    $canWriteProgress = auth()->user()->isProjectPersonnel() && $project->hasPersonnel(auth()->user());
    $phases = $project->phases;
    $timeline = $project->timelineSummary();
    $completion = $project->completionPercentage();

    $segments = collect();
    if ($project->planned_start_date && $project->target_completion_date) {
        $start = Carbon::parse($project->planned_start_date);
        $end = Carbon::parse($project->target_completion_date);
        $totalDays = max(1, $start->diffInDays($end));
        $count = max(1, $phases->count());
        $segmentDays = $totalDays / $count;

        $segments = $phases->values()->map(function ($phase, $index) use ($segmentDays, $totalDays) {
            $segStart = (int) round($index * $segmentDays);
            $segEnd = (int) round(($index + 1) * $segmentDays);

            return [
                'phase' => $phase,
                'left' => $totalDays > 0 ? ($segStart / $totalDays) * 100 : 0,
                'width' => $totalDays > 0 ? max(1, (($segEnd - $segStart) / $totalDays) * 100) : 100,
            ];
        });

        $today = Carbon::today();
        $todayPercent = $today->between($start, $end) ? ($start->diffInDays($today) / $totalDays) * 100 : null;
    }
@endphp

<section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Project Progress</h2>
            <p class="mt-0.5 text-sm text-slate-500">Infrastructure completion and schedule health.</p>
        </div>
        <div class="flex items-center gap-3">
            <x-status-badge :status="$timeline['timeline_status']" />
            @if (auth()->user()->isAdmin())
                <button type="button" @click="$dispatch('open-modal', 'edit-project')" class="inline-flex items-center gap-1.5 rounded-md border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 transition-colors duration-150 hover:bg-slate-50">
                    <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                    Edit Schedule
                </button>
            @endif
        </div>
    </div>

    <div class="mt-5 flex items-center justify-between gap-4">
        <p class="text-sm font-medium text-slate-700">Overall Infrastructure Completion</p>
        <p class="text-2xl font-semibold tabular-nums text-slate-900">{{ $completion }}%</p>
    </div>
    <x-progress-bar :percent="$completion" class="mt-2 h-2.5" :tone="match(true) {
        $completion >= 100 => 'emerald',
        default => 'brand',
    }" />

    <div class="mt-6">
        <x-phase-stepper :phases="$phases" />
    </div>

    <dl class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div><dt class="text-xs uppercase tracking-wide text-slate-500">Planned Start</dt><dd class="mt-1 text-sm text-slate-700">{{ $project->planned_start_date ?? 'N/A' }}</dd></div>
        <div><dt class="text-xs uppercase tracking-wide text-slate-500">Target Completion</dt><dd class="mt-1 text-sm text-slate-700">{{ $project->target_completion_date ?? 'N/A' }}</dd></div>
        <div><dt class="text-xs uppercase tracking-wide text-slate-500">Actual Start</dt><dd class="mt-1 text-sm text-slate-700">{{ $project->actual_start_date ?? 'N/A' }}</dd></div>
        <div><dt class="text-xs uppercase tracking-wide text-slate-500">Actual Completion</dt><dd class="mt-1 text-sm text-slate-700">{{ $project->actual_completion_date ?? 'N/A' }}</dd></div>
        <div><dt class="text-xs uppercase tracking-wide text-slate-500">Days Elapsed</dt><dd class="mt-1 text-sm text-slate-700">{{ $timeline['days_elapsed'] }} days</dd></div>
        <div><dt class="text-xs uppercase tracking-wide text-slate-500">Days Remaining</dt><dd class="mt-1 text-sm text-slate-700">{{ $timeline['days_remaining'] ?? 'N/A' }} days</dd></div>
        <div><dt class="text-xs uppercase tracking-wide text-slate-500">Delay</dt><dd class="mt-1 text-sm text-slate-700">{{ $timeline['delay_days'] > 0 ? $timeline['delay_days'].' days' : 'None' }}</dd></div>
        <div><dt class="text-xs uppercase tracking-wide text-slate-500">Schedule Status</dt><dd class="mt-1 text-sm font-semibold text-slate-900">{{ $timeline['timeline_status'] }}</dd></div>
    </dl>
</section>

@if ($segments->isNotEmpty())
    <section class="mt-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-lg font-semibold text-slate-900">Schedule</h2>
            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] text-slate-500">
                <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-slate-900"></span>Completed</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-brand-600"></span>In Progress</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-slate-200"></span>Not Started</span>
            </div>
        </div>
        <div class="mt-5 overflow-x-auto">
            <div class="min-w-[480px]">
                <div class="relative h-3">
                    @if ($todayPercent !== null)
                        <span class="absolute -top-1 bottom-[-2px] z-10 w-px bg-slate-400/70" style="left: {{ $todayPercent }}%" title="Today"></span>
                    @endif
                    @foreach ($segments as $segment)
                        <div class="absolute inset-y-0 {{ match ($segment['phase']->status) { 'Completed' => 'bg-slate-900', 'In Progress' => 'bg-brand-600', default => 'bg-slate-200' } }} {{ $loop->first ? 'rounded-l-full' : '' }} {{ $loop->last ? 'rounded-r-full' : '' }}" style="left: {{ $segment['left'] }}%; width: {{ $segment['width'] }}%; {{ ! $loop->last ? 'border-right: 2px solid white;' : '' }}" title="{{ $segment['phase']->name }} — {{ $segment['phase']->status }}"></div>
                    @endforeach
                </div>
                <div class="mt-2 flex">
                    @foreach ($segments as $segment)
                        <div class="text-center text-[10.5px] font-medium text-slate-500" style="width: {{ $segment['width'] }}%">{{ $segment['phase']->name }}</div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endif

@if ($canWriteProgress)
    <section class="mt-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm" x-data="{
        phaseId: '{{ old('phase_id', $project->currentPhase()?->id) }}',
        phaseStatus: '{{ old('phase_status', 'In Progress') }}',
        phases: {{ $phases->map(fn ($phase) => ['id' => (string) $phase->id, 'sequence' => $phase->sequence, 'status' => $phase->status])->values()->toJson() }},
        total: {{ max(1, $phases->count()) }},
        get preview() {
            const selected = this.phases.find((p) => p.id === this.phaseId);
            if (! selected) { return null; }
            const completedCount = this.phaseStatus === 'Completed' ? selected.sequence : selected.sequence - 1;
            return Math.round((Math.max(0, completedCount) / this.total) * 100);
        },
    }">
        <h2 class="text-lg font-semibold text-slate-900">Update Project Progress</h2>
        <form method="POST" action="{{ route('projects.progress.store', $project) }}" enctype="multipart/form-data" class="mt-4 space-y-4">
            @csrf
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="progress-phase" class="mb-1 block text-sm font-medium text-slate-700">Construction Stage</label>
                    <select id="progress-phase" name="phase_id" x-model="phaseId" class="w-full rounded-md border-slate-300 text-sm shadow-sm transition-all duration-150 focus:border-brand-400 focus:ring-2 focus:ring-brand-100" required>
                        @foreach ($phases as $phase)
                            <option value="{{ $phase->id }}">{{ $phase->sequence }}. {{ $phase->name }} ({{ $phase->status }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="progress-phase-status" class="mb-1 block text-sm font-medium text-slate-700">Stage Status</label>
                    <select id="progress-phase-status" name="phase_status" x-model="phaseStatus" class="w-full rounded-md border-slate-300 text-sm shadow-sm transition-all duration-150 focus:border-brand-400 focus:ring-2 focus:ring-brand-100" required>
                        <option value="In Progress">In Progress</option>
                        <option value="Completed">Completed</option>
                    </select>
                </div>
                <div>
                    <label for="progress-date" class="mb-1 block text-sm font-medium text-slate-700">Update Date</label>
                    <input id="progress-date" type="date" name="progress_date" value="{{ old('progress_date', now()->toDateString()) }}" class="w-full rounded-md border-slate-300 text-sm shadow-sm transition-all duration-150 focus:border-brand-400 focus:ring-2 focus:ring-brand-100" required>
                </div>
                <div class="flex items-end">
                    <div class="w-full rounded-md border border-brand-100 bg-brand-50 px-3 py-2 text-sm text-brand-800">
                        New completion: <span class="font-semibold" x-text="preview !== null ? preview + '%' : '—'"></span>
                    </div>
                </div>
            </div>
            <div>
                <label for="progress-accomplishments" class="mb-1 block text-sm font-medium text-slate-700">Accomplishments</label>
                <textarea id="progress-accomplishments" name="accomplishments" rows="2" class="w-full rounded-md border-slate-300 text-sm shadow-sm transition-all duration-150 focus:border-brand-400 focus:ring-2 focus:ring-brand-100" required>{{ old('accomplishments') }}</textarea>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="progress-activities-completed" class="mb-1 block text-sm font-medium text-slate-700">Activities Completed</label>
                    <textarea id="progress-activities-completed" name="activities_completed" rows="2" class="w-full rounded-md border-slate-300 text-sm shadow-sm transition-all duration-150 focus:border-brand-400 focus:ring-2 focus:ring-brand-100">{{ old('activities_completed') }}</textarea>
                </div>
                <div>
                    <label for="progress-activities-remaining" class="mb-1 block text-sm font-medium text-slate-700">Activities Remaining</label>
                    <textarea id="progress-activities-remaining" name="activities_remaining" rows="2" class="w-full rounded-md border-slate-300 text-sm shadow-sm transition-all duration-150 focus:border-brand-400 focus:ring-2 focus:ring-brand-100">{{ old('activities_remaining') }}</textarea>
                </div>
            </div>
            <div>
                <label for="progress-issues" class="mb-1 block text-sm font-medium text-slate-700">Issues / Delays</label>
                <textarea id="progress-issues" name="issues" rows="2" class="w-full rounded-md border-slate-300 text-sm shadow-sm transition-all duration-150 focus:border-brand-400 focus:ring-2 focus:ring-brand-100">{{ old('issues') }}</textarea>
            </div>
            <div>
                <label for="progress-file" class="mb-1 block text-sm font-medium text-slate-700">Attach Photo or Report</label>
                <input id="progress-file" type="file" name="file" class="w-full text-sm">
            </div>
            <button class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white shadow-sm transition-all duration-150 hover:bg-slate-800 hover:shadow-md active:scale-[0.98]">Save Progress</button>
        </form>
    </section>
@endif

<section class="mt-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
    <h2 class="text-lg font-semibold text-slate-900">Progress History</h2>
    <div class="mt-4 overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-3 py-2">Date</th>
                    <th class="px-3 py-2">Stage</th>
                    <th class="px-3 py-2">Completion</th>
                    <th class="px-3 py-2">Accomplishments</th>
                    <th class="px-3 py-2">Issues</th>
                    <th class="px-3 py-2">Submitted By</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($project->progress->sortByDesc('progress_date') as $entry)
                    <tr>
                        <td class="px-3 py-3">{{ $entry->progress_date->format('M d, Y') }}</td>
                        <td class="px-3 py-3">{{ $entry->phase?->name ?? '—' }}</td>
                        <td class="px-3 py-3">{{ $entry->progress_percentage }}%</td>
                        <td class="px-3 py-3">{{ $entry->accomplishments }}</td>
                        <td class="px-3 py-3">{{ $entry->issues ?? '—' }}</td>
                        <td class="px-3 py-3">{{ $entry->user->name }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-3 py-5 text-center text-slate-500">No progress updates yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
