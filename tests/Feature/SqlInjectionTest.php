<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SqlInjectionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string}>
     */
    public static function payloads(): array
    {
        return [
            'always-true condition' => ["' OR '1'='1"],
            'comment terminator' => ["' OR 1=1 -- "],
            'stacked query' => ["'; DROP TABLE projects; -- "],
            'union select' => ["' UNION SELECT id, email, password FROM users -- "],
            'double quote' => ['" OR ""="'],
        ];
    }

    #[DataProvider('payloads')]
    public function test_search_and_filters_treat_injection_payloads_as_plain_text(string $payload): void
    {
        $personnel = User::factory()->create(['role' => 'project_personnel', 'position_type' => 'Foreman']);
        $secret = Project::factory()->create(['title' => 'Confidential Vault Project']);

        // The personnel is not assigned to the project, so no payload may reveal it.
        $this->actingAs($personnel)->get(route('search.index', ['q' => $payload]))
            ->assertOk()
            ->assertDontSee($secret->title);

        $this->actingAs($personnel)->get(route('projects.index', [
            'search' => $payload,
            'status' => $payload,
            'project_type' => $payload,
            'category_id' => $payload,
            'budget_min' => $payload,
            'budget_max' => $payload,
            'contractor_id' => $payload,
            'assigned_user_id' => $payload,
        ]))
            ->assertOk()
            ->assertDontSee($secret->title);

        $this->assertDatabaseHas('projects', ['id' => $secret->id]);
    }

    #[DataProvider('payloads')]
    public function test_admin_list_filters_survive_injection_payloads(string $payload): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Project::factory()->create();

        foreach (['users.index', 'audit-logs.index', 'documents.index', 'budget.expenses', 'budget.requests'] as $route) {
            $this->actingAs($admin)->get(route($route, ['search' => $payload, 'role' => $payload, 'module' => $payload, 'document_type' => $payload, 'status' => $payload]))
                ->assertOk();
        }

        $this->assertSame(1, Project::count());
        $this->assertSame(1, User::where('role', 'admin')->count());
    }

    #[DataProvider('payloads')]
    public function test_login_cannot_be_bypassed_with_an_injection_payload(string $payload): void
    {
        User::factory()->create(['email' => 'admin@example.com', 'role' => 'admin']);

        $this->post('/login', ['email' => "admin@example.com{$payload}", 'password' => $payload]);
        $this->assertGuest();

        $this->post('/login', ['email' => 'admin@example.com', 'password' => $payload]);
        $this->assertGuest();
    }

    public function test_malformed_filter_values_do_not_crash_the_project_list(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Project::factory()->create();

        $this->actingAs($admin)->get('/projects?date_from=not-a-date&date_to=%27%20OR%201=1')->assertOk();
        $this->actingAs($admin)->get('/projects?budget_min=abc&category_id=1.5x&assigned_user_id=--')->assertOk();
    }

    public function test_list_values_in_the_query_string_are_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/projects?status[]=Ongoing&search[]=x')->assertStatus(400);
        $this->actingAs($admin)->get('/search?q[]=x')->assertStatus(400);
        $this->actingAs($admin)->get('/users?search[$ne]=x')->assertStatus(400);
    }

    public function test_route_ids_carrying_sql_do_not_match_a_record(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = Project::factory()->create();

        $this->actingAs($admin)->get("/projects/{$project->id} OR 1=1")->assertNotFound();
        $this->actingAs($admin)->get('/projects/0 UNION SELECT 1')->assertNotFound();
    }
}
