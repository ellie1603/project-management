@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
    <x-page-hero
        eyebrow="Reports"
        title="Report Center"
        subtitle="Filter, then export any report as PDF, Excel, or view it on screen."
        class="animate-rise-in"
    />

    <form method="GET" id="report-filters" data-turbo="false" class="mt-6 animate-rise-in overflow-hidden rounded-3xl bg-white shadow-soft ring-1 ring-slate-900/5" style="animation-delay: 80ms">
        <div class="flex items-center gap-3 border-b border-slate-100 px-6 py-5">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl chip-ink text-white shadow-sm">
                <i data-lucide="sliders-horizontal" class="h-4 w-4"></i>
            </span>
            <div>
                <h2 class="font-display text-[15px] font-semibold text-slate-900">Filter Reports</h2>
                <p class="text-xs text-slate-500">Applies to every report below — export uses these same filters.</p>
            </div>
        </div>

        <div class="grid gap-5 p-6 sm:p-7 md:grid-cols-3">
            <div>
                <label for="filter-status" class="mb-1.5 block text-sm font-medium text-slate-700">Status</label>
                <select id="filter-status" name="status" class="w-full rounded-xl border-slate-200 bg-slate-50/60 text-sm shadow-sm transition-all duration-150 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100">
                    <option value="">Any</option>
                    @foreach (\App\Models\Project::STATUSES as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="filter-category" class="mb-1.5 block text-sm font-medium text-slate-700">Category</label>
                <select id="filter-category" name="category_id" class="w-full rounded-xl border-slate-200 bg-slate-50/60 text-sm shadow-sm transition-all duration-150 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100">
                    <option value="">Any</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="filter-project-type" class="mb-1.5 block text-sm font-medium text-slate-700">Project Type</label>
                <input id="filter-project-type" type="text" name="project_type" value="{{ request('project_type') }}" class="w-full rounded-xl border-slate-200 bg-slate-50/60 text-sm shadow-sm transition-all duration-150 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100">
            </div>
            <div>
                <label for="filter-date-from" class="mb-1.5 block text-sm font-medium text-slate-700">Date From</label>
                <input id="filter-date-from" type="date" name="date_from" value="{{ request('date_from') }}" class="w-full rounded-xl border-slate-200 bg-slate-50/60 text-sm shadow-sm transition-all duration-150 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100">
            </div>
            <div>
                <label for="filter-date-to" class="mb-1.5 block text-sm font-medium text-slate-700">Date To</label>
                <input id="filter-date-to" type="date" name="date_to" value="{{ request('date_to') }}" class="w-full rounded-xl border-slate-200 bg-slate-50/60 text-sm shadow-sm transition-all duration-150 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100">
            </div>
            <div>
                <label for="filter-search" class="mb-1.5 block text-sm font-medium text-slate-700">Search</label>
                <input id="filter-search" type="text" name="search" value="{{ request('search') }}" placeholder="Code, title, location" class="w-full rounded-xl border-slate-200 bg-slate-50/60 text-sm shadow-sm transition-all duration-150 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100">
            </div>
            <div>
                <label for="filter-budget-min" class="mb-1.5 block text-sm font-medium text-slate-700">Budget Min</label>
                <input id="filter-budget-min" type="number" step="0.01" name="budget_min" value="{{ request('budget_min') }}" class="w-full rounded-xl border-slate-200 bg-slate-50/60 text-sm shadow-sm transition-all duration-150 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100">
            </div>
            <div>
                <label for="filter-budget-max" class="mb-1.5 block text-sm font-medium text-slate-700">Budget Max</label>
                <input id="filter-budget-max" type="number" step="0.01" name="budget_max" value="{{ request('budget_max') }}" class="w-full rounded-xl border-slate-200 bg-slate-50/60 text-sm shadow-sm transition-all duration-150 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100">
            </div>
            <div>
                <label for="filter-contractor" class="mb-1.5 block text-sm font-medium text-slate-700">Contractor</label>
                <select id="filter-contractor" name="contractor_id" class="w-full rounded-xl border-slate-200 bg-slate-50/60 text-sm shadow-sm transition-all duration-150 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100">
                    <option value="">Any</option>
                    @foreach ($contractors as $contractor)
                        <option value="{{ $contractor->id }}" @selected((string) request('contractor_id') === (string) $contractor->id)>{{ $contractor->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="filter-assigned-user" class="mb-1.5 block text-sm font-medium text-slate-700">Assigned Personnel</label>
                <select id="filter-assigned-user" name="assigned_user_id" class="w-full rounded-xl border-slate-200 bg-slate-50/60 text-sm shadow-sm transition-all duration-150 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100">
                    <option value="">Any</option>
                    @foreach ($personnel as $person)
                        <option value="{{ $person->id }}" @selected((string) request('assigned_user_id') === (string) $person->id)>{{ $person->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </form>

    @php
        $reports = [
            ['route' => 'reports.project-status', 'title' => 'Project Status Report', 'description' => 'Status, timeline, completion, and issues for every project.', 'icon' => 'file-text', 'chip' => 'chip-ink'],
            ['route' => 'reports.budget', 'title' => 'Budget Monitoring Report', 'description' => 'Approved budget, expenses, remaining, and utilization.', 'icon' => 'wallet', 'chip' => 'chip-ink'],
            ['route' => 'reports.accomplishment', 'title' => 'Project Accomplishment Report', 'description' => 'Latest accomplishments and completion by project.', 'icon' => 'clipboard-check', 'chip' => 'chip-ink'],
            ['route' => 'reports.delayed-projects', 'title' => 'Delayed Projects Report', 'description' => 'Projects past their target completion date.', 'icon' => 'triangle-alert', 'chip' => 'chip-ink'],
            ['route' => 'reports.summary', 'title' => 'Project Summary Report', 'description' => 'A high-level snapshot of every tracked project.', 'icon' => 'bar-chart-3', 'chip' => 'chip-ink'],
            ['route' => 'reports.contractor-history', 'title' => 'Contractor History Report', 'description' => 'Contractor engagements, roles, and contract values.', 'icon' => 'hard-hat', 'chip' => 'chip-ink'],
        ];
    @endphp

    <div class="mt-6 grid gap-4 md:grid-cols-2">
        @foreach ($reports as $i => $report)
            <div class="group animate-rise-in rounded-3xl bg-white p-5 shadow-soft ring-1 ring-slate-900/5 transition-all duration-300 ease-elegant hover:-translate-y-0.5 hover:shadow-elevated" style="animation-delay: {{ 130 + $i * 40 }}ms">
                <div class="flex items-start gap-3.5">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $report['chip'] }} text-white shadow-sm transition-transform duration-300 ease-elegant group-hover:scale-110">
                        <i data-lucide="{{ $report['icon'] }}" class="h-[18px] w-[18px]"></i>
                    </span>
                    <div class="min-w-0">
                        <h3 class="font-display text-sm font-semibold text-slate-900">{{ $report['title'] }}</h3>
                        <p class="mt-1 text-xs text-slate-500">{{ $report['description'] }}</p>
                    </div>
                </div>
                <div class="mt-4 flex gap-2">
                    <button type="submit" form="report-filters" formaction="{{ route($report['route']) }}" formtarget="_blank" name="format" value="html" class="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 transition-colors duration-150 hover:bg-slate-900 hover:text-white">
                        <i data-lucide="eye" class="h-3.5 w-3.5"></i>
                        View
                    </button>
                    <button type="submit" form="report-filters" formaction="{{ route($report['route']) }}" name="format" value="pdf" class="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 transition-colors duration-150 hover:bg-slate-900 hover:text-white">
                        <i data-lucide="file-down" class="h-3.5 w-3.5"></i>
                        PDF
                    </button>
                    <button type="submit" form="report-filters" formaction="{{ route($report['route']) }}" name="format" value="xlsx" class="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 transition-colors duration-150 hover:bg-slate-900 hover:text-white">
                        <i data-lucide="sheet" class="h-3.5 w-3.5"></i>
                        Excel
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    @if (auth()->user()->isFinance())
        <section class="mt-6 animate-rise-in overflow-hidden rounded-3xl bg-white shadow-soft ring-1 ring-slate-900/5" style="animation-delay: 420ms">
            <div class="flex items-center gap-3 border-b border-slate-100 px-6 py-5">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl chip-ink text-white shadow-sm">
                    <i data-lucide="file-plus" class="h-4 w-4"></i>
                </span>
                <div>
                    <h2 class="font-display text-[15px] font-semibold text-slate-900">Create Budget Report</h2>
                    <p class="text-xs text-slate-500">Generates a saved Budget Monitoring Report visible to Admin from this page.</p>
                </div>
            </div>
            <form method="POST" action="{{ route('reports.finance.store') }}" class="flex flex-wrap items-end gap-3 p-6 sm:p-7">
                @csrf
                <div>
                    <label for="finance-report-project" class="mb-1.5 block text-sm font-medium text-slate-700">Project</label>
                    <select id="finance-report-project" name="project_id" class="w-64 rounded-xl border-slate-200 bg-slate-50/60 text-sm shadow-sm transition-all duration-150 ease-smooth focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100">
                        <option value="">All Projects</option>
                        @foreach ($projectOptions as $option)
                            <option value="{{ $option->id }}">{{ $option->project_code }} — {{ $option->title }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="btn-sheen inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all duration-200 ease-elegant hover:-translate-y-px hover:bg-slate-800 hover:shadow-elevated">
                    <i data-lucide="sparkles" class="h-4 w-4"></i>
                    Generate &amp; Save
                </button>
            </form>
        </section>

        <section class="mt-6 animate-rise-in overflow-hidden rounded-3xl bg-white shadow-soft ring-1 ring-slate-900/5" style="animation-delay: 470ms">
            <div class="flex items-center gap-3 border-b border-slate-100 px-6 py-5">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl chip-ink text-white shadow-sm">
                    <i data-lucide="folder-open" class="h-4 w-4"></i>
                </span>
                <h2 class="font-display text-[15px] font-semibold text-slate-900">My Saved Reports</h2>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($myFinanceReports as $report)
                    <div class="flex items-center justify-between gap-4 px-6 py-3.5 sm:px-7">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-slate-900">{{ $report->title }}</p>
                            <p class="text-xs text-slate-500">{{ $report->created_at->format('M d, Y h:i A') }}</p>
                        </div>
                        <a href="{{ route('reports.finance.download', $report) }}" data-turbo="false" class="inline-flex shrink-0 items-center gap-1.5 rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 transition-colors duration-150 hover:bg-slate-900 hover:text-white">
                            <i data-lucide="download" class="h-3.5 w-3.5"></i>
                            Download
                        </a>
                    </div>
                @empty
                    <div class="px-6 py-10 text-center text-sm text-slate-500">You haven't saved any reports yet.</div>
                @endforelse
            </div>
        </section>
    @endif

    @if (auth()->user()->isAdmin())
        <div class="mt-8 animate-rise-in" style="animation-delay: 420ms">
            <h2 class="mb-1 font-display text-lg font-semibold text-slate-900">Submitted Reports</h2>
            <p class="mb-4 text-sm text-slate-500">Reports generated by Finance and reports submitted by Project Personnel, for viewing only.</p>

            <div class="grid gap-4 lg:grid-cols-2">
                <section class="overflow-hidden rounded-3xl bg-white shadow-soft ring-1 ring-slate-900/5">
                    <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-4">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg chip-ink text-white shadow-sm">
                            <i data-lucide="wallet" class="h-3.5 w-3.5"></i>
                        </span>
                        <h3 class="text-sm font-semibold text-slate-900">Finance Reports</h3>
                    </div>
                    <div class="max-h-80 divide-y divide-slate-100 overflow-y-auto">
                        @forelse ($financeReports as $report)
                            <div class="flex items-center justify-between gap-4 px-5 py-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-slate-900">{{ $report->title }}</p>
                                    <p class="text-xs text-slate-500">{{ $report->generator?->name ?? 'Finance' }} · {{ $report->created_at->format('M d, Y') }}</p>
                                </div>
                                <a href="{{ route('reports.finance.download', $report) }}" data-turbo="false" class="inline-flex shrink-0 items-center gap-1.5 text-sm font-medium text-slate-500 transition-colors duration-150 hover:text-brand-600">
                                    <i data-lucide="download" class="h-3.5 w-3.5"></i>
                                    Download
                                </a>
                            </div>
                        @empty
                            <div class="px-5 py-10 text-center text-sm text-slate-500">No finance reports submitted yet.</div>
                        @endforelse
                    </div>
                </section>

                <section class="overflow-hidden rounded-3xl bg-white shadow-soft ring-1 ring-slate-900/5">
                    <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-4">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg chip-ink text-white shadow-sm">
                            <i data-lucide="clipboard-check" class="h-3.5 w-3.5"></i>
                        </span>
                        <h3 class="text-sm font-semibold text-slate-900">Personnel Reports</h3>
                    </div>
                    <div class="max-h-80 divide-y divide-slate-100 overflow-y-auto">
                        @forelse ($personnelReports as $document)
                            <div class="flex items-center justify-between gap-4 px-5 py-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-slate-900">{{ $document->document_type }} — {{ $document->project?->title }}</p>
                                    <p class="text-xs text-slate-500">{{ $document->uploader?->name ?? 'Personnel' }} · {{ $document->created_at->format('M d, Y') }}</p>
                                </div>
                                @if ($document->project)
                                    <a href="{{ route('projects.documents.download', [$document->project, $document]) }}" data-turbo="false" class="inline-flex shrink-0 items-center gap-1.5 text-sm font-medium text-slate-500 transition-colors duration-150 hover:text-brand-600">
                                        <i data-lucide="download" class="h-3.5 w-3.5"></i>
                                        Download
                                    </a>
                                @endif
                            </div>
                        @empty
                            <div class="px-5 py-10 text-center text-sm text-slate-500">No personnel reports submitted yet.</div>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    @endif
</div>
@endsection
