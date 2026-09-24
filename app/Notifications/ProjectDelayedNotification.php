<?php

namespace App\Notifications;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProjectDelayedNotification extends Notification
{
    use Queueable;

    public function __construct(public Project $project, public int $delayDays) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Project delayed',
            'message' => "{$this->project->title} is {$this->delayDays} day(s) past its target completion date.",
            'project_id' => $this->project->id,
            'project_code' => $this->project->project_code,
        ];
    }
}
