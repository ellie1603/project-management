<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProjectDocumentAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigned_project_personnel_cannot_upload_an_approved_design(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create(['role' => 'admin']);
        $personnel = User::factory()->create(['role' => 'project_personnel', 'position_type' => 'Foreman']);
        $project = Project::factory()->create(['created_by' => $admin->id]);
        $project->assignPersonnel($personnel, 'Foreman', 'Site monitoring');

        $this->actingAs($personnel)
            ->post('/projects/'.$project->id.'/documents', [
                'document_type' => 'Approved Design',
                'description' => 'Unauthorized design replacement',
                'file' => UploadedFile::fake()->create('design.pdf', 100, 'application/pdf'),
            ])
            ->assertStatus(403);

        $this->assertDatabaseCount('project_documents', 0);
    }
}
