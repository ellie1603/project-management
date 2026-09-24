<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetRequest extends Model
{
    use HasFactory;

    public const STATUSES = ['Pending', 'Approved', 'Rejected'];

    protected $fillable = [
        'project_id', 'requested_by', 'amount', 'purpose', 'description',
        'document_path', 'status', 'reviewed_by', 'reviewed_at', 'remarks', 'expense_id',
    ];

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function requester(): BelongsTo { return $this->belongsTo(User::class, 'requested_by'); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
    public function expense(): BelongsTo { return $this->belongsTo(ProjectExpense::class, 'expense_id'); }

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'reviewed_at' => 'datetime'];
    }
}
