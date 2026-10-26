<?php

namespace App\View\Components;

use App\Http\Requests\StoreExpenseBatchRequest;
use App\Models\Project;
use App\Models\ProjectExpense;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Finance's "Add Expenses" dialog: several expenses at once, each a project,
 * total amount, date, and optional reference. Shown only to users who may
 * record expenses; after saving it returns to the page it was opened from.
 */
class AddExpensesModal extends Component
{
    public function __construct(
        public ?Project $project = null,
        public bool $open = false,
    ) {}

    public function shouldRender(): bool
    {
        return Auth::user()?->can('create', ProjectExpense::class) ?? false;
    }

    public function render(): View
    {
        $projects = Project::query()
            ->withBudgetTotals()
            ->where('status', '!=', 'Cancelled')
            ->orderBy('project_code')
            ->get()
            ->map(fn (Project $project): array => [
                'id' => $project->id,
                'code' => $project->project_code,
                'title' => $project->title,
                'remaining' => (float) $project->budgetSummary()['remaining_budget'],
            ]);

        $defaultProjectId = $this->project?->id ?? request()->integer('project');

        return view('components.add-expenses-modal', [
            'projects' => $projects,
            'defaultProjectId' => $projects->firstWhere('id', $defaultProjectId)['id'] ?? null,
            'maxRows' => StoreExpenseBatchRequest::MAX_ROWS,
        ]);
    }
}
