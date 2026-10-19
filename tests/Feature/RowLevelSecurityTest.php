<?php

namespace Tests\Feature;

use App\Models\BudgetRequest;
use App\Models\Contractor;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\ProjectDocument;
use App\Models\ProjectExpense;
use App\Models\ProjectPhase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RowLevelSecurityTest extends TestCase
{
    use RefreshDatabase;

    private Project $assigned;

    private Project $unassigned;

    private User $personnel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->personnel = User::factory()->create(['role' => 'project_personnel', 'position_type' => 'Foreman']);
        $colleague = User::factory()->create(['role' => 'project_personnel', 'position_type' => 'Contractor']);

        $this->assigned = Project::factory()->create(['title' => 'Assigned Warehouse']);
        $this->assigned->assignPersonnel($this->personnel, 'Foreman');

        $this->unassigned = Project::factory()->create(['title' => 'Unassigned Bridge']);
        $this->unassigned->assignPersonnel($colleague, 'Contractor');

        foreach ([$this->assigned, $this->unassigned] as $project) {
            ProjectDocument::factory()->create(['project_id' => $project->id]);
            ProjectExpense::factory()->create(['project_id' => $project->id]);
            $project->phases()->create(['name' => 'Foundation', 'sequence' => 1, 'status' => 'Not Started']);
            $project->budgetRequests()->create([
                'requested_by' => $colleague->id, 'amount' => 1000, 'purpose' => 'Cement', 'status' => 'Pending',
            ]);
        }
    }

    public function test_queries_without_a_signed_in_user_see_every_row(): void
    {
        $this->assertSame(2, Project::count());
        $this->assertSame(2, ProjectDocument::count());
    }

    public function test_project_personnel_only_read_rows_of_their_assigned_projects(): void
    {
        $this->actingAs($this->personnel);

        $this->assertSame([$this->assigned->id], Project::pluck('id')->all());
        $this->assertNull(Project::find($this->unassigned->id));

        foreach ([ProjectDocument::class, ProjectExpense::class, ProjectPhase::class, BudgetRequest::class, ProjectAssignment::class] as $model) {
            $this->assertSame(
                [$this->assigned->id],
                $model::query()->distinct()->pluck('project_id')->all(),
                "{$model} leaked rows from an unassigned project.",
            );
        }
    }

    public function test_relations_of_an_unassigned_project_come_back_empty_for_personnel(): void
    {
        $this->actingAs($this->personnel);

        $this->assertSame(0, $this->unassigned->documents()->count());
        $this->assertSame(0, $this->unassigned->expenses()->count());
        $this->assertSame(0, $this->unassigned->assignments()->count());
    }

    public function test_admin_and_finance_read_every_row(): void
    {
        foreach (['admin', 'finance_accounting'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]));

            $this->assertSame(2, Project::count(), "{$role} should see every project.");
            $this->assertSame(2, ProjectExpense::count(), "{$role} should see every expense.");
        }
    }

    public function test_opening_an_unassigned_project_is_still_forbidden_rather_than_not_found(): void
    {
        $document = $this->unassigned->documents()->firstOrFail();

        $this->actingAs($this->personnel)->get("/projects/{$this->unassigned->id}")->assertForbidden();
        $this->actingAs($this->personnel)
            ->get("/projects/{$this->unassigned->id}/documents/{$document->id}/download")
            ->assertForbidden();
    }

    public function test_contractor_page_hides_projects_the_personnel_is_not_assigned_to(): void
    {
        $contractor = Contractor::factory()->create();
        $this->assigned->contractors()->attach($contractor->id, ['role' => 'Builder']);
        $this->unassigned->contractors()->attach($contractor->id, ['role' => 'Builder']);

        $this->actingAs($this->personnel)
            ->get("/contractors/{$contractor->id}")
            ->assertOk()
            ->assertSee('Assigned Warehouse')
            ->assertDontSee('Unassigned Bridge');
    }
}
