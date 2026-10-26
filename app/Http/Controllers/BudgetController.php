<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectExpense;
use App\Services\AuditLogger;
use App\Services\BudgetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BudgetController extends Controller
{
    public function __construct(private readonly BudgetService $budgetService) {}

    public function storeExpense(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('view', $project);
        $this->authorize('create', ProjectExpense::class);

        $validated = $request->validate([
            'expense_date' => ['required', 'date', 'before_or_equal:today'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'reference_number' => ['nullable', 'string', 'max:100'],
        ], ['expense_date.before_or_equal' => 'The date cannot be in the future.']);

        $expense = $project->expenses()->create([
            ...$validated,
            'created_by' => Auth::id(),
        ]);

        app(AuditLogger::class)->record($request, 'created', 'project_expenses', $expense->id, 'Recorded expense of ₱'.number_format((float) $expense->amount, 2)." for {$project->title}.", null, $expense->toArray());

        $this->budgetService->checkThresholds($project);

        return redirect()->route('projects.show', $project)
            ->with('status', 'Expense recorded.')
            ->with('tab', 'budget');
    }

    public function show(Project $project): View
    {
        $this->authorize('viewBudget', $project);
        $project->load(['expenses.creator', 'expenses.reversal:id,reverses_expense_id', 'expenses.reverses:id,reference_number', 'budgetRequests.requester', 'budgetRequests.reviewer']);

        return view('projects.budget', compact('project'));
    }

    /**
     * Org-wide budget overview: aggregate totals plus a compact per-project
     * table. Distinct from Reports (a formal PDF/Excel export) and from the
     * Projects list (a whole-project view) — this page is finance-totals
     * first.
     */
    public function overview(Request $request): View
    {
        $this->authorizeFinanceAccess();

        $projects = Project::query()->withBudgetTotals()->with('category')->get();
        $summaries = $projects->mapWithKeys(fn (Project $project): array => [$project->id => $project->budgetSummary()]);

        $stats = [
            'total_approved_budget' => $projects->sum(fn (Project $project): float => (float) $project->approved_budget),
            'total_expenses' => $summaries->sum(fn (array $summary): float => (float) $summary['total_expenses']),
            'remaining_budget' => $summaries->sum(fn (array $summary): float => (float) $summary['remaining_budget']),
            'pending_requests' => $summaries->sum(fn (array $summary): float => (float) $summary['pending_requests']),
            'approved_requests' => $summaries->sum(fn (array $summary): float => (float) $summary['approved_requests']),
        ];
        $stats['overall_utilization_percent'] = $stats['total_approved_budget'] > 0
            ? round(($stats['total_expenses'] / $stats['total_approved_budget']) * 100, 2)
            : 0.0;

        $projectRows = $projects->map(fn (Project $project): array => [
            'project' => $project,
            'budget' => $summaries[$project->id],
        ])->sortByDesc(fn (array $row): float => (float) $row['budget']['budget_utilization_percent'])->values();

        return view('budget.overview', compact('stats', 'projectRows'));
    }

    public function expensesLog(Request $request): View
    {
        $this->authorizeFinanceAccess();

        $expenses = ProjectExpense::query()
            ->with(['project:id,project_code,title', 'creator:id,name', 'reversal:id,reverses_expense_id', 'reverses:id,reference_number'])
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where(function ($nested) use ($search): void {
                    $nested->where('description', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%")
                        ->orWhere('payee', 'like', "%{$search}%")
                        ->orWhere('reference_number', 'like', "%{$search}%")
                        ->orWhereHas('project', fn ($projectQuery) => $projectQuery->where('title', 'like', "%{$search}%")
                            ->orWhere('project_code', 'like', "%{$search}%"));
                });
            })
            ->latest('expense_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return $this->respond($request, 'budget.expenses', 'budget.partials.expenses-results', compact('expenses'));
    }

    /**
     * Cancels a recorded expense with a negative entry instead of editing or
     * deleting it. The original stays in the history, marked as reversed.
     */
    public function reverseExpense(Request $request, ProjectExpense $expense): RedirectResponse
    {
        $this->authorize('create', ProjectExpense::class);
        $this->authorize('view', $expense->project);

        $validated = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $reversal = DB::transaction(function () use ($expense, $validated): ProjectExpense {
            // Locked so two people reversing the same expense can't both succeed.
            $original = ProjectExpense::query()->whereKey($expense->getKey())->lockForUpdate()->firstOrFail();

            abort_if($original->isReversal(), 422, 'A reversal cannot itself be reversed.');
            abort_if($original->isReversed(), 422, 'This expense has already been reversed.');

            return $original->project->expenses()->create([
                'reverses_expense_id' => $original->id,
                'expense_date' => now()->toDateString(),
                'amount' => -1 * (float) $original->amount,
                'reference_number' => 'REV-'.($original->reference_number ?? $original->id),
                'remarks' => $validated['reason'],
                'created_by' => Auth::id(),
            ]);
        });

        app(AuditLogger::class)->record(
            $request,
            'reversed',
            'project_expenses',
            $expense->id,
            'Reversed expense of ₱'.number_format((float) $expense->amount, 2)." for {$expense->project->title}: {$validated['reason']}",
            $expense->toArray(),
            $reversal->toArray(),
        );

        return back()->with('status', 'Expense reversed. Both entries stay in the history.');
    }

    private function authorizeFinanceAccess(): void
    {
        abort_unless(Auth::user()->hasPermission('finance.view'), 403);
    }
}
