<?php

namespace Database\Factories;

use App\Models\Contractor;
use App\Models\ContractorQuotation;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContractorQuotation>
 */
class ContractorQuotationFactory extends Factory
{
    protected $model = ContractorQuotation::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'contractor_id' => Contractor::factory(),
            'quotation_amount' => fake()->randomFloat(2, 10000, 500000),
            'quotation_date' => now()->toDateString(),
            'document_path' => null,
            'remarks' => fake()->sentence(),
        ];
    }
}
