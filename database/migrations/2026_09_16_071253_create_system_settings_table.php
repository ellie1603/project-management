<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        DB::table('system_settings')->insert([
            ['key' => 'organization_name', 'value' => 'BMPC', 'description' => 'Organization name shown across the app.'],
            ['key' => 'organization_logo_path', 'value' => 'resources/logo/logo.png', 'description' => 'Path to the organization logo.'],
            ['key' => 'currency', 'value' => 'PHP', 'description' => 'Default currency used in reports.'],
            ['key' => 'project_registration_threshold', 'value' => '50000', 'description' => 'Minimum threshold for project registration.'],
            ['key' => 'budget_warning_threshold_percent', 'value' => '80', 'description' => 'Warning threshold for budget utilization.'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
