<?php

namespace App\Services;

use App\Models\FinanceReport;
use App\Models\Project;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Permanently deletes a project and everything that belongs to it: its
 * assignments, stages, progress updates, documents, expenses, budget
 * requests, contractor links, quotations (all removed by the database's
 * cascading foreign keys), its saved finance reports and notifications, and
 * every uploaded file. Only the audit log entry recording the deletion stays.
 */
class ProjectDeletion
{
    /**
     * @return array<string, int> what was removed, for the audit log
     */
    public function delete(Project $project, ?Request $request = null): array
    {
        $project = Project::withTrashed()->withoutGlobalScopes()->findOrFail($project->getKey());

        $counts = [
            'documents' => $project->documents()->count(),
            'progress updates' => $project->progress()->count(),
            'expenses' => $project->expenses()->count(),
            'budget requests' => $project->budgetRequests()->count(),
        ];
        $reportFiles = FinanceReport::query()->withoutGlobalScopes()->where('project_id', $project->id)->pluck('file_path')->all();
        $oldValues = $project->toArray();

        DB::transaction(function () use ($project): void {
            $this->rememberCode($project->project_code);

            FinanceReport::query()->withoutGlobalScopes()->where('project_id', $project->id)->delete();

            // Notification data is JSON text; match the id exactly so project 1 never matches 10.
            DB::table('notifications')
                ->where(fn ($query) => $query
                    ->where('data', 'like', '%"project_id":'.$project->id.',%')
                    ->orWhere('data', 'like', '%"project_id":'.$project->id.'}%'))
                ->delete();

            $project->forceDelete();
        });

        // Files go last, once the records are gone for good.
        Storage::disk('local')->deleteDirectory("projects/{$project->id}");
        Storage::disk('local')->delete($reportFiles);

        $removed = collect($counts)->filter()->map(fn (int $count, string $label): string => "{$count} {$label}")->implode(', ');

        app(AuditLogger::class)->record(
            $request ?? request(),
            'deleted',
            'projects',
            $project->id,
            "Permanently deleted project {$project->project_code} ({$project->title})".($removed !== '' ? " with {$removed}" : '').'.',
            $oldValues,
        );

        return $counts;
    }

    /**
     * Project codes are never reused: keep the highest number issued per year
     * even after the project holding it is deleted.
     */
    private function rememberCode(string $code): void
    {
        if (! preg_match('/^BMPC-PRJ-(\d{4})-(\d+)$/', $code, $matches)) {
            return;
        }

        $key = "project_code_last_{$matches[1]}";
        $current = (int) SystemSetting::resolve($key, 0);

        if ((int) $matches[2] > $current) {
            SystemSetting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => (string) (int) $matches[2], 'description' => "Highest project code number issued in {$matches[1]}; deleted projects never free their code."],
            );
        }
    }
}
