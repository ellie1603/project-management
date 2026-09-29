@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <x-page-hero
        eyebrow="Finance"
        title="Budget & Finance"
        subtitle="Org-wide budget totals and utilization across every project."
        class="animate-rise-in"
    >
        <x-slot name="aside">
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('budget.expenses') }}" class="inline-flex items-center gap-2 rounded-xl bg-accent-500 px-4 py-2.5 text-sm font-semibold text-white shadow-glow hover:bg-accent-600 transition-transform duration-200 ease-elegant hover:-translate-y-0.5">
                    <i data-lucide="receipt" class="h-4 w-4"></i>
                    Expenses
                </a>
                <a href="{{ route('budget.requests') }}" class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-4 py-2.5 text-sm font-medium text-slate-200 backdrop-blur transition-colors duration-200 hover:bg-white/10 hover:text-white">
                    Budget Requests
                    <i data-lucide="arrow-right" class="h-4 w-4"></i>
                </a>
            </div>
        </x-slot>

        <x-slot name="footer">
            <div class="flex items-center justify-between text-[11px] font-medium text-slate-400">
                <span>Overall utilization</span>
                <span class="tabular-nums text-slate-300">{{ $stats['overall_utilization_percent'] }}%</span>
            </div>
            <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-white/10">
                <div
                    class="h-full origin-left animate-grow-x rounded-full {{ $stats['overall_utilization_percent'] >= 100 ? 'bg-red-500' : ($stats['overall_utilization_percent'] >= 80 ? 'bg-amber-400' : 'bg-white') }}"
                    style="width: {{ min(100, (float) $stats['overall_utilization_percent']) }}%; animation-delay: 250ms"
                ></div>
            </div>
        </x-slot>
    </x-page-hero>

    <div class="mt-6 grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 xl:grid-cols-5">
        <x-stat-card label="Approved Budget" value="₱{{ number_format($stats['total_approved_budget'], 2) }}" icon="wallet" sub="Baseline allocation" class="animate-rise-in" style="animation-delay: 60ms" />
        <x-stat-card label="Total Expenses" value="₱{{ number_format($stats['total_expenses'], 2) }}" icon="calculator" sub="Recorded to date" class="animate-rise-in" style="animation-delay: 110ms" />
        <x-stat-card label="Remaining Budget" value="₱{{ number_format($stats['remaining_budget'], 2) }}" icon="piggy-bank" sub="Available to spend" class="animate-rise-in" style="animation-delay: 160ms" />
        <x-stat-card label="Overall Utilization" value="{{ $stats['overall_utilization_percent'] }}%" icon="bar-chart-3" sub="Across all projects" class="animate-rise-in" style="animation-delay: 210ms" />
        <x-stat-card label="Pending Requests" value="₱{{ number_format($stats['pending_requests'], 2) }}" icon="hand-coins" sub="Awaiting review" class="col-span-2 animate-rise-in md:col-span-1" style="animation-delay: 260ms" />
    </div>

    <section class="mt-6 animate-rise-in overflow-hidden rounded-3xl bg-white shadow-soft ring-1 ring-slate-900/5 transition-shadow duration-300 ease-elegant hover:shadow-elevated" style="animation-delay: 260ms">
        <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-4">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl chip-ink text-white shadow-sm">
                <i data-lucide="table-2" class="h-4 w-4"></i>
            </span>
            <div>
                <h3 class="font-display text-[15px] font-semibold text-slate-900">Per-Project Budget</h3>
                <p class="text-xs text-slate-500">Approved, spent, and remaining for every project.</p>
            </div>
        </div>

        <div x-data="pager(10)" data-pager>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-slate-100">
                        <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Project</th>
                        <th class="px-4 py-3 text-right text-[11px] font-semibold uppercase tracking-wider text-slate-400">Approved</th>
                        <th class="px-4 py-3 text-right text-[11px] font-semibold uppercase tracking-wider text-slate-400">Expenses</th>
                        <th class="px-4 py-3 text-right text-[11px] font-semibold uppercase tracking-wider text-slate-400">Remaining</th>
                        <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Utilization</th>
                        <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-400">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($projectRows as $row)
                        @php $remaining = (float) $row['budget']['remaining_budget']; @endphp
                        <tr data-page-item class="group transition-colors duration-150 hover:bg-slate-50/80">
                            <td class="px-5 py-3.5">
                                <a href="{{ route('projects.show', $row['project']) }}" class="text-sm font-semibold text-slate-900 transition-colors duration-150 group-hover:text-brand-700">{{ $row['project']->title }}</a>
                                <p class="mt-0.5 text-xs text-slate-400">{{ $row['project']->project_code }}</p>
                            </td>
                            <td class="px-4 py-3.5 text-right text-sm tabular-nums text-slate-700">₱{{ number_format((float) $row['budget']['approved_budget'], 2) }}</td>
                            <td class="px-4 py-3.5 text-right text-sm tabular-nums text-slate-700">₱{{ number_format((float) $row['budget']['total_expenses'], 2) }}</td>
                            <td class="px-4 py-3.5 text-right text-sm font-semibold tabular-nums {{ $remaining < 0 ? 'text-red-600' : 'text-slate-900' }}">₱{{ number_format($remaining, 2) }}</td>
                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-2.5">
                                    <x-progress-bar
                                        :percent="$row['budget']['budget_utilization_percent']"
                                        class="w-24 !h-1.5"
                                        :tone="match(true) {
                                            $row['budget']['budget_utilization_percent'] >= 100 => 'red',
                                            $row['budget']['budget_utilization_percent'] >= 80 => 'amber',
                                            default => 'emerald',
                                        }"
                                    />
                                    <span class="text-xs font-semibold tabular-nums text-slate-600">{{ number_format((float) $row['budget']['budget_utilization_percent'], 1) }}%</span>
                                </div>
                            </td>
                            <td class="px-5 py-3.5">
                                <x-status-badge :status="$row['budget']['budget_status']" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-16 text-center">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                    <i data-lucide="wallet" class="h-5 w-5"></i>
                                </div>
                                <p class="mt-3 text-sm font-medium text-slate-600">No projects to monitor yet.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-pager-controls />
        </div>
    </section>
</div>
@endsection
