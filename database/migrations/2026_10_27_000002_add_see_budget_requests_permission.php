<?php

use App\Services\Permissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Budget requests are between contractor personnel and the CEO, so Finance
 * no longer sees them. The CEO and personnel keep seeing them; the System
 * Administrator can turn them back on for Finance on Roles & Permissions.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach (['admin', 'project_personnel'] as $role) {
            DB::table('role_permissions')->insertOrIgnore(['role' => $role, 'permission' => 'budget_requests.view', 'created_at' => $now, 'updated_at' => $now]);
        }

        Permissions::forget();
    }

    public function down(): void
    {
        DB::table('role_permissions')->where('permission', 'budget_requests.view')->delete();

        Permissions::forget();
    }
};
