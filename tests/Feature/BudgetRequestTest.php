<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_unassigned_project_personnel_cannot_submit_a_budget_request(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $other = User::factory()->create(['role' => 'project_personnel', 'position_type' => 'Contractor']);
        $project = Project::factory()->create([
            'project_code' => 'BMPC-PRJ-'.date('Y').'-0001',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($other)
            ->post('/projects/'.$project->id.'/budget-requests', [
                'amount' => 5000,
                'purpose' => 'Tools',
            ])
            ->assertStatus(403);
    }

    public function test_admin_can_approve_a_budget_request(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $personnel = User::factory()->create(['role' => 'project_personnel', 'position_type' => 'Foreman']);
        $project = Project::factory()->create([
            'project_code' => 'BMPC-PRJ-'.date('Y').'-0002',
            'approved_budget' => 50000,
            'created_by' => $admin->id,
        ]);
        $project->assignPersonnel($personnel, 'Foreman', 'Site lead');
        $budgetRequest = $project->budgetRequests()->create([
            'requested_by' => $personnel->id,
            'amount' => 10000,
            'purpose' => 'Tools',
            'status' => 'Pending',
        ]);

        $this->actingAs($admin)
            ->patch('/projects/'.$project->id.'/budget-requests/'.$budgetRequest->id.'/approve')
            ->assertRedirect();

        $this->assertSame('Approved', $budgetRequest->fresh()->status);
    }

    public function test_finance_cannot_assign_project_personnel(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $finance = User::factory()->create(['role' => 'finance_accounting']);
        $personnel = User::factory()->create(['role' => 'project_personnel', 'position_type' => 'Foreman']);
        $project = Project::factory()->create([
            'project_code' => 'BMPC-PRJ-'.date('Y').'-0003',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($finance)
            ->post('/projects/'.$project->id.'/assignments', [
                'user_id' => $personnel->id,
                'position_type' => 'Foreman',
                'responsibility' => 'Site lead',
            ])
            ->assertStatus(403);
    }

    public function test_a_budget_request_cannot_be_reviewed_twice(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $finance = User::factory()->create(['role' => 'finance_accounting']);
        $personnel = User::factory()->create(['role' => 'project_personnel', 'position_type' => 'Foreman']);
        $project = Project::factory()->create([
            'project_code' => 'BMPC-PRJ-'.date('Y').'-0004',
            'approved_budget' => 50000,
            'created_by' => $admin->id,
        ]);
        $project->assignPersonnel($personnel, 'Foreman', 'Site lead');
        $budgetRequest = $project->budgetRequests()->create([
            'requested_by' => $personnel->id,
            'amount' => 10000,
            'purpose' => 'Tools',
            'status' => 'Pending',
        ]);

        $this->actingAs($finance)
            ->patch('/projects/'.$project->id.'/budget-requests/'.$budgetRequest->id.'/approve')
            ->assertRedirect();

        $this->actingAs($finance)
            ->patch('/projects/'.$project->id.'/budget-requests/'.$budgetRequest->id.'/approve')
            ->assertStatus(422);
    }
}
