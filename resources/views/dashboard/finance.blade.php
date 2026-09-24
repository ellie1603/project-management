@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <x-page-hero
        eyebrow="Finance & Accounting"
        title="Budget Monitoring"
        subtitle="Organization-wide financial utilization across every registered project."
        class="animate-rise-in"
    >
        <x-slot name="aside">
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('budget.expenses') }}" class="btn-sheen inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-900 shadow-glow transition-transform duration-200 ease-elegant hover:-translate-y-0.5">
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
                    class="h-full origin-left animate-grow-x rounded-full bg-gradient-to-r {{ $stats['overall_utilization_percent'] >= 100 ? 'from-red-500 to-rose-400' : ($stats['overall_utilization_percent'] >= 80 ? 'from-amber-400 to-orange-400' : 'from-slate-300 to-white') }}"
                    style="width: {{ min(100, (float) $stats['overall_utilization_percent']) }}%; animation-delay: 250ms"
                ></div>
            </div>
        </x-slot>
    </x-page-hero>

    <div class="mt-6 grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 xl:grid-cols-5">
        <x-stat-card label="Approved Budget" value="₱{{ number_format($stats['total_approved_budget'], 2) }}" icon="wallet" sub="Baseline allocation" class="animate-rise-in" style="animation-delay: 60ms" />
        <x-stat-card label="Total Expenses" value="₱{{ number_format($stats['total_expenses'], 2) }}" icon="calculator" sub="Recorded to date" class="animate-rise-in" style="animation-delay: 110ms" />
        <x-stat-card label="Remaining Budget" value="₱{{ number_format($stats['remaining_budget'], 2) }}" icon="piggy-bank" sub="Available to spend" class="animate-rise-in" style="animation-delay: 160ms" />
        <x-stat-card label="Over Budget" :value="$stats['over_budget']" icon="triangle-alert" :sub="$stats['near_budget_limit'].' near limit'" class="animate-rise-in" style="animation-delay: 210ms" />
        <x-stat-card label="Pending Requests" value="₱{{ number_format($stats['pending_budget_requests_total'], 2) }}" icon="hand-coins" :sub="$stats['pending_budget_requests'].' to review'" class="col-span-2 animate-rise-in md:col-span-1" style="animation-delay: 260ms" />
    </div>

    <div class="mt-6 grid gap-4 lg:grid-cols-3">
        <div class="animate-rise-in rounded-2xl border border-slate-200/80 bg-white p-5 shadow-soft transition-shadow duration-300 ease-elegant hover:shadow-elevated lg:col-span-1" style="animation-delay: 200ms">
            <h3 class="font-display text-sm font-semibold text-slate-900">Budget Status Breakdown</h3>
            <div class="mt-3 h-56">
                <canvas id="chart-budget-status"></canvas>
            </div>
        </div>

        <div class="animate-rise-in rounded-2xl border border-slate-200/80 bg-white shadow-soft transition-shadow duration-300 ease-elegant hover:shadow-elevated lg:col-span-1" style="animation-delay: 240ms">
            <div class="border-b border-slate-100 px-5 py-4">
                <h3 class="font-display text-sm font-semibold text-slate-900">Projects Needing Attention</h3>
                <p class="text-xs text-slate-500">Approaching limit or over budget.</p>
            </div>
            <div class="max-h-56 divide-y divide-slate-100 overflow-y-auto">
                @forelse ($attentionProjects as $row)
                    <a href="{{ route('projects.budget.show', $row['project']) }}" class="flex items-center justify-between gap-3 px-5 py-3 transition-colors duration-150 hover:bg-slate-50/80">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-slate-900">{{ $row['project']->title }}</p>
                            <p class="text-xs text-slate-500">{{ number_format((float) $row['budget']['budget_utilization_percent'], 1) }}% utilized</p>
                        </div>
                        <x-status-badge :status="$row['budget']['budget_status']" />
                    </a>
                @empty
                    <div class="px-5 py-10 text-center text-sm text-slate-500">No projects need attention right now.</div>
                @endforelse
            </div>
        </div>

        <div class="animate-rise-in rounded-2xl border border-slate-200/80 bg-white shadow-soft transition-shadow duration-300 ease-elegant hover:shadow-elevated lg:col-span-1" style="animation-delay: 280ms">
            <div class="border-b border-slate-100 px-5 py-4">
                <h3 class="font-display text-sm font-semibold text-slate-900">Recent Budget Activity</h3>
                <p class="text-xs text-slate-500">Latest recorded expenses.</p>
            </div>
            <div class="max-h-56 divide-y divide-slate-100 overflow-y-auto">
                @forelse ($recentExpenses as $expense)
                    <div class="px-5 py-3">
                        <div class="flex items-center justify-between gap-3">
                            <p class="truncate text-sm font-medium text-slate-900">{{ $expense->project?->title ?? 'Unknown project' }}</p>
                            <p class="shrink-0 text-sm font-semibold tabular-nums text-slate-700">₱{{ number_format((float) $expense->amount, 2) }}</p>
                        </div>
                        <p class="mt-0.5 text-xs text-slate-500">{{ $expense->category }} · {{ \Illuminate\Support\Carbon::parse($expense->expense_date)->format('M d, Y') }}</p>
                    </div>
                @empty
                    <div class="px-5 py-10 text-center text-sm text-slate-500">No expenses recorded yet.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    (() => {
        const render = () => {
            const canvas = document.getElementById('chart-budget-status');

            if (!window.Chart || !canvas) {
                return;
            }

            window.Chart.getChart(canvas)?.destroy();

            const budgetColors = {
                'Within Budget': '#059669',
                'Approaching Budget Limit': '#f59e0b',
                'Budget Exceeded': '#ef4444',
            };
            const budgetLabels = @json($charts['budget_status']->keys());

            new Chart(canvas, {
                type: 'doughnut',
                data: {
                    labels: budgetLabels,
                    datasets: [{
                        data: @json($charts['budget_status']->values()),
                        backgroundColor: budgetLabels.map((label) => budgetColors[label] ?? window.bmpcColor('--fg-slate-400')),
                        borderColor: window.bmpcColor('--bg-white'),
                        borderWidth: 2,
                        hoverOffset: 6,
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    cutout: '68%',
                    animation: { duration: 900, easing: 'easeOutQuart', animateRotate: true, animateScale: true },
                    plugins: { legend: { position: 'bottom', labels: { color: window.bmpcColor('--fg-slate-600'), boxWidth: 8, usePointStyle: true, pointStyle: 'circle', padding: 14 } } },
                },
            });
        };

        document.addEventListener('turbo:load', render);
        document.addEventListener('bmpc:theme', render);
    })();
</script>
@endpush
@endsection
