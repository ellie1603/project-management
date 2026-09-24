<section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
    <h2 class="text-lg font-semibold text-slate-900">Project Overview</h2>
    <dl class="mt-4 grid gap-4 sm:grid-cols-2">
        <div>
            <dt class="text-xs uppercase tracking-wide text-slate-500">Project Code</dt>
            <dd class="mt-1 text-sm text-slate-700">{{ $project->project_code }}</dd>
        </div>
        <div>
            <dt class="text-xs uppercase tracking-wide text-slate-500">Category</dt>
            <dd class="mt-1 text-sm text-slate-700">{{ $project->category?->name ?? 'Unassigned' }}</dd>
        </div>
        <div>
            <dt class="text-xs uppercase tracking-wide text-slate-500">Project Type</dt>
            <dd class="mt-1 text-sm text-slate-700">{{ $project->project_type ?? 'Not provided' }}</dd>
        </div>
        <div>
            <dt class="text-xs uppercase tracking-wide text-slate-500">Location</dt>
            <dd class="mt-1 text-sm text-slate-700">{{ $project->location ?? 'Not provided' }}</dd>
        </div>
        <div class="sm:col-span-2">
            <dt class="text-xs uppercase tracking-wide text-slate-500">Objective</dt>
            <dd class="mt-1 text-sm text-slate-700">{{ $project->objective ?? 'Not provided' }}</dd>
        </div>
        <div class="sm:col-span-2">
            <dt class="text-xs uppercase tracking-wide text-slate-500">Description</dt>
            <dd class="mt-1 text-sm text-slate-700">{{ $project->description ?? 'Not provided' }}</dd>
        </div>
        <div class="sm:col-span-2">
            <dt class="text-xs uppercase tracking-wide text-slate-500">Remarks</dt>
            <dd class="mt-1 text-sm text-slate-700">{{ $project->remarks ?? 'Not provided' }}</dd>
        </div>
    </dl>
</section>
