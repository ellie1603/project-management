<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\FinanceReport;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\User;
use App\Notifications\BudgetExceededNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProjectDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_a_project_after_confirming_its_code(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = Project::factory()->create(['project_code' => 'BMPC-PRJ-2026-0100']);

        $this->actingAs($admin)
            ->delete(route('projects.destroy', $project), ['confirm_code' => 'BMPC-PRJ-2026-0100'])
            ->assertRedirect(route('projects.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
        $this->assertTrue(AuditLog::where('module', 'projects')
            ->where('action', 'deleted')
            ->where('record_id', $project->id)
            ->exists());
    }

    public function test_deleting_a_project_removes_everything_that_belongs_to_it(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create(['role' => 'admin']);
        $finance = User::factory()->create(['role' => 'finance_accounting']);
        $personnel = User::factory()->create(['role' => 'project_personnel', 'position_type' => 'Foreman']);
        $project = Project::factory()->create(['project_code' => 'BMPC-PRJ-2026-0200']);
        $other = Project::factory()->create(['project_code' => 'BMPC-PRJ-2026-0201']);
        $project->assignPersonnel($personnel, 'Foreman', 'Site lead');

        Storage::disk('local')->put("projects/{$project->id}/progress/photo.jpg", 'photo');
        Storage::disk('local')->put("projects/{$project->id}/budget-requests/quote.pdf", 'quote');
        Storage::disk('local')->put("projects/{$other->id}/documents/keep.pdf", 'keep');
        Storage::disk('local')->put('finance-reports/project-report.pdf', 'report');

        $progress = $project->progress()->create(['user_id' => $personnel->id, 'progress_percentage' => 20, 'progress_date' => now()->toDateString(), 'accomplishments' => 'Footings']);
        $project->documents()->create([
            'progress_id' => $progress->id, 'document_type' => 'Progress Photo', 'document_name' => 'photo.jpg',
            'file_path' => "projects/{$project->id}/progress/photo.jpg", 'file_type' => 'image/jpeg', 'file_size' => 5,
            'version' => 1, 'is_current' => true, 'uploaded_by' => $personnel->id,
        ]);
        $project->expenses()->create(['expense_date' => now()->toDateString(), 'amount' => 1000, 'created_by' => $finance->id]);
        $project->budgetRequests()->create(['requested_by' => $personnel->id, 'amount' => 500, 'purpose' => 'Tools', 'status' => 'Pending', 'document_path' => "projects/{$project->id}/budget-requests/quote.pdf"]);
        FinanceReport::create(['project_id' => $project->id, 'title' => 'Budget report', 'file_path' => 'finance-reports/project-report.pdf', 'generated_by' => $finance->id]);
        $admin->notify(new BudgetExceededNotification($project, 120));
        $admin->notify(new BudgetExceededNotification($other, 110));

        $this->actingAs($admin)
            ->delete(route('projects.destroy', $project), ['confirm_code' => 'BMPC-PRJ-2026-0200'])
            ->assertRedirect(route('projects.index'));

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
        foreach (['project_assignments', 'project_progress', 'project_documents', 'project_expenses', 'budget_requests', 'project_phases', 'finance_reports'] as $table) {
            $this->assertSame(0, DB::table($table)->where('project_id', $project->id)->count(), "{$table} still has rows");
        }
        $this->assertSame(0, ProjectDocument::count());
        Storage::disk('local')->assertMissing("projects/{$project->id}/progress/photo.jpg");
        Storage::disk('local')->assertMissing("projects/{$project->id}/budget-requests/quote.pdf");
        Storage::disk('local')->assertMissing('finance-reports/project-report.pdf');
        $this->assertSame(1, $admin->notifications()->count(), 'only the other project\'s notification remains');

        // Other projects are untouched.
        Storage::disk('local')->assertExists("projects/{$other->id}/documents/keep.pdf");
        $this->assertDatabaseHas('projects', ['id' => $other->id]);
    }

    public function test_a_deleted_project_code_is_never_given_to_a_new_project(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = \App\Models\ProjectCategory::create(['name' => 'Cat', 'status' => 'active']);
        $payload = [
            'title' => 'P', 'category_id' => $category->id, 'location' => 'L', 'approved_budget' => 100000,
            'planned_start_date' => now()->toDateString(), 'target_completion_date' => now()->addMonth()->toDateString(),
        ];

        $this->actingAs($admin)->post('/projects', $payload);
        $this->actingAs($admin)->post('/projects', $payload);
        $latest = Project::latest('id')->firstOrFail();
        $this->actingAs($admin)->delete("/projects/{$latest->id}", ['confirm_code' => $latest->project_code]);

        $this->actingAs($admin)->post('/projects', $payload);

        $this->assertSame('BMPC-PRJ-'.now()->year.'-0003', Project::latest('id')->value('project_code'));
    }

    public function test_delete_is_refused_when_the_typed_code_does_not_match(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = Project::factory()->create(['project_code' => 'BMPC-PRJ-2026-0101']);

        $this->actingAs($admin)
            ->delete(route('projects.destroy', $project), ['confirm_code' => 'BMPC-PRJ-2026-9999'])
            ->assertSessionHasErrors('confirm_code', null, 'projectDeletion');

        $this->assertNotSoftDeleted($project);
    }

    public function test_finance_and_personnel_cannot_delete_projects(): void
    {
        $project = Project::factory()->create(['project_code' => 'BMPC-PRJ-2026-0102']);
        $finance = User::factory()->create(['role' => 'finance_accounting']);
        $personnel = User::factory()->create(['role' => 'project_personnel', 'position_type' => 'Foreman']);
        $project->assignPersonnel($personnel, 'Foreman', 'Site lead');

        foreach ([$finance, $personnel] as $user) {
            $this->actingAs($user)
                ->delete(route('projects.destroy', $project), ['confirm_code' => 'BMPC-PRJ-2026-0102'])
                ->assertForbidden();
        }

        $this->assertNotSoftDeleted($project);
    }

    public function test_only_admin_sees_the_delete_button(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $finance = User::factory()->create(['role' => 'finance_accounting']);
        $project = Project::factory()->create(['project_code' => 'BMPC-PRJ-2026-0103']);

        $this->actingAs($admin)->get(route('projects.show', $project))->assertSee('Delete Project');
        $this->actingAs($finance)->get(route('projects.show', $project))->assertDontSee('Delete Project');
    }
}
