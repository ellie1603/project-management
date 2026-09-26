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
    @php
        $formConfig = [
            'phases' => $phases->map(fn ($phase) => [
                'id' => (string) $phase->id,
                'sequence' => $phase->sequence,
                'name' => $phase->name,
                'status' => $phase->status,
            ])->values(),
            'phaseId' => (string) old('phase_id', $project->currentPhase()?->id),
            'phaseStatus' => old('phase_status', 'In Progress'),
            'currentCompletion' => $completion,
            'date' => old('progress_date', now()->toDateString()),
            'today' => now()->toDateString(),
            'notes' => old('site_notes') ?: old('accomplishments', ''),
            'issues' => old('issues', ''),
            'aiEnabled' => $aiAssistEnabled ?? false,
            'assistUrl' => route('projects.progress.assist', $project),
        ];
    @endphp

    <section class="mt-6 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5" x-data="progressUpdate(@js($formConfig))">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Update Project Progress</h2>
            <p class="mt-0.5 text-sm text-slate-500">Tap the stage status, add site photos, and write a short note.</p>
        </div>

        @if ($errors->any())
            <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2.5 text-sm text-red-700">
                <p class="font-medium">Your update was not saved.</p>
                <ul class="mt-1 list-disc space-y-0.5 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('projects.progress.store', $project) }}" enctype="multipart/form-data" class="mt-5 space-y-6" @submit="submit($event)">
            @csrf
            <input type="hidden" name="phase_id" :value="phaseId">
            <input type="hidden" name="phase_status" :value="phaseStatus">
            <input type="hidden" name="accomplishments" :value="accomplishments">
            <input type="hidden" name="activities_completed" :value="draft ? draft.activities_completed : ''">
            <input type="hidden" name="activities_remaining" :value="draft ? draft.activities_remaining : ''">
            <input type="hidden" name="issues" :value="issuesValue">
            <input type="hidden" name="ai_assisted" :value="draft ? 1 : 0">
            <input type="hidden" name="site_notes" :value="draft ? notes : ''">
            <input type="file" name="files[]" multiple class="hidden" x-ref="files" tabindex="-1" aria-hidden="true">

            {{-- 1. Stage --}}
            <div>
                <div class="flex items-center justify-between gap-3">
                    <p class="text-sm font-semibold text-slate-900">
                        <span class="mr-1.5 inline-flex h-5 w-5 items-center justify-center rounded-full bg-brand-600 text-[11px] font-semibold text-white">1</span>
                        Construction stage
                    </p>
                    <button type="button" @click="changingStage = ! changingStage" class="text-xs font-medium text-slate-500 underline-offset-2 hover:text-slate-900 hover:underline" x-text="changingStage ? 'Done' : 'Change stage'"></button>
                </div>

                <p class="mt-2 text-[15px] font-medium text-slate-900" x-show="! changingStage">
                    <span x-text="phase ? phase.sequence + '. ' + phase.name : 'Select a stage'"></span>
                </p>
                <select x-show="changingStage" x-model="phaseId" class="mt-2 w-full rounded-lg border-slate-300 text-base shadow-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 sm:text-sm" style="display: none;" aria-label="Construction stage">
                    @foreach ($phases as $phase)
                        <option value="{{ $phase->id }}">{{ $phase->sequence }}. {{ $phase->name }} ({{ $phase->status }})</option>
                    @endforeach
                </select>

                <div class="mt-3 grid grid-cols-2 gap-2.5" role="radiogroup" aria-label="Stage status">
                    @foreach ([
                        ['value' => 'In Progress', 'icon' => 'hammer', 'title' => 'Still working', 'hint' => 'Work continues on this stage'],
                        ['value' => 'Completed', 'icon' => 'circle-check-big', 'title' => 'Stage finished', 'hint' => 'Marks this stage complete'],
                    ] as $option)
                        <button
                            type="button"
                            role="radio"
                            @click="phaseStatus = '{{ $option['value'] }}'"
                            :aria-checked="phaseStatus === '{{ $option['value'] }}'"
                            :class="phaseStatus === '{{ $option['value'] }}' ? 'border-brand-600 bg-brand-600 text-white shadow-sm' : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300'"
                            class="flex min-h-[4.5rem] flex-col items-start justify-center rounded-xl border px-3.5 py-3 text-left transition-colors duration-150"
                        >
                            <span class="flex items-center gap-2 text-sm font-semibold">
                                <i data-lucide="{{ $option['icon'] }}" class="h-4 w-4"></i>
                                {{ $option['title'] }}
                            </span>
                            <span class="mt-0.5 text-xs opacity-70">{{ $option['hint'] }}</span>
                        </button>
                    @endforeach
                </div>

                <div class="mt-3 flex items-center gap-3 text-xs text-slate-500">
                    <span class="shrink-0">Completion</span>
                    <div class="relative h-1.5 flex-1 overflow-hidden rounded-full bg-slate-100">
                        <div class="absolute inset-y-0 left-0 rounded-full bg-emerald-500 transition-all duration-300" :style="`width: ${newCompletion}%`"></div>
                        <div class="absolute inset-y-0 left-0 rounded-full bg-slate-900" :style="`width: ${currentCompletion}%`"></div>
                    </div>
                    <span class="shrink-0 font-semibold tabular-nums text-slate-900">
                        <span x-text="currentCompletion + '%'"></span>
                        <template x-if="newCompletion !== currentCompletion">
                            <span class="text-emerald-600"> &rarr; <span x-text="newCompletion + '%'"></span></span>
                        </template>
                    </span>
                </div>
            </div>

            {{-- 2. Photos & reports --}}
            <div>
                <p class="text-sm font-semibold text-slate-900">
                    <span class="mr-1.5 inline-flex h-5 w-5 items-center justify-center rounded-full bg-brand-600 text-[11px] font-semibold text-white">2</span>
                    Site photos &amp; reports
                </p>

                <div class="mt-3 grid grid-cols-2 gap-2.5">
                    <button type="button" @click="takePhoto()" class="flex min-h-[3.25rem] items-center justify-center gap-2 rounded-xl bg-brand-600 px-3 text-sm font-semibold text-white shadow-sm transition-colors duration-150 hover:bg-brand-700">
                        <i data-lucide="camera" class="h-5 w-5"></i>
                        Take photo
                    </button>
                    {{-- Phones: opens the native camera app. Desktops use the in-page camera below. --}}
                    <input type="file" accept="image/*" capture="environment" class="hidden" x-ref="cameraInput" tabindex="-1" aria-hidden="true" @change="addFiles($event.target.files); $event.target.value = ''">
                    <label class="flex min-h-[3.25rem] cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-700 transition-colors duration-150 hover:bg-slate-50">
                        <i data-lucide="upload" class="h-5 w-5"></i>
                        Upload files
                        <input type="file" accept="image/*,.heic,.heif,.pdf,.doc,.docx,.xls,.xlsx" multiple class="sr-only" @change="addFiles($event.target.files); $event.target.value = ''">
                    </label>
                </div>

                <p class="mt-2 text-xs text-slate-500">Photos are resized on your phone before uploading. Up to 10 files, PDF / Word / Excel reports allowed.</p>
                <p class="mt-2 text-xs font-medium text-red-600" x-show="fileError" x-text="fileError" style="display: none;"></p>
                <p class="mt-2 flex items-center gap-1.5 text-xs text-slate-500" x-show="processing > 0" style="display: none;">
                    <i data-lucide="loader-circle" class="h-3.5 w-3.5 animate-spin"></i>
                    Preparing photos...
                </p>

                {{-- In-page webcam for laptops/desktops --}}
                <template x-teleport="body">
                    <div
                        x-show="cameraOpen"
                        x-transition.opacity
                        @keydown.escape.window="cameraOpen && closeCamera()"
                        class="palette-fixed fixed inset-0 z-[90] flex flex-col bg-black"
                        style="display: none;"
                        role="dialog"
                        aria-modal="true"
                        aria-label="Camera"
                    >
                        <div class="relative min-h-0 flex-1">
                            <video x-init="registerVideo($el)" playsinline muted autoplay class="h-full w-full object-contain" :class="facingMode === 'user' ? '-scale-x-100' : ''"></video>
                            <div x-show="flash" class="pointer-events-none absolute inset-0 bg-white/70"></div>
                            <div x-show="cameraError" class="absolute inset-0 flex items-center justify-center p-6" style="display: none;">
                                <div class="max-w-sm rounded-2xl bg-white/10 p-5 text-center text-sm text-white backdrop-blur">
                                    <p x-text="cameraError"></p>
                                    <button type="button" @click="startCamera()" class="mt-4 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-900">Try again</button>
                                </div>
                            </div>
                            <p x-show="shotsTaken > 0" class="absolute left-4 top-4 rounded-full bg-black/60 px-3 py-1 text-xs font-semibold text-white" x-text="shotsTaken + (shotsTaken === 1 ? ' photo added' : ' photos added')" style="display: none;"></p>
                        </div>

                        <div class="grid grid-cols-3 items-center bg-black px-6 py-5">
                            <button type="button" @click="closeCamera()" class="justify-self-start rounded-lg px-3 py-2 text-sm font-semibold text-white hover:bg-white/10" x-text="shotsTaken > 0 ? 'Done' : 'Cancel'"></button>
                            <button type="button" @click="capturePhoto()" :disabled="cameraError !== ''" class="justify-self-center flex h-16 w-16 items-center justify-center rounded-full border-4 border-white/40 bg-white transition-transform active:scale-95 disabled:opacity-40" aria-label="Capture photo">
                                <span class="h-12 w-12 rounded-full border-2 border-black/10 bg-white"></span>
                            </button>
                            <button type="button" x-show="cameraCount > 1" @click="switchCamera()" class="justify-self-end flex h-11 w-11 items-center justify-center rounded-full text-white hover:bg-white/10" aria-label="Switch camera" style="display: none;">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6" aria-hidden="true"><path d="M11 19H4a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h5"/><path d="M13 5h7a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-5"/><circle cx="12" cy="12" r="3"/><path d="m18 22-3-3 3-3"/><path d="m6 2 3 3-3 3"/></svg>
                            </button>
                        </div>
                    </div>
                </template>

                <div class="mt-3 grid grid-cols-3 gap-2 sm:grid-cols-5" x-show="attachments.length > 0" style="display: none;">
                    <template x-for="attachment in attachments" :key="attachment.key">
                        <div class="relative aspect-square overflow-hidden rounded-lg bg-slate-100 ring-1 ring-slate-200">
                            <template x-if="attachment.isImage">
                                <img :src="attachment.url" :alt="attachment.name" class="h-full w-full object-cover" x-on:error="$el.remove()">
                            </template>
                            <div class="flex h-full w-full flex-col items-center justify-center gap-1 p-2 text-center" x-show="! attachment.isImage">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6 text-slate-500" aria-hidden="true"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/></svg>
                                <span class="line-clamp-2 break-all text-[10px] font-medium text-slate-600" x-text="attachment.name"></span>
                            </div>
                            <span class="absolute bottom-1 left-1 rounded bg-black/60 px-1 text-[10px] font-medium text-white" x-text="attachment.size"></span>
                            <button type="button" @click="removeAttachment(attachment.key)" class="absolute right-1 top-1 flex h-7 w-7 items-center justify-center rounded-full bg-black/60 text-white hover:bg-black/80" :aria-label="'Remove ' + attachment.name">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" class="h-3.5 w-3.5" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            {{-- 3. What was done --}}
            <div>
                <label for="progress-notes" class="text-sm font-semibold text-slate-900">
                    <span class="mr-1.5 inline-flex h-5 w-5 items-center justify-center rounded-full bg-brand-600 text-[11px] font-semibold text-white">3</span>
                    What was done?
                </label>

                <textarea
                    id="progress-notes"
                    x-ref="notes"
                    x-model="notes"
                    rows="3"
                    maxlength="2000"
                    :readonly="draft !== null"
                    :class="draft ? 'opacity-60' : ''"
                    placeholder="e.g. Natapos na ang roofing sa east side. 6 workers. Waiting pa sa tiles."
                    class="mt-2 w-full rounded-lg border-slate-300 text-base shadow-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 sm:text-sm"
                ></textarea>
                <p class="mt-1 text-xs text-slate-500">Write in English, Tagalog, or Hiligaynon. Short notes are fine.</p>

                <template x-if="aiEnabled && ! draft">
                    <button type="button" @click="assist()" :disabled="aiLoading" class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-800 shadow-sm transition-colors duration-150 hover:bg-slate-50 disabled:opacity-60 sm:w-auto">
                        <svg x-show="! aiLoading" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M9.94 14.06 4 20M14 4l1.5 3.5L19 9l-3.5 1.5L14 14l-1.5-3.5L9 9l3.5-1.5Z"/></svg>
                        <svg x-show="aiLoading" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="h-4 w-4 animate-spin" aria-hidden="true" style="display: none;"><path d="M21 12a9 9 0 1 1-6.22-8.56"/></svg>
                        <span x-text="aiLoading ? 'Writing your report...' : (photoCount > 0 ? 'Write report with AI (uses your notes and photos)' : 'Write report with AI')"></span>
                    </button>
                </template>
                <p class="mt-2 text-xs font-medium text-red-600" x-show="aiError" x-text="aiError" style="display: none;"></p>

                {{-- AI draft: personnel review and edit before it is saved --}}
                <div x-show="draft" style="display: none;" class="mt-3 rounded-xl border border-slate-200 bg-slate-50 p-3.5 sm:p-4">
                    <div class="flex items-start justify-between gap-3">
                        <p class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-slate-600">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5" aria-hidden="true"><path d="M9.94 14.06 4 20M14 4l1.5 3.5L19 9l-3.5 1.5L14 14l-1.5-3.5L9 9l3.5-1.5Z"/></svg>
                            AI draft &middot; check and edit before saving
                        </p>
                        <button type="button" @click="discardDraft()" class="shrink-0 text-xs font-medium text-slate-500 underline-offset-2 hover:text-slate-900 hover:underline">Use my own words</button>
                    </div>
                    <template x-if="draft">
                        <div class="mt-3 space-y-3">
                            <div>
                                <label for="draft-summary" class="mb-1 block text-xs font-medium text-slate-600">Summary</label>
                                <textarea id="draft-summary" x-model="draft.accomplishments" rows="2" class="w-full rounded-lg border-slate-300 text-base shadow-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 sm:text-sm"></textarea>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label for="draft-done" class="mb-1 block text-xs font-medium text-slate-600">Tasks finished</label>
                                    <textarea id="draft-done" x-model="draft.activities_completed" rows="3" class="w-full rounded-lg border-slate-300 text-base shadow-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 sm:text-sm"></textarea>
                                </div>
                                <div>
                                    <label for="draft-next" class="mb-1 block text-xs font-medium text-slate-600">Next tasks</label>
                                    <textarea id="draft-next" x-model="draft.activities_remaining" rows="3" class="w-full rounded-lg border-slate-300 text-base shadow-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 sm:text-sm"></textarea>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- 4. Problems --}}
            <div>
                <p class="text-sm font-semibold text-slate-900">
                    <span class="mr-1.5 inline-flex h-5 w-5 items-center justify-center rounded-full bg-brand-600 text-[11px] font-semibold text-white">4</span>
                    Any problems or delays?
                </p>
                <div class="mt-3 grid grid-cols-2 gap-2.5">
                    <button type="button" @click="hasIssue = false" :class="! hasIssue ? 'border-brand-600 bg-brand-600 text-white' : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300'" class="min-h-[3rem] rounded-xl border px-3 text-sm font-semibold transition-colors duration-150">No problems</button>
                    <button type="button" @click="hasIssue = true" :class="hasIssue ? 'border-amber-500 bg-amber-50 text-amber-800' : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300'" class="min-h-[3rem] rounded-xl border px-3 text-sm font-semibold transition-colors duration-150">Yes, report a problem</button>
                </div>

                <div x-show="hasIssue" style="display: none;" class="mt-3 space-y-2.5">
                    <div class="flex flex-wrap gap-2">
                        <template x-for="option in issueOptions" :key="option">
                            <button type="button" @click="toggleIssueTag(option)" :class="issueTags.includes(option) ? 'border-amber-500 bg-amber-100 text-amber-800' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'" class="rounded-full border px-3 py-1.5 text-xs font-medium transition-colors duration-150" x-text="option"></button>
                        </template>
                    </div>
                    <textarea x-model="issueText" rows="2" maxlength="2000" placeholder="Describe the problem (optional)" aria-label="Problem details" class="w-full rounded-lg border-slate-300 text-base shadow-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 sm:text-sm"></textarea>
                </div>
            </div>

            {{-- Date + save --}}
            <div class="border-t border-slate-100 pt-4">
                <div class="flex items-center justify-between gap-3 text-sm">
                    <span class="text-slate-500">Update date: <span class="font-medium text-slate-900" x-text="dateLabel"></span></span>
                    <button type="button" @click="changingDate = ! changingDate" class="text-xs font-medium text-slate-500 underline-offset-2 hover:text-slate-900 hover:underline" x-show="! changingDate">Change</button>
                </div>
                <input type="date" name="progress_date" x-model="date" :max="today" x-show="changingDate" style="display: none;" class="mt-2 w-full rounded-lg border-slate-300 text-base shadow-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 sm:w-56 sm:text-sm" required aria-label="Update date">

                <p class="mt-3 text-sm font-medium text-red-600" x-show="formError" x-text="formError" style="display: none;"></p>

                <button type="submit" :disabled="submitting || processing > 0" class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-brand-600 px-5 py-3.5 text-sm font-semibold text-white shadow-sm transition-all duration-150 hover:bg-brand-700 active:scale-[0.99] disabled:opacity-60 sm:w-auto">
                    <span x-text="submitting ? 'Saving...' : 'Save update'">Save update</span>
                </button>
            </div>
        </form>
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
            <li class="rounded-xl border border-slate-200 p-4">
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
                    <div class="flex items-center gap-2 text-xs">
                        <span class="font-semibold tabular-nums text-slate-900">{{ $previous }}% &rarr; {{ $entry->progress_percentage }}%</span>
                        @if ($delta > 0)
                            <span class="rounded-full bg-emerald-50 px-2 py-0.5 font-semibold text-emerald-700">+{{ $delta }}%</span>
                        @endif
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
