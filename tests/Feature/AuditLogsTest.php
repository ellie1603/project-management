<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogsTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_assignment_writes_an_audit_log_and_admin_can_view_it(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $personnel = User::factory()->create(['role' => 'project_personnel', 'position_type' => 'Foreman']);
        $project = Project::factory()->create(['created_by' => $admin->id]);

        $this->actingAs($admin)
            ->post('/projects/'.$project->id.'/assignments', [
                'user_id' => $personnel->id,
                'position_type' => 'Foreman',
                'responsibility' => 'Site monitoring',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'assigned',
            'module' => 'projects',
            'record_id' => $project->id,
        ]);

        $this->actingAs($admin)->get('/audit-logs')->assertOk()->assertSee('Assigned');
    }

    public function test_non_admin_cannot_view_audit_logs(): void
    {
        $personnel = User::factory()->create(['role' => 'project_personnel', 'position_type' => 'Foreman']);

        $this->actingAs($personnel)->get('/audit-logs')->assertStatus(403);
    }
}
