<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\ProjectProgress;
use App\Models\User;
use App\Services\ProgressReportAssistant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class ProgressUpdateFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_personnel_can_attach_several_phone_photos_and_a_report_in_one_update(): void
    {
        Storage::fake('local');
        [$project, $personnel, $phase] = $this->assignedProject();

        $this->actingAs($personnel)
            ->post(route('projects.progress.store', $project), [
                'phase_id' => $phase->id,
                'phase_status' => 'In Progress',
                'progress_date' => now()->toDateString(),
                'accomplishments' => 'Roofing on the east side finished.',
                'files' => [
                    UploadedFile::fake()->image('site-1.jpg', 1200, 900),
                    UploadedFile::fake()->image('site-2.jpg', 1200, 900),
                    UploadedFile::fake()->create('daily-report.pdf', 120, 'application/pdf'),
                ],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $progress = ProjectProgress::query()->where('project_id', $project->id)->firstOrFail();
        $documents = $progress->projectDocuments()->get();

        $this->assertCount(3, $documents);
        $this->assertSame(2, $documents->where('document_type', 'Progress Photo')->count());
        $this->assertSame(1, $documents->where('document_type', 'Progress Report')->count());
        $this->assertCount(3, $documents->pluck('file_path')->unique(), 'Each attachment must get its own stored file.');
        $documents->each(fn ($document) => Storage::disk('local')->assertExists($document->file_path));
    }

    public function test_progress_update_rejects_unsupported_attachment_types(): void
    {
        Storage::fake('local');
        [$project, $personnel, $phase] = $this->assignedProject();

        $this->actingAs($personnel)
            ->post(route('projects.progress.store', $project), [
                'phase_id' => $phase->id,
                'phase_status' => 'In Progress',
                'progress_date' => now()->toDateString(),
                'accomplishments' => 'Work continues.',
                'files' => [UploadedFile::fake()->create('script.exe', 10, 'application/x-msdownload')],
            ])
            ->assertSessionHasErrors('files.0');

        $this->assertDatabaseCount('project_progress', 0);
    }

    public function test_progress_date_cannot_be_in_the_future(): void
    {
        [$project, $personnel, $phase] = $this->assignedProject();

        $this->actingAs($personnel)
            ->post(route('projects.progress.store', $project), [
                'phase_id' => $phase->id,
                'phase_status' => 'In Progress',
                'progress_date' => now()->addDay()->toDateString(),
                'accomplishments' => 'Work continues.',
            ])
            ->assertSessionHasErrors('progress_date');
    }

    public function test_ai_assisted_update_keeps_the_original_site_notes(): void
    {
        [$project, $personnel, $phase] = $this->assignedProject();

        $this->actingAs($personnel)
            ->post(route('projects.progress.store', $project), [
                'phase_id' => $phase->id,
                'phase_status' => 'Completed',
                'progress_date' => now()->toDateString(),
                'accomplishments' => 'Excavation for the foundation was completed.',
                'activities_completed' => "Excavation\nSoil hauling",
                'ai_assisted' => 1,
                'site_notes' => 'tapos na excavation, hakot lupa done',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('project_progress', [
            'project_id' => $project->id,
            'ai_assisted' => true,
            'site_notes' => 'tapos na excavation, hakot lupa done',
        ]);
    }

    public function test_site_notes_are_not_stored_for_updates_written_without_ai(): void
    {
        [$project, $personnel, $phase] = $this->assignedProject();

        $this->actingAs($personnel)
            ->post(route('projects.progress.store', $project), [
                'phase_id' => $phase->id,
                'phase_status' => 'In Progress',
                'progress_date' => now()->toDateString(),
                'accomplishments' => 'Excavation ongoing.',
                'ai_assisted' => 0,
                'site_notes' => 'should be ignored',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('project_progress', ['project_id' => $project->id, 'ai_assisted' => false, 'site_notes' => null]);
    }

    public function test_assigned_personnel_get_an_ai_draft_for_review(): void
    {
        [$project, $personnel, $phase] = $this->assignedProject();

        $this->mock(ProgressReportAssistant::class, function (MockInterface $mock): void {
            $mock->shouldReceive('isEnabled')->andReturn(true);
            $mock->shouldReceive('draft')->once()->andReturn([
                'accomplishments' => 'Roof sheets were installed on the east side.',
                'activities_completed' => 'Roof sheet installation',
                'activities_remaining' => 'Gutter installation',
                'issues' => '',
            ]);
        });

        $this->actingAs($personnel)
            ->postJson(route('projects.progress.assist', $project), [
                'phase_id' => $phase->id,
                'phase_status' => 'In Progress',
                'notes' => 'natapos roofing east side',
            ])
            ->assertOk()
            ->assertJsonPath('draft.accomplishments', 'Roof sheets were installed on the east side.');

        $this->assertDatabaseCount('project_progress', 0);
    }

    public function test_ai_draft_failure_returns_a_readable_message(): void
    {
        [$project, $personnel, $phase] = $this->assignedProject();

        $this->mock(ProgressReportAssistant::class, function (MockInterface $mock): void {
            $mock->shouldReceive('isEnabled')->andReturn(true);
            $mock->shouldReceive('draft')->andThrow(new RuntimeException('The AI assistant is busy right now.'));
        });

        $this->actingAs($personnel)
            ->postJson(route('projects.progress.assist', $project), [
                'phase_id' => $phase->id,
                'phase_status' => 'In Progress',
                'notes' => 'natapos roofing',
            ])
            ->assertStatus(503)
            ->assertJsonPath('message', 'The AI assistant is busy right now.');
    }

    public function test_unassigned_personnel_cannot_use_the_ai_assistant(): void
    {
        [$project, , $phase] = $this->assignedProject();
        $outsider = User::factory()->create(['role' => 'project_personnel', 'position_type' => 'Staff']);

        $this->mock(ProgressReportAssistant::class, function (MockInterface $mock): void {
            $mock->shouldReceive('isEnabled')->andReturn(true);
            $mock->shouldNotReceive('draft');
        });

        $this->actingAs($outsider)
            ->postJson(route('projects.progress.assist', $project), [
                'phase_id' => $phase->id,
                'phase_status' => 'In Progress',
                'notes' => 'anything',
            ])
            ->assertForbidden();
    }

    public function test_ai_assistant_is_unavailable_without_an_api_key(): void
    {
        config(['services.progress_ai.provider' => 'gemini', 'services.gemini.key' => null]);
        [$project, $personnel, $phase] = $this->assignedProject();

        $this->actingAs($personnel)
            ->postJson(route('projects.progress.assist', $project), [
                'phase_id' => $phase->id,
                'phase_status' => 'In Progress',
                'notes' => 'natapos roofing',
            ])
            ->assertNotFound();
    }

    public function test_project_page_shows_the_update_form_only_to_assigned_personnel(): void
    {
        [$project, $personnel] = $this->assignedProject();
        $admin = User::query()->where('role', 'admin')->firstOrFail();

        $this->actingAs($personnel)
            ->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('Update Project Progress')
            ->assertSee('capture="environment"', false);

        $this->actingAs($admin)
            ->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('Progress History')
            ->assertDontSee('Update Project Progress');
    }

    public function test_progress_photos_can_be_viewed_inline(): void
    {
        Storage::fake('local');
        [$project, $personnel, $phase] = $this->assignedProject();

        $this->actingAs($personnel)->post(route('projects.progress.store', $project), [
            'phase_id' => $phase->id,
            'phase_status' => 'In Progress',
            'progress_date' => now()->toDateString(),
            'accomplishments' => 'Walls plastered.',
            'files' => [UploadedFile::fake()->image('wall.jpg')],
        ]);

        $photo = $project->documents()->where('document_type', 'Progress Photo')->firstOrFail();

        $response = $this->actingAs($personnel)
            ->get(route('projects.documents.download', [$project, $photo, 'inline' => 1]))
            ->assertOk();

        $this->assertStringStartsWith('inline', (string) $response->headers->get('Content-Disposition'));
    }

    /**
     * @return array{0: Project, 1: User, 2: ProjectPhase}
     */
    private function assignedProject(): array
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $personnel = User::factory()->create(['role' => 'project_personnel', 'position_type' => 'Foreman']);
        $project = Project::factory()->create(['created_by' => $admin->id]);
        $project->assignPersonnel($personnel, 'Foreman', 'Site monitoring');

        foreach (Project::DEFAULT_PHASES as $index => $name) {
            ProjectPhase::query()->create([
                'project_id' => $project->id,
                'name' => $name,
                'sequence' => $index + 1,
                'status' => 'Not Started',
            ]);
        }

        return [$project, $personnel, $project->phases()->where('sequence', 1)->firstOrFail()];
    }
}
