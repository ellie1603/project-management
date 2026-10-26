<?php

namespace App\Models;

use App\Models\Concerns\RestrictedToAssignedProjects;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A recorded project expense. Expenses are never edited or deleted; a mistake
 * is corrected by a reversal (a negative entry linked through
 * reverses_expense_id), so the history always shows what happened.
 */
class ProjectExpense extends Model
{
    use HasFactory, RestrictedToAssignedProjects;

    protected $fillable = [
        'project_id',
        'reverses_expense_id',
        'expense_date',
        'category',
        'description',
        'amount',
        'reference_number',
        'payee',
        'document_path',
        'remarks',
        'created_by',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    /** The expense this entry cancels, when it is a reversal. */
    public function reverses(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reverses_expense_id');
    }

    /** The entry that cancelled this expense, if any. */
    public function reversal(): HasOne
    {
        return $this->hasOne(self::class, 'reverses_expense_id');
    }

    public function isReversal(): bool
    {
        return $this->reverses_expense_id !== null;
    }

    public function isReversed(): bool
    {
        return $this->relationLoaded('reversal') ? $this->reversal !== null : $this->reversal()->exists();
    }

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }
}
