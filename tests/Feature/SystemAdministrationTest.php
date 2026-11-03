<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Project;
use App\Models\User;
use App\Models\UserSignIn;
use App\Services\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemAdministrationTest extends TestCase
{
    use RefreshDatabase;

    private User $sysadmin;

    private User $ceo;

    private User $finance;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sysadmin = User::factory()->create(['role' => 'admin', 'name' => 'System Administrator']);
        $this->sysadmin->forceFill(['is_system_admin' => true])->save();
        $this->ceo = User::factory()->create(['role' => 'admin', 'name' => 'CEO']);
        $this->finance = User::factory()->create(['role' => 'finance_accounting']);
    }

    public function test_only_the_system_administrator_opens_the_system_pages(): void
    {
        $this->actingAs($this->sysadmin)->get(route('system.permissions'))->assertOk()->assertSee('Record expenses');
        $this->actingAs($this->sysadmin)->get(route('system.usage'))->assertOk();

        foreach ([$this->ceo, $this->finance] as $user) {
            $this->actingAs($user)->get(route('system.permissions'))->assertForbidden();
            $this->actingAs($user)->get(route('system.usage'))->assertForbidden();
            $this->actingAs($user)->put(route('system.permissions.update', 'finance_accounting'), ['permissions' => []])->assertForbidden();
        }
    }

    public function test_roles_are_listed_with_their_permission_and_user_counts(): void
    {
        $this->actingAs($this->sysadmin)->get(route('system.permissions'))
            ->assertOk()
            ->assertSeeInOrder(['System Administrator', 'All', '1', 'CEO', (string) count(Permissions::grantedMap()['admin']), '1'])
            ->assertSee('Edit Finance &amp; Accounting', false)
            ->assertSee(route('system.permissions.update', 'project_personnel'));
    }

    public function test_saving_one_role_leaves_the_other_roles_unchanged(): void
    {
        $before = Permissions::grantedMap();

        $this->actingAs($this->sysadmin)
            ->put(route('system.permissions.update', 'finance_accounting'), ['permissions' => []])
            ->assertRedirect(route('system.permissions'));

        $after = Permissions::grantedMap();
        $this->assertSame([], $after['finance_accounting']);
        $this->assertSame($before['admin'], $after['admin']);
        $this->assertSame($before['project_personnel'], $after['project_personnel']);
    }

    public function test_an_unknown_role_cannot_be_edited(): void
    {
        $this->actingAs($this->sysadmin)->put(route('system.permissions.update', 'board'), ['permissions' => []])->assertNotFound();
    }

    public function test_only_the_system_administrator_sees_the_system_menu(): void
    {
        $this->actingAs($this->sysadmin)->get('/dashboard')->assertSee('Roles &amp; Permissions', false)->assertSee('System Usage');
        $this->actingAs($this->ceo)->get('/dashboard')->assertDontSee('System Usage');
    }

    public function test_default_permissions_match_the_business_rules(): void
    {
        $this->assertTrue($this->finance->hasPermission('expenses.record'));
        $this->assertFalse($this->ceo->hasPermission('expenses.record'));
        $this->assertTrue($this->ceo->hasPermission('budget_requests.review'));
        $this->assertFalse($this->finance->hasPermission('budget_requests.review'));
        $this->assertFalse($this->finance->hasPermission('audit_logs.view'));
        $this->assertTrue($this->sysadmin->hasPermission('expenses.record'));
    }

    public function test_granting_a_permission_takes_effect_and_is_audited(): void
    {
        $this->actingAs($this->finance)->get('/audit-logs')->assertForbidden();

        $this->actingAs($this->sysadmin)
            ->put(route('system.permissions.update', 'finance_accounting'), ['permissions' => $this->grantsWith('finance_accounting', 'audit_logs.view')])
            ->assertRedirect(route('system.permissions'));

        $this->actingAs($this->finance->fresh())->get('/audit-logs')->assertOk();
        $this->assertTrue(AuditLog::where('module', 'permissions')->where('description', 'like', '%Finance & Accounting%View audit logs%')->exists());
    }

    public function test_removing_a_permission_from_the_ceo_does_not_affect_the_system_administrator(): void
    {
        $grants = array_values(array_diff(Permissions::grantedMap()['admin'], ['projects.register']));

        $this->actingAs($this->sysadmin)->put(route('system.permissions.update', 'admin'), ['permissions' => $grants]);

        $this->actingAs($this->ceo)->get(route('projects.create'))->assertForbidden();
        $this->actingAs($this->sysadmin)->get(route('projects.create'))->assertOk();
    }

    public function test_locked_permissions_cannot_be_granted(): void
    {
        $this->actingAs($this->sysadmin)
            ->put(route('system.permissions.update', 'finance_accounting'), ['permissions' => $this->grantsWith('finance_accounting', 'budget_requests.review')])
            ->assertSessionHasErrors('permissions');

        $this->assertFalse($this->finance->fresh()->hasPermission('budget_requests.review'));
    }

    public function test_personnel_lose_reports_when_the_permission_is_removed(): void
    {
        $personnel = User::factory()->create(['role' => 'project_personnel', 'position_type' => 'Foreman']);
        Project::factory()->create()->assignPersonnel($personnel, 'Foreman', 'Site lead');

        $this->actingAs($personnel)->get(route('reports.index'))->assertOk();

        $grants = array_values(array_diff(Permissions::grantedMap()['project_personnel'], ['reports.view']));
        $this->actingAs($this->sysadmin)->put(route('system.permissions.update', 'project_personnel'), ['permissions' => $grants]);

        $this->actingAs($personnel->fresh())->get(route('reports.index'))->assertForbidden();
    }

    public function test_the_ceo_cannot_change_or_remove_the_system_administrator_account(): void
    {
        $this->actingAs($this->ceo)->patch(route('users.toggle-status', $this->sysadmin))->assertForbidden();
        $this->actingAs($this->ceo)->delete(route('users.destroy', $this->sysadmin), ['confirmation' => 'confirm'])->assertForbidden();
        $this->actingAs($this->ceo)->put(route('users.update', $this->sysadmin), [
            'name' => 'Hijacked', 'email' => $this->sysadmin->email, 'role' => 'project_personnel', 'position_type' => 'Staff',
        ])->assertForbidden();

        $this->assertTrue($this->sysadmin->fresh()->isSystemAdmin());
        $this->assertTrue($this->sysadmin->fresh()->is_active);

        // User accounts are the System Administrator's job, not the CEO's.
        $this->actingAs($this->ceo)->patch(route('users.toggle-status', $this->finance))->assertForbidden();
        $this->actingAs($this->sysadmin)->patch(route('users.toggle-status', $this->finance))->assertRedirect();
    }

    public function test_sign_ins_failed_attempts_and_sign_outs_are_recorded(): void
    {
        $user = User::factory()->create(['role' => 'finance_accounting', 'email' => 'cashier@bmpc.test', 'password' => bcrypt('secret-pass-1')]);

        $this->post('/login', ['email' => 'cashier@bmpc.test', 'password' => 'wrong-password']);
        $this->post('/login', ['email' => 'cashier@bmpc.test', 'password' => 'secret-pass-1'], ['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/130.0']);
        $this->post('/logout');

        $this->assertSame(['failed', 'login', 'logout'], UserSignIn::where('user_id', $user->id)->orderBy('id')->pluck('event')->all());
        $this->assertStringContainsString('Chrome', UserSignIn::where('event', 'login')->value('user_agent'));
    }

    public function test_last_active_time_is_kept_up_to_date(): void
    {
        $this->assertNull($this->finance->last_active_at);

        $this->actingAs($this->finance)->get('/dashboard')->assertOk();

        $this->assertNotNull($this->finance->fresh()->last_active_at);
    }

    public function test_usage_report_shows_sign_ins_and_actions_per_user(): void
    {
        UserSignIn::create(['user_id' => $this->finance->id, 'email' => $this->finance->email, 'event' => 'login', 'ip_address' => '10.0.0.5', 'user_agent' => 'Mozilla/5.0 (Android 14) Chrome/130.0 Mobile']);
        UserSignIn::create(['user_id' => $this->finance->id, 'email' => $this->finance->email, 'event' => 'failed', 'ip_address' => '10.0.0.5', 'user_agent' => null]);
        foreach (range(1, 3) as $i) {
            AuditLog::create(['user_id' => $this->finance->id, 'action' => 'created', 'module' => 'project_expenses', 'record_id' => $i, 'description' => "Recorded expense {$i}."]);
        }

        $this->actingAs($this->sysadmin)->get(route('system.usage'))
            ->assertOk()
            ->assertSee($this->finance->name)
            ->assertSee('3 expenses');

        $this->actingAs($this->sysadmin)->get(route('system.usage.user', $this->finance))
            ->assertOk()
            ->assertSee('10.0.0.5')
            ->assertSee('Chrome on Android')
            ->assertSee('Recorded expense 3.');
    }

    /**
     * @return array<int, string>
     */
    private function grantsWith(string $role, string $permission): array
    {
        return [...Permissions::grantedMap()[$role], $permission];
    }
}
