<?php

namespace App\Policies;

use App\Models\Contractor;
use App\Models\User;

class ContractorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isFinance() || $user->isProjectPersonnel();
    }

    public function view(User $user, Contractor $contractor): bool
    {
        return $user->isAdmin() || $user->isFinance() || $contractor->projects()->whereHas('assignments', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->exists();
    }
}
