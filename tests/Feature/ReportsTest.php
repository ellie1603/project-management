<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_download_project_status_report_as_pdf_and_excel(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = ProjectCategory::factory()->create(['name' => 'Infrastructure']);
        Project::factory()->create([
            'project_code' => 'BMPC-PRJ-'.date('Y').'-0001',
            'title' => 'Bridge Repair',
            'status' => 'Ongoing',
            'category_id' => $category->id,
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get('/reports/project-status?format=pdf&status=Ongoing')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($admin)
            ->get('/reports/project-status?format=xlsx&category_id='.$category->id)
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_project_personnel_can_only_report_on_assigned_projects(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $personnel = User::factory()->create(['role' => 'project_personnel', 'position_type' => 'Foreman']);
        Project::factory()->create([
            'project_code' => 'BMPC-PRJ-'.date('Y').'-0002',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($personnel)
            ->get('/reports/project-status')
            ->assertStatus(403);
    }

    public function test_budget_and_delayed_reports_support_pdf_and_excel(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Project::factory()->create([
            'project_code' => 'BMPC-PRJ-'.date('Y').'-0003',
            'status' => 'Ongoing',
            'created_by' => $admin->id,
            'planned_start_date' => now()->subMonth()->toDateString(),
            'target_completion_date' => now()->subDay()->toDateString(),
        ]);

        foreach (['budget', 'delayed-projects'] as $report) {
            $this->actingAs($admin)
                ->get('/reports/'.$report.'?format=pdf')
                ->assertOk()
                ->assertHeader('content-type', 'application/pdf');

            $this->actingAs($admin)
                ->get('/reports/'.$report.'?format=xlsx')
                ->assertOk()
                ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        }
    }
}
