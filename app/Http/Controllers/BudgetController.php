<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectExpense;
use App\Services\AuditLogger;
use App\Services\BudgetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class BudgetController extends Controller
{
    public function __construct(private readonly BudgetService $budgetService) {}

    public function storeExpense(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('manageFinance', $project);

        $validated = $request->validate([
            'expense_date' => ['required', 'date'],
            'category' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'payee' => ['nullable', 'string', 'max:255'],
            'document_path' => ['nullable', 'string', 'max:500'],
            'remarks' => ['nullable', 'string'],
        ]);

        $validated['created_by'] = Auth::id();
        $expense = $project->expenses()->create($validated);
        app(AuditLogger::class)->record($request, 'created', 'project_expenses', $expense->id, "Recorded expense for {$project->title}.", null, $expense->toArray());

        $this->budgetService->checkThresholds($project);

        return redirect()->route('projects.show', $project);
    }

    public function show(Project $project): View
    {
        $this->authorize('view', $project);
        abort_if(Auth::user()->isProjectPersonnel(), 403);
        $project->load(['expenses.creator', 'budgetRequests.requester', 'budgetRequests.reviewer']);

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

        $projects = Project::query()->with('category')->get();
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
            ->with(['project:id,project_code,title', 'creator:id,name'])
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where(function ($nested) use ($search): void {
                    $nested->where('description', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%")
                        ->orWhere('payee', 'like', "%{$search}%")
                        ->orWhereHas('project', fn ($projectQuery) => $projectQuery->where('title', 'like', "%{$search}%")
                            ->orWhere('project_code', 'like', "%{$search}%"));
                });
            })
            ->latest('expense_date')
            ->paginate(15)
            ->withQueryString();

        return $this->respond($request, 'budget.expenses', 'budget.partials.expenses-results', compact('expenses'));
    }

    private function authorizeFinanceAccess(): void
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->isFinance(), 403);
    }
}
