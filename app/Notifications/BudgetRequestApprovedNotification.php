<?php

namespace App\Notifications;

use App\Models\BudgetRequest;
use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BudgetRequestApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(public Project $project, public BudgetRequest $budgetRequest) {}

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
            'title' => 'Budget request approved',
            'message' => "Your budget request of ₱".number_format((float) $this->budgetRequest->amount, 2)." for {$this->project->title} was approved.",
            'project_id' => $this->project->id,
            'project_code' => $this->project->project_code,
        ];
    }
}
