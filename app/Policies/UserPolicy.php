<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('users.manage');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        return $user->hasPermission('users.manage');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('users.manage');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        return $user->hasPermission('users.manage') && $this->mayChange($user, $model);
    }

    /**
     * Determine whether the user can toggle another user's active status.
     */
    public function toggleStatus(User $user, User $model): bool
    {
        return $user->hasPermission('users.manage') && $user->isNot($model) && $this->mayChange($user, $model);
    }

    /**
     * Self-deletion and accounts with project history are blocked in the controller.
     */
    public function delete(User $user, User $model): bool
    {
        return $user->hasPermission('users.manage') && $user->isNot($model) && $this->mayChange($user, $model);
    }

    /**
     * The System Administrator (developer) account can only be changed by a
     * System Administrator, so no one else can lock the maintainer out.
     */
    private function mayChange(User $user, User $model): bool
    {
        return ! $model->isSystemAdmin() || $user->isSystemAdmin();
    }
}
