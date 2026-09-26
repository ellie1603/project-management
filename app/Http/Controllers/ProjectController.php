<?php

namespace App\Http\Controllers;

use App\Models\Contractor;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\ProjectCategory;
use App\Models\ProjectDocument;
use App\Models\ProjectPhase;
use App\Models\User;
use App\Notifications\NewDocumentUploadedNotification;
use App\Notifications\ProjectAssignmentNotification;
use App\Notifications\ProjectCompletedNotification;
use App\Notifications\ProjectProgressUpdatedNotification;
use App\Services\AuditLogger;
use App\Services\ProgressReportAssistant;
use App\Services\ProjectNotifier;
use App\Services\ProjectPhaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectController extends Controller
{
    /** Attachments accepted with a progress update: site photos and report files. */
    private const PROGRESS_FILE_TYPES = 'jpg,jpeg,png,webp,heic,heif,pdf,doc,docx,xls,xlsx';

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Project::class);

        $projects = Project::query()
            ->with(['category', 'assignments.user', 'contractors', 'phases'])
            ->visibleTo(Auth::user())
            ->filter($request)
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $projects->getCollection()->transform(function (Project $project): Project {
            $project->setAttribute('budget', $project->budgetSummary());
            $project->setAttribute('timeline', $project->timelineSummary());
            $project->setAttribute('latestProgress', $project->progress()->latest('progress_date')->first());

            return $project;
        });

        $categories = ProjectCategory::query()->orderBy('name')->get();

        $visible = Project::query()->visibleTo(Auth::user());
        $summaryStats = [
            'total' => (clone $visible)->count(),
            'ongoing' => (clone $visible)->where('status', 'Ongoing')->count(),
            'completed' => (clone $visible)->where('status', 'Completed')->count(),
            'on_hold' => (clone $visible)->where('status', 'On Hold')->count(),
        ];

        return $this->respond($request, 'projects.index', 'projects.partials.results', compact('projects', 'categories', 'summaryStats'));
    }

    public function create(): View
    {
        $this->authorize('create', Project::class);

        $categories = ProjectCategory::query()->orderBy('name')->get();
        $projectPersonnel = User::query()->where('role', 'project_personnel')->orderBy('name')->get();

        return view('projects.create', compact('categories', 'projectPersonnel'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Project::class);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['required', 'exists:project_categories,id'],
            'project_type' => ['nullable', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:255'],
            'objective' => ['nullable', 'string'],
            'remarks' => ['nullable', 'string'],
            'approved_budget' => ['required', 'numeric', 'min:0'],
            'planned_start_date' => ['required', 'date'],
            'target_completion_date' => ['required', 'date', 'after_or_equal:planned_start_date'],
            'personnel' => ['nullable', 'array'],
            'personnel.*.user_id' => ['required', 'distinct', Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'project_personnel'))],
            'personnel.*.position_type' => ['nullable', Rule::in(ProjectAssignment::POSITION_TYPES)],
            'personnel.*.responsibility' => ['nullable', 'string', 'max:255'],
        ]);

        $personnel = $validated['personnel'] ?? [];
        unset($validated['personnel']);

        $project = DB::transaction(function () use ($validated, $personnel): Project {
            $project = Project::create([
                ...$validated,
                'project_code' => $this->generateProjectCode(),
                'status' => 'Registered',
                'created_by' => Auth::id(),
            ]);

            foreach (Project::DEFAULT_PHASES as $index => $name) {
                $project->phases()->create([
                    'name' => $name,
                    'sequence' => $index + 1,
                    'status' => 'Not Started',
                ]);
            }

            foreach ($personnel as $row) {
                $user = User::findOrFail($row['user_id']);
                $project->assignPersonnel($user, $row['position_type'] ?? $user->position_type ?? 'Staff', $row['responsibility'] ?? null);
                $user->notify(new ProjectAssignmentNotification($project));
            }

            return $project;
        });

        app(AuditLogger::class)->record(
            $request,
            'created',
            'projects',
            $project->id,
            "Registered {$project->title}.",
            null,
            $project->toArray(),
        );

        return redirect()->route('projects.show', $project)
            ->with('status', "Project {$project->project_code} registered successfully.");
    }

    public function show(Project $project): View
    {
        $this->authorize('view', $project);

        $project->load([
            'category',
            'documents.uploader',
            'progress.user',
            'progress.phase',
            'progress.projectDocuments',
            'phases',
            'assignments.user',
            'contractors',
            'quotations.contractor',
            'expenses.creator',
            'budgetRequests.requester',
            'budgetRequests.reviewer',
        ]);
        $projectPersonnel = Auth::user()->isAdmin()
            ? User::query()->where('role', 'project_personnel')->orderBy('name')->get()
            : collect();
        $availableContractors = (Auth::user()->isAdmin())
            ? Contractor::query()->orderBy('name')->get()
            : collect();
        $activityLog = $project->activityLog();
        $aiAssistEnabled = app(ProgressReportAssistant::class)->isEnabled();

        return view('projects.show', compact('project', 'projectPersonnel', 'availableContractors', 'activityLog', 'aiAssistEnabled'));
    }

    public function edit(Project $project): View
    {
        $this->authorize('update', $project);

        $categories = ProjectCategory::query()->orderBy('name')->get();

        return view('projects.edit', compact('project', 'categories'));
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:project_categories,id'],
            'project_type' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'objective' => ['nullable', 'string'],
            'planned_start_date' => ['nullable', 'date'],
            'target_completion_date' => ['nullable', 'date', 'after_or_equal:planned_start_date'],
            'actual_start_date' => ['nullable', 'date'],
            'actual_completion_date' => ['nullable', 'date', 'after_or_equal:actual_start_date'],
            'status' => ['sometimes', 'required', Rule::in(Project::STATUSES)],
            'remarks' => ['nullable', 'string'],
        ]);
        $oldValues = $project->toArray();
        $wasCompleted = $project->status === 'Completed';
        $project->update($validated);
        $project->refresh();

        app(AuditLogger::class)->record(
            $request,
            'updated',
            'projects',
            $project->id,
            "Updated {$project->title}.",
            $oldValues,
            $project->toArray(),
        );

        if (! $wasCompleted && $project->status === 'Completed') {
            $notifier = app(ProjectNotifier::class);
            $recipients = $notifier->projectTeam($project)->merge($notifier->financeTeam())->unique('id');
            $notifier->notify($recipients, new ProjectCompletedNotification($project), Auth::user());
        }

        return redirect()->route('projects.show', $project);
    }

    public function destroy(Project $project): RedirectResponse
    {
        $this->authorize('delete', $project);

        $project->delete();

        return redirect()->route('projects.index');
    }

    public function assignPersonnel(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'user_id' => ['required', Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'project_personnel'))],
            'position_type' => ['required', Rule::in(ProjectAssignment::POSITION_TYPES)],
            'responsibility' => ['required', 'string'],
        ]);

        $user = User::findOrFail($validated['user_id']);
        $assignment = $project->assignPersonnel($user, $validated['position_type'], $validated['responsibility']);
        $user->notify(new ProjectAssignmentNotification($project));
        app(AuditLogger::class)->record(
            $request,
            'assigned',
            'projects',
            $project->id,
            "Assigned {$user->name} to {$project->title}.",
            null,
            $assignment->toArray(),
        );

        return back();
    }

    public function uploadDocument(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'document_type' => ['required', 'string', Rule::in(Project::DOCUMENT_TYPES)],
            'description' => ['nullable', 'string', 'max:255'],
            'file' => ['required', 'file', 'max:20480'],
        ]);

        $this->authorize(
            $validated['document_type'] === 'Approved Design' ? 'manageDesign' : 'uploadDocument',
            $project,
        );

        $file = $request->file('file');
        $directory = "projects/{$project->id}/documents";
        $fileName = $this->buildDocumentFileName($file, $validated['document_type']);
        $path = $file->storeAs($directory, $fileName, 'local');

        $version = (int) ProjectDocument::query()
            ->where('project_id', $project->id)
            ->where('document_type', $validated['document_type'])
            ->max('version') + 1;

        $document = $project->documents()->create([
            'project_id' => $project->id,
            'document_type' => $validated['document_type'],
            'document_name' => $fileName,
            'file_path' => $path,
            'file_type' => $file->getClientMimeType() ?? 'application/octet-stream',
            'file_size' => $file->getSize(),
            'version' => $version,
            'is_current' => $validated['document_type'] === 'Approved Design',
            'description' => $validated['description'] ?? null,
            'uploaded_by' => Auth::id(),
        ]);

        if ($validated['document_type'] === 'Approved Design') {
            $project->documents()
                ->where('document_type', 'Approved Design')
                ->whereKeyNot($document->getKey())
                ->update(['is_current' => false]);
        }

        app(AuditLogger::class)->record(
            $request,
            'uploaded',
            'project_documents',
            $document->id,
            "Uploaded {$document->document_type} for {$project->title}.",
            null,
            $document->toArray(),
        );

        $notifier = app(ProjectNotifier::class);
        $notifier->notify($notifier->projectTeam($project), new NewDocumentUploadedNotification($project, $document), Auth::user());

        return redirect()->route('projects.show', $project)->with('status', 'Document uploaded successfully.');
    }

    public function downloadDocument(Project $project, ProjectDocument $document): StreamedResponse
    {
        $this->authorize('view', $project);
        abort_unless($document->project_id === $project->id, 404);

        // ?inline=1 lets progress photos render as thumbnails instead of downloading.
        if (request()->boolean('inline') && str_starts_with((string) $document->file_type, 'image/')) {
            return Storage::disk('local')->response($document->file_path, $document->document_name, [
                'Content-Type' => $document->file_type,
                'Cache-Control' => 'private, max-age=86400',
            ]);
        }

        return Storage::disk('local')->download($document->file_path, $document->document_name, [
            'Content-Type' => $document->file_type ?? 'application/octet-stream',
        ]);
    }

    public function storeProgress(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('createProgress', $project);

        $validated = $request->validate([
            'phase_id' => ['required', Rule::exists('project_phases', 'id')->where('project_id', $project->id)],
            'phase_status' => ['required', Rule::in(['In Progress', 'Completed'])],
            'progress_date' => ['required', 'date', 'before_or_equal:today'],
            'accomplishments' => ['required', 'string', 'max:5000'],
            'activities_completed' => ['nullable', 'string', 'max:5000'],
            'activities_remaining' => ['nullable', 'string', 'max:5000'],
            'issues' => ['nullable', 'string', 'max:5000'],
            'ai_assisted' => ['nullable', 'boolean'],
            'site_notes' => ['nullable', 'string', 'max:2000'],
            // Site photos straight from a phone camera, or a report file.
            'files' => ['nullable', 'array', 'max:10'],
            'files.*' => ['file', 'max:20480', 'mimes:'.self::PROGRESS_FILE_TYPES],
            'file' => ['nullable', 'file', 'max:20480', 'mimes:'.self::PROGRESS_FILE_TYPES],
        ], [
            'files.*.mimes' => 'Attach photos (JPG, PNG, WEBP, HEIC) or reports (PDF, Word, Excel) only.',
            'files.*.max' => 'Each attachment must be 20 MB or smaller.',
            'progress_date.before_or_equal' => 'The update date cannot be in the future.',
        ]);

        $phase = ProjectPhase::findOrFail($validated['phase_id']);
        $previousPercentage = $project->completionPercentage();
        $percentage = app(ProjectPhaseService::class)->applyUpdate($project, $phase, $validated['phase_status'], $validated['progress_date']);

        // The system determines project status from completion automatically:
        // 0% = Not Started, 1-99% = In Progress (Ongoing), 100% = Completed.
        // The first progress update also records when construction actually began.
        $wasCompleted = $project->status === 'Completed';
        $projectUpdates = [];
        if ($project->actual_start_date === null) {
            $projectUpdates['actual_start_date'] = $validated['progress_date'];
        }
        if ($percentage >= 100) {
            $projectUpdates['actual_completion_date'] = $project->actual_completion_date ?? $validated['progress_date'];
            $projectUpdates['status'] = 'Completed';
        } elseif ($project->status === 'Registered') {
            $projectUpdates['status'] = 'Ongoing';
        }
        if ($projectUpdates !== []) {
            $project->update($projectUpdates);
        }

        if (! $wasCompleted && $project->status === 'Completed') {
            $notifier = app(ProjectNotifier::class);
            $recipients = $notifier->projectTeam($project)->merge($notifier->financeTeam())->unique('id');
            $notifier->notify($recipients, new ProjectCompletedNotification($project), Auth::user());
        }

        $progress = $project->progress()->create([
            'project_id' => $project->id,
            'phase_id' => $phase->id,
            'user_id' => Auth::id(),
            'progress_date' => $validated['progress_date'],
            'progress_percentage' => $percentage,
            'accomplishments' => $validated['accomplishments'],
            'activities_completed' => $validated['activities_completed'] ?? null,
            'activities_remaining' => $validated['activities_remaining'] ?? null,
            'issues' => $validated['issues'] ?? null,
            'ai_assisted' => (bool) ($validated['ai_assisted'] ?? false),
            'site_notes' => ($validated['ai_assisted'] ?? false) ? ($validated['site_notes'] ?? null) : null,
        ]);

        // `file` is the legacy single-attachment field; `files[]` carries phone photos.
        $attachments = array_values(array_filter([...$request->file('files', []), $request->file('file')]));
        foreach ($attachments as $index => $file) {
            $documentType = str_starts_with((string) $file->getMimeType(), 'image/') ? 'Progress Photo' : 'Progress Report';
            $fileName = $this->buildDocumentFileName($file, $documentType, "{$progress->id}-".($index + 1));
            $path = $file->storeAs("projects/{$project->id}/progress", $fileName, 'local');

            $project->documents()->create([
                'project_id' => $project->id,
                'progress_id' => $progress->id,
                'document_type' => $documentType,
                'document_name' => $fileName,
                'file_path' => $path,
                'file_type' => $file->getMimeType() ?? 'application/octet-stream',
                'file_size' => $file->getSize(),
                'version' => 1,
                'is_current' => true,
                'description' => 'Uploaded with project progress update',
                'uploaded_by' => Auth::id(),
            ]);
        }

        $stageChange = $validated['phase_status'] === 'Completed' ? "finished {$phase->name}" : "working on {$phase->name}";

        app(AuditLogger::class)->record(
            $request,
            'created',
            'project_progress',
            $progress->id,
            "Recorded progress for {$project->title}: {$stageChange}, completion {$previousPercentage}% to {$percentage}%"
                .(count($attachments) > 0 ? ', with '.count($attachments).' attachment'.(count($attachments) === 1 ? '' : 's') : '').'.',
            null,
            $progress->toArray(),
        );

        $notifier = app(ProjectNotifier::class);
        $notifier->notify($notifier->projectTeam($project), new ProjectProgressUpdatedNotification($project, $progress), Auth::user());

        return redirect()->route('projects.show', $project)->with('status', 'Progress update saved successfully.');
    }

    protected function buildDocumentFileName(UploadedFile $file, string $documentType, int|string|null $suffix = null): string
    {
        $sanitized = preg_replace('/[^A-Za-z0-9._-]+/', '-', strtolower($documentType));
        $name = trim((string) $sanitized, '-');

        if ($suffix !== null) {
            $name .= '-'.$suffix;
        }

        return $name.'-'.time().'.'.$file->getClientOriginalExtension();
    }

    protected function generateProjectCode(): string
    {
        $year = now()->year;
        $prefix = "BMPC-PRJ-{$year}-";
        $latestCode = Project::query()
            ->where('project_code', 'like', $prefix.'%')
            ->lockForUpdate()
            ->max('project_code');
        $latestSequence = $latestCode === null ? 0 : (int) substr($latestCode, -4);

        return $prefix.str_pad((string) ($latestSequence + 1), 4, '0', STR_PAD_LEFT);
    }
}
