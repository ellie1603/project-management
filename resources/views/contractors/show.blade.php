@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-5xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Provider</p>
            <h1 class="mt-1 text-2xl font-semibold text-slate-900">{{ $contractor->name }}</h1>
        </div>
        @if (auth()->user()->isAdmin() && $contractor->user)
            <a href="{{ route('users.index', ['search' => $contractor->user->email, 'edit' => $contractor->user->id]) }}" class="inline-flex items-center gap-2 rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition-colors duration-150 hover:bg-slate-50">
                <i data-lucide="pencil" class="h-4 w-4"></i>
                Edit via Users
            </a>
        @endif
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="font-semibold text-slate-900">Provider Information</h2>
        <dl class="mt-4 grid gap-4 sm:grid-cols-2">
            <div>
                <dt class="text-xs uppercase tracking-wide text-slate-500">Contact Person</dt>
                <dd class="mt-1 text-sm text-slate-700">{{ $contractor->contact_person ?? 'Not provided' }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-slate-500">Contact Number</dt>
                <dd class="mt-1 text-sm text-slate-700">{{ $contractor->contact_number ?? 'Not provided' }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-slate-500">Email</dt>
                <dd class="mt-1 text-sm text-slate-700">{{ $contractor->email ?? 'Not provided' }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-slate-500">Status</dt>
                <dd class="mt-1 text-sm text-slate-700">{{ ucfirst($contractor->status) }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-xs uppercase tracking-wide text-slate-500">Address</dt>
                <dd class="mt-1 text-sm text-slate-700">{{ $contractor->address ?? 'Not provided' }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-xs uppercase tracking-wide text-slate-500">Registration Information</dt>
                <dd class="mt-1 text-sm text-slate-700">{{ $contractor->registration_information ?? 'Not provided' }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-xs uppercase tracking-wide text-slate-500">Remarks</dt>
                <dd class="mt-1 text-sm text-slate-700">{{ $contractor->remarks ?? 'Not provided' }}</dd>
            </div>
        </dl>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm" x-data="pager(10)" data-pager>
        <h2 class="font-semibold text-slate-900">Project History</h2>
        <ul class="mt-4 divide-y divide-slate-100">
            @forelse ($contractor->projects as $project)
                <li data-page-item class="flex flex-wrap items-center justify-between gap-2 py-3">
                    <a class="font-medium text-slate-900 hover:underline" href="{{ route('projects.show', $project) }}">{{ $project->title }}</a>
                    <span class="text-sm text-slate-500">
                        {{ $project->pivot->role }} ·
                        {{ $project->pivot->contract_amount ? '₱'.number_format((float) $project->pivot->contract_amount, 2) : 'Amount not provided' }} ·
                        {{ $project->pivot->start_date ?? 'N/A' }} to {{ $project->pivot->end_date ?? 'N/A' }}
                    </span>
                </li>
            @empty
                <li class="py-3 text-sm text-slate-500">No project history.</li>
            @endforelse
        </ul>
        <x-pager-controls class="!px-0" />
    </div>
</div>
@endsection
