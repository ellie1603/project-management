<?php

use App\Models\Project;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_phases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedTinyInteger('sequence');
            $table->enum('status', ['Not Started', 'In Progress', 'Completed'])->default('Not Started');
            $table->date('started_at')->nullable();
            $table->date('completed_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'sequence']);
        });

        $this->backfillExistingProjects();
    }

    public function down(): void
    {
        Schema::dropIfExists('project_phases');
    }

    /**
     * Every project created before this migration has no phases yet. Seed the
     * standard 5-phase template for each, inferring how far along each phase
     * set should be from the project's latest recorded progress percentage
     * (or its lifecycle status) so existing demo/production data doesn't
     * regress to 0% complete the moment this ships.
     */
    private function backfillExistingProjects(): void
    {
        $phaseNames = Project::DEFAULT_PHASES ?? ['Planning', 'Preparation', 'Implementation', 'Monitoring', 'Completion'];
        $totalPhases = count($phaseNames);

        $projects = DB::table('projects')->select('id', 'status')->get();

        foreach ($projects as $project) {
            $latestPercentage = (int) DB::table('project_progress')
                ->where('project_id', $project->id)
                ->orderByDesc('progress_date')
                ->value('progress_percentage') ?? 0;

            $completedCount = match (true) {
                $project->status === 'Completed' => $totalPhases,
                $project->status === 'Cancelled' => 0,
                default => (int) floor($latestPercentage / 100 * $totalPhases),
            };
            $completedCount = max(0, min($totalPhases, $completedCount));
            $hasInProgress = $completedCount < $totalPhases && $latestPercentage > 0;

            $now = now();
            $rows = [];

            foreach ($phaseNames as $index => $name) {
                $sequence = $index + 1;
                $status = match (true) {
                    $sequence <= $completedCount => 'Completed',
                    $sequence === $completedCount + 1 && $hasInProgress => 'In Progress',
                    default => 'Not Started',
                };

                $rows[] = [
                    'project_id' => $project->id,
                    'name' => $name,
                    'sequence' => $sequence,
                    'status' => $status,
                    'started_at' => $status !== 'Not Started' ? $now->toDateString() : null,
                    'completed_at' => $status === 'Completed' ? $now->toDateString() : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('project_phases')->insert($rows);
        }
    }
};
