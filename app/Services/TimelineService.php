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
        $delayDays = $today->greaterThan($target) && $project->status !== 'Completed'
            ? (int) $target->diffInDays($today)
            : 0;

        $status = match (true) {
            $project->status === 'Completed' => 'Completed',
            $actualStart === null => 'Not Started',
            $today->greaterThan($target) => 'Delayed',
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
