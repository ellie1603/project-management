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

    public function test_opening_a_single_notification_marks_only_it_read(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $personnel = User::factory()->create(['role' => 'project_personnel', 'position_type' => 'Foreman']);
        $first = Project::factory()->create(['created_by' => $admin->id]);
        $second = Project::factory()->create(['created_by' => $admin->id]);

        foreach ([$first, $second] as $project) {
            $this->actingAs($admin)->post('/projects/'.$project->id.'/assignments', [
                'user_id' => $personnel->id,
                'position_type' => 'Foreman',
                'responsibility' => 'Site monitoring',
            ]);
        }

        $notification = $personnel->notifications()->get()->firstWhere('data.project_id', $first->id);

        $this->actingAs($personnel)
            ->get(route('notifications.open', $notification->id))
            ->assertRedirect(route('projects.show', $first));

        $this->assertNotNull($notification->fresh()->read_at);
        $this->assertSame(1, $personnel->fresh()->unreadNotifications()->count());
    }

    public function test_users_cannot_open_someone_elses_notification(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $personnel = User::factory()->create(['role' => 'project_personnel', 'position_type' => 'Foreman']);
        $other = User::factory()->create(['role' => 'project_personnel', 'position_type' => 'Staff']);
        $project = Project::factory()->create(['created_by' => $admin->id]);

        $this->actingAs($admin)->post('/projects/'.$project->id.'/assignments', [
            'user_id' => $personnel->id,
            'position_type' => 'Foreman',
            'responsibility' => 'Site monitoring',
        ]);

        $notification = $personnel->notifications()->firstOrFail();

        $this->actingAs($other)->get(route('notifications.open', $notification->id))->assertNotFound();
        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_success_message_is_not_repeated_as_an_error(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->withSession(['status' => 'Saved it.'])
            ->get('/users')
            ->assertOk()
            ->assertSee('role="status"', false)
            ->assertDontSee('role="alert"', false);
    }
}
