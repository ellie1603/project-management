<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\ProjectProgress;

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

    /**
     * Reset every stage and replay the remaining progress updates in order, so
     * deleting an update also undoes the stage changes it made. Each update's
     * stored percentage is recalculated, since later updates built on the removed one.
     */
    public function rebuildFromHistory(Project $project): int
    {
        $project->phases()->update(['status' => 'Not Started', 'started_at' => null, 'completed_at' => null]);

        $phaseCount = max(1, $project->phases()->count());

        $project->progress()
            ->with('phase')
            ->whereNotNull('phase_id')
            ->orderBy('progress_date')
            ->orderBy('id')
            ->get()
            ->each(function (ProjectProgress $entry) use ($project, $phaseCount): void {
                if ($entry->phase === null) {
                    return;
                }

                // Older rows predate phase_status; a finished stage is the only way completion reached its threshold.
                $status = $entry->phase_status
                    ?? ($entry->progress_percentage >= (int) round($entry->phase->sequence / $phaseCount * 100) ? 'Completed' : 'In Progress');

                $percentage = $this->applyUpdate($project, $entry->phase->fresh(), $status, $entry->progress_date->toDateString());

                $entry->fill(['progress_percentage' => $percentage, 'phase_status' => $status]);

                if ($entry->isDirty()) {
                    $entry->save();
                }
            });

        $project->unsetRelation('phases');

        return $project->completionPercentage();
    }
}
