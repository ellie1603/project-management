<?php

namespace App\Http\Controllers;

use App\Models\BudgetRequest;
use App\Models\Project;
use App\Models\ProjectExpense;
use App\Services\BudgetService;
use App\Services\TimelineService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * A project sitting in "Registered" without a progress update for this many
     * days is flagged as stalled — it has been approved and entered into the
     * system but implementation has not visibly started.
     */
    private const STALLED_REGISTRATION_DAYS = 10;

    public function __construct(
        private readonly BudgetService $budgetService,
        private readonly TimelineService $timelineService,
    ) {}

    public function index(): View
    {
        $user = Auth::user();

        return match (true) {
            $user->isAdmin() => $this->adminDashboard(),
            $user->isFinance() => $this->financeDashboard(),
            default => $this->personnelDashboard(),
        };
    }

    private function adminDashboard(): View
    {
        $projects = Project::query()->with('category')->get();
        $summaries = $this->summarizeAll($projects);

        $stats = [
            'total_projects' => $projects->count(),
            'registered' => $projects->where('status', 'Registered')->count(),
            'ongoing' => $projects->where('status', 'Ongoing')->count(),
            'on_hold' => $projects->where('status', 'On Hold')->count(),
            'completed' => $projects->where('status', 'Completed')->count(),
            'cancelled' => $projects->where('status', 'Cancelled')->count(),
            'delayed' => $summaries->where('timeline.timeline_status', 'Delayed')->count(),
            'total_approved_budget' => $projects->sum(fn (Project $project): float => (float) $project->approved_budget),
            'total_expenses' => $summaries->sum(fn (array $summary): float => (float) $summary['budget']['total_expenses']),
            'remaining_budget' => $summaries->sum(fn (array $summary): float => (float) $summary['budget']['remaining_budget']),
            'near_budget_limit' => $summaries->where('budget.budget_status', 'Approaching Budget Limit')->count(),
            'over_budget' => $summaries->where('budget.budget_status', 'Budget Exceeded')->count(),
            'pending_budget_requests' => BudgetRequest::query()->where('status', 'Pending')->count(),
            'pending_budget_requests_total' => (float) BudgetRequest::query()->where('status', 'Pending')->sum('amount'),
        ];

        $charts = [
            'status' => $projects->countBy('status'),
        ];

        $attention = $this->attentionItems($projects, $summaries);
        $timelineTasks = $this->buildTimelineTasks($projects, $summaries);

        return view('dashboard.admin', compact('stats', 'charts', 'attention', 'timelineTasks'));
    }

    private function financeDashboard(): View
    {
        $projects = Project::query()->with('category')->get();
        $summaries = $this->summarizeAll($projects);

        $stats = [
            'total_approved_budget' => $projects->sum(fn (Project $project): float => (float) $project->approved_budget),
            'total_expenses' => $summaries->sum(fn (array $summary): float => (float) $summary['budget']['total_expenses']),
            'remaining_budget' => $summaries->sum(fn (array $summary): float => (float) $summary['budget']['remaining_budget']),
            'near_budget_limit' => $summaries->where('budget.budget_status', 'Approaching Budget Limit')->count(),
            'over_budget' => $summaries->where('budget.budget_status', 'Budget Exceeded')->count(),
            'pending_budget_requests' => BudgetRequest::query()->where('status', 'Pending')->count(),
            'pending_budget_requests_total' => (float) BudgetRequest::query()->where('status', 'Pending')->sum('amount'),
        ];

        $stats['overall_utilization_percent'] = $stats['total_approved_budget'] > 0
            ? round(($stats['total_expenses'] / $stats['total_approved_budget']) * 100, 2)
            : 0.0;

        $charts = [
            'budget_status' => $summaries->countBy(fn (array $summary): string => $summary['budget']['budget_status']),
        ];

        $attentionProjects = $projects->values()
            ->filter(fn (Project $project, int $key): bool => in_array($summaries[$key]['budget']['budget_status'], ['Approaching Budget Limit', 'Budget Exceeded'], true))
            ->map(fn (Project $project, int $key): array => ['project' => $project, 'budget' => $summaries[$key]['budget']])
            ->sortByDesc(fn (array $row): float => (float) $row['budget']['budget_utilization_percent'])
            ->take(8)
            ->values();

        $recentExpenses = ProjectExpense::query()
            ->with('project:id,project_code,title')
            ->latest('expense_date')
            ->take(8)
            ->get();

        return view('dashboard.finance', compact('stats', 'charts', 'attentionProjects', 'recentExpenses'));
    }

    private function personnelDashboard(): View
    {
        $user = Auth::user();
        $projects = $user->assignedProjects()->with('category')->get();
        $summaries = $this->summarizeAll($projects);

        $stats = [
            'assigned' => $projects->count(),
            'ongoing' => $projects->where('status', 'Ongoing')->count(),
            'completed' => $projects->where('status', 'Completed')->count(),
            'delayed' => $summaries->where('timeline.timeline_status', 'Delayed')->count(),
            'upcoming_deadlines' => $summaries->filter(fn (array $summary): bool => $summary['timeline']['timeline_status'] === 'Approaching Deadline')->count(),
        ];

        $projectRows = $projects->values()->map(function (Project $project, int $key) use ($summaries): array {
            $latestUpdate = $project->progress()->latest('progress_date')->first();

            return [
                'project' => $project,
                'completion_percentage' => $latestUpdate?->progress_percentage ?? 0,
                'timeline_status' => $summaries[$key]['timeline']['timeline_status'],
                'budget_status' => $summaries[$key]['budget']['budget_status'],
                'latest_update' => $latestUpdate,
                'required_action' => $this->requiredAction($summaries[$key]),
            ];
        });

        $timelineTasks = $this->buildTimelineTasks($projects, $summaries);

        return view('dashboard.personnel', compact('stats', 'projectRows', 'timelineTasks'));
    }

    /**
     * Build the monitoring board rows: one health summary per scheduled project,
     * ordered most-urgent-first rather than chronologically, because the question
     * this panel answers is "what needs attention right now", not "what is scheduled
     * when". Each row carries both work completed and schedule elapsed so the view
     * can surface projects burning time faster than they are delivering work.
     *
     * @param  Collection<int, Project>  $projects
     * @param  Collection<int, array{budget: array<string, mixed>, timeline: array<string, mixed>}>  $summaries
     * @return array<int, array<string, mixed>>
     */
    private function buildTimelineTasks(Collection $projects, Collection $summaries): array
    {
        $severity = ['delayed' => 0, 'approaching' => 1, 'onhold' => 2, 'ongoing' => 3, 'registered' => 4, 'completed' => 5];

        return $projects->values()
            ->map(fn (Project $project, int $key): array => [
                'project' => $project,
                'timeline' => $summaries[$key]['timeline'],
                'budget' => $summaries[$key]['budget'],
            ])
            ->filter(fn (array $row): bool => filled($row['project']->planned_start_date) && filled($row['project']->target_completion_date))
            ->map(function (array $row): array {
                $project = $row['project'];
                $timeline = $row['timeline'];
                $timelineStatus = $timeline['timeline_status'];

                [$statusKey, $statusLabel] = match (true) {
                    $project->status === 'Completed' => ['completed', 'Completed'],
                    $timelineStatus === 'Delayed' => ['delayed', 'Delayed'],
                    $project->status === 'On Hold' => ['onhold', 'On Hold'],
                    $project->status === 'Registered' => ['registered', 'Registered'],
                    $timelineStatus === 'Approaching Deadline' => ['approaching', 'Approaching Deadline'],
                    default => ['ongoing', 'On Schedule'],
                };

                $start = Carbon::parse($project->planned_start_date);
                $target = Carbon::parse($project->target_completion_date);
                $duration = (int) $timeline['duration'];
                $elapsedPercent = $duration > 0
                    ? (int) round(min(100, max(0, $timeline['days_elapsed'] / $duration * 100)))
                    : ($timelineStatus === 'Not Started' ? 0 : 100);

                $progress = (int) ($project->progress()->latest('progress_date')->value('progress_percentage') ?? 0);
                $behindPace = $statusKey !== 'completed' && $statusKey !== 'registered' && ($elapsedPercent - $progress) >= 20;

                $daysRegistered = (int) $project->created_at->diffInDays(now());
                $stalled = $statusKey === 'registered' && $daysRegistered >= self::STALLED_REGISTRATION_DAYS;

                $overBudget = $row['budget']['budget_status'] === 'Budget Exceeded';
                $nearBudget = $row['budget']['budget_status'] === 'Approaching Budget Limit';

                $riskScore = ($statusKey === 'delayed' ? 100 : 0)
                    + ($overBudget ? 80 : 0)
                    + ($statusKey === 'approaching' ? 50 : 0)
                    + ($nearBudget ? 40 : 0)
                    + ($stalled ? 30 : 0)
                    + ($behindPace ? 20 : 0)
                    + ($statusKey === 'onhold' ? 10 : 0);

                $needsAttention = in_array($statusKey, ['delayed', 'approaching'], true) || $overBudget || $nearBudget || $stalled;

                return [
                    'code' => $project->project_code,
                    'title' => $project->title,
                    'progress' => $progress,
                    'elapsed_percent' => $elapsedPercent,
                    'behind_pace' => $behindPace,
                    'status_key' => $statusKey,
                    'status_label' => $statusLabel,
                    'days_remaining' => $timeline['days_remaining'],
                    'delay_days' => $timeline['delay_days'],
                    'days_registered' => $daysRegistered,
                    'stalled' => $stalled,
                    'over_budget' => $overBudget,
                    'near_budget' => $nearBudget,
                    'budget_utilization_percent' => $row['budget']['budget_utilization_percent'],
                    'needs_attention' => $needsAttention,
                    'risk_score' => $riskScore,
                    'start_label' => $start->format('M j, Y'),
                    'target_label' => $target->format('M j, Y'),
                    'duration_label' => $duration >= 30 ? round($duration / 30).' mo' : $duration.' d',
                    'url' => route('projects.show', $project),
                ];
            })
            ->sortBy(fn (array $row): array => [-$row['risk_score'], $severity[$row['status_key']], -$row['elapsed_percent']])
            ->take(15)
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Project>  $projects
     * @return Collection<int, array{budget: array<string, mixed>, timeline: array<string, mixed>}>
     */
    private function summarizeAll(Collection $projects): Collection
    {
        return $projects->values()->map(fn (Project $project): array => [
            'budget' => $this->budgetService->summarize($project),
            'timeline' => $this->timelineService->summarize($project),
        ]);
    }

    /**
     * @param  Collection<int, Project>  $projects
     * @param  Collection<int, array{budget: array<string, mixed>, timeline: array<string, mixed>}>  $summaries
     * @return Collection<int, array{project: Project, reason: string}>
     */
    private function attentionItems(Collection $projects, Collection $summaries): Collection
    {
        $items = collect();

        foreach ($projects->values() as $key => $project) {
            /** @var Project $project */
            $summary = $summaries[$key];

            if ($summary['timeline']['timeline_status'] === 'Delayed') {
                $items->push(['project' => $project, 'reason' => 'Delayed project']);
            }

            if ($summary['timeline']['timeline_status'] === 'Approaching Deadline') {
                $items->push(['project' => $project, 'reason' => 'Deadline approaching']);
            }

            if ($summary['budget']['budget_status'] === 'Approaching Budget Limit') {
                $items->push(['project' => $project, 'reason' => 'Budget approaching limit']);
            }

            if ($summary['budget']['budget_status'] === 'Budget Exceeded') {
                $items->push(['project' => $project, 'reason' => 'Budget exceeded']);
            }

            if (in_array($project->status, ['Registered', 'Ongoing'], true)) {
                $latestProgress = $project->progress()->latest('progress_date')->first();

                if ($latestProgress === null || $latestProgress->progress_date->lt(now()->subDays(14))) {
                    $items->push(['project' => $project, 'reason' => 'No recent progress update']);
                }

                if (! $project->documents()->where('document_type', 'Approved Design')->exists()) {
                    $items->push(['project' => $project, 'reason' => 'Missing required document']);
                }
            }
        }

        return $items;
    }

    /**
     * @param  array{budget: array<string, mixed>, timeline: array<string, mixed>}  $summary
     */
    private function requiredAction(array $summary): ?string
    {
        return match (true) {
            $summary['timeline']['timeline_status'] === 'Delayed' => 'Submit an updated timeline and explain the delay.',
            $summary['timeline']['timeline_status'] === 'Approaching Deadline' => 'Prioritize remaining activities before the deadline.',
            $summary['budget']['budget_status'] === 'Budget Exceeded' => 'Coordinate with Finance regarding the budget overrun.',
            default => null,
        };
    }
}
