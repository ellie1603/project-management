<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\ProjectProgress;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isFinance() || $user->isProjectPersonnel();
    }

    public function view(User $user, Project $project): bool
    {
        if ($user->isAdmin() || $user->isFinance()) {
            return true;
        }

        return $project->hasPersonnel($user);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Editing project information and assigning personnel are Admin-only;
     * personnel record their work through progress updates instead.
     */
    public function update(User $user, Project $project): bool
    {
        return $user->isAdmin();
    }

    public function uploadDocument(User $user, Project $project): bool
    {
        return $user->isAdmin() || $project->hasPersonnel($user);
    }

    public function manageDesign(User $user, Project $project): bool
    {
        return $user->isAdmin();
    }

    public function createProgress(User $user, Project $project): bool
    {
        return $project->hasPersonnel($user);
    }

    /**
     * Only the author, while still assigned, may correct their own update.
     */
    public function manageProgress(User $user, Project $project, ProjectProgress $progress): bool
    {
        return $progress->project_id === $project->id
            && $progress->user_id === $user->id
            && $project->hasPersonnel($user);
    }

    public function createBudgetRequest(User $user, Project $project): bool
    {
        return $project->hasPersonnel($user);
    }

    public function manageContractors(User $user, Project $project): bool
    {
        return $user->isAdmin();
    }

    public function viewContractors(User $user, Project $project): bool
    {
        return $user->isAdmin() || $user->isFinance() || $project->hasPersonnel($user);
    }

    public function manageFinance(User $user, Project $project): bool
    {
        return $user->isAdmin() || $user->isFinance();
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, Project $project): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, Project $project): bool
    {
        return $user->isAdmin();
    }
}
