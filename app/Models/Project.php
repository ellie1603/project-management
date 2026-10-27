<?php

namespace App\Models;

use App\Models\Concerns\RestrictedToAssignedProjects;
use App\Services\BudgetService;
use App\Services\TimelineService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

class Project extends Model
{
    use HasFactory, RestrictedToAssignedProjects, SoftDeletes;

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
     * far along each stage is, weighted by its share — see completionPercentage().
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

    /**
     * Each default stage's share of the whole project, in the same order as
     * DEFAULT_PHASES (adds up to 100). The admin can change them per project.
     *
     * @var array<int, int>
     */
    public const DEFAULT_PHASE_WEIGHTS = [5, 25, 35, 25, 10];

    /**
     * How many percentage points actual completion may trail the planned
     * completion for today before the project is flagged as behind plan.
     */
    public const BEHIND_PLAN_POINTS = 10;

    /**
     * Expected type of each query-string filter accepted by scopeFilter().
     *
     * @var array<string, array<int, string>>
     */
    private const FILTER_RULES = [
        'status' => ['nullable', 'string', 'max:50'],
        'category_id' => ['nullable', 'integer'],
        'project_type' => ['nullable', 'string', 'max:255'],
        'date_from' => ['nullable', 'date'],
        'date_to' => ['nullable', 'date'],
        'budget_min' => ['nullable', 'numeric'],
        'budget_max' => ['nullable', 'numeric'],
        'contractor_id' => ['nullable', 'integer'],
        'assigned_user_id' => ['nullable', 'integer'],
        'search' => ['nullable', 'string', 'max:255'],
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
     * Each stage's share of the project, keyed by stage id. Uses the stored
     * weights when every stage has one, otherwise equal shares.
     *
     * @return array<int, int>
     */
    public function stageWeights(): array
    {
        $phases = $this->phases;

        if ($phases->isNotEmpty() && $phases->every(fn (ProjectPhase $phase): bool => $phase->weight !== null) && $phases->sum('weight') > 0) {
            return $phases->pluck('weight', 'id')->map(fn ($weight): int => (int) $weight)->all();
        }

        return $phases->mapWithKeys(fn (ProjectPhase $phase): array => [$phase->id => 1])->all();
    }

    /**
     * Project completion is computed, never typed in: each stage's progress
     * (0-100%) times its share of the project. It only reaches 100% when
     * every stage is finished, and shows at least 1% once any work is recorded.
     */
    public function completionPercentage(): int
    {
        if ($this->phases->isEmpty()) {
            return 0;
        }

        $weights = $this->stageWeights();
        $weighted = $this->phases->sum(fn (ProjectPhase $phase): float => $weights[$phase->id] * $phase->currentProgress());
        $percentage = $weighted / array_sum($weights);

        if ($this->phases->every(fn (ProjectPhase $phase): bool => $phase->currentProgress() >= 100)) {
            return 100;
        }

        return $percentage > 0 ? (int) min(99, max(1, round($percentage))) : 0;
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

    /**
     * Percentage points actual completion is ahead of (positive) or behind
     * (negative) the planned completion for today. Null when the project has
     * no planned dates or is already closed.
     *
     * @param  array<string, mixed>|null  $timeline  a timelineSummary() already computed by the caller
     */
    public function progressVariance(?array $timeline = null): ?int
    {
        $planned = ($timeline ?? $this->timelineSummary())['planned_percentage'] ?? null;

        if ($planned === null || in_array($this->status, ['Completed', 'Cancelled'], true)) {
            return null;
        }

        return $this->completionPercentage() - (int) $planned;
    }

    public function isBehindPlan(?array $timeline = null): bool
    {
        $variance = $this->progressVariance($timeline);

        return $variance !== null && $variance <= -self::BEHIND_PLAN_POINTS;
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

    /** @var array<int, bool> per-user answers, since a page asks several times */
    private array $personnelChecks = [];

    public function hasPersonnel(User $user): bool
    {
        $userId = (int) $user->getKey();

        if ($this->relationLoaded('assignments')) {
            return $this->assignments->contains('user_id', $userId);
        }

        return $this->personnelChecks[$userId] ??= $this->assignments()->where('user_id', $userId)->exists();
    }

    /**
     * Preloads the budget totals budgetSummary() needs, in the same query as
     * the projects, for pages that summarize many projects at once.
     */
    public function scopeWithBudgetTotals(Builder $query): Builder
    {
        return $query
            ->withSum('expenses as expenses_total', 'amount')
            ->withSum(['budgetRequests as pending_requests_total' => fn (Builder $requests) => $requests->where('status', 'Pending')], 'amount')
            ->withSum(['budgetRequests as approved_requests_total' => fn (Builder $requests) => $requests->where('status', 'Approved')], 'amount');
    }

    /**
     * The most recent progress update, for eager loading on list pages.
     */
    public function latestProgress(): HasOne
    {
        return $this->hasOne(ProjectProgress::class)->ofMany(['progress_date' => 'max', 'id' => 'max']);
    }

    /**
     * Restrict the query to projects the given user is allowed to browse:
     * everything with the "See every project" permission, otherwise only
     * assigned projects.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasPermission('projects.view_all')) {
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
        // Filters come straight from the URL, so a value of the wrong type is ignored rather than passed to the query.
        $filters = array_filter(
            Validator::make($request->only(array_keys(self::FILTER_RULES)), self::FILTER_RULES)->valid(),
            fn (mixed $value): bool => filled($value),
        );

        return $query
            ->when(isset($filters['status']), fn (Builder $q) => $q->where('status', $filters['status']))
            ->when(isset($filters['category_id']), fn (Builder $q) => $q->where('category_id', (int) $filters['category_id']))
            ->when(isset($filters['project_type']), fn (Builder $q) => $q->where('project_type', $filters['project_type']))
            ->when(isset($filters['date_from']), fn (Builder $q) => $q->whereDate('planned_start_date', '>=', Carbon::parse($filters['date_from'])))
            ->when(isset($filters['date_to']), fn (Builder $q) => $q->whereDate('target_completion_date', '<=', Carbon::parse($filters['date_to'])))
            ->when(isset($filters['budget_min']), fn (Builder $q) => $q->where('approved_budget', '>=', (float) $filters['budget_min']))
            ->when(isset($filters['budget_max']), fn (Builder $q) => $q->where('approved_budget', '<=', (float) $filters['budget_max']))
            ->when(isset($filters['contractor_id']), fn (Builder $q) => $q->whereHas('contractors', fn (Builder $r) => $r->whereKey((int) $filters['contractor_id'])))
            ->when(isset($filters['assigned_user_id']), fn (Builder $q) => $q->whereHas('assignments', fn (Builder $r) => $r->where('user_id', (int) $filters['assigned_user_id'])))
            ->when(isset($filters['search']), function (Builder $q) use ($filters): void {
                $search = $filters['search'];
                $q->where(function (Builder $nested) use ($search): void {
                    $nested->where('project_code', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%");
                });
            });
    }

    public function assignPersonnel(User $user, string $positionType, ?string $responsibility = null): ProjectAssignment
    {
        unset($this->personnelChecks[(int) $user->getKey()]);
        $this->unsetRelation('assignments');

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
