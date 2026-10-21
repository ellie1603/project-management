<?php

use App\Services\Permissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Which abilities each role has. A row means "granted". Starts from the
 * defaults, which match how the system behaved before permissions were
 * configurable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('role', 50);
            $table->string('permission', 100);
            $table->timestamps();

            $table->unique(['role', 'permission']);
        });

        $now = now();
        $rows = [];
        foreach (Permissions::defaults() as $role => $permissions) {
            foreach ($permissions as $permission) {
                $rows[] = ['role' => $role, 'permission' => $permission, 'created_at' => $now, 'updated_at' => $now];
            }
        }

        DB::table('role_permissions')->insert($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};
