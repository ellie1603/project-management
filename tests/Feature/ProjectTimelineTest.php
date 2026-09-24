<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTimelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_timeline_summary_reports_delayed_project(): void
    {
        $project = Project::factory()->create([
            'project_code' => 'BMPC-PRJ-'.date('Y').'-0001',
            'planned_start_date' => Carbon::today()->subDays(10),
            'target_completion_date' => Carbon::today()->subDays(3),
            'actual_start_date' => Carbon::today()->subDays(10),
            'status' => 'Ongoing',
            'created_by' => User::factory(),
        ]);

        $timeline = $project->timelineSummary();

        $this->assertSame(7, $timeline['duration']);
        $this->assertSame(10, $timeline['days_elapsed']);
        $this->assertSame(0, $timeline['days_remaining']);
        $this->assertSame(3, $timeline['delay_days']);
        $this->assertSame('Delayed', $timeline['timeline_status']);
    }

    public function test_project_without_actual_start_date_is_not_started(): void
    {
        $project = Project::factory()->create([
            'project_code' => 'BMPC-PRJ-'.date('Y').'-0004',
            'planned_start_date' => Carbon::today()->subDays(5),
            'target_completion_date' => Carbon::today()->addMonth(),
            'actual_start_date' => null,
            'status' => 'Registered',
            'created_by' => User::factory(),
        ]);

        $timeline = $project->timelineSummary();

        $this->assertSame(0, $timeline['days_elapsed']);
        $this->assertSame('Not Started', $timeline['timeline_status']);
    }

    public function test_completed_project_is_not_marked_delayed(): void
    {
        $project = Project::factory()->create([
            'project_code' => 'BMPC-PRJ-'.date('Y').'-0002',
            'planned_start_date' => Carbon::today()->subDays(20),
            'target_completion_date' => Carbon::today()->subDays(3),
            'actual_completion_date' => Carbon::today()->subDays(4),
            'status' => 'Completed',
            'created_by' => User::factory(),
        ]);

        $this->assertSame('Completed', $project->timelineSummary()['timeline_status']);
    }
}
