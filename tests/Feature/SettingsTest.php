<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_system_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->put('/settings', [
                'organization_name' => 'Barbaza Multi-Purpose Cooperative',
                'currency' => 'PHP',
                'project_registration_threshold' => 75000,
                'budget_warning_threshold_percent' => 85,
            ])
            ->assertRedirect('/settings');

        $this->assertSame('75000', SystemSetting::resolve('project_registration_threshold'));
        $this->assertSame('85', SystemSetting::resolve('budget_warning_threshold_percent'));
    }

    public function test_admin_can_manage_project_categories(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post('/settings/categories', [
                'name' => 'Water Systems',
                'description' => 'Water supply infrastructure',
                'status' => 'active',
            ])
            ->assertRedirect('/settings');

        $category = ProjectCategory::where('name', 'Water Systems')->firstOrFail();

        $this->actingAs($admin)
            ->delete('/settings/categories/'.$category->id)
            ->assertRedirect('/settings');

        $this->assertDatabaseMissing('project_categories', ['id' => $category->id]);
    }

    public function test_category_in_use_cannot_be_deleted(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = ProjectCategory::factory()->create();
        Project::factory()->create([
            'project_code' => 'BMPC-PRJ-'.date('Y').'-0001',
            'category_id' => $category->id,
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->delete('/settings/categories/'.$category->id)
            ->assertRedirect('/settings');

        $this->assertDatabaseHas('project_categories', ['id' => $category->id]);
    }

    public function test_non_admin_cannot_access_settings(): void
    {
        $finance = User::factory()->create(['role' => 'finance_accounting']);

        $this->actingAs($finance)->get('/settings')->assertStatus(403);
        $this->actingAs($finance)->put('/settings', [])->assertStatus(403);
    }
}
