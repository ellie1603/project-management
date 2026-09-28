@php
    $canWriteProgress = auth()->user()->isProjectPersonnel() && $project->hasPersonnel(auth()->user());
    $phases = $project->phases;
    $completion = $project->completionPercentage();
    $manageableProgress = $project->progress->filter(fn ($entry) => auth()->user()->can('manageProgress', [$project, $entry]));
    $maxAttachments = 2;
    $input = 'w-full rounded-lg border-slate-300 text-base shadow-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 sm:text-sm';
@endphp

@if ($canWriteProgress)
    @php
        $isCreateError = old('_form') === 'progress-create';
        $formConfig = [
            'phases' => $phases->map(fn ($phase) => [
                'id' => (string) $phase->id,
                'sequence' => $phase->sequence,
                'name' => $phase->name,
                'status' => $phase->status,
            ])->values(),
            'phaseId' => (string) ($isCreateError ? old('phase_id') : $project->currentPhase()?->id),
            'phaseStatus' => $isCreateError ? old('phase_status', 'In Progress') : 'In Progress',
            'currentCompletion' => $completion,
            'date' => $isCreateError ? old('progress_date', now()->toDateString()) : now()->toDateString(),
            'today' => now()->toDateString(),
            'notes' => $isCreateError ? (old('site_notes') ?: old('accomplishments', '')) : '',
            'issues' => $isCreateError ? old('issues', '') : '',
            'aiEnabled' => $aiAssistEnabled ?? false,
            'assistUrl' => route('projects.progress.assist', $project),
        ];
    @endphp

    <x-modal name="progress-create" :show="$isCreateError && $errors->any()" max-width="2xl">
        <div x-data="progressUpdate(@js($formConfig))">
            <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-4 sm:px-6">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl chip-ink text-white shadow-sm">
                        <i data-lucide="clipboard-pen" class="h-[18px] w-[18px]"></i>
                    </span>
                    <div>
                        <h2 class="font-display text-lg font-semibold text-slate-900">Update Project Progress</h2>
                        <p class="mt-0.5 text-sm text-slate-500">Pick the stage status, add up to {{ $maxAttachments }} photos, and write a short note.</p>
                    </div>
                </div>
                <button type="button" x-on:click="closeCamera(); $dispatch('close')" class="shrink-0 rounded-lg p-1.5 text-slate-400 transition-colors duration-150 hover:bg-slate-100 hover:text-slate-700" aria-label="Close">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('projects.progress.store', $project) }}" enctype="multipart/form-data" class="space-y-6 px-5 py-5 sm:px-6" @submit="submit($event)">
                @csrf
                <input type="hidden" name="_form" value="progress-create">
                <input type="hidden" name="phase_id" :value="phaseId">
                <input type="hidden" name="phase_status" :value="phaseStatus">
                <input type="hidden" name="accomplishments" :value="accomplishments">
                <input type="hidden" name="activities_completed" :value="draft ? draft.activities_completed : ''">
                <input type="hidden" name="activities_remaining" :value="draft ? draft.activities_remaining : ''">
                <input type="hidden" name="issues" :value="issuesValue">
                <input type="hidden" name="ai_assisted" :value="draft ? 1 : 0">
                <input type="hidden" name="site_notes" :value="draft ? notes : ''">
                <input type="file" name="files[]" multiple class="hidden" x-ref="files" tabindex="-1" aria-hidden="true">

                @if ($isCreateError && $errors->any())
                    <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2.5 text-sm text-red-700">
                        <p class="font-medium">Your update was not saved.</p>
                        <ul class="mt-1 list-disc space-y-0.5 pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

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
                    <select x-show="changingStage" x-model="phaseId" class="mt-2 {{ $input }}" style="display: none;" aria-label="Construction stage">
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
                                class="flex min-h-[4rem] flex-col items-start justify-center rounded-xl border px-3.5 py-3 text-left transition-colors duration-150"
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
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm font-semibold text-slate-900">
                            <span class="mr-1.5 inline-flex h-5 w-5 items-center justify-center rounded-full bg-brand-600 text-[11px] font-semibold text-white">2</span>
                            Site photos &amp; reports
                        </p>
                        <span class="text-xs font-medium tabular-nums text-slate-500" x-text="attachments.length + ' / ' + maxAttachments"></span>
                    </div>

                    <div class="mt-3 grid grid-cols-2 gap-2.5">
                        <button type="button" @click="takePhoto()" :disabled="isFull" class="flex min-h-[3rem] items-center justify-center gap-2 rounded-xl bg-brand-600 px-3 text-sm font-semibold text-white shadow-sm transition-colors duration-150 hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-50">
                            <i data-lucide="camera" class="h-5 w-5"></i>
                            Take photo
                        </button>
                        {{-- Phones: opens the native camera app. Desktops use the in-page camera below. --}}
                        <input type="file" accept="image/*" capture="environment" class="hidden" x-ref="cameraInput" tabindex="-1" aria-hidden="true" @change="addFiles($event.target.files); $event.target.value = ''">
                        <label :class="isFull ? 'pointer-events-none opacity-50' : 'cursor-pointer hover:bg-slate-50'" class="flex min-h-[3rem] items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-700 transition-colors duration-150">
                            <i data-lucide="upload" class="h-5 w-5"></i>
                            Upload files
                            <input type="file" accept="image/*,.heic,.heif,.pdf,.doc,.docx,.xls,.xlsx" multiple class="sr-only" :disabled="isFull" @change="addFiles($event.target.files); $event.target.value = ''">
                        </label>
                    </div>

                    <p class="mt-2 text-xs text-slate-500">Up to {{ $maxAttachments }} files, 5 MB each. Photos are shrunk automatically before saving.</p>
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

                    <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-4" x-show="attachments.length > 0" style="display: none;">
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
                        class="mt-2 {{ $input }}"
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
                                    <textarea id="draft-summary" x-model="draft.accomplishments" rows="2" class="{{ $input }}"></textarea>
                                </div>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <label for="draft-done" class="mb-1 block text-xs font-medium text-slate-600">Tasks finished</label>
                                        <textarea id="draft-done" x-model="draft.activities_completed" rows="3" class="{{ $input }}"></textarea>
                                    </div>
                                    <div>
                                        <label for="draft-next" class="mb-1 block text-xs font-medium text-slate-600">Next tasks</label>
                                        <textarea id="draft-next" x-model="draft.activities_remaining" rows="3" class="{{ $input }}"></textarea>
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
                        <textarea x-model="issueText" rows="2" maxlength="2000" placeholder="Describe the problem (optional)" aria-label="Problem details" class="{{ $input }}"></textarea>
                    </div>
                </div>

                {{-- Date + save --}}
                <div class="border-t border-slate-100 pt-4">
                    <div class="flex items-center justify-between gap-3 text-sm">
                        <span class="text-slate-500">Update date: <span class="font-medium text-slate-900" x-text="dateLabel"></span></span>
                        <button type="button" @click="changingDate = ! changingDate" class="text-xs font-medium text-slate-500 underline-offset-2 hover:text-slate-900 hover:underline" x-show="! changingDate">Change</button>
                    </div>
                    <input type="date" name="progress_date" x-model="date" :max="today" x-show="changingDate" style="display: none;" class="mt-2 {{ $input }} sm:w-56" required aria-label="Update date">

                    <p class="mt-3 text-sm font-medium text-red-600" x-show="formError" x-text="formError" style="display: none;"></p>

                    <div class="mt-4 flex flex-col-reverse gap-2.5 sm:flex-row sm:justify-end">
                        <button type="button" x-on:click="closeCamera(); $dispatch('close')" class="rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-medium text-slate-700 shadow-sm transition-colors duration-150 hover:bg-slate-50">Cancel</button>
                        <button type="submit" :disabled="submitting || processing > 0" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition-all duration-150 hover:bg-brand-700 active:scale-[0.99] disabled:opacity-60">
                            <i data-lucide="check" class="h-4 w-4"></i>
                            <span x-text="submitting ? 'Saving...' : 'Save update'">Save update</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </x-modal>
@endif

@foreach ($manageableProgress as $entry)
    @php
        $formKey = 'progress-edit-'.$entry->id;
        $isEditError = old('_form') === $formKey;
        $value = fn (string $field, $default) => $isEditError ? old($field, $default) : $default;
        $existingDocs = $entry->projectDocuments;
    @endphp

    <x-modal :name="$formKey" :show="$isEditError && $errors->any()" max-width="2xl">
        <form
            method="POST"
            action="{{ route('projects.progress.update', [$project, $entry]) }}"
            enctype="multipart/form-data"
            x-data="{
                existing: {{ $existingDocs->count() }},
                removed: @js(array_map('strval', $isEditError ? old('remove_documents', []) : [])),
                newCount: 0,
                error: '',
                get slots() { return {{ $maxAttachments }} - (this.existing - this.removed.length) },
                pick(event) {
                    this.error = '';
                    const files = Array.from(event.target.files);
                    if (files.length > this.slots) {
                        this.error = this.slots > 0 ? `You can add ${this.slots} more file${this.slots === 1 ? '' : 's'}.` : 'Remove an attachment first. The limit is {{ $maxAttachments }}.';
                        event.target.value = '';
                    } else if (files.some((file) => file.size > 5 * 1024 * 1024)) {
                        this.error = 'Each file must be 5 MB or smaller.';
                        event.target.value = '';
                    }
                    this.newCount = event.target.files.length;
                },
            }"
        >
            @csrf
            @method('PUT')
            <input type="hidden" name="_form" value="{{ $formKey }}">

            <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-4 sm:px-6">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl chip-ink text-white shadow-sm">
                        <i data-lucide="pencil" class="h-[18px] w-[18px]"></i>
                    </span>
                    <div>
                        <h2 class="font-display text-lg font-semibold text-slate-900">Edit progress update</h2>
                        <p class="mt-0.5 text-sm text-slate-500">
                            {{ $entry->phase ? $entry->phase->name.' · ' : '' }}completion {{ $entry->progress_percentage }}%
                        </p>
                    </div>
                </div>
                <button type="button" x-on:click="$dispatch('close')" class="shrink-0 rounded-lg p-1.5 text-slate-400 transition-colors duration-150 hover:bg-slate-100 hover:text-slate-700" aria-label="Close">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>

            <div class="space-y-4 px-5 py-5 sm:px-6">
                @if ($isEditError && $errors->any())
                    <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2.5 text-sm text-red-700">
                        <ul class="list-disc space-y-0.5 pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div>
                    <label for="edit-date-{{ $entry->id }}" class="mb-1.5 block text-sm font-medium text-slate-700">Update date</label>
                    <input id="edit-date-{{ $entry->id }}" type="date" name="progress_date" max="{{ now()->toDateString() }}" value="{{ $value('progress_date', $entry->progress_date->toDateString()) }}" class="{{ $input }} sm:w-56" required>
                </div>

                <div>
                    <label for="edit-accomplishments-{{ $entry->id }}" class="mb-1.5 block text-sm font-medium text-slate-700">What was done</label>
                    <textarea id="edit-accomplishments-{{ $entry->id }}" name="accomplishments" rows="3" maxlength="5000" class="{{ $input }}" required>{{ $value('accomplishments', $entry->accomplishments) }}</textarea>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="edit-done-{{ $entry->id }}" class="mb-1.5 block text-sm font-medium text-slate-700">Tasks finished <span class="font-normal text-slate-400">(optional)</span></label>
                        <textarea id="edit-done-{{ $entry->id }}" name="activities_completed" rows="3" maxlength="5000" class="{{ $input }}">{{ $value('activities_completed', $entry->activities_completed) }}</textarea>
                    </div>
                    <div>
                        <label for="edit-next-{{ $entry->id }}" class="mb-1.5 block text-sm font-medium text-slate-700">Next tasks <span class="font-normal text-slate-400">(optional)</span></label>
                        <textarea id="edit-next-{{ $entry->id }}" name="activities_remaining" rows="3" maxlength="5000" class="{{ $input }}">{{ $value('activities_remaining', $entry->activities_remaining) }}</textarea>
                    </div>
                </div>

                <div>
                    <label for="edit-issues-{{ $entry->id }}" class="mb-1.5 block text-sm font-medium text-slate-700">Problems or delays <span class="font-normal text-slate-400">(leave blank if none)</span></label>
                    <textarea id="edit-issues-{{ $entry->id }}" name="issues" rows="2" maxlength="5000" class="{{ $input }}">{{ $value('issues', $entry->issues) }}</textarea>
                </div>

                <div>
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm font-medium text-slate-700">Attachments</p>
                        <span class="text-xs tabular-nums text-slate-500" x-text="(existing - removed.length + newCount) + ' / {{ $maxAttachments }}'"></span>
                    </div>

                    @if ($existingDocs->isNotEmpty())
                        <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-4">
                            @foreach ($existingDocs as $doc)
                                @php $isPhoto = str_starts_with((string) $doc->file_type, 'image/'); @endphp
                                <label class="group relative block aspect-square cursor-pointer overflow-hidden rounded-lg bg-slate-100 ring-1 ring-slate-200" :class="removed.includes('{{ $doc->id }}') ? 'opacity-40 ring-red-300' : ''">
                                    <input type="checkbox" name="remove_documents[]" value="{{ $doc->id }}" x-model="removed" class="sr-only">
                                    @if ($isPhoto)
                                        <img src="{{ route('projects.documents.download', [$project, $doc, 'inline' => 1]) }}" alt="Attached photo" loading="lazy" class="h-full w-full object-cover">
                                    @else
                                        <span class="flex h-full w-full flex-col items-center justify-center gap-1 p-2 text-center">
                                            <i data-lucide="file-text" class="h-6 w-6 text-slate-500"></i>
                                            <span class="line-clamp-2 break-all text-[10px] font-medium text-slate-600">{{ $doc->document_name }}</span>
                                        </span>
                                    @endif
                                    <span class="absolute inset-x-1 bottom-1 rounded bg-black/65 px-1.5 py-0.5 text-center text-[10px] font-semibold text-white" x-text="removed.includes('{{ $doc->id }}') ? 'Will be removed · undo' : 'Tap to remove'"></span>
                                </label>
                            @endforeach
                        </div>
                    @endif

                    <label x-show="slots > 0" class="mt-2 flex min-h-[2.75rem] cursor-pointer items-center justify-center gap-2 rounded-xl border border-dashed border-slate-300 bg-white px-3 text-sm font-medium text-slate-600 transition-colors duration-150 hover:bg-slate-50">
                        <i data-lucide="upload" class="h-4 w-4"></i>
                        <span x-text="newCount > 0 ? newCount + ' file' + (newCount === 1 ? '' : 's') + ' selected' : 'Add photo or report'"></span>
                        <input type="file" name="files[]" accept="image/*,.heic,.heif,.pdf,.doc,.docx,.xls,.xlsx" multiple class="sr-only" @change="pick($event)">
                    </label>
                    <p class="mt-1.5 text-xs text-slate-500">Up to {{ $maxAttachments }} files, 5 MB each. The construction stage can't be changed here; delete this update and submit a new one instead.</p>
                    <p class="mt-1.5 text-xs font-medium text-red-600" x-show="error" x-text="error" style="display: none;"></p>
                </div>
            </div>

            <div class="flex flex-col-reverse gap-2.5 border-t border-slate-100 bg-slate-50/80 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                <button type="button" x-on:click="$dispatch('close')" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition-colors duration-150 hover:bg-slate-50">Cancel</button>
                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-150 hover:bg-brand-700">
                    <i data-lucide="check" class="h-4 w-4"></i>
                    Save changes
                </button>
            </div>
        </form>
    </x-modal>

    <x-modal :name="'progress-delete-'.$entry->id" max-width="md">
            <form method="POST" action="{{ route('projects.progress.destroy', [$project, $entry]) }}" class="px-6 pb-6 pt-7">
                @csrf
                @method('DELETE')

                <div class="flex items-start gap-4">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-red-50 text-red-600">
                        <i data-lucide="triangle-alert" class="h-5 w-5"></i>
                    </span>
                    <div>
                        <h2 class="font-display text-lg font-semibold text-slate-900">Delete this progress update?</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            The update from {{ $entry->progress_date->format('M d, Y') }} and its attachments will be removed.
                            Any stage it marked will be reverted and completion recalculated from the remaining updates. This cannot be undone.
                        </p>
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-end gap-3">
                    <button type="button" x-on:click="$dispatch('close')" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition-colors duration-150 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-150 hover:bg-red-700">
                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                        Delete Update
                    </button>
                </div>
            </form>
        </x-modal>
@endforeach
