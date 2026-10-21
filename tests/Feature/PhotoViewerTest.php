<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhotoViewerTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_raster_images_open_in_the_viewer(): void
    {
        $this->assertTrue(ProjectDocument::factory()->make(['file_type' => 'image/jpeg'])->isInlineImage());
        $this->assertFalse(ProjectDocument::factory()->make(['file_type' => 'image/svg+xml'])->isInlineImage());
        $this->assertFalse(ProjectDocument::factory()->make(['file_type' => 'application/pdf'])->isInlineImage());
    }

    public function test_viewer_entry_links_to_the_inline_photo_and_its_download(): void
    {
        $document = ProjectDocument::factory()->create(['file_type' => 'image/png']);

        $entry = $document->toViewerPhoto('Site photo');

        $this->assertSame('Site photo', $entry['caption']);
        $this->assertSame(route('projects.documents.download', [$document->project_id, $document->id, 'inline' => 1]), $entry['src']);
        $this->assertSame(route('projects.documents.download', [$document->project_id, $document->id]), $entry['download']);
    }

    public function test_documents_tab_offers_view_for_photos_but_not_for_other_files(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = Project::factory()->create();
        ProjectDocument::factory()->for($project)->create(['document_type' => 'Progress Photo', 'file_type' => 'image/jpeg']);
        ProjectDocument::factory()->for($project)->create(['document_type' => 'Contract', 'file_type' => 'application/pdf']);

        $response = $this->actingAs($admin)->get(route('projects.show', $project))->assertOk();

        $this->assertSame(1, substr_count($response->getContent(), "\$dispatch('view-photos'"));
    }
}
