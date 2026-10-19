<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    public function test_requests_beyond_the_global_limit_are_rejected(): void
    {
        config(['rate_limits.global_user' => 3]);
        $user = User::factory()->create(['role' => 'admin']);

        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($user)->get('/profile')->assertOk();
        }

        $this->actingAs($user)->get('/profile')
            ->assertTooManyRequests()
            ->assertHeader('Retry-After')
            ->assertSee('Too many requests');
    }

    public function test_the_global_limit_is_counted_per_account(): void
    {
        config(['rate_limits.global_user' => 1]);
        $first = User::factory()->create(['role' => 'admin']);
        $second = User::factory()->create(['role' => 'admin']);

        $this->actingAs($first)->get('/profile')->assertOk();
        $this->actingAs($first)->get('/profile')->assertTooManyRequests();

        $this->actingAs($second)->get('/profile')->assertOk();
    }

    public function test_writes_have_a_stricter_limit_than_reads(): void
    {
        config(['rate_limits.writes' => 2]);
        $user = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)->patch('/notifications/read-all')->assertRedirect();
        $this->actingAs($user)->patch('/notifications/read-all')->assertRedirect();
        $this->actingAs($user)->patch('/notifications/read-all')->assertTooManyRequests();

        $this->actingAs($user)->get('/profile')->assertOk();
    }

    public function test_sign_in_locks_after_five_wrong_passwords_and_counts_down_first(): void
    {
        User::factory()->create(['email' => 'staff@example.com']);
        $attempt = fn () => $this->post('/login', ['email' => 'staff@example.com', 'password' => 'wrong']);

        $attempt()->assertSessionHasErrors(['email' => 'These credentials do not match our records. 4 attempts left before sign-in is locked.']);
        $attempt();
        $attempt();
        $attempt()->assertSessionHasErrors(['email' => 'These credentials do not match our records. 1 attempt left before sign-in is locked.']);

        $this->from('/login')->followingRedirects()
            ->post('/login', ['email' => 'staff@example.com', 'password' => 'wrong'])
            ->assertSee('Too many login attempts.');

        // Locked: even the correct password is refused until the minute is up.
        $this->post('/login', ['email' => 'staff@example.com', 'password' => 'password']);
        $this->assertGuest();
    }

    public function test_a_successful_sign_in_resets_the_wrong_password_count(): void
    {
        $user = User::factory()->create(['email' => 'staff@example.com']);

        $this->post('/login', ['email' => 'staff@example.com', 'password' => 'wrong']);
        $this->post('/login', ['email' => 'staff@example.com', 'password' => 'password']);
        $this->assertAuthenticatedAs($user);
        $this->post('/logout');

        $this->post('/login', ['email' => 'staff@example.com', 'password' => 'wrong'])
            ->assertSessionHasErrors(['email' => 'These credentials do not match our records. 4 attempts left before sign-in is locked.']);
    }

    public function test_one_ip_cannot_keep_guessing_logins_by_switching_email(): void
    {
        config(['rate_limits.login' => 3]);

        for ($i = 0; $i < 3; $i++) {
            $this->post('/login', ['email' => "guess{$i}@example.com", 'password' => 'wrong'])
                ->assertSessionHasErrors('email');
        }

        $this->post('/login', ['email' => 'guess99@example.com', 'password' => 'wrong'])
            ->assertTooManyRequests();
    }

    public function test_password_reset_requests_are_limited(): void
    {
        config(['rate_limits.password' => 2]);

        $this->post('/forgot-password', ['email' => 'a@example.com']);
        $this->post('/forgot-password', ['email' => 'b@example.com']);

        $this->post('/forgot-password', ['email' => 'c@example.com'])->assertTooManyRequests();
    }

    public function test_reports_and_search_have_their_own_limits(): void
    {
        config(['rate_limits.reports' => 1, 'rate_limits.search' => 1]);
        $user = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)->get('/reports/summary')->assertOk();
        $this->actingAs($user)->get('/reports/budget')->assertTooManyRequests();

        $this->actingAs($user)->get('/search?q=road')->assertOk();
        $this->actingAs($user)->get('/search?q=bridge')->assertTooManyRequests();
    }
}
