<?php

namespace App\Notifications;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BudgetApproachingLimitNotification extends Notification
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
            'title' => 'Budget approaching limit',
            'message' => "{$this->project->title} has used {$this->utilizationPercent}% of its available budget.",
            'project_id' => $this->project->id,
            'project_code' => $this->project->project_code,
        ];
    }
}
