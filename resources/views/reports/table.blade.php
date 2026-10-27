<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
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
        <h1>{{ $title }}</h1>
    </div>
    <p>Generated {{ now()->format('Y-m-d H:i') }}</p>
    <table>
        <thead><tr>@foreach ($headings as $heading)<th>{{ $heading }}</th>@endforeach</tr></thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>@foreach ($row as $value)<td>{{ $value ?? 'N/A' }}</td>@endforeach</tr>
            @empty
                <tr><td colspan="{{ count($headings) }}">No records match the selected filters.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
