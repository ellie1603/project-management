<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsersManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_user_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post('/users', [
                'name' => 'New Foreman',
                'email' => 'new.foreman@bmpc.test',
                'password' => 'password123',
                'role' => 'project_personnel',
                'position_type' => 'Foreman',
            ])
            ->assertRedirect('/users')
            ->assertSessionHas('created_user.email', 'new.foreman@bmpc.test');

        $this->assertDatabaseHas('users', [
            'email' => 'new.foreman@bmpc.test',
            'role' => 'project_personnel',
            'position_type' => 'Foreman',
            'is_active' => 1,
        ]);

        $this->actingAs($admin)->get('/users')->assertSee('User account created');
    }

    public function test_admin_can_delete_a_user_without_project_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'finance_accounting']);

        $this->actingAs($admin)
            ->delete('/users/'.$user->id)
            ->assertRedirect('/users')
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseHas('audit_logs', ['module' => 'users', 'action' => 'deleted', 'record_id' => $user->id]);
    }

    public function test_user_with_project_history_cannot_be_deleted(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $creator = User::factory()->create(['role' => 'admin']);
        Project::factory()->create(['created_by' => $creator->id]);

        $this->actingAs($admin)
            ->delete('/users/'.$creator->id)
            ->assertRedirect('/users')
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $creator->id]);
    }

    public function test_admin_cannot_delete_own_account_and_others_cannot_delete(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $finance = User::factory()->create(['role' => 'finance_accounting']);
        $target = User::factory()->create(['role' => 'project_personnel', 'position_type' => 'Staff']);

        $this->actingAs($admin)->delete('/users/'.$admin->id)->assertStatus(403);
        $this->actingAs($finance)->delete('/users/'.$target->id)->assertStatus(403);

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }

    public function test_admin_can_deactivate_and_reactivate_a_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'project_personnel', 'position_type' => 'Staff']);

        $this->actingAs($admin)
            ->patch('/users/'.$user->id.'/toggle-status')
            ->assertRedirect('/users');

        $this->assertFalse($user->fresh()->is_active);

        $this->actingAs($admin)
            ->patch('/users/'.$user->id.'/toggle-status')
            ->assertRedirect('/users');

        $this->assertTrue($user->fresh()->is_active);
    }

    public function test_deactivated_user_cannot_log_in(): void
    {
        $user = User::factory()->create([
            'role' => 'project_personnel',
            'password' => bcrypt('password123'),
            'is_active' => false,
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_non_admin_cannot_manage_users(): void
    {
        $personnel = User::factory()->create(['role' => 'project_personnel', 'position_type' => 'Foreman']);
        $finance = User::factory()->create(['role' => 'finance_accounting']);

        $this->actingAs($personnel)->get('/users')->assertStatus(403);
        $this->actingAs($finance)->get('/users')->assertStatus(403);
    }
}
