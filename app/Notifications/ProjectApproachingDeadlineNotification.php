<?php

namespace App\Notifications;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProjectApproachingDeadlineNotification extends Notification
{
    use Queueable;

    public function __construct(public Project $project, public int $daysRemaining) {}

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
            'title' => 'Deadline approaching',
            'message' => "{$this->project->title} is due in {$this->daysRemaining} day(s).",
            'project_id' => $this->project->id,
            'project_code' => $this->project->project_code,
        ];
    }
}
