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
        <div class="flex flex-wrap items-center gap-3">
            <x-status-badge :status="$timeline['timeline_status']" />
            @if ($canWriteProgress)
                <button type="button" @click="$dispatch('open-modal', 'progress-create')" class="inline-flex items-center gap-1.5 rounded-xl bg-brand-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition-colors duration-150 hover:bg-brand-700">
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    Update Progress
                </button>
            @endif
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

@php
    // Oldest first so each entry can show how much completion moved since the previous one.
    $history = $project->progress->sortBy([['progress_date', 'asc'], ['id', 'asc']])->values();
    $previousByEntry = [];
    foreach ($history as $index => $entry) {
        $previousByEntry[$entry->id] = $index > 0 ? $history[$index - 1]->progress_percentage : 0;
    }
    $positions = $project->assignments->pluck('position_type', 'user_id');
    $phaseCount = max(1, $phases->count());
    $lines = fn (?string $text) => collect(preg_split('/\r\n|\r|\n/', (string) $text))
        ->map(fn ($line) => trim(ltrim(trim($line), '-*•')))
        ->filter()
        ->values();
@endphp

<section class="mt-6 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5" x-data="{ photo: null }" @keydown.escape.window="photo = null">
    <div class="flex items-center justify-between gap-3">
        <h2 class="text-lg font-semibold text-slate-900">Progress History</h2>
        <span class="text-xs text-slate-500">{{ $history->count() }} {{ Str::plural('update', $history->count()) }}</span>
    </div>

    <div x-data="pager(10)" data-pager>
    <ol class="mt-4 space-y-4">
        @forelse ($history->reverse() as $entry)
            @php
                $previous = $previousByEntry[$entry->id];
                $delta = $entry->progress_percentage - $previous;
                $finishedStage = $entry->phase && $entry->progress_percentage >= (int) round($entry->phase->sequence / $phaseCount * 100);
                $photos = $entry->projectDocuments->filter(fn ($doc) => str_starts_with((string) $doc->file_type, 'image/'));
                $files = $entry->projectDocuments->reject(fn ($doc) => str_starts_with((string) $doc->file_type, 'image/'));
                $done = $lines($entry->activities_completed);
                $next = $lines($entry->activities_remaining);
            @endphp
            <li data-page-item class="rounded-xl border border-slate-200 p-4">
                <div class="flex flex-wrap items-start justify-between gap-x-4 gap-y-2">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full chip-ink text-xs font-semibold text-white">{{ strtoupper(substr($entry->user->name, 0, 1)) }}</span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-slate-900">{{ $entry->user->name }}</p>
                            <p class="truncate text-xs text-slate-500">
                                {{ $positions[$entry->user_id] ?? 'Project Personnel' }} &middot; {{ $entry->progress_date->format('M d, Y') }}
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-2 text-xs">
                        <span class="font-semibold tabular-nums text-slate-900">{{ $previous }}% &rarr; {{ $entry->progress_percentage }}%</span>
                        @if ($delta > 0)
                            <span class="rounded-full bg-emerald-50 px-2 py-0.5 font-semibold text-emerald-700">+{{ $delta }}%</span>
                        @endif
                        @can('manageProgress', [$project, $entry])
                            <span class="mx-1 hidden h-4 w-px bg-slate-200 sm:block"></span>
                            <button type="button" @click="$dispatch('open-modal', 'progress-edit-{{ $entry->id }}')" class="inline-flex items-center gap-1 rounded-lg px-2 py-1 font-medium text-slate-500 transition-colors duration-150 hover:bg-slate-100 hover:text-brand-600">
                                <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                Edit
                            </button>
                            <button type="button" @click="$dispatch('open-modal', 'progress-delete-{{ $entry->id }}')" class="inline-flex items-center gap-1 rounded-lg px-2 py-1 font-medium text-slate-500 transition-colors duration-150 hover:bg-red-50 hover:text-red-600">
                                <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                                Delete
                            </button>
                        @endcan
                    </div>
                </div>

                @if ($entry->phase)
                    <p class="mt-3 inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-medium {{ $finishedStage ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-700' }}">
                        <i data-lucide="{{ $finishedStage ? 'circle-check-big' : 'hammer' }}" class="h-3.5 w-3.5"></i>
                        {{ $finishedStage ? 'Finished' : 'Working on' }}: {{ $entry->phase->name }}
                    </p>
                @endif

                <p class="mt-3 whitespace-pre-line text-sm text-slate-800">{{ $entry->accomplishments }}</p>

                @if ($done->isNotEmpty() || $next->isNotEmpty())
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        @if ($done->isNotEmpty())
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Tasks finished</p>
                                <ul class="mt-1 space-y-1 text-sm text-slate-700">
                                    @foreach ($done as $line)
                                        <li class="flex gap-2"><span class="mt-2 h-1 w-1 shrink-0 rounded-full bg-emerald-500"></span>{{ $line }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        @if ($next->isNotEmpty())
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Next tasks</p>
                                <ul class="mt-1 space-y-1 text-sm text-slate-700">
                                    @foreach ($next as $line)
                                        <li class="flex gap-2"><span class="mt-2 h-1 w-1 shrink-0 rounded-full bg-slate-400"></span>{{ $line }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                @endif

                @if ($entry->issues)
                    <div class="mt-3 flex gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                        <i data-lucide="triangle-alert" class="mt-0.5 h-4 w-4 shrink-0"></i>
                        <p class="whitespace-pre-line"><span class="font-semibold">Issue:</span> {{ $entry->issues }}</p>
                    </div>
                @endif

                @if ($photos->isNotEmpty())
                    <div class="mt-3 grid grid-cols-3 gap-2 sm:grid-cols-6">
                        @foreach ($photos as $doc)
                            @php $photoUrl = route('projects.documents.download', [$project, $doc, 'inline' => 1]); @endphp
                            <button type="button" @click="photo = @js($photoUrl)" class="aspect-square overflow-hidden rounded-lg bg-slate-100 ring-1 ring-slate-200 transition-opacity hover:opacity-90" aria-label="View site photo">
                                <img src="{{ $photoUrl }}" alt="Site photo from {{ $entry->progress_date->format('M d, Y') }}" loading="lazy" class="h-full w-full object-cover">
                            </button>
                        @endforeach
                    </div>
                @endif

                @if ($files->isNotEmpty())
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($files as $doc)
                            <a href="{{ route('projects.documents.download', [$project, $doc]) }}" data-turbo="false" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                <i data-lucide="file-text" class="h-3.5 w-3.5"></i>
                                {{ $doc->document_name }}
                            </a>
                        @endforeach
                    </div>
                @endif

                @if ($entry->ai_assisted)
                    <details class="group mt-3 text-xs text-slate-500">
                        <summary class="inline-flex cursor-pointer list-none items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 font-medium text-slate-600 hover:text-slate-900">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3 w-3" aria-hidden="true"><path d="M9.94 14.06 4 20M14 4l1.5 3.5L19 9l-3.5 1.5L14 14l-1.5-3.5L9 9l3.5-1.5Z"/></svg>
                            Written with AI assistance &middot; view original notes
                        </summary>
                        <p class="mt-2 whitespace-pre-line rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-700">{{ $entry->site_notes ?: 'No original notes recorded.' }}</p>
                    </details>
                @endif
            </li>
        @empty
            <li class="rounded-xl border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500">No progress updates yet.</li>
        @endforelse
    </ol>
    <x-pager-controls class="mt-4 !px-0" />
    </div>

    <div
        x-show="photo"
        x-transition.opacity
        @click="photo = null"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/85 p-4"
        style="display: none;"
        role="dialog"
        aria-modal="true"
        aria-label="Site photo"
    >
        <img :src="photo" alt="Site photo" class="max-h-full max-w-full rounded-lg object-contain shadow-2xl">
        <button type="button" @click="photo = null" class="absolute right-4 top-4 flex h-10 w-10 items-center justify-center rounded-full bg-white/15 text-white hover:bg-white/25" aria-label="Close photo">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" class="h-5 w-5" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
    </div>
</section>
