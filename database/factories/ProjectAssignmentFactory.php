<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectAssignment>
 */
class ProjectAssignmentFactory extends Factory
{
    protected $model = ProjectAssignment::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'user_id' => User::factory(),
            'position_type' => 'Foreman',
            'responsibility' => 'Site coordination',
            'assignment_date' => now()->toDateString(),
            'remarks' => fake()->sentence(),
        ];
    }
}
