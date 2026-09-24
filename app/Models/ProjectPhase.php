<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectPhase extends Model
{
    use HasFactory;

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
        'status',
        'started_at',
        'completed_at',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function progressUpdates(): HasMany
    {
        return $this->hasMany(ProjectProgress::class, 'phase_id');
    }

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'started_at' => 'date',
            'completed_at' => 'date',
        ];
    }
}
