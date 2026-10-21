@extends('layouts.app')

@section('content')
@php
    $query = ['from' => $from->toDateString(), 'to' => $to->toDateString()];
@endphp

<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">System</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">System Usage</h1>
            <p class="mt-1 text-sm text-slate-600">
                Sign-ins and activity from {{ $from->format('M j, Y') }} to {{ $to->format('M j, Y') }}.
            </p>
        </div>

        <form method="GET" class="flex flex-wrap items-end gap-2">
            <label class="text-xs font-medium text-slate-500">
                From
                <input type="date" name="from" value="{{ $from->toDateString() }}" max="{{ now()->toDateString() }}" class="mt-1 block rounded-lg border-slate-300 text-sm">
            </label>
            <label class="text-xs font-medium text-slate-500">
                To
                <input type="date" name="to" value="{{ $to->toDateString() }}" max="{{ now()->toDateString() }}" class="mt-1 block rounded-lg border-slate-300 text-sm">
            </label>
            <label class="text-xs font-medium text-slate-500">
                Role
                <select name="role" class="mt-1 block rounded-lg border-slate-300 text-sm">
                    <option value="">All roles</option>
                    @foreach ($roles as $value => $label)
                        <option value="{{ $value }}" @selected($role === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <button class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Apply</button>
        </form>
    </div>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat-card label="Active Users" :value="$totals['active_users'].' of '.$rows->count()" icon="users" sub="Signed in or did something" />
        <x-stat-card label="Sign-ins" :value="$totals['sign_ins']" icon="log-in" sub="Successful logins" />
        <x-stat-card label="Failed Sign-ins" :value="$totals['failed'] + $totals['unknown_failed']" icon="shield-alert" :sub="$totals['unknown_failed'] ? $totals['unknown_failed'].' with unknown emails' : 'Wrong passwords'" />
        <x-stat-card label="Actions" :value="$totals['actions']" icon="mouse-pointer-click" sub="Recorded in the audit log" />
    </div>

    <section class="mt-6 overflow-hidden rounded-2xl bg-white shadow-soft ring-1 ring-slate-900/5">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                        <th class="px-5 py-3">User</th>
                        <th class="px-4 py-3">Last Active</th>
                        <th class="px-4 py-3">Last Sign-in</th>
                        <th class="px-4 py-3 text-right">Sign-ins</th>
                        <th class="px-4 py-3 text-right">Failed</th>
                        <th class="px-4 py-3">What They Did</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($rows as $row)
                        @php $person = $row['user']; @endphp
                        <tr class="transition-colors duration-150 hover:bg-slate-50/80">
                            <td class="px-5 py-3.5">
                                <p class="font-medium text-slate-900">
                                    {{ $person->name }}
                                    @unless ($person->is_active)
                                        <span class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-slate-500">Inactive</span>
                                    @endunless
                                </p>
                                <p class="text-xs text-slate-500">{{ $person->roleLabel() }}{{ $person->position_type ? ' · '.$person->position_type : '' }}</p>
                            </td>
                            <td class="px-4 py-3.5 whitespace-nowrap text-slate-600" title="{{ $person->last_active_at?->format('M j, Y g:i A') }}">
                                {{ $person->last_active_at?->diffForHumans() ?? 'Never' }}
                            </td>
                            <td class="px-4 py-3.5 whitespace-nowrap text-slate-600" title="{{ $row['last_sign_in']?->format('M j, Y g:i A') }}">
                                {{ $row['last_sign_in']?->diffForHumans() ?? 'Never' }}
                            </td>
                            <td class="px-4 py-3.5 text-right tabular-nums font-medium text-slate-900">{{ $row['sign_ins'] }}</td>
                            <td class="px-4 py-3.5 text-right tabular-nums {{ $row['failed'] > 0 ? 'font-semibold text-red-600' : 'text-slate-400' }}">{{ $row['failed'] }}</td>
                            <td class="px-4 py-3.5">
                                @if ($row['breakdown'])
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach ($row['breakdown'] as $item)
                                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{{ $item }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400">No actions</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <a href="{{ route('system.usage.user', ['user' => $person] + $query) }}" class="inline-flex items-center gap-1 text-sm font-medium text-slate-700 hover:text-slate-900">
                                    Details
                                    <i data-lucide="chevron-right" class="h-4 w-4"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-12 text-center text-slate-500">No users match this filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <p class="mt-3 text-xs text-slate-500">Sign-ins are recorded from the day this report was added. Actions come from the audit log.</p>
</div>
@endsection
