<?php

namespace App\Policies;

use App\Models\ProjectAssignment;
use App\Models\User;

class ProjectAssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isFinance() || $user->isProjectPersonnel();
    }

    public function view(User $user, ProjectAssignment $projectAssignment): bool
    {
        return $user->isAdmin() || $user->isFinance() || $projectAssignment->project->hasPersonnel($user);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, ProjectAssignment $projectAssignment): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, ProjectAssignment $projectAssignment): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, ProjectAssignment $projectAssignment): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, ProjectAssignment $projectAssignment): bool
    {
        return $user->isAdmin();
    }
}
