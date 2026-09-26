@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-6">
        <a href="{{ route('projects.show', $project) }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition-colors duration-150 hover:text-slate-900">
            <i data-lucide="arrow-left" class="h-4 w-4"></i>
            Back to Project
        </a>
        <p class="mt-4 text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Project Update</p>
        <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">Edit Project</h1>
    </div>

    <form action="{{ route('projects.update', $project) }}" method="POST" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')

        <div class="grid gap-5 md:grid-cols-2">
            <div>
                <label for="project-title" class="mb-1 block text-sm font-medium text-slate-700">Title</label>
                <input id="project-title" name="title" value="{{ old('title', $project->title) }}" class="w-full rounded-md border-slate-300 shadow-sm transition-all duration-150 focus:border-brand-400 focus:ring-2 focus:ring-brand-100" required>
            </div>
            <div class="md:col-span-2">
                <label for="project-description" class="mb-1 block text-sm font-medium text-slate-700">Description</label>
                <textarea id="project-description" name="description" rows="3" class="w-full rounded-md border-slate-300 shadow-sm transition-all duration-150 focus:border-brand-400 focus:ring-2 focus:ring-brand-100">{{ old('description', $project->description) }}</textarea>
            </div>
            <div>
                <label for="project-category" class="mb-1 block text-sm font-medium text-slate-700">Category</label>
                <select id="project-category" name="category_id" class="w-full rounded-md border-slate-300 shadow-sm transition-all duration-150 focus:border-brand-400 focus:ring-2 focus:ring-brand-100">
                    <option value="">Uncategorized</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('category_id', $project->category_id) == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="project-type" class="mb-1 block text-sm font-medium text-slate-700">Project Type</label>
                <input id="project-type" name="project_type" value="{{ old('project_type', $project->project_type) }}" class="w-full rounded-md border-slate-300 shadow-sm transition-all duration-150 focus:border-brand-400 focus:ring-2 focus:ring-brand-100">
            </div>
            <div>
                <label for="project-location" class="mb-1 block text-sm font-medium text-slate-700">Location</label>
                <input id="project-location" name="location" value="{{ old('location', $project->location) }}" class="w-full rounded-md border-slate-300 shadow-sm transition-all duration-150 focus:border-brand-400 focus:ring-2 focus:ring-brand-100">
            </div>
            <div>
                <label for="project-status" class="mb-1 block text-sm font-medium text-slate-700">Status</label>
                <select id="project-status" name="status" class="w-full rounded-md border-slate-300 shadow-sm transition-all duration-150 focus:border-brand-400 focus:ring-2 focus:ring-brand-100">
                    @foreach (\App\Models\Project::STATUSES as $status)
                        <option value="{{ $status }}" @selected(old('status', $project->status) === $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="project-planned-start" class="mb-1 block text-sm font-medium text-slate-700">Planned Start Date</label>
                <input id="project-planned-start" type="date" name="planned_start_date" value="{{ old('planned_start_date', $project->planned_start_date) }}" class="w-full rounded-md border-slate-300 shadow-sm transition-all duration-150 focus:border-brand-400 focus:ring-2 focus:ring-brand-100" required>
            </div>
            <div>
                <label for="project-target-completion" class="mb-1 block text-sm font-medium text-slate-700">Target Completion Date</label>
                <input id="project-target-completion" type="date" name="target_completion_date" value="{{ old('target_completion_date', $project->target_completion_date) }}" class="w-full rounded-md border-slate-300 shadow-sm transition-all duration-150 focus:border-brand-400 focus:ring-2 focus:ring-brand-100" required>
            </div>
            <div>
                <label for="project-actual-start" class="mb-1 block text-sm font-medium text-slate-700">Actual Start Date</label>
                <input id="project-actual-start" type="date" name="actual_start_date" value="{{ old('actual_start_date', $project->actual_start_date) }}" class="w-full rounded-md border-slate-300 shadow-sm transition-all duration-150 focus:border-brand-400 focus:ring-2 focus:ring-brand-100">
            </div>
            <div>
                <label for="project-actual-completion" class="mb-1 block text-sm font-medium text-slate-700">Actual Completion Date</label>
                <input id="project-actual-completion" type="date" name="actual_completion_date" value="{{ old('actual_completion_date', $project->actual_completion_date) }}" class="w-full rounded-md border-slate-300 shadow-sm transition-all duration-150 focus:border-brand-400 focus:ring-2 focus:ring-brand-100">
            </div>
            <div class="md:col-span-2">
                <label for="project-remarks" class="mb-1 block text-sm font-medium text-slate-700">Remarks</label>
                <textarea id="project-remarks" name="remarks" rows="3" class="w-full rounded-md border-slate-300 shadow-sm transition-all duration-150 focus:border-brand-400 focus:ring-2 focus:ring-brand-100">{{ old('remarks', $project->remarks) }}</textarea>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('projects.show', $project) }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition-colors duration-150 hover:bg-slate-50">Cancel</a>
            <button type="submit" class="rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-all duration-150 hover:bg-brand-700 hover:shadow-md active:scale-[0.98]">Update Project</button>
        </div>
    </form>
</div>
@endsection
