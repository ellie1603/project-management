<?php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateStageWeightsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('project'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'weights' => ['required', 'array'],
            'weights.*' => ['required', 'integer', 'between:1,100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'weights.*.required' => 'Give every stage a share.',
            'weights.*.integer' => 'Stage shares must be whole numbers.',
            'weights.*.between' => 'Each stage needs a share from 1% to 100%.',
        ];
    }

    /**
     * Every stage of this project needs a share, and the shares must add up to 100%.
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                /** @var Project $project */
                $project = $this->route('project');
                $weights = $this->input('weights', []);
                $stageIds = $project->phases->pluck('id')->map(fn ($id): string => (string) $id)->sort()->values()->all();
                $submittedIds = collect(array_keys($weights))->map(fn ($id): string => (string) $id)->sort()->values()->all();

                if ($stageIds !== $submittedIds) {
                    $validator->errors()->add('weights', 'Give a share to every stage of this project.');

                    return;
                }

                $total = array_sum(array_map('intval', $weights));

                if ($total !== 100) {
                    $validator->errors()->add('weights', "The stage shares add up to {$total}%. They must add up to exactly 100%.");
                }
            },
        ];
    }
}
