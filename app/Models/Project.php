<?php

namespace App\Models;

use App\Services\BudgetService;
use App\Services\TimelineService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The document types supported for project document uploads.
     *
     * @var array<int, string>
     */
    public const DOCUMENT_TYPES = [
        'Approved Design',
        'Project Plan',
        'Contract',
        'Quotation',
        'Accomplishment Report',
        'Progress Report',
        'Progress Photo',
        'Other',
    ];

    /**
     * The valid project lifecycle statuses.
     *
     * @var array<int, string>
     */
    public const STATUSES = [
        'Registered',
        'Ongoing',
        'On Hold',
        'Completed',
        'Cancelled',
    ];

    /**
     * The default construction stage template applied to every
     * newly-registered project. Completion percentage is derived from how
     * many of these are marked Completed — see completionPercentage().
     *
     * @var array<int, string>
     */
    public const DEFAULT_PHASES = [
        'Construction Started',
        'Foundation / Structural Work',
        'Building / Structural Development',
        'Installation & Finishing',
        'Inspection & Completion',
    ];

    protected $fillable = [
        'project_code',
        'title',
        'description',
        'category_id',
        'project_type',
        'location',
        'objective',
        'approved_budget',
        'planned_start_date',
        'target_completion_date',
        'actual_start_date',
        'actual_completion_date',
        'status',
        'remarks',
        'created_by',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProjectCategory::class, 'category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ProjectAssignment::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ProjectDocument::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(ProjectProgress::class);
    }

    public function phases(): HasMany
    {
        return $this->hasMany(ProjectPhase::class)->orderBy('sequence');
    }

    /**
     * Project completion is derived from how many phases are Completed, not
     * a manually-entered number (spec: personnel select a phase/status,
     * the system computes the percentage).
     */
    public function completionPercentage(): int
    {
        $total = $this->phases->count();

        if ($total === 0) {
            return 0;
        }

        $completed = $this->phases->where('status', 'Completed')->count();

        return (int) round($completed / $total * 100);
    }

    /**
     * The phase currently being worked on: the first one not yet Completed,
     * or the last phase if every phase is done.
     */
    public function currentPhase(): ?ProjectPhase
    {
        return $this->phases->firstWhere('status', '!=', 'Completed') ?? $this->phases->last();
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(ContractorQuotation::class);
    }

    public function contractors(): BelongsToMany
    {
        return $this->belongsToMany(Contractor::class, 'project_contractors')
            ->withPivot(['role', 'contract_amount', 'start_date', 'end_date', 'remarks']);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(ProjectExpense::class);
    }

    public function budgetRequests(): HasMany
    {
        return $this->hasMany(BudgetRequest::class);
    }

    /**
     * @return array<string, string|float>
     */
    public function budgetSummary(): array
    {
        return app(BudgetService::class)->summarize($this);
    }

    /**
     * @return array<string, int|string|null>
     */
    public function timelineSummary(): array
    {
        return app(TimelineService::class)->summarize($this);
    }

    protected function casts(): array
    {
        return [
            'approved_budget' => 'decimal:2',
        ];
    }

    public function assignedUsers()
    {
        return $this->belongsToMany(User::class, 'project_assignments');
    }

    public function hasPersonnel(User $user): bool
    {
        return $this->assignments()->where('user_id', $user->getKey())->exists();
    }

    /**
     * Restrict the query to projects the given user is allowed to browse:
     * everything for Admin/Finance, only assigned projects for Personnel.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin() || $user->isFinance()) {
            return $query;
        }

        return $query->whereHas('assignments', fn (Builder $assignmentQuery) => $assignmentQuery->where('user_id', $user->getKey()));
    }

    /**
     * Apply the shared set of search/report filters (spec 28) from a
     * request's query string. Reused by the project list and every report.
     */
    public function scopeFilter(Builder $query, Request $request): Builder
    {
        return $query
            ->when($request->filled('status'), fn (Builder $q) => $q->where('status', $request->string('status')->toString()))
            ->when($request->filled('category_id'), fn (Builder $q) => $q->where('category_id', $request->integer('category_id')))
            ->when($request->filled('project_type'), fn (Builder $q) => $q->where('project_type', $request->string('project_type')->toString()))
            ->when($request->filled('date_from'), fn (Builder $q) => $q->whereDate('planned_start_date', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn (Builder $q) => $q->whereDate('target_completion_date', '<=', $request->date('date_to')))
            ->when($request->filled('budget_min'), fn (Builder $q) => $q->where('approved_budget', '>=', $request->input('budget_min')))
            ->when($request->filled('budget_max'), fn (Builder $q) => $q->where('approved_budget', '<=', $request->input('budget_max')))
            ->when($request->filled('contractor_id'), fn (Builder $q) => $q->whereHas('contractors', fn (Builder $r) => $r->whereKey($request->integer('contractor_id'))))
            ->when($request->filled('assigned_user_id'), fn (Builder $q) => $q->whereHas('assignments', fn (Builder $r) => $r->where('user_id', $request->integer('assigned_user_id'))))
            ->when($request->filled('search'), function (Builder $q) use ($request): void {
                $search = $request->string('search')->toString();
                $q->where(function (Builder $nested) use ($search): void {
                    $nested->where('project_code', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%");
                });
            });
    }

    public function assignPersonnel(User $user, string $positionType, ?string $responsibility = null): ProjectAssignment
    {
        return $this->assignments()->firstOrCreate([
            'user_id' => $user->getKey(),
        ], [
            'position_type' => $positionType,
            'responsibility' => $responsibility,
            'assignment_date' => now()->toDateString(),
            'remarks' => 'Assigned via BMPC project registration',
        ]);
    }

    /**
     * Every audit_logs entry that touches this project directly or through
     * one of its child records (assignments, documents, progress, expenses,
     * budget requests).
     *
     * @return Collection<int, AuditLog>
     */
    public function activityLog(): Collection
    {
        $recordIdsByModule = [
            'projects' => [$this->getKey()],
            'project_assignments' => $this->assignments()->pluck('id')->all(),
            'project_documents' => $this->documents()->pluck('id')->all(),
            'project_progress' => $this->progress()->pluck('id')->all(),
            'project_expenses' => $this->expenses()->pluck('id')->all(),
            'budget_requests' => $this->budgetRequests()->pluck('id')->all(),
            'contractor_quotations' => $this->quotations()->pluck('id')->all(),
            // Contractor attachments are logged against the project's own id (see ContractorController::attachToProject).
            'project_contractors' => [$this->getKey()],
        ];

        return AuditLog::query()
            ->where(function ($query) use ($recordIdsByModule): void {
                foreach ($recordIdsByModule as $module => $ids) {
                    if ($ids === []) {
                        continue;
                    }

                    $query->orWhere(function ($moduleQuery) use ($module, $ids): void {
                        $moduleQuery->where('module', $module)->whereIn('record_id', $ids);
                    });
                }
            })
            ->with('user')
            ->latest()
            ->get();
    }
}
