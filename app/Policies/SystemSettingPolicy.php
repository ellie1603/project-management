<?php

namespace App\Policies;

use App\Models\User;

class SystemSettingPolicy
{
    /**
     * Determine whether the user can view and manage system settings.
     */
    public function manage(User $user): bool
    {
        return $user->isAdmin();
    }
}
