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

    public function test_only_the_system_administrator_sees_users_and_audit_logs(): void
    {
        $sysadmin = User::factory()->systemAdmin()->create();
        $ceo = User::factory()->create(['role' => 'admin']);
        $personnel = User::factory()->create([
            'role' => 'project_personnel',
            'position_type' => 'Foreman',
        ]);

        $this->actingAs($sysadmin)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee(route('users.index'))
            ->assertSee(route('audit-logs.index'));

        foreach ([$ceo, $personnel] as $user) {
            $this->actingAs($user)
                ->get('/dashboard')
                ->assertOk()
                ->assertDontSee(route('users.index'))
                ->assertDontSee(route('audit-logs.index'));
        }

        $this->actingAs($ceo)->get(route('users.index'))->assertForbidden();
        $this->actingAs($ceo)->get(route('audit-logs.index'))->assertForbidden();
        $this->actingAs($ceo)->post(route('users.store'), [
            'name' => 'New Foreman', 'email' => 'new.foreman@bmpc.test', 'password' => 'password123',
            'role' => 'project_personnel', 'position_type' => 'Foreman',
        ])->assertForbidden();

        // The CEO keeps Settings.
        $this->actingAs($ceo)->get('/dashboard')->assertSee(route('settings.edit'));
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
