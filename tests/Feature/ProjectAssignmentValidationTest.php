<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectAssignmentValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_assign_finance_or_admin_accounts_as_project_personnel(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'position_type' => null]);
        $finance = User::factory()->create(['role' => 'finance_accounting', 'position_type' => null]);
        $project = Project::factory()->create(['created_by' => $admin->id]);

        $this->actingAs($admin)
            ->post('/projects/'.$project->id.'/assignments', [
                'user_id' => $finance->id,
                'position_type' => 'Foreman',
                'responsibility' => 'Site monitoring',
            ])
            ->assertSessionHasErrors('user_id');

        $this->assertDatabaseMissing('project_assignments', [
            'project_id' => $project->id,
            'user_id' => $finance->id,
        ]);
    }
}
