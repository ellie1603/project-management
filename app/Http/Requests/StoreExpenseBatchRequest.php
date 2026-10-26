<?php

namespace App\Http\Requests;

use App\Models\ProjectExpense;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreExpenseBatchRequest extends FormRequest
{
    public const MAX_ROWS = 200;

    public function authorize(): bool
    {
        return $this->user()->can('create', ProjectExpense::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'rows' => ['required', 'array', 'min:1', 'max:'.self::MAX_ROWS],
            'rows.*.project_id' => ['required', 'integer', Rule::exists('projects', 'id')->whereNull('deleted_at')],
            'rows.*.expense_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'rows.*.amount' => ['required', 'numeric', 'gt:0', 'max:9999999999999'],
            'rows.*.reference_number' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rows.required' => 'Add at least one expense.',
            'rows.max' => 'Save at most '.self::MAX_ROWS.' expenses at a time.',
            'rows.*.expense_date.before_or_equal' => 'The date cannot be in the future.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'rows.*.project_id' => 'project',
            'rows.*.expense_date' => 'date',
            'rows.*.amount' => 'amount',
            'rows.*.reference_number' => 'reference',
        ];
    }

    /**
     * The reference is either a document number (OR, voucher) or a reference
     * person. A document number may be recorded only once per project, so the
     * same payment is never counted twice; a person's name may repeat. Any
     * reference containing a digit is treated as a document number.
     *
     * @return array<int, Closure>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $rows = collect($this->input('rows', []))
                ->filter(fn ($row): bool => is_array($row) && is_numeric($row['project_id'] ?? null) && self::isDocumentNumber((string) ($row['reference_number'] ?? '')));

            if ($rows->isEmpty()) {
                return;
            }

            $key = fn (mixed $projectId, string $reference): string => (int) $projectId.'|'.mb_strtoupper(trim($reference));

            $recorded = ProjectExpense::query()
                ->whereIn('project_id', $rows->pluck('project_id')->map(fn ($id): int => (int) $id)->unique())
                ->whereNotNull('reference_number')
                ->get(['project_id', 'reference_number'])
                ->mapWithKeys(fn (ProjectExpense $expense): array => [$key($expense->project_id, $expense->reference_number) => true]);
            $seen = [];

            foreach ($rows as $index => $row) {
                $reference = trim((string) $row['reference_number']);
                $rowKey = $key($row['project_id'], $reference);

                if ($recorded->has($rowKey)) {
                    $validator->errors()->add("rows.{$index}.reference_number", "{$reference} is already recorded for this project.");
                } elseif (isset($seen[$rowKey])) {
                    $validator->errors()->add("rows.{$index}.reference_number", "{$reference} is used twice in this batch.");
                }

                $seen[$rowKey] = true;
            }
        }];
    }

    public static function isDocumentNumber(string $reference): bool
    {
        return preg_match('/\d/', $reference) === 1;
    }
}
