<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The Materials module was removed from the system: infrastructure
     * monitoring is scoped to progress and budget, not construction
     * material inventory.
     */
    public function up(): void
    {
        Schema::dropIfExists('project_materials');
    }

    public function down(): void
    {
        Schema::create('project_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('material_name');
            $table->text('description')->nullable();
            $table->decimal('quantity', 15, 2)->default(0);
            $table->string('unit');
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->date('date_used');
            $table->string('supplier')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }
};
