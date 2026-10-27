<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\ProjectPhase;
use App\Models\ProjectProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeightedProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_registering_a_project_gives_its_stages_the_default_shares(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = ProjectCategory::create(['name' => 'Buildings', 'status' => 'active']);

        $this->actingAs($admin)->post('/projects', [
            'title' => 'Warehouse', 'category_id' => $category->id, 'location' => 'Barbaza', 'approved_budget' => 500000,
            'planned_start_date' => now()->toDateString(), 'target_completion_date' => now()->addYear()->toDateString(),
        ])->assertSessionHasNoErrors();

        $this->assertSame(Project::DEFAULT_PHASE_WEIGHTS, Project::firstOrFail()->phases->pluck('weight')->all());
        $this->assertSame(100, array_sum(Project::DEFAULT_PHASE_WEIGHTS));
    }

    public function test_partial_stage_progress_moves_completion_by_the_stage_share(): void
    {
        [$project, $personnel] = $this->weightedProject();
        $foundation = $this->stage($project, 2);

        $progress = $this->submit($project, $personnel, $foundation, 40);

        // Foundation is 25% of the project; 40% of it done = 10% overall.
        $this->assertSame(10, $project->fresh()->completionPercentage());
        $this->assertSame(10, $progress->progress_percentage);
        $this->assertSame(40, $progress->stage_progress);
        $this->assertSame('In Progress', $progress->phase_status);
        $this->assertSame(40, $foundation->fresh()->progress);
        $this->assertSame('In Progress', $foundation->fresh()->status);
        $this->assertSame('Ongoing', $project->fresh()->status);
    }

    public function test_many_small_updates_each_move_completion(): void
    {
        [$project, $personnel] = $this->weightedProject();
        $structure = $this->stage($project, 3);

        $percentages = collect([10, 20, 35, 50])->map(fn (int $stage) => $this->submit($project, $personnel, $structure, $stage)->progress_percentage);

        // Structure is 35% of the project.
        $this->assertSame([4, 7, 12, 18], $percentages->all());
    }

    public function test_stage_progress_cannot_go_backwards(): void
    {
        [$project, $personnel] = $this->weightedProject();
        $foundation = $this->stage($project, 2);
        $this->submit($project, $personnel, $foundation, 60);

        $this->actingAs($personnel)
            ->post(route('projects.progress.store', $project), $this->payload($foundation, 40))
            ->assertSessionHasErrors('stage_progress');

        $this->assertSame(1, ProjectProgress::count());
        $this->assertSame(60, $foundation->fresh()->progress);
    }

    public function test_finishing_a_stage_finishes_the_earlier_ones(): void
    {
        [$project, $personnel] = $this->weightedProject();

        $progress = $this->submit($project, $personnel, $this->stage($project, 2), 100);

        $this->assertSame('Completed', $progress->phase_status);
        $this->assertSame('Completed', $this->stage($project, 1)->status);
        $this->assertSame(100, $this->stage($project, 1)->progress);
        $this->assertSame(30, $project->fresh()->completionPercentage());
    }

    public function test_completion_reaches_100_only_when_every_stage_is_finished(): void
    {
        [$project, $personnel] = $this->weightedProject();
        $this->submit($project, $personnel, $this->stage($project, 4), 100);
        $this->submit($project, $personnel, $this->stage($project, 5), 95);

        // 90% + 9.5% would round to 100%, but the last stage isn't finished.
        $this->assertSame(99, $project->fresh()->completionPercentage());
        $this->assertNotSame('Completed', $project->fresh()->status);

        $this->submit($project, $personnel, $this->stage($project, 5), 100);

        $this->assertSame(100, $project->fresh()->completionPercentage());
        $this->assertSame('Completed', $project->fresh()->status);
    }

    public function test_deleting_an_update_restores_the_stage_percentage_before_it(): void
    {
        [$project, $personnel] = $this->weightedProject();
        $foundation = $this->stage($project, 2);
        $this->submit($project, $personnel, $foundation, 40);
        $latest = $this->submit($project, $personnel, $foundation, 70);

        $this->actingAs($personnel)
            ->delete(route('projects.progress.destroy', [$project, $latest]))
            ->assertSessionHasNoErrors();

        $this->assertSame(40, $foundation->fresh()->progress);
        $this->assertSame(10, $project->fresh()->completionPercentage());
    }

    public function test_old_updates_without_a_stage_percentage_still_work(): void
    {
        [$project, $personnel] = $this->weightedProject();

        $this->actingAs($personnel)
            ->post(route('projects.progress.store', $project), [
                ...$this->payload($this->stage($project, 1), null),
                'phase_status' => 'Completed',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(5, $project->fresh()->completionPercentage());
        $this->assertSame(100, ProjectProgress::firstOrFail()->stage_progress);
    }

    public function test_admin_can_change_stage_shares_and_completion_follows(): void
    {
        [$project, $personnel, $admin] = $this->weightedProject();
        $this->submit($project, $personnel, $this->stage($project, 2), 100);
        $weights = $project->phases->pluck('id')->combine([10, 40, 20, 20, 10])->all();

        $this->actingAs($admin)
            ->put(route('projects.stages.update', $project), ['weights' => $weights])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('projects.show', $project));

        $this->assertSame([10, 40, 20, 20, 10], $project->fresh()->phases->pluck('weight')->all());
        $this->assertSame(50, $project->fresh()->completionPercentage());
        $this->assertDatabaseHas('audit_logs', ['module' => 'project_stages', 'record_id' => $project->id]);
    }

    public function test_stage_shares_must_cover_every_stage_and_add_up_to_100(): void
    {
        [$project, , $admin] = $this->weightedProject();
        $ids = $project->phases->pluck('id');

        $this->actingAs($admin)
            ->put(route('projects.stages.update', $project), ['weights' => $ids->combine([10, 40, 20, 20, 20])->all()])
            ->assertSessionHasErrors('weights');

        $this->actingAs($admin)
            ->put(route('projects.stages.update', $project), ['weights' => $ids->take(4)->combine([25, 25, 25, 25])->all()])
            ->assertSessionHasErrors('weights');

        $this->assertSame(Project::DEFAULT_PHASE_WEIGHTS, $project->fresh()->phases->pluck('weight')->all());
    }

    public function test_only_the_admin_can_change_stage_shares(): void
    {
        [$project, $personnel] = $this->weightedProject();
        $finance = User::factory()->create(['role' => 'finance_accounting']);
        $weights = $project->phases->pluck('id')->combine([20, 20, 20, 20, 20])->all();

        foreach ([$personnel, $finance] as $user) {
            $this->actingAs($user)
                ->put(route('projects.stages.update', $project), ['weights' => $weights])
                ->assertForbidden();
        }

        $this->assertSame(Project::DEFAULT_PHASE_WEIGHTS, $project->fresh()->phases->pluck('weight')->all());
    }

    public function test_planned_completion_follows_the_schedule(): void
    {
        [$project] = $this->weightedProject([
            'planned_start_date' => '2026-01-01',
            'target_completion_date' => '2026-12-31',
        ]);

        $this->travelTo('2025-12-15');
        $this->assertSame(0, $project->timelineSummary()['planned_percentage']);

        $this->travelTo('2026-07-02');
        $this->assertSame(50, $project->timelineSummary()['planned_percentage']);

        $this->travelTo('2027-02-01');
        $this->assertSame(100, $project->timelineSummary()['planned_percentage']);
    }

    public function test_project_behind_its_plan_is_flagged_for_the_admin(): void
    {
        [$project, $personnel, $admin] = $this->weightedProject([
            'planned_start_date' => now()->subMonths(6)->toDateString(),
            'target_completion_date' => now()->addMonths(6)->toDateString(),
        ]);
        $this->submit($project, $personnel, $this->stage($project, 2), 40);

        $project = $project->fresh();
        $this->assertTrue($project->isBehindPlan());
        $this->assertLessThanOrEqual(-Project::BEHIND_PLAN_POINTS, $project->progressVariance());

        $this->actingAs($admin)->get(route('dashboard'))->assertOk()->assertSee('Behind planned progress');
        $this->actingAs($admin)->get(route('projects.show', $project))->assertOk()->assertSee('behind plan');
    }

    public function test_project_page_shows_stage_percentages_and_the_shares_dialog_to_the_admin_only(): void
    {
        [$project, $personnel, $admin] = $this->weightedProject();
        $this->submit($project, $personnel, $this->stage($project, 2), 40);

        $this->actingAs($admin)
            ->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('40% done')
            ->assertSee('25% of project')
            ->assertSee('Stage Shares');

        $this->actingAs($personnel)
            ->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('How much of this stage is done now?')
            ->assertSee('0% &rarr; 40% of stage', false)
            ->assertDontSee('Save Shares');
    }

    private function submit(Project $project, User $personnel, ProjectPhase $phase, int $stageProgress): ProjectProgress
    {
        $this->actingAs($personnel)
            ->post(route('projects.progress.store', $project), $this->payload($phase, $stageProgress))
            ->assertSessionHasNoErrors();

        return ProjectProgress::query()->where('project_id', $project->id)->latest('id')->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(ProjectPhase $phase, ?int $stageProgress): array
    {
        return array_filter([
            'phase_id' => $phase->id,
            'stage_progress' => $stageProgress,
            'progress_date' => now()->toDateString(),
            'accomplishments' => 'Work continued on site.',
        ], fn ($value) => $value !== null);
    }

    private function stage(Project $project, int $sequence): ProjectPhase
    {
        return $project->phases()->where('sequence', $sequence)->firstOrFail();
    }

    /**
     * A project with the default stages and shares, like a newly registered one.
     *
     * @param  array<string, mixed>  $attributes
     * @return array{0: Project, 1: User, 2: User}
     */
    private function weightedProject(array $attributes = []): array
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $personnel = User::factory()->create(['role' => 'project_personnel', 'position_type' => 'Foreman']);
        $project = Project::factory()->create(['created_by' => $admin->id, ...$attributes]);
        $project->assignPersonnel($personnel, 'Foreman', 'Site monitoring');

        foreach (Project::DEFAULT_PHASES as $index => $name) {
            $project->phases()->create([
                'name' => $name,
                'sequence' => $index + 1,
                'weight' => Project::DEFAULT_PHASE_WEIGHTS[$index],
                'status' => 'Not Started',
            ]);
        }

        return [$project, $personnel, $admin];
    }
}
