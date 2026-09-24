<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('project_code')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('project_categories');
            $table->string('project_type')->nullable();
            $table->string('location')->nullable();
            $table->text('objective')->nullable();
            $table->decimal('approved_budget', 15, 2)->default(0);
            $table->date('planned_start_date')->nullable();
            $table->date('target_completion_date')->nullable();
            $table->date('actual_start_date')->nullable();
            $table->date('actual_completion_date')->nullable();
            $table->enum('status', ['Registered', 'Ongoing', 'On Hold', 'Completed', 'Cancelled'])->default('Registered');
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
