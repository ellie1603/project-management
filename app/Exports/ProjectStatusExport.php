<?php

namespace App\Exports;

use App\Models\Project;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProjectStatusExport implements FromCollection, ShouldAutoSize, WithHeadings
{
    /**
     * @param  Collection<int, Project>  $projects
     */
    public function __construct(private readonly Collection $projects) {}

    public function collection(): Collection
    {
        return $this->projects->map(fn ($project): array => [
            $project->project_code,
            $project->title,
            $project->category?->name,
            $project->status,
            (float) $project->approved_budget,
            $project->planned_start_date,
            $project->target_completion_date,
        ]);
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Project Code',
            'Title',
            'Category',
            'Status',
            'Approved Budget',
            'Planned Start',
            'Target Completion',
        ];
    }
}
