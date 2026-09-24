@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-6">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Global Search</p>
        <h1 class="mt-1 text-2xl font-semibold text-slate-900">
            @if ($term !== '')
                Results for &ldquo;{{ $term }}&rdquo;
            @else
                Search projects, contractors, and personnel
            @endif
        </h1>
    </div>

    <form method="GET" action="{{ route('search.index') }}" class="mb-8">
        <div class="relative max-w-xl">
            <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
            <input type="search" name="q" value="{{ $term }}" placeholder="Search..." aria-label="Search" class="w-full rounded-lg border border-slate-300 py-2 pl-9 pr-3 text-sm transition-all duration-150 focus:border-brand-400 focus:ring-2 focus:ring-brand-100" autofocus>
        </div>
    </form>

    <div class="space-y-8">
        <section>
            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Projects</h2>
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="divide-y divide-slate-200">
                    @forelse ($projects as $project)
                        <a href="{{ route('projects.show', $project) }}" class="flex items-center justify-between gap-4 px-5 py-4 hover:bg-slate-50">
                            <div>
                                <p class="text-sm font-medium text-slate-900">{{ $project->title }}</p>
                                <p class="text-xs text-slate-500">{{ $project->project_code }} · {{ $project->location ?? 'No location' }} · {{ $project->category?->name ?? 'Uncategorized' }}</p>
                            </div>
                            <x-status-badge :status="$project->status" />
                        </a>
                    @empty
                        <p class="px-5 py-8 text-center text-sm text-slate-500">No matching projects.</p>
                    @endforelse
                </div>
            </div>
        </section>

        @if ($contractors->isNotEmpty() || (auth()->user()->isAdmin() || auth()->user()->isFinance()))
            <section>
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Contractors / Providers</h2>
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="divide-y divide-slate-200">
                        @forelse ($contractors as $contractor)
                            <a href="{{ route('contractors.show', $contractor) }}" class="flex items-center justify-between gap-4 px-5 py-4 hover:bg-slate-50">
                                <div>
                                    <p class="text-sm font-medium text-slate-900">{{ $contractor->name }}</p>
                                    <p class="text-xs text-slate-500">{{ $contractor->contact_person }} · {{ $contractor->contact_number }}</p>
                                </div>
                            </a>
                        @empty
                            <p class="px-5 py-8 text-center text-sm text-slate-500">No matching contractors.</p>
                        @endforelse
                    </div>
                </div>
            </section>

            <section>
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Assigned Personnel</h2>
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="divide-y divide-slate-200">
                        @forelse ($personnel as $person)
                            <div class="flex items-center justify-between gap-4 px-5 py-4">
                                <div>
                                    <p class="text-sm font-medium text-slate-900">{{ $person->name }}</p>
                                    <p class="text-xs text-slate-500">{{ $person->position_type ?? 'Project Personnel' }}</p>
                                </div>
                            </div>
                        @empty
                            <p class="px-5 py-8 text-center text-sm text-slate-500">No matching personnel.</p>
                        @endforelse
                    </div>
                </div>
            </section>
        @endif
    </div>
</div>
@endsection
