<?php

use App\Services\Permissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Users and Audit Logs are now System Administrator pages; the CEO no
 * longer has them. Servers seeded before the System Administrator existed
 * never had that account flagged, so flag it here, or it would lose the
 * pages too.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('role_permissions')
            ->where('role', 'admin')
            ->whereIn('permission', ['users.manage', 'audit_logs.view'])
            ->delete();

        if (! DB::table('users')->where('role', 'admin')->where('is_system_admin', true)->exists()) {
            $account = DB::table('users')->where('role', 'admin')->where('email', 'admin@bmpc.test')->value('id');

            if ($account === null) {
                $named = DB::table('users')->where('role', 'admin')->where('name', 'System Administrator')->pluck('id');
                $account = $named->count() === 1 ? $named->first() : null;
            }

            if ($account !== null) {
                DB::table('users')->where('id', $account)->update(['is_system_admin' => true]);
            }
        }

        Permissions::forget();
    }

    public function down(): void
    {
        $now = now();

        foreach (['users.manage', 'audit_logs.view'] as $permission) {
            DB::table('role_permissions')->insertOrIgnore(['role' => 'admin', 'permission' => $permission, 'created_at' => $now, 'updated_at' => $now]);
        }

        Permissions::forget();
    }
};
