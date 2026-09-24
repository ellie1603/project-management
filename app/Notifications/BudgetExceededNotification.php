<?php

namespace App\Notifications;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BudgetExceededNotification extends Notification
{
    use Queueable;

    public function __construct(public Project $project, public float $utilizationPercent) {}

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
            'title' => 'Budget exceeded',
            'message' => "{$this->project->title} has exceeded its available budget ({$this->utilizationPercent}% utilized).",
            'project_id' => $this->project->id,
            'project_code' => $this->project->project_code,
        ];
    }
}
