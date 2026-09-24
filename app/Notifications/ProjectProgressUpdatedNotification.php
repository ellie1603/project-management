<?php

namespace App\Notifications;

use App\Models\Project;
use App\Models\ProjectProgress;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProjectProgressUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(public Project $project, public ProjectProgress $progress) {}

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
            'title' => 'Project progress updated',
            'message' => "{$this->project->title} progress updated to {$this->progress->progress_percentage}%.",
            'project_id' => $this->project->id,
            'project_code' => $this->project->project_code,
        ];
    }
}
