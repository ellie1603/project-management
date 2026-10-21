<?php

namespace App\Policies;

use App\Models\User;

class ProjectExpensePolicy
{
    /**
     * "Record expenses" permission; by default only Finance & Accounting
     * encodes expenses and the Admin/CEO reviews them.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('expenses.record');
    }
}
