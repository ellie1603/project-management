<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Completion becomes weighted: each stage carries a share of the whole
     * project (weights add up to 100) and records how far along it is, so a
     * year-long stage moves the total a little with every update instead of
     * all at once when it finishes.
     */
    public function up(): void
    {
        Schema::table('project_phases', function (Blueprint $table) {
            $table->unsignedTinyInteger('weight')->nullable()->after('sequence');
            $table->unsignedTinyInteger('progress')->default(0)->after('status');
        });

        Schema::table('project_progress', function (Blueprint $table) {
            $table->unsignedTinyInteger('stage_progress')->nullable()->after('phase_status');
        });

        // Existing projects keep equal stage shares, so their completion doesn't
        // jump; the admin can set real weights from the Progress tab.
        DB::table('project_phases')->where('status', 'Completed')->update(['progress' => 100]);

        DB::table('project_phases')->orderBy('project_id')->orderBy('sequence')->get(['id', 'project_id'])
            ->groupBy('project_id')
            ->each(function ($stages): void {
                $count = $stages->count();
                $stages->values()->each(function ($stage, int $index) use ($count): void {
                    // Spread the remainder over the first stages so the shares add up to exactly 100.
                    $weight = intdiv(100, $count) + ($index < 100 % $count ? 1 : 0);
                    DB::table('project_phases')->where('id', $stage->id)->update(['weight' => $weight]);
                });
            });

        DB::table('project_progress')->where('phase_status', 'Completed')->update(['stage_progress' => 100]);
    }

    public function down(): void
    {
        Schema::table('project_progress', function (Blueprint $table) {
            $table->dropColumn('stage_progress');
        });

        Schema::table('project_phases', function (Blueprint $table) {
            $table->dropColumn(['weight', 'progress']);
        });
    }
};
