<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectPhase;
use App\Services\ProgressReportAssistant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class ProgressAssistController extends Controller
{
    /**
     * Draft a progress report from the personnel's site notes and photos.
     * Returns the draft for review only — nothing is saved here.
     */
    public function __invoke(Request $request, Project $project, ProgressReportAssistant $assistant): JsonResponse
    {
        $this->authorize('createProgress', $project);

        abort_unless($assistant->isEnabled(), 404);

        $validated = $request->validate([
            'phase_id' => ['required', Rule::exists('project_phases', 'id')->where('project_id', $project->id)],
            'phase_status' => ['required', Rule::in(['In Progress', 'Completed'])],
            'notes' => ['required', 'string', 'min:3', 'max:2000'],
            'photos' => ['nullable', 'array', 'max:'.ProgressReportAssistant::MAX_IMAGES],
            'photos.*' => ['image', 'max:5120'],
        ]);

        try {
            $draft = $assistant->draft(
                $project,
                ProjectPhase::findOrFail($validated['phase_id']),
                $validated['phase_status'],
                $validated['notes'],
                $request->file('photos', []),
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 503);
        }

        return response()->json(['draft' => $draft]);
    }
}
