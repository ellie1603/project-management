<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Supplemental Budgets (Finance directly increasing a project's baseline
     * allocation) is replaced by the Budget Request workflow: personnel
     * request funds against the existing approved budget, and Finance
     * approves or rejects — there is no longer a separate mechanism for
     * raising the baseline itself.
     */
    public function up(): void
    {
        Schema::dropIfExists('supplemental_budgets');
    }

    public function down(): void
    {
        Schema::create('supplemental_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->text('reason');
            $table->string('reference')->nullable();
            $table->date('approval_date');
            $table->string('document_path')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }
};
