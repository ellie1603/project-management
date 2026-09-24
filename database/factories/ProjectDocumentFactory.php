<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectDocument>
 */
class ProjectDocumentFactory extends Factory
{
    protected $model = ProjectDocument::class;

    public function definition(): array
    {
        $documentType = fake()->randomElement(Project::DOCUMENT_TYPES);
        $fileName = strtolower(str_replace(' ', '-', $documentType)).'-'.fake()->unique()->numberBetween(1, 100000).'.pdf';

        return [
            'project_id' => Project::factory(),
            'document_type' => $documentType,
            'document_name' => $fileName,
            'file_path' => "seed/{$fileName}",
            'file_type' => 'application/pdf',
            'file_size' => fake()->numberBetween(20_000, 2_000_000),
            'version' => 1,
            'is_current' => $documentType === 'Approved Design',
            'description' => fake()->optional()->sentence(),
            'uploaded_by' => User::factory(),
        ];
    }
}
