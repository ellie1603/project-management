<?php

namespace App\Models\Scopes;

use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Auth;

/**
 * Row-level security for project data. MySQL has no native row policies, so
 * the rule lives here and is attached to every project-owned model: Project
 * Personnel only ever read rows belonging to projects they are assigned to.
 */
class ProjectAccessScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        // No signed-in user means the scheduler, a queue worker, or a seeder,
        // which must see every project. Every web route that reads project
        // data sits behind the auth middleware.
        if ($user === null || $user->hasPermission('projects.view_all')) {
            return;
        }

        $column = $model instanceof Project
            ? $model->getQualifiedKeyName()
            : $model->qualifyColumn('project_id');

        // A plain query-builder subquery, so this scope is not applied to itself.
        $builder->whereIn($column, fn (QueryBuilder $query) => $query
            ->select('project_id')
            ->from('project_assignments')
            ->where('user_id', $user->getAuthIdentifier()));
    }
}
