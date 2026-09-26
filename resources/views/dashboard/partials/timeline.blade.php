@php
    $rows = collect($timelineTasks);

    // Rows are already sorted most-urgent-first, so paging keeps the card a fixed,
    // scannable height while page 1 always shows what needs attention.
    $perPage = $perPage ?? 5;

    $health = [
        'delayed' => ['label' => 'Delayed', 'dot' => 'bg-red-500', 'accent' => 'bg-red-500', 'bar' => 'bg-red-500'],
        'approaching' => ['label' => 'Due soon', 'dot' => 'bg-amber-500', 'accent' => 'bg-amber-500', 'bar' => 'bg-amber-400'],
        'onhold' => ['label' => 'On hold', 'dot' => 'bg-amber-400', 'accent' => 'bg-amber-400', 'bar' => 'bg-amber-300'],
        'ongoing' => ['label' => 'On schedule', 'dot' => 'bg-emerald-500', 'accent' => 'bg-emerald-500', 'bar' => 'bg-emerald-500'],
        'registered' => ['label' => 'Not started', 'dot' => 'bg-slate-300', 'accent' => 'bg-slate-300', 'bar' => 'bg-slate-300'],
        'completed' => ['label' => 'Completed', 'dot' => 'bg-slate-900', 'accent' => 'bg-slate-900', 'bar' => 'bg-slate-700'],
    ];

    $needsAttention = $rows->where('needs_attention', true)->count();

    // Grouped by what kind of problem it is, because a bare colored dot can't tell
    // you whether "red" means a missed deadline or an overspend. The group label
    // carries the domain, the dot colour carries severity (red = critical, amber =
    // warning), and every tag that can appear on a row below is listed here.
    $signalGroups = [
        [
            'label' => 'Schedule',
            'items' => [
                ['label' => 'Delayed', 'dot' => 'bg-red-500', 'count' => $rows->where('status_key', 'delayed')->count()],
                ['label' => 'Due soon', 'dot' => 'bg-amber-500', 'count' => $rows->where('status_key', 'approaching')->count()],
            ],
        ],
        [
            'label' => 'Budget',
            'items' => [
                ['label' => 'Over budget', 'dot' => 'bg-red-500', 'count' => $rows->where('over_budget', true)->count()],
                ['label' => 'Near limit', 'dot' => 'bg-amber-500', 'count' => $rows->where('near_budget', true)->count()],
            ],
        ],
        [
            'label' => 'Progress',
            'items' => [
                ['label' => 'Behind pace', 'dot' => 'bg-red-500', 'count' => $rows->where('behind_pace', true)->count()],
                ['label' => 'Stalled', 'dot' => 'bg-slate-400', 'count' => $rows->where('stalled', true)->count()],
            ],
        ],
    ];

    $visibleGroups = collect($signalGroups)
        ->map(fn (array $group): array => [...$group, 'items' => array_filter($group['items'], fn (array $item): bool => $item['count'] > 0)])
        ->filter(fn (array $group): bool => count($group['items']) > 0)
        ->values();
@endphp

<section
    class="flex flex-col overflow-hidden rounded-3xl bg-white shadow-soft ring-1 ring-slate-900/5 transition-shadow duration-300 ease-elegant hover:shadow-elevated"
    x-data="{ page: 1, perPage: {{ $perPage }}, total: {{ $rows->count() }}, get pages() { return Math.max(1, Math.ceil(this.total / this.perPage)); } }"
>
    <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
        <div class="flex min-w-0 items-center gap-3">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl chip-ink text-white shadow-sm">
                <i data-lucide="activity" class="h-4 w-4"></i>
            </span>
            <div class="min-w-0">
                <h3 class="font-display text-[15px] font-semibold text-slate-900">Project Monitoring</h3>
                <p class="truncate text-xs text-slate-500">
                    @if ($rows->isEmpty())
                        Live schedule and budget health across all projects.
                    @elseif ($needsAttention > 0)
                        {{ $needsAttention }} of {{ $rows->count() }} project{{ $rows->count() === 1 ? '' : 's' }} need attention — most urgent first.
                    @else
                        All {{ $rows->count() }} scheduled project{{ $rows->count() === 1 ? '' : 's' }} are tracking on schedule.
                    @endif
                </p>
            </div>
        </div>
        <a href="{{ route('projects.index') }}" class="inline-flex shrink-0 items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-slate-600 ring-1 ring-slate-200 transition-colors duration-150 hover:bg-slate-50 hover:text-slate-900">
            View all
            <i data-lucide="arrow-right" class="h-3.5 w-3.5"></i>
        </a>
    </div>

    @if ($visibleGroups->isNotEmpty())
        <div class="flex flex-wrap items-center gap-x-5 gap-y-2.5 border-b border-slate-100 bg-slate-50/70 px-5 py-3">
            @foreach ($visibleGroups as $group)
                @if (! $loop->first)
                    <span class="hidden h-4 w-px bg-slate-200 sm:block"></span>
                @endif
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1.5">
                    <span class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">{{ $group['label'] }}</span>
                    @foreach ($group['items'] as $item)
                        <span class="inline-flex items-center gap-1.5 text-[12px] text-slate-600">
                            <span class="h-2 w-2 shrink-0 rounded-full {{ $item['dot'] }}"></span>
                            <span class="font-semibold tabular-nums text-slate-900">{{ $item['count'] }}</span>
                            {{ $item['label'] }}
                        </span>
                    @endforeach
                </div>
            @endforeach
        </div>
    @endif

    @if ($rows->isEmpty())
        <div class="p-5">
            <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50/60 py-12 text-center">
                <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-white text-slate-400 shadow-soft">
                    <i data-lucide="activity" class="h-5 w-5"></i>
                </div>
                <p class="mt-3 text-sm font-medium text-slate-600">Nothing to monitor yet.</p>
                <p class="mt-1 text-xs text-slate-500">Projects appear here once they have a planned start and target completion date.</p>
            </div>
        </div>
    @else
        <div class="divide-y divide-slate-100">
            @foreach ($rows as $row)
                @php
                    $meta = $health[$row['status_key']];
                    $dayWord = fn (int $n): string => $n === 1 ? 'day' : 'days';

                    // Work bar keeps one fixed colour: it reports delivery, not schedule
                    // health. The time bar is the one that changes colour, so a red bar
                    // always means exactly one thing — the schedule is in trouble.
                    $workTone = $row['status_key'] === 'completed'
                        ? 'bg-emerald-500'
                        : 'bg-brand-600';

                    $timeTone = match (true) {
                        $row['status_key'] === 'completed' => 'bg-slate-300',
                        $row['status_key'] === 'delayed' => 'bg-red-500',
                        $row['behind_pace'] || $row['status_key'] === 'approaching' => 'bg-amber-400',
                        default => 'bg-slate-300',
                    };

                    $timeTextTone = match (true) {
                        $row['status_key'] === 'delayed' => 'text-red-600',
                        $row['behind_pace'] || $row['status_key'] === 'approaching' => 'text-amber-600',
                        default => 'text-slate-500',
                    };

                    $workTooltip = $row['progress'].'% of work reported complete'
                        .($row['behind_pace'] ? ' — behind the '.$row['elapsed_percent'].'% of time already used' : '');

                    $timeTooltip = match (true) {
                        $row['status_key'] === 'completed' => 'Completed — schedule closed',
                        $row['status_key'] === 'delayed' => 'Delayed — '.$row['delay_days'].' '.$dayWord($row['delay_days']).' past the target date',
                        $row['stalled'] => 'Stalled — registered '.$row['days_registered'].' '.$dayWord($row['days_registered']).' ago, not started',
                        $row['status_key'] === 'registered' => 'Not started — begins '.$row['start_label'],
                        default => $row['elapsed_percent'].'% of schedule used · '.$row['days_remaining'].' '.$dayWord($row['days_remaining']).' left'
                            .($row['behind_pace'] ? ' · behind pace' : ' · on schedule'),
                    };
                @endphp
                <a
                    href="{{ $row['url'] }}"
                    class="group relative block animate-rise-in px-5 py-4 pl-6 transition-colors duration-200 ease-smooth hover:bg-slate-50/70"
                    style="animation-delay: {{ ($loop->index % $perPage) * 40 }}ms; {{ $loop->iteration > $perPage ? 'display: none;' : '' }}"
                    x-show="Math.ceil({{ $loop->iteration }} / perPage) === page"
                >
                    <span class="absolute inset-y-0 left-0 w-[3px] {{ $meta['accent'] }} opacity-70 transition-opacity duration-200 group-hover:opacity-100"></span>

                    <div class="flex flex-wrap items-start justify-between gap-x-4 gap-y-2">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="truncate font-display text-sm font-semibold text-slate-900 transition-colors duration-150 group-hover:text-brand-700">{{ $row['title'] }}</p>
                                @if ($row['behind_pace'])
                                    <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-red-100 px-2 py-0.5 text-[11px] font-medium text-red-700" title="Time is being used faster than work is being delivered">
                                        <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-red-500"></span>
                                        Behind pace
                                    </span>
                                @endif
                                @if ($row['over_budget'])
                                    <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-red-100 px-2 py-0.5 text-[11px] font-medium text-red-700" title="Expenses have exceeded the available budget">
                                        <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-red-500"></span>
                                        Over budget
                                    </span>
                                @elseif ($row['near_budget'])
                                    <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-medium text-amber-700" title="Expenses have passed the budget warning threshold">
                                        <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-amber-500"></span>
                                        Near limit
                                    </span>
                                @endif
                                @if ($row['stalled'])
                                    <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-slate-200 px-2 py-0.5 text-[11px] font-medium text-slate-700" title="Registered but implementation has not started">
                                        <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-slate-400"></span>
                                        Stalled
                                    </span>
                                @endif
                            </div>
                            <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-slate-500">
                                <span class="font-medium text-slate-400">{{ $row['code'] }}</span>
                                <span class="inline-flex items-center gap-1">
                                    <i data-lucide="calendar" class="h-3 w-3"></i>
                                    Due {{ $row['target_label'] }}
                                </span>
                                <span class="inline-flex items-center gap-1">
                                    <i data-lucide="hourglass" class="h-3 w-3"></i>
                                    @if ($row['status_key'] === 'delayed')
                                        <span class="font-semibold text-red-600">{{ $row['delay_days'] }} day{{ $row['delay_days'] === 1 ? '' : 's' }} overdue</span>
                                    @elseif ($row['status_key'] === 'completed')
                                        Delivered
                                    @elseif ($row['stalled'])
                                        <span class="font-semibold text-slate-600">Registered {{ $row['days_registered'] }} days ago — not started</span>
                                    @elseif ($row['status_key'] === 'registered')
                                        Starts {{ $row['start_label'] }}
                                    @else
                                        <span class="{{ $row['status_key'] === 'approaching' ? 'font-semibold text-amber-600' : '' }}">{{ $row['days_remaining'] }} day{{ $row['days_remaining'] === 1 ? '' : 's' }} left</span>
                                    @endif
                                </span>
                            </p>
                        </div>

                        <x-status-badge :status="$row['status_label']" class="shrink-0" />
                    </div>

                    <div class="mt-3.5 grid gap-x-8 gap-y-2.5 sm:grid-cols-2">
                        <div class="relative" x-data="{ show: false }" @mouseenter="show = true" @mouseleave="show = false">
                            <div class="flex items-center justify-between text-[11px]">
                                <span class="font-medium text-slate-500">Work completed</span>
                                <span class="font-semibold tabular-nums text-slate-900">{{ $row['progress'] }}%</span>
                            </div>
                            <div class="mt-1.5 h-2 w-full overflow-hidden rounded-full bg-slate-100">
                                <div
                                    class="h-full origin-left animate-grow-x rounded-full {{ $workTone }}"
                                    style="width: {{ $row['progress'] }}%; animation-delay: {{ min($loop->index, 10) * 40 + 100 }}ms"
                                ></div>
                            </div>
                            <div
                                x-show="show"
                                x-transition:enter="transition ease-elegant duration-150"
                                x-transition:enter-start="opacity-0 translate-y-1"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                x-transition:leave="transition ease-in duration-100"
                                x-transition:leave-start="opacity-100"
                                x-transition:leave-end="opacity-0"
                                class="pointer-events-none absolute bottom-full left-0 z-20 mb-2 w-max max-w-full rounded-lg bg-slate-900 px-2.5 py-1.5 text-[11px] font-medium text-white shadow-premium"
                                style="display: none;"
                            >
                                {{ $workTooltip }}
                            </div>
                        </div>

                        <div class="relative" x-data="{ show: false }" @mouseenter="show = true" @mouseleave="show = false">
                            <div class="flex items-center justify-between text-[11px]">
                                <span class="font-medium text-slate-500">Time elapsed</span>
                                <span class="font-semibold tabular-nums {{ $timeTextTone }}">{{ $row['elapsed_percent'] }}%</span>
                            </div>
                            <div class="mt-1.5 h-2 w-full overflow-hidden rounded-full bg-slate-100">
                                <div
                                    class="h-full origin-left animate-grow-x rounded-full {{ $timeTone }}"
                                    style="width: {{ $row['elapsed_percent'] }}%; animation-delay: {{ min($loop->index, 10) * 40 + 160 }}ms"
                                ></div>
                            </div>
                            <div
                                x-show="show"
                                x-transition:enter="transition ease-elegant duration-150"
                                x-transition:enter-start="opacity-0 translate-y-1"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                x-transition:leave="transition ease-in duration-100"
                                x-transition:leave-start="opacity-100"
                                x-transition:leave-end="opacity-0"
                                class="pointer-events-none absolute bottom-full left-0 z-20 mb-2 w-max max-w-full rounded-lg bg-slate-900 px-2.5 py-1.5 text-[11px] font-medium text-white shadow-premium"
                                style="display: none;"
                            >
                                {{ $timeTooltip }}
                            </div>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        @if ($rows->count() > $perPage)
            <div class="mt-auto flex items-center justify-between gap-3 border-t border-slate-100 bg-slate-50/60 px-5 py-3">
                <p class="text-xs text-slate-500">
                    Showing
                    <span class="font-semibold tabular-nums text-slate-900" x-text="((page - 1) * perPage + 1) + '–' + Math.min(page * perPage, total)">1–{{ $perPage }}</span>
                    of <span class="font-semibold tabular-nums text-slate-900">{{ $rows->count() }}</span>
                </p>

                <nav class="flex items-center gap-1" aria-label="Project monitoring pages">
                    <button
                        type="button"
                        @click="page = Math.max(1, page - 1)"
                        :disabled="page === 1"
                        class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 ring-1 ring-slate-200 transition-colors duration-150 hover:bg-white hover:text-slate-900 disabled:pointer-events-none disabled:opacity-40"
                        aria-label="Previous page"
                    >
                        <i data-lucide="chevron-left" class="h-4 w-4"></i>
                    </button>

                    @php $pageCount = (int) ceil($rows->count() / $perPage); @endphp
                    @if ($pageCount <= 7)
                        <div class="hidden items-center gap-1 sm:flex">
                            @for ($n = 1; $n <= $pageCount; $n++)
                                <button
                                    type="button"
                                    @click="page = {{ $n }}"
                                    :class="page === {{ $n }} ? 'bg-brand-600 text-white shadow-sm' : 'text-slate-600 hover:bg-white hover:text-slate-900'"
                                    class="h-8 min-w-[2rem] rounded-lg px-2 text-xs font-semibold tabular-nums transition-colors duration-150"
                                    :aria-current="page === {{ $n }} ? 'page' : null"
                                >{{ $n }}</button>
                            @endfor
                        </div>
                    @endif
                    <span class="px-1.5 text-xs font-medium tabular-nums text-slate-500 {{ $pageCount <= 7 ? 'sm:hidden' : '' }}" x-text="page + ' / ' + pages">1 / {{ $pageCount }}</span>

                    <button
                        type="button"
                        @click="page = Math.min(pages, page + 1)"
                        :disabled="page === pages"
                        class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 ring-1 ring-slate-200 transition-colors duration-150 hover:bg-white hover:text-slate-900 disabled:pointer-events-none disabled:opacity-40"
                        aria-label="Next page"
                    >
                        <i data-lucide="chevron-right" class="h-4 w-4"></i>
                    </button>
                </nav>
            </div>
        @endif
    @endif
</section>
