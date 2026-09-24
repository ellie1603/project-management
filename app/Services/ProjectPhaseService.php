<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectPhase;

class ProjectPhaseService
{
    /**
     * Apply a personnel-submitted phase status update and return the
     * project's fresh completion percentage.
     *
     * Marking a phase Completed also completes every earlier phase (by
     * sequence) — a project can't sensibly be "done" with Implementation
     * while Planning is still Not Started, and this keeps the derived
     * completion percentage monotonic as personnel work through phases in
     * order. Marking a phase In Progress only touches that one phase.
     */
    public function applyUpdate(Project $project, ProjectPhase $phase, string $status, ?string $date = null): int
    {
        $date ??= now()->toDateString();

        if ($status === 'Completed') {
            $project->phases()
                ->where('sequence', '<=', $phase->sequence)
                ->where('status', '!=', 'Completed')
                ->get()
                ->each(function (ProjectPhase $earlierPhase) use ($date): void {
                    $earlierPhase->update([
                        'status' => 'Completed',
                        'started_at' => $earlierPhase->started_at ?? $date,
                        'completed_at' => $date,
                    ]);
                });
        } else {
            $phase->update([
                'status' => $status,
                'started_at' => $phase->started_at ?? $date,
            ]);
        }

        $project->unsetRelation('phases');

        return $project->completionPercentage();
    }
}
