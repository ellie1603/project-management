<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('progress_id')->nullable()->constrained('project_progress')->cascadeOnDelete();
            $table->enum('document_type', ['Approved Design', 'Project Plan', 'Contract', 'Quotation', 'Accomplishment Report', 'Progress Report', 'Progress Photo', 'Other']);
            $table->string('document_name');
            $table->string('file_path');
            $table->string('file_type')->nullable();
            $table->integer('file_size')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_current')->default(false);
            $table->text('description')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_documents');
    }
};
