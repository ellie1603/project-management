<?php

namespace App\Policies;

use App\Models\ProjectDocument;
use App\Models\User;

class ProjectDocumentPolicy
{
    /**
     * Only Admin/CEO browses the cross-project document register.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, ProjectDocument $document): bool
    {
        if ($user->isAdmin() || $user->isFinance()) {
            return true;
        }

        return $document->project?->hasPersonnel($user) ?? false;
    }
}
