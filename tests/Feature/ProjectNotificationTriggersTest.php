<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Notifications\BudgetExceededNotification;
use App\Notifications\BudgetRequestApprovedNotification;
use App\Notifications\BudgetRequestRejectedNotification;
use App\Notifications\BudgetRequestSubmittedNotification;
use App\Notifications\ProjectCompletedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectNotificationTriggersTest extends TestCase
{
    use RefreshDatabase;

    public function test_exceeding_the_budget_notifies_admin_and_finance(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $finance = User::factory()->create(['role' => 'finance_accounting']);
        $project = Project::factory()->create([
            'project_code' => 'BMPC-PRJ-'.date('Y').'-0001',
            'approved_budget' => 10000,
            'created_by' => $admin->id,
        ]);

        $this->actingAs($finance)->post('/projects/'.$project->id.'/expenses', [
            'expense_date' => now()->toDateString(),
            'category' => 'Materials',
            'description' => 'Over-budget purchase',
            'amount' => 15000,
        ])->assertRedirect();

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $admin->id,
            'type' => BudgetExceededNotification::class,
        ]);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $finance->id,
            'type' => BudgetExceededNotification::class,
        ]);
    }

    public function test_submitting_a_budget_request_notifies_the_finance_team(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $personnel = User::factory()->create(['role' => 'project_personnel', 'position_type' => 'Foreman']);
        $project = Project::factory()->create([
            'project_code' => 'BMPC-PRJ-'.date('Y').'-0002',
            'created_by' => $admin->id,
        ]);
        $project->assignPersonnel($personnel, 'Foreman', 'Site lead');

        $this->actingAs($personnel)->post('/projects/'.$project->id.'/budget-requests', [
            'amount' => 20000,
            'purpose' => 'Scope change',
        ])->assertRedirect();

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $admin->id,
            'type' => BudgetRequestSubmittedNotification::class,
        ]);
    }

    public function test_approving_a_budget_request_notifies_the_requester_and_records_an_expense(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $finance = User::factory()->create(['role' => 'finance_accounting']);
        $personnel = User::factory()->create(['role' => 'project_personnel', 'position_type' => 'Foreman']);
        $project = Project::factory()->create([
            'project_code' => 'BMPC-PRJ-'.date('Y').'-0004',
            'approved_budget' => 100000,
            'created_by' => $admin->id,
        ]);
        $project->assignPersonnel($personnel, 'Foreman', 'Site lead');
        $budgetRequest = $project->budgetRequests()->create([
            'requested_by' => $personnel->id,
            'amount' => 15000,
            'purpose' => 'Additional fittings',
            'status' => 'Pending',
        ]);

        $this->actingAs($finance)
            ->patch('/projects/'.$project->id.'/budget-requests/'.$budgetRequest->id.'/approve')
            ->assertRedirect();

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $personnel->id,
            'type' => BudgetRequestApprovedNotification::class,
        ]);
        $this->assertDatabaseHas('project_expenses', [
            'project_id' => $project->id,
            'amount' => '15000.00',
        ]);
        $this->assertSame('Approved', $budgetRequest->fresh()->status);
    }

    public function test_rejecting_a_budget_request_requires_remarks_and_notifies_the_requester(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $finance = User::factory()->create(['role' => 'finance_accounting']);
        $personnel = User::factory()->create(['role' => 'project_personnel', 'position_type' => 'Foreman']);
        $project = Project::factory()->create([
            'project_code' => 'BMPC-PRJ-'.date('Y').'-0005',
            'created_by' => $admin->id,
        ]);
        $project->assignPersonnel($personnel, 'Foreman', 'Site lead');
        $budgetRequest = $project->budgetRequests()->create([
            'requested_by' => $personnel->id,
            'amount' => 15000,
            'purpose' => 'Additional fittings',
            'status' => 'Pending',
        ]);

        $this->actingAs($finance)
            ->patch('/projects/'.$project->id.'/budget-requests/'.$budgetRequest->id.'/reject', [])
            ->assertSessionHasErrors('remarks');

        $this->actingAs($finance)
            ->patch('/projects/'.$project->id.'/budget-requests/'.$budgetRequest->id.'/reject', [
                'remarks' => 'Exceeds remaining allocation',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $personnel->id,
            'type' => BudgetRequestRejectedNotification::class,
        ]);
        $this->assertSame('Rejected', $budgetRequest->fresh()->status);
    }

    public function test_marking_a_project_completed_notifies_the_project_team(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $personnel = User::factory()->create(['role' => 'project_personnel', 'position_type' => 'Foreman']);
        $project = Project::factory()->create([
            'project_code' => 'BMPC-PRJ-'.date('Y').'-0003',
            'status' => 'Ongoing',
            'created_by' => $admin->id,
        ]);
        $project->assignPersonnel($personnel, 'Foreman', 'Site lead');

        $this->actingAs($admin)->put('/projects/'.$project->id, [
            'title' => $project->title,
            'planned_start_date' => $project->planned_start_date,
            'target_completion_date' => $project->target_completion_date,
            'status' => 'Completed',
        ])->assertRedirect();

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $personnel->id,
            'type' => ProjectCompletedNotification::class,
        ]);
    }
}
