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

    public function test_only_contractor_personnel_submit_budget_requests(): void
    {
        $project = Project::factory()->create(['project_code' => 'BMPC-PRJ-'.date('Y').'-0010']);
        $contractor = User::factory()->create(['role' => 'project_personnel', 'position_type' => 'Contractor']);
        $project->assignPersonnel($contractor, 'Contractor', 'Builder');

        $this->actingAs($contractor)->get(route('projects.show', $project))->assertOk()->assertSee('data-tab="budget-request"', false);
        $this->actingAs($contractor)
            ->post(route('projects.budget-requests.store', $project), ['amount' => 50000, 'purpose' => '30% progress billing'])
            ->assertRedirect();

        // In-house (by administration) personnel have no budget requests; Finance records their expenses.
        foreach (['OIC', 'Staff', 'Branch Manager', 'Foreman'] as $position) {
            $inHouse = User::factory()->create(['role' => 'project_personnel', 'position_type' => $position]);
            $project->assignPersonnel($inHouse, $position, 'Site work');

            $this->actingAs($inHouse)->get(route('projects.show', $project))->assertOk()->assertDontSee('data-tab="budget-request"', false);
            $this->actingAs($inHouse)
                ->post(route('projects.budget-requests.store', $project), ['amount' => 5000, 'purpose' => 'Tools'])
                ->assertForbidden();
        }

        $this->assertSame(1, $project->budgetRequests()->count());
    }

    public function test_finance_cannot_approve_or_reject_a_budget_request(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $finance = User::factory()->create(['role' => 'finance_accounting']);
        $personnel = User::factory()->create(['role' => 'project_personnel', 'position_type' => 'Contractor']);
        $project = Project::factory()->create([
            'project_code' => 'BMPC-PRJ-'.date('Y').'-0005',
            'approved_budget' => 50000,
            'created_by' => $admin->id,
        ]);
        $project->assignPersonnel($personnel, 'Contractor', 'Builder');
        $budgetRequest = $project->budgetRequests()->create([
            'requested_by' => $personnel->id,
            'amount' => 10000,
            'purpose' => 'Progress billing',
            'status' => 'Pending',
        ]);

        $this->actingAs($finance)
            ->patch('/projects/'.$project->id.'/budget-requests/'.$budgetRequest->id.'/approve')
            ->assertForbidden();
        $this->actingAs($finance)
            ->patch('/projects/'.$project->id.'/budget-requests/'.$budgetRequest->id.'/reject', ['remarks' => 'No'])
            ->assertForbidden();

        $this->assertSame('Pending', $budgetRequest->fresh()->status);
        $this->assertSame(0, $project->expenses()->count());
    }

    public function test_finance_sees_budget_requests_without_approval_buttons(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $finance = User::factory()->create(['role' => 'finance_accounting']);
        $personnel = User::factory()->create(['role' => 'project_personnel', 'position_type' => 'Contractor']);
        $project = Project::factory()->create([
            'project_code' => 'BMPC-PRJ-'.date('Y').'-0006',
            'created_by' => $admin->id,
        ]);
        $project->assignPersonnel($personnel, 'Contractor', 'Builder');
        $budgetRequest = $project->budgetRequests()->create([
            'requested_by' => $personnel->id,
            'amount' => 10000,
            'purpose' => 'Mobilization',
            'status' => 'Pending',
        ]);
        $approveUrl = route('projects.budget-requests.approve', [$project, $budgetRequest]);

        $this->actingAs($finance)->get(route('budget.requests'))
            ->assertOk()
            ->assertSee('Mobilization')
            ->assertDontSee($approveUrl);
        $this->actingAs($finance)->get(route('projects.show', $project))
            ->assertOk()
            ->assertDontSee($approveUrl);

        $this->actingAs($admin)->get(route('budget.requests'))
            ->assertOk()
            ->assertSee($approveUrl);
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

        $this->actingAs($admin)
            ->patch('/projects/'.$project->id.'/budget-requests/'.$budgetRequest->id.'/approve')
            ->assertRedirect();

        $this->actingAs($admin)
            ->patch('/projects/'.$project->id.'/budget-requests/'.$budgetRequest->id.'/approve')
            ->assertStatus(422);
    }
}
