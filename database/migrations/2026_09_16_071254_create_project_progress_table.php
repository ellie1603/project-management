<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->date('progress_date');
            $table->unsignedTinyInteger('progress_percentage')->default(0);
            $table->text('accomplishments');
            $table->text('activities_completed')->nullable();
            $table->text('activities_remaining')->nullable();
            $table->text('issues')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_progress');
    }
};
