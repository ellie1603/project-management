<?php

namespace App\Policies;

use App\Models\ProjectDocument;
use App\Models\User;

class ProjectDocumentPolicy
{
    /**
     * The cross-project document register needs "Browse the Documents page".
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('documents.library');
    }

    public function view(User $user, ProjectDocument $document): bool
    {
        if ($user->hasPermission('projects.view_all')) {
            return true;
        }

        return $document->project?->hasPersonnel($user) ?? false;
    }
}
