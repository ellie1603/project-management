<?php

namespace Tests\Feature;

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
            ->assertRedirect('/users');

        $this->assertDatabaseHas('users', [
            'email' => 'new.foreman@bmpc.test',
            'role' => 'project_personnel',
            'position_type' => 'Foreman',
            'is_active' => 1,
        ]);
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
