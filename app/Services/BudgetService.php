<?php

namespace App\Services;

use App\Models\Project;
use App\Notifications\BudgetApproachingLimitNotification;
use App\Notifications\BudgetExceededNotification;
use Illuminate\Support\Facades\DB;

class BudgetService
{
    /**
     * @return array<string, string|float>
     */
    private ?float $warningThreshold = null;

    /**
     * Uses the totals preloaded by Project::scopeWithBudgetTotals() when
     * present, so list pages don't run three queries per project.
     *
     * @return array<string, string|float>
     */
    public function summarize(Project $project): array
    {
        $approvedBudget = (float) $project->approved_budget;
        $totalExpenses = $this->total($project, 'expenses_total', fn () => $project->expenses()->sum('amount'));
        $pendingRequests = $this->total($project, 'pending_requests_total', fn () => $project->budgetRequests()->where('status', 'Pending')->sum('amount'));
        $approvedRequests = $this->total($project, 'approved_requests_total', fn () => $project->budgetRequests()->where('status', 'Approved')->sum('amount'));
        $remainingBudget = $approvedBudget - $totalExpenses;
        $utilizationPercent = $approvedBudget > 0
            ? ($totalExpenses / $approvedBudget) * 100
            : 0;
        $warningThreshold = $this->warningThreshold ??= (float) (DB::table('system_settings')
            ->where('key', 'budget_warning_threshold_percent')
            ->value('value') ?? 80);

        $budgetStatus = match (true) {
            $utilizationPercent >= 100 => 'Budget Exceeded',
            $utilizationPercent >= $warningThreshold => 'Approaching Budget Limit',
            default => 'Within Budget',
        };

        return [
            'approved_budget' => number_format($approvedBudget, 2, '.', ''),
            'pending_requests' => number_format($pendingRequests, 2, '.', ''),
            'approved_requests' => number_format($approvedRequests, 2, '.', ''),
            'total_available_budget' => number_format($approvedBudget, 2, '.', ''),
            'total_expenses' => number_format($totalExpenses, 2, '.', ''),
            'remaining_budget' => number_format($remainingBudget, 2, '.', ''),
            'budget_utilization_percent' => round($utilizationPercent, 2),
            'budget_warning_threshold_percent' => $warningThreshold,
            'budget_status' => $budgetStatus,
        ];
    }

    private function total(Project $project, string $preloaded, callable $query): float
    {
        return array_key_exists($preloaded, $project->getAttributes())
            ? (float) $project->getAttribute($preloaded)
            : (float) $query();
    }

    /**
     * Notify Admin/CEO and Finance & Accounting whenever a project's budget
     * utilization crosses the warning or exceeded thresholds. Shared by
     * expense recording and budget request approval, since both change
     * utilization.
     */
    public function checkThresholds(Project $project): void
    {
        $budget = $this->summarize($project);
        $notifier = app(ProjectNotifier::class);

        match ($budget['budget_status']) {
            'Budget Exceeded' => $notifier->notify(
                $notifier->financeTeam(),
                new BudgetExceededNotification($project, (float) $budget['budget_utilization_percent']),
            ),
            'Approaching Budget Limit' => $notifier->notify(
                $notifier->financeTeam(),
                new BudgetApproachingLimitNotification($project, (float) $budget['budget_utilization_percent']),
            ),
            default => null,
        };
    }
}
