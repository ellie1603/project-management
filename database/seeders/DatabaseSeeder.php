<?php

namespace Database\Seeders;

use App\Models\ProjectCategory;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds only the login accounts and the project categories needed to register
 * a project. No sample projects, contractors, or expenses are created.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->seedCategories();
        $this->seedUsers();
    }

    private function seedCategories(): void
    {
        $categories = [
            'Branch Expansion' => 'New branch and satellite offices extending member service reach',
            'Head Office Development' => 'Main and head office buildings',
            'Business Building' => 'Income-generating cooperative buildings',
            'Branch Repairs' => 'Repair and renovation of existing branch offices',
        ];

        foreach ($categories as $name => $description) {
            ProjectCategory::firstOrCreate(['name' => $name], ['description' => $description, 'status' => 'active']);
        }
    }

    private function seedUsers(): void
    {
        $accounts = [
            // Both use the admin role: the CEO runs the cooperative's projects; the
            // System Administrator is the developer who maintains the system.
            ['ceo@bmpc.test', 'CEO', 'admin', null],
            ['admin@bmpc.test', 'System Administrator', 'admin', null],
            ['finance@bmpc.test', 'Finance Officer', 'finance_accounting', null],
        ];

        foreach ($accounts as [$email, $name, $role, $position]) {
            // firstOrCreate so re-seeding never resets a password someone already changed.
            User::query()->firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make('password123'),
                    'role' => $role,
                    'position_type' => $position,
                    'is_active' => true,
                ],
            );
        }

        // The developer account always has full access and manages roles & permissions.
        User::query()->where('email', 'admin@bmpc.test')->update(['is_system_admin' => true]);
    }
}
