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
            ['admin@bmpc.test', 'System Administrator', 'admin', null],
            ['juan@bmpc.test', 'Juan Dela Cruz', 'project_personnel', 'Contractor'],
            ['maria@bmpc.test', 'Maria Santos', 'project_personnel', 'Foreman'],
            ['pedro@bmpc.test', 'Pedro Reyes', 'project_personnel', 'Branch Manager'],
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
    }
}
