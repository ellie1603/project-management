<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\ProjectProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProjectDocumentsAndProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_new_approved_design_version_and_keep_history(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create(['role' => 'admin']);
        $project = Project::factory()->create([
            'project_code' => 'BMPC-PRJ-'.date('Y').'-0001',
            'created_by' => $admin->id,
        ]);

        $first = UploadedFile::fake()->create('design-v1.pdf', 200, 'application/pdf');
        $this->actingAs($admin)
            ->post('/projects/'.$project->id.'/documents', [
                'document_type' => 'Approved Design',
                'description' => 'Initial approved design',
                'file' => $first,
            ])
            ->assertRedirect();

        $second = UploadedFile::fake()->create('design-v2.pdf', 200, 'application/pdf');
        $this->actingAs($admin)
            ->post('/projects/'.$project->id.'/documents', [
                'document_type' => 'Approved Design',
                'description' => 'Updated approved design',
                'file' => $second,
            ])
            ->assertRedirect();

        $project->refresh();

        $this->assertDatabaseHas('project_documents', [
            'project_id' => $project->id,
            'document_type' => 'Approved Design',
            'description' => 'Updated approved design',
            'is_current' => true,
        ]);

        $this->assertDatabaseHas('project_documents', [
            'project_id' => $project->id,
            'document_type' => 'Approved Design',
            'description' => 'Initial approved design',
            'is_current' => false,
        ]);

        $this->assertSame(1, $project->documents()->where('document_type', 'Approved Design')->where('is_current', true)->count());
        $this->assertSame(2, $project->documents()->where('document_type', 'Approved Design')->count());
    }

    public function test_assigned_project_personnel_can_create_progress_updates(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create(['role' => 'admin']);
        $personnel = User::factory()->create(['role' => 'project_personnel', 'position_type' => 'Foreman']);
        $project = Project::factory()->create([
            'project_code' => 'BMPC-PRJ-'.date('Y').'-0002',
            'created_by' => $admin->id,
        ]);

        $project->assignPersonnel($personnel, 'Foreman', 'Site monitoring');
        $this->seedDefaultPhases($project);
        $secondPhase = $project->phases()->where('sequence', 2)->firstOrFail();

        $response = $this->actingAs($personnel)
            ->post('/projects/'.$project->id.'/progress', [
                'phase_id' => $secondPhase->id,
                'phase_status' => 'Completed',
                'progress_date' => now()->toDateString(),
                'accomplishments' => 'Completed excavation and trench works.',
                'activities_completed' => 'Excavation, trenching',
                'activities_remaining' => 'Concreting, roofing',
                'issues' => 'Minor delay due to rain',
                'file' => UploadedFile::fake()->create('progress-report.pdf', 200, 'application/pdf'),
            ]);

        $response->assertRedirect();

        // Completing phase 2 of 5 also completes phase 1 (sequential cascade) → 40%.
        $this->assertDatabaseHas('project_progress', [
            'project_id' => $project->id,
            'user_id' => $personnel->id,
            'phase_id' => $secondPhase->id,
            'progress_percentage' => 40,
        ]);
        $this->assertSame(40, $project->fresh()->load('phases')->completionPercentage());

        $this->assertNotNull(ProjectProgress::query()->where('project_id', $project->id)->first()?->project_documents());
    }

    public function test_unassigned_personnel_cannot_create_progress_updates(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $personnel = User::factory()->create(['role' => 'project_personnel', 'position_type' => 'Foreman']);
        $otherPersonnel = User::factory()->create(['role' => 'project_personnel', 'position_type' => 'Contractor']);
        $project = Project::factory()->create([
            'project_code' => 'BMPC-PRJ-'.date('Y').'-0003',
            'created_by' => $admin->id,
        ]);

        $project->assignPersonnel($personnel, 'Foreman', 'Site monitoring');
        $this->seedDefaultPhases($project);
        $firstPhase = $project->phases()->where('sequence', 1)->firstOrFail();

        $this->actingAs($otherPersonnel)
            ->post('/projects/'.$project->id.'/progress', [
                'phase_id' => $firstPhase->id,
                'phase_status' => 'In Progress',
                'progress_date' => now()->toDateString(),
                'accomplishments' => 'Unauthorized update',
                'activities_completed' => 'None',
                'activities_remaining' => 'All',
            ])
            ->assertStatus(403);
    }

    private function seedDefaultPhases(Project $project): void
    {
        foreach (Project::DEFAULT_PHASES as $index => $name) {
            ProjectPhase::query()->create([
                'project_id' => $project->id,
                'name' => $name,
                'sequence' => $index + 1,
                'status' => 'Not Started',
            ]);
        }
    }
}
