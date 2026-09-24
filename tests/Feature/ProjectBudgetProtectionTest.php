<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectBudgetProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigned_project_personnel_cannot_change_the_approved_budget(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $personnel = User::factory()->create(['role' => 'project_personnel', 'position_type' => 'Foreman']);
        $project = Project::factory()->create([
            'approved_budget' => 100000,
            'created_by' => $admin->id,
        ]);
        $project->assignPersonnel($personnel, 'Foreman', 'Site monitoring');

        $this->actingAs($personnel)
            ->put('/projects/'.$project->id, [
                'title' => 'Updated title',
                'approved_budget' => 1,
            ])
            ->assertRedirect('/projects/'.$project->id);

        $this->assertSame('100000.00', $project->fresh()->approved_budget);
        $this->assertSame('Updated title', $project->fresh()->title);
    }
}
