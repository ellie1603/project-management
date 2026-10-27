<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Project Status Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1e293b; }
        h1 { font-size: 22px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #cbd5e1; padding: 8px; text-align: left; font-size: 12px; }
        th { background: #e2e8f0; }
        .report-header { display: table; width: 100%; margin-bottom: 12px; }
        .report-header img { height: 48px; }
        .report-header h1 { margin: 0 0 4px; }
    </style>
</head>
<body>
    <div class="report-header">
        @include('reports.partials.logo')
        <h1>Project Status Report</h1>
    </div>
    <p>Generated {{ now()->format('Y-m-d H:i') }}</p>
    <table>
        <thead><tr><th>Project Code</th><th>Title</th><th>Category</th><th>Status</th><th>Approved Budget</th><th>Planned Start</th><th>Target Completion</th></tr></thead>
        <tbody>
            @forelse ($projects as $project)
                <tr><td>{{ $project->project_code }}</td><td>{{ $project->title }}</td><td>{{ $project->category?->name ?? 'Unassigned' }}</td><td>{{ $project->status }}</td><td>{{ number_format((float) $project->approved_budget, 2) }}</td><td>{{ $project->planned_start_date ?? 'N/A' }}</td><td>{{ $project->target_completion_date ?? 'N/A' }}</td></tr>
            @empty
                <tr><td colspan="7">No projects match the selected filters.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
