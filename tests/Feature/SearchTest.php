<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_find_a_project_by_code(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = Project::factory()->create([
            'project_code' => 'BMPC-PRJ-'.date('Y').'-0099',
            'title' => 'Searchable Bridge Project',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('search.index', ['q' => 'Searchable Bridge']))
            ->assertOk()
            ->assertSee($project->title);
    }

    public function test_project_personnel_only_finds_assigned_projects(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $personnel = User::factory()->create(['role' => 'project_personnel', 'position_type' => 'Foreman']);
        $otherPersonnel = User::factory()->create(['role' => 'project_personnel', 'position_type' => 'Contractor']);

        $ownProject = Project::factory()->create([
            'project_code' => 'BMPC-PRJ-'.date('Y').'-0010',
            'title' => 'Shared Keyword Assigned Project',
            'created_by' => $admin->id,
        ]);
        $ownProject->assignPersonnel($personnel, 'Foreman', 'Site lead');

        $otherProject = Project::factory()->create([
            'project_code' => 'BMPC-PRJ-'.date('Y').'-0011',
            'title' => 'Shared Keyword Unassigned Project',
            'created_by' => $admin->id,
        ]);
        $otherProject->assignPersonnel($otherPersonnel, 'Contractor', 'Site lead');

        $this->actingAs($personnel)
            ->get(route('search.index', ['q' => 'Shared Keyword']))
            ->assertOk()
            ->assertSee($ownProject->title)
            ->assertDontSee($otherProject->title);
    }
}
