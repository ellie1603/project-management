<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectProgress extends Model
{
    use HasFactory;

    protected $table = 'project_progress';

    protected $fillable = [
        'project_id',
        'phase_id',
        'phase_status',
        'user_id',
        'progress_date',
        'progress_percentage',
        'accomplishments',
        'activities_completed',
        'activities_remaining',
        'issues',
        'remarks',
        'ai_assisted',
        'site_notes',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function phase(): BelongsTo
    {
        return $this->belongsTo(ProjectPhase::class, 'phase_id');
    }

    public function projectDocuments(): HasMany
    {
        return $this->hasMany(ProjectDocument::class, 'progress_id');
    }

    public function project_documents(): HasMany
    {
        return $this->projectDocuments();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'progress_date' => 'date',
            'progress_percentage' => 'integer',
            'ai_assisted' => 'boolean',
        ];
    }
}
