<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\ProjectProgress;

class ProjectPhaseService
{
    /**
     * Apply a personnel-submitted stage update (how far along the stage is,
     * 0-100) and return the project's fresh completion percentage.
     *
     * Finishing a stage (100%) also finishes every earlier stage (by
     * sequence): a project can't sensibly be done with Implementation while
     * Planning is still open. A stage never moves backwards here; mistakes are
     * corrected by deleting the update, which replays the history.
     */
    public function applyUpdate(Project $project, ProjectPhase $phase, int $stageProgress, ?string $date = null): int
    {
        $date ??= now()->toDateString();
        $stageProgress = max($phase->currentProgress(), min(100, max(0, $stageProgress)));

        if ($stageProgress >= 100) {
            $project->phases()
                ->where('sequence', '<=', $phase->sequence)
                ->where('status', '!=', 'Completed')
                ->get()
                ->each(function (ProjectPhase $earlierPhase) use ($date): void {
                    $earlierPhase->update([
                        'status' => 'Completed',
                        'progress' => 100,
                        'started_at' => $earlierPhase->started_at ?? $date,
                        'completed_at' => $date,
                    ]);
                });
        } else {
            $phase->update([
                'status' => 'In Progress',
                'progress' => $stageProgress,
                'started_at' => $phase->started_at ?? $date,
            ]);
        }

        $project->unsetRelation('phases');

        return $project->completionPercentage();
    }

    /**
     * Reset every stage and replay the remaining progress updates in order, so
     * deleting an update also undoes the stage changes it made. Each update's
     * stored percentages are recalculated, since later updates built on the removed one.
     */
    public function rebuildFromHistory(Project $project): int
    {
        $project->phases()->update(['status' => 'Not Started', 'progress' => 0, 'started_at' => null, 'completed_at' => null]);

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

                $phase = $entry->phase->fresh();
                $stageProgress = $entry->stage_progress ?? $this->legacyStageProgress($entry, $phase, $phaseCount);
                $percentage = $this->applyUpdate($project, $phase, $stageProgress, $entry->progress_date->toDateString());
                $phase->refresh();

                $entry->fill([
                    'progress_percentage' => $percentage,
                    'stage_progress' => $phase->currentProgress(),
                    'phase_status' => $phase->status,
                ]);

                if ($entry->isDirty()) {
                    $entry->save();
                }
            });

        $project->unsetRelation('phases');

        return $project->completionPercentage();
    }

    /**
     * Updates saved before stages had their own percentage only recorded
     * "still working" or "finished". Older rows predate even that status; a
     * finished stage is the only way completion reached its threshold.
     */
    private function legacyStageProgress(ProjectProgress $entry, ProjectPhase $phase, int $phaseCount): int
    {
        $status = $entry->phase_status
            ?? ($entry->progress_percentage >= (int) round($phase->sequence / $phaseCount * 100) ? 'Completed' : 'In Progress');

        return $status === 'Completed' ? 100 : $phase->currentProgress();
    }
}
