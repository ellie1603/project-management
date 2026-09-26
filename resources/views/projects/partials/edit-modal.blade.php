@php
    $categories = \App\Models\ProjectCategory::query()->orderBy('name')->get();
@endphp

<x-modal name="edit-project" max-width="2xl">
    <form action="{{ route('projects.update', $project) }}" method="POST" class="flex max-h-[85vh] flex-col">
        @csrf
        @method('PUT')

        <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl chip-ink text-white shadow-sm">
                    <i data-lucide="pencil" class="h-[18px] w-[18px]"></i>
                </span>
                <div>
                    <h2 class="font-display text-lg font-semibold text-slate-900">Edit Project</h2>
                    <p class="mt-0.5 text-sm text-slate-500">Update project information and timeline.</p>
                </div>
            </div>
            <button type="button" @click="$dispatch('close-modal', 'edit-project')" class="shrink-0 rounded-lg p-1.5 text-slate-400 outline-none transition-colors duration-150 hover:bg-slate-100 hover:text-slate-700 focus-visible:ring-2 focus-visible:ring-brand-200" aria-label="Close">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto px-6 py-5">
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-slate-400">
                <i data-lucide="file-text" class="h-3.5 w-3.5"></i>
                Project Information
            </div>
            <div class="mt-4 grid gap-5 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label for="modal-project-title" class="mb-1.5 block text-sm font-medium text-slate-700">Title</label>
                    <input id="modal-project-title" name="title" value="{{ old('title', $project->title) }}" class="w-full rounded-xl border-slate-200 bg-slate-50/60 shadow-sm transition-all duration-150 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100" required>
                </div>
                <div class="md:col-span-2">
                    <label for="modal-project-description" class="mb-1.5 block text-sm font-medium text-slate-700">Description</label>
                    <textarea id="modal-project-description" name="description" rows="2" class="w-full rounded-xl border-slate-200 bg-slate-50/60 shadow-sm transition-all duration-150 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100">{{ old('description', $project->description) }}</textarea>
                </div>
                <div class="md:col-span-2">
                    <label for="modal-project-objective" class="mb-1.5 block text-sm font-medium text-slate-700">Objective</label>
                    <textarea id="modal-project-objective" name="objective" rows="2" class="w-full rounded-xl border-slate-200 bg-slate-50/60 shadow-sm transition-all duration-150 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100">{{ old('objective', $project->objective) }}</textarea>
                </div>
                <div>
                    <label for="modal-project-category" class="mb-1.5 block text-sm font-medium text-slate-700">Category</label>
                    <select id="modal-project-category" name="category_id" class="w-full rounded-xl border-slate-200 bg-slate-50/60 shadow-sm transition-all duration-150 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100">
                        <option value="">Uncategorized</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id', $project->category_id) == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="modal-project-type" class="mb-1.5 block text-sm font-medium text-slate-700">Project Type</label>
                    <input id="modal-project-type" name="project_type" value="{{ old('project_type', $project->project_type) }}" class="w-full rounded-xl border-slate-200 bg-slate-50/60 shadow-sm transition-all duration-150 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100">
                </div>
                <div>
                    <label for="modal-project-location" class="mb-1.5 block text-sm font-medium text-slate-700">Location</label>
                    <input id="modal-project-location" name="location" value="{{ old('location', $project->location) }}" class="w-full rounded-xl border-slate-200 bg-slate-50/60 shadow-sm transition-all duration-150 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100">
                </div>
                <div>
                    <label for="modal-project-status" class="mb-1.5 block text-sm font-medium text-slate-700">Status</label>
                    <select id="modal-project-status" name="status" class="w-full rounded-xl border-slate-200 bg-slate-50/60 shadow-sm transition-all duration-150 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100">
                        @foreach (\App\Models\Project::STATUSES as $status)
                            <option value="{{ $status }}" @selected(old('status', $project->status) === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-6 flex items-center gap-2 border-t border-slate-100 pt-5 text-xs font-semibold uppercase tracking-wider text-slate-400">
                <i data-lucide="calendar-range" class="h-3.5 w-3.5"></i>
                Timeline
            </div>
            <div class="mt-4 grid gap-5 md:grid-cols-2">
                <div>
                    <label for="modal-planned-start" class="mb-1.5 block text-sm font-medium text-slate-700">Planned Start Date</label>
                    <input id="modal-planned-start" type="date" name="planned_start_date" value="{{ old('planned_start_date', $project->planned_start_date) }}" class="w-full rounded-xl border-slate-200 bg-slate-50/60 shadow-sm transition-all duration-150 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100" required>
                </div>
                <div>
                    <label for="modal-target-completion" class="mb-1.5 block text-sm font-medium text-slate-700">Target Completion Date</label>
                    <input id="modal-target-completion" type="date" name="target_completion_date" value="{{ old('target_completion_date', $project->target_completion_date) }}" class="w-full rounded-xl border-slate-200 bg-slate-50/60 shadow-sm transition-all duration-150 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100" required>
                </div>
                <div>
                    <label for="modal-actual-start" class="mb-1.5 block text-sm font-medium text-slate-700">Actual Start Date</label>
                    <input id="modal-actual-start" type="date" name="actual_start_date" value="{{ old('actual_start_date', $project->actual_start_date) }}" class="w-full rounded-xl border-slate-200 bg-slate-50/60 shadow-sm transition-all duration-150 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100">
                </div>
                <div>
                    <label for="modal-actual-completion" class="mb-1.5 block text-sm font-medium text-slate-700">Actual Completion Date</label>
                    <input id="modal-actual-completion" type="date" name="actual_completion_date" value="{{ old('actual_completion_date', $project->actual_completion_date) }}" class="w-full rounded-xl border-slate-200 bg-slate-50/60 shadow-sm transition-all duration-150 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100">
                </div>
            </div>

            <div class="mt-6 border-t border-slate-100 pt-5">
                <label for="modal-project-remarks" class="mb-1.5 block text-sm font-medium text-slate-700">Remarks</label>
                <textarea id="modal-project-remarks" name="remarks" rows="2" class="w-full rounded-xl border-slate-200 bg-slate-50/60 shadow-sm transition-all duration-150 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100">{{ old('remarks', $project->remarks) }}</textarea>
            </div>
        </div>

        <div class="flex shrink-0 items-center justify-end gap-3 border-t border-slate-100 bg-slate-50/80 px-6 py-4">
            <button type="button" @click="$dispatch('close-modal', 'edit-project')" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition-colors duration-150 hover:bg-slate-50">Cancel</button>
            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all duration-200 ease-elegant hover:-translate-y-px hover:bg-brand-700 hover:shadow-elevated active:translate-y-0">
                <i data-lucide="check" class="h-4 w-4"></i>
                Save Changes
            </button>
        </div>
    </form>
</x-modal>
