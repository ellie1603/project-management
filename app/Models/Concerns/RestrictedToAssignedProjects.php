<?php

namespace App\Models\Concerns;

use App\Models\Scopes\ProjectAccessScope;

/**
 * Applies ProjectAccessScope to every query on the model.
 */
trait RestrictedToAssignedProjects
{
    public static function bootRestrictedToAssignedProjects(): void
    {
        static::addGlobalScope(new ProjectAccessScope);
    }

    /**
     * Route-model binding looks the record up without the scope so that the
     * policy can answer 403 (spec 30) instead of the scope answering 404.
     * Everything loaded after that point is scoped again.
     */
    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        return parent::resolveRouteBindingQuery($query, $value, $field)
            ->withoutGlobalScope(ProjectAccessScope::class);
    }
}
