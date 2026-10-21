<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_a_project_after_confirming_its_code(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = Project::factory()->create(['project_code' => 'BMPC-PRJ-2026-0100']);

        $this->actingAs($admin)
            ->delete(route('projects.destroy', $project), ['confirm_code' => 'BMPC-PRJ-2026-0100'])
            ->assertRedirect(route('projects.index'))
            ->assertSessionHas('status');

        $this->assertSoftDeleted($project);
        $this->assertTrue(AuditLog::where('module', 'projects')
            ->where('action', 'deleted')
            ->where('record_id', $project->id)
            ->exists());
    }

    public function test_delete_is_refused_when_the_typed_code_does_not_match(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = Project::factory()->create(['project_code' => 'BMPC-PRJ-2026-0101']);

        $this->actingAs($admin)
            ->delete(route('projects.destroy', $project), ['confirm_code' => 'BMPC-PRJ-2026-9999'])
            ->assertSessionHasErrors('confirm_code', null, 'projectDeletion');

        $this->assertNotSoftDeleted($project);
    }

    public function test_finance_and_personnel_cannot_delete_projects(): void
    {
        $project = Project::factory()->create(['project_code' => 'BMPC-PRJ-2026-0102']);
        $finance = User::factory()->create(['role' => 'finance_accounting']);
        $personnel = User::factory()->create(['role' => 'project_personnel', 'position_type' => 'Foreman']);
        $project->assignPersonnel($personnel, 'Foreman', 'Site lead');

        foreach ([$finance, $personnel] as $user) {
            $this->actingAs($user)
                ->delete(route('projects.destroy', $project), ['confirm_code' => 'BMPC-PRJ-2026-0102'])
                ->assertForbidden();
        }

        $this->assertNotSoftDeleted($project);
    }

    public function test_only_admin_sees_the_delete_button(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $finance = User::factory()->create(['role' => 'finance_accounting']);
        $project = Project::factory()->create(['project_code' => 'BMPC-PRJ-2026-0103']);

        $this->actingAs($admin)->get(route('projects.show', $project))->assertSee('Delete Project');
        $this->actingAs($finance)->get(route('projects.show', $project))->assertDontSee('Delete Project');
    }
}
