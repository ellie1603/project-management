<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectProgress;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectProgress>
 */
class ProjectProgressFactory extends Factory
{
    protected $model = ProjectProgress::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'user_id' => User::factory(),
            'progress_date' => fake()->dateTimeBetween('-2 months', 'now')->format('Y-m-d'),
            'progress_percentage' => fake()->numberBetween(0, 100),
            'accomplishments' => fake()->sentence(),
            'activities_completed' => fake()->sentence(),
            'activities_remaining' => fake()->optional()->sentence(),
            'issues' => fake()->optional()->sentence(),
            'remarks' => fake()->optional()->sentence(),
        ];
    }
}
