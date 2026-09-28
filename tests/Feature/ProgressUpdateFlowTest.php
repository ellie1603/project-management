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

    public function test_personnel_can_attach_a_photo_and_a_report_in_one_update(): void
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
                    UploadedFile::fake()->create('daily-report.pdf', 120, 'application/pdf'),
                ],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $progress = ProjectProgress::query()->where('project_id', $project->id)->firstOrFail();
        $documents = $progress->projectDocuments()->get();

        $this->assertCount(2, $documents);
        $this->assertSame(1, $documents->where('document_type', 'Progress Photo')->count());
        $this->assertSame(1, $documents->where('document_type', 'Progress Report')->count());
        $this->assertCount(2, $documents->pluck('file_path')->unique(), 'Each attachment must get its own stored file.');
        $documents->each(fn ($document) => Storage::disk('local')->assertExists($document->file_path));
    }

    public function test_progress_update_accepts_at_most_two_attachments(): void
    {
        Storage::fake('local');
        [$project, $personnel, $phase] = $this->assignedProject();

        $this->actingAs($personnel)
            ->post(route('projects.progress.store', $project), [
                'phase_id' => $phase->id,
                'phase_status' => 'In Progress',
                'progress_date' => now()->toDateString(),
                'accomplishments' => 'Too many photos.',
                'files' => [
                    UploadedFile::fake()->image('a.jpg'),
                    UploadedFile::fake()->image('b.jpg'),
                    UploadedFile::fake()->image('c.jpg'),
                ],
            ])
            ->assertSessionHasErrors('files');

        $this->assertDatabaseCount('project_progress', 0);
    }

    public function test_attachments_larger_than_five_megabytes_are_rejected(): void
    {
        Storage::fake('local');
        [$project, $personnel, $phase] = $this->assignedProject();

        $this->actingAs($personnel)
            ->post(route('projects.progress.store', $project), [
                'phase_id' => $phase->id,
                'phase_status' => 'In Progress',
                'progress_date' => now()->toDateString(),
                'accomplishments' => 'Big report.',
                'files' => [UploadedFile::fake()->create('huge.pdf', 6000, 'application/pdf')],
            ])
            ->assertSessionHasErrors('files.0');
    }

    public function test_large_photos_are_shrunk_on_the_server(): void
    {
        Storage::fake('local');
        [$project, $personnel, $phase] = $this->assignedProject();

        $this->actingAs($personnel)
            ->post(route('projects.progress.store', $project), [
                'phase_id' => $phase->id,
                'phase_status' => 'In Progress',
                'progress_date' => now()->toDateString(),
                'accomplishments' => 'Photo straight from a camera.',
                'files' => [UploadedFile::fake()->image('camera.png', 4000, 3000)],
            ])
            ->assertSessionHasNoErrors();

        $photo = $project->documents()->where('document_type', 'Progress Photo')->firstOrFail();
        [$width, $height] = getimagesizefromstring(Storage::disk('local')->get($photo->file_path));

        $this->assertSame('image/jpeg', $photo->file_type);
        $this->assertStringEndsWith('.jpg', $photo->file_path);
        $this->assertLessThanOrEqual(1600, max($width, $height));
        $this->assertSame(strlen(Storage::disk('local')->get($photo->file_path)), (int) $photo->file_size);
    }

    public function test_author_can_edit_their_progress_update_and_swap_attachments(): void
    {
        Storage::fake('local');
        [$project, $personnel, $phase] = $this->assignedProject();
        $progress = $this->submitProgress($project, $personnel, $phase, 'In Progress', [
            UploadedFile::fake()->image('old-1.jpg'),
            UploadedFile::fake()->image('old-2.jpg'),
        ]);
        $oldPhoto = $progress->projectDocuments()->firstOrFail();

        $this->actingAs($personnel)
            ->put(route('projects.progress.update', [$project, $progress]), [
                'progress_date' => now()->subDay()->toDateString(),
                'accomplishments' => 'Corrected: only the north wall was plastered.',
                'issues' => '',
                'remove_documents' => [$oldPhoto->id],
                'files' => [UploadedFile::fake()->image('new.jpg')],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('projects.show', $project));

        $progress->refresh();
        $this->assertSame('Corrected: only the north wall was plastered.', $progress->accomplishments);
        $this->assertSame(now()->subDay()->toDateString(), $progress->progress_date->toDateString());
        $this->assertCount(2, $progress->projectDocuments()->get());
        $this->assertDatabaseMissing('project_documents', ['id' => $oldPhoto->id]);
        Storage::disk('local')->assertMissing($oldPhoto->file_path);
        $this->assertDatabaseHas('audit_logs', ['module' => 'project_progress', 'action' => 'updated', 'record_id' => $progress->id]);
    }

    public function test_editing_cannot_exceed_the_attachment_limit(): void
    {
        Storage::fake('local');
        [$project, $personnel, $phase] = $this->assignedProject();
        $progress = $this->submitProgress($project, $personnel, $phase, 'In Progress', [
            UploadedFile::fake()->image('one.jpg'),
            UploadedFile::fake()->image('two.jpg'),
        ]);

        $this->actingAs($personnel)
            ->put(route('projects.progress.update', [$project, $progress]), [
                'progress_date' => now()->toDateString(),
                'accomplishments' => 'Adding a third photo.',
                'files' => [UploadedFile::fake()->image('three.jpg')],
            ])
            ->assertSessionHasErrors('files');

        $this->assertCount(2, $progress->projectDocuments()->get());
    }

    public function test_only_the_author_can_edit_or_delete_a_progress_update(): void
    {
        [$project, $personnel, $phase] = $this->assignedProject();
        $progress = $this->submitProgress($project, $personnel, $phase, 'In Progress');

        $coworker = User::factory()->create(['role' => 'project_personnel', 'position_type' => 'Staff']);
        $project->assignPersonnel($coworker, 'Staff');
        $finance = User::factory()->create(['role' => 'finance_accounting']);

        foreach ([$coworker, $finance] as $user) {
            $this->actingAs($user)
                ->put(route('projects.progress.update', [$project, $progress]), [
                    'progress_date' => now()->toDateString(),
                    'accomplishments' => 'Tampered.',
                ])
                ->assertForbidden();

            $this->actingAs($user)
                ->delete(route('projects.progress.destroy', [$project, $progress]))
                ->assertForbidden();
        }

        $this->assertDatabaseHas('project_progress', ['id' => $progress->id, 'accomplishments' => 'Walls plastered.']);
    }

    public function test_progress_update_must_belong_to_the_project_in_the_url(): void
    {
        [$project, $personnel, $phase] = $this->assignedProject();
        $progress = $this->submitProgress($project, $personnel, $phase, 'In Progress');
        $otherProject = Project::factory()->create();
        $admin = User::query()->where('role', 'admin')->firstOrFail();

        $this->actingAs($admin)
            ->delete(route('projects.progress.destroy', [$otherProject, $progress]))
            ->assertNotFound();
    }

    public function test_deleting_the_latest_update_reverts_the_stage_and_completion(): void
    {
        Storage::fake('local');
        [$project, $personnel, $phase] = $this->assignedProject();
        $this->submitProgress($project, $personnel, $phase, 'Completed');
        $secondStage = $project->phases()->where('sequence', 2)->firstOrFail();
        $latest = $this->submitProgress($project, $personnel, $secondStage, 'Completed', [UploadedFile::fake()->image('roof.jpg')]);
        $photo = $latest->projectDocuments()->firstOrFail();

        $this->assertSame(40, $project->fresh()->completionPercentage());

        $this->actingAs($personnel)
            ->delete(route('projects.progress.destroy', [$project, $latest]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('projects.show', $project));

        $this->assertDatabaseMissing('project_progress', ['id' => $latest->id]);
        Storage::disk('local')->assertMissing($photo->file_path);
        $this->assertSame(20, $project->fresh()->completionPercentage());
        $this->assertSame('Not Started', $secondStage->fresh()->status);
        $this->assertSame('Completed', $phase->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['module' => 'project_progress', 'action' => 'deleted', 'record_id' => $latest->id]);
    }

    public function test_history_shows_edit_and_delete_only_to_the_author(): void
    {
        Storage::fake('local');
        [$project, $personnel, $phase] = $this->assignedProject();
        $progress = $this->submitProgress($project, $personnel, $phase, 'In Progress', [UploadedFile::fake()->image('wall.jpg')]);
        $coworker = User::factory()->create(['role' => 'project_personnel', 'position_type' => 'Staff']);
        $project->assignPersonnel($coworker, 'Staff');

        $this->actingAs($personnel)
            ->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee("progress-edit-{$progress->id}", false)
            ->assertSee("progress-delete-{$progress->id}", false)
            ->assertSee('Update Progress');

        $this->actingAs($coworker)
            ->get(route('projects.show', $project))
            ->assertOk()
            ->assertDontSee("progress-edit-{$progress->id}", false);
    }

    public function test_deleting_an_older_update_recalculates_the_updates_after_it(): void
    {
        [$project, $personnel, $phase] = $this->assignedProject();
        $stageTwo = $project->phases()->where('sequence', 2)->firstOrFail();
        $stageThree = $project->phases()->where('sequence', 3)->firstOrFail();

        $first = $this->submitProgress($project, $personnel, $phase, 'Completed');
        $second = $this->submitProgress($project, $personnel, $stageTwo, 'Completed');
        $third = $this->submitProgress($project, $personnel, $stageThree, 'In Progress');

        $this->actingAs($personnel)
            ->delete(route('projects.progress.destroy', [$project, $first]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('project_progress', ['id' => $first->id]);
        // Finishing stage 2 still implies stage 1 is done, so completion holds at 40%.
        $this->assertSame(40, $second->fresh()->progress_percentage);
        $this->assertSame(40, $third->fresh()->progress_percentage);
        $this->assertSame('In Progress', $stageThree->fresh()->status);
        $this->assertSame(40, $project->fresh()->completionPercentage());
    }

    public function test_deleting_the_earliest_update_moves_the_actual_start_date(): void
    {
        [$project, $personnel, $phase] = $this->assignedProject();
        $project->update(['status' => 'Registered', 'actual_start_date' => null]);

        $this->actingAs($personnel)->post(route('projects.progress.store', $project), [
            'phase_id' => $phase->id,
            'phase_status' => 'In Progress',
            'progress_date' => now()->subDays(3)->toDateString(),
            'accomplishments' => 'Site cleared.',
        ]);
        $earliest = ProjectProgress::query()->where('project_id', $project->id)->latest('id')->firstOrFail();
        $this->submitProgress($project, $personnel, $phase, 'In Progress');

        $this->actingAs($personnel)->delete(route('projects.progress.destroy', [$project, $earliest]));

        $project->refresh();
        $this->assertSame('Ongoing', $project->status);
        $this->assertSame(now()->toDateString(), substr((string) $project->actual_start_date, 0, 10));
    }

    public function test_deleting_the_only_update_returns_the_project_to_registered(): void
    {
        [$project, $personnel, $phase] = $this->assignedProject();
        $project->update(['status' => 'Registered', 'actual_start_date' => null]);
        $progress = $this->submitProgress($project, $personnel, $phase, 'In Progress');

        $this->assertSame('Ongoing', $project->fresh()->status);

        $this->actingAs($personnel)->delete(route('projects.progress.destroy', [$project, $progress]));

        $project->refresh();
        $this->assertSame('Registered', $project->status);
        $this->assertNull($project->actual_start_date);
        $this->assertSame('Not Started', $phase->fresh()->status);
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
     * @param  array<int, UploadedFile>  $files
     */
    private function submitProgress(Project $project, User $personnel, ProjectPhase $phase, string $status, array $files = []): ProjectProgress
    {
        $this->actingAs($personnel)
            ->post(route('projects.progress.store', $project), [
                'phase_id' => $phase->id,
                'phase_status' => $status,
                'progress_date' => now()->toDateString(),
                'accomplishments' => 'Walls plastered.',
                'files' => $files,
            ])
            ->assertSessionHasNoErrors();

        return ProjectProgress::query()->where('project_id', $project->id)->latest('id')->firstOrFail();
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
