<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Accounting records an expense with just its date, total amount, and an
 * optional reference number, so category and description are no longer
 * required. Existing values are kept.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_expenses', function (Blueprint $table) {
            $table->string('category')->nullable()->change();
            $table->text('description')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('project_expenses')->whereNull('category')->update(['category' => 'Other']);
        DB::table('project_expenses')->whereNull('description')->update(['description' => '']);

        Schema::table('project_expenses', function (Blueprint $table) {
            $table->string('category')->nullable(false)->change();
            $table->text('description')->nullable(false)->change();
        });
    }
};
