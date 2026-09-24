<?php

namespace App\Notifications;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProjectAssignmentNotification extends Notification
{
    use Queueable;

    public function __construct(public Project $project) {}

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
            'title' => 'Project assignment',
            'message' => "You have been assigned to {$this->project->title}.",
            'project_id' => $this->project->id,
            'project_code' => $this->project->project_code,
        ];
    }
}
