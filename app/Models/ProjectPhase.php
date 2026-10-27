<?php

namespace App\Models;

use App\Models\Concerns\RestrictedToAssignedProjects;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectPhase extends Model
{
    use HasFactory, RestrictedToAssignedProjects;

    /**
     * The valid phase statuses (spec: project progress is phase/stage-based,
     * completion is derived from how many phases are Completed).
     *
     * @var array<int, string>
     */
    public const STATUSES = [
        'Not Started',
        'In Progress',
        'Completed',
    ];

    protected $fillable = [
        'project_id',
        'name',
        'sequence',
        'weight',
        'status',
        'progress',
        'started_at',
        'completed_at',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * How far along the stage is, 0-100. Stages finished before progress was
     * recorded per stage only have their status, so Completed counts as 100.
     */
    public function currentProgress(): int
    {
        return $this->status === 'Completed' ? 100 : (int) $this->progress;
    }

    public function progressUpdates(): HasMany
    {
        return $this->hasMany(ProjectProgress::class, 'phase_id');
    }

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'weight' => 'integer',
            'progress' => 'integer',
            'started_at' => 'date',
            'completed_at' => 'date',
        ];
    }
}
