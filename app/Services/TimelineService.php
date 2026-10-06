<?php

namespace App\Services;

use App\Models\Project;
use Carbon\CarbonImmutable;

class TimelineService
{
    /**
     * @return array<string, int|string|null>
     */
    public function summarize(Project $project): array
    {
        $start = $project->planned_start_date ? CarbonImmutable::parse($project->planned_start_date) : null;
        $actualStart = $project->actual_start_date ? CarbonImmutable::parse($project->actual_start_date) : null;
        $target = $project->target_completion_date ? CarbonImmutable::parse($project->target_completion_date) : null;
        $today = CarbonImmutable::today();

        if ($start === null || $target === null) {
            return [
                'duration' => null,
                'days_elapsed' => 0,
                'days_remaining' => null,
                'delay_days' => 0,
                'timeline_status' => 'Not Started',
            ];
        }

        $duration = (int) max(0, $start->diffInDays($target));
        // Days elapsed is measured from when construction actually began, not the
        // planned date — a project that hasn't broken ground yet has 0 elapsed days.
        $daysElapsed = $actualStart !== null ? (int) max(0, $actualStart->diffInDays($today)) : 0;
        $daysRemaining = (int) max(0, $today->diffInDays($target, false));
        $closed = in_array($project->status, ['Completed', 'Cancelled'], true);
        $delayDays = $today->greaterThan($target) && ! $closed
            ? (int) $target->diffInDays($today)
            : 0;

        // A missed deadline counts as Delayed even if work never started.
        $status = match (true) {
            $project->status === 'Completed' => 'Completed',
            $project->status === 'Cancelled' => 'Cancelled',
            $today->greaterThan($target) => 'Delayed',
            $actualStart === null => 'Not Started',
            $daysRemaining <= 7 => 'Approaching Deadline',
            default => 'On Schedule',
        };

        return [
            'duration' => $duration,
            'days_elapsed' => $daysElapsed,
            'days_remaining' => $daysRemaining,
            'delay_days' => $delayDays,
            'timeline_status' => $status,
        ];
    }
}
