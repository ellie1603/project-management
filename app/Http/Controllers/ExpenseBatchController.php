<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseBatchRequest;
use App\Models\Project;
use App\Models\ProjectExpense;
use App\Services\AuditLogger;
use App\Services\BudgetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Finance's quick entry: several expenses at once, each just a project,
 * date, total amount, and optional reference, saved together from the
 * Add Expenses dialog.
 */
class ExpenseBatchController extends Controller
{
    public function __construct(private readonly BudgetService $budgetService) {}

    /**
     * Adding expenses happens in a dialog (components/add-expenses-modal). The
     * old page address now opens the Expenses page with that dialog showing.
     */
    public function create(Request $request): RedirectResponse
    {
        $this->authorize('create', ProjectExpense::class);

        return redirect()->route('budget.expenses', array_filter([
            'add' => 1,
            'project' => $request->integer('project') ?: null,
        ]));
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
