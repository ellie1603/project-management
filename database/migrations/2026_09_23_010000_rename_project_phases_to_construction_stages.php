<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The infrastructure monitoring redesign replaces the generic
     * Planning/Preparation/Implementation/Monitoring/Completion phase
     * template with construction-specific stages. Existing project_phases
     * rows are renamed in place by sequence number so history and
     * completion percentages are preserved.
     */
    private const RENAMES = [
        1 => 'Construction Started',
        2 => 'Foundation / Structural Work',
        3 => 'Building / Structural Development',
        4 => 'Installation & Finishing',
        5 => 'Inspection & Completion',
    ];

    public function up(): void
    {
        foreach (self::RENAMES as $sequence => $name) {
            DB::table('project_phases')->where('sequence', $sequence)->update(['name' => $name]);
        }
    }

    public function down(): void
    {
        $originals = [
            1 => 'Planning',
            2 => 'Preparation',
            3 => 'Implementation',
            4 => 'Monitoring',
            5 => 'Completion',
        ];

        foreach ($originals as $sequence => $name) {
            DB::table('project_phases')->where('sequence', $sequence)->update(['name' => $name]);
        }
    }
};
