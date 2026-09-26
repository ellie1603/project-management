@extends('layouts.app')

@section('content')
@php
    $available = (float) $stats['total_approved_budget'];
    $utilization = $available > 0 ? round(((float) $stats['total_expenses'] / $available) * 100, 1) : 0.0;
@endphp

<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <x-page-hero
        eyebrow="Admin Dashboard"
        title="Overview"
        subtitle="Live status of every registered project — budget utilization, schedule health, and items needing your attention."
        class="animate-rise-in"
    >
        <x-slot name="aside">
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('projects.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-accent-500 px-4 py-2.5 text-sm font-semibold text-white shadow-glow hover:bg-accent-600 transition-transform duration-200 ease-elegant hover:-translate-y-0.5">
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    Register Project
                </a>
                <a href="{{ route('projects.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-4 py-2.5 text-sm font-medium text-slate-200 backdrop-blur transition-colors duration-200 hover:bg-white/10 hover:text-white">
                    All Projects
                    <i data-lucide="arrow-right" class="h-4 w-4"></i>
                </a>
            </div>
        </x-slot>

        <x-slot name="footer">
            <div class="grid grid-cols-2 gap-x-4 gap-y-5 sm:grid-cols-3 sm:gap-6">
                @foreach ([
                    ['label' => 'Approved Budget', 'value' => $stats['total_approved_budget'], 'tone' => 'text-white'],
                    ['label' => 'Total Expenses', 'value' => $stats['total_expenses'], 'tone' => 'text-brand-300'],
                    ['label' => 'Remaining', 'value' => $stats['remaining_budget'], 'tone' => 'text-emerald-300'],
                ] as $i => $figure)
                    <div class="min-w-0 animate-rise-in {{ $loop->first ? 'col-span-2 sm:col-span-1' : '' }}" style="animation-delay: {{ 120 + $i * 70 }}ms">
                        <p class="truncate text-[11px] font-medium uppercase tracking-wider text-slate-500">{{ $figure['label'] }}</p>
                        <p class="mt-1.5 truncate font-display text-lg font-semibold tabular-nums tracking-tight sm:text-xl {{ $figure['tone'] }}">
                            ₱{{ number_format($figure['value'], 2) }}
                        </p>
                    </div>
                @endforeach
            </div>

            <div class="mt-6">
                <div class="flex items-center justify-between text-[11px] font-medium text-slate-400">
                    <span>Budget utilization</span>
                    <span class="tabular-nums text-slate-300">{{ $utilization }}%</span>
                </div>
                <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-white/10">
                    <div
                        class="h-full origin-left animate-grow-x rounded-full {{ $utilization >= 100 ? 'bg-red-500' : ($utilization >= 80 ? 'bg-amber-400' : 'bg-white') }}"
                        style="width: {{ min(100, $utilization) }}%; animation-delay: 300ms"
                    ></div>
                </div>
            </div>
        </x-slot>
    </x-page-hero>

    <div class="mt-6 grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 xl:grid-cols-5">
        <x-stat-card label="Total Projects" :value="$stats['total_projects']" icon="folder" :sub="$stats['registered'].' registered'" class="animate-rise-in" style="animation-delay: 60ms" />
        <x-stat-card label="Ongoing" :value="$stats['ongoing']" icon="loader" :sub="$stats['on_hold'].' on hold'" class="animate-rise-in" style="animation-delay: 110ms" />
        <x-stat-card label="Completed" :value="$stats['completed']" icon="circle-check-big" sub="Delivered in full" class="animate-rise-in" style="animation-delay: 160ms" />
        <x-stat-card label="Delayed" :value="$stats['delayed']" icon="triangle-alert" :sub="$stats['over_budget'].' over budget'" class="animate-rise-in" style="animation-delay: 210ms" />
        <x-stat-card label="Pending Requests" value="₱{{ number_format($stats['pending_budget_requests_total'], 2) }}" icon="hand-coins" :sub="$stats['pending_budget_requests'].' awaiting Finance'" class="col-span-2 animate-rise-in md:col-span-1" style="animation-delay: 260ms" />
    </div>

    <div class="mt-6 grid items-start gap-4 xl:grid-cols-3">
        <div class="min-w-0 animate-rise-in xl:col-span-2" style="animation-delay: 260ms">
            @include('dashboard.partials.timeline', ['timelineTasks' => $timelineTasks])
        </div>

        {{-- Side column: sits beside the monitoring list on wide screens, pairs up on laptops, stacks on phones. --}}
        <div class="grid min-w-0 gap-4 lg:grid-cols-2 xl:grid-cols-1">
            <section class="min-w-0 animate-rise-in overflow-hidden rounded-3xl bg-white shadow-soft ring-1 ring-slate-900/5 transition-shadow duration-300 ease-elegant hover:shadow-elevated" style="animation-delay: 310ms">
                <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-4">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl chip-ink text-white shadow-sm">
                        <i data-lucide="chart-pie" class="h-4 w-4"></i>
                    </span>
                    <div class="min-w-0">
                        <h3 class="font-display text-[15px] font-semibold text-slate-900">Project Status</h3>
                        <p class="truncate text-xs text-slate-500">Distribution across the lifecycle.</p>
                    </div>
                </div>

                <div class="flex flex-col items-center gap-6 p-5 sm:flex-row sm:gap-8 lg:flex-col lg:gap-6">
                    <div class="relative h-40 w-40 shrink-0 sm:h-44 sm:w-44">
                        <canvas id="chart-status"></canvas>
                        <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                            <p class="font-display text-[26px] font-semibold tabular-nums leading-none tracking-tight text-slate-900">{{ $stats['total_projects'] }}</p>
                            <p class="mt-1 text-[11px] uppercase tracking-wider text-slate-400">Total</p>
                        </div>
                    </div>

                    @php
                        $statusDots = [
                            'Registered' => 'bg-slate-500',
                            'Ongoing' => 'bg-brand-600',
                            'On Hold' => 'bg-amber-500',
                            'Completed' => 'bg-emerald-500',
                            'Cancelled' => 'bg-red-500',
                        ];
                    @endphp
                    <div class="w-full min-w-0 space-y-1 sm:flex-1 lg:flex-none">
                        @foreach ($charts['status'] as $label => $count)
                            <div class="flex items-center gap-3 rounded-lg px-2 py-1.5 transition-colors duration-150 hover:bg-slate-50">
                                <span data-status-dot="{{ $label }}" class="h-2 w-2 shrink-0 rounded-full {{ $statusDots[$label] ?? 'bg-slate-400' }}"></span>
                                <span class="min-w-0 flex-1 truncate text-[13px] text-slate-600">{{ $label }}</span>
                                <span class="text-[13px] font-semibold tabular-nums text-slate-900">{{ $count }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="min-w-0 animate-rise-in overflow-hidden rounded-3xl bg-white shadow-soft ring-1 ring-slate-900/5 transition-shadow duration-300 ease-elegant hover:shadow-elevated" style="animation-delay: 360ms">
                <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl chip-ink text-white shadow-sm">
                            <i data-lucide="triangle-alert" class="h-4 w-4"></i>
                        </span>
                        <div class="min-w-0">
                            <h3 class="truncate font-display text-[15px] font-semibold text-slate-900">Projects Requiring Attention</h3>
                            <p class="truncate text-xs text-slate-500">Schedule, budget, and documentation exceptions.</p>
                        </div>
                    </div>
                    <span class="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold tabular-nums text-slate-600">{{ $attention->count() }}</span>
                </div>

                <div class="max-h-80 divide-y divide-slate-100 overflow-y-auto">
                    @php
                        $reasonIcons = [
                            'Delayed project' => 'triangle-alert',
                            'Deadline approaching' => 'clock',
                            'Budget approaching limit' => 'wallet',
                            'Budget exceeded' => 'wallet',
                            'No recent progress update' => 'clipboard-x',
                            'Missing required document' => 'file-warning',
                        ];
                    @endphp
                    @forelse ($attention as $item)
                        <a href="{{ route('projects.show', $item['project']) }}" class="group flex items-center justify-between gap-3 px-5 py-3.5 transition-colors duration-150 hover:bg-slate-50/80">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 transition-transform duration-300 ease-elegant group-hover:scale-110">
                                    <i data-lucide="{{ $reasonIcons[$item['reason']] ?? 'triangle-alert' }}" class="h-4 w-4"></i>
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-slate-900 transition-colors duration-150 group-hover:text-brand-700">{{ $item['project']->title }}</p>
                                    <p class="mt-0.5 truncate text-xs text-slate-500">{{ $item['project']->project_code }} · {{ $item['reason'] }}</p>
                                </div>
                            </div>
                            <span class="inline-flex shrink-0 items-center gap-1 text-sm font-medium text-slate-400 transition-all duration-200 ease-elegant group-hover:translate-x-0.5 group-hover:text-brand-600">
                                <span class="hidden sm:inline lg:hidden">Review</span>
                                <i data-lucide="arrow-right" class="h-3.5 w-3.5"></i>
                            </span>
                        </a>
                    @empty
                        <div class="px-5 py-16 text-center">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-600">
                                <i data-lucide="circle-check-big" class="h-5 w-5"></i>
                            </div>
                            <p class="mt-3 text-sm font-medium text-slate-700">Everything is on track.</p>
                            <p class="mt-1 text-xs text-slate-500">No schedule, budget, or documentation exceptions right now.</p>
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</div>

@push('scripts')
<script>
    (() => {
        const render = () => {
            const canvas = document.getElementById('chart-status');

            if (!window.Chart || !canvas) {
                return;
            }

            window.Chart.getChart(canvas)?.destroy();

            const statusLabels = @json($charts['status']->keys());

            // Slice colours come straight from the legend dots, so the chart and its
            // legend can never drift apart and both follow the light/dark theme.
            const dotColor = (label) => {
                const dot = document.querySelector(`[data-status-dot="${CSS.escape(label)}"]`);

                return dot ? getComputedStyle(dot).backgroundColor : window.bmpcColor('--fg-slate-400');
            };

            new Chart(canvas, {
                type: 'doughnut',
                data: {
                    labels: statusLabels,
                    datasets: [{
                        data: @json($charts['status']->values()),
                        backgroundColor: statusLabels.map(dotColor),
                        borderColor: window.bmpcColor('--bg-white'),
                        borderWidth: 3,
                        hoverOffset: 8,
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    cutout: '74%',
                    animation: { duration: 900, easing: 'easeOutQuart' },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            padding: 10,
                            cornerRadius: 10,
                            boxPadding: 4,
                            backgroundColor: '#18181b',
                            titleFont: { weight: '600' },
                        },
                    },
                },
            });
        };

        document.addEventListener('turbo:load', render);
        document.addEventListener('bmpc:theme', render);
    })();
</script>
@endpush
@endsection
