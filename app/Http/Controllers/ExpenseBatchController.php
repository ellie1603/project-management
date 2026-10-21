<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseBatchRequest;
use App\Models\Project;
use App\Models\ProjectExpense;
use App\Services\AuditLogger;
use App\Services\BudgetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Finance's quick entry screen: several expenses in one list, each just a
 * project, date, total amount, and optional reference, saved together.
 */
class ExpenseBatchController extends Controller
{
    public function __construct(private readonly BudgetService $budgetService) {}

    public function create(Request $request): View
    {
        $this->authorize('create', ProjectExpense::class);

        $projects = Project::query()
            ->where('status', '!=', 'Cancelled')
            ->orderBy('project_code')
            ->get()
            ->map(fn (Project $project): array => [
                'id' => $project->id,
                'code' => $project->project_code,
                'title' => $project->title,
                'remaining' => (float) $project->budgetSummary()['remaining_budget'],
            ]);

        return view('budget.expense-batch', [
            'projects' => $projects,
            'defaultProjectId' => $projects->firstWhere('id', $request->integer('project'))['id'] ?? null,
            'maxRows' => StoreExpenseBatchRequest::MAX_ROWS,
        ]);
    }

    public function store(StoreExpenseBatchRequest $request): JsonResponse
    {
        $rows = collect($request->validated('rows'));
        $projects = Project::query()->whereIn('id', $rows->pluck('project_id'))->get()->keyBy('id');

        $expenses = DB::transaction(fn () => $rows->map(function (array $row) use ($request, $projects): ProjectExpense {
            $project = $projects[$row['project_id']];
            $expense = $project->expenses()->create([
                'expense_date' => $row['expense_date'],
                'amount' => $row['amount'],
                'reference_number' => $row['reference_number'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            app(AuditLogger::class)->record(
                $request, 'created', 'project_expenses', $expense->id,
                'Recorded expense of ₱'.number_format((float) $expense->amount, 2)." for {$project->title}.",
                null, $expense->toArray(),
            );

            return $expense;
        }));

        $projects->each(fn (Project $project) => $this->budgetService->checkThresholds($project));

        $request->session()->flash('status', sprintf(
            'Recorded %d %s totalling ₱%s.',
            $expenses->count(),
            Str::plural('expense', $expenses->count()),
            number_format((float) $expenses->sum('amount'), 2),
        ));

        return response()->json(['redirect' => route('budget.expenses')]);
    }
}
