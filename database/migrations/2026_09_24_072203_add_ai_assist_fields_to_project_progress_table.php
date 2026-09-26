<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_progress', function (Blueprint $table) {
            // Set when the report text was drafted by the AI assistant from site notes,
            // so reviewers can tell AI wording from the personnel's own wording.
            $table->boolean('ai_assisted')->default(false)->after('remarks');
            // The personnel's original notes the draft was generated from, kept verbatim.
            $table->text('site_notes')->nullable()->after('ai_assisted');
        });
    }

    public function down(): void
    {
        Schema::table('project_progress', function (Blueprint $table) {
            $table->dropColumn(['ai_assisted', 'site_notes']);
        });
    }
};
