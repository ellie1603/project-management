<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectExpense;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectExpense>
 */
class ProjectExpenseFactory extends Factory
{
    protected $model = ProjectExpense::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'expense_date' => fake()->dateTimeBetween('-2 months', 'now')->format('Y-m-d'),
            'category' => fake()->randomElement(['Labor', 'Materials', 'Equipment Rental', 'Transportation', 'Permits']),
            'description' => fake()->sentence(),
            'amount' => fake()->randomFloat(2, 5000, 150000),
            'reference_number' => strtoupper(fake()->bothify('OR-####')),
            'payee' => fake()->company(),
            'document_path' => null,
            'remarks' => fake()->optional()->sentence(),
            'created_by' => User::factory(),
        ];
    }
}
