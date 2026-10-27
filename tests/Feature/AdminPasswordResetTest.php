<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $juan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->systemAdmin()->create(['is_active' => true]);
        $this->juan = User::factory()->create([
            'role' => 'project_personnel', 'position_type' => 'Foreman', 'is_active' => true,
            'email' => 'juan@bmpc.test', 'password' => 'old-password-1',
        ]);
    }

    public function test_admin_resets_a_password_and_sees_a_one_time_code(): void
    {
        $response = $this->actingAs($this->admin)->post(route('users.reset-password', $this->juan));

        $response->assertRedirect()->assertSessionHas('password_reset');
        $code = session('password_reset')['code'];
        $this->assertMatchesRegularExpression('/^[A-Z2-9]{4}-[A-Z2-9]{4}$/', $code);

        $juan = $this->juan->fresh();
        $this->assertTrue($juan->must_change_password);
        $this->assertTrue($juan->password_reset_expires_at->isFuture());
        $this->assertFalse(Hash::check('old-password-1', $juan->password), 'the old password stops working');
        $this->assertTrue(AuditLog::where('module', 'users')->where('action', 'password_reset')->where('record_id', $juan->id)->exists());
        $this->assertFalse(AuditLog::where('description', 'like', "%{$code}%")->exists(), 'the code is never logged');

        $this->actingAs($this->admin)->get(route('users.index'))->assertSee($code);
    }

    public function test_the_user_signs_in_with_the_code_and_must_set_a_new_password(): void
    {
        $code = $this->resetCode();

        // Typed in lowercase without the dash still works.
        $this->post('/login', ['email' => 'juan@bmpc.test', 'password' => strtolower(str_replace('-', '', $code))])
            ->assertRedirect();
        $this->assertAuthenticatedAs($this->juan->fresh());

        // Every page leads to "set your new password" until it's done.
        $this->get('/dashboard')->assertRedirect(route('password.setup'));
        $this->get(route('password.setup'))->assertOk()->assertSee('Set your new password');

        $this->put(route('password.setup.update'), ['password' => 'brand-new-pass-9', 'password_confirmation' => 'brand-new-pass-9'])
            ->assertRedirect(route('dashboard'));

        $juan = $this->juan->fresh();
        $this->assertFalse($juan->must_change_password);
        $this->assertNull($juan->password_reset_expires_at);
        $this->assertTrue(Hash::check('brand-new-pass-9', $juan->password));
        $this->get('/dashboard')->assertOk();

        // The code no longer works.
        $this->post('/logout');
        $this->post('/login', ['email' => 'juan@bmpc.test', 'password' => $code])->assertSessionHasErrors('email');
    }

    public function test_an_expired_code_is_refused(): void
    {
        $code = $this->resetCode();
        $this->juan->forceFill(['password_reset_expires_at' => now()->subMinute()])->save();

        $this->post('/login', ['email' => 'juan@bmpc.test', 'password' => $code])
            ->assertSessionHasErrors(['email' => 'This reset code has expired. Ask your administrator to reset your password again.']);
        $this->assertGuest();
    }

    public function test_only_user_managers_can_reset_and_the_system_administrator_is_protected(): void
    {
        $finance = User::factory()->create(['role' => 'finance_accounting', 'is_active' => true]);
        $this->actingAs($finance)->post(route('users.reset-password', $this->juan))->assertForbidden();

        // Only the System Administrator resets passwords; the CEO cannot, least of all the System Administrator's.
        $ceo = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->actingAs($ceo)->post(route('users.reset-password', $this->juan))->assertForbidden();
        $this->actingAs($ceo)->post(route('users.reset-password', $this->admin))->assertForbidden();

        // Your own password is changed in My Account instead.
        $this->actingAs($this->admin)->post(route('users.reset-password', $this->admin))->assertForbidden();
    }

    public function test_every_user_has_my_account(): void
    {
        $this->actingAs($this->juan)->get('/dashboard')->assertSee('My Account')->assertSee(route('profile.edit'));
        $this->actingAs($this->juan)->get(route('profile.edit'))->assertOk()->assertSee('My Account');
    }

    private function resetCode(): string
    {
        $this->actingAs($this->admin)->post(route('users.reset-password', $this->juan));
        $code = session('password_reset')['code'];
        $this->post('/logout');

        return $code;
    }
}
