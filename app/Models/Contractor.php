<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Contractor extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'contact_person',
        'contact_number',
        'email',
        'address',
        'registration_information',
        'status',
        'remarks',
    ];

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_contractors')
            ->withPivot(['role', 'contract_amount', 'start_date', 'end_date', 'remarks']);
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(ContractorQuotation::class);
    }

    /**
     * The login account this contractor profile is managed through, if any.
     * Legacy contractor records created before this link existed may have none.
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }
}
