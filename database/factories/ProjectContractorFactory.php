<?php

namespace Database\Factories;

use App\Models\Contractor;
use App\Models\Project;
use App\Models\ProjectContractor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectContractor>
 */
class ProjectContractorFactory extends Factory
{
    protected $model = ProjectContractor::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'contractor_id' => Contractor::factory(),
            'role' => 'Provider',
            'contract_amount' => fake()->randomFloat(2, 10000, 500000),
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(3)->toDateString(),
            'remarks' => fake()->sentence(),
        ];
    }
}
