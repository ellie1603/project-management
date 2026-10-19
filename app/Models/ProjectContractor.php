<?php

namespace App\Models;

use App\Models\Concerns\RestrictedToAssignedProjects;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectContractor extends Model
{
    use HasFactory, RestrictedToAssignedProjects;

    protected $table = 'project_contractors';

    protected $fillable = [
        'project_id',
        'contractor_id',
        'role',
        'contract_amount',
        'start_date',
        'end_date',
        'remarks',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class);
    }
}
