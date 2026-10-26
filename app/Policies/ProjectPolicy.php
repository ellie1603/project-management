<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\ProjectProgress;
use App\Models\User;

/**
 * Feature access comes from the role's permissions (App\Services\Permissions,
 * set on the Roles & Permissions page). Users without "See every project"
 * still only reach projects they are assigned to.
 */
class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isFinance() || $user->isProjectPersonnel();
    }

    public function view(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.view_all') || $project->hasPersonnel($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('projects.register');
    }

    public function update(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.edit');
    }

    public function assignPersonnel(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.assign');
    }

    public function uploadDocument(User $user, Project $project): bool
    {
        return $user->hasPermission('documents.upload') && $this->view($user, $project);
    }

    public function manageDesign(User $user, Project $project): bool
    {
        return $user->hasPermission('documents.design');
    }

    public function createProgress(User $user, Project $project): bool
    {
        return $user->hasPermission('progress.update') && $project->hasPersonnel($user);
    }

    /**
     * Only the author, while still assigned, may correct their own update.
     */
    public function manageProgress(User $user, Project $project, ProjectProgress $progress): bool
    {
        return $progress->project_id === $project->id
            && $progress->user_id === $user->id
            && $this->createProgress($user, $project);
    }

    /**
     * Only contractor (3rd-party) personnel request budget from the CEO.
     * In-house personnel (by administration) never do: Finance records
     * their project's expenses directly.
     */
    public function createBudgetRequest(User $user, Project $project): bool
    {
        return $user->isContractor()
            && $user->hasPermission('budget_requests.submit')
            && $project->hasPersonnel($user);
    }

    public function reviewBudgetRequest(User $user, Project $project): bool
    {
        return $user->hasPermission('budget_requests.review');
    }

    public function manageContractors(User $user, Project $project): bool
    {
        return $user->hasPermission('contractors.manage');
    }

    public function viewContractors(User $user, Project $project): bool
    {
        return $this->view($user, $project);
    }

    public function viewBudget(User $user, Project $project): bool
    {
        return $user->hasPermission('finance.view') && $this->view($user, $project);
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.delete');
    }

    public function restore(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.delete');
    }

    public function forceDelete(User $user, Project $project): bool
    {
        return $user->hasPermission('projects.delete');
    }
}
