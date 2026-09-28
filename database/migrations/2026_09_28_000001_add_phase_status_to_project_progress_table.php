<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_progress', function (Blueprint $table) {
            // Needed to rebuild construction stages when the latest update is deleted.
            $table->string('phase_status')->nullable()->after('phase_id');
        });
    }

    public function down(): void
    {
        Schema::table('project_progress', function (Blueprint $table) {
            $table->dropColumn('phase_status');
        });
    }
};
