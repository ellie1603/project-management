<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectAssignment extends Model
{
    use HasFactory;

    /**
     * Position types available to project_personnel assignments.
     *
     * @var array<int, string>
     */
    public const POSITION_TYPES = [
        'OIC',
        'Staff',
        'Branch Manager',
        'Foreman',
        'Contractor',
    ];

    protected $fillable = [
        'project_id',
        'user_id',
        'position_type',
        'responsibility',
        'assignment_date',
        'remarks',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
