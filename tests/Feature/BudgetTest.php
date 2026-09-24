<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_finance_can_record_expense_without_overwriting_approved_budget(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $finance = User::factory()->create(['role' => 'finance_accounting']);
        $project = Project::factory()->create([
            'project_code' => 'BMPC-PRJ-'.date('Y').'-0001',
            'approved_budget' => 100000,
            'created_by' => $admin->id,
        ]);

        $this->actingAs($finance)
            ->post('/projects/'.$project->id.'/expenses', [
                'expense_date' => now()->toDateString(),
                'category' => 'Materials',
                'description' => 'Concrete supplies',
                'amount' => 70000,
                'reference_number' => 'EXP-001',
                'payee' => 'BMPC Supplies',
                'remarks' => 'First expense',
            ])
            ->assertRedirect('/projects/'.$project->id);

        $project->refresh();

        $this->assertSame('100000.00', $project->approved_budget);
        $this->assertSame('70000.00', $project->budgetSummary()['total_expenses']);
        $this->assertSame('30000.00', $project->budgetSummary()['remaining_budget']);
        $this->assertSame('Within Budget', $project->budgetSummary()['budget_status']);
    }

    public function test_approved_budget_request_reduces_remaining_budget_and_counts_as_utilized(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $finance = User::factory()->create(['role' => 'finance_accounting']);
        $personnel = User::factory()->create(['role' => 'project_personnel', 'position_type' => 'Foreman']);
        $project = Project::factory()->create([
            'project_code' => 'BMPC-PRJ-'.date('Y').'-0006',
            'approved_budget' => 100000,
            'created_by' => $admin->id,
        ]);
        $project->assignPersonnel($personnel, 'Foreman', 'Site lead');

        $this->actingAs($personnel)
            ->post('/projects/'.$project->id.'/budget-requests', [
                'amount' => 20000,
                'purpose' => 'Construction expense',
            ])
            ->assertRedirect();

        $budgetRequest = $project->budgetRequests()->sole();
        $this->assertSame('20000.00', $project->budgetSummary()['pending_requests']);

        $this->actingAs($finance)
            ->patch('/projects/'.$project->id.'/budget-requests/'.$budgetRequest->id.'/approve')
            ->assertRedirect();

        $project->refresh();
        $budget = $project->budgetSummary();
        $this->assertSame('100000.00', $project->approved_budget);
        $this->assertSame('0.00', $budget['pending_requests']);
        $this->assertSame('20000.00', $budget['approved_requests']);
        $this->assertSame('20000.00', $budget['total_expenses']);
        $this->assertSame('80000.00', $budget['remaining_budget']);
    }

    public function test_project_personnel_cannot_approve_their_own_budget_request(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $personnel = User::factory()->create(['role' => 'project_personnel', 'position_type' => 'Foreman']);
        $project = Project::factory()->create([
            'project_code' => 'BMPC-PRJ-'.date('Y').'-0007',
            'created_by' => $admin->id,
        ]);
        $project->assignPersonnel($personnel, 'Foreman', 'Site lead');
        $budgetRequest = $project->budgetRequests()->create([
            'requested_by' => $personnel->id,
            'amount' => 5000,
            'purpose' => 'Tools',
            'status' => 'Pending',
        ]);

        $this->actingAs($personnel)
            ->patch('/projects/'.$project->id.'/budget-requests/'.$budgetRequest->id.'/approve')
            ->assertStatus(403);
    }

    public function test_budget_status_uses_runtime_warning_threshold(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $finance = User::factory()->create(['role' => 'finance_accounting']);
        $project = Project::factory()->create([
            'project_code' => 'BMPC-PRJ-'.date('Y').'-0002',
            'approved_budget' => 100000,
            'created_by' => $admin->id,
        ]);

        $this->actingAs($finance)->post('/projects/'.$project->id.'/expenses', [
            'expense_date' => now()->toDateString(),
            'category' => 'Labor',
            'description' => 'Labor cost',
            'amount' => 85000,
        ]);

        $this->assertSame('Approaching Budget Limit', $project->fresh()->budgetSummary()['budget_status']);
    }

    public function test_project_personnel_cannot_record_financial_transactions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $personnel = User::factory()->create(['role' => 'project_personnel', 'position_type' => 'Foreman']);
        $project = Project::factory()->create([
            'project_code' => 'BMPC-PRJ-'.date('Y').'-0003',
            'created_by' => $admin->id,
        ]);
        $project->assignPersonnel($personnel, 'Foreman', 'Site monitoring');

        $this->actingAs($personnel)
            ->post('/projects/'.$project->id.'/expenses', [
                'expense_date' => now()->toDateString(),
                'category' => 'Labor',
                'description' => 'Unauthorized expense',
                'amount' => 1000,
            ])
            ->assertStatus(403);
    }
}
