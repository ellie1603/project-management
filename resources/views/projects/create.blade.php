@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-4xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
    <a href="{{ route('projects.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition-colors duration-150 hover:text-slate-900">
        <i data-lucide="arrow-left" class="h-4 w-4"></i>
        Back to Projects
    </a>

    <x-page-hero
        eyebrow="Project Registration"
        title="Register a new project"
        subtitle="Record an already-approved project into the system — its code, budget, and timeline become the baseline for every report from here on."
        class="animate-rise-in"
    />

    <form action="{{ route('projects.store') }}" method="POST" class="animate-rise-in overflow-hidden rounded-3xl bg-white shadow-soft ring-1 ring-slate-900/5" style="animation-delay: 80ms">
        @csrf

        <div class="p-6 sm:p-7">
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-slate-400">
                <i data-lucide="file-text" class="h-3.5 w-3.5"></i>
                Project Information
            </div>

            <div class="mt-4 grid gap-5 md:grid-cols-2">
                <div>
                    <label for="project-title" class="mb-1.5 block text-sm font-medium text-slate-700">Title</label>
                    <input id="project-title" name="title" value="{{ old('title') }}" class="w-full rounded-xl border-slate-200 bg-slate-50/60 shadow-sm transition-all duration-150 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100" required>
                </div>
                <div>
                    <label for="project-category" class="mb-1.5 block text-sm font-medium text-slate-700">Category</label>
                    <select id="project-category" name="category_id" class="w-full rounded-xl border-slate-200 bg-slate-50/60 shadow-sm transition-all duration-150 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100" required>
                        <option value="">Select a category</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label for="project-description" class="mb-1.5 block text-sm font-medium text-slate-700">Description</label>
                    <textarea id="project-description" name="description" rows="3" class="w-full rounded-xl border-slate-200 bg-slate-50/60 shadow-sm transition-all duration-150 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100">{{ old('description') }}</textarea>
                </div>
                <div>
                    <label for="project-type" class="mb-1.5 block text-sm font-medium text-slate-700">Project Type</label>
                    <input id="project-type" name="project_type" value="{{ old('project_type') }}" class="w-full rounded-xl border-slate-200 bg-slate-50/60 shadow-sm transition-all duration-150 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100" required>
                </div>
                <div>
                    <label for="project-location" class="mb-1.5 block text-sm font-medium text-slate-700">Location</label>
                    <input id="project-location" name="location" value="{{ old('location') }}" class="w-full rounded-xl border-slate-200 bg-slate-50/60 shadow-sm transition-all duration-150 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100" required>
                </div>
                <div class="md:col-span-2">
                    <label for="project-objective" class="mb-1.5 block text-sm font-medium text-slate-700">Objective</label>
                    <textarea id="project-objective" name="objective" rows="3" class="w-full rounded-xl border-slate-200 bg-slate-50/60 shadow-sm transition-all duration-150 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100" required>{{ old('objective') }}</textarea>
                </div>
                <div class="md:col-span-2">
                    <label for="project-remarks" class="mb-1.5 block text-sm font-medium text-slate-700">Remarks</label>
                    <textarea id="project-remarks" name="remarks" rows="2" class="w-full rounded-xl border-slate-200 bg-slate-50/60 shadow-sm transition-all duration-150 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100">{{ old('remarks') }}</textarea>
                </div>
            </div>

            <div class="mt-7 flex items-center gap-2 border-t border-slate-100 pt-6 text-xs font-semibold uppercase tracking-wider text-slate-400">
                <i data-lucide="wallet" class="h-3.5 w-3.5"></i>
                Budget &amp; Timeline
            </div>

            <div class="mt-4 grid gap-5 md:grid-cols-3">
                <div>
                    <label for="project-approved-budget" class="mb-1.5 block text-sm font-medium text-slate-700">Approved Budget</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-slate-400">₱</span>
                        <input id="project-approved-budget" type="number" name="approved_budget" value="{{ old('approved_budget') }}" step="0.01" min="0.01" class="w-full rounded-xl border-slate-200 bg-slate-50/60 py-2 pl-8 pr-3 shadow-sm transition-all duration-150 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100" required>
                    </div>
                </div>
                <div>
                    <label for="project-planned-start" class="mb-1.5 block text-sm font-medium text-slate-700">Planned Start Date</label>
                    <input id="project-planned-start" type="date" name="planned_start_date" value="{{ old('planned_start_date') }}" class="w-full rounded-xl border-slate-200 bg-slate-50/60 shadow-sm transition-all duration-150 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100" required>
                </div>
                <div>
                    <label for="project-target-completion" class="mb-1.5 block text-sm font-medium text-slate-700">Target Completion Date</label>
                    <input id="project-target-completion" type="date" name="target_completion_date" value="{{ old('target_completion_date') }}" class="w-full rounded-xl border-slate-200 bg-slate-50/60 shadow-sm transition-all duration-150 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100" required>
                </div>
            </div>
        </div>

        <div class="border-t border-slate-100 bg-slate-50/60 p-6 sm:p-7" x-data="{ personnel: [{ user_id: '', position_type: '', responsibility: '' }] }">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-slate-400">
                    <i data-lucide="users" class="h-3.5 w-3.5"></i>
                    Assign Personnel
                </div>
                <button type="button" @click="personnel.push({ user_id: '', position_type: '', responsibility: '' })" class="inline-flex items-center gap-1.5 rounded-lg bg-white px-2.5 py-1.5 text-sm font-medium text-brand-600 shadow-sm ring-1 ring-slate-200 transition-colors duration-150 hover:bg-brand-50">
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    Add Personnel
                </button>
            </div>

            <template x-for="(row, index) in personnel" :key="index">
                <div class="mt-3 grid gap-3 rounded-2xl bg-white p-3.5 shadow-sm ring-1 ring-slate-900/5 sm:grid-cols-[1fr_1fr_1fr_auto]">
                    <select :name="`personnel[${index}][user_id]`" x-model="row.user_id" aria-label="Personnel" class="rounded-lg border-slate-200 bg-slate-50/60 text-sm shadow-sm transition-all duration-150 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100" required>
                        <option value="">Select personnel</option>
                        @foreach ($projectPersonnel as $person)
                            <option value="{{ $person->id }}">{{ $person->name }}</option>
                        @endforeach
                    </select>
                    <select :name="`personnel[${index}][position_type]`" x-model="row.position_type" aria-label="Position type" class="rounded-lg border-slate-200 bg-slate-50/60 text-sm shadow-sm transition-all duration-150 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100" required>
                        <option value="">Position</option>
                        @foreach (\App\Models\ProjectAssignment::POSITION_TYPES as $position)
                            <option value="{{ $position }}">{{ $position }}</option>
                        @endforeach
                    </select>
                    <input :name="`personnel[${index}][responsibility]`" x-model="row.responsibility" placeholder="Responsibility" aria-label="Responsibility" class="rounded-lg border-slate-200 bg-slate-50/60 text-sm shadow-sm transition-all duration-150 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100" required>
                    <button type="button" x-show="personnel.length > 1" @click="personnel.splice(index, 1)" class="inline-flex items-center justify-center gap-1.5 rounded-lg px-3 text-sm font-medium text-red-600 transition-colors duration-150 hover:bg-red-50">
                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                        <span class="sm:hidden">Remove</span>
                    </button>
                </div>
            </template>
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-slate-100 px-6 py-4 sm:px-7">
            <a href="{{ route('projects.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition-colors duration-150 hover:bg-slate-50">Cancel</a>
            <button type="submit" class="btn-sheen inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all duration-200 ease-elegant hover:-translate-y-px hover:bg-slate-800 hover:shadow-elevated active:translate-y-0">
                <i data-lucide="check" class="h-4 w-4"></i>
                Save Project
            </button>
        </div>
    </form>
</div>
@endsection
