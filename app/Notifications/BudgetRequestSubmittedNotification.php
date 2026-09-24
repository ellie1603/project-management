<?php

namespace App\Notifications;

use App\Models\BudgetRequest;
use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BudgetRequestSubmittedNotification extends Notification
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
            'title' => 'Budget request submitted',
            'message' => "A budget request of ₱".number_format((float) $this->budgetRequest->amount, 2)." was submitted for {$this->project->title}.",
            'project_id' => $this->project->id,
            'project_code' => $this->project->project_code,
        ];
    }
}
