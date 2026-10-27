<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateStageWeightsRequest;
use App\Models\Project;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class ProjectStageController extends Controller
{
    /**
     * Set how much of the whole project each construction stage is worth.
     * Stage progress stays as recorded; only the overall completion changes.
     */
    public function update(UpdateStageWeightsRequest $request, Project $project): RedirectResponse
    {
        $weights = array_map('intval', $request->validated('weights'));
        $oldWeights = $project->phases->pluck('weight', 'name')->all();
        $previousPercentage = $project->completionPercentage();

        DB::transaction(function () use ($project, $weights): void {
            foreach ($project->phases as $phase) {
                $phase->update(['weight' => $weights[$phase->id]]);
            }
        });

        $project->unsetRelation('phases');
        $percentage = $project->completionPercentage();

        app(AuditLogger::class)->record(
            $request,
            'updated',
            'project_stages',
            $project->id,
            "Changed the stage shares for {$project->title}; completion {$previousPercentage}% to {$percentage}%.",
            $oldWeights,
            $project->phases->pluck('weight', 'name')->all(),
        );

        return redirect()->route('projects.show', $project)
            ->with(['status' => 'Stage shares saved.', 'tab' => 'progress']);
    }
}
