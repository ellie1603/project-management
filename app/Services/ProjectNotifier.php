<?php

namespace App\Services;

use App\Models\Project;
use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class ProjectNotifier
{
    /**
     * Admin/CEO plus every project_personnel assigned to the project.
     */
    public function projectTeam(Project $project): Collection
    {
        $assignedPersonnel = $project->assignments()->with('user')->get()->pluck('user');

        return $this->admins()->merge($assignedPersonnel)->unique('id');
    }

    /**
     * Admin/CEO plus Finance & Accounting.
     */
    public function financeTeam(): Collection
    {
        return $this->admins()->merge(User::query()->where('role', 'finance_accounting')->get())->unique('id');
    }

    public function admins(): Collection
    {
        return User::query()->where('role', 'admin')->get();
    }

    public function notify(Collection $recipients, Notification $notification, ?User $exclude = null): void
    {
        $recipients
            ->when($exclude, fn (Collection $collection) => $collection->reject(fn (User $user) => $user->is($exclude)))
            ->each(fn (User $user) => $user->notify($notification));
    }
}
