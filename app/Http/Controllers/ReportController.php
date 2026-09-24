<?php

namespace App\Http\Controllers;

use App\Exports\ArrayReportExport;
use App\Exports\ProjectStatusExport;
use App\Models\Contractor;
use App\Models\FinanceReport;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\ProjectDocument;
use App\Models\User;
use App\Services\AuditLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(): View
    {
        $this->authorizeReportAccess();
        $user = Auth::user();

        $categories = ProjectCategory::query()->orderBy('name')->get();
        $contractors = Contractor::query()->orderBy('name')->get();
        $personnel = User::query()->where('role', 'project_personnel')->orderBy('name')->get();

        $projectOptions = $user->isFinance()
            ? Project::query()->orderBy('title')->get(['id', 'title', 'project_code'])
            : collect();

        $myFinanceReports = $user->isFinance()
            ? FinanceReport::query()->where('generated_by', $user->id)->with('project:id,title,project_code')->latest()->get()
            : collect();

        $financeReports = $user->isAdmin()
            ? FinanceReport::query()->with(['project:id,title,project_code', 'generator:id,name'])->latest()->get()
            : collect();

        $personnelReports = $user->isAdmin()
            ? ProjectDocument::query()
                ->whereIn('document_type', ['Accomplishment Report', 'Progress Report'])
                ->with(['project:id,title,project_code', 'uploader:id,name'])
                ->latest()
                ->take(25)
                ->get()
            : collect();

        return view('reports.index', compact(
            'categories', 'contractors', 'personnel',
            'projectOptions', 'myFinanceReports', 'financeReports', 'personnelReports'
        ));
    }

    /**
     * Finance-only: generate a Budget Monitoring Report (org-wide or for one
     * project) and save it so Admin can browse it from the Reports page
     * without regenerating it (spec: "reports should be saved properly and
     * made available to the appropriate users, particularly Admin").
     */
    public function storeFinanceReport(Request $request): RedirectResponse
    {
        abort_unless(Auth::user()->isFinance(), 403);

        $validated = $request->validate([
            'project_id' => ['nullable', 'exists:projects,id'],
        ]);

        $projects = Project::query()
            ->with(['category', 'expenses'])
            ->when($validated['project_id'] ?? null, fn ($query) => $query->whereKey($validated['project_id']))
            ->get();

        $title = isset($validated['project_id'])
            ? 'Budget Report — '.($projects->first()->title ?? 'Project')
            : 'Budget Report — All Projects';

        [$headings, $rows] = $this->buildRows($projects, 'budget');
        $pdfBytes = Pdf::loadView('reports.table', ['title' => $title, 'headings' => $headings, 'rows' => $rows])->output();

        $fileName = 'budget-report-'.now()->format('Ymd-His').'-'.uniqid().'.pdf';
        $path = "reports/finance/{$fileName}";
        Storage::disk('local')->put($path, $pdfBytes);

        $report = FinanceReport::create([
            'project_id' => $validated['project_id'] ?? null,
            'type' => 'budget',
            'title' => $title,
            'file_path' => $path,
            'generated_by' => Auth::id(),
        ]);

        app(AuditLogger::class)->record($request, 'created', 'finance_reports', $report->id, "Generated {$title}.", null, $report->toArray());

        return redirect()->route('reports.index')->with('status', 'Report generated and saved successfully.');
    }

    public function financeReportDownload(FinanceReport $report): StreamedResponse
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->isFinance(), 403);

        return Storage::disk('local')->download($report->file_path, $report->title.'.pdf');
    }

    public function projectStatus(Request $request): View|Response|BinaryFileResponse
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->isFinance() || Auth::user()->isProjectPersonnel(), 403);

        if (Auth::user()->isProjectPersonnel()) {
            abort_unless(Auth::user()->assignedProjects()->exists(), 403);
        }

        $projects = Project::query()
            ->with('category')
            ->visibleTo(Auth::user())
            ->filter($request)
            ->latest()
            ->get();
        $format = $request->string('format', 'html')->lower()->toString();

        return match ($format) {
            'pdf' => Pdf::loadView('reports.project-status', compact('projects'))->download('project-status-report.pdf'),
            'xlsx' => Excel::download(new ProjectStatusExport($projects), 'project-status-report.xlsx'),
            default => view('reports.project-status', compact('projects')),
        };
    }

    public function budget(Request $request): View|Response|BinaryFileResponse
    {
        return $this->tableReport($request, 'budget', 'Budget Monitoring Report');
    }

    public function accomplishment(Request $request): View|Response|BinaryFileResponse
    {
        return $this->tableReport($request, 'accomplishment', 'Project Accomplishment Report');
    }

    public function delayedProjects(Request $request): View|Response|BinaryFileResponse
    {
        return $this->tableReport($request, 'delayed-projects', 'Delayed Projects Report');
    }

    public function summary(Request $request): View|Response|BinaryFileResponse
    {
        return $this->tableReport($request, 'summary', 'Project Summary Report');
    }

    public function contractorHistory(Request $request): View|Response|BinaryFileResponse
    {
        return $this->tableReport($request, 'contractor-history', 'Contractor History Report');
    }

    private function tableReport(Request $request, string $type, string $title): View|Response|BinaryFileResponse
    {
        $this->authorizeReportAccess();
        $projects = $this->filteredProjects($request, $type);
        [$headings, $rows] = $this->buildRows($projects, $type);
        $format = $request->string('format', 'html')->lower()->toString();

        return match ($format) {
            'pdf' => Pdf::loadView('reports.table', compact('title', 'headings', 'rows'))->download(str_replace(' ', '-', strtolower($title)).'.pdf'),
            'xlsx' => Excel::download(new ArrayReportExport($headings, $rows), str_replace(' ', '-', strtolower($title)).'.xlsx'),
            default => view('reports.table', compact('title', 'headings', 'rows')),
        };
    }

    private function authorizeReportAccess(): void
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->isFinance() || Auth::user()->isProjectPersonnel(), 403);

        if (Auth::user()->isProjectPersonnel()) {
            abort_unless(Auth::user()->assignedProjects()->exists(), 403);
        }
    }

    /**
     * @return Collection<int, Project>
     */
    private function filteredProjects(Request $request, string $type): Collection
    {
        $projects = Project::query()
            ->with(['category', 'expenses', 'progress', 'contractors'])
            ->visibleTo(Auth::user())
            ->filter($request)
            ->latest()
            ->get();

        if ($type === 'delayed-projects') {
            $projects = $projects->filter(fn (Project $project): bool => $project->timelineSummary()['timeline_status'] === 'Delayed')->values();
        }

        return $projects;
    }

    /**
     * @return array{0: array<int, string>, 1: Collection<int, array<int, mixed>>}
     */
    private function buildRows(Collection $projects, string $type): array
    {
        return match ($type) {
            'budget' => [
                ['Project Code', 'Title', 'Approved Budget', 'Total Available', 'Expenses', 'Remaining', 'Utilization %', 'Status'],
                $projects->map(fn (Project $project): array => array_values([
                    $project->project_code,
                    $project->title,
                    $project->budgetSummary()['approved_budget'],
                    $project->budgetSummary()['total_available_budget'],
                    $project->budgetSummary()['total_expenses'],
                    $project->budgetSummary()['remaining_budget'],
                    $project->budgetSummary()['budget_utilization_percent'],
                    $project->budgetSummary()['budget_status'],
                ])),
            ],
            'accomplishment' => [
                ['Project Code', 'Title', 'Progress %', 'Progress Date', 'Accomplishments'],
                $projects->map(fn (Project $project): array => array_values([
                    $project->project_code,
                    $project->title,
                    $project->progress->sortByDesc('progress_date')->first()?->progress_percentage,
                    $project->progress->sortByDesc('progress_date')->first()?->progress_date,
                    $project->progress->sortByDesc('progress_date')->first()?->accomplishments,
                ])),
            ],
            'delayed-projects' => [
                ['Project Code', 'Title', 'Target Completion', 'Delay Days', 'Status'],
                $projects->map(fn (Project $project): array => array_values([
                    $project->project_code,
                    $project->title,
                    $project->target_completion_date,
                    $project->timelineSummary()['delay_days'],
                    $project->timelineSummary()['timeline_status'],
                ])),
            ],
            'contractor-history' => [
                ['Project Code', 'Project', 'Contractor', 'Role', 'Contract Amount', 'Start Date', 'End Date'],
                $projects->flatMap(fn (Project $project): Collection => $project->contractors->map(fn ($contractor): array => [
                    $project->project_code,
                    $project->title,
                    $contractor->name,
                    $contractor->pivot->role,
                    $contractor->pivot->contract_amount,
                    $contractor->pivot->start_date,
                    $contractor->pivot->end_date,
                ]))->values(),
            ],
            default => [
                ['Project Code', 'Title', 'Status', 'Approved Budget', 'Expenses', 'Progress %', 'Timeline'],
                $projects->map(fn (Project $project): array => [
                    $project->project_code,
                    $project->title,
                    $project->status,
                    $project->approved_budget,
                    $project->budgetSummary()['total_expenses'],
                    $project->progress->sortByDesc('progress_date')->first()?->progress_percentage,
                    $project->timelineSummary()['timeline_status'],
                ]),
            ],
        };
    }
}
