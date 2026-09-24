<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProjectDocumentDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigned_personnel_can_download_a_project_document(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create(['role' => 'admin']);
        $personnel = User::factory()->create(['role' => 'project_personnel', 'position_type' => 'Foreman']);
        $project = Project::factory()->create(['created_by' => $admin->id]);
        $project->assignPersonnel($personnel, 'Foreman', 'Site monitoring');
        Storage::disk('local')->put('projects/1/documents/plan.pdf', 'project plan');
        $document = ProjectDocument::create([
            'project_id' => $project->id,
            'document_type' => 'Project Plan',
            'document_name' => 'plan.pdf',
            'file_path' => 'projects/1/documents/plan.pdf',
            'file_type' => 'application/pdf',
            'file_size' => 12,
            'version' => 1,
            'is_current' => true,
            'uploaded_by' => $admin->id,
        ]);

        $this->actingAs($personnel)
            ->get('/projects/'.$project->id.'/documents/'.$document->id.'/download')
            ->assertOk()
            ->assertDownload('plan.pdf');
    }

    public function test_unassigned_personnel_cannot_download_a_project_document(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create(['role' => 'admin']);
        $personnel = User::factory()->create(['role' => 'project_personnel', 'position_type' => 'Foreman']);
        $project = Project::factory()->create(['created_by' => $admin->id]);
        $document = ProjectDocument::create([
            'project_id' => $project->id,
            'document_type' => 'Project Plan',
            'document_name' => 'plan.pdf',
            'file_path' => 'projects/1/documents/plan.pdf',
            'version' => 1,
            'is_current' => true,
            'uploaded_by' => $admin->id,
        ]);

        $this->actingAs($personnel)
            ->get('/projects/'.$project->id.'/documents/'.$document->id.'/download')
            ->assertStatus(403);
    }
}
