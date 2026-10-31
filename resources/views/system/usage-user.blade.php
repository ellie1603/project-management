@extends('layouts.app')

@section('content')
@php
    $eventStyle = [
        'login' => ['Signed in', 'log-in', 'text-emerald-700 bg-emerald-50'],
        'logout' => ['Signed out', 'log-out', 'text-slate-600 bg-slate-100'],
        'failed' => ['Failed sign-in', 'shield-alert', 'text-red-700 bg-red-50'],
    ];
@endphp

<div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
    <x-breadcrumbs />

    <div class="mb-6">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ $user->roleLabel() }}{{ $user->position_type ? ' · '.$user->position_type : '' }}</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">{{ $user->name }}</h1>
        <p class="mt-1 text-sm text-slate-600">
            {{ $user->email }} &middot; {{ $from->format('M j, Y') }} to {{ $to->format('M j, Y') }}
        </p>
    </div>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat-card label="Last Active" :value="$user->last_active_at?->diffForHumans() ?? 'Never'" icon="clock" :sub="$user->last_active_at?->format('M j, Y g:i A') ?? 'No activity yet'" />
        <x-stat-card label="Sign-ins" :value="$summary['sign_ins'] ?? 0" icon="log-in" sub="In this period" />
        <x-stat-card label="Failed Sign-ins" :value="$summary['failed'] ?? 0" icon="shield-alert" sub="In this period" />
        <x-stat-card label="Actions" :value="$summary['actions'] ?? 0" icon="mouse-pointer-click" sub="In this period" />
    </div>

    @if (! empty($summary['breakdown']))
        <div class="mt-4 flex flex-wrap gap-2">
            @foreach ($summary['breakdown'] as $item)
                <span class="rounded-full bg-white px-3 py-1 text-sm text-slate-700 shadow-soft ring-1 ring-slate-900/5">{{ $item }}</span>
            @endforeach
        </div>
    @endif

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <section class="overflow-hidden rounded-2xl glass">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-sm font-semibold text-slate-900">Sign-in History</h2>
                <p class="mt-0.5 text-xs text-slate-500">Latest first, up to 100.</p>
            </div>
            <ul class="divide-y divide-slate-100">
                @forelse ($signIns as $signIn)
                    @php [$label, $icon, $tone] = $eventStyle[$signIn->event]; @endphp
                    <li class="flex items-start gap-3 px-5 py-3">
                        <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $tone }}">
                            <i data-lucide="{{ $icon }}" class="h-4 w-4"></i>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-slate-900">{{ $label }}</p>
                            <p class="text-xs text-slate-500">{{ $signIn->device() }} &middot; {{ $signIn->ip_address ?? 'Unknown IP' }}</p>
                        </div>
                        <p class="shrink-0 text-xs text-slate-500" title="{{ $signIn->created_at->format('M j, Y g:i:s A') }}">{{ $signIn->created_at->format('M j, g:i A') }}</p>
                    </li>
                @empty
                    <li class="px-5 py-10 text-center text-sm text-slate-500">No sign-ins in this period.</li>
                @endforelse
            </ul>
        </section>

        <section class="overflow-hidden rounded-2xl glass">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-sm font-semibold text-slate-900">Recent Actions</h2>
                <p class="mt-0.5 text-xs text-slate-500">From the audit log, latest 50.</p>
            </div>
            <ul class="divide-y divide-slate-100">
                @forelse ($actions as $action)
                    <li class="flex items-start justify-between gap-3 px-5 py-3">
                        <p class="min-w-0 text-sm text-slate-700">{{ $action->description }}</p>
                        <p class="shrink-0 text-xs text-slate-500" title="{{ $action->created_at->format('M j, Y g:i:s A') }}">{{ $action->created_at->format('M j, g:i A') }}</p>
                    </li>
                @empty
                    <li class="px-5 py-10 text-center text-sm text-slate-500">No actions in this period.</li>
                @endforelse
            </ul>
        </section>
    </div>
</div>
@endsection
