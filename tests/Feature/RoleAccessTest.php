<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_dashboard(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'position_type' => null,
        ]);

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk();
    }

    public function test_admin_sees_audit_navigation_but_project_personnel_does_not(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'position_type' => null,
        ]);
        $personnel = User::factory()->create([
            'role' => 'project_personnel',
            'position_type' => 'Foreman',
        ]);

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Audit Logs');

        $this->actingAs($personnel)
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee('Audit Logs');
    }

    public function test_project_personnel_cannot_access_unassigned_project(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $personnel = User::factory()->create(['role' => 'project_personnel', 'position_type' => 'Foreman']);
        $otherPersonnel = User::factory()->create(['role' => 'project_personnel', 'position_type' => 'Contractor']);

        $category = ProjectCategory::create([
            'name' => 'Infrastructure',
            'description' => 'Infrastructure works',
            'status' => 'active',
        ]);

        $project = Project::create([
            'project_code' => 'BMPC-PRJ-'.date('Y').'-0001',
            'title' => 'Road Upgrade',
            'description' => 'Road improvement project',
            'category_id' => $category->id,
            'project_type' => 'Civil Works',
            'location' => 'Cebu',
            'objective' => 'Improve roads',
            'approved_budget' => 120000,
            'planned_start_date' => now()->toDateString(),
            'target_completion_date' => now()->addMonths(3)->toDateString(),
            'status' => 'Registered',
            'remarks' => 'Approved externally',
            'created_by' => $admin->id,
        ]);

        $project->assignPersonnel($otherPersonnel, 'Foreman', 'Site lead');

        $this->actingAs($personnel)
            ->get('/projects/'.$project->id)
            ->assertStatus(403);
    }

    public function test_finance_cannot_assign_personnel(): void
    {
        $finance = User::factory()->create(['role' => 'finance_accounting']);
        $project = Project::factory()->create([
            'project_code' => 'BMPC-PRJ-'.date('Y').'-0002',
        ]);

        $this->actingAs($finance)
            ->post('/projects/'.$project->id.'/assignments', [
                'user_id' => $finance->id,
                'position_type' => 'Foreman',
                'responsibility' => 'Site monitor',
            ])
            ->assertStatus(403);
    }
}
