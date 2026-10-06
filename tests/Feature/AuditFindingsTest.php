<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuditFindingsTest extends TestCase
{
    use RefreshDatabase;

    private function personnelOn(Project $project, string $position = 'Contractor'): User
    {
        $user = User::factory()->create(['role' => 'project_personnel', 'position_type' => $position]);
        $project->assignPersonnel($user, $position);

        return $user;
    }

    public function test_assigned_personnel_cannot_edit_project_information(): void
    {
        $project = Project::factory()->create(['status' => 'Ongoing', 'title' => 'Original']);
        $personnel = $this->personnelOn($project);

        $this->actingAs($personnel)->get("/projects/{$project->id}/edit")->assertForbidden();
        $this->actingAs($personnel)
            ->put("/projects/{$project->id}", ['title' => 'Hijacked', 'status' => 'Cancelled'])
            ->assertForbidden();

        $this->assertSame('Original', $project->fresh()->title);
        $this->assertSame('Ongoing', $project->fresh()->status);
    }

    public function test_assigned_personnel_cannot_assign_other_personnel(): void
    {
        $project = Project::factory()->create();
        $personnel = $this->personnelOn($project);
        $other = User::factory()->create(['role' => 'project_personnel', 'position_type' => 'Staff']);

        $this->actingAs($personnel)
            ->post("/projects/{$project->id}/assignments", ['user_id' => $other->id, 'position_type' => 'Staff', 'responsibility' => 'x'])
            ->assertForbidden();

        $this->assertFalse($project->hasPersonnel($other));
    }

    public function test_deleted_or_deactivated_users_cannot_be_assigned(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = Project::factory()->create();
        $deleted = User::factory()->create(['role' => 'project_personnel', 'position_type' => 'Staff']);
        $deleted->delete();
        $inactive = User::factory()->create(['role' => 'project_personnel', 'position_type' => 'Staff', 'is_active' => false]);

        foreach ([$deleted, $inactive] as $user) {
            $this->actingAs($admin)
                ->post("/projects/{$project->id}/assignments", ['user_id' => $user->id, 'position_type' => 'Staff', 'responsibility' => 'x'])
                ->assertSessionHasErrors('user_id');
        }
    }

    public function test_registering_after_archiving_the_latest_project_gets_a_new_code(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = \App\Models\ProjectCategory::create(['name' => 'Cat', 'status' => 'active']);
        $payload = [
            'title' => 'P', 'category_id' => $category->id, 'location' => 'L', 'approved_budget' => 100000,
            'planned_start_date' => now()->toDateString(), 'target_completion_date' => now()->addMonth()->toDateString(),
        ];

        $this->actingAs($admin)->post('/projects', $payload);
        $first = Project::firstOrFail();
        $this->actingAs($admin)->delete("/projects/{$first->id}");

        $this->actingAs($admin)->post('/projects', $payload)->assertSessionHasNoErrors()->assertRedirect();

        $this->assertSame(1, Project::count());
        $this->assertNotSame($first->project_code, Project::first()->project_code);
    }

    public function test_public_self_registration_is_disabled(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', [
            'name' => 'Stranger', 'email' => 'stranger@example.com',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertNotFound();

        $this->assertDatabaseMissing('users', ['email' => 'stranger@example.com']);
    }

    public function test_project_past_target_that_never_started_is_delayed(): void
    {
        $project = Project::factory()->create([
            'status' => 'Registered',
            'planned_start_date' => now()->subMonths(3)->toDateString(),
            'target_completion_date' => now()->subDays(5)->toDateString(),
            'actual_start_date' => null,
        ]);

        $timeline = $project->timelineSummary();

        $this->assertSame('Delayed', $timeline['timeline_status']);
        $this->assertSame(5, $timeline['delay_days']);
    }

    public function test_cancelled_project_is_not_reported_as_delayed(): void
    {
        $project = Project::factory()->create([
            'status' => 'Cancelled',
            'planned_start_date' => now()->subMonths(3)->toDateString(),
            'target_completion_date' => now()->subDays(5)->toDateString(),
            'actual_start_date' => now()->subMonths(3)->toDateString(),
        ]);

        $this->assertNotSame('Delayed', $project->timelineSummary()['timeline_status']);
        $this->assertSame(0, $project->timelineSummary()['delay_days']);
    }

    public function test_document_upload_rejects_unsafe_file_types(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $project = Project::factory()->create();

        foreach (['page.html', 'shell.php', 'image.svg', 'run.exe'] as $name) {
            $this->actingAs($admin)
                ->post("/projects/{$project->id}/documents", [
                    'document_type' => 'Other',
                    'file' => UploadedFile::fake()->create($name, 10),
                ])
                ->assertSessionHasErrors('file');
        }

        $this->assertSame(0, $project->documents()->count());
    }

    public function test_svg_documents_are_never_rendered_inline(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $project = Project::factory()->create();
        Storage::disk('local')->put('projects/x.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');
        $document = $project->documents()->create([
            'document_type' => 'Other', 'document_name' => 'x.svg', 'file_path' => 'projects/x.svg',
            'file_type' => 'image/svg+xml', 'file_size' => 10, 'version' => 1, 'uploaded_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get("/projects/{$project->id}/documents/{$document->id}/download?inline=1");

        $this->assertStringContainsString('attachment', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_app_uses_philippine_time(): void
    {
        $this->assertSame('Asia/Manila', config('app.timezone'));
    }
}
