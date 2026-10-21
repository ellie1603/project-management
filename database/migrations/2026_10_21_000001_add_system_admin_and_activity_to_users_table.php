<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The System Administrator is an admin-role account flagged as the person
 * who maintains the system (still one of the three roles), and every user's
 * last activity is kept for the System Usage report.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_system_admin')->default(false)->after('role');
            $table->timestamp('last_active_at')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_system_admin', 'last_active_at']);
        });
    }
};
