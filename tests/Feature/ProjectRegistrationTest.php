<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_code_is_generated_server_side_and_status_is_registered(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'position_type' => null]);
        $personnel = User::factory()->create(['role' => 'project_personnel', 'position_type' => 'Foreman']);
        $category = ProjectCategory::factory()->create();
        Project::factory()->create([
            'project_code' => 'BMPC-PRJ-'.date('Y').'-0007',
            'status' => 'Ongoing',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->post('/projects', [
                'project_code' => 'CLIENT-SUPPLIED-CODE',
                'title' => 'New Approved Project',
                'category_id' => $category->id,
                'project_type' => 'Civil Works',
                'location' => 'Culasi, Antique',
                'objective' => 'Improve access roads.',
                'approved_budget' => 85000,
                'planned_start_date' => now()->toDateString(),
                'target_completion_date' => now()->addMonth()->toDateString(),
                'personnel' => [
                    ['user_id' => $personnel->id, 'position_type' => 'Foreman', 'responsibility' => 'Site supervision'],
                ],
            ])
            ->assertRedirect();

        $project = Project::query()->latest('id')->first();

        $this->assertSame('BMPC-PRJ-'.date('Y').'-0008', $project->project_code);
        $this->assertSame('Registered', $project->status);
        $this->assertDatabaseMissing('projects', ['project_code' => 'CLIENT-SUPPLIED-CODE']);
    }

    public function test_project_can_be_registered_with_only_the_essential_fields(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'position_type' => null]);
        $personnel = User::factory()->create(['role' => 'project_personnel', 'position_type' => 'Foreman']);
        $category = ProjectCategory::factory()->create();

        $this->actingAs($admin)
            ->post('/projects', [
                'title' => 'Quick Registration',
                'category_id' => $category->id,
                'location' => 'Barbaza, Antique',
                'approved_budget' => 120000,
                'planned_start_date' => now()->toDateString(),
                'target_completion_date' => now()->addMonth()->toDateString(),
                'personnel' => [['user_id' => $personnel->id]],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $project = Project::query()->where('title', 'Quick Registration')->firstOrFail();

        $this->assertDatabaseHas('project_assignments', [
            'project_id' => $project->id,
            'user_id' => $personnel->id,
            'position_type' => 'Foreman',
        ]);
    }

    public function test_project_can_be_registered_without_personnel(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'position_type' => null]);
        $category = ProjectCategory::factory()->create();

        $this->actingAs($admin)
            ->post('/projects', [
                'title' => 'Unassigned Project',
                'category_id' => $category->id,
                'location' => 'Culasi, Antique',
                'approved_budget' => 60000,
                'planned_start_date' => now()->toDateString(),
                'target_completion_date' => now()->addWeek()->toDateString(),
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('projects', ['title' => 'Unassigned Project', 'status' => 'Registered']);
    }
}
