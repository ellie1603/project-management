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
    public function summarize(Project $project): array
    {
        $approvedBudget = (float) $project->approved_budget;
        $totalExpenses = (float) $project->expenses()->sum('amount');
        $pendingRequests = (float) $project->budgetRequests()->where('status', 'Pending')->sum('amount');
        $approvedRequests = (float) $project->budgetRequests()->where('status', 'Approved')->sum('amount');
        $remainingBudget = $approvedBudget - $totalExpenses;
        $utilizationPercent = $approvedBudget > 0
            ? ($totalExpenses / $approvedBudget) * 100
            : 0;
        $warningThreshold = (float) DB::table('system_settings')
            ->where('key', 'budget_warning_threshold_percent')
            ->value('value', 80);

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
