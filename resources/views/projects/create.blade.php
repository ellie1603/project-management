@extends('layouts.app')

@php
    $field = 'w-full rounded-xl border-slate-200 bg-slate-50/60 shadow-sm transition-all duration-150 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100';
    $oldPersonnel = collect(old('personnel', []))->pluck('user_id')->filter()->values();
    $hasOptionalInput = filled(old('description')) || filled(old('project_type')) || filled(old('objective')) || filled(old('remarks'));
@endphp

@section('content')
<div class="mx-auto max-w-4xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
    <a href="{{ route('projects.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition-colors duration-150 hover:text-slate-900">
        <i data-lucide="arrow-left" class="h-4 w-4"></i>
        Back to Projects
    </a>

    <x-page-hero
        eyebrow="Project Registration"
        title="Register a new project"
        subtitle="Enter the essentials of the approved project. The project code is generated automatically, and everything else can be added later from the project page."
        class="animate-rise-in"
    />

    @if ($errors->any())
        <div class="flex items-start gap-2.5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <i data-lucide="alert-circle" class="mt-0.5 h-4 w-4 shrink-0"></i>
            <ul class="space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('projects.store') }}" method="POST" class="animate-rise-in overflow-hidden rounded-3xl bg-white shadow-soft ring-1 ring-slate-900/5" style="animation-delay: 80ms">
        @csrf

        <div class="p-6 sm:p-7">
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-slate-400">
                <i data-lucide="file-text" class="h-3.5 w-3.5"></i>
                Project
            </div>

            <div class="mt-4 grid gap-5 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label for="project-title" class="mb-1.5 block text-sm font-medium text-slate-700">Project Title</label>
                    <input id="project-title" name="title" value="{{ old('title') }}" placeholder="e.g. New Branch Office — Altavas" class="{{ $field }}" required autofocus>
                </div>
                <div>
                    <label for="project-category" class="mb-1.5 block text-sm font-medium text-slate-700">Category</label>
                    <select id="project-category" name="category_id" class="{{ $field }}" required>
                        <option value="">Select a category</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="project-location" class="mb-1.5 block text-sm font-medium text-slate-700">Location</label>
                    <input id="project-location" name="location" value="{{ old('location') }}" placeholder="e.g. Barbaza, Antique" class="{{ $field }}" required>
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
                        <input id="project-approved-budget" type="number" name="approved_budget" value="{{ old('approved_budget') }}" step="0.01" min="0.01" inputmode="decimal" class="{{ $field }} py-2 pl-8 pr-3" required>
                    </div>
                </div>
                <div>
                    <label for="project-planned-start" class="mb-1.5 block text-sm font-medium text-slate-700">Start Date</label>
                    <input id="project-planned-start" type="date" name="planned_start_date" value="{{ old('planned_start_date') }}" class="{{ $field }}" required>
                </div>
                <div>
                    <label for="project-target-completion" class="mb-1.5 block text-sm font-medium text-slate-700">Target Completion</label>
                    <input id="project-target-completion" type="date" name="target_completion_date" value="{{ old('target_completion_date') }}" class="{{ $field }}" required>
                </div>
            </div>
        </div>

        <div class="border-t border-slate-100 bg-slate-50/60 p-6 sm:p-7" x-data="{ personnel: @js($oldPersonnel->isEmpty() ? [''] : $oldPersonnel) }">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-slate-400">
                        <i data-lucide="users" class="h-3.5 w-3.5"></i>
                        Assign Personnel
                        <span class="font-normal normal-case tracking-normal text-slate-400">(optional)</span>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">Their position is taken from their user account.</p>
                </div>
                <button type="button" @click="personnel.push('')" class="inline-flex shrink-0 items-center gap-1.5 rounded-lg bg-white px-2.5 py-1.5 text-sm font-medium text-brand-600 shadow-sm ring-1 ring-slate-200 transition-colors duration-150 hover:bg-brand-50">
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    Add
                </button>
            </div>

            <template x-for="(userId, index) in personnel" :key="index">
                <div class="mt-3 flex items-center gap-2">
                    <select :name="personnel[index] ? `personnel[${index}][user_id]` : ''" x-model="personnel[index]" aria-label="Personnel" class="{{ $field }} text-sm">
                        <option value="">Select personnel</option>
                        @foreach ($projectPersonnel as $person)
                            <option value="{{ $person->id }}">{{ $person->name }}{{ $person->position_type ? ' — '.$person->position_type : '' }}</option>
                        @endforeach
                    </select>
                    <button type="button" x-show="personnel.length > 1" @click="personnel.splice(index, 1)" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-red-600 transition-colors duration-150 hover:bg-red-50" aria-label="Remove personnel">
                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                    </button>
                </div>
            </template>
        </div>

        <details class="group border-t border-slate-100" @if ($hasOptionalInput) open @endif>
            <summary class="flex cursor-pointer list-none items-center justify-between px-6 py-4 text-sm font-medium text-slate-600 transition-colors duration-150 hover:text-slate-900 sm:px-7">
                <span class="inline-flex items-center gap-2">
                    <i data-lucide="list-plus" class="h-4 w-4 text-slate-400"></i>
                    More details <span class="font-normal text-slate-400">(optional)</span>
                </span>
                <i data-lucide="chevron-down" class="h-4 w-4 text-slate-400 transition-transform duration-200 group-open:rotate-180"></i>
            </summary>

            <div class="grid gap-5 px-6 pb-6 sm:px-7 md:grid-cols-2">
                <div>
                    <label for="project-type" class="mb-1.5 block text-sm font-medium text-slate-700">Project Type</label>
                    <input id="project-type" name="project_type" value="{{ old('project_type') }}" list="project-type-options" placeholder="e.g. Construction" class="{{ $field }}">
                    <datalist id="project-type-options">
                        <option value="Construction">
                        <option value="Construction / Rent Space">
                        <option value="Repair & Renovation">
                    </datalist>
                </div>
                <div class="md:col-span-2">
                    <label for="project-description" class="mb-1.5 block text-sm font-medium text-slate-700">Description</label>
                    <textarea id="project-description" name="description" rows="2" class="{{ $field }}">{{ old('description') }}</textarea>
                </div>
                <div class="md:col-span-2">
                    <label for="project-objective" class="mb-1.5 block text-sm font-medium text-slate-700">Objective</label>
                    <textarea id="project-objective" name="objective" rows="2" class="{{ $field }}">{{ old('objective') }}</textarea>
                </div>
                <div class="md:col-span-2">
                    <label for="project-remarks" class="mb-1.5 block text-sm font-medium text-slate-700">Remarks</label>
                    <textarea id="project-remarks" name="remarks" rows="2" class="{{ $field }}">{{ old('remarks') }}</textarea>
                </div>
            </div>
        </details>

        <div class="flex items-center justify-end gap-3 border-t border-slate-100 px-6 py-4 sm:px-7">
            <a href="{{ route('projects.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition-colors duration-150 hover:bg-slate-50">Cancel</a>
            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all duration-200 ease-elegant hover:-translate-y-px hover:bg-brand-700 hover:shadow-elevated active:translate-y-0">
                <i data-lucide="check" class="h-4 w-4"></i>
                Register Project
            </button>
        </div>
    </form>
</div>
@endsection
