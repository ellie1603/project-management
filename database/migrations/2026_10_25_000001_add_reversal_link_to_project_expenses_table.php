<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Recorded expenses are never edited or deleted. A mistake is corrected with
 * a reversal: a negative entry pointing at the expense it cancels. The unique
 * index means an expense can be reversed only once.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_expenses', function (Blueprint $table) {
            $table->foreignId('reverses_expense_id')->nullable()->unique()->after('project_id')
                ->constrained('project_expenses')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('project_expenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reverses_expense_id');
        });
    }
};
