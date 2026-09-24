<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'project_code' => 'BMPC-PRJ-'.date('Y').'-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'category_id' => ProjectCategory::factory(),
            'project_type' => 'Civil Works',
            'location' => fake()->city(),
            'objective' => fake()->sentence(),
            'approved_budget' => 100000,
            'planned_start_date' => now()->toDateString(),
            'target_completion_date' => now()->addMonth()->toDateString(),
            'actual_start_date' => null,
            'actual_completion_date' => null,
            'status' => 'Registered',
            'remarks' => fake()->sentence(),
            'created_by' => User::factory(),
        ];
    }
}
