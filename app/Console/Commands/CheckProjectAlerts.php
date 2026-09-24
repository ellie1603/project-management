<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Notifications\ProjectApproachingDeadlineNotification;
use App\Notifications\ProjectDelayedNotification;
use App\Services\ProjectNotifier;
use App\Services\TimelineService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('projects:check-alerts')]
#[Description('Notify the project team about approaching deadlines and delayed projects.')]
class CheckProjectAlerts extends Command
{
    public function handle(TimelineService $timelineService, ProjectNotifier $notifier): int
    {
        $projects = Project::query()
            ->whereIn('status', ['Registered', 'Ongoing', 'On Hold'])
            ->get();

        foreach ($projects as $project) {
            $timeline = $timelineService->summarize($project);
            $recipients = $notifier->projectTeam($project);

            if ($timeline['timeline_status'] === 'Delayed') {
                $notifier->notify($recipients, new ProjectDelayedNotification($project, $timeline['delay_days']));
            } elseif ($timeline['timeline_status'] === 'Approaching Deadline') {
                $notifier->notify($recipients, new ProjectApproachingDeadlineNotification($project, $timeline['days_remaining']));
            }
        }

        $this->info("Checked {$projects->count()} active project(s) for timeline alerts.");

        return self::SUCCESS;
    }
}
