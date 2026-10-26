<?php

namespace Tests\Feature;

use App\Models\Contractor;
use App\Models\Project;
use App\Models\User;
use App\Services\Breadcrumbs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class BreadcrumbsTest extends TestCase
{
    use RefreshDatabase;

    public function test_pages_inside_a_module_trail_back_to_that_module_only(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = Project::factory()->create(['title' => 'Farm-to-Market Road']);

        $this->actingAs($admin)->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('aria-label="Breadcrumb"', false)
            ->assertSeeInOrder(['Projects', 'Farm-to-Market Road']);

        $crumbs = $this->crumbsFor($admin, 'projects.budget.show', ['project' => $project]);
        $this->assertSame(['Projects', 'Farm-to-Market Road', 'Budget'], array_column($crumbs, 'label'));
        $this->assertSame(route('projects.index'), $crumbs[0]['url']);
        $this->assertSame(route('projects.show', $project), $crumbs[1]['url']);
        $this->assertNull($crumbs[2]['url'], 'the current page is not a link');
    }

    public function test_pages_opened_from_the_sidebar_have_no_breadcrumbs(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $finance = User::factory()->create(['role' => 'finance_accounting']);

        foreach (['dashboard', 'projects.index', 'settings.edit', 'users.index', 'budget.overview'] as $route) {
            $this->assertSame([], $this->crumbsFor($admin, $route), $route);
        }

        $this->actingAs($admin)->get(route('settings.edit'))->assertOk()->assertDontSee('aria-label="Breadcrumb"', false);

        // Finance opens Expenses from its own sidebar, the Admin/CEO from Budget & Finance.
        $this->assertSame([], $this->crumbsFor($finance, 'budget.expenses'));
        $this->assertSame(['Budget & Finance', 'Expenses'], array_column($this->crumbsFor($admin, 'budget.expenses'), 'label'));
    }

    public function test_personnel_trail_starts_at_my_projects(): void
    {
        $personnel = User::factory()->create(['role' => 'project_personnel', 'position_type' => 'Foreman']);
        $project = Project::factory()->create(['title' => 'Water System']);

        $this->assertSame(['My Projects', 'Water System'], array_column($this->crumbsFor($personnel, 'projects.show', ['project' => $project]), 'label'));
    }

    public function test_provider_page_goes_back_to_users_for_admins(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $contractor = Contractor::factory()->create(['name' => 'ABC Company']);

        $this->actingAs($admin)->get(route('contractors.show', $contractor))
            ->assertOk()
            ->assertSeeInOrder(['Users', 'ABC Company'])
            ->assertDontSee('Contractors / Providers');
    }

    public function test_usage_details_link_back_to_the_same_period(): void
    {
        $sysadmin = User::factory()->create(['role' => 'admin']);
        $sysadmin->forceFill(['is_system_admin' => true])->save();
        $finance = User::factory()->create(['role' => 'finance_accounting', 'name' => 'Ana Reyes']);

        $crumbs = $this->crumbsFor($sysadmin, 'system.usage.user', ['user' => $finance], ['from' => '2026-09-01', 'to' => '2026-09-30']);

        $this->assertSame(['System Usage', 'Ana Reyes'], array_column($crumbs, 'label'));
        $this->assertSame(route('system.usage', ['from' => '2026-09-01', 'to' => '2026-09-30']), $crumbs[0]['url']);
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @param  array<string, string>  $query
     * @return array<int, array{label: string, url: ?string}>
     */
    private function crumbsFor(User $user, string $routeName, array $parameters = [], array $query = []): array
    {
        $request = Request::create(route($routeName, $parameters), 'GET', $query);
        $route = Route::getRoutes()->match($request);
        foreach ($parameters as $key => $value) {
            $route->setParameter($key, $value);
        }
        $request->setRouteResolver(fn () => $route);
        $request->setUserResolver(fn () => $user);

        return app(Breadcrumbs::class)->for($request);
    }
}
