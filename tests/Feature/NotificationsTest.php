<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigning_personnel_creates_a_database_notification_and_center_lists_it(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $personnel = User::factory()->create(['role' => 'project_personnel', 'position_type' => 'Foreman']);
        $project = Project::factory()->create([
            'project_code' => 'BMPC-PRJ-'.date('Y').'-0001',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->post('/projects/'.$project->id.'/assignments', [
                'user_id' => $personnel->id,
                'position_type' => 'Foreman',
                'responsibility' => 'Site monitoring',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $personnel->id,
            'type' => 'App\\Notifications\\ProjectAssignmentNotification',
        ]);

        $this->actingAs($personnel)
            ->get('/notifications/recent')
            ->assertOk()
            ->assertSee($project->title);
    }
}
