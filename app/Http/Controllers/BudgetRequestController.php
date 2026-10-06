<?php

namespace App\Http\Controllers;

use App\Models\BudgetRequest;
use App\Models\Project;
use App\Notifications\BudgetRequestApprovedNotification;
use App\Notifications\BudgetRequestRejectedNotification;
use App\Notifications\BudgetRequestSubmittedNotification;
use App\Services\AuditLogger;
use App\Services\BudgetService;
use App\Services\ProjectNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BudgetRequestController extends Controller
{
    public function __construct(private readonly BudgetService $budgetService) {}

    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('createBudgetRequest', $project);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'purpose' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'document' => ['nullable', 'file', 'max:20480', 'mimes:'.ProjectController::DOCUMENT_FILE_TYPES],
        ]);

        $documentPath = null;
        if ($request->hasFile('document')) {
            $documentPath = $request->file('document')->store("projects/{$project->id}/budget-requests", 'local');
        }

        $budgetRequest = $project->budgetRequests()->create([
            'requested_by' => Auth::id(),
            'amount' => $validated['amount'],
            'purpose' => $validated['purpose'],
            'description' => $validated['description'] ?? null,
            'document_path' => $documentPath,
            'status' => 'Pending',
        ]);

        app(AuditLogger::class)->record(
            $request, 'created', 'budget_requests', $budgetRequest->id,
            "Requested ₱".number_format((float) $budgetRequest->amount, 2)." for {$project->title}.",
            null, $budgetRequest->toArray(),
        );

        $notifier = app(ProjectNotifier::class);
        $notifier->notify($notifier->financeTeam(), new BudgetRequestSubmittedNotification($project, $budgetRequest), Auth::user());

        return redirect()->route('projects.show', $project)->with('status', 'Budget request submitted successfully.');
    }

    public function approve(Request $request, Project $project, BudgetRequest $budgetRequest): RedirectResponse
    {
        $this->authorize('manageFinance', $project);
        abort_unless($budgetRequest->project_id === $project->id, 404);
        abort_unless($budgetRequest->status === 'Pending', 422, 'This budget request has already been reviewed.');

        $validated = $request->validate(['remarks' => ['nullable', 'string']]);
        $oldValues = $budgetRequest->toArray();

        DB::transaction(function () use ($project, $budgetRequest, $validated): void {
            $expense = $project->expenses()->create([
                'expense_date' => now()->toDateString(),
                'category' => 'Budget Request',
                'description' => $budgetRequest->purpose.($budgetRequest->description ? ' — '.$budgetRequest->description : ''),
                'amount' => $budgetRequest->amount,
                'reference_number' => 'BR-'.str_pad((string) $budgetRequest->id, 4, '0', STR_PAD_LEFT),
                'document_path' => $budgetRequest->document_path,
                'created_by' => Auth::id(),
            ]);

            $budgetRequest->update([
                'status' => 'Approved',
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
                'remarks' => $validated['remarks'] ?? null,
                'expense_id' => $expense->id,
            ]);
        });

        app(AuditLogger::class)->record(
            $request, 'approved', 'budget_requests', $budgetRequest->id,
            "Approved budget request of ₱".number_format((float) $budgetRequest->amount, 2)." for {$project->title}.",
            $oldValues, $budgetRequest->fresh()->toArray(),
        );

        $notifier = app(ProjectNotifier::class);
        $notifier->notify(collect([$budgetRequest->requester]), new BudgetRequestApprovedNotification($project, $budgetRequest), Auth::user());
        $this->budgetService->checkThresholds($project->fresh());

        return back()->with('status', 'Budget request approved.');
    }

    public function reject(Request $request, Project $project, BudgetRequest $budgetRequest): RedirectResponse
    {
        $this->authorize('manageFinance', $project);
        abort_unless($budgetRequest->project_id === $project->id, 404);
        abort_unless($budgetRequest->status === 'Pending', 422, 'This budget request has already been reviewed.');

        $validated = $request->validate(['remarks' => ['required', 'string']]);
        $oldValues = $budgetRequest->toArray();

        $budgetRequest->update([
            'status' => 'Rejected',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'remarks' => $validated['remarks'],
        ]);

        app(AuditLogger::class)->record(
            $request, 'rejected', 'budget_requests', $budgetRequest->id,
            "Rejected budget request of ₱".number_format((float) $budgetRequest->amount, 2)." for {$project->title}.",
            $oldValues, $budgetRequest->fresh()->toArray(),
        );

        $notifier = app(ProjectNotifier::class);
        $notifier->notify(collect([$budgetRequest->requester]), new BudgetRequestRejectedNotification($project, $budgetRequest), Auth::user());

        return back()->with('status', 'Budget request rejected.');
    }

    /**
     * Finance-wide queue of budget requests across every project, filterable
     * by status (defaults to Pending, since that's the actionable queue).
     */
    public function index(Request $request): View
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->isFinance(), 403);

        $status = $request->string('status', 'Pending')->toString();

        $budgetRequests = BudgetRequest::query()
            ->with(['project:id,project_code,title', 'requester:id,name', 'reviewer:id,name'])
            ->when(in_array($status, BudgetRequest::STATUSES, true), fn ($q) => $q->where('status', $status))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(function ($nested) use ($search) {
                    $nested->where('purpose', 'like', "%{$search}%")
                        ->orWhereHas('project', fn ($pq) => $pq->where('title', 'like', "%{$search}%")
                            ->orWhere('project_code', 'like', "%{$search}%"));
                });
            })
            ->latest()->paginate(15)->withQueryString();

        return $this->respond($request, 'budget.requests', 'budget.partials.requests-results', compact('budgetRequests', 'status'));
    }
}
